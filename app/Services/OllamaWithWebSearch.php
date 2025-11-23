<?php

namespace App\Services;

use App\Services\OllamaService;
use App\Services\WebSearchService;

class OllamaWithWebSearch
{
    protected OllamaService $ollama;
    protected WebSearchService $search;

    public function __construct(OllamaService $ollama, WebSearchService $search)
    {
        $this->ollama = $ollama;
        $this->search = $search;
    }

    /**
     * Generate with automatic web search when needed.
     */
    public function generateWithSearch(string $prompt, ?string $model = null): string
    {
        // First, ask if web search is needed
        $checkPrompt = "Does this question require current information from the web to answer accurately? Answer only YES or NO.\n\nQuestion: {$prompt}";
        
        $needsSearch = $this->ollama->generate($checkPrompt, $model);
        
        if (stripos($needsSearch, 'yes') !== false) {
            // Extract search query
            $queryPrompt = "Extract the main search query from this question. Return only the search terms, nothing else.\n\nQuestion: {$prompt}";
            $searchQuery = $this->ollama->generate($queryPrompt, $model);
            
            // Perform web search
            $searchResults = $this->search->searchAndFormat(trim($searchQuery));
            
            // Generate answer with search context
            $enhancedPrompt = "Based on the following web search results, answer the question.\n\n";
            $enhancedPrompt .= $searchResults . "\n\n";
            $enhancedPrompt .= "Question: {$prompt}\n\n";
            $enhancedPrompt .= "Answer based on the search results above:";
            
            return $this->ollama->generate($enhancedPrompt, $model);
        }
        
        // No search needed, generate normally
        return $this->ollama->generate($prompt, $model);
    }

    /**
     * Force web search and generate answer.
     */
    public function searchAndGenerate(string $query, string $prompt, ?string $model = null): string
    {
        $searchResults = $this->search->searchAndFormat($query);
        
        $enhancedPrompt = "Based on the following web search results:\n\n";
        $enhancedPrompt .= $searchResults . "\n\n";
        $enhancedPrompt .= "Question: {$prompt}\n\n";
        $enhancedPrompt .= "Answer:";
        
        return $this->ollama->generate($enhancedPrompt, $model);
    }

    /**
     * Research mode: multiple searches and comprehensive answer.
     */
    public function research(string $topic, ?string $model = null): string
    {
        // Generate search queries
        $queriesPrompt = "Generate 3 different search queries to research this topic thoroughly:\n\n{$topic}\n\nReturn only the queries, one per line.";
        $queries = explode("\n", trim($this->ollama->generate($queriesPrompt, $model)));
        
        // Perform multiple searches
        $allResults = [];
        foreach (array_slice($queries, 0, 3) as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $allResults[] = $this->search->searchAndFormat($query, 3);
            }
        }
        
        // Combine results and generate comprehensive answer
        $context = implode("\n\n---\n\n", $allResults);
        
        $researchPrompt = "Based on all the web search results below, provide a comprehensive answer about: {$topic}\n\n";
        $researchPrompt .= $context . "\n\n";
        $researchPrompt .= "Provide a detailed, well-structured answer:";
        
        return $this->ollama->generate($researchPrompt, $model);
    }
}