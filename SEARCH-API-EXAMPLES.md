# Search & Filter API Examples

Complete collection of API examples for testing the Advanced Search & Filter system.

## Authentication

First, obtain an auth token:

```bash
# Register a user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# Save the token from response for authenticated requests
TOKEN="your_token_here"
```

## Search Examples

### 1. Global Search (Simple)

```bash
curl -X POST http://localhost:8000/api/search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Prophet Muhammad"
  }'
```

### 2. Global Search with Module Selection

```bash
curl -X POST http://localhost:8000/api/search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Prophet Muhammad",
    "modules": ["article", "hadith", "seerah"],
    "per_page": 20
  }'
```

### 3. Global Search with Filters

```bash
curl -X POST http://localhost:8000/api/search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Companions",
    "modules": ["article", "sahaba"],
    "filters": {
      "status": "published"
    },
    "sort": "date",
    "order": "desc",
    "per_page": 15
  }'
```

### 4. Module-Specific Search: Articles

```bash
curl -X POST http://localhost:8000/api/search/article \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Mecca",
    "filters": {
      "status": "published",
      "category_id": [1, 2]
    },
    "sort": "date",
    "order": "desc",
    "per_page": 20
  }'
```

### 5. Module-Specific Search: Hadith

```bash
curl -X POST http://localhost:8000/api/search/hadith \
  -H "Content-Type: application/json" \
  -d '{
    "query": "prayer",
    "filters": {
      "authenticity_grade": "sahih",
      "book_id": [1, 2]
    },
    "sort": "relevance",
    "per_page": 25
  }'
```

### 6. Search with Date Range Filter

```bash
curl -X POST http://localhost:8000/api/search/article \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Ramadan",
    "filters": {
      "status": "published",
      "published_at": {
        "from": "2024-01-01",
        "to": "2024-12-31"
      }
    },
    "sort": "date",
    "order": "desc"
  }'
```

### 7. Search Seerah Events by Type

```bash
curl -X POST http://localhost:8000/api/search/seerah \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Battle",
    "filters": {
      "event_type": "battle",
      "importance_level": "high"
    }
  }'
```

### 8. Search Quran by Surah

```bash
curl -X POST http://localhost:8000/api/search/quran \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mercy",
    "filters": {
      "surah_id": [1, 2, 3],
      "revelation_type": "meccan"
    }
  }'
```

### 9. Get Search Suggestions

```bash
# Simple suggestion
curl -X GET "http://localhost:8000/api/search/suggestions?q=prop"

# Module-specific suggestion
curl -X GET "http://localhost:8000/api/search/suggestions?q=hadith&module=hadith"

# Longer query
curl -X GET "http://localhost:8000/api/search/suggestions?q=prophet%20muhammad"
```

### 10. Get Trending Searches

```bash
# All modules
curl -X GET "http://localhost:8000/api/search/trending"

# Specific module
curl -X GET "http://localhost:8000/api/search/trending?module=article"

# Custom time period (last 30 days)
curl -X GET "http://localhost:8000/api/search/trending?days=30"

# Module + time period
curl -X GET "http://localhost:8000/api/search/trending?module=hadith&days=7"
```

### 11. Get Available Modules

```bash
curl -X GET "http://localhost:8000/api/search/modules"
```

### 12. Get Search History (Authenticated)

```bash
# All history
curl -X GET "http://localhost:8000/api/search/history" \
  -H "Authorization: Bearer $TOKEN"

# Filter by module
curl -X GET "http://localhost:8000/api/search/history?module=article" \
  -H "Authorization: Bearer $TOKEN"
```

### 13. Clear Search History (Authenticated)

```bash
# Clear all history
curl -X DELETE "http://localhost:8000/api/search/history" \
  -H "Authorization: Bearer $TOKEN"

# Clear specific module
curl -X DELETE "http://localhost:8000/api/search/history?module=article" \
  -H "Authorization: Bearer $TOKEN"
```

## Filter Management Examples

### 1. List Saved Filters (Authenticated)

```bash
# All filters
curl -X GET "http://localhost:8000/api/filters" \
  -H "Authorization: Bearer $TOKEN"

# Module-specific
curl -X GET "http://localhost:8000/api/filters?module=article" \
  -H "Authorization: Bearer $TOKEN"
```

### 2. Save a Filter (Authenticated)

```bash
curl -X POST http://localhost:8000/api/filters \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
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
  }'
```

### 3. Save Public Filter

```bash
curl -X POST http://localhost:8000/api/filters \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "Sahih Hadith Only",
    "description": "Filter for only authentic hadith",
    "module": "hadith",
    "filters": {
      "authenticity_grade": "sahih"
    },
    "is_public": true,
    "is_default": false
  }'
```

### 4. Update a Filter (Authenticated)

```bash
curl -X PUT http://localhost:8000/api/filters/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "Updated Filter Name",
    "description": "Updated description",
    "module": "article",
    "filters": {
      "status": "published",
      "category_id": [1, 2, 3]
    }
  }'
```

### 5. Delete a Filter (Authenticated)

```bash
curl -X DELETE http://localhost:8000/api/filters/1 \
  -H "Authorization: Bearer $TOKEN"
```

### 6. Apply a Saved Filter (Authenticated)

