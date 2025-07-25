<?php

namespace App\Models;

// Only extend with Scout if it's available
if (class_exists('\Laravel\Scout\Searchable')) {
    class SearchableKnowledgeBaseArticle extends KnowledgeBaseArticle
    {
        use \Laravel\Scout\Searchable;

        /**
         * Determine if the model should be searchable.
         */
        public function shouldBeSearchable(): bool
        {
            return $this->is_published;
        }

        /**
         * Get the indexable data array for the model.
         */
        public function toSearchableArray(): array
        {
            // Only include published articles in search index
            if (!$this->is_published) {
                return [];
            }

            // Convert JSON body to searchable text
            if (is_array($this->body)) {
                $bodyText = $this->raw_body;
            } else {
                $bodyText = $this->body ?? '';
            }

            return [
                'id' => $this->id,
                'title' => $this->title,
                'excerpt' => $this->excerpt,
                'body' => $bodyText,
                'is_published' => $this->is_published,
                'published_at' => $this->published_at?->toDateTimeString(),
            ];
        }
    }
} else {
    // Fallback class when Scout is not available
    class SearchableKnowledgeBaseArticle extends KnowledgeBaseArticle
    {
        // No additional functionality needed
    }
}
