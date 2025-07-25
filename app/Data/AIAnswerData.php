<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AIAnswerData extends Data
{
    public function __construct(
        public string $answer,
        #[DataCollectionOf(ArticleSourceData::class)]
        public ?DataCollection $sources = null,
        public string $query = '',
        public string $timestamp = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        $sources = null;
        if (isset($data['sources']) && is_iterable($data['sources'])) {
            $sourcesArray = collect($data['sources'])->toArray();
            $sources = new DataCollection(ArticleSourceData::class, 
                collect($sourcesArray)->map(fn ($source) => ArticleSourceData::from($source))
            );
        }

        return new self(
            answer: $data['answer'] ?? '',
            sources: $sources,
            query: $data['query'] ?? '',
            timestamp: $data['timestamp'] ?? now()->toISOString(),
        );
    }
}
