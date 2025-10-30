# Developer Guide

**The Last Messenger** - Admin Panel Development Documentation
**Version**: 1.0.0
**Last Updated**: 2025-10-29

---

## 📚 Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Adding New Modules](#adding-new-modules)
3. [Component System](#component-system)
4. [Customization Guide](#customization-guide)
5. [API Reference](#api-reference)
6. [Testing](#testing)

---

## 🏗️ Architecture Overview

### Technology Stack

**Backend**:
- Laravel 12.x
- PHP 8.2+
- MySQL 8.0+
- Redis (caching)

**Frontend**:
- Blade Templates
- Alpine.js 3.x
- Tailwind CSS 4.x
- Chart.js 4.x
- Lucide Icons

**Authentication**:
- Laravel Sanctum (session-based)
- Spatie Laravel Permission (RBAC)

### Directory Structure

```
app/
├── Domain/                    # Business Logic Layer (DDD)
│   ├── Blog/                 # Articles domain
│   │   ├── Models/
│   │   │   └── Article.php
│   │   ├── Repositories/
│   │   │   ├── ArticleRepositoryInterface.php
│   │   │   └── ArticleRepository.php
│   │   ├── Services/
│   │   │   └── ArticleService.php
│   │   └── Enums/
│   │       └── ArticleStatus.php
│   ├── Hadith/               # Hadith domain
│   ├── Media/                # Media programs domain
│   ├── Seerah/               # Timeline events domain
│   ├── Sahaba/               # Companions domain
│   ├── Quran/                # Quran verses domain
│   ├── Genealogy/            # Family tree domain
│   └── Shared/               # Shared utilities
│       ├── Models/
│       ├── Repositories/
│       └── Services/
│           └── DashboardService.php
│
├── Http/                      # HTTP Layer
│   ├── Controllers/
│   │   └── Admin/            # Admin controllers
│   │       ├── DashboardController.php
│   │       ├── ArticleController.php
│   │       ├── HadithController.php
│   │       ├── MediaProgramController.php
│   │       ├── QuranController.php
│   │       ├── SahabiController.php
│   │       ├── SeerahEventController.php
│   │       ├── GenealogyController.php
│   │       ├── UserController.php
│   │       ├── RoleController.php
│   │       ├── ActivityLogController.php
│   │       └── MediaUploadController.php
│   │
│   ├── Middleware/
│   │   └── AdminMiddleware.php
│   │
│   ├── Requests/             # Form validation
│   │   └── Admin/
│   │       ├── StoreArticleRequest.php
│   │       ├── UpdateArticleRequest.php
│   │       └── ...
│   │
│   └── Resources/            # JSON transformers
│       ├── ArticleResource.php
│       ├── HadithResource.php
│       └── ...
│
├── Support/
│   └── Traits/
│       └── ApiResponse.php   # Consistent API responses
│
└── Providers/
    └── RepositoryServiceProvider.php  # DI bindings
```

### Design Patterns

#### 1. **Repository Pattern**

**Purpose**: Abstract data access logic from business logic.

**Structure**:
```php
// Interface
interface ArticleRepositoryInterface
{
    public function getAll(): Collection;
    public function findById(int $id): ?Article;
    public function create(array $data): Article;
    public function update(int $id, array $data): Article;
    public function delete(int $id): bool;
}

// Implementation
class ArticleRepository extends BaseRepository implements ArticleRepositoryInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->model = Article::class;
    }

    public function getAll(): Collection
    {
        return Cache::tags(['articles'])->remember(
            'articles:all',
            now()->addMinutes(15),
            fn() => $this->getBaseQuery()->get()
        );
    }

    protected function getBaseQuery()
    {
        // Eager load relationships to prevent N+1
        return $this->model::with(['category', 'tags', 'author']);
    }
}
```

#### 2. **Service Layer Pattern**

**Purpose**: Encapsulate complex business logic.

**Structure**:
```php
class ArticleService
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function create(array $data): Article
    {
        // Business logic here
        $data['slug'] = $this->generateSlug($data['title']['en']);

        // Create article
        $article = $this->articleRepository->create($data);

        // Attach relationships
        if (isset($data['tags'])) {
            $article->tags()->attach($data['tags']);
        }

        // Clear cache
        Cache::tags(['articles'])->flush();

        return $article;
    }

    private function generateSlug(string $title): string
    {
        return Str::slug($title);
    }
}
```

#### 3. **Dependency Injection**

**Binding in `RepositoryServiceProvider`**:
```php
public function register(): void
{
    $this->app->bind(
        ArticleRepositoryInterface::class,
        ArticleRepository::class
    );

    $this->app->bind(
        HadithRepositoryInterface::class,
        HadithRepository::class
    );

    // ... other bindings
}
```

**Usage in Controllers**:
```php
class ArticleController extends Controller
{
    public function __construct(
        protected ArticleService $articleService,
        protected ArticleRepositoryInterface $articleRepository
    ) {}

    public function index(Request $request)
    {
        $articles = $this->articleRepository->getAll();
        return view('admin.articles.index', compact('articles'));
    }
}
```

### Data Flow Diagram

```
┌─────────────┐
│   Browser   │
└──────┬──────┘
       │ HTTP Request
       ▼
┌─────────────────┐
│  Routes (web)   │
│  /su/portal/v1/ │
│     admin/*     │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│   Middleware    │
│ - auth          │
│ - admin         │
│ - throttle      │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│   Controller    │
│ (Thin layer)    │
│ - Validation    │
│ - Call Service  │
│ - Return View   │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│    Service      │
│ (Business Logic)│
│ - Validation    │
│ - Orchestration │
│ - Cache mgmt    │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│   Repository    │
│ (Data Access)   │
│ - Queries       │
│ - Caching       │
│ - Eager Loading │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│     Model       │
│ (Eloquent ORM)  │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│    Database     │
│    (MySQL)      │
└─────────────────┘
```

---

## 🆕 Adding New Modules

### Step-by-Step Guide

Let's create a complete "Books" module as an example.

#### Step 1: Create Migration

```bash
php artisan make:migration create_books_table
```

```php
// database/migrations/2025_10_29_create_books_table.php
public function up(): void
{
    Schema::create('books', function (Blueprint $table): void {
        $table->id();
        $table->json('title');  // Multi-language: {ar, en, fr, tr, ur}
        $table->json('description');
        $table->string('isbn')->unique()->nullable();
        $table->string('author_name');
        $table->integer('publication_year')->nullable();
        $table->integer('page_count')->nullable();
        $table->string('cover_image')->nullable();
        $table->enum('status', ['published', 'draft', 'archived'])->default('draft');
        $table->boolean('is_featured')->default(false);
        $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
        $table->timestamps();
        $table->softDeletes();

        // Indexes
        $table->index('status');
        $table->index('category_id');
        $table->index('is_featured');
        $table->index('created_at');

        // Full-text search (generated columns)
        $table->string('title_ar_generated')->storedAs("JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar'))");
        $table->string('title_en_generated')->storedAs("JSON_UNQUOTE(JSON_EXTRACT(title, '$.en'))");
        $table->fullText(['title_ar_generated', 'title_en_generated']);
    });
}
```

#### Step 2: Create Domain Layer

**Model**:
```php
// app/Domain/Library/Models/Book.php
declare(strict_types=1);

namespace App\Domain\Library\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Book extends Model
{
    use SoftDeletes, HasTranslations;

    protected $fillable = [
        'title',
        'description',
        'isbn',
        'author_name',
        'publication_year',
        'page_count',
        'cover_image',
        'status',
        'is_featured',
        'category_id',
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'is_featured' => 'boolean',
        'publication_year' => 'integer',
        'page_count' => 'integer',
    ];

    public $translatable = ['title', 'description'];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
```

**Repository Interface**:
```php
// app/Domain/Library/Repositories/BookRepositoryInterface.php
declare(strict_types=1);

namespace App\Domain\Library\Repositories;

use App\Domain\Library\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookRepositoryInterface
{
    public function getAll(): Collection;
    public function findById(int $id): ?Book;
    public function getPaginated(int $perPage = 15): LengthAwarePaginator;
    public function getPublished(): Collection;
    public function getFeatured(int $limit = 10): Collection;
    public function create(array $data): Book;
    public function update(int $id, array $data): Book;
    public function delete(int $id): bool;
    public function search(string $query, array $filters = []): Collection;
}
```

**Repository Implementation**:
```php
// app/Domain/Library/Repositories/BookRepository.php
declare(strict_types=1);

namespace App\Domain\Library\Repositories;

use App\Domain\Library\Models\Book;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class BookRepository extends BaseRepository implements BookRepositoryInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->model = Book::class;
    }

    protected function getBaseQuery()
    {
        return $this->model::with(['category']);
    }

    public function getAll(): Collection
    {
        return Cache::tags(['books'])->remember(
            'books:all',
            now()->addMinutes(15),
            fn() => $this->getBaseQuery()->get()
        );
    }

    public function findById(int $id): ?Book
    {
        return Cache::tags(['books'])->remember(
            "books:{$id}",
            now()->addMinutes(30),
            fn() => $this->getBaseQuery()->find($id)
        );
    }

    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getBaseQuery()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPublished(): Collection
    {
        return Cache::tags(['books'])->remember(
            'books:published',
            now()->addMinutes(15),
            fn() => $this->getBaseQuery()
                ->where('status', 'published')
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function getFeatured(int $limit = 10): Collection
    {
        return Cache::tags(['books'])->remember(
            "books:featured:{$limit}",
            now()->addMinutes(30),
            fn() => $this->getBaseQuery()
                ->where('is_featured', true)
                ->where('status', 'published')
                ->limit($limit)
                ->get()
        );
    }

    public function create(array $data): Book
    {
        $book = $this->model::create($data);

        // Clear cache
        Cache::tags(['books'])->flush();

        return $book->load('category');
    }

    public function update(int $id, array $data): Book
    {
        $book = $this->model::findOrFail($id);
        $book->update($data);

        // Clear cache
        Cache::tags(['books'])->flush();

        return $book->load('category');
    }

    public function delete(int $id): bool
    {
        $book = $this->model::findOrFail($id);
        $result = $book->delete();

        // Clear cache
        Cache::tags(['books'])->flush();

        return $result;
    }

    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->getBaseQuery();

        // Full-text search
        if (!empty($query)) {
            $queryBuilder->whereRaw(
                "MATCH(title_ar_generated, title_en_generated) AGAINST(? IN NATURAL LANGUAGE MODE)",
                [$query]
            );
        }

        // Apply filters
        if (isset($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        if (isset($filters['category_id'])) {
            $queryBuilder->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_featured'])) {
            $queryBuilder->where('is_featured', $filters['is_featured']);
        }

        return $queryBuilder->get();
    }
}
```

**Service**:
```php
// app/Domain/Library/Services/BookService.php
declare(strict_types=1);

namespace App\Domain\Library\Services;

use App\Domain\Library\Models\Book;
use App\Domain\Library\Repositories\BookRepositoryInterface;
use Illuminate\Support\Str;

class BookService
{
    public function __construct(
        protected BookRepositoryInterface $bookRepository
    ) {}

    public function create(array $data): Book
    {
        // Generate ISBN if not provided
        if (empty($data['isbn'])) {
            $data['isbn'] = $this->generateISBN();
        }

        return $this->bookRepository->create($data);
    }

    public function update(int $id, array $data): Book
    {
        return $this->bookRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->bookRepository->delete($id);
    }

    private function generateISBN(): string
    {
        // Simple ISBN-13 generator (for demo purposes)
        return '978-' . rand(1000000000, 9999999999);
    }
}
```

#### Step 3: Create HTTP Layer

**Form Requests**:
```php
// app/Http/Requests/Admin/StoreBookRequest.php
declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('books.create');
    }

    public function rules(): array
    {
        return [
            'title.ar' => 'required|string|max:255',
            'title.en' => 'required|string|max:255',
            'title.fr' => 'nullable|string|max:255',
            'title.tr' => 'nullable|string|max:255',
            'title.ur' => 'nullable|string|max:255',

            'description.ar' => 'required|string',
            'description.en' => 'required|string',
            'description.fr' => 'nullable|string',
            'description.tr' => 'nullable|string',
            'description.ur' => 'nullable|string',

            'isbn' => 'nullable|string|unique:books,isbn',
            'author_name' => 'required|string|max:255',
            'publication_year' => 'nullable|integer|min:1|max:' . date('Y'),
            'page_count' => 'nullable|integer|min:1',
            'cover_image' => 'nullable|string|max:500',
            'status' => 'required|in:published,draft,archived',
            'is_featured' => 'boolean',
            'category_id' => 'required|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.ar.required' => 'العنوان بالعربية مطلوب',
            'title.en.required' => 'العنوان بالإنجليزية مطلوب',
            'description.ar.required' => 'الوصف بالعربية مطلوب',
            'author_name.required' => 'اسم المؤلف مطلوب',
            'category_id.required' => 'التصنيف مطلوب',
        ];
    }
}
```

**Controller**:
```php
// app/Http/Controllers/Admin/BookController.php
declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Library\Models\Book;
use App\Domain\Library\Repositories\BookRepositoryInterface;
use App\Domain\Library\Services\BookService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BookService $bookService,
        protected BookRepositoryInterface $bookRepository
    ) {}

    /**
     * Display a listing of books
     *
     * GET /su/portal/v1/admin/books
     */
    public function index(Request $request)
    {
        // Return JSON if requested
        if ($request->wantsJson()) {
            $books = $this->bookRepository->getPaginated($request->integer('per_page', 15));
            return BookResource::collection($books);
        }

        // Return Blade view
        $statistics = $this->getStatistics();
        return view('admin.books.index', compact('statistics'));
    }

    /**
     * Show the form for creating a new book
     *
     * GET /su/portal/v1/admin/books/create
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin.books.create', compact('categories'));
    }

    /**
     * Store a newly created book
     *
     * POST /su/portal/v1/admin/books
     */
    public function store(StoreBookRequest $request)
    {
        $book = $this->bookService->create($request->validated());

        if ($request->wantsJson()) {
            return $this->createdResponse(new BookResource($book));
        }

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'تم إنشاء الكتاب بنجاح');
    }

    /**
     * Display the specified book
     *
     * GET /su/portal/v1/admin/books/{id}
     */
    public function show(Request $request, int $id)
    {
        $book = $this->bookRepository->findById($id);

        if (!$book) {
            return $request->wantsJson()
                ? $this->notFoundResponse('Book not found')
                : abort(404);
        }

        if ($request->wantsJson()) {
            return $this->successResponse(new BookResource($book));
        }

        return view('admin.books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified book
     *
     * GET /su/portal/v1/admin/books/{id}/edit
     */
    public function edit(int $id)
    {
        $book = $this->bookRepository->findById($id);

        if (!$book) {
            abort(404);
        }

        $categories = Category::all();
        return view('admin.books.edit', compact('book', 'categories'));
    }

    /**
     * Update the specified book
     *
     * PUT /su/portal/v1/admin/books/{id}
     */
    public function update(UpdateBookRequest $request, int $id)
    {
        $book = $this->bookService->update($id, $request->validated());

        if ($request->wantsJson()) {
            return $this->successResponse(new BookResource($book));
        }

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'تم تحديث الكتاب بنجاح');
    }

    /**
     * Remove the specified book
     *
     * DELETE /su/portal/v1/admin/books/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->bookService->delete($id);

        return $this->successResponse(null, 'Book deleted successfully');
    }

    /**
     * Search books
     *
     * GET /su/portal/v1/admin/books/search
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->string('q');
        $filters = $request->only(['status', 'category_id', 'is_featured']);

        $books = $this->bookRepository->search($query, $filters);

        return $this->successResponse(BookResource::collection($books));
    }

    /**
     * Bulk delete books
     *
     * POST /su/portal/v1/admin/books/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:books,id',
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            if ($this->bookService->delete($id)) {
                $count++;
            }
        }

        return $this->successResponse(
            ['deleted_count' => $count],
            "{$count} books deleted successfully"
        );
    }

    /**
     * Get statistics for dashboard
     */
    private function getStatistics(): array
    {
        return Cache::remember('books:statistics', now()->addMinutes(5), function () {
            $total = Book::count();
            $published = Book::where('status', 'published')->count();
            $draft = Book::where('status', 'draft')->count();
            $featured = Book::where('is_featured', true)->count();

            return compact('total', 'published', 'draft', 'featured');
        });
    }
}
```

**Resource**:
```php
// app/Http/Resources/BookResource.php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'isbn' => $this->isbn,
            'author_name' => $this->author_name,
            'publication_year' => $this->publication_year,
            'page_count' => $this->page_count,
            'cover_image' => $this->cover_image,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

#### Step 4: Register Repository Binding

```php
// app/Providers/RepositoryServiceProvider.php

public function register(): void
{
    // ... existing bindings

    $this->app->bind(
        \App\Domain\Library\Repositories\BookRepositoryInterface::class,
        \App\Domain\Library\Repositories\BookRepository::class
    );
}
```

#### Step 5: Add Routes

```php
// routes/admin.php

// Books Routes
Route::prefix('books')->group(function (): void {
    Route::get('/search', [BookController::class, 'search'])->name('admin.books.search');
    Route::post('/bulk-delete', [BookController::class, 'bulkDelete'])->name('admin.books.bulk-delete');
});

Route::resource('books', BookController::class)->names([
    'index' => 'admin.books.index',
    'create' => 'admin.books.create',
    'store' => 'admin.books.store',
    'show' => 'admin.books.show',
    'edit' => 'admin.books.edit',
    'update' => 'admin.books.update',
    'destroy' => 'admin.books.destroy',
]);
```

#### Step 6: Create Views

**Index View**:
```blade
{{-- resources/views/admin/books/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'إدارة الكتب')
@section('page-title', 'إدارة الكتب')

@section('content')
<div x-data="booksData()" x-init="init()">
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Total Books Card --}}
        <div class="p-6 rounded-2xl" :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-bold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إجمالي الكتب</p>
                    <h3 class="text-3xl font-black" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="stats.total"></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                    <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Published Books Card --}}
        <div class="p-6 rounded-2xl" :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-bold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">منشور</p>
                    <h3 class="text-3xl font-black" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="stats.published"></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Draft Books Card --}}
        <div class="p-6 rounded-2xl" :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-bold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مسودة</p>
                    <h3 class="text-3xl font-black" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="stats.draft"></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-yellow-500 to-yellow-600 flex items-center justify-center">
                    <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>

        {{-- Featured Books Card --}}
        <div class="p-6 rounded-2xl" :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-bold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">مميز</p>
                    <h3 class="text-3xl font-black" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="stats.featured"></h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                    <i data-lucide="star" class="w-6 h-6 text-white"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="p-6 rounded-2xl mb-6" :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">
        <div class="flex flex-col md:flex-row gap-4">
            {{-- Search Input --}}
            <div class="flex-1">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                    <input
                        type="text"
                        x-model="searchQuery"
                        @input.debounce.500ms="loadBooks()"
                        placeholder="بحث في الكتب..."
                        class="w-full pr-12 pl-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all"
                        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-gray-50 border-gray-200 text-gray-900'"
                    />
                </div>
            </div>

            {{-- Status Filter --}}
            <select
                x-model="filters.status"
                @change="loadBooks()"
                class="px-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500"
                :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-gray-50 border-gray-200 text-gray-900'"
            >
                <option value="">كل الحالات</option>
                <option value="published">منشور</option>
                <option value="draft">مسودة</option>
                <option value="archived">أرشيف</option>
            </select>

            {{-- New Book Button --}}
            <a href="{{ route('admin.books.create') }}" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold rounded-xl hover:shadow-lg transition-all flex items-center gap-2 whitespace-nowrap">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>كتاب جديد</span>
            </a>
        </div>
    </div>

    {{-- Books Table --}}
    <div class="rounded-2xl overflow-hidden" :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">
        {{-- Loading State --}}
        <div x-show="loading" class="p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2" :class="darkMode ? 'border-white' : 'border-blue-500'"></div>
            <p class="mt-4 font-bold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">جاري التحميل...</p>
        </div>

        {{-- Table --}}
        <div x-show="!loading" class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b" :class="darkMode ? 'border-gray-700 bg-gray-700/50' : 'border-gray-200 bg-gray-50'">
                        <th class="px-6 py-4 text-right text-sm font-bold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                        <th class="px-6 py-4 text-right text-sm font-bold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">العنوان</th>
                        <th class="px-6 py-4 text-right text-sm font-bold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المؤلف</th>
                        <th class="px-6 py-4 text-right text-sm font-bold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الحالة</th>
                        <th class="px-6 py-4 text-right text-sm font-bold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">التاريخ</th>
                        <th class="px-6 py-4 text-center text-sm font-bold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(book, index) in books" :key="book.id">
                        <tr class="border-b" :class="darkMode ? 'border-gray-700 hover:bg-gray-700/30' : 'border-gray-100 hover:bg-gray-50'">
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="book.id"></td>
                            <td class="px-6 py-4">
                                <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="book.title.ar"></p>
                                <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="book.title.en"></p>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="book.author_name"></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-bold"
                                      :class="{
                                          'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400': book.status === 'published',
                                          'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400': book.status === 'draft',
                                          'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400': book.status === 'archived'
                                      }"
                                      x-text="book.status === 'published' ? 'منشور' : book.status === 'draft' ? 'مسودة' : 'أرشيف'">
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="formatDate(book.created_at)"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <a :href="`{{ route('admin.books.edit', '') }}/${book.id}`"
                                       class="p-2 rounded-lg transition-colors"
                                       :class="darkMode ? 'hover:bg-blue-500/20 text-blue-400' : 'hover:bg-blue-50 text-blue-600'">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    <button @click="deleteBook(book.id)"
                                            class="p-2 rounded-lg transition-colors"
                                            :class="darkMode ? 'hover:bg-red-500/20 text-red-400' : 'hover:bg-red-50 text-red-600'">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty State --}}
                    <tr x-show="books.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
                                    <i data-lucide="book-open" class="w-8 h-8" :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                                </div>
                                <p class="font-bold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا توجد كتب</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function booksData() {
    return {
        books: [],
        stats: {
            total: {{ $statistics['total'] }},
            published: {{ $statistics['published'] }},
            draft: {{ $statistics['draft'] }},
            featured: {{ $statistics['featured'] }}
        },
        loading: true,
        searchQuery: '',
        filters: {
            status: ''
        },
        darkMode: localStorage.getItem('darkMode') !== 'false',

        async init() {
            await this.loadBooks();
            this.$nextTick(() => lucide.createIcons());
        },

        async loadBooks() {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    q: this.searchQuery,
                    ...this.filters
                });

                const response = await fetch(`/su/portal/v1/admin/books?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                this.books = data.data;
            } catch (error) {
                console.error('Error loading books:', error);
                this.showToast('حدث خطأ أثناء تحميل الكتب', 'error');
            } finally {
                this.loading = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        async deleteBook(id) {
            if (!confirm('هل أنت متأكد من حذف هذا الكتاب?')) return;

            try {
                const response = await fetch(`/su/portal/v1/admin/books/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.showToast('تم حذف الكتاب بنجاح', 'success');
                    await this.loadBooks();
                }
            } catch (error) {
                console.error('Error deleting book:', error);
                this.showToast('حدث خطأ أثناء حذف الكتاب', 'error');
            }
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleDateString('ar-EG');
        },

        showToast(message, type = 'success') {
            // Implement toast notification
            alert(message);
        }
    };
}
</script>
@endpush
```

**Create/Edit Forms**: Similar pattern to existing modules.

---

## 🎨 Component System

### Built-in Blade Components

The admin panel doesn't use separate Blade component files but follows consistent patterns. Here's how to create reusable components:

### Creating a Stats Card Component

**Define the component**:
```blade
{{-- resources/views/components/admin/stat-card.blade.php --}}
@props([
    'title',
    'value',
    'icon',
    'color' => 'blue',
    'badge' => null
])

