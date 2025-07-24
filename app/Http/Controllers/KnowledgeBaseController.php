<?php

namespace App\Http\Controllers;

use App\Data\ArticleDetailData;
use App\Data\ArticleListItemData;
use App\Data\TagData;
use App\Http\Requests\KnowledgeBaseIndexRequest;
use App\Models\KnowledgeBaseArticle;
use App\Models\Tag;
use App\Services\SearchService;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    /**
     * Display a listing of published knowledge base articles.
     * Accept search, tag, page query parameters.
     * Return paginated ArticleListItemDTO collection, TagDTO list, current filters.
     */
    public function index(KnowledgeBaseIndexRequest $request)
    {
        $search = $request->input('search');
        $tagSlug = $request->input('tag');

        $searchService = new SearchService();
        $articles = $searchService->searchArticles($search, $tagSlug);

        // Highlight search results if there's a search query
        if (!empty($search)) {
            $articles = $searchService->getHighlightedResults($search, $articles);
        }

        $currentTag = $tagSlug ? Tag::where('slug', $tagSlug)->first() : null;

        $tags = Tag::withCount('knowledgeBaseArticles')
            ->orderBy('name')
            ->get();

        // Current filters for frontend state
        $currentFilters = [
            'search' => $search,
            'tag' => $tagSlug,
            'page' => $request->input('page', 1),
        ];

        return Inertia::render('KnowledgeBase/Index', [
            'articles' => ArticleListItemData::collect($articles),
            'tags' => TagData::collect($tags),
            'currentFilters' => $currentFilters,
            'currentTag' => $currentTag ? TagData::from($currentTag) : null,
        ]);
    }

    /**
     * Display the specified knowledge base article.
     * Retrieve by slug, 404 if not published.
     * Return ArticleDetailDTO with related tags and prev/next links.
     * Optionally increment view counter.
     */
    public function show(string $slug)
    {
        $article = KnowledgeBaseArticle::with(['tags', 'createdBy'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Increment view counter
        $article->increment('view_count');

        return Inertia::render('KnowledgeBase/Show', [
            'article' => ArticleDetailData::from($article),
        ]);
    }

}
