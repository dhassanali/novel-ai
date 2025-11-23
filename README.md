# Laravel Local AI Stack

A complete Docker-based local AI solution for Laravel applications. Run LLMs, vector search, speech-to-text, and text-to-speech entirely on your own infrastructure - no API keys required.

## Features

- 🤖 **LLM Text Generation** - Ollama with Llama 3.1, Mistral, and more
- 🔍 **Semantic Search** - Vector embeddings with Qdrant
- 🎤 **Speech-to-Text** - OpenAI Whisper for transcription
- 🔊 **Text-to-Speech** - Coqui TTS for voice synthesis
- 📚 **RAG (Document Q&A)** - Query your documents with AI
- 🔒 **Privacy-First** - All processing on-premise
- 💰 **Cost-Effective** - No API bills

## Requirements

- Docker & Docker Compose
- PHP 8.1+
- Laravel 10+
- At least 8GB RAM (16GB recommended)
- 20GB+ free disk space for models

## Quick Start

### 1. Install via Composer

```bash
composer require hassan/laravel-local-ai
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=local-ai-config
php artisan vendor:publish --tag=local-ai-docker
```

### 3. Start Docker Services

```bash
cd docker/local-ai
docker-compose up -d
```

### 4. Pull AI Models

```bash
# Pull a language model (this will take a few minutes)
php artisan local-ai:pull-model llama3.1

# Models will be downloaded and cached
```

### 5. Test It

```php
use LocalAI\Facades\LocalAI;

// Generate text
$response = LocalAI::generate('Explain Laravel in simple terms');

// Create embeddings
$embedding = LocalAI::embed('This is a test document');

// Transcribe audio
$text = LocalAI::transcribe('/path/to/audio.mp3');

// Text to speech
$audioPath = LocalAI::speak('Hello from Laravel!');
```

## Installation from Source (Development)

```bash
# Clone the repository
git clone https://github.com/yourname/laravel-local-ai.git
cd laravel-local-ai

# Install dependencies
composer install

# Start Docker services
cd docker
docker-compose up -d

# Wait for services to be ready (check with docker-compose ps)
```

## Architecture

```
Docker Services:
├── Ollama (Port 11434)      - LLM inference
├── Qdrant (Port 6333)       - Vector database
├── Whisper (Port 9000)      - Speech-to-text
└── Coqui TTS (Port 5002)    - Text-to-speech
```

## Usage Examples

### Text Generation

```php
use LocalAI\Facades\LocalAI;

// Simple generation
$response = LocalAI::generate('Write a haiku about PHP');

// Advanced options
$response = LocalAI::generate('Explain recursion')
    ->model('llama3.1')
    ->temperature(0.7)
    ->maxTokens(500)
    ->stream(function($chunk) {
        echo $chunk;
    });
```

### Semantic Search

```php
use LocalAI\Models\Concerns\HasEmbeddings;

class Article extends Model
{
    use HasEmbeddings;
    
    protected $embeddingFields = ['title', 'content'];
}

// Create embeddings (automatic on save)
$article = Article::create([
    'title' => 'Laravel Best Practices',
    'content' => '...'
]);

// Search semantically
$results = Article::semanticSearch('how to optimize laravel')
    ->take(5)
    ->get();
    
// Find similar articles
$similar = $article->similar()->take(5)->get();
```

### Speech Processing

```php
// Speech to Text
$transcription = LocalAI::transcribe('/path/to/audio.mp3')
    ->language('en')
    ->get();

// Text to Speech
$audioPath = LocalAI::speak('Welcome to Laravel Local AI')
    ->voice('female')
    ->speed(1.0)
    ->save();

// Or get audio data directly
$audioData = LocalAI::speak('Hello')->getAudio();
```

### Document Q&A (RAG)

```php
// Ingest documents
LocalAI::ingestDocuments('/path/to/documents/*.pdf');

// Ask questions
$answer = LocalAI::askDocuments(
    'What are the main deployment strategies mentioned?'
);

// With sources
$result = LocalAI::askDocuments('Summarize key points')
    ->withSources()
    ->get();
```

## Configuration

Edit `config/local-ai.php`:

```php
return [
    'ollama' => [
        'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
        'default_model' => env('OLLAMA_MODEL', 'llama3.1'),
        'timeout' => 120,
    ],
    
    'qdrant' => [
        'host' => env('QDRANT_HOST', 'http://localhost:6333'),
        'collection' => env('QDRANT_COLLECTION', 'documents'),
    ],
    
    'whisper' => [
        'host' => env('WHISPER_HOST', 'http://localhost:9000'),
        'model' => env('WHISPER_MODEL', 'base'),
    ],
    
    'coqui' => [
        'host' => env('COQUI_HOST', 'http://localhost:5002'),
        'default_voice' => 'en-us-female',
    ],
];
```

## Available Models

### LLM Models (Ollama)
- `llama3.1` (8B) - Best general purpose
- `llama3.1:70b` - More capable, slower
- `mistral` - Fast and efficient
- `codellama` - Code-focused
- `phi3` - Lightweight, 3.8B

### Whisper Models
- `tiny` - Fastest, least accurate
- `base` - Good balance (default)
- `small` - Better accuracy
- `medium` - High accuracy
- `large` - Best accuracy, slowest

## Artisan Commands

```bash
# Model management
php artisan local-ai:pull-model llama3.1
php artisan local-ai:list-models
php artisan local-ai:remove-model mistral

# Document ingestion
php artisan local-ai:ingest /path/to/docs --collection=my-docs

# Service health check
php artisan local-ai:health

# Clear embeddings cache
php artisan local-ai:clear-embeddings
```

## Performance Tips

1. **GPU Acceleration**: If you have NVIDIA GPU, uncomment GPU sections in docker-compose.yml
2. **Model Selection**: Use smaller models (llama3.1:8b) for faster responses
3. **Batch Processing**: Process embeddings in batches using queues
4. **Cache Results**: Cache frequent queries to reduce compute

## Troubleshooting

### Services not starting

```bash
# Check logs
docker-compose logs -f

# Restart services
docker-compose restart

# Check disk space
df -h
```

### Out of memory

- Reduce model size (use `phi3` instead of `llama3.1:70b`)
- Increase Docker memory limit
- Close other applications

### Slow responses

- Use smaller models
- Enable GPU acceleration
- Reduce max_tokens parameter

## Development

```bash
# Run tests
composer test

# Code style
composer lint

# Type checking
composer type-check
```

## Roadmap

- [ ] Support for more LLM providers (LocalAI, LM Studio)
- [ ] Image generation (Stable Diffusion)
- [ ] Multi-modal models (vision + text)
- [ ] Model fine-tuning helpers
- [ ] Web UI for management
- [ ] Horizontal scaling support

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

MIT License - see [LICENSE.md](LICENSE.md)

## Credits

Built by Hassan for the Laravel community.

Powered by:
- [Ollama](https://ollama.ai/) - LLM inference
- [Qdrant](https://qdrant.tech/) - Vector database
- [OpenAI Whisper](https://github.com/openai/whisper) - Speech recognition
- [Coqui TTS](https://github.com/coqui-ai/TTS) - Text-to-speech