<div {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-2xl transition-all duration-300 hover:scale-105 hover:shadow-2xl']) }}
     :class="darkMode ? 'bg-gray-800/50 border border-gray-700/50' : 'bg-white border border-gray-200 shadow-lg'">

    {{-- Gradient Accent --}}
    <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-{{ $color }}-500/20 to-{{ $color }}-600/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:scale-150 transition-transform duration-500"></div>

    <div class="relative z-10 p-6">
        <div class="flex items-center justify-between mb-4">
            {{-- Icon --}}
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-{{ $color }}-500 to-{{ $color }}-600 flex items-center justify-center shadow-lg shadow-{{ $color }}-500/50">
                <i data-lucide="{{ $icon }}" class="w-7 h-7 text-white"></i>
            </div>

            {{-- Badge --}}
            @if($badge)
                <span class="px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400">
                    {{ $badge }}
                </span>
            @endif
        </div>

        <h3 class="text-3xl font-black mb-2 bg-gradient-to-r from-{{ $color }}-600 to-{{ $color }}-800 dark:from-{{ $color }}-400 dark:to-{{ $color }}-200 bg-clip-text text-transparent">
            {{ $value }}
        </h3>
        <p class="font-bold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $title }}</p>

        {{ $slot }}
    </div>
</div>
```

**Usage**:
```blade
<x-admin.stat-card
    title="المقالات"
    value="152"
    icon="file-text"
    color="blue"
    badge="+12%"
>
    <div class="mt-4 flex items-center text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        <i data-lucide="trending-up" class="w-4 h-4 ml-1 text-emerald-500"></i>
        <span>نمو هذا الشهر</span>
    </div>
</x-admin.stat-card>
```

### Creating a Search Bar Component

```blade
{{-- resources/views/components/admin/search-bar.blade.php --}}
@props([
    'placeholder' => 'بحث...',
    'xModel' => 'searchQuery'
])

<div class="relative">
    <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
    <input
        type="text"
        x-model="{{ $xModel }}"
        @input.debounce.500ms="loadData()"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full pr-12 pl-4 py-3 rounded-xl focus:ring-2 focus:ring-blue-500 transition-all']) }}
        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : 'bg-gray-50 border-gray-200 text-gray-900'"
    />
</div>
```

**Usage**:
```blade
<x-admin.search-bar placeholder="بحث في المقالات..." x-model="articleSearch" />
```

---

## 🎨 Customization Guide

### Changing Colors

**Gradient Colors**:
The admin panel uses a blue-to-green gradient scheme. To change:

1. **Update Tailwind Config** (if using custom build):
```javascript
// tailwind.config.js
module.exports = {
    theme: {
        extend: {
            colors: {
                // Your custom colors
                'brand-primary': '#YOUR_COLOR',
                'brand-secondary': '#YOUR_COLOR',
            }
        }
    }
}
```

2. **Replace in Blade Files**:
```blade
{{-- Find: --}}
from-[#0056b3] to-[#218838]

{{-- Replace with: --}}
from-[#YOUR_COLOR] to-[#YOUR_COLOR]
```

**Stat Card Colors**:
Edit in each index view:
```blade
{{-- Articles (Blue) --}}
from-blue-500 to-blue-600

{{-- Hadith (Green) --}}
from-emerald-500 to-emerald-600

{{-- Media (Purple) --}}
from-purple-500 to-purple-600

{{-- Users (Cyan) --}}
from-[#64CCC5] to-[#4BA5A3]
```

### Adding Animations

**Example: Pulse Animation**:
```blade
{{-- Add to any element --}}
<div class="animate-pulse">
    {{-- Content --}}
</div>
```

**Custom Animation**:
```html
<style>
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.slide-in {
    animation: slideIn 0.3s ease-out;
}
</style>

<div class="slide-in">
    {{-- Content --}}
</div>
```

### Extending Functionality

**Adding a Quick Action Button**:
```blade
{{-- In dashboard.blade.php, Quick Actions section --}}
<a href="{{ route('admin.books.create') }}"
   class="block p-4 rounded-xl transition-all duration-300 group border"
   :class="darkMode ? 'bg-orange-500/10 border-orange-500/30 hover:bg-orange-500/20' : 'bg-orange-50 border-orange-200 hover:bg-orange-100'">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center group-hover:scale-110 transition-transform shadow-md">
            <i data-lucide="book-open" class="w-5 h-5 text-white"></i>
        </div>
        <div>
            <p class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">كتاب جديد</p>
            <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">إضافة كتاب جديد</p>
        </div>
    </div>
</a>
```

---

## 📡 API Reference

### Authentication Endpoints

#### Login
```http
POST /su/portal/v1/admin/login
Content-Type: application/x-www-form-urlencoded

email=admin@example.com&password=Admin123!&remember=1
```

**Response**:
```http
302 Redirect
Location: /su/portal/v1/admin/dashboard
Set-Cookie: laravel_session=...
```

#### Logout
```http
POST /su/portal/v1/admin/logout
```

**Response**:
```http
302 Redirect
Location: /su/portal/v1/admin/login
```

### Dashboard Endpoints

#### Get Statistics
```http
GET /su/portal/v1/admin/dashboard/statistics
Accept: application/json
```

**Response**:
```json
{
  "success": true,
  "message": "Dashboard statistics retrieved successfully",
  "data": {
    "articles": {
      "total": 152,
      "published": 120,
      "draft": 25,
      "archived": 7
    },
    "hadith": {
      "total": 347,
      "sahih": 300,
      "hasan": 40,
      "daif": 7
    },
    "media_programs": {
      "total": 89,
      "published": 75
    },
    "users": {
      "total": 1247,
      "verified": 1100
    }
  }
}
```

#### Get Recent Activity
```http
GET /su/portal/v1/admin/dashboard/activity?limit=10
Accept: application/json
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "name": "Admin User"
      },
      "action": "create",
      "model_type": "Article",
      "model_id": 45,
      "title": "Created article: New Seerah Article",
      "time": "2 hours ago",
      "icon": "file-plus",
      "color": "bg-blue-500"
    }
  ]
}
```

### Article Endpoints

#### List Articles
```http
GET /su/portal/v1/admin/articles?page=1&per_page=15&status=published
Accept: application/json
```

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "title": {
        "ar": "عنوان المقال",
        "en": "Article Title"
      },
      "status": "published",
      "category": {
        "id": 1,
        "name": {"ar": "السيرة"}
      },
      "views": 1250,
      "created_at": "2025-10-29T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 152,
    "per_page": 15,
    "last_page": 11
  }
}
```

