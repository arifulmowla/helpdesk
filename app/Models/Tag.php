<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    
    // To enable search functionality with Laravel Scout:
    // 1. Install Laravel Scout: composer require laravel/scout
    // 2. Publish Scout config: php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
    // 3. Uncomment the line below:
    // use \Laravel\Scout\Searchable;
    //
    // Optional: Define searchable fields by adding this method:
    // public function toSearchableArray()
    // {
    //     return [
    //         'name' => $this->name,
    //     ];
    // }

    protected $fillable = ['name', 'slug'];

    public function knowledgeBaseArticles()
    {
        return $this->belongsToMany(KnowledgeBaseArticle::class, 'article_tag', 'tag_id', 'article_id');
    }
}
