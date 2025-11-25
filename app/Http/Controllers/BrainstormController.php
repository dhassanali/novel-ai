<?php

namespace App\Http\Controllers;

use App\Models\Novel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BrainstormController extends Controller
{
    public function generate(Request $request, Novel $novel)
    {
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
        $prompt .= " Provide the output as a simple list.";

        // Mocking AI response for now as I don't have the actual AI service integration details
        // In a real app, this would call the AI service similar to ChapterController

        // Simulating AI delay
        // sleep(1);

        $suggestions = [
            "Idea 1 for {$category}: " . ($context ? "based on {$context}" : "Generic idea"),
            "Idea 2 for {$category}: A twist on the concept.",
            "Idea 3 for {$category}: Something unexpected.",
            "Idea 4 for {$category}: A darker take.",
            "Idea 5 for {$category}: A lighter, more comedic approach.",
        ];

        // If we had the AI service:
        /*
        $response = Http::withToken(config('services.openai.api_key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful creative writing assistant.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        
        $text = $response->json('choices.0.message.content');
        // Parse text into array
        $suggestions = array_filter(explode("\n", $text));
        */

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }
}
