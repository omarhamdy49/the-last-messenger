# Quick Start - The Last Messenger Backend

## 🎯 Current Status
✅ **Backend 50% Complete** - Foundation, Blog, and Hadith modules fully implemented!

## 🔧 Fix Migration Error & Start Fresh

### Step 1: Run Fresh Migration
```bash
cd /Volumes/Data/work/projects/the-last-massenger/the-last-messenger-laravel

# Option A: Using the helper script (interactive)
./reset-database.sh

# Option B: Direct command
php artisan migrate:fresh --seed
```

### Step 2: Verify Success
```bash
# Check migration status
php artisan migrate:status

# You should see 20+ migrations completed:
# - Users & authentication tables
# - Roles & permissions (Spatie)
# - Media library (Spatie)
# - Blog module (articles, categories, tags, series)
# - Hadith module (books, chapters, narrators, hadith)
# - Favorites & user interactions
```

### Step 3: Start the Server
```bash
php artisan serve

# Server will start at: http://localhost:8000
```

### Step 4: Test the API
```bash
# Health check
curl http://localhost:8000/api/health

# Get articles
curl http://localhost:8000/api/articles

# Get hadith books
curl http://localhost:8000/api/hadith/books
```

## 📁 What's Been Implemented

### ✅ Phase 1: Foundation (100%)
- Base repository pattern
- Service layer architecture
- API response helpers
- Custom exceptions
- Clean code structure

### ✅ Phase 2: Blog/Seerah Module (100%)
- **Models**: Article, Category, Tag, Series, Favorite, UserInteraction
- **Repositories**: ArticleRepository with caching
- **Services**: ArticleService with business logic
- **API Resources**: JSON transformers
- **Form Requests**: Validation
- **Controllers**: Thin API layer
- **15+ API endpoints** including:
  - CRUD operations
  - Search, filtering, pagination
  - Featured, popular, recent articles
  - Favorites system

### ✅ Phase 3: Hadith Module (100%)
- **Models**: Hadith, HadithBook, HadithChapter, HadithNarrator
- **Repositories**: Complex filtering and eager loading
- **Services**: Advanced search capabilities
- **API Resources**: Comprehensive transformers
- **9+ API endpoints** including:
  - Search by book, chapter, grade, narrator
  - Full-text search (Arabic & English)
  - Book and chapter browsing
  - Narrator chain relationships

### 🚧 Phase 4: Media Module (In Progress)
- 3 migrations created (programs, episodes, categories)
- Models, repositories, and controllers pending

## 📊 Database Schema

After migration, you'll have these tables:

**Core Tables:**
- users, password_reset_tokens, sessions
- personal_access_tokens (Sanctum auth)

**Permission System (Spatie):**
- roles, permissions, model_has_roles, model_has_permissions, role_has_permissions

**Media Library (Spatie):**
- media

**Blog Module:**
- articles (with generated columns for full-text search)
- categories
- tags, taggables (polymorphic)
- series
- favorites (polymorphic)
- user_interactions (polymorphic)

**Hadith Module:**
- hadith_books
- hadith_chapters
- hadith (with full-text search on Arabic & English)
- hadith_narrators
- hadith_narrator_chain (pivot table)

**Media Module (pending completion):**
- media_categories
- media_programs
- media_episodes

## 🔥 Key Features

### Multi-Language Support
All content supports 5 languages stored in JSON:
```json
{
  "ar": "العنوان بالعربية",
  "en": "Title in English",
  "fr": "Titre en français",
  "tr": "Türkçe başlık",
  "ur": "اردو میں عنوان"
}
```

### Full-Text Search
- Articles: Generated columns for Arabic title/excerpt
- Hadith: Arabic text + English translation
- MySQL MATCH...AGAINST for blazing fast search

### Caching Strategy
- Redis caching in repositories (15-30 min TTL)
- Cache invalidation on model updates
- Tag-based cache management

### Query Optimization
- Eager loading everywhere (no N+1 queries)
- Strategic indexes on all foreign keys
- Composite indexes for common queries
- Select specific columns

### RBAC (Role-Based Access Control)
6 predefined roles:
- super_admin (full access)
- admin (manage content)
- editor (edit articles)
- researcher (read-only advanced)
- user (basic authenticated)
- guest (public access)

