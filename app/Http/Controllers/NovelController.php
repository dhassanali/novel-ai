<?php

namespace App\Http\Controllers;

use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class NovelController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        return Inertia::render('Novels/Index', [
            'novels' => Novel::where('user_id', auth()->id())->latest()->get(),
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

        Inertia::encryptHistory();

        $novel->load(['chapters', 'sourceDocuments', 'characters', 'locations']);

        return Inertia::render('Novels/Show', [
            'novel' => $novel,
        ]);
    }

    public function update(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'genre' => 'nullable|string|max:255',
        ]);

        $novel->update($validated);

        return back();
    }

    public function destroy(Novel $novel)
    {
        $this->authorize('delete', $novel);

        // Delete cover image if exists
        if ($novel->cover_image) {
            Storage::disk('public')->delete($novel->cover_image);
        }

        $novel->delete();

        return to_route('novels.index');
    }

    public function uploadCover(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);

        $request->validate([
            'cover' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Delete old cover if exists
        if ($novel->cover_image) {
            Storage::disk('public')->delete($novel->cover_image);
        }

        // Store new cover
        $path = $request->file('cover')->store('novel-covers', 'public');

        $novel->update(['cover_image' => $path]);

        return back()->with('success', 'Cover image uploaded successfully');
    }

    public function deleteCover(Novel $novel)
    {
        $this->authorize('update', $novel);

        if ($novel->cover_image) {
            Storage::disk('public')->delete($novel->cover_image);
            $novel->update(['cover_image' => null]);
        }

        return back()->with('success', 'Cover image deleted successfully');
    }
}
