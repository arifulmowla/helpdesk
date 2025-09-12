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
        $status = $request->input('status', 'all'); // published, draft, trashed, all
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
            $data['created_by'] = (string) auth()->id();
            $data['updated_by'] = (string) auth()->id();
            
            $article = KnowledgeBaseArticle::create($this->prepareArticleData($data));
            $this->syncArticleTags($article, $data['tags'] ?? []);

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
            $data['updated_by'] = (string) auth()->id();
            
            $article->update($this->prepareArticleData($data, $article));
            $this->syncArticleTags($article, $data['tags'] ?? []);

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
     * Handle file uploads for TipTap editor (images and files).
     */
    public function upload(Request $request)
    {
        $isImage = $request->hasFile('image');
        $fileKey = $isImage ? 'image' : 'file';
        
        $rules = $isImage 
            ? ['image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048']
            : ['file' => 'required|file|mimes:pdf,doc,docx,txt,zip|max:10240'];
            
        $request->validate($rules);

        try {
            $directory = $isImage ? 'knowledge-base/images' : 'knowledge-base/files';
            $method = $isImage ? 'uploadImage' : 'uploadFile';
            
            $url = $this->fileUploadService->$method(
                $request->file($fileKey),
                $directory
            );

            $response = ['success' => true, 'url' => $url];
            
            if (!$isImage) {
                $response['name'] = $request->file($fileKey)->getClientOriginalName();
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Prepare article data for create/update operations.
     */
    private function prepareArticleData(array $data, ?KnowledgeBaseArticle $article = null): array
    {
        // Remove tags from article data
        unset($data['tags']);
        
        // Handle slug generation
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug(Str::slug($data['title']), KnowledgeBaseArticle::class, $article?->id);
        } elseif ($article && $data['slug'] !== $article->slug) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], KnowledgeBaseArticle::class, $article->id);
        }
        
        // Handle publication timestamps
        if ($data['is_published']) {
            if (!$article || (!$article->is_published && empty($data['published_at']))) {
                $data['published_at'] = now();
            }
        } else {
            $data['published_at'] = null;
        }
        
        return $data;
    }
    
    /**
     * Sync article tags, creating new ones as needed.
     */
    private function syncArticleTags(KnowledgeBaseArticle $article, array $tags): void
    {
        if (empty($tags)) {
            $article->tags()->sync([]);
            return;
        }
        
        $tagIds = collect($tags)->map(function ($tagData) {
            if (is_array($tagData)) {
                if (isset($tagData['id']) && !str_starts_with($tagData['id'], 'new-')) {
                    return $tagData['id'];
                }
                if (isset($tagData['name'])) {
                    return Tag::firstOrCreate(
                        ['name' => $tagData['name']],
                        ['slug' => $this->generateUniqueSlug(Str::slug($tagData['name']), Tag::class)]
                    )->id;
                }
            }
            return $tagData;
        })->filter()->toArray();
        
        $article->tags()->sync($tagIds);
    }

    /**
     * Generate a unique slug for any model.
     */
    private function generateUniqueSlug(string $baseSlug, string $modelClass, ?string $excludeId = null): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while ($modelClass::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        
        return $slug;
    }
}
