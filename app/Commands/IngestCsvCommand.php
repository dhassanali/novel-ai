<?php

namespace App\Commands;

use Illuminate\Console\Command;
use App\Facades\LocalAI;

class IngestCsvCommand extends Command
{
    protected $signature = 'local-ai:ingest-csv
                            {file : Path to CSV file}
                            {--collection= : Collection name}
                            {--columns=* : Specific columns to use}
                            {--template= : Custom template for formatting}
                            {--chunk-size=1 : Rows per chunk}
                            {--no-header : CSV has no header row}';

    protected $description = 'Ingest CSV file into vector database for semantic search';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info("Analyzing CSV file...");

        // Get CSV stats
        $csv = LocalAI::csv();
        $stats = $csv->getStats($file, !$this->option('no-header'));

        $this->newLine();
        $this->info("CSV Statistics:");
        $this->line("  Rows: " . $stats['row_count']);
        $this->line("  Columns: " . $stats['column_count']);
        $this->line("  Estimated tokens: " . $stats['estimated_tokens']);

        if (!empty($stats['columns'])) {
            $this->line("  Column names: " . implode(', ', $stats['columns']));
        }

        $this->newLine();

        // Validate
        $issues = $csv->validate($file);
        if (!empty($issues)) {
            $this->warn("Validation issues found:");
            foreach ($issues as $issue) {
                $this->line("  - {$issue}");
            }

            if (!$this->confirm('Continue anyway?', false)) {
                return self::FAILURE;
            }
        }

        // Confirm ingestion
        if (!$this->confirm("Ingest {$stats['row_count']} rows into vector database?", true)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info("Ingesting CSV data...");

        // Prepare options
        $options = [
            'has_header' => !$this->option('no-header'),
            'chunk_size' => (int)$this->option('chunk-size'),
        ];

        if ($this->option('columns')) {
            $options['columns'] = $this->option('columns');
        }

        if ($this->option('template')) {
            $options['template'] = $this->option('template');
        }

        $collection = $this->option('collection') ?? 'csv-data';

        // Ingest with progress bar
        try {
            $bar = $this->output->createProgressBar();
            $bar->start();

            $count = LocalAI::ingestCsv($file, $collection, $options);

            $bar->finish();
            $this->newLine(2);

            $this->info("Successfully ingested {$count} chunks!");
            $this->line("Collection: {$collection}");

            $this->newLine();
            $this->info("You can now search this data:");
            $this->line("  php artisan tinker");
            $this->line("  >>> LocalAI::searchCsv('your search query')");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("Failed to ingest CSV: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
