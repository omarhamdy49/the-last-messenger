# Article Module - Detailed Implementation Guide
## The Last Messenger Admin Dashboard - Article Management System

**Status:** Ready for Parallel Implementation
**Version:** 1.0
**Date:** 2025-10-31
**Team Size:** 4-6 developers (parallel tasks)

---

## 📋 Overview

This document provides a complete implementation guide for the Article module of the Admin Dashboard. The Article module is the foundation of the Seerah/Blog section and includes:

- Article CRUD operations
- Multi-language support (AR/EN/FR/TR/UR)
- Featured image management
- SEO metadata
- Publishing workflow (draft → review → published)
- Author and category management
- Article statistics
- Search and filtering

---

## 🎯 Implementation Goals

**Phase 1 Deliverables:**
- ✅ Article creation/editing/deletion
- ✅ Featured image upload with resizing
- ✅ Multi-language support
- ✅ Publishing status management
- ✅ Author assignment
- ✅ Category assignment
- ✅ Article search/filter
- ✅ Activity logging

**Phase 2 Extensions:**
- SEO metadata editor
- Publishing workflow
- Revision history
- Content localization interface
- Article statistics

---

## 📦 Deliverables by Task

```
ARTICLE MODULE (Implementation Parallelizable)
├── TASK A1: Database Schema & Migrations
├── TASK A2: Models & Relationships
├── TASK A3: Controllers & Routes
├── TASK A4: Form Components (Livewire)
├── TASK A5: WYSIWYG Editor Integration
├── TASK A6: Image Upload Manager
├── TASK A7: SEO Metadata System
└── TASK A8: Publishing Workflow
```

**Recommended Parallel Execution:**
- Team 1 → TASK A1 + A2 (Database & Models)
- Team 2 → TASK A3 (Controllers & Routes)
- Team 3 → TASK A4 (Form Components)
- Team 4 → TASK A5 (WYSIWYG Editor)
- Team 5 → TASK A6 (Image Upload)
- Team 6 → TASK A7 + A8 (SEO & Workflow)

---

## TASK A1: DATABASE SCHEMA & MIGRATIONS

**Duration:** 3-4 days | **Team:** 1 developer | **Dependencies:** MySQL setup

### Subtask A1.1: Create Articles Table Migration

**File:** `database/migrations/2025_10_31_000001_create_articles_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('title'); // Will be translated via translatable
            $table->string('slug')->unique();
            $table->longText('content'); // Main article content
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable(); // Path to image

            // Relationships
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');

            // Status & Publishing
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null');

            // SEO
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_keywords')->nullable();
            $table->string('og_image')->nullable();

            // Meta
            $table->integer('view_count')->default(0);
            $table->integer('order')->default(0); // For manual ordering
            $table->boolean('featured')->default(false);
            $table->boolean('is_pinned')->default(false);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('author_id');
            $table->index('published_at');
            $table->index('featured');
            $table->fullText(['title', 'content', 'excerpt']); // For search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

**Checklist:**
- [ ] File created in migrations directory
- [ ] All fields properly typed
- [ ] Foreign keys configured
- [ ] Indexes added for performance
- [ ] Soft deletes implemented
- [ ] Full-text search index added

---

### Subtask A1.2: Create Article_Translations Table

**File:** `database/migrations/2025_10_31_000002_create_article_translations_table.php`

**Why:** Using Spatie Translatable package for multi-language support

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->string('locale')->index();

            // Translatable fields
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            $table->timestamps();

            // Composite index for performance
            $table->unique(['article_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_translations');
    }
};
```

**Alternative Approach:** If not using Spatie Translatable:
- Add locale field to articles table
- Query with locale filter
- Less elegant but works

**Recommended:** Use Spatie Translatable (industry standard)

---

### Subtask A1.3: Create Article_Categories (Pivot) Table

**File:** `database/migrations/2025_10_31_000003_create_article_category_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->integer('order')->default(0); // For category ordering

            $table->timestamps();

            // Ensure no duplicates
            $table->unique(['article_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_category');
    }
};
```

---

### Subtask A1.4: Create Categories Table (If Not Exists)

