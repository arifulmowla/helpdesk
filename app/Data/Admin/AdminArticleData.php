<?php

namespace App\Data\Admin;

use App\Data\TagData;
use App\Data\UserData;
use App\Models\KnowledgeBaseArticle;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AdminArticleData extends Data
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
        /** @var array<int> */
        public array $tag_ids,
        /** @var string[] */
        public array $tag_names,
        public array $body,
        public ?UserData $created_by,
        public ?UserData $updated_by,
        /** @var TagData[] */
        public array $tags,
    ) {
    }

    public static function fromModel(KnowledgeBaseArticle $article): self
    {
        $article->load(['tags', 'createdBy', 'updatedBy']);

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
            tag_ids: $article->tags->pluck('id')->toArray(),
            tag_names: $article->tags->pluck('name')->toArray(),
            body: $article->getAttribute('body') ?? [],
            created_by: UserData::fromModel($article->createdBy),
            updated_by: UserData::fromModel($article->updatedBy),
            tags: $article->tags->map(fn($tag) => TagData::fromModel($tag))->all(),
        );
    }
}
