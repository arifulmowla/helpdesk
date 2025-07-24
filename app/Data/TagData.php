<?php

namespace App\Data;

use App\Models\Tag;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TagData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public int $articles_count,
        public ?string $created_at = null,
    ) {
    }

    public static function fromModel(Tag $tag): self
    {
        return new self(
            id: (string) $tag->getKey(),
            name: $tag->getAttribute('name'),
            slug: $tag->getAttribute('slug'),
            articles_count: $tag->knowledge_base_articles_count ?? $tag->knowledgeBaseArticles()->count(),
            created_at: $tag->created_at?->toISOString(),
        );
    }
}
