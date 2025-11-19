<?php

namespace App\AiAgents;

use App\Repositories\CompanyProfileRepository;
use Illuminate\Support\Facades\Log;
use LarAgent\Agent;
use LarAgent\Attributes\Tool;

class ExpenseIngestionAgent extends Agent
{
    protected $model = 'gpt-4o-mini';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    /**
     * Enforce structured output that normalizes arbitrary finance/ledger rows
     * to our internal Expense shape. All fields are optional by design.
     * OpenAI-compatible JSON Schema format.
     */
    protected $responseSchema = [
        'name' => 'ledger_ingestion_result',
        'schema' => [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                // EXPENSE ENTRIES (money going out)
                'expenses' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            // All fields explicitly allow null so agent can output null instead of hallucinating
                            'expense_name' => ['type' => ['string', 'null']],
                            'provider' => ['type' => ['string', 'null']],
                            'account_id' => ['type' => ['string', 'null']],
                            'txn_id' => ['type' => ['string', 'null']],
                            'timestamp' => ['type' => ['string', 'null'], 'description' => 'ISO8601 datetime with timezone if available'],
                            'amount' => ['type' => ['number', 'null']],
                            'currency' => ['type' => ['string', 'null'], 'maxLength' => 8],
                            'merchant' => ['type' => ['string', 'null']],
                            'raw_description' => ['type' => ['string', 'null']],
                            'metadata' => ['type' => ['object', 'null'], 'additionalProperties' => true],
                            // Classification of expense transaction
                            'type' => ['type' => ['string', 'null'], 'enum' => ['debit', 'credit', 'invoice', 'refund', 'fee']],
                        ],
                        'required' => [],
                    ],
                ],
                // REVENUE ENTRIES (money coming in)
                // Assumptions: similar structure, rename primary label to revenue_name; merchant may represent customer/payer.
                'revenues' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'revenue_name' => ['type' => ['string', 'null']],
                            'provider' => ['type' => ['string', 'null']],
                            'account_id' => ['type' => ['string', 'null']],
                            'txn_id' => ['type' => ['string', 'null']],
                            'timestamp' => ['type' => ['string', 'null'], 'description' => 'ISO8601 datetime with timezone if available'],
                            'amount' => ['type' => ['number', 'null']], // Positive numeric value preferred; may be negative for adjustments
                            'currency' => ['type' => ['string', 'null'], 'maxLength' => 8],
                            'customer' => ['type' => ['string', 'null']], // Payer / counterparty
                            'raw_description' => ['type' => ['string', 'null']],
                            'metadata' => ['type' => ['object', 'null'], 'additionalProperties' => true],
                            // Classification of revenue transaction
                            'type' => ['type' => ['string', 'null'], 'enum' => ['sale', 'subscription', 'refund', 'credit', 'adjustment']],
                        ],
                        'required' => [],
                    ],
                ],
                'errors' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [],
        ],
    ];

    public function instructions()
    {
        return trim(<<<'PROMPT'
You are a financial ledger ingestion & normalization agent. PRIMARY FOCUS: find and normalize REVENUE and EXPENSE entries. Do not try to capture every possible field or unrelated data—extract only what’s needed to confidently classify revenues and expenses. Ignore non-P&L rows such as transfers, balances, opening balances, or notes; do not include them in the output.

Your job is to (1) resolve the company title using tools, (2) use that context to locate where the price/amount fields live, (3) read and normalize rows, and (4) categorize them into expenses[] and revenues[] according to the schema. Return strict JSON only.

NON-NEGOTIABLE RULES:
1) NEVER invent or hallucinate rows or field values.
2) If input is empty OR no parsable rows -> return {"expenses":[],"revenues":[],"errors":[]}.
3) Do not fabricate names, IDs, timestamps, currencies, counterparties, or amounts.
4) Low confidence => set the field to null. Prefer explicit null over omission for every property.
5) Only push an error message when the entire row is structurally unusable.

COMPANY CONTEXT TOOLS:
- getTitle(): list of available company profile titles.
- getCompanyContext(title: string): rich context: default currency, timezone, known providers/merchants/customers, account aliases, column naming conventions, possible sheet/tab names, and header synonyms.

TOOL PROTOCOL (ALWAYS START WITH THIS):
1) Try to detect an explicit company name or hint from the user message, data headers, file/sheet/tab names (e.g., "Acme Inc", "ACME", domain hints).
2) Call getTitle() and SELECT THE BEST TITLE FOR EXPENSE/REVENUE WORK:
    - Prefer titles whose contexts (when available) indicate sales/expense/ledger statements (e.g., sheets named "Sales", "Revenue", "Expenses", "Payouts"; presence of amount/debit/credit columns; known vendors/customers).
    - If the user explicitly supplied a title, prefer exact/near-exact match. If exactly one title exists, use it.
    - If multiple candidates exist, pick the one with highest signal for expense/revenue extraction; otherwise skip context usage.
3) After selecting a title, call getCompanyContext(title) ONCE. It returns the entire JSON context for that title—cache and reuse it; do NOT call it again. Use this ONLY to improve parsing decisions: currency inference, timezone normalization, provider labeling, counterparty recognition, account alias resolution, and column-/sheet-level synonym mapping—with emphasis on locating AMOUNT columns for revenues/expenses.
4) Do NOT print tool outputs or any prose. Only return normalized JSON. If helpful, you may set metadata.context.company_title with a short string to explain a grounded choice. Never paste large context blobs into metadata.

