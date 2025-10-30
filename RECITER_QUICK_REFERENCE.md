# ReciterController Quick Reference

## Files Location

### Backend Core
```
app/Domain/Quran/
├── Models/Reciter.php (pre-existing)
├── Repositories/
│   ├── ReciterRepositoryInterface.php
│   └── ReciterRepository.php
└── Services/
    └── ReciterService.php

app/Http/
├── Controllers/Admin/
│   └── ReciterController.php
└── Requests/Admin/
    ├── StoreReciterRequest.php
    └── UpdateReciterRequest.php
```

### Configuration
```
app/Providers/RepositoryServiceProvider.php (updated)
routes/admin.php (updated)
database/migrations/2025_10_27_150002_create_quran_reciters_table.php (ran)
```

## API Endpoints

### Resource Routes
```php
GET    /su/portal/v1/admin/reciters              # List all reciters
GET    /su/portal/v1/admin/reciters/create       # Show create form
POST   /su/portal/v1/admin/reciters              # Store new reciter
GET    /su/portal/v1/admin/reciters/{id}         # Show reciter details
GET    /su/portal/v1/admin/reciters/{id}/edit    # Show edit form
PUT    /su/portal/v1/admin/reciters/{id}         # Update reciter
DELETE /su/portal/v1/admin/reciters/{id}         # Delete reciter
```

### Custom Routes
```php
GET  /su/portal/v1/admin/reciters/search                 # Search reciters
POST /su/portal/v1/admin/reciters/bulk-delete            # Bulk delete
POST /su/portal/v1/admin/reciters/reorder                # Reorder reciters
POST /su/portal/v1/admin/reciters/{id}/toggle-featured   # Toggle featured
```

## Usage Examples

### Create Reciter (cURL)
```bash
curl -X POST http://localhost:8000/su/portal/v1/admin/reciters \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "name[ar]=الشيخ عبد الباسط" \
  -F "name[en]=Sheikh Abdulbasit" \
  -F "country=Egypt" \
  -F "style=Hafs" \
  -F "profile_image=@/path/to/image.jpg" \
  -F "is_featured=1"
```

### Search Reciters (AJAX)
```javascript
fetch('/su/portal/v1/admin/reciters/search?query=عبد', {
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': 'Bearer YOUR_TOKEN'
    }
})
.then(res => res.json())
.then(data => console.log(data.data));
```

### Reorder Reciters
```javascript
fetch('/su/portal/v1/admin/reciters/reorder', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': 'Bearer YOUR_TOKEN'
    },
    body: JSON.stringify({ ids: [5, 2, 8, 1, 3] })
});
```

### Toggle Featured
```javascript
fetch('/su/portal/v1/admin/reciters/1/toggle-featured', {
    method: 'POST',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Authorization': 'Bearer YOUR_TOKEN'
    }
});
```

## Database Schema

```sql
quran_reciters
├── id (BIGINT, PRIMARY KEY)
├── slug (VARCHAR, UNIQUE)
├── name (JSON) {"ar": "...", "en": "...", "fr": "...", "tr": "...", "ur": "..."}
├── description (JSON, NULLABLE)
├── style (VARCHAR, NULLABLE)
├── country (VARCHAR, NULLABLE)
├── profile_image (VARCHAR, NULLABLE)
├── is_featured (BOOLEAN, DEFAULT FALSE)
├── display_order (INT, DEFAULT 0)
├── created_at (TIMESTAMP)
└── updated_at (TIMESTAMP)
```

## Validation Rules

### Name (Required)
- Arabic (ar): required, string, max:255
- English (en): required, string, max:255
- French (fr): nullable, string, max:255
- Turkish (tr): nullable, string, max:255
- Urdu (ur): nullable, string, max:255

### Description (Optional)
- All languages: nullable, string, max:2000

### Other Fields
- slug: nullable, string, unique, regex (auto-generated if empty)
- country: nullable, string, max:100
- style: nullable, in:Tajweed,Qaloon,Warsh,Hafs,Kalon,Duri,Kasai
- profile_image: nullable, image, mimes:jpeg,png,jpg,webp, max:2048KB
- is_featured: nullable, boolean
- display_order: nullable, integer, min:0

## Service Methods

```php
// ReciterService methods
$service->paginate(15);                      // Paginated list
$service->create($data);                     // Create with image upload
$service->update($reciter, $data);           // Update with image replace
$service->delete($reciter);                  // Delete with image cleanup
$service->bulkDelete([1, 2, 3]);             // Bulk delete
$service->getByCountry('Egypt');             // Filter by country
$service->getByStyle('Hafs');                // Filter by style
$service->getFeatured();                     // Get featured reciters
$service->toggleFeatured($reciter);          // Toggle featured status
$service->reorder([5, 2, 8, 1, 3]);          // Reorder by IDs
$service->countRecitations($reciter);        // Count recitations (placeholder)
$service->search('عبد');                      // Multi-language search
```

## Repository Methods

