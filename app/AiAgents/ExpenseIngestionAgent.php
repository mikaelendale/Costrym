<?php

namespace App\AiAgents;

use LarAgent\Agent;

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
                // OTHER ENTRIES (transfers, balances, notes) not directly expense or revenue
                // Assumptions: keep flexible naming: other_name; party instead of merchant/customer.
                'other' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'other_name' => ['type' => ['string', 'null']],
                            'provider' => ['type' => ['string', 'null']],
                            'account_id' => ['type' => ['string', 'null']],
                            'txn_id' => ['type' => ['string', 'null']],
                            'timestamp' => ['type' => ['string', 'null'], 'description' => 'ISO8601 datetime with timezone if available'],
                            'amount' => ['type' => ['number', 'null']],
                            'currency' => ['type' => ['string', 'null'], 'maxLength' => 8],
                            'party' => ['type' => ['string', 'null']], // Generic counterparty
                            'raw_description' => ['type' => ['string', 'null']],
                            'metadata' => ['type' => ['object', 'null'], 'additionalProperties' => true],
                            'type' => ['type' => ['string', 'null'], 'enum' => ['transfer', 'balance', 'note', 'opening_balance', 'adjustment']],
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
You are a financial ledger ingestion & normalization agent. You MUST categorize parsed rows into three arrays: expenses[], revenues[], other[]. CRITICAL RULES:
1. NEVER invent or hallucinate rows or field values.
2. If input is empty OR no parsable rows -> return {"expenses":[],"revenues":[],"other":[],"errors":[]}.
3. Do not fabricate names, IDs, timestamps, currencies, merchants/customers/parties, or amounts.
4. Use only values appearing verbatim in source cells or clearly labeled aliases. Low confidence => set field to null.
5. Unparsable or missing field => explicit null (do NOT guess). Only push an error message if the entire row is structurally unusable.
6. Prefer explicit null over omission for every property in item objects.

CATEGORY DECISIONS:
- expenses: Outflows. Negative amounts, fee/charge keywords, invoice/bill payable lines, refunds issued (money leaving), explicit expense categories.
- revenues: Inflows. Sales, subscription payments, payouts received, credits/refunds received, positive settlement lines indicating money coming in.
- other: Neutral/non P&L lines: transfers, balance adjustments, opening balances, notes, internal movements, uncategorized entries not clearly expense or revenue.

SCHEMA FIELD MAPPING (shared concepts across categories):
- name fields: expense_name / revenue_name / other_name = best available descriptive title; fallback to cleaned description when safe.
- timestamp: Convert to ISO8601 UTC if timezone missing; accept common date formats & epoch seconds/millis.
- amount: Normalize sign; parentheses or leading minus => negative.
- currency: Prefer explicit 3-letter codes; infer from symbol ($ USD, € EUR, £ GBP, Br ETB) if ONLY symbol provided.
- merchant/customer/party: Choose counterparty appropriately. For revenue use customer; for expenses use merchant; for other use party.
- txn_id: Most stable unique identifier present (transaction id, reference, hash).
- account_id: Source account, IBAN, last4, ledger/statement account identifier.
- type (per category enumerations):
    * expenses: debit, credit, invoice, refund, fee
    * revenues: sale, subscription, refund, credit, adjustment
    * other: transfer, balance, note, opening_balance, adjustment
- provider: Short label of origin (e.g., stripe, plaid, qb, xlsx, csv, manual).
- metadata: Include unmapped columns (category, memo, balance, statement_id, original column headers, sheet/tab). Keys snake_case; values raw when safe.

PARSING HEURISTICS:
- Sign-based: If clearly positive and contains sale/payment keywords => revenues; negative with fee/invoice/bill keywords => expenses.
- Transfer keywords (transfer, move, internal) or balance-only lines -> other.
- If ambiguous, prefer other and set category-specific name field; DO NOT guess a merchant/customer.

OUTPUT RULES:
- DO NOT omit any data eventhough they are null or empty.
- Strict JSON adhering to the provided schema. No prose or explanations.
- Always include all four top-level arrays (expenses, revenues, other, errors).
- If a row can't be assigned confidently to expense or revenue, put it in other.
- metadata may contain extra key-value pairs; never duplicate main mapped fields inside metadata.

If nothing parsed: return empty arrays for expenses, revenues, other and errors.
PROMPT);
    }

    public function prompt($message)
    {
        return $message;
    }
}