#### Create Article
```http
POST /su/portal/v1/admin/articles
Content-Type: application/json
Accept: application/json

{
  "title": {
    "ar": "عنوان المقال",
    "en": "Article Title",
    "fr": "Titre de l'article",
    "tr": "Makale Başlığı",
    "ur": "مضمون کا عنوان"
  },
  "content": {
    "ar": "محتوى المقال...",
    "en": "Article content..."
  },
  "excerpt": {
    "ar": "مقتطف",
    "en": "Excerpt"
  },
  "status": "published",
  "category_id": 1,
  "series_id": null,
  "tags": [1, 2, 3]
}
```

**Response**:
```json
{
  "success": true,
  "message": "Resource created successfully",
  "data": {
    "id": 153,
    "title": {...},
    "status": "published",
    "created_at": "2025-10-29T12:00:00Z"
  }
}
```

#### Update Article
```http
PUT /su/portal/v1/admin/articles/153
Content-Type: application/json
Accept: application/json

{
  "title": {
    "ar": "عنوان محدث"
  },
  "status": "published"
}
```

#### Delete Article
```http
DELETE /su/portal/v1/admin/articles/153
Accept: application/json
```

**Response**:
```json
{
  "success": true,
  "message": "Resource deleted successfully"
}
```

#### Search Articles
```http
GET /su/portal/v1/admin/articles/search?q=seerah&status=published
Accept: application/json
```