**File:** `database/migrations/2025_10_31_000004_create_categories_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Lucide icon name
            $table->string('color')->nullable(); // Hex color for UI
            $table->enum('module', ['seerah', 'hadith', 'quran', 'genealogy', 'media'])->default('seerah');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            $table->index('module');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

---

### Subtask A1.5: Create Authors Table (If Not Exists)

**File:** `database/migrations/2025_10_31_000005_create_authors_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->string('image')->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable(); // Twitter, LinkedIn, etc.

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
```

---

### Subtask A1.6: Run Migrations & Verify

**Steps:**
```bash
# Run migrations
php artisan migrate

# Verify tables created
php artisan migrate:status

# Check table structure
php artisan tinker
# > DB::table('articles')->getConnection()->getSchemaBuilder()->getColumnListing('articles')
```

**Checklist:**
- [ ] All migrations run successfully
- [ ] No foreign key constraint errors
- [ ] All indexes created
- [ ] Soft deletes working
- [ ] Full-text search index active

---

## TASK A2: MODELS & RELATIONSHIPS

**Duration:** 2-3 days | **Team:** 1 developer | **Dependencies:** TASK A1

### Subtask A2.1: Create Article Model

**File:** `app/Models/Article.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class Article extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'author_id',
        'status',
        'published_at',
        'reviewed_at',
        'reviewer_id',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'view_count',
        'order',
        'featured',
        'is_pinned',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'featured' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    // Translatable fields (Spatie)
    public array $translatable = [
        'title',
        'content',
        'excerpt',
        'seo_title',
        'seo_description',
    ];

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class)
            ->withPivot('order')
            ->orderByPivot('order');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeReview($query)
    {
        return $query->where('status', 'review');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeSearch($query, $search)
    {
        return $query->whereRaw('MATCH(title, content, excerpt) AGAINST(? IN BOOLEAN MODE)', [$search]);
    }

    // Methods
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function sendToReview()
    {
        $this->update(['status' => 'review']);
    }

    public function approve(User $reviewer)
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'reviewer_id' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(User $reviewer)
    {
        $this->update([
            'status' => 'draft',
            'reviewer_id' => $reviewer->id,
        ]);
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'review' => 'yellow',
            'published' => 'green',
            'archived' => 'red',
        };
    }
}
```

**Checklist:**
- [ ] Model created in correct namespace
- [ ] All fillable fields configured
- [ ] Casts configured correctly
- [ ] Translatable fields set up
- [ ] All relationships defined
- [ ] Scopes implemented
- [ ] Helper methods added

---

### Subtask A2.2: Create Category Model

**File:** `app/Models/Category.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'module',
        'parent_id',
    ];

    // Relationships
    public function articles()
    {
        return $this->belongsToMany(Article::class)
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Scopes
    public function scopeForModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeParents($query)
    {
        return $query->whereNull('parent_id');
    }

    // Methods
    public function getFullPath()
    {
        $path = [$this->name];
        if ($this->parent) {
            $path = array_merge($this->parent->getFullPath(), $path);
        }
        return implode(' > ', $path);
    }
}
```

---

### Subtask A2.3: Create User Author Relationship

**Update:** `app/Models/User.php`

```php
public function articles()
{
    return $this->hasMany(Article::class, 'author_id');
}

public function authorProfile()
{
    return $this->hasOne(Author::class);
}

public function isEditor(): bool
{
    return $this->role === 'editor' || $this->role === 'admin';
}

