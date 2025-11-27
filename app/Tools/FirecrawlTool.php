<?php

namespace App\Tools;

use JPCaparas\LaravelFirecrawl\Facades\LaravelFirecrawl;
use LarAgent\Tool;

class FirecrawlTool extends Tool
{
    protected string $name = 'web-related_operations';

    protected string $description = 'Perform web scraping, crawling, mapping, extraction, and search operations using Firecrawl. Use this tool when you need to scrape a website, crawl a website, map a website, extract data from a website, or search the web.';

    protected array $properties = [
        'operation' => [
            'type' => 'string',
            'description' => 'The operation to perform',
            'enum' => ['map', 'extract', 'crawl', 'scrape', 'search'],
        ],
        'url' => [
            'type' => 'string',
            'description' => 'The URL to process (required for map, extract, crawl, scrape)',
        ],
        'query' => [
            'type' => 'string',
            'description' => 'Search query (required for search operation)',
        ],
        'prompt' => [
            'type' => 'string',
            'description' => 'Custom prompt for extract operation (optional)',
        ],
        'schema' => [
            'type' => 'object',
            'description' => 'JSON schema for extract operation (optional)',
        ],
        'enableWebSearch' => [
            'type' => 'boolean',
            'description' => 'Enable web search for extract operation (optional)',
        ],
        'options' => [
            'type' => 'object',
            'description' => 'Additional options for the operation',
        ],
    ];

    protected array $required = ['operation'];

    public function execute(array $input): mixed
    {
        $operation = $input['operation'];
        $url = $input['url'] ?? null;
        $query = $input['query'] ?? null;
        $prompt = $input['prompt'] ?? null;
        $schema = $input['schema'] ?? null;
        $enableWebSearch = $input['enableWebSearch'] ?? null;
        $options = $input['options'] ?? [];

        // Merge specific parameters into options for extract operation
        if ($operation === 'extract') {
            if ($prompt) {
                $options['prompt'] = $prompt;
            }
            if ($schema) {
                $options['schema'] = $schema;
            }
            if ($enableWebSearch !== null) {
                $options['enableWebSearch'] = $enableWebSearch;
            }
        }

        try {
            switch ($operation) {
                case 'map':
                    if (! $url) {
                        return 'Error: URL is required for map operation';
                    }

                    return $this->mapWebsite($url, $options);

                case 'extract':
                    if (! $url) {
                        return 'Error: URL is required for extract operation';
                    }

                    return $this->extractData($url, $options);

                case 'crawl':
                    if (! $url) {
                        return 'Error: URL is required for crawl operation';
                    }

                    return $this->crawlWebsite($url, $options);

                case 'scrape':
                    if (! $url) {
                        return 'Error: URL is required for scrape operation';
                    }

                    return $this->scrapePage($url, $options);

                case 'search':
                    if (! $query) {
                        return 'Error: Query is required for search operation';
                    }

                    return $this->searchWeb($query, $options);

                default:
                    return 'Error: Invalid operation. Must be one of: map, extract, crawl, scrape, search';
            }
        } catch (\Exception $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    private function mapWebsite(string $url, array $options): string
    {
        $result = LaravelFirecrawl::map()->map($url, $options);

        return "Map operation completed for: {$url}\n\nResults:\n".json_encode($result, JSON_PRETTY_PRINT);
    }

    private function extractData(string $url, array $options): string
    {
        // Extract requires specific parameters: urls array, prompt, and schema
        $urls = [$url];
        $prompt = $options['prompt'] ?? 'Extract the main content, title, and any relevant information from this webpage';
        $schema = $options['schema'] ?? [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'content' => ['type' => 'string'],
                'links' => ['type' => 'array'],
            ],
        ];
        $enableWebSearch = $options['enableWebSearch'] ?? false;

        $result = LaravelFirecrawl::extract()->extract($urls, $prompt, $schema, $enableWebSearch);

        return "Extract operation completed for: {$url}\n\nExtracted Data:\n".json_encode($result, JSON_PRETTY_PRINT);
    }

    private function crawlWebsite(string $url, array $options): string
    {
        $result = LaravelFirecrawl::crawl()->crawl($url, $options);

        return "Crawl operation completed for: {$url}\n\nCrawled Data:\n".json_encode($result, JSON_PRETTY_PRINT);
    }

    private function scrapePage(string $url, array $options): string
    {
        $result = LaravelFirecrawl::scrape()->scrape($url, $options);

        return "Scrape operation completed for: {$url}\n\nScraped Content:\n".json_encode($result, JSON_PRETTY_PRINT);
    }

    private function searchWeb(string $query, array $options): string
    {
        $result = LaravelFirecrawl::search()->search($query, $options);

        return "Search operation completed for query: {$query}\n\nSearch Results:\n".json_encode($result, JSON_PRETTY_PRINT);
    }
}
