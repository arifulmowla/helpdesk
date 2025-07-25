<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBaseArticle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Boot the model and add event listeners
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($article) {
            // Automatically update raw_body when body changes
            if ($article->isDirty('body')) {
                if (is_array($article->body)) {
                    $article->raw_body = $article->extractTextFromTiptapContent($article->body);
                } else {
                    $article->raw_body = strip_tags($article->body ?? '');
                }
            }
        });
    }

    // Conditionally use Scout Searchable trait if available
    public static function bootIfScoutAvailable()
    {
        if (class_exists('\Laravel\Scout\Searchable')) {
            static::addGlobalScope('searchable', function ($builder) {
                // This ensures Scout indexing works when available
            });
        }
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        if (!class_exists('\Laravel\Scout\Searchable')) {
            return [];
        }

        $array = $this->toArray();

        // Only include published articles in search index
        if (!$this->is_published) {
            return [];
        }

        // Convert JSON body to searchable text
        if (is_array($this->body)) {
            $bodyText = $this->raw_body;
        } else {
            $bodyText = $this->body;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $bodyText,
            'is_published' => $this->is_published,
        ];
    }

    /**
     * Get plain text content for embedding generation
     */
    public function getPlainTextContent(): string
    {
        // Use cached raw_body if available for better performance
        if (!empty($this->raw_body)) {
            return $this->raw_body;
        }

        // Fallback to extracting from body (for existing records without raw_body)
        if (is_array($this->body)) {
            return $this->raw_body;
        }

        return strip_tags($this->body ?? '');
    }

    /**
     * Get the excerpt for the article.
     * If no excerpt is set, generate one from the body content.
     */
    public function getExcerptAttribute($value): string
    {
        // If excerpt is manually set, return it
        if (!empty($value)) {
            return $value;
        }

        // Generate excerpt from body content
        if (is_array($this->body)) {
            $text = $this->raw_body;
        } else {
            $text = strip_tags($this->body ?? '');
        }

        // Truncate to 200 characters and add ellipsis
        if (strlen($text) > 200) {
            return substr($text, 0, 200) . '...';
        }

        return $text;
    }

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'raw_body',
        'is_published',
        'published_at',
        'view_count',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'body' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tag', 'article_id', 'tag_id');
    }
}

