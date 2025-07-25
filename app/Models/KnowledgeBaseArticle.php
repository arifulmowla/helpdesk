<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KnowledgeBaseArticle extends Model
{
    use HasFactory, HasUlids, SoftDeletes;
    
    // Note: If Laravel Scout is installed, you should add:
    // use \Laravel\Scout\Searchable;
    // to enable full-text search capabilities



    /**
     * Get the indexable data array for the model (used by Scout if available).
     */
    public function toSearchableArray(): array
    {
        return $this->is_published ? [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'body' => $this->getPlainTextContent(),
            'is_published' => $this->is_published,
        ] : [];
    }

    /**
     * Get plain text content for embedding generation
     */
    public function getPlainTextContent(): string
    {
        return $this->raw_body ?? '';
    }

    /**
     * Get the excerpt for the article.
     */
    public function getExcerptAttribute($value): string
    {
        if (!empty($value)) {
            return $value;
        }

        $text = $this->getPlainTextContent();
        return strlen($text) > 200 ? substr($text, 0, 200) . '...' : $text;
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