public function canPublishArticles(): bool
{
    return $this->role === 'admin';
}
```

---

### Subtask A2.4: Create Factories & Seeders

**File:** `database/factories/ArticleFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'slug' => $this->faker->unique()->slug(),
            'content' => $this->faker->paragraphs(5, true),
            'excerpt' => $this->faker->paragraph(),
            'author_id' => User::factory(),
            'status' => $this->faker->randomElement(['draft', 'published']),
            'published_at' => $this->faker->optional()->dateTime(),
            'featured' => $this->faker->boolean(10),
            'view_count' => $this->faker->numberBetween(0, 1000),
        ];
    }

    public function published(): self
    {
        return $this->state([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function draft(): self
    {
        return $this->state(['status' => 'draft']);
    }
}
```

**File:** `database/seeders/ArticleSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample articles
        Article::factory()
            ->count(10)
            ->published()
            ->create([
                'author_id' => User::where('role', 'editor')->first()->id ?? User::first()->id,
            ]);

        Article::factory()
            ->count(5)
            ->draft()
            ->create();
    }
}
```

---

## TASK A3: CONTROLLERS & ROUTES

**Duration:** 3-4 days | **Team:** 2 developers | **Dependencies:** TASK A2

### Subtask A3.1: Create ArticleController

**File:** `app/Http/Controllers/Admin/ArticleController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:edit articles')->except(['index', 'show']);
    }

    // List all articles
    public function index(Request $request)
    {
        $query = Article::with(['author', 'categories'])
            ->orderByDesc('created_at');

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->author_id) {
            $query->where('author_id', $request->author_id);
        }

        if ($request->category_id) {
            $query->whereHas('categories', function ($q) {
                $q->where('category_id', request('category_id'));
            });
        }

        if ($request->search) {
            $query->where('title', 'like', "%{$request->search}%")
                ->orWhereRaw('MATCH(title, content) AGAINST(? IN BOOLEAN MODE)', [$request->search]);
        }

        $articles = $query->paginate(15);

        return view('admin.articles.index', [
            'articles' => $articles,
            'statuses' => ['draft', 'review', 'published', 'archived'],
            'categories' => Category::forModule('seerah')->get(),
        ]);
    }

    // Show article detail
    public function show(Article $article)
    {
        return view('admin.articles.show', ['article' => $article]);
    }

    // Create form
    public function create()
    {
        return view('admin.articles.create', [
            'article' => new Article(),
            'categories' => Category::forModule('seerah')->get(),
        ]);
    }

    // Store new article
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|max:2048',
            'author_id' => 'required|exists:users,id',
            'categories' => 'array|exists:categories,id',
            'status' => 'in:draft,review,published',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:160',
        ]);

        // Handle featured image
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('articles', 'public');
            $validated['featured_image'] = $path;
        }

        // Generate slug
        $validated['slug'] = Str::slug($request->title) . '-' . uniqid();

        $article = Article::create($validated);

        // Attach categories
        if ($request->categories) {
            $article->categories()->attach($request->categories);
        }

        // Log activity
        activity('create')
            ->performedOn($article)
            ->withProperties(['model' => 'Article'])
            ->log('Article created');

        return redirect()
            ->route('admin.articles.show', $article)
            ->with('success', 'Article created successfully');
    }

    // Edit form
    public function edit(Article $article)
    {
        return view('admin.articles.edit', [
            'article' => $article,
            'categories' => Category::forModule('seerah')->get(),
        ]);
    }

    // Update article
    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|max:2048',
            'categories' => 'array|exists:categories,id',
            'status' => 'in:draft,review,published',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:160',
        ]);

        // Handle featured image
        if ($request->hasFile('featured_image')) {
            // Delete old image
            if ($article->featured_image) {
                \Storage::disk('public')->delete($article->featured_image);
            }
            $path = $request->file('featured_image')->store('articles', 'public');
            $validated['featured_image'] = $path;
        }

        $article->update($validated);

        // Update categories
        if ($request->categories) {
            $article->categories()->sync($request->categories);
        }

        activity('update')
            ->performedOn($article)
            ->withProperties(['changes' => $request->only(['title', 'status'])])
            ->log('Article updated');

        return redirect()
            ->route('admin.articles.show', $article)
            ->with('success', 'Article updated successfully');
    }

    // Delete article
    public function destroy(Article $article)
    {
        $article->delete();

        activity('delete')
            ->performedOn($article)
            ->log('Article deleted');

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'Article deleted successfully');
    }

    // Bulk delete
    public function bulkDelete(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array|exists:articles,id'])['ids'];
        Article::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }

    // Publish article
    public function publish(Article $article)
    {
        $article->publish();

        activity('publish')
            ->performedOn($article)
            ->log('Article published');

        return response()->json(['success' => true]);
    }
}
```

---

### Subtask A3.2: Create API Routes

**File:** `routes/api.php` (or new file `routes/api/articles.php`)

```php
<?php

