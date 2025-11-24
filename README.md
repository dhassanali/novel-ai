# Novel Editor & Generator

A powerful, local AI-assisted novel writing application built with Laravel. Leverage the power of local LLMs to generate story ideas, continue chapters, and manage your novel's context—all without API fees or privacy concerns.

## Features

- ✍️ **AI Story Generation** - Continue your story with context-aware AI suggestions using Ollama (Llama 3, Mistral, etc.)
- 📚 **Context Management** - Manage novels, chapters, and characters
- 🧠 **RAG (Retrieval-Augmented Generation)** - Upload reference documents and web links to guide the AI's writing style and lore
- 🔍 **Semantic Search** - Vector embeddings with Qdrant for deep context understanding
- 🔒 **Privacy-First** - All data stays on your machine
- ⚡ **Real-time Editor** - Modern writing interface

## Requirements

- Docker & Docker Compose
- PHP 8.2+
- Node.js & NPM
- Laravel 10+
- At least 8GB RAM (16GB recommended for larger models)

## Quick Start

### 1. Setup Project

```bash
# Clone the repository
git clone <repository-url>
cd local-ai

# Install PHP dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Install Node dependencies
npm install
npm run build
```

### 2. Start Services

Start the AI services (Ollama, Qdrant) and the database:

```bash
docker-compose up -d
```

### 3. Initialize Database

```bash
php artisan migrate
```

### 4. Pull AI Models

```bash
# Pull the default language model
php artisan local-ai:pull-model llama3.1
```

### 5. Run the Application

```bash
# Start the Laravel development server
php artisan serve

# In a separate terminal, run the queue worker (for background AI tasks)
php artisan queue:listen
```

Visit `http://localhost:8000` to start writing!

## Usage

### Writing Assistant

The editor provides an AI "Continue" button that analyzes your current chapter and previous context to suggest the next paragraphs.

### Knowledge Base

Upload PDF documents or add web links to your novel's "Source Documents". The AI will reference these materials when generating text, ensuring consistency with your lore and world-building.

## Configuration

Configure your AI settings in `.env`:

```env
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=llama3.1
QDRANT_HOST=http://localhost:6333
```

## Architecture

- **Laravel**: Backend framework
- **Ollama**: Local LLM inference
- **Qdrant**: Vector database for semantic search and RAG
- **React/Inertia**: Frontend interface

## License

MIT License
