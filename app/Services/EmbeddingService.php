<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class EmbeddingService
{
    /**
     * Generate embedding for a text string
     */
    public function generateEmbedding(string $text): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('ai.openai.api_key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => config('ai.embeddings.model'),
                'input' => $text,
                'encoding_format' => 'float'
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown OpenAI API error';
                
                Log::error('OpenAI embedding API request failed', [
                    'status' => $response->status(),
                    'error_message' => $errorMessage,
                    'full_response' => $errorBody,
                    'text' => substr($text, 0, 100) . '...'
                ]);
                
                return null;
            }

            $data = $response->json();
            
            // Get the first embedding from the response
            if (isset($data['data'][0]['embedding'])) {
                return $data['data'][0]['embedding'];
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Embedding generation failed', [
                'text' => substr($text, 0, 100) . '...',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate embeddings for multiple text strings
     */
    public function generateBatchEmbeddings(array $texts): array
    {
        $embeddings = [];

        foreach ($texts as $index => $text) {
            $embedding = $this->generateEmbedding($text);
            if ($embedding) {
                $embeddings[$index] = $embedding;
            }
        }

        return $embeddings;
    }

    /**
     * Split text into chunks for embedding
     */
    public function chunkText(string $text, int $chunkSize = null, int $overlap = null): array
    {
        $chunkSize = $chunkSize ?? config('ai.embeddings.chunk_size', 1000);
        $overlap = $overlap ?? config('ai.embeddings.chunk_overlap', 200);

        if (strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $start = 0;

        while ($start < strlen($text)) {
            $end = min($start + $chunkSize, strlen($text));

            // Try to break at a sentence or word boundary
            if ($end < strlen($text)) {
                $lastPeriod = strrpos(substr($text, $start, $chunkSize), '.');
                $lastSpace = strrpos(substr($text, $start, $chunkSize), ' ');

                if ($lastPeriod !== false && $lastPeriod > $chunkSize * 0.7) {
                    $end = $start + $lastPeriod + 1;
                } elseif ($lastSpace !== false && $lastSpace > $chunkSize * 0.7) {
                    $end = $start + $lastSpace;
                }
            }

            $chunk = substr($text, $start, $end - $start);
            $chunks[] = trim($chunk);

            // Move start position, accounting for overlap
            $start = max($start + 1, $end - $overlap);
        }

        return array_filter($chunks);
    }

    /**
     * Generate embeddings for text chunks and return with metadata
     */
    public function generateChunkEmbeddings(string $text, array $metadata = []): array
    {
        Log::info('Starting generateChunkEmbeddings', [
            'text_length' => strlen($text),
            'text_preview' => substr($text, 0, 100) . '...'
        ]);
        
        $chunks = $this->chunkText($text);
        $results = [];

        Log::info('Text chunked', [
            'chunk_count' => count($chunks),
            'chunks_preview' => array_map(fn($chunk) => substr($chunk, 0, 50) . '...', array_slice($chunks, 0, 2))
        ]);

        foreach ($chunks as $index => $chunk) {
            Log::info('Generating embedding for chunk', [
                'chunk_index' => $index,
                'chunk_length' => strlen($chunk),
                'chunk_preview' => substr($chunk, 0, 100) . '...'
            ]);
            
            $embedding = $this->generateEmbedding($chunk);

            if ($embedding) {
                Log::info('Embedding generated successfully', [
                    'chunk_index' => $index,
                    'embedding_dimension' => count($embedding)
                ]);
                
                $results[] = [
                    'embedding' => $embedding,
                    'text' => $chunk,
                    'metadata' => array_merge($metadata, [
                        'chunk_index' => $index,
                        'chunk_count' => count($chunks)
                    ])
                ];
            } else {
                Log::error('Embedding generation failed for chunk', [
                    'chunk_index' => $index,
                    'chunk_length' => strlen($chunk),
                    'chunk_preview' => substr($chunk, 0, 100) . '...'
                ]);
            }
        }

        Log::info('generateChunkEmbeddings completed', [
            'total_chunks' => count($chunks),
            'successful_embeddings' => count($results)
        ]);

        return $results;
    }
}