use App\Http\Controllers\Admin\ArticleController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // Article routes
    Route::apiResource('articles', ArticleController::class);

    // Additional actions
    Route::post('articles/{article}/publish', [ArticleController::class, 'publish']);
    Route::post('articles/bulk-delete', [ArticleController::class, 'bulkDelete']);
    Route::get('articles/search', [ArticleController::class, 'search']);
});
```

---

### Subtask A3.3: Create Web Routes

**File:** `routes/web.php` (admin section)

```php
<?php

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    // Article routes
    Route::get('articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::get('articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
    Route::get('articles/{article}/edit', [ArticleController::class, 'edit'])->name('articles.edit');
    Route::put('articles/{article}', [ArticleController::class, 'update'])->name('articles.update');
    Route::delete('articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
});
```

---

## TASK A4: FORM COMPONENTS (LIVEWIRE)

**Duration:** 3-4 days | **Team:** 2 developers | **Dependencies:** TASK A3

### Subtask A4.1: Create Article Form Component

**File:** `app/Livewire/Admin/ArticleForm.php`

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class ArticleForm extends Component
{
    use WithFileUploads;

    public ?Article $article = null;
    public array $form = [
        'title' => '',
        'slug' => '',
        'content' => '',
        'excerpt' => '',
        'status' => 'draft',
        'author_id' => '',
        'featured_image' => null,
        'seo_title' => '',
        'seo_description' => '',
    ];

    public $featuredImagePreview = null;
    public array $selectedCategories = [];
    public array $languages = ['ar' => 'عربي', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'];
    public $currentLanguage = 'ar';

    public function mount()
    {
        if ($this->article) {
            $this->form = [
                'title' => $this->article->title,
                'slug' => $this->article->slug,
                'content' => $this->article->content,
                'excerpt' => $this->article->excerpt,
                'status' => $this->article->status,
                'author_id' => $this->article->author_id,
                'seo_title' => $this->article->seo_title,
                'seo_description' => $this->article->seo_description,
            ];
            $this->selectedCategories = $this->article->categories->pluck('id')->toArray();
        }
    }

    public function updatedFormTitle()
    {
        $this->form['slug'] = Str::slug($this->form['title']) . '-' . uniqid();
    }

    public function updatedFeaturedImage()
    {
        if ($this->form['featured_image']) {
            $this->featuredImagePreview = $this->form['featured_image']->temporaryUrl();
        }
    }

    public function submit()
    {
        $validated = $this->validate([
            'form.title' => 'required|string|max:255',
            'form.slug' => 'required|string|unique:articles,slug,' . ($this->article?->id ?? 'null'),
            'form.content' => 'required|string',
            'form.excerpt' => 'nullable|string|max:500',
            'form.status' => 'in:draft,review,published',
            'form.author_id' => 'required|exists:users,id',
            'form.seo_title' => 'nullable|string|max:255',
            'form.seo_description' => 'nullable|string|max:160',
        ]);

        $data = $validated['form'];

        // Handle featured image
        if ($this->form['featured_image'] && is_object($this->form['featured_image'])) {
            $path = $this->form['featured_image']->store('articles', 'public');
            $data['featured_image'] = $path;
        }

        if ($this->article) {
            $this->article->update($data);
            $this->article->categories()->sync($this->selectedCategories);
            $message = 'Article updated successfully';
        } else {
            $article = Article::create($data);
            $article->categories()->attach($this->selectedCategories);
            $this->article = $article;
            $message = 'Article created successfully';
        }

        $this->dispatch('notify', message: $message, type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.article-form', [
            'categories' => Category::forModule('seerah')->get(),
        ]);
    }
}
```

**Blade View:** `resources/views/livewire/admin/article-form.blade.php`

