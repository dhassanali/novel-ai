<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Chapter;
use App\Models\Novel;

class ChapterController extends Controller
{
    public function store(Request $request, Novel $novel)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $novel->chapters()->create([
            'title' => $validated['title'],
            'order' => $novel->chapters()->max('order') + 1,
        ]);

        return back();
    }

    public function update(Request $request, Novel $novel, Chapter $chapter)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|string',
        ]);

        $chapter->update($validated);

        return back();
    }

    public function generate(Request $request, Novel $novel, Chapter $chapter)
    {
        $request->validate([
            'prompt' => 'required|string',
            'mode' => 'sometimes|string|in:context,web',
        ]);
        
        $prompt = $request->input('prompt');
        $mode = $request->input('mode', 'context');
        
        if ($mode === 'web') {
            $generatedText = \App\Facades\LocalAI::generateWithWebSearch($prompt);
        } else {
            $filter = [
                'must' => [
                    ['key' => 'collection', 'match' => ['value' => 'novel_' . $novel->id]]
                ]
            ];
            
            $generatedText = \App\Facades\LocalAI::askDocuments($prompt, 3, $filter);
        }
            
        return response()->json(['text' => $generatedText]);
    }

    public function analyze(Request $request, Novel $novel, Chapter $chapter)
    {
        set_time_limit(120); // Increase timeout to 2 minutes
        $request->validate(['selection' => 'required|string']);
        $selection = $request->input('selection');
        
        $prompt = "Analyze the following text for grammar, style, and clarity improvements. Provide specific suggestions.\n\nText: \"{$selection}\"";
        
        $analysis = \App\Facades\LocalAI::generate($prompt);
        
        return response()->json(['analysis' => $analysis]);
    }

    public function suggest(Request $request, Novel $novel, Chapter $chapter)
    {
        set_time_limit(120); // Increase timeout to 2 minutes
        $request->validate(['context' => 'required|string']);
        $context = $request->input('context');
        
        // Fetch previous chapters content
        $previousContent = $novel->chapters()
            ->where('order', '<', $chapter->order)
            ->orderBy('order', 'asc')
            ->pluck('content')
            ->implode("\n\n");
            
        // Truncate previous content if too long (e.g., last 10000 chars)
        if (strlen($previousContent) > 10000) {
            $previousContent = '...' . substr($previousContent, -10000);
        }
        
        $prompt = "Continue the story based on the following context. Keep the style consistent.\n\nStory So Far:\n\"{$previousContent}\"\n\nCurrent Context:\n\"{$context}\"";
        
        $suggestion = \App\Facades\LocalAI::generate($prompt);
        
        return response()->json(['suggestion' => $suggestion]);
    }
}
