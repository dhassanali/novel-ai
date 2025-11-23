<?php

namespace App\Providers;

use App\Services\CoquiService;

class SpeechBuilder
{
    protected string $text;
    protected ?string $voice = null;
    protected float $speed = 1.0;

    public function __construct(
        protected CoquiService $coqui,
        string $text
    ) {
        $this->text = $text;
    }

    /**
     * Set the voice to use.
     */
    public function voice(string $voice): self
    {
        $this->voice = $voice;
        return $this;
    }

    /**
     * Set speech speed (0.5 to 2.0).
     */
    public function speed(float $speed): self
    {
        $this->speed = $speed;
        return $this;
    }

    /**
     * Generate audio and return raw audio data.
     */
    public function getAudio(): string
    {
        return $this->coqui->speak($this->text, $this->voice, $this->speed);
    }

    /**
     * Generate audio and save to file, return path.
     */
    public function save(?string $filename = null): string
    {
        return $this->coqui->speakToFile($this->text, $filename, $this->voice, $this->speed);
    }

    /**
     * Generate audio and return as base64.
     */
    public function toBase64(): string
    {
        $audio = $this->getAudio();
        return base64_encode($audio);
    }

    /**
     * Generate audio with metadata.
     */
    public function withMetadata(): array
    {
        $path = $this->save();

        return [
            'path' => $path,
            'text' => $this->text,
            'voice' => $this->voice,
            'speed' => $this->speed,
            'duration' => null, // Could be calculated if needed
        ];
    }
}