```blade
<form wire:submit="submit" class="space-y-6">
    <!-- Language Tabs -->
    <div class="flex gap-2 border-b">
        @foreach($languages as $code => $label)
            <button
                type="button"
                wire:click="$set('currentLanguage', '{{ $code }}')"
                class="px-4 py-2 {{ $currentLanguage === $code ? 'border-b-2 border-blue-600' : '' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    <!-- Title -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Title</label>
        <input
            type="text"
            wire:model.defer="form.title"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        />
        @error('form.title') <span class="text-red-500">{{ $message }}</span> @enderror
    </div>

    <!-- Slug -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Slug</label>
        <input
            type="text"
            wire:model.defer="form.slug"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        />
        @error('form.slug') <span class="text-red-500">{{ $message }}</span> @enderror
    </div>

    <!-- Content Editor (WYSIWYG) -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Content</label>
        <div id="editor" wire:ignore></div>
        @error('form.content') <span class="text-red-500">{{ $message }}</span> @enderror
    </div>

    <!-- Featured Image -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Featured Image</label>
        <input
            type="file"
            wire:model="form.featured_image"
            accept="image/*"
            class="mt-1 block w-full"
        />
        @if($featuredImagePreview)
            <img src="{{ $featuredImagePreview }}" alt="Preview" class="mt-2 h-32 w-32 object-cover rounded">
        @endif
        @error('form.featured_image') <span class="text-red-500">{{ $message }}</span> @enderror
    </div>

    <!-- Categories -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Categories</label>
        <div class="mt-2 space-y-2">
            @foreach($categories as $category)
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        value="{{ $category->id }}"
                        wire:model.defer="selectedCategories"
                        class="rounded"
                    />
                    <span class="ml-2">{{ $category->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Status -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Status</label>
        <select
            wire:model.defer="form.status"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
        >
            <option value="draft">Draft</option>
            <option value="review">Review</option>
            <option value="published">Published</option>
        </select>
    </div>

    <!-- SEO -->
    <div class="border-t pt-6">
        <h3 class="text-lg font-semibold mb-4">SEO Settings</h3>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">SEO Title</label>
            <input
                type="text"
                wire:model.defer="form.seo_title"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                maxlength="60"
            />
            <p class="text-xs text-gray-500 mt-1">{{ strlen($form['seo_title'] ?? '') }}/60</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">SEO Description</label>
            <textarea
                wire:model.defer="form.seo_description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                maxlength="160"
            ></textarea>
            <p class="text-xs text-gray-500 mt-1">{{ strlen($form['seo_description'] ?? '') }}/160</p>
        </div>
    </div>

    <!-- Submit -->
    <div class="flex gap-2">
        <button
            type="submit"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
            Save Article
        </button>
        <a href="{{ route('admin.articles.index') }}" class="px-4 py-2 bg-gray-200 rounded-md">
            Cancel
        </a>
    </div>
</form>

@push('scripts')
<script>
    // Initialize WYSIWYG editor after component mounts
    document.addEventListener('livewire:navigated', () => {
        initializeEditor();
    });

    function initializeEditor() {
        tinymce.init({
            selector: '#editor',
            plugins: 'lists link image code',
            toolbar: 'undo redo | formatselect | bold italic | bullist numlist | link image | code',
            setup: function(editor) {
                editor.on('change', function() {
                    @this.set('form.content', editor.getContent());
                });
            }
        });
    }
</script>
@endpush
```

---

## TASK A5: WYSIWYG EDITOR INTEGRATION

**Duration:** 2-3 days | **Team:** 1 developer | **Dependencies:** TASK A4

### Subtask A5.1: Install and Configure TinyMCE

**Installation:**
```bash
npm install tinymce
npm install @tinymce/tinymce-vue

# OR use CDN (simpler for now)
```

**File:** `resources/views/layouts/admin.blade.php`

```blade
<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    tinymce.init({
        selector: '#editor, .tinymce-editor',
        plugins: 'anchor autolink charmap code emoticons image link lists media searchreplace table visualblocks wordcount',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code',
        height: 500,
        menubar: 'file edit view insert format tools table help',
        // Arabic support
        directionality: 'rtl',
        font_formats: 'Segoe UI=Segoe UI;Cairo="Cairo", sans-serif;Amiri="Amiri", serif;',
        content_css: '/css/tinymce-content.css',
        automatic_uploads: true,
        file_picker_types: 'image',
        file_picker_callback: function(callback, value, meta) {
            // Handle file upload
        },
        paste_as_text: true,
        paste_data_images: true,
    });
</script>
```

**Custom CSS for Arabic:** `public/css/tinymce-content.css`

```css
body {
    font-family: 'Cairo', 'Segoe UI', sans-serif;
    direction: rtl;
    text-align: right;
    line-height: 1.8;
    font-size: 16px;
}

p {
    margin-bottom: 1em;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: 600;
    margin: 1em 0 0.5em 0;
}

ul, ol {
    margin-left: 0;
    margin-right: 2em;
    padding-left: 0;
    padding-right: 2em;
}

img {
    max-width: 100%;
    height: auto;
}
```

---

### Subtask A5.2: Create Editor JavaScript Component

**File:** `resources/js/components/ArticleEditor.js`

