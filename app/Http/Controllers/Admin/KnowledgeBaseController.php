<?php

namespace App\Http\Controllers\Admin;

use App\Data\ArticleDetailData;
use App\Data\ArticleListItemData;
use App\Data\TagData;
use App\Data\Admin\AdminArticleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKnowledgeBaseArticleRequest;
use App\Http\Requests\Admin\UpdateKnowledgeBaseArticleRequest;
use App\Models\KnowledgeBaseArticle;
use App\Models\Tag;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {
        //
    }

    /**
     * Display a listing of knowledge base articles for admin.
     * Supports sorting, filtering, and soft-delete toggle.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $tagSlug = $request->input('tag');
        $sortBy = $request->input('sort_by', 'updated_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $status = $request->input('status', 'published'); // published, draft, trashed, all
        $perPage = $request->input('per_page', 15);

        $query = KnowledgeBaseArticle::with(['tags', 'createdBy', 'updatedBy']);

        // Apply status filter
        switch ($status) {
            case 'published':
                $query->where('is_published', true);
                break;
            case 'draft':
                $query->where('is_published', false);
                break;
            case 'trashed':
                $query->onlyTrashed();
                break;
            case 'all':
                $query->withTrashed();
                break;
        }

        // Apply search filter
        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        // Apply tag filter
        $currentTag = null;
        if ($tagSlug) {
            $currentTag = Tag::where('slug', $tagSlug)->first();
            if ($currentTag) {
                $query->whereHas('tags', function ($q) use ($currentTag) {
                    $q->where('tags.id', $currentTag->id);
                });
            }
        }

        // Apply sorting
        $allowedSorts = ['title', 'published_at', 'created_at', 'updated_at', 'view_count'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $articles = $query->paginate($perPage);

        // Get all tags for filter dropdown
        $tags = Tag::withCount('knowledgeBaseArticles')
            ->orderBy('name')
            ->get();

        // Current filters for frontend state
        $currentFilters = [
            'search' => $search,
            'tag' => $tagSlug,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'status' => $status,
            'per_page' => $perPage,
        ];

        return Inertia::render('Admin/KnowledgeBase/Index', [
            'articles' => ArticleListItemData::collect($articles),
            'tags' => TagData::collect($tags),
            'currentFilters' => $currentFilters,
            'currentTag' => $currentTag ? TagData::from($currentTag) : null,
        ]);
    }

    /**
     * Show the form for creating a new article.
     */
    public function create()
    {
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/KnowledgeBase/Create', [
            'tags' => TagData::collect($tags),
        ]);
    }

    /**
     * Store a newly created article in storage.
     */
    public function store(StoreKnowledgeBaseArticleRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            
            // Extract tags from data before creating article
            $tags = $data['tags'] ?? [];
            unset($data['tags']);
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $baseSlug = Str::slug($data['title']);
                $data['slug'] = $this->generateUniqueSlug($baseSlug);
            }
            
            // Set publish timestamp if publishing
            if ($data['is_published'] && empty($data['published_at'])) {
                $data['published_at'] = now();
            }
            
            // Set author
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            $article = KnowledgeBaseArticle::create($data);

            // Handle tags (including new ones)
            if (!empty($tags)) {
                $tagIds = $this->processTagsAndGetIds($tags);
                $article->tags()->sync($tagIds);
            }

            return redirect()
                ->route('admin.knowledge-base.index')
                ->with('success', 'Article created successfully.');
        });
    }

    /**
     * Display the specified article.
     */
    public function show(KnowledgeBaseArticle $article)
    {
        $article->load(['tags', 'createdBy', 'updatedBy']);

        return Inertia::render('Admin/KnowledgeBase/Show', [
            'article' => ArticleDetailData::from($article),
        ]);
    }

    /**
     * Show the form for editing the specified article.
     */
    public function edit(KnowledgeBaseArticle $article)
    {
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/KnowledgeBase/Edit', [
            'article' => AdminArticleData::from($article),
            'tags' => TagData::collect($tags),
        ]);
    }

    /**
     * Update the specified article in storage.
     */
    public function update(UpdateKnowledgeBaseArticleRequest $request, KnowledgeBaseArticle $article)
    {
        return DB::transaction(function () use ($request, $article) {
            $data = $request->validated();
            
            // Extract tags from data before updating article
            $tags = $data['tags'] ?? [];
            unset($data['tags']);
            
            // Generate slug if changed
            if (isset($data['slug']) && $data['slug'] !== $article->slug) {
                $data['slug'] = $this->generateUniqueSlug($data['slug'], $article->id);
            }
            
            // Handle publishing state changes
            if ($data['is_published'] && !$article->is_published && empty($data['published_at'])) {
                $data['published_at'] = now();
            } elseif (!$data['is_published']) {
                $data['published_at'] = null;
            }
            
            // Set updater
            $data['updated_by'] = auth()->id();

            $article->update($data);

            // Handle tags (including new ones)
            if (!empty($tags)) {
                $tagIds = $this->processTagsAndGetIds($tags);
                $article->tags()->sync($tagIds);
            } else {
                // If no tags provided, remove all tags
                $article->tags()->sync([]);
            }

            return redirect()
                ->route('admin.knowledge-base.index')
                ->with('success', 'Article updated successfully.');
        });
    }

    /**
     * Remove the specified article from storage (soft delete).
     */
    public function destroy(KnowledgeBaseArticle $article)
    {
        $article->delete();

        return redirect()
            ->route('admin.knowledge-base.index')
            ->with('success', 'Article moved to trash.');
    }

    /**
     * Restore a soft-deleted article.
     */
    public function restore($id)
    {
        $article = KnowledgeBaseArticle::withTrashed()->findOrFail($id);
        $article->restore();

        return redirect()
            ->route('admin.knowledge-base.index')
            ->with('success', 'Article restored successfully.');
    }

    /**
     * Permanently delete a soft-deleted article.
     */
    public function forceDelete($id)
    {
        $article = KnowledgeBaseArticle::withTrashed()->findOrFail($id);
        $article->forceDelete();

        return redirect()
            ->route('admin.knowledge-base.index')
            ->with('success', 'Article permanently deleted.');
    }

    /**
     * Handle image uploads for TipTap editor.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            $url = $this->fileUploadService->uploadImage(
                $request->file('image'),
                'knowledge-base/images'
            );

            return response()->json([
                'success' => true,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle file uploads for TipTap editor.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,txt,zip|max:10240', // 10MB max
        ]);

        try {
            $url = $this->fileUploadService->uploadFile(
                $request->file('file'),
                'knowledge-base/files'
            );

            return response()->json([
                'success' => true,
                'url' => $url,
                'name' => $request->file('file')->getClientOriginalName(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process tags array and return IDs, creating new tags as needed.
     */
    private function processTagsAndGetIds(array $tags): array
    {
        $tagIds = [];
        
        foreach ($tags as $tagData) {
            if (is_array($tagData)) {
                // If it's an array with id and name
                if (isset($tagData['id']) && !str_starts_with($tagData['id'], 'new-')) {
                    // Existing tag
                    $tagIds[] = $tagData['id'];
                } elseif (isset($tagData['name'])) {
                    // New tag - create it
                    $existingTag = Tag::where('name', $tagData['name'])->first();
                    if ($existingTag) {
                        $tagIds[] = $existingTag->id;
                    } else {
                        // Create with unique slug
                        $baseSlug = Str::slug($tagData['name']);
                        $uniqueSlug = $this->generateUniqueTagSlug($baseSlug);
                        $tag = Tag::create([
                            'name' => $tagData['name'],
                            'slug' => $uniqueSlug
                        ]);
                        $tagIds[] = $tag->id;
                    }
                }
            } else {
                // If it's just an ID
                $tagIds[] = $tagData;
            }
        }
        
        return $tagIds;
    }

    /**
     * Generate a unique slug for tags.
     */
    private function generateUniqueTagSlug(string $baseSlug, ?int $excludeId = null): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = Tag::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->exists()) {
                return $slug;
            }
            
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }

    /**
     * Generate a unique slug for the article.
     */
    private function generateUniqueSlug(string $baseSlug, ?int $excludeId = null): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = KnowledgeBaseArticle::where('slug', $slug);
            
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->exists()) {
                return $slug;
            }
            
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }
}