#### Bulk Delete
```http
POST /su/portal/v1/admin/articles/bulk-delete
Content-Type: application/json

{
  "ids": [1, 2, 3, 4, 5]
}
```

**Response**:
```json
{
  "success": true,
  "message": "5 items deleted successfully",
  "data": {
    "deleted_count": 5
  }
}
```

### Error Responses

**Validation Error (422)**:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "title.ar": ["The Arabic title field is required."],
    "category_id": ["The selected category is invalid."]
  }
}
```

**Not Found (404)**:
```json
{
  "success": false,
  "message": "Resource not found"
}
```

**Unauthorized (403)**:
```json
{
  "success": false,
  "message": "Unauthorized access"
}
```

**Server Error (500)**:
```json
{
  "success": false,
  "message": "Internal server error"
}
```

---

## 🧪 Testing

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage

# Run single test file
php artisan test tests/Feature/Admin/ArticleControllerTest.php

# Run single test method
php artisan test --filter test_can_create_article
```

### Writing Feature Tests

**Example Test**:
```php
// tests/Feature/Admin/BookControllerTest.php
declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Library\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_view_books_index(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.books.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.books.index');
        $response->assertViewHas('statistics');
    }

    public function test_admin_can_create_book(): void
    {
        $this->actingAs($this->admin);

        $bookData = [
            'title' => [
                'ar' => 'كتاب تجريبي',
                'en' => 'Test Book',
            ],
            'description' => [
                'ar' => 'وصف تجريبي',
                'en' => 'Test description',
            ],
            'author_name' => 'Test Author',
            'status' => 'published',
            'category_id' => Category::factory()->create()->id,
        ];

        $response = $this->postJson(route('admin.books.store'), $bookData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'author_name' => 'Test Author',
            'status' => 'published',
        ]);
    }

    public function test_admin_can_update_book(): void
    {
        $this->actingAs($this->admin);

        $book = Book::factory()->create();

        $updateData = [
            'author_name' => 'Updated Author',
            'status' => 'draft',
        ];

        $response = $this->putJson(route('admin.books.update', $book->id), $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'author_name' => 'Updated Author',
            'status' => 'draft',
        ]);
    }

    public function test_admin_can_delete_book(): void
    {
        $this->actingAs($this->admin);

        $book = Book::factory()->create();

        $response = $this->deleteJson(route('admin.books.destroy', $book->id));

        $response->assertStatus(200);
        $this->assertSoftDeleted('books', ['id' => $book->id]);
    }

    public function test_non_admin_cannot_access_books(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user);

        $response = $this->get(route('admin.books.index'));

        $response->assertStatus(403);
    }

    public function test_can_search_books(): void
    {
        $this->actingAs($this->admin);

        Book::factory()->create([
            'title' => ['ar' => 'كتاب السيرة', 'en' => 'Biography Book'],
        ]);

        Book::factory()->create([
            'title' => ['ar' => 'كتاب الفقه', 'en' => 'Fiqh Book'],
        ]);

        $response = $this->getJson(route('admin.books.search', ['q' => 'السيرة']));

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_bulk_delete_books(): void
    {
        $this->actingAs($this->admin);

        $books = Book::factory()->count(3)->create();
        $ids = $books->pluck('id')->toArray();

        $response = $this->postJson(route('admin.books.bulk-delete'), [
            'ids' => $ids,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => ['deleted_count' => 3],
        ]);

        foreach ($ids as $id) {
            $this->assertSoftDeleted('books', ['id' => $id]);
        }
    }
}
```

