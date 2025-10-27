# The Last Messenger - Laravel Backend

A comprehensive Islamic digital encyclopedia backend built with Laravel 12.x, featuring Prophet Muhammad ﷺ biography (Seerah), Hadith database, Quran module, media programs, timeline events, and genealogy.

## Project Status

**Version:** 0.1.0-alpha  
**Laravel:** 12.9.0  
**PHP:** 8.3+  
**Database:** MySQL 8.0+  
**Cache:** Redis

## Architecture

This project follows **Domain-Driven Design (DDD)** with **Repository Pattern** and **Service Layer**:

```
app/
├── Domain/              # Business logic organized by domain
│   ├── Blog/           # Seerah/Articles module
│   ├── Hadith/         # Hadith database (pending)
│   ├── Media/          # Video programs (pending)
│   ├── Timeline/       # Chronological events (pending)
│   ├── Genealogy/      # Family tree (pending)
│   ├── Quran/          # Quran module (pending)
│   └── Shared/         # Shared contracts and base classes
├── Http/               # API layer (controllers, resources, requests)
├── Support/            # Utilities and helpers
└── Exceptions/         # Custom exceptions
```

## Key Features

### Implemented ✅
- **Clean Architecture**: Repository pattern, service layer, dependency injection
- **Multi-Language Support**: Arabic, English, French, Turkish, Urdu
- **API Authentication**: Laravel Sanctum with token-based auth
- **Permission System**: Spatie Permission (role-based access control)
- **Media Management**: Spatie Media Library integration
- **Optimized Queries**: Eager loading, database indexes, no N+1 queries
- **Caching Strategy**: Redis caching in repository layer (15 min TTL)
- **Full-Text Search**: MySQL full-text indexes for articles
- **Polymorphic Relations**: Favorites, tags, user interactions

### Blog/Seerah Module ✅
- Multi-language articles with JSON storage
- Hierarchical categories
- Article series support
- Tagging system (polymorphic)
- User favorites/bookmarks
- View tracking and analytics
- Reading time calculation
- Publication workflow (draft, published, archived)
- Soft deletes

### Pending Modules ⏳
- Hadith database with narrator chains
- Media programs (Netflix-style UI)
- Timeline events with geolocation
- Genealogy tree (interactive family tree)
- Quran with recitation support
- Global search across all modules
- User profiles and preferences
- Notifications system

## Installation

### Requirements
- PHP 8.3+
- Composer
- MySQL 8.0+
- Redis
- Node.js & NPM (for frontend assets)

### Setup

```bash
# Clone repository
git clone <repository-url>
cd the-last-messenger-laravel

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database and Redis in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=last_messenger
DB_USERNAME=root
DB_PASSWORD=

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed permissions and roles
php artisan db:seed --class=RolePermissionSeeder

# Start development server
php artisan serve
```

## Database Schema

### Users & Auth
- `users` - User accounts with language preferences
- `password_reset_tokens` - Password reset tokens
- `personal_access_tokens` - Sanctum API tokens
- `roles` - User roles (Spatie Permission)
- `permissions` - Granular permissions
- `model_has_roles` - Role assignments
- `model_has_permissions` - Direct permission assignments

### Blog Module
- `categories` - Hierarchical categories (multi-language)
- `tags` - Generic tagging system
- `taggables` - Polymorphic tag pivot
- `series` - Article series grouping
- `articles` - Blog posts/Seerah content
- `favorites` - User bookmarks (polymorphic)
- `user_interactions` - View tracking, progress

### Media (Spatie)
- `media` - File uploads and media library

## API Endpoints

### Authentication
```
POST   /api/register          # User registration
POST   /api/login             # User login
POST   /api/logout            # User logout
GET    /api/user              # Get authenticated user
```

### Articles (Pending Implementation)
```
GET    /api/articles          # List articles (paginated, filtered)
GET    /api/articles/{slug}   # Get single article
POST   /api/articles          # Create article (auth required)
PUT    /api/articles/{id}     # Update article (auth required)
DELETE /api/articles/{id}     # Delete article (auth required)
GET    /api/articles/search   # Search articles
POST   /api/articles/{id}/favorite    # Favorite article
DELETE /api/articles/{id}/favorite    # Unfavorite article
```

### Categories
```
GET    /api/categories        # List categories
GET    /api/categories/{slug} # Get category with articles
```

## Design Patterns

### Repository Pattern
All data access goes through repositories, never direct Eloquent calls in controllers:

```php
// ❌ Bad: Direct model usage in controller
$articles = Article::where('status', 'published')->get();

// ✅ Good: Through repository
$articles = $this->articleRepository->getPublished();
```

### Service Layer
Business logic resides in service classes:

```php
// ❌ Bad: Logic in controller
public function store(Request $request) {
    $article = Article::create($request->all());
    $article->tags()->attach($request->tags);
    Cache::forget('articles');
    return response()->json($article);
}

// ✅ Good: Logic in service
public function store(Request $request) {
    $article = $this->articleService->create($request->validated());
    return $this->createdResponse($article);
}
```

### Caching Strategy
- Articles: 15 minutes TTL
- Categories: 30 minutes TTL
- Hadith: 30 minutes TTL
- Genealogy tree: 1 hour TTL
- Quran data: Permanent (rarely changes)

Cache is automatically invalidated on create/update/delete operations.

## Code Standards

- **Strict Types**: All files use `declare(strict_types=1);`
- **Type Hints**: All parameters and return types are typed
- **PHPDoc**: Complete documentation for all methods
- **PSR-12**: Following Laravel's coding style
- **SOLID Principles**: Applied throughout
- **No N+1 Queries**: Eager loading enforced
- **Database Indexes**: All foreign keys and search columns indexed

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

## Performance

### Query Optimization
- Eager loading all relationships
- Selective column fetching
- Database indexes on all foreign keys
- Composite indexes for common queries
- Full-text indexes for search

### Caching
- Repository layer caching with Redis
- Query result caching
- Cache invalidation on mutations
- Cache warming commands

### Scalability
- Stateless API (Sanctum tokens)
- Redis session storage
- CDN-ready media handling
- Queue jobs for heavy operations
- Horizontal scaling capable

## Security

- CORS configuration
- CSRF protection
- XSS prevention
- SQL injection protection (parameterized queries)
- Rate limiting on API routes
- Role-based access control
- Input validation and sanitization

## Contributing

1. Follow the established architecture patterns
2. Write tests for all new features
3. Use repository pattern (no direct Model calls)
4. Add proper type hints and PHPDoc
5. Run `php artisan pint` for code formatting
6. Ensure no N+1 queries (use Laravel Debugbar)

## License

[Your License Here]

## Credits

**Developer**: [Your Name]  
**Framework**: Laravel 12.x  
**Packages**: Sanctum, Spatie (Permission, Media Library, Translatable)
