<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Exceptions\PrismException;

class EmbeddingService
{
    /**
     * Generate embedding for a text string
     */
    public function generateEmbedding(string $text): ?array
    {
        try {
            $response = Prism::embeddings()
                ->using(Provider::OpenAI, config('ai.embeddings.model'))
                ->fromInput($text)
                ->asEmbeddings();

            // Get the first embedding from the response
            if (isset($response->embeddings[0])) {
                return $response->embeddings[0]->embedding;
            }
            
            return null;
        } catch (PrismException $e) {
            Log::error('Prism embedding generation failed', [
                'text' => substr($text, 0, 100) . '...',
                'error' => $e->getMessage()
            ]);
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
        $chunks = $this->chunkText($text);
        $results = [];

        foreach ($chunks as $index => $chunk) {
            $embedding = $this->generateEmbedding($chunk);

            if ($embedding) {
                $results[] = [
                    'embedding' => $embedding,
                    'text' => $chunk,
                    'metadata' => array_merge($metadata, [
                        'chunk_index' => $index,
                        'chunk_count' => count($chunks)
                    ])
                ];
            }
        }

        return $results;
    }
}
