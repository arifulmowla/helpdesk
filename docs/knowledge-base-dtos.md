# Knowledge Base DTOs

This document outlines the Data Transfer Objects (DTOs) created for the Knowledge Base feature. These DTOs are designed to work seamlessly with Inertia.js and provide type-safe data exchange between the backend and frontend.

## DTOs Overview

### TagData
Located: `app/Data/TagData.php`

Used for tag information across the knowledge base.

**Fields:**
- `id`: Tag ID
- `name`: Tag name
- `slug`: URL-friendly slug
- `articles_count`: Number of articles with this tag

**Usage:**
```php
// Single tag
$tagData = TagData::from($tag);

// Collection of tags
$tagsData = TagData::collect($tags);
```

### ArticleListItemData
Located: `app/Data/ArticleListItemData.php`

Used for article listings (index pages, search results, etc.).

**Fields:**
- `id`: Article ID
- `title`: Article title
- `slug`: URL-friendly slug
- `excerpt`: Short description (nullable)
- `published_at`: Publication date (nullable)
- `tag_names`: Array of tag names

**Usage:**
```php
// For paginated results
$articles = KnowledgeBaseArticle::with('tags')
    ->where('is_published', true)
    ->paginate(10);

return Inertia::render('KnowledgeBase/Index', [
    'articles' => ArticleListItemData::collect($articles),
]);
```

### ArticleDetailData
Located: `app/Data/ArticleDetailData.php`

Used for individual article pages with full content and navigation.

**Fields:**
- All fields from `ArticleListItemData` plus:
- `body`: Article content (array format)
- `author`: Author information (id, name, email)
- `tags`: Full tag objects (lazy loaded)
- `previous_article`: Previous article navigation (id, title, slug)
- `next_article`: Next article navigation (id, title, slug)

**Usage:**
```php
$article = KnowledgeBaseArticle::with(['tags', 'createdBy'])
    ->where('slug', $slug)
    ->firstOrFail();

return Inertia::render('KnowledgeBase/Show', [
    'article' => ArticleDetailData::from($article),
]);
```

## Controller Implementation

### Example Controller: KnowledgeBaseController

The `KnowledgeBaseController` demonstrates proper usage of these DTOs:

1. **Index Method**: Lists articles using `ArticleListItemData`
2. **Show Method**: Displays single article using `ArticleDetailData`
3. **byTag Method**: Filters articles by tag
4. **Search Method**: Searches articles with query

### Key Benefits

1. **Type Safety**: DTOs provide TypeScript types automatically
2. **Consistent Data Shape**: Ensures frontend receives predictable data structure
3. **Performance**: Only loads necessary relationships
4. **Maintainability**: Changes to data structure are centralized

## Frontend Integration

Since these DTOs use the `#[TypeScript]` attribute, they automatically generate TypeScript interfaces for the frontend. This provides:

- Intellisense in your IDE
- Compile-time type checking
- Better developer experience

## Best Practices

1. **Always use DTOs with Inertia**: Never pass raw Eloquent models
2. **Load Required Relationships**: Use `with()` to eager load necessary relationships
3. **Handle Nullable Fields**: Always account for nullable fields in your DTOs
4. **Use Lazy Loading**: For expensive relationships, use `Lazy::whenLoaded()`

## Navigation Implementation

The `ArticleDetailData` automatically calculates previous and next articles based on publication date. This provides seamless article navigation without additional queries in controllers.

Articles are ordered by `published_at` and only published articles are considered for navigation.
