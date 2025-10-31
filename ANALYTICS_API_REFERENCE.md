# Analytics API Reference

## Base URL
```
/su/portal/v1/admin/analytics
```

## Authentication
All endpoints require authentication via session (web middleware) and admin role.

## Rate Limiting
60 requests per minute per user.

---

## Endpoints

### 1. Analytics Dashboard (View)

```http
GET /su/portal/v1/admin/analytics
```

**Description**: Returns the analytics dashboard Blade view.

**Response**: HTML view

**Example**:
```bash
curl -X GET https://example.com/su/portal/v1/admin/analytics \
  -H "Cookie: laravel_session=..."
```

---

### 2. Get Comprehensive Statistics

```http
GET /su/portal/v1/admin/analytics/stats
```

**Description**: Retrieve comprehensive statistics for all modules with optional date filtering.

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | string (Y-m-d) | No | Start date for filtering |
| end_date | string (Y-m-d) | No | End date (must be >= start_date) |

**Response**:
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "content": {
      "total_content": 1234,
      "by_module": {
        "articles": {
          "total": 450,
          "published": 350,
          "draft": 50,
          "archived": 50
        },
        "hadith": {
          "total": 5000,
          "sahih": 4200,
          "hasan": 600,
          "daif": 200
        },
        "media": {
          "programs": 25,
          "episodes": 150,
          "total_views": 50000
        }
        // ... other modules
      }
    },
    "engagement": {
      "total_views": 125000,
      "total_favorites": 3500,
      "average_views_per_article": 277.78,
      "by_module": {
        // ... module-specific engagement
      },
      "engagement_rate": 101.38
    },
    "users": {
      "total": 500,
      "verified": 450,
      "unverified": 50,
      "active": 120,
      "by_role": {
        "admin": 5,
        "editor": 10,
        "user": 485
      },
      "verification_rate": 90.00
    },
    "modules": [
      {
        "name": "Articles",
        "slug": "articles",
        "icon": "book-open",
        "color": "#0056b3",
        "count": 450,
        "recent_count": 15,
        "last_activity": "2025-10-31 12:00:00",
        "growth_rate": 3.33
      }
      // ... other modules
    ]
  }
}
```

**Example**:
```bash
# All time statistics
curl -X GET https://example.com/su/portal/v1/admin/analytics/stats

# Filtered by date range
curl -X GET https://example.com/su/portal/v1/admin/analytics/stats?start_date=2025-10-01&end_date=2025-10-31
```

---

### 3. Get Chart Data

```http
GET /su/portal/v1/admin/analytics/charts/{type}
```

**Description**: Get data for various chart types.

**Path Parameters**:
| Parameter | Type | Values | Description |
|-----------|------|--------|-------------|
| type | string | growth, distribution, engagement, activity | Chart type |

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| period | string | No | hourly, daily, weekly, monthly (default: daily) |
| limit | integer | No | Number of data points (7-365, default: 30) |

**Response (Growth Chart)**:
```json
{
  "success": true,
  "message": "Chart data retrieved successfully",
  "data": {
    "period": "daily",
    "limit": 30,
    "trends": {
      "articles": [
        {"period": "2025-10-01", "count": 5},
        {"period": "2025-10-02", "count": 8}
      ],
      "hadith": [
        {"period": "2025-10-01", "count": 12},
        {"period": "2025-10-02", "count": 15}
      ],
      "media": [...],
      "users": [...],
      "activity": [...]
    }
  }
}
```

**Response (Distribution Chart)**:
```json
{
  "success": true,
  "message": "Chart data retrieved successfully",
  "data": {
    "labels": ["Articles", "Hadith", "Media", "Sahaba", "Seerah", "Quran", "Genealogy"],
    "data": [450, 5000, 175, 120, 85, 6236, 200],
    "colors": ["#0056b3", "#218838", "#64CCC5", "#3B82F6", "#F59E0B", "#10B981", "#8B5CF6"]
  }
}
```

**Examples**:
```bash
# Growth chart (daily, last 30 days)
curl -X GET https://example.com/su/portal/v1/admin/analytics/charts/growth

# Growth chart (weekly, last 12 weeks)
curl -X GET https://example.com/su/portal/v1/admin/analytics/charts/growth?period=weekly&limit=12

# Distribution chart
curl -X GET https://example.com/su/portal/v1/admin/analytics/charts/distribution

# Engagement chart
curl -X GET https://example.com/su/portal/v1/admin/analytics/charts/engagement?limit=30

