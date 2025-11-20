<?php

namespace App\Tools;

use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

/**
 * KnowledgeBase Tool
 *
 * Allows agents to access user context and business information
 * stored in the knowledge_base table.
 */
class KnowledgeBaseTool implements ToolInterface
{
    protected ?int $userId = null;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
    }

    /**
     * Get the tool's definition for the LLM
     */
    public function definition(): array
    {
        return [
            'name' => 'knowledge_base',
            'description' => 'Access user business context and information from the knowledge base. Use this to get company details, financial goals, business model, products, services, team size, industry, and other relevant context about the user\'s business.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'Optional query to filter specific context (e.g., "company_info", "financial_goals", "products"). If not provided, returns all context.',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    /**
     * Execute the KnowledgeBase query
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        try {
            // Get user ID from context if not set in constructor
            $userId = $this->userId ?? $context->getState('user_id');

            if (! $userId) {
                return json_encode([
                    'success' => false,
                    'error' => 'User ID not found in context. Cannot access knowledge base.',
                ]);
            }

            $query = $arguments['query'] ?? null;

            Log::info('KnowledgeBaseTool: Querying knowledge base', [
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
                    'data' => [],
                    'message' => 'No knowledge base entries found for this user.',
                ]);
            }

            // Merge all context data
            $allContext = [];
            foreach ($knowledgeEntries as $entry) {
                if (is_array($entry->context)) {
                    $allContext = array_merge($allContext, $entry->context);
                }
            }

            // Filter by query if provided
            if ($query) {
                $filteredContext = $this->filterContext($allContext, $query);

                return json_encode([
                    'success' => true,
                    'data' => $filteredContext,
                    'query' => $query,
                    'total_entries' => count($knowledgeEntries),
                ]);
            }

            // Return all context
            return json_encode([
                'success' => true,
                'data' => $allContext,
                'total_entries' => count($knowledgeEntries),
            ]);

        } catch (\Exception $e) {
            Log::error('KnowledgeBaseTool execution error', [
                'user_id' => $userId ?? null,
                'error' => $e->getMessage(),
            ]);

            return json_encode([
                'success' => false,
                'error' => 'Tool execution failed: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Filter context based on query
     */
    protected function filterContext(array $context, string $query): array
    {
        $query = strtolower($query);
        $filtered = [];

        foreach ($context as $key => $value) {
            // Check if key matches query
            if (str_contains(strtolower($key), $query)) {
                $filtered[$key] = $value;

                continue;
            }

            // Check if value matches query (for string values)
            if (is_string($value) && str_contains(strtolower($value), $query)) {
                $filtered[$key] = $value;

                continue;
            }

            // Check nested arrays
            if (is_array($value)) {
                $nestedMatches = $this->searchNestedArray($value, $query);
                if (! empty($nestedMatches)) {
                    $filtered[$key] = $nestedMatches;
                }
            }
        }

        return $filtered;
    }

    /**
     * Search nested arrays for query matches
     */
    protected function searchNestedArray(array $array, string $query): array
    {
        $matches = [];

        foreach ($array as $key => $value) {
            if (is_string($key) && str_contains(strtolower($key), $query)) {
                $matches[$key] = $value;
            } elseif (is_string($value) && str_contains(strtolower($value), $query)) {
                $matches[$key] = $value;
            } elseif (is_array($value)) {
                $nested = $this->searchNestedArray($value, $query);
                if (! empty($nested)) {
                    $matches[$key] = $nested;
                }
            }
        }

        return $matches;
    }
}
