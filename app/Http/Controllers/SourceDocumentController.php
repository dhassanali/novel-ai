<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Novel;

class SourceDocumentController extends Controller
{
    public function store(Request $request, Novel $novel)
    {
        $request->validate([
            'file' => 'required',
            'file.*' => 'file|mimes:pdf,txt,md,csv|max:10240',
        ]);

        $files = $request->file('file');
        
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $path = $file->store('novels/' . $novel->id, 'local');

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
