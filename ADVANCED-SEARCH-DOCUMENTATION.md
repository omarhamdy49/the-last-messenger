# Advanced Search & Filter System Documentation

## Overview

The Advanced Search & Filter System provides a comprehensive, production-ready search solution across all content modules in The Last Messenger platform. It features dynamic filtering, search history tracking, saved filters, and advanced query building capabilities.

## Architecture

### Core Components

1. **SearchService** (`app/Domain/Shared/Services/SearchService.php`)
   - Unified search interface across all modules
   - Search history tracking
   - Search preferences management
   - Saved filter management
   - Caching support

2. **FilterBuilder** (`app/Domain/Shared/Builders/FilterBuilder.php`)
   - Dynamic query builder
   - 16 filter operators
   - Type-safe filter application
   - JSON field support
   - Sorting and pagination

3. **Repositories**
   - `SearchHistoryRepository` - Manages search history
   - `SavedFilterRepository` - Manages saved filters

4. **Models**
   - `SearchHistory` - Search logs with metadata
   - `SearchPreference` - User preferences per module
   - `SavedFilter` - Saved filter combinations

5. **Enums**
   - `SearchModule` - Available search modules
   - `FilterOperator` - Filter comparison operators
   - `FilterType` - Filter data types
   - `SortDirection` - Sort directions

## API Endpoints

### Search Endpoints

#### 1. Global Search
```http
POST /api/search
Content-Type: application/json

{
  "query": "Prophet Muhammad",
  "modules": ["article", "hadith", "seerah"],
  "filters": {
    "status": "published",
    "published_at": {
      "from": "2024-01-01",
      "to": "2024-12-31"
    }
  },
  "sort": "relevance",
  "order": "desc",
  "per_page": 15
}
```

**Response:**
```json
{
  "success": true,
  "message": "Search completed successfully",
  "data": {
    "query": "Prophet Muhammad",
    "results": {
      "article": {
        "total": 45,
        "data": [...],
        "per_page": 15,
        "current_page": 1
      },
      "hadith": {
        "total": 123,
        "data": [...],
        "per_page": 15,
        "current_page": 1
      }
    },
    "total_results": 168,
    "execution_time_ms": 45.23,
    "filters_applied": true
  }
}
```

#### 2. Module-Specific Search
```http
POST /api/search/{module}
Content-Type: application/json

{
  "query": "Quraysh",
  "filters": {
    "status": "published",
    "category_id": [1, 2, 3]
  },
  "sort": "date",
  "order": "desc",
  "per_page": 20
}
```

#### 3. Search Suggestions
```http
GET /api/search/suggestions?q=prophet&module=article
```

**Response:**
```json
{
  "success": true,
  "data": {
    "suggestions": [
      "prophet muhammad",
      "prophet biography",
      "prophet companions"
    ],
    "count": 3
  }
}
```

#### 4. Trending Searches
```http
GET /api/search/trending?module=hadith&days=7
```

#### 5. Search History (Auth Required)
```http
GET /api/search/history?module=article
Authorization: Bearer {token}
```

#### 6. Clear Search History (Auth Required)
```http
DELETE /api/search/history?module=article
Authorization: Bearer {token}
```

#### 7. Available Modules
```http
GET /api/search/modules
```

### Filter Endpoints (All Require Authentication)

#### 1. List Saved Filters
```http
GET /api/filters?module=article
Authorization: Bearer {token}
```

#### 2. Save New Filter
```http
POST /api/filters
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Published Articles 2024",
  "description": "All published articles from 2024",
  "module": "article",
  "filters": {
    "status": "published",
    "published_at": {
      "from": "2024-01-01",
      "to": "2024-12-31"
    }
  },
  "sort_options": {
    "sort": "date",
    "order": "desc"
  },
  "is_public": false,
  "is_default": false
}
```

#### 3. Update Filter
```http
PUT /api/filters/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Updated Filter Name",
  "filters": {...}
}
```

#### 4. Delete Filter
```http
DELETE /api/filters/{id}
Authorization: Bearer {token}
```

#### 5. Apply Saved Filter
```http
POST /api/filters/{id}/apply
Authorization: Bearer {token}
Content-Type: application/json

{
  "query": "optional search query",
  "per_page": 15
}
```

#### 6. Set as Default
```http
POST /api/filters/{id}/set-default
Authorization: Bearer {token}
```

#### 7. Public Filters
```http
GET /api/filters/public?module=article
```

#### 8. Get Search Preferences
```http
GET /api/filters/preferences/{module}
Authorization: Bearer {token}
```

#### 9. Save Search Preferences
```http
POST /api/filters/preferences/{module}
Authorization: Bearer {token}
Content-Type: application/json

{
  "default_filters": {
    "status": "published"
  },
  "default_sort": "date",
  "default_order": "desc",
  "default_per_page": 20,
  "show_facets": true,
  "highlight_results": true,
  "language": "en"
}
```

## Filter Operators

### Available Operators

1. **Equals** (`=`)
2. **Not Equals** (`!=`)
3. **Greater Than** (`>`)
4. **Greater Than or Equal** (`>=`)
5. **Less Than** (`<`)
6. **Less Than or Equal** (`<=`)
7. **Like** (`like`)
8. **Not Like** (`not_like`)
9. **In** (`in`)
10. **Not In** (`not_in`)
11. **Between** (`between`)
12. **Is Null** (`null`)
13. **Is Not Null** (`not_null`)
14. **Contains** (`contains`)
15. **Starts With** (`starts_with`)
16. **Ends With** (`ends_with`)
17. **Full Text** (`full_text`)