```bash
# Apply with default settings
curl -X POST http://localhost:8000/api/filters/1/apply \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{}'

# Apply with custom query
curl -X POST http://localhost:8000/api/filters/1/apply \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "query": "Prophet Muhammad",
    "per_page": 25
  }'
```

### 7. Set Filter as Default (Authenticated)

```bash
curl -X POST http://localhost:8000/api/filters/1/set-default \
  -H "Authorization: Bearer $TOKEN"
```

### 8. Get Public Filters

```bash
# All public filters
curl -X GET "http://localhost:8000/api/filters/public"

# Module-specific public filters
curl -X GET "http://localhost:8000/api/filters/public?module=hadith"
```

### 9. Get Search Preferences (Authenticated)

```bash
curl -X GET "http://localhost:8000/api/filters/preferences/article" \
  -H "Authorization: Bearer $TOKEN"
```

### 10. Save Search Preferences (Authenticated)

```bash
curl -X POST http://localhost:8000/api/filters/preferences/article \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "default_filters": {
      "status": "published"
    },
    "default_sort": "date",
    "default_order": "desc",
    "default_per_page": 20,
    "show_facets": true,
    "highlight_results": true,
    "language": "en"
  }'
```

## Advanced Filter Examples

### Complex Multi-Filter Search

```bash
curl -X POST http://localhost:8000/api/search/article \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Islamic history",
    "filters": {
      "status": "published",
      "category_id": [1, 2, 3, 4],
      "author_id": 5,
      "published_at": {
        "from": "2024-01-01",
        "to": "2024-12-31"
      }
    },
    "sort": "popular",
    "order": "desc",
    "per_page": 30
  }'
```

### Sahaba Search with Multiple Filters

```bash
curl -X POST http://localhost:8000/api/search/sahaba \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Abu Bakr",
    "filters": {
      "status": "companion",
      "tribe": "Quraysh",
      "gender": "male"
    },
    "sort": "relevance"
  }'
```

### Media Search with Duration Range

```bash
curl -X POST http://localhost:8000/api/search/media \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Seerah series",
    "filters": {
      "category_id": [1, 2],
      "type": "series",
      "duration": {
        "min": 30,
        "max": 60
      }
    }
  }'
```

## Testing Workflow

### 1. Initial Setup

```bash
# Register and login
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "password123"}' \
  | jq -r '.data.token')

echo "Token: $TOKEN"
```

### 2. Test Search Flow

```bash
# 1. Get available modules
curl -s http://localhost:8000/api/search/modules | jq .

# 2. Simple search
curl -s -X POST http://localhost:8000/api/search \
  -H "Content-Type: application/json" \
  -d '{"query": "Prophet"}' | jq .

# 3. Get suggestions
curl -s "http://localhost:8000/api/search/suggestions?q=prophet" | jq .

# 4. Get trending
curl -s "http://localhost:8000/api/search/trending" | jq .
```

### 3. Test Filter Flow

```bash
# 1. Save a filter
FILTER_ID=$(curl -s -X POST http://localhost:8000/api/filters \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "name": "Test Filter",
    "module": "article",
    "filters": {"status": "published"}
  }' | jq -r '.data.id')

# 2. List filters
curl -s -X GET http://localhost:8000/api/filters \
  -H "Authorization: Bearer $TOKEN" | jq .

# 3. Apply filter
curl -s -X POST http://localhost:8000/api/filters/$FILTER_ID/apply \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"query": "test"}' | jq .

# 4. Delete filter
curl -s -X DELETE http://localhost:8000/api/filters/$FILTER_ID \
  -H "Authorization: Bearer $TOKEN" | jq .
```

## Expected Responses

### Successful Search Response

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
      }
    },
    "total_results": 45,
    "execution_time_ms": 45.23,
    "filters_applied": true
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "query": ["The query field is required."]
  }
}
```

## Performance Testing

### Load Test with Apache Bench

```bash
# Simple search load test (100 requests, 10 concurrent)
ab -n 100 -c 10 -p search.json -T application/json \
  http://localhost:8000/api/search

# Where search.json contains:
# {"query": "test"}
```

### Benchmark Different Scenarios

```bash
# 1. Simple search
time curl -X POST http://localhost:8000/api/search \
  -H "Content-Type: application/json" \
  -d '{"query": "test"}'

# 2. Complex search
time curl -X POST http://localhost:8000/api/search/article \
  -H "Content-Type: application/json" \
  -d '{
    "query": "test",
    "filters": {
      "status": "published",
      "category_id": [1,2,3],
      "published_at": {"from": "2024-01-01"}
    }
  }'

# 3. Cached search (run twice)
curl -X POST http://localhost:8000/api/search \
  -H "Content-Type: application/json" \
  -d '{"query": "test"}' > /dev/null

time curl -X POST http://localhost:8000/api/search \
  -H "Content-Type: application/json" \
  -d '{"query": "test"}'
```

## Troubleshooting

### Check if routes are registered

```bash
php artisan route:list --name=search
php artisan route:list --name=filter
```

### Test service instantiation

```bash
php artisan tinker --execute="
  \$service = app(\App\Domain\Shared\Services\SearchService::class);
  echo 'Service loaded successfully';
"
```

### Check database tables

```bash
php artisan tinker --execute="
  echo 'Search History: ' . \App\Domain\Shared\Models\SearchHistory::count();
  echo PHP_EOL;
  echo 'Saved Filters: ' . \App\Domain\Shared\Models\SavedFilter::count();
"
```
