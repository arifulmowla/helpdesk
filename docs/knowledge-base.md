# Knowledge Base Module

The Knowledge Base module provides a comprehensive article management system for creating, organizing, and searching help documentation. This module includes both public-facing knowledge base and admin management features.

## Architecture Overview

### Core Components

1. **Models**
   - `KnowledgeBaseArticle` - Main article model with TipTap JSON content
   - `Tag` - Categorization and labeling system
   - `User` - Author relationships (created_by, updated_by)

2. **Database Schema**
   - `knowledge_base_articles` - Article content and metadata
   - `tags` - Tag definitions with unique slugs
   - `article_tag` - Many-to-many pivot table
   - `knowledge_base_articles_fts` - Full-text search virtual table (SQLite FTS5)

3. **Data Transfer Objects (DTOs)**
   - `ArticleListItemData` - For listings and search results
   - `ArticleDetailData` - For full article display with navigation
   - `TagData` - Tag information with article counts

4. **Controllers**
   - `KnowledgeBaseController` - Public KB functionality
   - `Admin\KnowledgeBaseController` - Admin article management
   - `Admin\TagController` - Admin tag management

## Features

### Public Knowledge Base
- Article browsing with pagination
- Full-text search functionality
- Tag-based filtering
- Article navigation (previous/next)
- View count tracking
- SEO-friendly URLs with slugs

### Admin Management
- WYSIWYG article editor (TipTap)
- Tag management with auto-slug generation
- Draft/publish workflow
- Bulk operations
- Article analytics (view counts)
- User permission controls

### Search Capabilities
- **SQLite FTS5**: Built-in full-text search using virtual tables
- **Laravel Scout**: Optional advanced search with external drivers
- Tag-based filtering
- Advanced search operators

## How to Create and Edit Articles

### Creating New Articles

1. **Via Admin Interface**:
   ```
   Navigate to: /admin/knowledge-base/create
   ```
   - Fill in title (slug auto-generates)
   - Add excerpt for listings
   - Write content using TipTap editor
   - Select/create tags
   - Choose publish status

2. **Via Seeder/Factory**:
   ```php
   KnowledgeBaseArticle::factory()
       ->published()
       ->authoredBy($user)
       ->create([
           'title' => 'Your Article Title',
           'body' => $tiptapContent,
       ]);
   ```

### Content Format (TipTap JSON)

Articles use TipTap JSON format for rich content:

```php
$content = [
    'type' => 'doc',
    'content' => [
        [
            'type' => 'paragraph',
            'content' => [
                [
                    'type' => 'text',
                    'text' => 'Your paragraph content here.'
                ]
            ]
        ]
    ]
];
```

### Managing Tags

1. **Creating Tags**:
   ```php
   Tag::create([
       'name' => 'Getting Started',
       'slug' => 'getting-started'
   ]);
   ```

2. **Attaching to Articles**:
   ```php
   $article->tags()->attach($tagIds);
   ```

### Publishing Workflow

- **Draft**: `is_published = false`, `published_at = null`
- **Published**: `is_published = true`, `published_at = timestamp`
- Only published articles appear in public search results

## Search Configuration

### Option 1: Built-in FULLTEXT Search (SQLite FTS5)

**Current Implementation** - No additional setup required.

**Features**:
- Virtual FTS5 table with automatic sync triggers
- Searches title and extracted text from TipTap JSON
- Fast for small to medium datasets
- Zero external dependencies

**Usage**:
```php
// In KnowledgeBaseController
$articles = KnowledgeBaseArticle::whereRaw(
    "id IN (SELECT rowid FROM knowledge_base_articles_fts WHERE knowledge_base_articles_fts MATCH ?)",
    [$query]
)->get();
```

### Option 2: Laravel Scout (Advanced)