# Activity heatmap
curl -X GET https://example.com/su/portal/v1/admin/analytics/charts/activity?limit=30
```

---

### 4. Get Engagement Metrics

```http
GET /su/portal/v1/admin/analytics/engagement
```

**Description**: Get detailed user engagement metrics.

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | string (Y-m-d) | No | Start date for filtering |
| end_date | string (Y-m-d) | No | End date |

**Response**:
```json
{
  "success": true,
  "message": "Engagement metrics retrieved successfully",
  "data": {
    "total_views": 125000,
    "total_favorites": 3500,
    "average_views_per_article": 277.78,
    "by_module": {
      "articles": {
        "total_views": 50000,
        "total_favorites": 3500,
        "avg_views": 111.11
      },
      "hadith": {
        "total_views": 60000,
        "avg_views": 12.00
      },
      "media": {
        "total_views": 15000,
        "avg_views": 85.71
      }
    },
    "engagement_rate": 101.38
  }
}
```

---

### 5. Get Growth Trends

```http
GET /su/portal/v1/admin/analytics/trends
```

**Description**: Get content growth trends over time.

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| period | string | No | hourly, daily, weekly, monthly (default: daily) |
| limit | integer | No | Number of periods (7-365, default: 30) |

**Response**:
```json
{
  "success": true,
  "message": "Trend data retrieved successfully",
  "data": {
    "period": "daily",
    "limit": 30,
    "trends": {
      "articles": [
        {"period": "2025-10-01", "count": 5}
      ],
      "hadith": [...],
      "media": [...],
      "users": [...],
      "activity": [...]
    }
  }
}
```

---

### 6. Get Top Content

```http
GET /su/portal/v1/admin/analytics/top-content
```

**Description**: Get top viewed content across all modules.

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| limit | integer | No | Number of items per module (5-50, default: 10) |

**Response**:
```json
{
  "success": true,
  "message": "Top content retrieved successfully",
  "data": {
    "articles": [
      {
        "id": 1,
        "title": "Article Title",
        "views": 5000,
        "favorites": 250,
        "created_at": "2025-10-15"
      }
    ],
    "hadith": [
      {
        "id": 1,
        "text": "Hadith text...",
        "book": "Sahih Bukhari",
        "hadith_number": "1",
        "views": 3000,
        "created_at": "2025-10-10"
      }
    ],
    "media": [...],
    "reciters": [...]
  }
}
```

---

### 7. Get Module-Specific Analytics

```http
GET /su/portal/v1/admin/analytics/module/{module}
```

**Description**: Get detailed analytics for a specific module.

**Path Parameters**:
| Parameter | Type | Values | Description |
|-----------|------|--------|-------------|
| module | string | articles, hadith, media, sahaba, seerah, quran, genealogy | Module name |

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | string (Y-m-d) | No | Start date for filtering |
| end_date | string (Y-m-d) | No | End date |

**Response (Articles Module)**:
```json
{
  "success": true,
  "message": "Analytics for articles retrieved successfully",
  "data": {
    "stats": {
      "total": 450,
      "published": 350,
      "draft": 50,
      "archived": 50
    },
    "engagement": {
      "total_views": 50000,
      "total_favorites": 3500,
      "avg_views": 111.11
    },
    "top_content": [
      {
        "id": 1,
        "title": "Top Article",
        "views": 5000,
        "favorites": 250,
        "created_at": "2025-10-15"
      }
    ]
  }
}
```

**Examples**:
```bash
curl -X GET https://example.com/su/portal/v1/admin/analytics/module/articles
curl -X GET https://example.com/su/portal/v1/admin/analytics/module/hadith?start_date=2025-10-01&end_date=2025-10-31
```

---

### 8. Get Date Range Comparison

```http
GET /su/portal/v1/admin/analytics/comparison
```

**Description**: Compare analytics between current period and previous equal period.

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | string (Y-m-d) | Yes | Start date |
| end_date | string (Y-m-d) | Yes | End date (must be >= start_date) |

**Response**:
```json
{
  "success": true,
  "message": "Comparison analytics retrieved successfully",
  "data": {
    "current_period": {
      "start": "2025-10-01",
      "end": "2025-10-31",
      "stats": {
        "articles": 45,
        "hadith": 120,
        "media": 8,
        "users": 25,
        "activity": 350
      }
    },
    "previous_period": {
      "start": "2025-09-01",
      "end": "2025-09-30",
      "stats": {
        "articles": 35,
        "hadith": 100,
        "media": 6,
        "users": 20,
        "activity": 280
      }
    },
    "changes": {
      "articles": 28.57,
      "hadith": 20.00,
      "media": 33.33,
      "users": 25.00,
      "activity": 25.00
    }
  }
}
```

---

### 9. Get Recent Activity

```http
GET /su/portal/v1/admin/analytics/recent-activity
```

**Description**: Get recent activity summary from admin logs.

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| limit | integer | No | Number of items (5-100, default: 20) |

**Response**:
```json
{
  "success": true,
  "message": "Recent activity retrieved successfully",
  "data": [
    {
      "id": 1,
      "user": {
        "name": "Admin User",
        "avatar": null
      },
      "action": "created",
      "model": "Article",
      "description": "Admin User created Article 'New Title'",
      "timestamp": "5 minutes ago",
      "icon": "plus-circle",
      "color": "green"
    }
  ]
}
```

---

### 10. Get Performance Metrics

```http
GET /su/portal/v1/admin/analytics/performance
```

**Description**: Get system performance metrics.

**Response**:
```json
{
  "success": true,
  "message": "Performance metrics retrieved successfully",
  "data": {
    "api_response_time": 145,
    "database_size": 523.45,
    "cache_hit_rate": 85.0,
    "active_sessions": 12
  }
}
```

---

### 11. Export Analytics (Placeholder)

```http
GET /su/portal/v1/admin/analytics/export
```

**Description**: Export analytics data (to be implemented).

**Query Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| format | string | Yes | csv, excel |
| type | string | Yes | stats, trends, top_content, engagement |
| start_date | string (Y-m-d) | No | Start date |
| end_date | string (Y-m-d) | No | End date |

**Response**:
```json
{
  "success": true,
  "message": "Export request received",
  "data": {
    "message": "Export functionality coming soon",
    "requested_format": "excel",
    "requested_type": "stats"
  }
}
```

---

### 12. Clear Analytics Cache

```http
POST /su/portal/v1/admin/analytics/cache/clear
```

**Description**: Clear all analytics cache.

**Response**:
```json
{
  "success": true,
  "message": "Analytics cache cleared successfully",
  "data": null
}
```

**Example**:
```bash
curl -X POST https://example.com/su/portal/v1/admin/analytics/cache/clear \
  -H "Cookie: laravel_session=..." \
  -H "X-CSRF-TOKEN: ..."