```javascript
export class ArticleEditor {
    constructor(selector, options = {}) {
        this.selector = selector;
        this.options = {
            ...this.defaultOptions(),
            ...options
        };
        this.init();
    }

    defaultOptions() {
        return {
            language: 'ar',
            directionality: 'rtl',
            plugins: [
                'anchor autolink charmap codesample code emoticons image link lists media',
                'searchreplace table visualblocks wordcount paste',
            ],
            toolbar: [
                'undo redo | formatselect | bold italic underline strikethrough | fontselect fontsizeselect',
                'forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
                'link image media | code | table | paste pastetext pasteimage | removeformat | help',
            ],
            content_css: '/css/tinymce-content.css',
            height: 500,
            menubar: 'file edit view insert format tools table help',
            automatic_uploads: true,
            file_picker_types: 'image',
            paste_data_images: true,
        };
    }

    init() {
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: this.selector,
                ...this.options
            });
        }
    }

    getContent() {
        const editor = tinymce.get(this.selector);
        return editor ? editor.getContent() : '';
    }

    setContent(content) {
        const editor = tinymce.get(this.selector);
        if (editor) {
            editor.setContent(content);
        }
    }

    validate() {
        const content = this.getContent();
        return content.trim().length > 0;
    }
}
```

---

## TASK A6: IMAGE UPLOAD MANAGER

**Duration:** 2-3 days | **Team:** 1 developer | **Dependencies:** TASK A5

### Subtask A6.1: Create Image Upload Service

**File:** `app/Services/ImageUploadService.php`

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageUploadService
{
    protected $disk = 'public';
    protected $path = 'articles';
    protected $sizes = [
        'thumb' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 400, 'height' => 300],
        'large' => ['width' => 1200, 'height' => 800],
    ];

    public function upload(UploadedFile $file, $customPath = null): array
    {
        $path = $customPath ?? $this->path;

        // Store original
        $filename = $this->generateFilename($file);
        $originalPath = $file->storeAs($path, $filename, $this->disk);

        // Generate thumbnails
        $thumbnails = [];
        foreach ($this->sizes as $size => $dimensions) {
            $thumbnailPath = $this->createThumbnail(
                $file,
                $path,
                $filename,
                $size,
                $dimensions
            );
            $thumbnails[$size] = $thumbnailPath;
        }

        return [
            'original' => $originalPath,
            'thumbnails' => $thumbnails,
            'url' => Storage::disk($this->disk)->url($originalPath),
            'filename' => $filename,
        ];
    }

    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = str_random(8);
        return "{$timestamp}_{$random}.{$extension}";
    }

    protected function createThumbnail(
        UploadedFile $file,
        $path,
        $filename,
        $size,
        $dimensions
    ): string {
        $image = Image::make($file);

        // Resize maintaining aspect ratio
        $image->fit(
            $dimensions['width'],
            $dimensions['height'],
            function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            }
        );

        // Optimize
        $image->optimize();

        // Generate filename
        $parts = pathinfo($filename);
        $thumbFilename = "{$parts['filename']}__{$size}.{$parts['extension']}";

        // Store
        $fullPath = $path . '/' . $thumbFilename;
        Storage::disk($this->disk)->put(
            $fullPath,
            (string) $image->encode()
        );

        return $fullPath;
    }

    public function delete($path): bool
    {
        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);

            // Delete thumbnails
            $parts = pathinfo($path);
            foreach (array_keys($this->sizes) as $size) {
                $thumbPath = $parts['dirname'] . '/' .
                    $parts['filename'] . '__' . $size . '.' .
                    $parts['extension'];

                if (Storage::disk($this->disk)->exists($thumbPath)) {
                    Storage::disk($this->disk)->delete($thumbPath);
                }
            }

            return true;
        }

        return false;
    }
}
```

---

## TASK A7: SEO METADATA SYSTEM

**Duration:** 2 days | **Team:** 1 developer | **Dependencies:** TASK A6

### Subtask A7.1: Create SEO Service

**File:** `app/Services/SeoService.php`

```php
<?php

namespace App\Services;

use App\Models\Article;

