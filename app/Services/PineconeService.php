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
        $apiKey = config('ai.pinecone.api_key');
        
        $this->client = new PineconeClient(
            apiKey: $apiKey,
            indexHost: $indexHost ?: null
        );
        
        $this->indexName = config('ai.pinecone.index_name');
    }

    public function upsert(string $id, array $vector, array $metadata = []): bool
    {
        try {
            $response = $this->client->data()->vectors()->upsert(
                vectors: [
                    [
                        'id' => $id,
                        'values' => $vector,
                        'metadata' => $metadata,
                    ]
                ]
            );

            if (!$response->successful()) {
                Log::error('Pinecone upsert failed', [
                    'id' => $id,
                    'status' => $response->status()
                ]);
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Pinecone upsert failed: ' . $e->getMessage());
            return false;
        }
    }

    public function query(array $vector, int $topK = 5, array $filter = []): array
    {
        try {
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
            Log::error('Pinecone query failed: ' . $e->getMessage());
            return [];
        }
    }

    public function delete(string $id): bool
    {
        try {
            $response = $this->client->data()->vectors()->delete(
                ids: [$id]
            );

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Pinecone delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteByFilter(array $filter): bool
    {
        try {
            $response = $this->client->data()->vectors()->delete(
                filter: $filter
            );

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Pinecone delete by filter failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getStats(): array
    {
        try {
            $response = $this->client->data()->vectors()->stats();

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (Exception $e) {
            Log::error('Pinecone stats failed: ' . $e->getMessage());
            return [];
        }
    }

    public function isReady(): bool
    {
        try {
            $response = $this->client->data()->vectors()->stats();
            return $response->successful();
        } catch (Exception $e) {
            Log::error('Pinecone readiness check failed: ' . $e->getMessage());
            return false;
        }
    }
}
