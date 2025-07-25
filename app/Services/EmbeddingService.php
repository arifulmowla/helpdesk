<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class EmbeddingService
{
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
                $errorData = $response->json();
                $errorMessage = $errorData['error']['message'] ?? 'Unknown OpenAI API error';
                
                Log::error('OpenAI embedding API error: ' . $errorMessage);
                return null;
            }

            $data = $response->json();
            
            if (isset($data['data'][0]['embedding'])) {
                return $data['data'][0]['embedding'];
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Embedding generation failed: ' . $e->getMessage());
            return null;
        }
    }

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

            $start = max($start + 1, $end - $overlap);
        }

        return array_filter($chunks);
    }

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
            } else {
                Log::error('Embedding generation failed for chunk ' . $index);
            }
        }

        return $results;
    }
}
