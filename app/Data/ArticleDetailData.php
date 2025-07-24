<?php

namespace App\Data;

use App\Models\KnowledgeBaseArticle;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ArticleDetailData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public ?string $excerpt,
        public bool $is_published,
        public ?string $published_at,
        public ?string $created_at,
        public ?string $updated_at,
        public ?string $deleted_at,
        public int $view_count,
        /** @var string[] */
        public array $tag_names,
        public array $body,
        public ?array $author,
        public ?array $created_by,
        public ?array $updated_by,
        public Lazy|array $tags,
        public ?array $previous_article,
        public ?array $next_article,
    ) {
    }

    public static function fromModel(KnowledgeBaseArticle $article): self
    {
        // Get previous and next articles (published only, ordered by published_at)
        $previousArticle = KnowledgeBaseArticle::where('is_published', true)
            ->where('published_at', '<', $article->published_at)
            ->orderBy('published_at', 'desc')
            ->first();

        $nextArticle = KnowledgeBaseArticle::where('is_published', true)
            ->where('published_at', '>', $article->published_at)
            ->orderBy('published_at', 'asc')
            ->first();

        return new self(
            id: $article->getKey(),
            title: $article->getAttribute('title'),
            slug: $article->getAttribute('slug'),
            excerpt: $article->getAttribute('excerpt'),
            is_published: $article->getAttribute('is_published') ?? false,
            published_at: $article->published_at?->format('Y-m-d H:i:s'),
            created_at: $article->created_at?->format('Y-m-d H:i:s'),
            updated_at: $article->updated_at?->format('Y-m-d H:i:s'),
            deleted_at: $article->deleted_at?->format('Y-m-d H:i:s'),
            view_count: $article->getAttribute('view_count') ?? 0,
            tag_names: $article->tags->pluck('name')->toArray(),
            body: $article->getAttribute('body') ?? [],
            author: $article->createdBy ? [
                'id' => $article->createdBy->id,
                'name' => $article->createdBy->name,
                'email' => $article->createdBy->email,
            ] : null,
            created_by: $article->createdBy ? [
                'id' => $article->createdBy->id,
                'name' => $article->createdBy->name,
                'email' => $article->createdBy->email,
            ] : null,
            updated_by: $article->updatedBy ? [
                'id' => $article->updatedBy->id,
                'name' => $article->updatedBy->name,
                'email' => $article->updatedBy->email,
            ] : null,
            tags: Lazy::whenLoaded('tags', $article, fn () => TagData::collect($article->tags)),
            previous_article: $previousArticle ? [
                'id' => $previousArticle->getKey(),
                'title' => $previousArticle->getAttribute('title'),
                'slug' => $previousArticle->getAttribute('slug'),
            ] : null,
            next_article: $nextArticle ? [
                'id' => $nextArticle->getKey(),
                'title' => $nextArticle->getAttribute('title'),
                'slug' => $nextArticle->getAttribute('slug'),
            ] : null,
        );
    }
}
