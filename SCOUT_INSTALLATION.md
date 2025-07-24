# Laravel Scout Installation (Optional)

This search implementation provides both Laravel Scout and native database search capabilities. Laravel Scout is optional but provides enhanced search features.

## Option A: Install Laravel Scout

### 1. Install Laravel Scout package

```bash
composer require laravel/scout
```

### 2. Publish Scout configuration

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

### 3. Choose a search driver

#### Database Driver (Recommended for small to medium applications)

Add to your `.env` file:
```
SCOUT_DRIVER=database
```

Then run the database indexing:
```bash
php artisan scout:index "App\Models\KnowledgeBaseArticle"
```

#### Algolia Driver (For larger applications)

1. Sign up for Algolia account
2. Add to your `.env`:
```
SCOUT_DRIVER=algolia
ALGOLIA_APP_ID=your_app_id
ALGOLIA_SECRET=your_secret_key
```

3. Install Algolia SDK:
```bash
composer require algolia/algoliasearch-client-php
```

4. Index existing articles:
```bash
php artisan scout:import "App\Models\KnowledgeBaseArticle"
```

#### Meilisearch Driver (Self-hosted option)

1. Install Meilisearch server
2. Add to your `.env`:
```
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=your_master_key
```

3. Install Meilisearch SDK:
```bash
composer require meilisearch/meilisearch-php http-interop/http-factory-guzzle
```

4. Index existing articles:
```bash
php artisan scout:import "App\Models\KnowledgeBaseArticle"
```

## Option B: Use Native Database Search (Default)

If you don't install Laravel Scout, the system automatically falls back to native database search:

- **SQLite**: Uses FTS5 (Full-Text Search) for better search relevance
- **MySQL**: Uses FULLTEXT indexes for optimized search performance  
- **Other databases**: Falls back to LIKE queries

## Features

### With Laravel Scout:
- Advanced search algorithms
- Typo tolerance (with Algolia/Meilisearch)
- Search analytics
- Instant search capabilities
- Better relevance scoring

### With Native Database Search:
- No external dependencies
- Works out of the box
- Good performance for small to medium datasets
- Search highlighting
- Tag filtering
- Pagination

## Search Highlighting

Both approaches support search term highlighting in the Vue.js frontend components. Matching terms are wrapped with `<mark>` tags and styled with yellow highlighting.

## Configuration

The search service automatically detects which approach to use based on whether Laravel Scout is installed and configured. No additional configuration is needed to switch between approaches.
