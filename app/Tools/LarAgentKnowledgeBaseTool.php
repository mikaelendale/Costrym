<?php

namespace App\Tools;

use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Log;
use LarAgent\Tool;

/**
 * LarAgent-Compatible KnowledgeBase Tool
 *
 * Allows LarAgent agents to access user context and business information
 * stored in the knowledge_base table.
 */
class LarAgentKnowledgeBaseTool extends Tool
{
    protected string $name = 'knowledge_base';

    protected string $description = 'Access user business context and information from the knowledge base. Use this to get company details, financial goals, business model, products, services, team size, industry, and other relevant context about the user\'s business.';

    protected array $properties = [
        'query' => [
            'type' => 'string',
            'description' => 'Optional query to filter specific context (e.g., "company_info", "financial_goals", "products"). If not provided, returns all context.',
        ],
    ];

    protected array $required = [];
    public function execute(array $input): mixed
    {
        try {
            // Get user_id from the global agent context
            // LarAgent passes metadata through app() container
            $userId = app('laragent.user_id') ?? null;

            if (!$userId) {
                return json_encode([
                    'success' => false,
                    'error' => 'User context not available. This tool requires user authentication.',
                ]);
            }

            $query = $input['query'] ?? null;

            Log::info('LarAgentKnowledgeBaseTool: Querying knowledge base', [
                'user_id' => $userId,
                'query' => $query,
            ]);

            // Get all knowledge base entries for this user
            $knowledgeEntries = KnowledgeBase::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            if ($knowledgeEntries->isEmpty()) {
                return json_encode([
                    'success' => true,
                    'context' => 'No knowledge base entries found for this user.',
                ]);
            }

            // Collect all context text from entries
            $contextTexts = [];
            foreach ($knowledgeEntries as $entry) {
                // The context is stored as JSON array in the database
                if (is_array($entry->context)) {
                    // Convert array to readable text format
                    foreach ($entry->context as $key => $value) {
                        if (is_array($value)) {
                            $contextTexts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . json_encode($value);
                        } else {
                            $contextTexts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                        }
                    }
                }
            }

            // Join all context into a single text block
            $fullContext = implode("\n", $contextTexts);

            // Filter by query if provided
            if ($query) {
                $queryLower = strtolower($query);
                $filteredLines = array_filter($contextTexts, function($line) use ($queryLower) {
                    return str_contains(strtolower($line), $queryLower);
                });
                
                $filteredContext = implode("\n", $filteredLines);

                return json_encode([
                    'success' => true,
                    'context' => $filteredContext ?: 'No matching context found for query: ' . $query,
                    'query' => $query,
                ], JSON_PRETTY_PRINT);
            }

            // Return all context as plain text
            return json_encode([
                'success' => true,
                'context' => $fullContext,
            ], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            Log::error('LarAgentKnowledgeBaseTool execution error', [
                'user_id' => $userId ?? null,
                'error' => $e->getMessage(),
            ]);

            return json_encode([
                'success' => false,
                'error' => 'Tool execution failed: ' . $e->getMessage(),
            ]);
        }
    }


}
