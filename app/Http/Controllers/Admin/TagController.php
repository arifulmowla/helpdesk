<?php

namespace App\Http\Controllers\Admin;

use App\Data\TagData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TagController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-knowledge-base');
    }

    /**
     * Display a listing of tags for admin management.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'name');
        $sortDir = $request->input('sort_dir', 'asc');
        $perPage = $request->input('per_page', 15);

        $query = Tag::withCount('knowledgeBaseArticles');

        // Apply search filter
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply sorting
        $allowedSorts = ['name', 'created_at', 'knowledge_base_articles_count'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $tags = $query->paginate($perPage);

        $currentFilters = [
            'search' => $search,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'per_page' => $perPage,
        ];

        return Inertia::render('Admin/Tags/Index', [
            'tags' => TagData::collect($tags),
            'currentFilters' => $currentFilters,
        ]);
    }

    /**
     * Store a newly created tag (API endpoint for inline creation).
     */
    public function store(StoreTagRequest $request)
    {
        $data = $request->validated();
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $baseSlug = Str::slug($data['name']);
            $data['slug'] = $this->generateUniqueSlug($baseSlug);
        }

        $tag = Tag::create($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'tag' => TagData::from($tag),
            ]);
        }

        return back()->with('success', 'Tag created successfully.');
    }

    /**
     * Update the specified tag.
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $data = $request->validated();
        
        // Generate slug if name changed
        if (isset($data['name']) && $data['name'] !== $tag->name) {
            $data['slug'] = $this->generateUniqueSlug(Str::slug($data['name']), $tag->id);
        }

        $tag->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'tag' => TagData::from($tag),
            ]);
        }

        return back()->with('success', 'Tag updated successfully.');
    }

    /**
     * Remove the specified tag from storage.
     */
    public function destroy(Tag $tag)
    {
        // Check if tag has articles
        $articleCount = $tag->knowledgeBaseArticles()->count();
        
        if ($articleCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete tag '{$tag->name}' because it is used by {$articleCount} article(s). Please remove the tag from all articles first."
            ]);
        }

        $tag->delete();

        return back()->with('success', 'Tag deleted successfully.');
    }

    /**
     * Get all tags for dropdowns/select components.
     */
    public function list()
    {
        $tags = Tag::orderBy('name')->get();
        
        return response()->json([
            'tags' => TagData::collect($tags),
        ]);
    }

    /**
     * Generate a unique slug for the tag.
     */
    private function generateUniqueSlug(string $baseSlug, ?int $excludeId = null): string
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
}
