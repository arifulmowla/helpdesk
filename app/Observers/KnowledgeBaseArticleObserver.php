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

    public function created(KnowledgeBaseArticle $article): void
    {
        if ($article->is_published) {
            $this->embedArticle($article, 'created');
        }
    }

    public function updated(KnowledgeBaseArticle $article): void
    {
        $shouldEmbed = $article->is_published && (
            $article->wasChanged(['title', 'body', 'is_published']) ||
            $article->wasChanged('tags')
        );

        $wasPublished = $article->getOriginal('is_published');

        if ($shouldEmbed) {
            $this->embedArticle($article, 'updated');
        } elseif ($wasPublished && !$article->is_published) {
            $this->deleteEmbeddings($article, 'unpublished');
        }
    }

    public function deleted(KnowledgeBaseArticle $article): void
    {
        $this->deleteEmbeddings($article, 'deleted');
    }

    public function restored(KnowledgeBaseArticle $article): void
    {
        if ($article->is_published) {
            $this->embedArticle($article, 'restored');
        }
    }

    protected function embedArticle(KnowledgeBaseArticle $article, string $action): void
    {
        try {
            dispatch(function () use ($article) {
                $this->embeddingService->embedArticle($article);
            });
        } catch (\Exception $e) {
            Log::error('Failed to queue article embedding: ' . $e->getMessage());
        }
    }

    protected function deleteEmbeddings(KnowledgeBaseArticle $article, string $action): void
    {
        try {
            dispatch(function () use ($article) {
                $this->embeddingService->deleteArticleEmbeddings($article);
            });
        } catch (\Exception $e) {
            Log::error('Failed to queue article embedding deletion: ' . $e->getMessage());
        }
    }
}
