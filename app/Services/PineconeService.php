<?php

namespace App\Services;

use Probots\Pinecone\Client as PineconeClient;
use Illuminate\Support\Facades\Log;
use Exception;

class PineconeService
{
    protected PineconeClient $client;
    protected string $indexName;

    public function __construct()
    {
        $indexHost = config('ai.pinecone.index_host');
        
        if ($indexHost) {
            // Initialize with both API key and index host
            $this->client = new PineconeClient(
                apiKey: config('ai.pinecone.api_key'),
                indexHost: $indexHost
            );
        } else {
            // Initialize with just API key, set index host later if needed
            $this->client = new PineconeClient(
                apiKey: config('ai.pinecone.api_key')
            );
        }
        
        $this->indexName = config('ai.pinecone.index_name');
    }

    /**
     * Store a vector in Pinecone
     */
    public function upsert(string $id, array $vector, array $metadata = []): bool
    {
        try {
            Log::info('PineconeService upsert called', [
                'id' => $id,
                'vector_dimension' => count($vector),
                'vector_type' => gettype($vector),
                'vector_preview' => array_slice($vector, 0, 5),
                'metadata_keys' => array_keys($metadata)
            ]);
            
            $response = $this->client->data()->vectors()->upsert(
                vectors: [
                    [
                        'id' => $id,
                        'values' => $vector,
                        'metadata' => $metadata,
                    ]
                ]
            );

            if ($response->successful()) {
                Log::info('Pinecone upsert successful', ['id' => $id]);
                return true;
            } else {
                Log::error('Pinecone upsert failed with response', [
                    'id' => $id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (Exception $e) {
            Log::error('Pinecone upsert failed with exception', [
                'id' => $id,
                'error' => $e->getMessage(),
                'vector_dimension' => count($vector)
            ]);
            return false;
        }
    }

    /**
     * Query vectors by similarity
     */
    public function query(array $vector, int $topK = 5, array $filter = []): array
    {
        try {
            $queryParams = [
                'vector' => $vector,
                'topK' => $topK,
                'includeMetadata' => true,
            ];

            if (!empty($filter)) {
                $queryData['filter'] = $filter;
            }

            $response = $this->client->data()->vectors()->query(
                vector: $vector,
                topK: $topK,
                includeMetadata: true,
                filter: !empty($filter) ? $filter : null
            );

            if ($response->successful()) {
                $data = $response->json();
                return $data['matches'] ?? [];
            }

            return [];
        } catch (Exception $e) {
            Log::error('Pinecone query failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Delete a vector from Pinecone
     */
    public function delete(string $id): bool
    {
        try {
            $response = $this->client->index($this->indexName)->vectors()->delete([
                'ids' => [$id]
            ]);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Pinecone delete failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete vectors by metadata filter
     */
    public function deleteByFilter(array $filter): bool
    {
        try {
            $response = $this->client->data()->vectors()->delete(
                filter: $filter
            );

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Pinecone delete by filter failed', [
                'filter' => $filter,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get index stats
     */
    public function getStats(): array
    {
        try {
            $response = $this->client->data()->vectors()->stats();

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (Exception $e) {
            Log::error('Pinecone stats failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check if index exists and is ready
     */
    public function isReady(): bool
    {
        try {
            $response = $this->client->data()->vectors()->stats();
            return $response->successful();
        } catch (Exception $e) {
            Log::error('Pinecone readiness check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
