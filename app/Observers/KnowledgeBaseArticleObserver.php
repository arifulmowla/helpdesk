<?php

namespace App\Observers;

use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBaseEmbeddingService;
use Illuminate\Support\Facades\Log;

class KnowledgeBaseArticleObserver
{
    protected KnowledgeBaseEmbeddingService $embeddingService;

    public function __construct(KnowledgeBaseEmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Handle the KnowledgeBaseArticle "created" event.
     */
    public function created(KnowledgeBaseArticle $article): void
    {
        if ($article->is_published) {
            $this->embedArticle($article, 'created');
        }
    }

    /**
     * Handle the KnowledgeBaseArticle "updated" event.
     */
    public function updated(KnowledgeBaseArticle $article): void
    {
        // Check if publication status changed or content was updated
        $shouldEmbed = $article->is_published && (
            $article->wasChanged(['title', 'body', 'is_published']) ||
            $article->wasChanged('tags') // In case tags are updated separately
        );

        $wasPublished = $article->getOriginal('is_published');

        if ($shouldEmbed) {
            $this->embedArticle($article, 'updated');
        } elseif ($wasPublished && !$article->is_published) {
            // Article was unpublished, remove embeddings
            $this->deleteEmbeddings($article, 'unpublished');
        }
    }

    /**
     * Handle the KnowledgeBaseArticle "deleted" event.
     */
    public function deleted(KnowledgeBaseArticle $article): void
    {
        $this->deleteEmbeddings($article, 'deleted');
    }

    /**
     * Handle the KnowledgeBaseArticle "restored" event.
     */
    public function restored(KnowledgeBaseArticle $article): void
    {
        if ($article->is_published) {
            $this->embedArticle($article, 'restored');
        }
    }

    /**
     * Handle embedding an article asynchronously
     */
    protected function embedArticle(KnowledgeBaseArticle $article, string $action): void
    {
        try {
            // Run embedding in background to avoid blocking the request
            dispatch(function () use ($article, $action) {
                $success = $this->embeddingService->embedArticle($article);
                
                Log::info('Article embedding queued', [
                    'article_id' => $article->id,
                    'action' => $action,
                    'success' => $success
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to queue article embedding', [
                'article_id' => $article->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle deleting embeddings asynchronously
     */
    protected function deleteEmbeddings(KnowledgeBaseArticle $article, string $action): void
    {
        try {
            // Run deletion in background to avoid blocking the request
            dispatch(function () use ($article, $action) {
                $success = $this->embeddingService->deleteArticleEmbeddings($article);
                
                Log::info('Article embedding deletion queued', [
                    'article_id' => $article->id,
                    'action' => $action,
                    'success' => $success
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to queue article embedding deletion', [
                'article_id' => $article->id,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }
}
