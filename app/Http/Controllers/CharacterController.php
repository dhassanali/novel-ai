<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role' => 'nullable|string|max:255',
        ]);

        $novel->characters()->create($validated);

        return back();
    }

    public function update(Request $request, Novel $novel, Character $character)
    {
        $this->authorize('update', $novel);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role' => 'nullable|string|max:255',
        ]);

        $character->update($validated);

        return back();
    }

    public function destroy(Novel $novel, Character $character)
    {
        $this->authorize('update', $novel);
        $character->delete();

        return back();
    }
}
