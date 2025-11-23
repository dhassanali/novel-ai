<?php

namespace App\Commands;

use Illuminate\Console\Command;
use App\Facades\LocalAI;

class IngestDocumentsCommand extends Command
{
    protected $signature = 'local-ai:ingest
                            {path : Path to documents (supports wildcards)}
                            {--collection= : Collection name}';

    protected $description = 'Ingest documents into vector database for RAG';

    public function handle(): int
    {
        $path = $this->argument('path');
        $collection = $this->option('collection');

        if (!glob($path)) {
            $this->error("No files found at: {$path}");
            return self::FAILURE;
        }

        $this->info("Ingesting documents from: {$path}");
        if ($collection) {
            $this->info("Collection: {$collection}");
        }
        $this->newLine();

        try {
            $bar = $this->output->createProgressBar();
            $bar->start();

            $count = LocalAI::ingestDocuments($path, $collection);

            $bar->finish();
            $this->newLine(2);

            $this->info("Successfully ingested {$count} document chunks!");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("Failed to ingest documents: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
