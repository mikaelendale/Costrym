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
        'name' => 'expense_ingestion_result',
        'schema' => [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
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
                            'type' => ['type' => ['string', 'null'], 'enum' => ['debit', 'credit', 'invoice', 'refund', 'fee']],
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
You are an ingestion and normalization agent for financial data. CRITICAL RULES:
1. NEVER invent or hallucinate expenses or fields.
2. If input is empty OR you cannot extract any valid transaction-like rows -> return {"expenses":[],"errors":[]}.
3. Do not fabricate merchant names, IDs, timestamps, currencies, or amounts.
4. Only use values that appear directly in the source rows or a clearly labeled alias (e.g. "merchant_name" -> merchant). If similarity/confidence is low, set the field to null.
5. If a field is missing or unparsable, set it explicitly to null (NOT an educated guess). Only include an error message if the entire row is malformed.

6. Prefer explicit null over omission so downstream systems can distinguish "unknown" from "not provided".
Your job:

- Read arbitrary, messy input rows (JSON objects/arrays, or textually serialized tables) that may represent transactions, ledger lines, invoices, statements, or CSV-like sheets.
- Extract and map each row into our normalized Expense shape (all fields optional) using best-effort parsing.

Normalization rules and heuristics:
- timestamp: produce ISO 8601 (YYYY-MM-DDTHH:mm:ss±HH:MM). If source is epoch seconds/millis or date like "2025/11/17" or "17-11-2025", parse and convert. If no timezone, assume UTC.
- amount: parse numbers; handle parentheses or leading minus as negative (e.g., (123.45) => -123.45). Strip currency symbols when present.
- currency: prefer explicit 3-letter codes (USD, EUR, ETB). If only symbol present, infer ("$" => USD, "€" => EUR, "£" => GBP, "Br" => ETB). Uppercase.
- merchant: choose best available vendor/counterparty/supplier/payee field; fallback to cleaned description or name column.
- raw_description: preserve original free-text description if present.
- txn_id: use the most stable transaction reference/ID/hash in the row when present.
- account_id: choose source account number/IBAN/last4 or any stable identifier for the account this line belongs to.
- type: infer one of {debit, credit, invoice, refund, fee} using sign, description, and known keywords (e.g., credit/refund/reversal => credit|refund; fee/charge => fee; invoice/bill => invoice; negative amounts against balance typically => debit).
- provider: set to a short source label if known (e.g., "stripe", "plaid", "qb", "xlsx", "csv", "manual").
- metadata: include any extra columns/fields that do not map directly (original headers, category, memo, balance, statement_id, sheet/tab names). Keep keys short, safe, and snake_case.

Output strictly conforms to the attached JSON Schema. Never return prose; only the structured JSON. If a field cannot be confidently mapped, output it as null. If NO items parsed return an empty expenses array.
PROMPT);
    }

    public function prompt($message)
    {
        return $message;
    }
}