```

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "end_date": ["The end date must be after or equal to start date."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Resource not found"
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "Internal server error"
}
```

### Bad Request (400)
```json
{
  "success": false,
  "message": "Invalid chart type"
}
```

---

## Caching

All analytics endpoints implement Redis caching:

- **Statistics**: 1 hour TTL
- **Charts**: 30 minutes TTL
- **Trends**: 1 day TTL

Cache keys follow the pattern:
```
analytics:{method}:{md5(params)}
```

Cache can be manually cleared via:
```bash
POST /su/portal/v1/admin/analytics/cache/clear
```

---

## Rate Limiting

All endpoints are rate-limited to **60 requests per minute** per authenticated user.

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1698768000
```

---

## Best Practices

1. **Use Date Filters**: For large datasets, always use date range filters to improve performance.
2. **Cache Wisely**: Don't clear cache unless necessary; let TTL handle expiration.
3. **Limit Results**: Use the `limit` parameter to reduce response size.
4. **Monitor Rate Limits**: Check rate limit headers to avoid throttling.
5. **Handle Errors**: Always handle validation and server errors gracefully.

---

## Examples with JavaScript

### Fetch Statistics
```javascript
async function getStatistics(startDate = null, endDate = null) {
  const params = new URLSearchParams();
  if (startDate) params.append('start_date', startDate);
  if (endDate) params.append('end_date', endDate);

  const response = await fetch(`/su/portal/v1/admin/analytics/stats?${params}`, {
    headers: {
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  return data.data;
}
```

### Load Chart Data
```javascript
async function loadGrowthChart() {
  const response = await fetch('/su/portal/v1/admin/analytics/charts/growth?period=daily&limit=30');
  const data = await response.json();

  if (data.success) {
    renderChart(data.data);
  }
}
```

### Clear Cache
```javascript
async function clearAnalyticsCache() {
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  const response = await fetch('/su/portal/v1/admin/analytics/cache/clear', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  console.log(data.message);
}
```

---

## Postman Collection

Import this collection to test all endpoints:

```json
{
  "info": {
    "name": "The Last Messenger - Analytics API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Get Statistics",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{base_url}}/analytics/stats?start_date=2025-10-01&end_date=2025-10-31",
          "host": ["{{base_url}}"],
          "path": ["analytics", "stats"],
          "query": [
            {"key": "start_date", "value": "2025-10-01"},
            {"key": "end_date", "value": "2025-10-31"}
          ]
        }
      }
    }
  ],
  "variable": [
    {
      "key": "base_url",
      "value": "https://example.com/su/portal/v1/admin"
    }
  ]
}
```

---

For questions or issues, refer to the main documentation or contact the development team.