class SeoService
{
    public function generateSchema(Article $article): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $article->seo_title ?? $article->title,
            'description' => $article->seo_description ?? substr(strip_tags($article->content), 0, 160),
            'image' => $article->featured_image ? asset('storage/' . $article->featured_image) : null,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $article->author->name,
            ],
        ];
    }

    public function generateMetaTags(Article $article): array
    {
        return [
            'title' => $article->seo_title ?? $article->title,
            'description' => $article->seo_description ?? substr(strip_tags($article->content), 0, 160),
            'keywords' => $article->seo_keywords,
            'og:title' => $article->seo_title ?? $article->title,
            'og:description' => $article->seo_description,
            'og:image' => $article->og_image ?? asset('storage/' . $article->featured_image),
            'og:type' => 'article',
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $article->seo_title ?? $article->title,
            'twitter:description' => $article->seo_description,
        ];
    }

    public function validateMetaTags(Article $article): array
    {
        $issues = [];

        if (!$article->seo_title) {
            $issues[] = 'SEO title is empty';
        } elseif (strlen($article->seo_title) < 30) {
            $issues[] = 'SEO title is too short (< 30 chars)';
        } elseif (strlen($article->seo_title) > 60) {
            $issues[] = 'SEO title is too long (> 60 chars)';
        }

        if (!$article->seo_description) {
            $issues[] = 'Meta description is empty';
        } elseif (strlen($article->seo_description) < 120) {
            $issues[] = 'Meta description is too short (< 120 chars)';
        } elseif (strlen($article->seo_description) > 160) {
            $issues[] = 'Meta description is too long (> 160 chars)';
        }

        if (!$article->featured_image && !$article->og_image) {
            $issues[] = 'No Open Graph image set';
        }

        return $issues;
    }
}
```

---

## TASK A8: PUBLISHING WORKFLOW

**Duration:** 2 days | **Team:** 1 developer | **Dependencies:** TASK A7

### Subtask A8.1: Implement Publishing States

**Create Migration:** `database/migrations/2025_10_31_000006_add_workflow_to_articles.php`

```php
public function up(): void
{
    Schema::table('articles', function (Blueprint $table) {
        $table->timestamp('submitted_at')->nullable()->after('published_at');
        $table->timestamp('review_started_at')->nullable()->after('reviewed_at');
        $table->foreignId('submitter_id')->nullable()->constrained('users')->onDelete('set null');
    });
}
```

**Update Article Model:**

```php
public function submitForReview()
{
    $this->update([
        'status' => 'review',
        'submitted_at' => now(),
        'submitter_id' => auth()->id(),
    ]);

    // Notify reviewers
    // TODO: Send notification
}

public function approve(User $reviewer)
{
    $this->update([
        'status' => 'published',
        'published_at' => now(),
        'reviewer_id' => $reviewer->id,
        'reviewed_at' => now(),
    ]);
}

public function reject(User $reviewer, $reason = null)
{
    $this->update([
        'status' => 'draft',
        'reviewer_id' => $reviewer->id,
        'reviewed_at' => now(),
    ]);

    // TODO: Store rejection reason and notify author
}

public function schedule($publishDate)
{
    $this->update([
        'status' => 'published',
        'published_at' => $publishDate,
    ]);

    // TODO: Create scheduled job
}
```

---

## 📊 Implementation Timeline Summary

| Task | Duration | Team | Dependencies | Status |
|------|----------|------|--------------|--------|
| A1: Database | 3-4 days | 1 dev | MySQL | Ready |
| A2: Models | 2-3 days | 1 dev | A1 | Ready |
| A3: Controllers | 3-4 days | 2 devs | A2 | Ready |
| A4: Forms | 3-4 days | 2 devs | A3 | Ready |
| A5: Editor | 2-3 days | 1 dev | A4 | Ready |
| A6: Images | 2-3 days | 1 dev | A5 | Ready |
| A7: SEO | 2 days | 1 dev | A6 | Ready |
| A8: Workflow | 2 days | 1 dev | A7 | Ready |
| **TOTAL** | **20-26 days** | **4-6 devs** | Parallel | Ready |

---

## ✅ Article Module Deliverables

### Phase 1 Complete (Week 1-4):
- [x] Database schema with migrations
- [x] Article model with relationships
- [x] CRUD controllers
- [x] Livewire form components
- [x] Featured image upload
- [x] Multi-language support setup
- [x] Basic publishing workflow
- [x] Activity logging

### Phase 2 Extensions:
- [ ] Advanced SEO editor
- [ ] Publishing workflow (draft → review → published)
- [ ] Revision history
- [ ] Scheduled publishing
- [ ] Content localization interface
- [ ] Statistics dashboard

---

## 🚀 Next Steps

1. **Assign Tasks:** Distribute A1-A8 among team members
2. **Setup Environment:** Ensure Laravel, MySQL, Livewire are installed
3. **Database:** Create and run migrations
4. **Development:** Implement tasks in parallel
5. **Integration:** Test cross-task dependencies
6. **Testing:** Unit and integration tests
7. **Review:** Code review before merging

---

**Document Version:** 1.0
**Status:** Ready for Implementation
**Last Updated:** 2025-10-31

Start with TASK A1 and proceed in order, with parallelization where dependencies allow.
