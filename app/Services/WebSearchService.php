<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebSearchService
{
    protected Client $client;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'provider' => env('WEB_SEARCH_PROVIDER', 'duckduckgo'), // duckduckgo, brave, serpapi
            'api_key' => env('WEB_SEARCH_API_KEY'),
            'cache_ttl' => 3600, // 1 hour
            'max_results' => 5,
        ], $config);

        $this->client = new Client([
            'timeout' => 10,
        ]);
    }

    /**
     * Search the web and return results.
     */
    public function search(string $query, int $limit = null): array
    {
        $limit = $limit ?? $this->config['max_results'];
        
        // Check cache first
        $cacheKey = 'web_search_' . md5($query . $limit);
        
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Perform search based on provider
        $results = match ($this->config['provider']) {
            'brave' => $this->searchBrave($query, $limit),
            'serpapi' => $this->searchSerpApi($query, $limit),
            'duckduckgo' => $this->searchDuckDuckGo($query, $limit),
            default => $this->searchDuckDuckGo($query, $limit),
        };

        // Cache results
        Cache::put($cacheKey, $results, $this->config['cache_ttl']);

        return $results;
    }

    /**
     * Search using DuckDuckGo (free, no API key needed).
     */
    protected function searchDuckDuckGo(string $query, int $limit): array
    {
        try {
            // Use DuckDuckGo's instant answer API
            $response = $this->client->get('https://api.duckduckgo.com/', [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'no_html' => 1,
                    'skip_disambig' => 1,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $results = [];

            // Add abstract if available
            if (!empty($data['Abstract'])) {
                $results[] = [
                    'title' => $data['Heading'] ?? 'DuckDuckGo Result',
                    'snippet' => $data['Abstract'],
                    'url' => $data['AbstractURL'] ?? '',
                ];
            }

            // Add related topics
            if (!empty($data['RelatedTopics'])) {
                foreach (array_slice($data['RelatedTopics'], 0, $limit - 1) as $topic) {
                    if (isset($topic['Text']) && isset($topic['FirstURL'])) {
                        $results[] = [
                            'title' => $topic['Text'],
                            'snippet' => $topic['Text'],
                            'url' => $topic['FirstURL'],
                        ];
                    }
                }
            }

            return array_slice($results, 0, $limit);
        } catch (GuzzleException $e) {
            Log::error('DuckDuckGo search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search using Brave Search API.
     */
    protected function searchBrave(string $query, int $limit): array
    {
        if (empty($this->config['api_key'])) {
            throw new \RuntimeException('Brave Search API key not configured');
        }

        try {
            $response = $this->client->get('https://api.search.brave.com/res/v1/web/search', [
                'headers' => [
                    'X-Subscription-Token' => $this->config['api_key'],
                ],
                'query' => [
                    'q' => $query,
                    'count' => $limit,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $results = [];

            if (!empty($data['web']['results'])) {
                foreach ($data['web']['results'] as $result) {
                    $results[] = [
                        'title' => $result['title'] ?? '',
                        'snippet' => $result['description'] ?? '',
                        'url' => $result['url'] ?? '',
                    ];
                }
            }

            return $results;
        } catch (GuzzleException $e) {
            Log::error('Brave search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Search using SerpApi.
     */
    protected function searchSerpApi(string $query, int $limit): array
    {
        if (empty($this->config['api_key'])) {
            throw new \RuntimeException('SerpApi API key not configured');
        }

        try {
            $response = $this->client->get('https://serpapi.com/search', [
                'query' => [
                    'q' => $query,
                    'api_key' => $this->config['api_key'],
                    'num' => $limit,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $results = [];

            if (!empty($data['organic_results'])) {
                foreach ($data['organic_results'] as $result) {
                    $results[] = [
                        'title' => $result['title'] ?? '',
                        'snippet' => $result['snippet'] ?? '',
                        'url' => $result['link'] ?? '',
                    ];
                }
            }

            return $results;
        } catch (GuzzleException $e) {
            Log::error('SerpApi search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Format search results for LLM context.
     */
    public function formatForContext(array $results): string
    {
        if (empty($results)) {
            return "No search results found.";
        }

        $formatted = "Web Search Results:\n\n";
        
        foreach ($results as $i => $result) {
            $formatted .= ($i + 1) . ". " . $result['title'] . "\n";
            $formatted .= "   " . $result['snippet'] . "\n";
            $formatted .= "   URL: " . $result['url'] . "\n\n";
        }

        return $formatted;
    }

    /**
     * Search and return formatted context.
     */
    public function searchAndFormat(string $query, int $limit = null): string
    {
        $results = $this->search($query, $limit);
        return $this->formatForContext($results);
    }
}