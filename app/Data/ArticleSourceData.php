<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ArticleSourceData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $url,
        public ?string $excerpt = null,
    ) {
    }
}
