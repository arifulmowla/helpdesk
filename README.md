# Helpdesk System

A modern Laravel-based helpdesk system with Vue.js frontend for ticket management, customer support, and knowledge base articles.

## What is this?

This is a complete helpdesk solution built with Laravel 12 and Vue 3 that includes:
- AI-powered email responses based on knowledge base articles
- Knowledge base with article management
- Automated customer support using OpenAI
- Vector search with Pinecone for intelligent article matching

## Getting Started

### Quick Setup

1. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

2. **Configure your .env file with required API keys:**
   ```env
   # OpenAI API Key (required for AI features)
   OPENAI_API_KEY=your-openai-api-key

   # Pinecone API Key (required for vector search)
   PINECONE_API_KEY=your-pinecone-api-key
   PINECONE_ENVIRONMENT=your-pinecone-environment

   # Database (SQLite for quick start)
   DB_CONNECTION=sqlite
   ```

3. **Setup database and run**
   ```bash
   php artisan migrate
   php artisan db:seed
   npm run dev
   php artisan serve
   ```

## Required API Keys

- **OpenAI API Key**: Get from [platform.openai.com](https://platform.openai.com/api-keys)
- **Pinecone API Key**: Get from [pinecone.io](https://www.pinecone.io/)

Add these to your `.env` file to enable AI-powered features and vector search capabilities.
