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

    /**
     * Embed and store a knowledge base article in Pinecone
     */
    public function embedArticle(KnowledgeBaseArticle $article): bool
    {
        try {
            // Get the plain text content from the article
            $content = $article->getPlainTextContent();
            
            if (empty($content)) {
                Log::warning('Article has no content to embed', ['article_id' => $article->id]);
                return false;
            }

            // Prepare the text for embedding (title + content)
            $textToEmbed = $article->title . "\n\n" . $content;

            // Generate chunks with embeddings
            $chunks = $this->embeddingService->generateChunkEmbeddings($textToEmbed, [
                'article_id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'is_published' => $article->is_published,
                'updated_at' => $article->updated_at?->toISOString(),
            ]);

            if (empty($chunks)) {
                Log::error('Failed to generate embeddings for article', ['article_id' => $article->id]);
                return false;
            }

            // Delete existing vectors for this article first
            $this->deleteArticleEmbeddings($article);

            // Store each chunk in Pinecone
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
                    Log::error('Failed to store embedding chunk', [
                        'article_id' => $article->id,
                        'chunk_index' => $index,
                        'vector_id' => $vectorId
                    ]);
                }
            }

            Log::info('Article embedding completed', [
                'article_id' => $article->id,
                'chunks_created' => count($chunks),
                'chunks_stored' => $successCount
            ]);

            return $successCount > 0;
        } catch (Exception $e) {
            Log::error('Article embedding failed', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete embeddings for an article from Pinecone
     */
    public function deleteArticleEmbeddings(KnowledgeBaseArticle $article): bool
    {
        try {
            // Delete by metadata filter
            $success = $this->pinecone->deleteByFilter([
                'article_id' => $article->id
            ]);

            if ($success) {
                Log::info('Deleted article embeddings', ['article_id' => $article->id]);
            } else {
                Log::warning('Failed to delete article embeddings', ['article_id' => $article->id]);
            }

            return $success;
        } catch (Exception $e) {
            Log::error('Error deleting article embeddings', [
                'article_id' => $article->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Embed all published articles
     */
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

        Log::info('Starting bulk article embedding', ['count' => $results['total']]);

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

        Log::info('Bulk article embedding completed', $results);

        return $results;
    }

    /**
     * Re-embed an article (useful when content changes)
     */
    public function reEmbedArticle(KnowledgeBaseArticle $article): bool
    {
        // Simply call embedArticle - it will delete existing embeddings first
        return $this->embedArticle($article);
    }

    /**
     * Generate a unique vector ID for an article chunk
     */
    protected function generateVectorId(int $articleId, int $chunkIndex): string
    {
        return "article_{$articleId}_chunk_{$chunkIndex}";
    }

    /**
     * Check if Pinecone is ready and configured
     */
    public function isReady(): bool
    {
        return $this->pinecone->isReady();
    }

    /**
     * Get embedding statistics
     */
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
            Log::error('Failed to get embedding stats', ['error' => $e->getMessage()]);
            return [
                'pinecone_ready' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
