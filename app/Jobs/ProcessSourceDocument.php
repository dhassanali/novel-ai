<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\Models\SourceDocument;
use App\Facades\LocalAI;

class ProcessSourceDocument implements ShouldQueue
{
    use Queueable;

    public function __construct(public SourceDocument $document)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->document->update(['status' => 'processing']);

        try {
            $path = storage_path('app/' . $this->document->path);
            $collection = 'novel_' . $this->document->novel_id;
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            
            if ($extension === 'csv') {
                LocalAI::ingestCsv($path, $collection);
            } else {
                LocalAI::ingestDocuments($path, $collection);
            }
            
            $this->document->update(['status' => 'completed']);
        } catch (\Exception $e) {
            $this->document->update(['status' => 'failed']);
            throw $e;
        }
    }
}
