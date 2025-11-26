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

        // $text = \App\Facades\LocalAI::generate($prompt);
        // $suggestions = array_filter(explode("\n", $text));

        $response = Http::withToken(config('services.openai.api_key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful creative writing assistant.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        $text = $response->json('choices.0.message.content');
        $suggestions = array_filter(explode("\n", $text));


        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }
}
