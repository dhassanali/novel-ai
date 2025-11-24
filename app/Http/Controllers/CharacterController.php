<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Character;
use App\Models\Novel;

class CharacterController extends Controller
{
    public function store(Request $request, Novel $novel)
    {
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
        $character->delete();

        return back();
    }
}
