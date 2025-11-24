<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Location;
use App\Models\Novel;

class LocationController extends Controller
{
    public function store(Request $request, Novel $novel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $novel->locations()->create($validated);

        return back();
    }

    public function update(Request $request, Novel $novel, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $location->update($validated);

        return back();
    }

    public function destroy(Novel $novel, Location $location)
    {
        $location->delete();

        return back();
    }
}
