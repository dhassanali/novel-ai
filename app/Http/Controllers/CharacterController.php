<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\CharacterRelationship;
use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Novel $novel): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $novel);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role' => 'nullable|string|max:255',
            'personality_traits' => 'nullable|string',
            'backstory' => 'nullable|string',
            'goals' => 'nullable|string',
            'speech_patterns' => 'nullable|string',
        ]);

        $novel->characters()->create($validated);

        return back();
    }

    public function update(Request $request, Novel $novel, Character $character): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $novel);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role' => 'nullable|string|max:255',
            'personality_traits' => 'nullable|string',
            'backstory' => 'nullable|string',
            'goals' => 'nullable|string',
            'speech_patterns' => 'nullable|string',
        ]);

        $character->update($validated);

        return back();
    }

    public function destroy(Novel $novel, Character $character): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $novel);
        $character->delete();

        return back();
    }

    public function storeRelationship(Request $request, Novel $novel, Character $character): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $novel);
        $validated = $request->validate([
            'related_character_id' => 'required|integer|exists:characters,id',
            'type' => 'required|string|in:friend,enemy,lover,family,rival,mentor,ally',
            'description' => 'nullable|string',
        ]);

        $character->relationships()->updateOrCreate(
            ['related_character_id' => $validated['related_character_id']],
            [
                'novel_id' => $novel->id,
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
            ]
        );

        return back();
    }

    public function destroyRelationship(Novel $novel, Character $character, CharacterRelationship $relationship): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $novel);
        $relationship->delete();

        return back();
    }
}
