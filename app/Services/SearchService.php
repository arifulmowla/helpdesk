<?php

namespace App\Services;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{
    /**
     * Search knowledge base articles using Scout if available, 
     * otherwise fall back to native database search
     */
    public function searchArticles(?string $query, ?string $tagSlug = null, int $perPage = 10): LengthAwarePaginator
    {
        // If no search query, return all published articles with optional tag filter
        if (empty($query)) {
            return $this->getAllArticles($tagSlug, $perPage);
        }

        if ($this->isScoutAvailable()) {
            return $this->searchWithScout($query, $tagSlug, $perPage);
        }

        return $this->searchWithNativeDatabase($query, $tagSlug, $perPage);
    }

    /**
     * Get all published articles with optional tag filter
     */
    protected function getAllArticles(?string $tagSlug = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = KnowledgeBaseArticle::query()
            ->where('is_published', true)
            ->with('tags');

        if ($tagSlug) {
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('tags.slug', $tagSlug);
            });
        }

        return $query->orderBy('published_at', 'desc')->paginate($perPage);
    }

    /**
     * Check if Laravel Scout is installed and configured
     */
    public function isScoutAvailable(): bool
    {
        return class_exists('\Laravel\Scout\Searchable') 
            && method_exists(KnowledgeBaseArticle::class, 'search');
    }

    /**
     * Search using Laravel Scout
     */
    protected function searchWithScout(string $query, ?string $tagSlug = null, int $perPage = 10): LengthAwarePaginator
    {
        $searchQuery = KnowledgeBaseArticle::search($query)
            ->where('is_published', true);

        if ($tagSlug) {
            // Apply tag filter using query builder callback
            $searchQuery->query(function ($builder) use ($tagSlug) {
                $builder->whereHas('tags', function ($q) use ($tagSlug) {
                    $q->where('tags.slug', $tagSlug);
                });
            });
        }

        return $searchQuery->paginate($perPage);
    }

    /**
     * Search using native database queries with FULLTEXT for MySQL 
     * or FTS5 for SQLite
     */
    protected function searchWithNativeDatabase(string $query, ?string $tagSlug = null, int $perPage = 10): LengthAwarePaginator
    {
        $dbDriver = config('database.default');
        $connection = config("database.connections.{$dbDriver}.driver");

        if ($connection === 'sqlite') {
            return $this->searchSqliteWithFts($query, $tagSlug, $perPage);
        } elseif ($connection === 'mysql') {
            return $this->searchMysqlWithFulltext($query, $tagSlug, $perPage);
        } else {
            // Fallback to LIKE search for other database drivers
            return $this->searchWithLike($query, $tagSlug, $perPage);
        }
    }

    /**
     * Search using SQLite with raw_body field
     */
    protected function searchSqliteWithFts(string $query, ?string $tagSlug = null, int $perPage = 10): LengthAwarePaginator
    {
        // Use LIKE search on raw_body since we removed the FTS table
        return $this->searchWithLike($query, $tagSlug, $perPage);
    }

    /**
     * Search using MySQL FULLTEXT on raw_body
     */
    protected function searchMysqlWithFulltext(string $query, ?string $tagSlug = null, int $perPage = 10): LengthAwarePaginator
    {
        $searchQuery = KnowledgeBaseArticle::query()
            ->whereRaw('MATCH(title, raw_body) AGAINST(? IN BOOLEAN MODE)', [$this->prepareMysqlQuery($query)])
            ->where('is_published', true)
            ->with('tags');

        if ($tagSlug) {
            $searchQuery->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('tags.slug', $tagSlug);
            });
        }

        return $searchQuery->orderByRaw('MATCH(title, raw_body) AGAINST(?) DESC', [$query])
            ->paginate($perPage);
    }

    /**
     * Fallback search using LIKE queries on raw_body
     */
    protected function searchWithLike(string $query, ?string $tagSlug = null, int $perPage = 10): LengthAwarePaginator
    {
        $searchQuery = KnowledgeBaseArticle::query()
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('excerpt', 'like', "%{$query}%")
                  ->orWhere('raw_body', 'like', "%{$query}%");
            })
            ->with('tags');

        if ($tagSlug) {
            $searchQuery->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('tags.slug', $tagSlug);
            });
        }

        return $searchQuery->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }



    /**
     * Prepare query for MySQL FULLTEXT
     */
    protected function prepareMysqlQuery(string $query): string
    {
        // Add wildcard for partial matching
        $terms = explode(' ', trim($query));
        return '+' . implode('* +', $terms) . '*';
    }

    /**
     * Get highlighted search results
     */
    public function getHighlightedResults(string $query, LengthAwarePaginator $results): LengthAwarePaginator
    {
        $results->getCollection()->transform(function ($article) use ($query) {
            $article->highlighted_title = $this->highlightText($article->title, $query);
            $article->highlighted_excerpt = $this->highlightText($article->excerpt, $query);
            return $article;
        });

        return $results;
    }

    /**
     * Highlight matching terms in text
     */
    protected function highlightText(string $text, string $query): string
    {
        if (empty($query) || empty($text)) {
            return $text;
        }

        $terms = array_filter(explode(' ', $query));
        
        foreach ($terms as $term) {
            $pattern = '/(' . preg_quote($term, '/') . ')/i';
            $text = preg_replace($pattern, '<mark>$1</mark>', $text);
        }

        return $text;
    }
}
