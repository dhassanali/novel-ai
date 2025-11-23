<?php

namespace App\Providers;

use App\Services\OllamaService;

class TextGenerationBuilder
{
    protected string $prompt;
    protected ?string $model = null;
    protected ?float $temperature = null;
    protected ?int $maxTokens = null;
    protected bool $shouldStream = false;
    protected $streamCallback = null;

    public function __construct(
        protected OllamaService $ollama,
        string $prompt
    ) {
        $this->prompt = $prompt;
    }

    /**
     * Set the model to use.
     */
    public function model(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set temperature (0.0 to 2.0).
     */
    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    /**
     * Set maximum tokens to generate.
     */
    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    /**
     * Enable streaming and set callback.
     */
    public function stream(callable $callback): self
    {
        $this->shouldStream = true;
        $this->streamCallback = $callback;
        return $this;
    }

    /**
     * Execute the generation.
     */
    public function get(): string
    {
        $options = [];

        if ($this->temperature !== null) {
            $options['temperature'] = $this->temperature;
        }

        if ($this->maxTokens !== null) {
            $options['max_tokens'] = $this->maxTokens;
        }

        if ($this->shouldStream && $this->streamCallback) {
            $fullResponse = '';

            $this->ollama->generateStream(
                $this->prompt,
                function ($chunk) use (&$fullResponse) {
                    $fullResponse .= $chunk;
                    call_user_func($this->streamCallback, $chunk);
                },
                $this->model,
                $options
            );

            return $fullResponse;
        }

        return $this->ollama->generate($this->prompt, $this->model, $options);
    }

    /**
     * Alias for get().
     */
    public function generate(): string
    {
        return $this->get();
    }

    /**
     * Execute and return as array with metadata.
     */
    public function withMetadata(): array
    {
        $response = $this->get();

        return [
            'text' => $response,
            'model' => $this->model,
            'prompt' => $this->prompt,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];
    }
}