### Creating Factories

```php
// database/factories/BookFactory.php
declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Library\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => [
                'ar' => $this->faker->sentence(3),
                'en' => $this->faker->sentence(3),
                'fr' => $this->faker->sentence(3),
                'tr' => $this->faker->sentence(3),
                'ur' => $this->faker->sentence(3),
            ],
            'description' => [
                'ar' => $this->faker->paragraph(),
                'en' => $this->faker->paragraph(),
                'fr' => $this->faker->paragraph(),
                'tr' => $this->faker->paragraph(),
                'ur' => $this->faker->paragraph(),
            ],
            'isbn' => $this->faker->unique()->isbn13(),
            'author_name' => $this->faker->name(),
            'publication_year' => $this->faker->numberBetween(1900, 2025),
            'page_count' => $this->faker->numberBetween(50, 1000),
            'cover_image' => $this->faker->imageUrl(500, 700, 'books'),
            'status' => $this->faker->randomElement(['published', 'draft', 'archived']),
            'is_featured' => $this->faker->boolean(20),
            'category_id' => Category::factory(),
        ];
    }

    public function published(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function featured(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => 'published',
        ]);
    }
}
```

### Test Coverage Goals

- **Controllers**: 100% (all methods tested)
- **Services**: 90%+ (all business logic)
- **Repositories**: 80%+ (main methods)
- **Overall**: 80%+

