<?php

namespace App\Http\Controllers;

use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class BrainstormController extends Controller
{
    use AuthorizesRequests;

    public function generate(Request $request, Novel $novel)
    {
        $this->authorize('view', $novel);
        $request->validate([
            'category' => 'required|string',
            'context' => 'nullable|string',
        ]);

        $category = $request->input('category');
        $context = $request->input('context');

        $prompt = "Brainstorm a list of 5 creative and unique ideas for '{$category}' for a novel.";
        if ($context) {
            $prompt .= " Context: {$context}";
        }
        $prompt .= ' Provide the output as a simple numbered list.';

        $text = \App\Facades\LocalAI::generate($prompt);

        // Split by newlines and filter empty lines
        $lines = array_filter(explode("\n", $text));

        // Clean up lines (remove numbering like "1. ", "- ", etc.)
        $suggestions = array_map(function ($line) {
            return preg_replace('/^[\d\.\-\s]+/', '', trim($line));
        }, $lines);

        // Filter out any empty strings after cleanup
        $suggestions = array_values(array_filter($suggestions));

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }
}
