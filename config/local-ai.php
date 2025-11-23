<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ollama Configuration (LLM)
    |--------------------------------------------------------------------------
    |
    | Configuration for Ollama service which provides LLM inference.
    | Default model options: llama3.1, mistral, phi3, codellama
    |
    */
    'ollama' => [
        'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
        'default_model' => env('OLLAMA_MODEL', 'llama3.2'), // mistral
        'timeout' => env('OLLAMA_TIMEOUT', 120),
        'max_tokens' => env('OLLAMA_MAX_TOKENS', 2000),
        'temperature' => env('OLLAMA_TEMPERATURE', 0.7),
        'stream' => env('OLLAMA_STREAM', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Qdrant Configuration (Vector Database)
    |--------------------------------------------------------------------------
    |
    | Configuration for Qdrant vector database used for embeddings
    | and semantic search functionality.
    |
    */
    'qdrant' => [
        'host' => env('QDRANT_HOST', 'http://localhost:6333'),
        'api_key' => env('QDRANT_API_KEY', null),
        'collection' => env('QDRANT_COLLECTION', 'documents'),
        'vector_size' => env('QDRANT_VECTOR_SIZE', 768),
        'distance' => env('QDRANT_DISTANCE', 'Cosine'), // Cosine, Euclid, Dot
        'timeout' => env('QDRANT_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Whisper Configuration (Speech-to-Text)
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenAI Whisper speech recognition.
    | Model options: tiny, base, small, medium, large
    |
    */
    'whisper' => [
        'host' => env('WHISPER_HOST', 'http://localhost:9000'),
        'model' => env('WHISPER_MODEL', 'base'),
        'language' => env('WHISPER_LANGUAGE', 'en'),
        'timeout' => env('WHISPER_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Coqui TTS Configuration (Text-to-Speech)
    |--------------------------------------------------------------------------
    |
    | Configuration for Coqui TTS text-to-speech synthesis.
    |
    */
    'coqui' => [
        'host' => env('COQUI_HOST', 'http://localhost:5002'),
        'default_voice' => env('COQUI_VOICE', 'en-us-female'),
        'speed' => env('COQUI_SPEED', 1.0),
        'timeout' => env('COQUI_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for embeddings and responses to improve performance.
    |
    */
    'cache' => [
        'enabled' => env('LOCAL_AI_CACHE_ENABLED', true),
        'driver' => env('LOCAL_AI_CACHE_DRIVER', 'redis'),
        'ttl' => env('LOCAL_AI_CACHE_TTL', 3600), // 1 hour
        'prefix' => env('LOCAL_AI_CACHE_PREFIX', 'local_ai'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Process embeddings and heavy AI tasks in background queues.
    |
    */
    'queue' => [
        'enabled' => env('LOCAL_AI_QUEUE_ENABLED', true),
        'connection' => env('LOCAL_AI_QUEUE_CONNECTION', 'redis'),
        'queue_name' => env('LOCAL_AI_QUEUE_NAME', 'local-ai'),
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG (Retrieval Augmented Generation) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for document Q&A and RAG functionality.
    |
    */
    'rag' => [
        'chunk_size' => env('RAG_CHUNK_SIZE', 1000),
        'chunk_overlap' => env('RAG_CHUNK_OVERLAP', 200),
        'top_k' => env('RAG_TOP_K', 5),
        'score_threshold' => env('RAG_SCORE_THRESHOLD', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure storage paths for generated audio and processed documents.
    |
    */
    'storage' => [
        'disk' => env('LOCAL_AI_STORAGE_DISK', 'local'),
        'audio_path' => env('LOCAL_AI_AUDIO_PATH', 'local-ai/audio'),
        'documents_path' => env('LOCAL_AI_DOCUMENTS_PATH', 'local-ai/documents'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for AI operations and debugging.
    |
    */
    'logging' => [
        'enabled' => env('LOCAL_AI_LOGGING_ENABLED', true),
        'channel' => env('LOCAL_AI_LOG_CHANNEL', 'stack'),
        'level' => env('LOCAL_AI_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Prevent abuse and manage resource usage with rate limiting.
    |
    */
    'rate_limit' => [
        'enabled' => env('LOCAL_AI_RATE_LIMIT_ENABLED', false),
        'max_requests' => env('LOCAL_AI_RATE_LIMIT_MAX', 60),
        'decay_minutes' => env('LOCAL_AI_RATE_LIMIT_DECAY', 1),
    ],
];
