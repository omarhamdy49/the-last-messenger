# Admin Routing - Quick Reference Guide

## Table of Contents
1. [URLs](#urls)
2. [Middleware](#middleware)
3. [Authorization](#authorization)
4. [Base Controller Usage](#base-controller-usage)
5. [Common Patterns](#common-patterns)
6. [Route Examples](#route-examples)

---

## URLs

### Admin Portal Base URL
```
/su/portal/v1/admin
```

### Key Endpoints
| Endpoint | Purpose |
|----------|---------|
| `/su/portal/v1/admin/login` | Admin login page |
| `/su/portal/v1/admin/logout` | Admin logout |
| `/su/portal/v1/admin/dashboard` | Main dashboard |
| `/su/portal/v1/admin/articles` | Articles management |
| `/su/portal/v1/admin/hadith` | Hadith management |
| `/su/portal/v1/admin/quran` | Quran management |
| `/su/portal/v1/admin/users` | User management |
| `/su/portal/v1/admin/roles` | Role management |

---

## Middleware

### Middleware Stack
```php
['web', 'auth', 'admin', 'throttle:60,1']
```

### Middleware Aliases
```php
'admin' => AdminAuthMiddleware::class
'permission' => PermissionMiddleware::class
'role' => RoleMiddleware::class
```

### Apply Additional Middleware
```php
Route::middleware(['admin', 'permission:edit articles'])
    ->group(function() {
        // Routes here
    });
```

---

## Authorization

### Check User Role
```php
// In controller
if (auth()->user()->hasRole('super_admin')) {
    // Super admin logic
}

if (auth()->user()->hasAnyRole(['admin', 'editor'])) {
    // Admin or editor logic
}
```

### Check Permission
```php
// Using base controller method
$this->authorizeAdmin('edit articles');

// Direct check
if (auth()->user()->hasPermissionTo('edit articles')) {
    // User has permission
}

// Using Gate
Gate::authorize('edit', $article);
```

### Available Roles
- `super_admin` - Full access
- `admin` - Content + user management
- `editor` - Content editing
- `researcher` - Content viewing + creation
- `user` - Basic viewing

### Common Permissions
```php
'view articles'
'create articles'
'edit articles'
'delete articles'
'publish articles'
'manage settings'
'view analytics'
```

---

## Base Controller Usage

### Extend AdminController
```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;

class YourController extends AdminController
{
    // Your methods here
}
```

### Available Methods

#### 1. Authorization
```php
// Check permission (throws 403 if denied)
$this->authorizeAdmin('edit articles');
```

#### 2. Validation
```php
$data = $this->validateRequest($request, [
    'title' => 'required|string|max:255',
    'content' => 'required|string',
], [
    'title.required' => 'Custom error message'
]);
```

#### 3. Pagination
```php
$params = $this->getPaginationParams($request);
// Returns: ['per_page' => 15, 'page' => 1, 'sort' => 'created_at', 'order' => 'desc']

$query->orderBy($params['sort'], $params['order'])
      ->paginate($params['per_page']);
```

#### 4. Search
```php
$search = $this->getSearchQuery($request);
if ($search) {
    $query->where('title', 'like', "%{$search}%");
}
```

#### 5. Filters
```php
$filters = $this->getFilters($request, ['status', 'category_id', 'author_id']);
foreach ($filters as $key => $value) {
    $query->where($key, $value);
}
```

#### 6. Activity Logging
```php
$this->logActivity('created', 'Article', $article->id, [
    'title' => $article->title,
    'category' => $article->category_id
]);
```

#### 7. Bulk Operations
```php
$results = $this->handleBulkOperation($request, 'delete', function($id) {
    Article::findOrFail($id)->delete();
});

// Returns: ['success' => 5, 'failed' => 2, 'errors' => [...]]
```

#### 8. Response Helpers
```php
// Success response (auto-detects JSON/HTML)
return $this->success('Article created', $article, 'admin.articles.index');

// Error response
return $this->failure('Article not found', 404);

// API responses
return $this->successResponse($data, 'Success message');
return $this->errorResponse('Error message', 400);
return $this->paginatedResponse($paginator);
```

---

## Common Patterns

### 1. Standard Index Method
```php
public function index(Request $request)
{
    $this->authorizeAdmin('view articles');

    $params = $this->getPaginationParams($request);
    $search = $this->getSearchQuery($request);
    $filters = $this->getFilters($request, ['status', 'category_id']);

    $query = Article::with('category', 'author');

    if ($search) {
        $query->where('title', 'like', "%{$search}%");
    }

    foreach ($filters as $key => $value) {
        $query->where($key, $value);
    }

    $articles = $query->orderBy($params['sort'], $params['order'])
                      ->paginate($params['per_page']);

    return $this->expectsJson($request)
        ? $this->paginatedResponse($articles)
        : view('admin.articles.index', compact('articles'));
}
```

### 2. Standard Store Method
```php
public function store(Request $request)
{
    $this->authorizeAdmin('create articles');

    $data = $this->validateRequest($request, [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'status' => 'required|in:draft,published',
    ]);

    $data['author_id'] = auth()->id();
    $article = Article::create($data);

    $this->logActivity('created', 'Article', $article->id);

    return $this->success('Article created successfully', $article, 'admin.articles.index');
}
```

### 3. Standard Update Method
```php
public function update(Request $request, Article $article)
{
    $this->authorizeAdmin('edit articles');

    $data = $this->validateRequest($request, [
        'title' => 'required|string|max:255',
        'content' => 'required|string',
    ]);

    $article->update($data);

    $this->logActivity('updated', 'Article', $article->id, [
        'changes' => $article->getChanges()
    ]);

    return $this->success('Article updated successfully', $article, 'admin.articles.show');
}
```

### 4. Standard Destroy Method
```php
public function destroy(Article $article)
{
    $this->authorizeAdmin('delete articles');

    $articleId = $article->id;
    $article->delete();

    $this->logActivity('deleted', 'Article', $articleId);

    return $this->success('Article deleted successfully', null, 'admin.articles.index');
}
```

### 5. Bulk Delete Method
```php
public function bulkDelete(Request $request)
{
    $this->authorizeAdmin('delete articles');

    $results = $this->handleBulkOperation($request, 'delete', function($id) {
        Article::findOrFail($id)->delete();
    });

    $message = "Deleted {$results['success']} articles";
    if ($results['failed'] > 0) {
        $message .= ", {$results['failed']} failed";
    }

    return $this->success($message, $results);
}
```

### 6. Toggle Status Method
```php
public function toggleFeature(Article $article)
{
    $this->authorizeAdmin('edit articles');

    $article->update([
        'is_featured' => !$article->is_featured
    ]);

    $this->logActivity('toggled_feature', 'Article', $article->id);

    return $this->success(
        'Article feature status updated',
        ['is_featured' => $article->is_featured]
    );
}
```

---

## Route Examples

### Define Resource Routes
```php
Route::resource('articles', ArticleController::class)->names([
    'index' => 'admin.articles.index',
    'create' => 'admin.articles.create',
    'store' => 'admin.articles.store',
    'show' => 'admin.articles.show',
    'edit' => 'admin.articles.edit',
    'update' => 'admin.articles.update',
    'destroy' => 'admin.articles.destroy',
]);
```

### Add Custom Routes
```php
Route::prefix('articles')->group(function() {
    // Before resource routes (no wildcard)
    Route::get('/search', [ArticleController::class, 'search'])->name('admin.articles.search');
    Route::get('/stats', [ArticleController::class, 'stats'])->name('admin.articles.stats');
    Route::post('/bulk-delete', [ArticleController::class, 'bulkDelete'])->name('admin.articles.bulk-delete');
});

// After resource routes (with wildcard)
Route::post('articles/{article}/toggle-feature', [ArticleController::class, 'toggleFeature'])
    ->name('admin.articles.toggle-feature');
```

### Route with Additional Middleware
```php
Route::middleware(['permission:manage users'])
    ->resource('users', UserController::class);
```

### Route with Multiple Permissions
```php
Route::prefix('settings')->middleware(['permission:manage settings'])
    ->group(function() {
        // Settings routes
    });
```

---

## Testing Commands

### List All Admin Routes
```bash
php artisan route:list --path=admin
```

### Filter by Name
```bash
php artisan route:list --name=admin.articles
```

### Check Specific Route
```bash
php artisan route:list | grep "articles"
```

### Clear Caches
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

---

## Blade Templates

### Check Auth
```blade
@auth
    <p>Welcome, {{ auth()->user()->name }}</p>
@endauth

@guest
    <a href="{{ route('login') }}">Login</a>
@endguest
```

### Check Role
```blade
@role('super_admin')
    <button>Super Admin Only</button>
@endrole

@hasanyrole('admin|editor')
    <button>Admin or Editor</button>
@endhasanyrole
```

### Check Permission
```blade
@can('edit articles')
    <a href="{{ route('admin.articles.edit', $article) }}">Edit</a>
@endcan

@cannot('delete articles')
    <p>You cannot delete articles</p>
@endcannot
```

### Display Errors
```blade
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

### Display Success Message
```blade
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif
```

---

## API Response Examples

### Success Response
```json
{
  "success": true,
  "message": "Article created successfully",
  "data": {
    "id": 1,
    "title": "Article Title",
    "content": "Article content..."
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."],
    "content": ["The content field is required."]
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Articles retrieved successfully",
  "data": [...],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  },
  "links": {
    "first": "url",
    "last": "url",
    "prev": null,
    "next": "url"
  }
}
```

---

## Security Checklist

- [ ] All routes use `auth` middleware
- [ ] Admin routes use `admin` middleware
- [ ] Sensitive routes use `permission` or `role` middleware
- [ ] All POST/PUT/PATCH/DELETE requests include CSRF token
- [ ] Rate limiting is applied (`throttle:60,1`)
- [ ] Input is validated before processing
- [ ] User actions are logged
- [ ] Sensitive data is not exposed in responses
- [ ] File uploads are validated and secured
- [ ] SQL injection is prevented (use query builder/Eloquent)

---

## Common Issues

### Issue: Permission Denied
**Check**:
```php
// User has role?
auth()->user()->hasRole('admin')

// User has permission?
auth()->user()->hasPermissionTo('edit articles')

// Role seeded?
php artisan db:seed --class=RolePermissionSeeder
```

### Issue: CSRF Token Mismatch
**Add to forms**:
```blade
@csrf
```

### Issue: Route Not Found
**Clear cache**:
```bash
php artisan route:clear
php artisan route:cache
```

---

## Performance Tips

1. **Eager Loading**: Always use `with()` to prevent N+1 queries
```php
$articles = Article::with('category', 'author')->paginate();
```

2. **Select Only Needed Columns**:
```php
$articles = Article::select('id', 'title', 'created_at')->get();
```

3. **Use Pagination**: Never load all records
```php
$articles = Article::paginate(15);
```

4. **Cache Results**: Cache expensive queries
```php
$stats = Cache::remember('dashboard_stats', 3600, function() {
    return Dashboard::getStats();
});
```

5. **Use Indexes**: Ensure database columns used in WHERE clauses are indexed

---

## File Locations Reference

| Component | Location |
|-----------|----------|
| Admin Routes | `/routes/admin.php` |
| Admin Middleware | `/app/Http/Middleware/AdminAuthMiddleware.php` |
| Base Admin Controller | `/app/Http/Controllers/Admin/AdminController.php` |
| Admin Controllers | `/app/Http/Controllers/Admin/*` |
| User Model | `/app/Models/User.php` |
| Permission Seeder | `/database/seeders/RolePermissionSeeder.php` |
| Bootstrap Config | `/bootstrap/app.php` |

---

**Last Updated**: 2025-11-07
**Laravel Version**: 12.35.1
