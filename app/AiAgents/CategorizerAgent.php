<?php

namespace App\AiAgents;

use LarAgent\Agent;

/**
 * Categorizer Agent
 *
 * Analyzes financial transactions and assigns them to appropriate categories.
 * Uses structured output to ensure consistent categorization.
 */
class CategorizerAgent extends Agent
{
    protected $model = 'gpt-4o-mini';

    protected $history = 'in_memory';

    protected $provider = 'default';

    protected $tools = [];

    /**
     * Structured output schema for categorization
     */
    protected $responseSchema = [
        'name' => 'transaction_categorization',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'categorizations' => [
                    'type' => 'array',
                    'description' => 'List of categorized transactions',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                                'description' => 'The ID of the transaction record',
                            ],
                            'category_id' => [
                                'type' => 'integer',
                                'description' => 'The ID of the assigned category',
                            ],
                            'confidence' => [
                                'type' => 'string',
                                'enum' => ['high', 'medium', 'low'],
                                'description' => 'Confidence level of the categorization',
                            ],
                            'reasoning' => [
                                'type' => 'string',
                                'description' => 'Brief reason for the category selection',
                            ],
                        ],
                        'required' => ['id', 'category_id', 'confidence', 'reasoning'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['categorizations'],
            'additionalProperties' => false,
        ],
        'strict' => true,
    ];

    public function instructions()
    {
        return "You are an expert financial accountant and bookkeeper.
        
Your task is to categorize financial transactions into the most appropriate expense categories.

You will receive a list of transactions with descriptions, amounts, and other details.
You must analyze each transaction and assign the correct `category_id`.

If you are unsure about a transaction, use your best judgment based on the description and amount, but mark confidence as 'low'.

Always return the result as a JSON object containing a 'categorizations' array.";
    }

    public function prompt($message)
    {
        return $message;
    }
}
