<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenAI API integration.
    |
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pinecone Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Pinecone vector database integration.
    |
    */
    'pinecone' => [
        'api_key' => env('PINECONE_API_KEY'),
        'index_name' => env('PINECONE_INDEX_NAME', 'helpdesk'),
        'index_host' => env('PINECONE_INDEX_HOST'), // Full index URL from Pinecone dashboard
        'environment' => env('PINECONE_ENVIRONMENT', 'gcp-starter'),
        'dimension' => env('PINECONE_DIMENSION', 3072), // OpenAI text-embedding-3-large dimension
        'metric' => env('PINECONE_METRIC', 'cosine'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for text embedding generation.
    |
    */
    'embeddings' => [
        'model' => env('EMBEDDING_MODEL', 'text-embedding-3-large'),
        'chunk_size' => env('EMBEDDING_CHUNK_SIZE', 1000),
        'chunk_overlap' => env('EMBEDDING_CHUNK_OVERLAP', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Retrieval-Augmented Generation.
    |
    */
    'rag' => [
        'max_context_articles' => env('RAG_MAX_CONTEXT_ARTICLES', 5),
        'similarity_threshold' => env('RAG_SIMILARITY_THRESHOLD', 0.7),
        'max_context_length' => env('RAG_MAX_CONTEXT_LENGTH', 4000),
    ],
];
