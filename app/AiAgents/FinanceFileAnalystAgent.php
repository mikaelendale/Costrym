<?php

namespace App\AiAgents;

use LarAgent\Agent;

/**
 * Finance File Analyst Agent
 * 
 * Analyzes financial data files (Excel/CSV) to determine:
 * - Monthly transaction volume
 * - Whether user meets minimum requirements ($1000+ monthly transactions)
 * - Key financial metrics and patterns
 * 
 * Uses structured output for consistent analysis results.
 */
class FinanceFileAnalystAgent extends Agent
{
    protected $model = 'gpt-4o-mini';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    /**
     * Structured output schema for financial analysis
     */
    protected $responseSchema = [
        'name' => 'financial_analysis',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'meets_requirement' => [
                    'type' => 'boolean',
                    'description' => 'Whether the company has monthly transactions above $1000',
                ],
                'monthly_transaction_amount' => [
                    'type' => 'number',
                    'description' => 'Average monthly transaction amount in USD',
                ],
                'total_monthly_transactions' => [
                    'type' => 'number',
                    'description' => 'Total number of monthly transactions',
                ],
                'analysis_summary' => [
                    'type' => 'string',
                    'description' => 'Brief summary of the financial data analysis (2-3 sentences)',
                ],
                'key_metrics' => [
                    'type' => 'object',
                    'description' => 'Key financial metrics extracted from the data',
                    'properties' => [
                        'total_revenue' => ['type' => 'number'],
                        'total_expenses' => ['type' => 'number'],
                        'net_income' => ['type' => 'number'],
                        'transaction_count' => ['type' => 'number'],
                    ],
                    'required' => ['total_revenue', 'total_expenses', 'net_income', 'transaction_count'],
                    'additionalProperties' => false,
                ],
            ],
            'required' => ['meets_requirement', 'monthly_transaction_amount', 'total_monthly_transactions', 'analysis_summary', 'key_metrics'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions()
    {
        return "You are a financial data analyst specializing in analyzing company financial records from Excel and CSV files.

Your primary task is to:
1. Analyze the provided financial data (transactions, income, expenses, etc.)
2. Calculate the average monthly transaction amount
3. Determine if the company has monthly transactions above $1000
4. Extract key financial metrics (revenue, expenses, net income, transaction count)
5. Provide a brief analysis summary

IMPORTANT INSTRUCTIONS:
- Look for transaction data, income statements, expense records, or general ledger entries
- Calculate monthly averages based on available time periods in the data
- If the data spans multiple months, calculate the average monthly transaction amount
- If the data is for a single month, use that month's data
- Consider all types of transactions: revenue, expenses, transfers, etc.
- If transaction amounts are not clearly labeled, infer from context (positive amounts = revenue, negative = expenses)
- Be conservative in your estimates - only count clear financial transactions
- If the data is insufficient or unclear, set meets_requirement to false and explain in analysis_summary

The meets_requirement field should be true ONLY if the average monthly transaction amount is $1000 or more.";
    }

    public function prompt($message)
    {
        return $message;
    }
}
