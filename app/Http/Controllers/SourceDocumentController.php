<?php

namespace App\Http\Controllers;

use App\Models\Novel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SourceDocumentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);
        $request->validate([
            'file' => 'required',
            'file.*' => 'file|mimes:pdf,txt,md,csv|max:10240',
        ]);

        $files = $request->file('file');

        if (! is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $path = $file->store('novels/'.$novel->id, 'local');

            $document = $novel->sourceDocuments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'status' => 'pending',
                'type' => 'file',
            ]);

            \App\Jobs\ProcessSourceDocument::dispatch($document);
        }

        return back();
    }

    public function storeLink(Request $request, Novel $novel)
    {
        $this->authorize('update', $novel);
        $request->validate([
            'url' => 'required|url',
        ]);

        $document = $novel->sourceDocuments()->create([
            'filename' => $request->input('url'),
            'path' => $request->input('url'),
            'status' => 'pending',
            'type' => 'web_link',
        ]);

        \App\Jobs\ProcessWebLink::dispatch($document);

        return back();
    }
}