20+ permissions for granular control

## 🚀 API Endpoints Available

### Authentication
```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/user
```

### Articles (Blog/Seerah)
```
GET    /api/articles              # List with pagination
GET    /api/articles/featured     # Featured articles
GET    /api/articles/popular      # Popular by views
GET    /api/articles/recent       # Recent published
GET    /api/articles/search       # Full-text search
GET    /api/articles/{slug}       # Single article
POST   /api/articles              # Create (auth required)
PUT    /api/articles/{id}         # Update (auth required)
DELETE /api/articles/{id}         # Delete (auth required)
POST   /api/articles/{id}/publish    # Publish (auth)
POST   /api/articles/{id}/unpublish  # Unpublish (auth)
POST   /api/articles/{id}/archive    # Archive (auth)
POST   /api/articles/{id}/favorite   # Add to favorites (auth)
```

### Categories
```
GET    /api/categories            # List all categories
GET    /api/categories/{slug}     # Single category with articles
```

### Hadith
```
GET    /api/hadith                # List with filters
GET    /api/hadith/{id}           # Single hadith
GET    /api/hadith/search         # Full-text search
GET    /api/hadith/books          # List all books
GET    /api/hadith/books/{slug}   # Single book with chapters
GET    /api/hadith/chapters/{id}  # Single chapter with hadith
GET    /api/hadith/sahih          # Only Sahih hadith
GET    /api/hadith/reliable       # Sahih + Hasan hadith
GET    /api/hadith/books/{bookId}/hadith/{number}  # Specific hadith
```

## 🧪 Testing the System

### Create a Test User
```bash
php artisan tinker
```

```php
// In tinker:
$user = App\Models\User::create([
    'name' => 'Test Admin',
    'email' => 'admin@test.com',
    'password' => Hash::make('password123'),
    'language' => 'ar'
]);

$user->assignRole('super_admin');

echo "✅ User created: " . $user->email;
```

### Test API with cURL
```bash
# Register a new user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John Doe","email":"john@example.com","password":"password123","password_confirmation":"password123","language":"en"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Copy the token from response, then:
export TOKEN="your_token_here"

# Get user info
curl http://localhost:8000/api/auth/user \
  -H "Authorization: Bearer $TOKEN"

# Create an article
curl -X POST http://localhost:8000/api/articles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": {"ar":"مقالة تجريبية","en":"Test Article"},
    "excerpt": {"ar":"مقتطف","en":"Excerpt"},
    "content": {"ar":"المحتوى","en":"Content"},
    "category_id": 1,
    "status": "published"
  }'
```

## 📈 Next Steps

### Complete Media Module (Phase 4)
- Finish models, repositories, services
- Complete API controllers
- Add video player integration

### Timeline Module (Phase 5)
- Create timeline_events table
- Implement chronological ordering
- Add geolocation support

### Genealogy Module (Phase 6)
- Create genealogy_persons table
- Build tree traversal logic
- Cache tree structure

### Quran Module (Phase 7)
- Import Quran data (114 surahs, 6236 verses)
- Add recitation support
- Multi-language translations

## 📚 Documentation

- **MIGRATION_FIX.md** - How we fixed JSON full-text search issue
- **MIGRATION_GUIDE.md** - Complete migration troubleshooting
- **IMPLEMENTATION_PROGRESS.md** - Detailed implementation status
- **README.md** - Project overview and architecture
- **be-tasks.md** - Detailed todo tasks

## 🐛 Common Issues

### "Table already exists"
**Solution**: Run `php artisan migrate:fresh`

### "Operation not permitted" (MySQL)
**Solution**: Check MySQL is running or switch to SQLite in `.env`:
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

### "JSON column supports indexing only via generated columns"
**Solution**: Already fixed! This was the migration error you encountered.

## 💡 Pro Tips

1. **Use SQLite for local development** (faster, simpler)
2. **Use MySQL for production** (better full-text search)
3. **Always run `composer dump-autoload`** after creating new classes
4. **Check `php artisan route:list`** to see all available routes
5. **Use Laravel Telescope** for debugging (optional, not installed yet)

---

**Ready to Go!** 🚀

Run `php artisan migrate:fresh --seed` and start building!