### Filter Examples

#### Text Filter
```json
{
  "title": "Prophet Muhammad"
}
```

#### Number Range
```json
{
  "views_count": {
    "min": 100,
    "max": 1000
  }
}
```

#### Date Range
```json
{
  "published_at": {
    "from": "2024-01-01",
    "to": "2024-12-31"
  }
}
```

#### Multi-Select
```json
{
  "category_id": [1, 2, 3, 4]
}
```

#### Complex Filter
```json
{
  "status": "published",
  "category_id": [1, 2],
  "published_at": {
    "from": "2024-01-01"
  },
  "views_count": {
    "min": 100
  }
}
```

## Search Modules

### Available Modules

1. **article** - Blog articles and Seerah content
   - Searchable: title, content, excerpt
   - Filters: status, category_id, author_id, published_at

2. **hadith** - Hadith database
   - Searchable: hadith_text, arabic_text, translation
   - Filters: book_id, chapter_id, authenticity_grade, narrator_id

3. **media** - Video programs and episodes
   - Searchable: title, description
   - Filters: category_id, type, duration, release_date

4. **seerah** - Timeline events
   - Searchable: title, description, location
   - Filters: event_type, importance_level, hijri_date, gregorian_date

5. **sahaba** - Companions profiles
   - Searchable: name, kunya, description, tribe
   - Filters: status, tribe, gender

6. **quran** - Quranic verses
   - Searchable: arabic_text, translation
   - Filters: surah_id, juz_number, revelation_type

7. **genealogy** - Family tree
   - Searchable: name, description
   - Filters: gender, birth_date

## Usage Examples

### PHP Service Usage

```php
use App\Domain\Shared\Services\SearchService;

class ExampleController extends Controller
{
    public function __construct(protected SearchService $searchService) {}

    public function search(Request $request)
    {
        // Global search
        $results = $this->searchService->search(
            query: 'Prophet Muhammad',
            filters: [
                'status' => 'published',
                'published_at' => [
                    'from' => '2024-01-01',
                    'to' => '2024-12-31',
                ],
            ],
            options: [
                'modules' => ['article', 'hadith'],
                'sort' => 'relevance',
                'order' => 'desc',
                'per_page' => 15,
                'user' => $request->user(),
            ]
        );

        // Module-specific search
        $articles = $this->searchService->searchWithin(
            moduleType: 'article',
            query: 'Companions',
            filters: ['status' => 'published'],
            options: [
                'sort' => 'date',
                'per_page' => 20,
                'user' => $request->user(),
            ]
        );

        // Get suggestions
        $suggestions = $this->searchService->getSuggestions(
            query: 'prop',
            module: 'article'
        );

        // Save filter
        $filter = $this->searchService->saveFilter(
            user: $request->user(),
            name: 'My Filter',
            module: 'article',
            filters: ['status' => 'published'],
            options: [
                'is_public' => false,
                'is_default' => true,
            ]
        );
    }
}
```

### FilterBuilder Usage

```php
use App\Domain\Shared\Builders\FilterBuilder;
use App\Domain\Blog\Models\Article;

$query = Article::query();
$builder = new FilterBuilder($query);

$builder
    ->addFilter('status', FilterOperator::EQUALS, 'published')
    ->addDateRange('published_at', '2024-01-01', '2024-12-31')
    ->addMultiSelect('category_id', [1, 2, 3])
    ->addTextSearch('title', 'Prophet', exact: false)
    ->addSort('published_at', SortDirection::DESC)
    ->setLimit(20);

$results = $builder->build()->get();
```

## Performance Considerations

### Caching Strategy

1. **Search Results**: 5-minute TTL
2. **Suggestions**: 1-hour TTL
3. **Trending Searches**: 1-day TTL
4. **Filter Options**: 30-minute TTL

### Query Optimization

1. **Indexes**: All searchable and filterable fields are indexed
2. **Eager Loading**: Default relationships are eager loaded
3. **N+1 Prevention**: All queries use eager loading
4. **Full-Text Search**: Uses MySQL MATCH AGAINST for indexed columns

### Best Practices

1. **Limit per_page**: Maximum 100 items per page
2. **Use Filters**: Narrow results before full-text search
3. **Cache Results**: Results are automatically cached
4. **Batch Operations**: Use bulk operations for multiple searches

## Database Schema

### search_history
- Tracks all searches with metadata
- Indexed on user_id, module, searched_at
- Stores filters, result count, execution time

### search_preferences
- User preferences per module
- Unique constraint on (user_id, module)
- Stores default filters, sort, pagination

### saved_filters
- Saved filter combinations
- Supports public/private visibility
- Tracks usage count and last used time
- Can be set as default per module

## Security

1. **Authentication**: Most endpoints are public, history/filters require auth
2. **Authorization**: Users can only access their own filters/history
3. **Input Validation**: All inputs validated via Form Requests
4. **SQL Injection**: Protected via Eloquent query builder
5. **Rate Limiting**: Standard API rate limiting applies

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error message here",
  "errors": {
    "field": ["Validation error"]
  }
}
```

## Future Enhancements

1. **Elasticsearch Integration**: For better full-text search performance
2. **Faceted Search**: Count results by filter categories
3. **Search Analytics**: Track popular searches, failed searches
4. **Auto-Complete**: Real-time suggestions as user types
5. **Spell Correction**: Suggest corrections for typos
6. **Synonym Support**: Search variations and synonyms
7. **Boolean Operators**: AND, OR, NOT in queries
8. **Field-Specific Search**: title:query, author:name syntax