---

## 📝 Code Style Guidelines

### PHP Code Standards

**PSR-12 Compliance**:
```bash
# Check code style
./vendor/bin/pint --test

# Fix code style
./vendor/bin/pint
```

**Strict Types**:
```php
<?php

declare(strict_types=1);

namespace App\Domain\Library\Models;
```

**Type Hints**:
```php
// Always use type hints
public function findById(int $id): ?Book
{
    return $this->model::find($id);
}

// Array types
public function getAll(): array
{
    return [];
}

// Collections
public function getPublished(): Collection
{
    return collect([]);
}
```

**PHPDoc Comments**:
```php
/**
 * Create a new book
 *
 * @param array<string, mixed> $data Book data
 * @return Book Created book instance
 * @throws \Illuminate\Validation\ValidationException
 */
public function create(array $data): Book
{
    // Implementation
}
```

### Blade Templates Style

**Consistent Indentation**:
```blade
<div class="container">
    <div class="row">
        <div class="col">
            {{-- Content --}}
        </div>
    </div>
</div>
```

**Alpine.js Directives**:
```blade
{{-- Use x- prefix for Alpine --}}
<div x-data="componentData()" x-init="init()">
    <button @click="handleClick">Click</button>
    <p x-text="message"></p>
    <div x-show="isVisible">Content</div>
</div>
```

