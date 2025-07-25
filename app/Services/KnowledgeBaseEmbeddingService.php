<?php

namespace App\Services;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Support\Facades\Log;
use Exception;

class KnowledgeBaseEmbeddingService
{
    protected PineconeService $pinecone;
    protected EmbeddingService $embeddingService;

    public function __construct(PineconeService $pinecone, EmbeddingService $embeddingService)
    {
        $this->pinecone = $pinecone;
        $this->embeddingService = $embeddingService;
    }

    public function embedArticle(KnowledgeBaseArticle $article): bool
    {
        try {
            $content = $article->getPlainTextContent();
            
            if (empty($content)) {
                Log::warning('Article has no content to embed: ' . $article->id);
                return false;
            }

            $textToEmbed = $article->title . "\n\n" . $content;

            $chunks = $this->embeddingService->generateChunkEmbeddings($textToEmbed, [
                'article_id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'is_published' => $article->is_published,
                'updated_at' => $article->updated_at?->toISOString(),
            ]);

            if (empty($chunks)) {
                Log::error('Failed to generate embeddings for article: ' . $article->id);
                return false;
            }

            $this->deleteArticleEmbeddings($article);

            $successCount = 0;
            foreach ($chunks as $index => $chunk) {
                $vectorId = $this->generateVectorId($article->id, $index);
                
                $success = $this->pinecone->upsert(
                    $vectorId,
                    $chunk['embedding'],
                    $chunk['metadata']
                );

                if ($success) {
                    $successCount++;
                } else {
                    Log::error('Failed to store embedding chunk for article: ' . $article->id);
                }
            }

            return $successCount > 0;
        } catch (Exception $e) {
            Log::error('Article embedding failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteArticleEmbeddings(KnowledgeBaseArticle $article): bool
    {
        try {
            $success = $this->pinecone->deleteByFilter([
                'article_id' => $article->id
            ]);

            if (!$success) {
                Log::warning('Failed to delete article embeddings: ' . $article->id);
            }

            return $success;
        } catch (Exception $e) {
            Log::error('Error deleting article embeddings: ' . $e->getMessage());
            return false;
        }
    }

    public function embedAllArticles(): array
    {
        $results = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        $articles = KnowledgeBaseArticle::where('is_published', true)->get();
        $results['total'] = $articles->count();

        foreach ($articles as $article) {
            try {
                if ($this->embedArticle($article)) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to embed article {$article->id}: {$article->title}";
                }
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Exception embedding article {$article->id}: " . $e->getMessage();
            }
        }

        return $results;
    }

    public function reEmbedArticle(KnowledgeBaseArticle $article): bool
    {
        return $this->embedArticle($article);
    }

    protected function generateVectorId(string $articleId, int $chunkIndex): string
    {
        return "article_{$articleId}_chunk_{$chunkIndex}";
    }

    public function isReady(): bool
    {
        return $this->pinecone->isReady();
    }

    public function getStats(): array
    {
        try {
            $pineconeStats = $this->pinecone->getStats();
            $publishedArticles = KnowledgeBaseArticle::where('is_published', true)->count();

            return [
                'pinecone_ready' => $this->isReady(),
                'pinecone_stats' => $pineconeStats,
                'published_articles' => $publishedArticles,
                'estimated_vectors' => $pineconeStats['totalVectorCount'] ?? 0,
            ];
        } catch (Exception $e) {
            Log::error('Failed to get embedding stats: ' . $e->getMessage());
            return [
                'pinecone_ready' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
