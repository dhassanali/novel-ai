<?php

namespace App\Commands;
use Illuminate\Console\Command;
use App\Services\QdrantService;

class ClearEmbeddingsCommand extends Command
{
    protected $signature = 'local-ai:clear-embeddings
                            {--collection= : Specific collection to clear}
                            {--all : Clear all collections}';

    protected $description = 'Clear embeddings from vector database';

    public function handle(QdrantService $qdrant): int
    {
        $collection = $this->option('collection');
        $all = $this->option('all');

        if (!$collection && !$all) {
            $this->error('Please specify either --collection=name or --all');
            return self::FAILURE;
        }

        if ($all) {
            if (!$this->confirm('Are you sure you want to clear ALL collections? This cannot be undone.')) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }

            $collections = $qdrant->listCollections();

            foreach ($collections as $coll) {
                $name = $coll['name'] ?? null;
                if ($name) {
                    $this->info("Deleting collection: {$name}");
                    $qdrant->deleteCollection($name);
                }
            }

            $this->info('All collections cleared!');
            return self::SUCCESS;
        }

        if (!$this->confirm("Are you sure you want to clear collection '{$collection}'? This cannot be undone.")) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $this->info("Clearing collection: {$collection}");

        try {
            $qdrant->deleteCollection($collection);
            $this->info("Collection '{$collection}' cleared successfully!");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to clear collection: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