**Setup Required**:
```bash
composer require laravel/scout
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

**Supported Drivers**:
- Algolia (cloud-based, instant search)
- Meilisearch (self-hosted, typo-tolerant)
- Elasticsearch (enterprise-grade)
- Database (Laravel's built-in driver)

**Configuration**:
1. Uncomment Scout trait in models:
   ```php
   // In KnowledgeBaseArticle.php
   use Laravel\Scout\Searchable;
   ```

2. Configure driver in `.env`:
   ```env
   SCOUT_DRIVER=meilisearch
   MEILISEARCH_HOST=http://localhost:7700
   ```

3. Index existing articles:
   ```bash
   php artisan scout:import "App\Models\KnowledgeBaseArticle"
   ```

**Benefits**:
- Typo tolerance and fuzzy matching
- Advanced filtering and faceting
- Instant search with autocomplete
- Analytics and search insights
- Scalable to millions of documents

### Choosing Between Options

| Feature | SQLite FTS5 | Laravel Scout |
|---------|-------------|---------------|
| Setup Complexity | None | Moderate |
| External Dependencies | None | Search service required |
| Performance (small datasets) | Excellent | Good |
| Performance (large datasets) | Good | Excellent |
| Advanced Features | Basic | Rich |
| Typo Tolerance | No | Yes (driver dependent) |
| Real-time Updates | Yes | Yes |
| Cost | Free | Variable |

**Recommendation**: 
- Start with SQLite FTS5 for simplicity
- Migrate to Scout + Meilisearch when you need advanced features or handle >10k articles

## API Endpoints

### Public Endpoints
- `GET /knowledge-base` - List articles
- `GET /knowledge-base/{slug}` - View article
- `GET /knowledge-base/tag/{tag}` - Articles by tag
- `GET /knowledge-base/search` - Search articles

### Admin Endpoints
- `GET /admin/knowledge-base` - Admin article list
- `POST /admin/knowledge-base` - Create article
- `PUT /admin/knowledge-base/{id}` - Update article
- `DELETE /admin/knowledge-base/{id}` - Delete article
- `GET /admin/tags` - Manage tags
- `POST /admin/tags` - Create tag (supports AJAX)

## Database Indexes

Optimized indexes for performance:

```sql
-- Articles table
INDEX(is_published, published_at)  -- For published article queries
INDEX(slug)                        -- For URL routing
INDEX(created_by, updated_by)      -- For author queries
INDEX(view_count)                  -- For popularity sorting

-- Tags table  
INDEX(slug)                        -- For tag routing

-- FTS virtual table
-- Automatically maintained by SQLite triggers
```

## Permissions

The Knowledge Base uses Laravel's authorization system:

```php
// In AuthServiceProvider
Gate::define('manage-knowledge-base', function ($user) {
    return $user->hasRole('admin') || $user->hasRole('editor');
});
```

Required permissions:
- `manage-knowledge-base` - Create, edit, delete articles and tags
- Public viewing requires no authentication

## Performance Considerations

### Caching Strategy
- Cache popular articles with view count-based TTL
- Cache tag counts for efficient filtering
- Use Redis for session-based search history

### Optimization Tips
1. **Pagination**: Always paginate article listings
2. **Eager Loading**: Load tags and authors with articles
3. **Image Optimization**: Compress uploaded images
4. **CDN**: Serve static assets from CDN
5. **Database**: Monitor slow queries and add indexes as needed

## Maintenance Tasks

### Regular Maintenance
```bash
# Rebuild FTS index if needed
php artisan db:rebuild-fts

# Clear article cache
php artisan cache:clear --tags=knowledge-base

# Update Scout index
php artisan scout:flush "App\Models\KnowledgeBaseArticle"
php artisan scout:import "App\Models\KnowledgeBaseArticle"
```

### Monitoring
- Track search query performance
- Monitor article view patterns
- Check FTS index size and update frequency
- Monitor tag usage statistics

## Development Workflow

1. **Local Development**:
   ```bash
   php artisan migrate
   php artisan db:seed --class=TagSeeder
   php artisan db:seed --class=KnowledgeBaseSeeder
   ```

2. **Testing**:
   ```bash
   php artisan test --filter=KnowledgeBase
   ```

3. **Production Deployment**:
   ```bash
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Troubleshooting

### Common Issues

1. **FTS Search Not Working**:
   - Check if SQLite FTS5 extension is enabled
   - Verify triggers are created properly
   - Rebuild FTS table if corrupted

2. **Scout Search Issues**:
   - Verify search service is running
   - Check API keys and configuration
   - Rebuild search index

3. **Slug Conflicts**:
   - Enable unique slug generation in models
   - Check for duplicate slugs in database

4. **TipTap Content Issues**:
   - Validate JSON structure before saving
   - Handle legacy content migration
   - Sanitize HTML input properly

### Debug Commands

```bash
# Check FTS table status
sqlite3 database.sqlite "SELECT * FROM knowledge_base_articles_fts LIMIT 5;"

# Verify article-tag relationships
php artisan tinker
>>> App\Models\KnowledgeBaseArticle::with('tags')->first()

# Test search functionality
>>> App\Models\KnowledgeBaseArticle::search('test query')->get()
```
