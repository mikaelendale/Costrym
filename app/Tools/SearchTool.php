<?php

namespace App\Tools;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Vizra\VizraADK\Contracts\ToolInterface;
use Vizra\VizraADK\Memory\AgentMemory;
use Vizra\VizraADK\System\AgentContext;

class SearchTool implements ToolInterface
{
    /**
     * Get the tool's definition for the LLM.
     * This structure should be JSON schema compatible.
     */
    public function definition(): array
    {
        return [
            'name' => 'search',
            'description' => 'Search for information or product online using a search tool.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => [
                        'type' => 'string',
                        'description' => 'The search query to look up online.',
                    ],
                ],
                'required' => ['query'],
            ],
        ];
    }

    /**
     * Execute the tool's logic.
     *
     * @param  array  $arguments  Arguments provided by the LLM, matching the parameters defined above.
     * @param  AgentContext  $context  The current agent context, providing access to session state etc.
     * @return string JSON string representation of the tool's result.
     */
    public function execute(array $arguments, AgentContext $context, AgentMemory $memory): string
    {
        // Access arguments: $location = $arguments['location'] ?? null;
        // Access context: $sessionId = $context->getSessionId();
        // Access state: $previousValue = $context->getState('some_key');

        // Access arguments: $query = $arguments['query'] ?? null;

        Log::info('SearchTool called with arguments', $arguments);
        $query = $arguments['query'] ?? null;
        if (! $query) {
            return json_encode([
                'status' => 'error',
                'message' => 'Missing required parameter: query',
            ]);
        }

        try {
            $payload = [
                'query' => $query,
                'limit' => 5,
            ];

            // Note: We intentionally avoid scrapeOptions here because many sites (e.g., YouTube)
            // can fail scraping; we only need the search metadata for the LLM.
            $result = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.config('services.firecrawl.key'),
            ])->post('https://api.firecrawl.dev/v2/search', $payload);

            if (! $result->successful()) {
                Log::warning('SearchTool non-success HTTP response', ['status' => $result->status(), 'body' => $result->body()]);

                return json_encode([
                    'status' => 'error',
                    'message' => 'Search request failed',
                    'http_status' => $result->status(),
                ]);
            }

            $json = $result->json();
            Log::info('search result', $json ?? []);

            $web = $json['data']['web'] ?? [];
            $simplified = array_map(function ($item) {
                return [
                    'title' => $item['title'] ?? null,
                    'url' => $item['url'] ?? null,
                    'description' => $item['description'] ?? null,
                ];
            }, $web);

            return json_encode([
                'status' => 'ok',
                'query' => $query,
                'count' => count($simplified),
                'results' => $simplified,
            ]);

        } catch (Exception $e) {
            Log::error('SearchTool execution error: '.$e->getMessage());

            return json_encode([
                'status' => 'error',
                'message' => 'An error occurred: '.$e->getMessage(),
            ]);
        }
    }
}