**Conditional Classes**:
```blade
{{-- Use :class for dynamic classes --}}
<div :class="darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'">
    Content
</div>
```

### JavaScript Style

**Modern ES6+**:
```javascript
// Use async/await
async function loadData() {
    try {
        const response = await fetch(url);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Error:', error);
    }
}

// Arrow functions
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG');

// Destructuring
const { id, name, status } = book;

// Template literals
const url = `/admin/books/${id}/edit`;
```

---

## 🔍 Debugging Tips

### Laravel Debugbar

```bash
# Install (development only)
composer require barryvdh/laravel-debugbar --dev
```

**Features**:
- SQL query profiler
- Request/Response inspection
- Route information
- View rendering times
- Cache hits/misses

### Browser Console

```javascript
// In browser console (F12)

// Check Alpine.js data
$el.__x.$data

// Trigger Alpine method
$el.__x.loadBooks()

// Check localStorage
localStorage.getItem('darkMode')

// Clear all cache
localStorage.clear()
```

### Common Issues

**Issue**: N+1 Queries

**Solution**: Use eager loading
```php
// Bad
$books = Book::all();
foreach ($books as $book) {
    echo $book->category->name; // N+1 query
}

// Good
$books = Book::with('category')->get();
foreach ($books as $book) {
    echo $book->category->name; // No extra queries
}
```

**Issue**: Cache not updating

**Solution**: Clear cache tags
```php
Cache::tags(['books'])->flush();
```

---

## 📚 Additional Resources

### Laravel Documentation
- [Laravel 12.x Docs](https://laravel.com/docs/12.x)
- [Eloquent ORM](https://laravel.com/docs/12.x/eloquent)
- [Blade Templates](https://laravel.com/docs/12.x/blade)

### Frontend Documentation
- [Alpine.js](https://alpinejs.dev)
- [Tailwind CSS](https://tailwindcss.com)
- [Chart.js](https://www.chartjs.org)
- [Lucide Icons](https://lucide.dev)

### Packages Used
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Spatie Laravel Translatable](https://github.com/spatie/laravel-translatable)
- [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum)

---

## 🎉 Conclusion

This guide covers the essential aspects of developing and extending the admin panel. For more specific questions:

- 📧 Email: dev@thelastmessenger.com
- 💬 GitHub Issues
- 📖 Project Wiki

**Happy Coding! 🚀**

---

**Document Version**: 1.0.0
**Last Updated**: 2025-10-29
**Maintained By**: The Last Messenger Development Team
