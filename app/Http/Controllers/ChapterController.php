<?php

namespace App\Http\Controllers;

use App\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ChapterController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);
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
        $this->authorize('update', $novel);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|string',
            'status' => ['sometimes', new Enum(ChapterStatus::class)],
            'pov_character_id' => 'sometimes|nullable|integer|exists:characters,id',
        ]);

        $chapter->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'saved']);
        }

        return back();
    }

    public function generate(Request $request, Novel $novel, Chapter $chapter)
    {
        $this->authorize('update', $novel);
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
                    ['key' => 'collection', 'match' => ['value' => 'novel_'.$novel->id]],
                ],
            ];

            $generatedText = \App\Facades\LocalAI::askDocuments($prompt, 3, $filter);
        }

        return response()->json(['text' => $generatedText]);
    }

    public function analyze(Request $request, Novel $novel, Chapter $chapter)
    {
        $this->authorize('update', $novel);
        set_time_limit(120);
        $request->validate(['selection' => 'required|string']);
        $selection = $request->input('selection');

        $prompt = "Analyze the following text for grammar, style, and clarity improvements. Provide specific suggestions.\n\nText: \"{$selection}\"";

        $analysis = \App\Facades\LocalAI::generate($prompt);

        return response()->json(['analysis' => $analysis]);
    }

    public function suggest(Request $request, Novel $novel, Chapter $chapter)
    {
        $this->authorize('update', $novel);
        set_time_limit(120);
        $request->validate(['context' => 'required|string']);
        $context = $request->input('context');

        $previousContent = $novel->chapters()
            ->where('order', '<', $chapter->order)
            ->orderBy('order', 'asc')
            ->pluck('content')
            ->implode("\n\n");

        if (strlen($previousContent) > 10000) {
            $previousContent = '...'.substr($previousContent, -10000);
        }

        $loreContext = $this->getLoreContext($novel, $chapter);

        $prompt = "Novel Title: {$novel->title}\nGenre: {$novel->genre}\nDescription: {$novel->description}\nChapter Title: {$chapter->title}\n\n{$loreContext}\n\nContinue the story based on the following context. Keep the style consistent.\n\nStory So Far:\n\"{$previousContent}\"\n\nCurrent Context:\n\"{$context}\"";

        $suggestion = \App\Facades\LocalAI::generate($prompt);

        return response()->json(['suggestion' => $suggestion]);
    }

    public function rewrite(Request $request, Novel $novel, Chapter $chapter)
    {
        $this->authorize('update', $novel);
        set_time_limit(120);
        $validated = $request->validate([
            'selection' => 'required|string',
            'instruction' => 'required|string',
        ]);

        $loreContext = $this->getLoreContext($novel, $chapter);

        $prompt = "Novel Title: {$novel->title}\nGenre: {$novel->genre}\nDescription: {$novel->description}\nChapter Title: {$chapter->title}\n\n{$loreContext}\n\nRewrite the following text based on these instructions: \"{$validated['instruction']}\".\n\nOriginal Text:\n\"{$validated['selection']}\"\n\nRewritten Text:";

        $rewritten = \App\Facades\LocalAI::generate($prompt);

        return response()->json(['rewritten' => $rewritten]);
    }

    public function expand(Request $request, Novel $novel, Chapter $chapter)
    {
        $this->authorize('update', $novel);
        set_time_limit(120);
        $validated = $request->validate([
            'selection' => 'required|string',
        ]);

        $loreContext = $this->getLoreContext($novel, $chapter);

        $prompt = "Novel Title: {$novel->title}\nGenre: {$novel->genre}\nDescription: {$novel->description}\nChapter Title: {$chapter->title}\n\n{$loreContext}\n\nExpand the following summary or short text into a full, detailed scene. Include dialogue, sensory details, and internal monologue where appropriate.\n\nSummary:\n\"{$validated['selection']}\"\n\nExpanded Scene:";

        $expanded = \App\Facades\LocalAI::generate($prompt);

        return response()->json(['expanded' => $expanded]);
    }

    private function getLoreContext(Novel $novel, ?Chapter $chapter = null): string
    {
        $characters = $novel->characters()->get()->map(function ($char) {
            return "- {$char->name} ({$char->role}): {$char->description}";
        })->implode("\n");

        $locations = $novel->locations()->get()->map(function ($loc) {
            return "- {$loc->name}: {$loc->description}";
        })->implode("\n");

        $context = '';

        if ($chapter?->pov_character_id) {
            $pov = $novel->characters()->find($chapter->pov_character_id);
            if ($pov) {
                $context .= "POV Character (write this chapter from their perspective): {$pov->name}\n\n";
            }
        }

        if (! empty($characters)) {
            $context .= "Characters:\n{$characters}\n\n";
        }
        if (! empty($locations)) {
            $context .= "Locations:\n{$locations}\n\n";
        }

        return $context;
    }
}
