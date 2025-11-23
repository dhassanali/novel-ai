<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class NovelController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return Inertia::render('Novels/Index', [
            'novels' => Novel::where('user_id', auth()->id())->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'genre' => 'nullable|string|max:255',
        ]);

        $novel = Novel::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return to_route('novels.show', $novel);
    }

    public function show(Novel $novel)
    {
        $this->authorize('view', $novel);
        
        $novel->load(['chapters', 'sourceDocuments']);

        return Inertia::render('Novels/Show', [
            'novel' => $novel
        ]);
    }

    public function destroy(Novel $novel)
    {
        $this->authorize('delete', $novel);
        $novel->delete();
        return to_route('novels.index');
    }
}
