<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CsvService
{
    /**
     * Parse CSV file and return as array of rows.
     */
    public function parse(string $filePath, bool $hasHeader = true): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("CSV file not found: {$filePath}");
        }

        $rows = [];
        $headers = [];
        $rowIndex = 0;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if ($rowIndex === 0 && $hasHeader) {
                    $headers = $data;
                    $rowIndex++;
                    continue;
                }

                if ($hasHeader && !empty($headers)) {
                    $rows[] = array_combine($headers, $data);
                } else {
                    $rows[] = $data;
                }

                $rowIndex++;
            }
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Convert CSV rows to text documents for embedding.
     */
    public function toDocuments(
        array $rows,
        ?array $columns = null,
        ?string $template = null
    ): array {
        $documents = [];

        foreach ($rows as $index => $row) {
            // If specific columns specified, use only those
            if ($columns !== null) {
                $filteredRow = [];
                foreach ($columns as $column) {
                    if (isset($row[$column])) {
                        $filteredRow[$column] = $row[$column];
                    }
                }
                $row = $filteredRow;
            }

            // Generate document text
            if ($template !== null) {
                // Use custom template
                $text = $this->applyTemplate($template, $row);
            } else {
                // Default: concatenate all values
                $text = $this->defaultFormat($row);
            }

            $documents[] = [
                'text' => $text,
                'metadata' => array_merge($row, [
                    'source' => 'csv',
                    'row_index' => $index,
                ]),
            ];
        }

        return $documents;
    }

    /**
     * Apply template to row data.
     */
    protected function applyTemplate(string $template, array $row): string
    {
        $text = $template;

        foreach ($row as $key => $value) {
            $text = str_replace("{{$key}}", (string)$value, $text);
        }

        return $text;
    }

    /**
     * Default formatting: key: value pairs.
     */
    protected function defaultFormat(array $row): string
    {
        $parts = [];

        foreach ($row as $key => $value) {
            if (is_string($key)) {
                $parts[] = "{$key}: {$value}";
            } else {
                $parts[] = (string)$value;
            }
        }

        return implode('. ', $parts);
    }

    /**
     * Extract text from CSV for embedding (simple approach).
     */
    public function extractText(string $filePath, bool $hasHeader = true): string
    {
        $rows = $this->parse($filePath, $hasHeader);
        $allText = [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $allText[] = implode(' ', array_values($row));
            }
        }

        return implode("\n", $allText);
    }

    /**
     * Chunk CSV file into groups of rows.
     */
    public function chunkByRows(string $filePath, int $rowsPerChunk = 10, bool $hasHeader = true): array
    {
        $rows = $this->parse($filePath, $hasHeader);
        $chunks = [];

        foreach (array_chunk($rows, $rowsPerChunk) as $chunk) {
            $texts = [];
            foreach ($chunk as $row) {
                $texts[] = $this->defaultFormat($row);
            }

            $chunks[] = [
                'text' => implode("\n", $texts),
                'metadata' => [
                    'source' => 'csv',
                    'row_count' => count($chunk),
                ],
            ];
        }

        return $chunks;
    }

    /**
     * Create embeddings-ready documents with smart chunking.
     */
    public function prepareForEmbedding(
        string $filePath,
        array $options = []
    ): array {
        $hasHeader = $options['has_header'] ?? true;
        $columns = $options['columns'] ?? null;
        $template = $options['template'] ?? null;
        $chunkSize = $options['chunk_size'] ?? 1; // rows per chunk
        $includeHeader = $options['include_header'] ?? true;

        $rows = $this->parse($filePath, $hasHeader);
        $documents = [];

        // Get headers if they exist
        $headers = $hasHeader ? array_keys($rows[0] ?? []) : null;

        // Chunk rows
        foreach (array_chunk($rows, $chunkSize) as $chunkIndex => $chunk) {
            $chunkTexts = [];

            // Optionally include header context
            if ($includeHeader && $headers) {
                $chunkTexts[] = "Columns: " . implode(', ', $headers);
            }

            foreach ($chunk as $row) {
                if ($columns !== null) {
                    $filteredRow = array_intersect_key($row, array_flip($columns));
                } else {
                    $filteredRow = $row;
                }

                if ($template !== null) {
                    $chunkTexts[] = $this->applyTemplate($template, $filteredRow);
                } else {
                    $chunkTexts[] = $this->defaultFormat($filteredRow);
                }
            }

            $documents[] = [
                'text' => implode("\n", $chunkTexts),
                'metadata' => [
                    'source_file' => basename($filePath),
                    'file_type' => 'csv',
                    'chunk_index' => $chunkIndex,
                    'row_count' => count($chunk),
                    'headers' => $headers,
                ],
            ];
        }

        return $documents;
    }

    /**
     * Convert CSV to Collection for easier manipulation.
     */
    public function toCollection(string $filePath, bool $hasHeader = true): Collection
    {
        $rows = $this->parse($filePath, $hasHeader);
        return collect($rows);
    }

    /**
     * Filter CSV rows based on criteria.
     */
    public function filter(string $filePath, callable $callback, bool $hasHeader = true): array
    {
        $rows = $this->parse($filePath, $hasHeader);
        return array_filter($rows, $callback);
    }

    /**
     * Get statistics about CSV file.
     */
    public function getStats(string $filePath, bool $hasHeader = true): array
    {
        $rows = $this->parse($filePath, $hasHeader);

        if (empty($rows)) {
            return [
                'row_count' => 0,
                'column_count' => 0,
                'columns' => [],
            ];
        }

        $firstRow = $rows[0];

        return [
            'row_count' => count($rows),
            'column_count' => count($firstRow),
            'columns' => is_array($firstRow) ? array_keys($firstRow) : [],
            'file_size' => filesize($filePath),
            'estimated_tokens' => $this->estimateTokens($rows),
        ];
    }

    /**
     * Estimate token count for pricing/limits.
     */
    protected function estimateTokens(array $rows): int
    {
        $totalChars = 0;

        foreach ($rows as $row) {
            foreach ($row as $value) {
                $totalChars += strlen((string)$value);
            }
        }

        // Rough estimate: 1 token ≈ 4 characters
        return (int)ceil($totalChars / 4);
    }

    /**
     * Validate CSV structure.
     */
    public function validate(string $filePath): array
    {
        $issues = [];

        if (!file_exists($filePath)) {
            $issues[] = 'File does not exist';
            return $issues;
        }

        if (!is_readable($filePath)) {
            $issues[] = 'File is not readable';
            return $issues;
        }

        $rows = $this->parse($filePath, true);

        if (empty($rows)) {
            $issues[] = 'CSV file is empty';
        }

        // Check for consistent column count
        $columnCounts = array_map('count', $rows);
        $uniqueCounts = array_unique($columnCounts);

        if (count($uniqueCounts) > 1) {
            $issues[] = 'Inconsistent column counts across rows';
        }

        return $issues;
    }
}