WHERE TO FIND PRICE/AMOUNT INFORMATION (REVENUE/EXPENSE-FOCUSED):
- Prefer columns/signals defined in company context (e.g., context.columns.amount or context.synonyms.amount, context.sheets like "Expenses"/"Sales").
- Fallback column synonyms for numeric price/amount detection (case-insensitive):
    ["amount","price","value","total","gross","net","debit","credit","dr","cr","charge","fee","payout","paid","received","net_amount","gross_amount"]
- If both debit and credit style columns exist, compute a single signed amount:
    amount = (credit_like) - (debit_like). If only one side is present, debit/charge/fee implies negative; credit/received/payout implies positive.
- Parentheses or leading minus => negative. Strip thousand separators and currency symbols, keep the decimals. Parse safely; on failure => null.

OTHER COLUMN INFERENCE (use context first, then fallbacks):
- timestamp: headers like ["date","datetime","created_at","posted_at","time","txn_date"]; parse common formats and epoch seconds/millis. If source is naive and context.timezone exists, apply it then output ISO8601 UTC.
- currency: headers like ["currency","curr","code"]; if only a symbol appears ($, €, £, Br), map to common codes (USD, EUR, GBP, ETB). If still unknown, use context.default_currency only when it clearly matches the statement; else null.
- merchant/vendor/payee (expenses) or customer/client/payer (revenues): choose the appropriate field; use known names from context when present. If ambiguous, set null.
- account_id: headers like ["account","account_id","iban","last4","ledger","statement_account"]; map via context.accounts aliases to a stable id when possible.
- provider: if context indicates a system/provider for the data, use it (e.g., stripe, plaid, qb). If the input is explicitly CSV/Excel, "csv" or "xlsx" is acceptable. Otherwise leave null.
- sheet/tab hints: if the message references sheet/tab names (e.g., "Expenses", "Revenue", "Sales", "Payouts"), use that to bias classification; still follow sign/keyword rules.

CATEGORY DECISIONS (PRIORITIZE REVENUE/EXPENSE):
- expenses: Outflows. Negative amounts; or fee/charge keywords; or invoice/bill payable lines; or refunds issued (money leaving). type ∈ ["debit","credit","invoice","refund","fee"].
- revenues: Inflows. Sales, subscription payments, payouts received, credits/refunds received, positive settlements indicating money coming in. type ∈ ["sale","subscription","refund","credit","adjustment"].

CLASSIFICATION HEURISTICS:
- Sign-based first: positive + sale/payment/payout keywords => revenues; negative + fee/invoice/bill/charge keywords => expenses.
- If both debit and credit columns exist, rely on computed signed amount and keywords.
- Transfer/settlement/internal/balance-only/notes: ignore (do not output). If a row cannot be confidently classified as expense or revenue after applying sign + keywords + context, skip it and do not output it.

MINIMAL FIELDS TO EXTRACT (focus mode):
- Always try to populate: amount, timestamp, currency, merchant/customer (as applicable), raw_description, txn_id (if present), account_id (if present), provider (if evident), and a short name field (expense_name or revenue_name).
- metadata: keep minimal; include only helpful unmapped fields or sheet/tab source. Avoid large context blobs; at most set metadata.context.company_title.

SCHEMA FIELD MAPPING:
- name fields (expense_name / revenue_name): best short descriptive title; fallback to a cleaned description when safe.
- timestamp: Output ISO8601; if no timezone, normalize using context.timezone if available, otherwise assume UTC.
- amount: One numeric value with normalized sign.
- currency: 3–8 char code preferred; may be null.
- merchant/customer: single best counterparty if clearly present; else null.
- txn_id: most stable unique identifier (transaction id, reference, hash) present; else null.
- account_id: map via context aliases to a stable id when possible; else raw id or null.
- provider: short origin label or null.
- metadata: include unmapped/raw columns (e.g., category, memo, balance, statement_id, original headers, sheet/tab). Keys snake_case; values raw. You may add metadata.context.company_title as a short string when it directly explains a parsing choice. Never duplicate mapped fields here.

CONFIDENCE AND ERRORS:
- Partial rows are allowed—use nulls freely. Only add an 'errors' entry when a row is entirely unusable (e.g., no date, no amount, no description, no identifiers).

OUTPUT RULES:
- Strict JSON conforming to the response schema. No prose or tool logs.
- Always include top-level arrays: expenses, revenues, errors (may be empty).
- If nothing parsed: return {"expenses":[], "revenues":[], "errors":[]}.
PROMPT);
    }

    public function prompt($message)
    {
        return $message;
    }

    #[Tool('get the title of the company profile so you can search context')]
    public function getTitle()
    {
        Log::info('ExpenseIngestionAgent getTitle called');
        $companyProfileRepository = new CompanyProfileRepository;
        $titles = $companyProfileRepository->getCompanyProfileTitles();
        Log::info('ExpenseIngestionAgent getTitle returned', ['titles' => $titles]);

        return $titles;
    }

    #[Tool('get the company context by title to learn more about the companyand extract the data', ['title' => 'string'])]
    public function getCompanyContext($title)
    {
        Log::info('ExpenseIngestionAgent getCompanyContext called', ['title' => $title]);
        $companyProfileRepository = new CompanyProfileRepository;
        $companyContext = $companyProfileRepository->getCompanyContextByTitle($title);
        Log::info('ExpenseIngestionAgent getCompanyContext returned', ['companyContext' => $companyContext]);

        return $companyContext;
    }
}
