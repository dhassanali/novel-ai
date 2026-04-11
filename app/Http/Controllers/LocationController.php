<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $novel->locations()->create($validated);

        return back();
    }

    public function update(Request $request, Novel $novel, Location $location)
    {
        $this->authorize('update', $novel);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $location->update($validated);

        return back();
    }

    public function destroy(Novel $novel, Location $location)
    {
        $this->authorize('update', $novel);
        $location->delete();

        return back();
    }
}
