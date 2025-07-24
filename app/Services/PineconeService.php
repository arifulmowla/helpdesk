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
        $this->client = new PineconeClient(
            apiKey: config('ai.pinecone.api_key')
        );
        $this->indexName = config('ai.pinecone.index_name');
    }

    /**
     * Store a vector in Pinecone
     */
    public function upsert(string $id, array $vector, array $metadata = []): bool
    {
        try {
            $response = $this->client->index($this->indexName)->vectors()->upsert([
                'vectors' => [
                    [
                        'id' => $id,
                        'values' => $vector,
                        'metadata' => $metadata,
                    ]
                ]
            ]);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Pinecone upsert failed', [
                'id' => $id,
                'error' => $e->getMessage()
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
                'includeValues' => false,
            ];

            if (!empty($filter)) {
                $queryParams['filter'] = $filter;
            }

            // Temporarily disable Pinecone due to API compatibility issues
            Log::warning('Pinecone query temporarily disabled due to API compatibility issues');
            return [];

            // Original code (commented out until API is fixed):
            // $response = $this->client->index($this->indexName)->vectors()->query($queryParams);
            // if ($response->successful()) {
            //     return $response->json()['matches'] ?? [];
            // }
            // return [];
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
            $response = $this->client->index($this->indexName)->vectors()->delete([
                'filter' => $filter
            ]);

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
            $response = $this->client->index($this->indexName)->describeIndexStats();

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
            $response = $this->client->index($this->indexName)->describeIndexStats();
            return $response->successful();
        } catch (Exception $e) {
            Log::error('Pinecone readiness check failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
