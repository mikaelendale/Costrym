<?php

namespace App\Tools;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LarAgent\Tool;

/**
 * @deprecated This tool is deprecated. Use FirecrawlTool with operation='search' instead.
 * @see \App\Tools\FirecrawlTool
 */
class SearchTool extends Tool
{
    public string $name = 'search';
    public string $description = 'Search for information or product online using a search tool.';
    public array $schema = [
        'type' => 'object',
        'properties' => [
            'query' => [
                'type' => 'string',
                'description' => 'The search query to look up online.',
            ],
        ],
        'required' => ['query'],
    ];

    public function execute(array $arguments): string
    {
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