```php
// ReciterRepository methods (all cached)
$repo->paginate(15);                         // Paginated (not cached)
$repo->getById(1);                           // By ID (cached 30 min)
$repo->getByCountry('Egypt');                // By country (cached)
$repo->getByStyle('Hafs');                   // By style (cached)
$repo->getFeatured();                        // Featured only (cached)
$repo->search('query');                      // Multi-language search
$repo->create($data);                        // Create + cache flush
$repo->update($reciter, $data);              // Update + cache flush
$repo->delete($reciter);                     // Delete + cache flush
$repo->bulkDelete([1, 2, 3]);                // Bulk delete + cache flush
$repo->reorder([5, 2, 8]);                   // Reorder + cache flush
$repo->updateFeatured($reciter, true);       // Update featured + cache flush
$repo->countRecitations($reciter);           // Count (placeholder)
```

## Image Storage

### Upload Path
```
public/storage/reciters/profiles/{filename}
```

### Access URL
```
https://your-domain.com/storage/reciters/profiles/{filename}
```

### Supported Formats
- JPEG (.jpg, .jpeg)
- PNG (.png)
- WebP (.webp)

### Max File Size
2 MB (2048 KB)

## Caching Strategy

### Cache Driver
Redis

### Cache Tags
```php
['reciters']
```

### TTL
30 minutes

### Cached Operations
- getById()
- getByCountry()
- getByStyle()
- getFeatured()

### Cache Invalidation Triggers
- create()
- update()
- delete()
- bulkDelete()
- reorder()
- updateFeatured()

### Manual Cache Clear
```bash
php artisan cache:tags reciters --flush
# OR
php artisan cache:clear
```

## Testing Commands

### Check Routes
```bash
php artisan route:list --path=reciters
```

### Test Controller Instantiation
```bash
php artisan tinker
> app('App\Http\Controllers\Admin\ReciterController');
```

### Test Service Resolution
```bash
php artisan tinker
> app('App\Domain\Quran\Services\ReciterService');
```

### Test Repository Binding
```bash
php artisan tinker
> app('App\Domain\Quran\Repositories\ReciterRepositoryInterface');
```

### Syntax Check
```bash
php -l app/Http/Controllers/Admin/ReciterController.php
php -l app/Domain/Quran/Services/ReciterService.php
php -l app/Domain/Quran/Repositories/ReciterRepository.php
```

## Common Operations

### Create Sample Reciter
```php
php artisan tinker
> $service = app('App\Domain\Quran\Services\ReciterService');
> $service->create([
    'name' => [
        'ar' => 'الشيخ عبد الباسط عبد الصمد',
        'en' => 'Sheikh Abdulbasit Abdulsamad',
    ],
    'country' => 'Egypt',
    'style' => 'Hafs',
    'is_featured' => true,
    'display_order' => 1
]);
```

### Search Reciters
```php
$service->search('عبد');
```

### Get Featured Reciters
```php
$service->getFeatured();
```

### Filter by Country
```php
$service->getByCountry('Egypt');
```

## Error Handling

All endpoints return consistent error responses:

### Success Response (JSON)
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {...}
}
```

### Error Response (JSON)
```json
{
    "success": false,
    "message": "Operation failed",
    "error": "Detailed error message"
}
```

### HTTP Status Codes
- 200: Success (GET, PUT, PATCH, DELETE)
- 201: Created (POST)
- 422: Validation Error
- 404: Not Found
- 500: Server Error

## Activity Logging

All operations are logged via AdminLoggerService:

```php
// Create log
$this->logger->logCreate($reciter, $user);

// Update log (with diff)
$this->logger->logUpdate($reciter, $oldValues, $newValues, $user);

// Delete log
$this->logger->logDelete($reciter, $user);

// Bulk action log
$this->logger->logBulkAction('bulk_delete', Reciter::class, $ids, $user);

// Custom action log
$this->logger->logCustomAction('reorder_reciters', Reciter::class, $user, null, ['ids' => $ids]);
```

View logs at: `/su/portal/v1/admin/activity-logs`

## Troubleshooting

### Image Upload Fails
```bash
# Check storage is linked
php artisan storage:link

# Check permissions
chmod -R 775 storage
chmod -R 775 public/storage
```

### Cache Not Clearing
```bash
# Clear all cache
php artisan cache:clear

# Clear specific tag
php artisan tinker
> Cache::tags(['reciters'])->flush();
```

### Routes Not Found
```bash
# Clear route cache
php artisan route:clear

# List all routes
php artisan route:list
```

### Class Not Found
```bash
# Rebuild autoload
composer dump-autoload
```

## Performance Tips

1. **Use pagination** for large datasets
2. **Enable Redis caching** for production
3. **Optimize images** before upload (compress, resize)
4. **Use eager loading** when accessing relationships
5. **Monitor cache hit rate** in production

## Security Notes

1. All routes protected by `auth` middleware
2. Admin role required via `admin` middleware
3. Rate limiting: 60 requests/minute
4. Image validation prevents malicious uploads
5. SQL injection prevented by Eloquent
6. XSS protection via validation rules

---

**Last Updated:** 2025-10-31
**Version:** 1.0.0
**Status:** Production Ready ✅
