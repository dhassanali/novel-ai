<?php

namespace App\Http\Controllers;

use App\Http\Requests\CharacterChatRequest;
use App\Models\Character;
use App\Models\Novel;

class CharacterChatController extends Controller
{
    public function chat(CharacterChatRequest $request, Novel $novel, Character $character): \Illuminate\Http\JsonResponse
    {
        set_time_limit(120);

        $character->load('relationships.relatedCharacter');

        $message = $request->string('message');
        $history = $request->input('history', []);

        $relationshipLines = $character->relationships->map(function ($rel) use ($character) {
            $desc = $rel->description ? " — {$rel->description}" : '';

            return "- {$character->name} ↔ {$rel->relatedCharacter->name} ({$rel->type}){$desc}";
        })->implode("\n");

        $systemPrompt = "You are {$character->name}, a character in the novel \"{$novel->title}\"";

        if ($novel->genre) {
            $systemPrompt .= " ({$novel->genre})";
        }

        $systemPrompt .= ".\n\nAbout you:\n";
        $systemPrompt .= "- Role: {$character->role}\n";
        $systemPrompt .= "- Description: {$character->description}\n";

        if (! empty($character->personality_traits)) {
            $systemPrompt .= "- Personality: {$character->personality_traits}\n";
        }
        if (! empty($character->backstory)) {
            $systemPrompt .= "- Backstory: {$character->backstory}\n";
        }
        if (! empty($character->goals)) {
            $systemPrompt .= "- Goals: {$character->goals}\n";
        }
        if (! empty($character->speech_patterns)) {
            $systemPrompt .= "- How you speak: {$character->speech_patterns}\n";
        }

        if (! empty($relationshipLines)) {
            $systemPrompt .= "\nYour relationships:\n{$relationshipLines}\n";
        }

        $systemPrompt .= "\nStay in character at all times. Respond as {$character->name} would — use their voice, knowledge, and perspective. Do not break the fourth wall or acknowledge you are a fictional character.";

        $conversationParts = [];

        foreach ($history as $turn) {
            $label = $turn['role'] === 'user' ? 'Interviewer' : $character->name;
            $conversationParts[] = "{$label}: {$turn['content']}";
        }

        $conversationParts[] = "Interviewer: {$message}";
        $conversationParts[] = "{$character->name}:";

        $fullPrompt = $systemPrompt."\n\n".implode("\n", $conversationParts);

        $reply = \App\Facades\LocalAI::generate($fullPrompt);

        return response()->json(['reply' => $reply]);
    }
}
