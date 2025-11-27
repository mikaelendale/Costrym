<?php

namespace App\AiAgents;

use LarAgent\Agent;

/**
 * JSON Ingestion Agent
 *
 * Analyzes the structure of a JSON file (converted from Excel) to identify:
 * 1. The sheet/key containing the main transaction data.
 * 2. The mapping of columns to our standard schema (date, amount, description).
 */
class JsonIngestionAgent extends Agent
{
    protected $model = 'gpt-4o-mini';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    /**
     * Structured output schema for file mapping
     */
    protected $responseSchema = [
        'name' => 'file_structure_mapping',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'main_data_key' => [
                    'type' => 'string',
                    'description' => 'The key/sheet name containing the transaction list. If root array, use "ROOT".',
                ],
                'is_valid_financial_file' => [
                    'type' => 'boolean',
                    'description' => 'Whether this file appears to contain financial transaction data.',
                ],
                'column_mapping' => [
                    'type' => 'object',
                    'description' => 'Mapping of source columns to destination fields',
                    'properties' => [
                        'date' => ['type' => 'string', 'description' => 'Column name for transaction date'],
                        'amount' => ['type' => 'string', 'description' => 'Column name for transaction amount'],
                        'description' => ['type' => 'string', 'description' => 'Column name for description/memo'],
                        'payee' => ['type' => ['string', 'null'], 'description' => 'Column name for payee/merchant (optional, return null if not found)'],
                        'currency' => ['type' => ['string', 'null'], 'description' => 'Column name for currency (optional, return null if not found)'],
                    ],
                    'required' => ['date', 'amount', 'description', 'payee', 'currency'],
                    'additionalProperties' => false,
                ],
                'confidence' => [
                    'type' => 'string',
                    'enum' => ['high', 'medium', 'low'],
                ],
            ],
            'required' => ['main_data_key', 'is_valid_financial_file', 'column_mapping', 'confidence'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions()
    {
        return "You are a Data Engineering AI specialist.
        
Your task is to analyze a sample of a JSON file (which represents an Excel workbook or CSV) and determine how to extract financial transactions from it.

1.  **Identify the Data Source**: Look at the keys (sheet names). Find the one that looks like a list of transactions (e.g., 'Sheet1', 'Transactions', 'Expenses').
2.  **Map Columns**: Look at the keys in the first few objects of that list. Identify which keys correspond to:
    *   **Date**: Look for 'Date', 'Time', 'Day', etc.
    *   **Amount**: Look for 'Amount', 'Debit', 'Credit', 'Value', 'Cost', etc.
    *   **Description**: Look for 'Description', 'Memo', 'Details', 'Narration', etc.
3.  **Validation**: Ensure the data actually looks like financial records.

Return a structured mapping object that the system can use to programmatically extract the data.";
    }

    public function prompt($message)
    {
        return $message;
    }
}
