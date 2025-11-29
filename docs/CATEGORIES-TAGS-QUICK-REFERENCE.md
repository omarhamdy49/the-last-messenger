# Categories & Tags - Quick Reference Guide

## Category Model Methods

### Basic Operations
```php
// Create category
$category = Category::create([
    'name' => ['ar' => 'الإسلام', 'en' => 'Islam'],
    'type' => CategoryType::BLOG,
    'status' => 'active',
    'parent_id' => null,
]);

// Update category
$category->update(['status' => 'inactive']);

// Delete (soft delete)
$category->delete();

// Restore
$category->restore();
```

### Relationships
```php
// Get parent category
$parent = $category->parent;

// Get child categories
$children = $category->children;

// Get all descendants (recursive)
$descendants = $category->descendants;

// Get ancestors using nested set
$ancestors = $category->ancestors()->get();

// Get siblings
$siblings = $category->siblings()->get();

// Get articles
$articles = $category->articles;

// Get media
$media = $category->media;
```

### Scopes
```php
// Get active categories only
$active = Category::active()->get();

// Get root categories (no parent)
$roots = Category::root()->get();

// Get categories at specific depth
$level2 = Category::withDepth(2)->get();

// Filter by type
$blogCategories = Category::ofType(CategoryType::BLOG)->get();

// Ordered by display order
$ordered = Category::ordered()->get();
```

### Helper Methods
```php
// Check if category has children
if ($category->hasChildren()) { }

// Check if root category
if ($category->isRoot()) { }

// Check if leaf node (no children)
if ($category->isLeaf()) { }

// Check if active
if ($category->isActive()) { }

// Get breadcrumb
$breadcrumb = $category->getBreadcrumb('en');
// Returns: ['Parent', 'Child', 'Current']

// Get category tree (static)
$tree = Category::getTree(null, 'ar');
```

### Multi-Language Methods
```php
// Get name in specific language
$name = $category->getNameInLanguage('en');

// Get description in specific language
$desc = $category->getDescriptionInLanguage('ar');

// Get all name translations
$names = $category->getNameTranslations();
// Returns: ['ar' => 'الإسلام', 'en' => 'Islam', ...]
```

---

## Tag Model Methods

### Basic Operations
```php
// Create tag
$tag = Tag::create([
    'name' => ['ar' => 'النبي', 'en' => 'Prophet'],
    'type' => 'article',
    'color' => '#218838',
]);

// Update tag
$tag->update(['color' => '#0056b3']);

// Delete (soft delete)
$tag->delete();

// Restore
$tag->restore();
```

### Relationships
```php
// Get articles with this tag
$articles = $tag->articles;

// Get media with this tag
$media = $tag->media;
```

### Scopes
```php
// Get popular tags (top 10)
$popular = Tag::popular(10)->get();

// Get recent tags (last 30 days)
$recent = Tag::recent(30)->get();

// Get unused tags (zero usage)
$unused = Tag::unused()->get();

// Filter by type
$articleTags = Tag::ofType('article')->get();

// Search by name
$found = Tag::search('prophet')->get();
```

### Usage Tracking
```php
// Increment usage count
$tag->incrementUsage();

// Decrement usage count
$tag->decrementUsage();

// Recalculate usage from relationships
$tag->recalculateUsage();
```

### Multi-Language Methods
```php
// Get name in specific language
$name = $tag->getNameInLanguage('en');

// Get description in specific language
$desc = $tag->getDescriptionInLanguage('ar');
```

---

## Category Service Methods

```php
// Inject service
$categoryService = app(CategoryService::class);

// Paginate with filters
$categories = $categoryService->paginateWithFilters(
    perPage: 50,
    search: 'islam',
    filters: ['type' => 'blog', 'parent_id' => null],
    with: ['parent', 'children']
);

// Create category (with validation)
$category = $categoryService->createCategory($data);

// Update category (with validation)
$category = $categoryService->updateCategory($id, $data);

// Delete category (validates no children/articles)
$categoryService->deleteCategory($id);

// Bulk delete (validates all)
$count = $categoryService->bulkDelete([1, 2, 3]);

// Reorder categories
$categoryService->reorder([
    ['id' => 1, 'display_order' => 0],
    ['id' => 2, 'display_order' => 1],
]);

// Search categories
$results = $categoryService->search('islam', 50, 'blog');

// Get root categories
$roots = $categoryService->getRootCategories();

// Get root categories except specific ID
$roots = $categoryService->getRootCategoriesExcept(5);

// Count by type
$count = $categoryService->countByType(CategoryType::BLOG);
```

---

## Tag Service Methods

```php
// Inject service
$tagService = app(TagService::class);

// Paginate with filters
$tags = $tagService->paginate(
    perPage: 50,
    search: 'prophet',
    filters: ['type' => 'article'],
    with: []
);

// Create tag
$tag = $tagService->create($data);

// Update tag
$tag = $tagService->update($id, $data);

// Delete tag (detaches from all articles)
$tagService->delete($id);

// Bulk delete
$count = $tagService->bulkDelete([1, 2, 3]);

// Merge tags (move all articles from source to destination)
$movedCount = $tagService->mergeTags($sourceId, $destinationId);

// Find duplicate tag
$duplicate = $tagService->findDuplicate([
    'ar' => 'النبي',
    'en' => 'Prophet'
]);

// Search for autocomplete
$tags = $tagService->searchForAutocomplete('prophet', 'article', 10);

// Get unused tags
$unused = $tagService->getUnusedTags(50);

// Get total count
$count = $tagService->count();

// Get total usage count
$usageCount = $tagService->getTotalUsageCount();

// Get most used tag
$mostUsed = $tagService->getMostUsedTag();

// Get usage count for specific tag
$count = $tagService->getUsageCount($tagId);

// Recalculate all usage counts
$updated = $tagService->recalculateAllUsageCounts();
```

---

## API Endpoints - Categories

### List Categories
```http
GET /admin/categories?per_page=50&search=islam&type=blog
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "stats": {
    "total": 125,
    "blog": 45,
    "media": 60,
    "timeline": 20
  },
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 125,
    "last_page": 3
  }
}
```

### Get Category Tree
```http
GET /admin/categories/tree?language=ar&type=blog
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "الإسلام",
      "slug": "islam",
      "icon": "book",
      "color": "#0056b3",
      "depth": 0,
      "has_children": true,
      "children": [...]
    }
  ]
}
```

### Search Categories
```http
GET /admin/categories/search?q=islam&type=blog&per_page=15
```

### Create Category
```http
POST /admin/categories
Content-Type: application/json

{
  "name": {
    "ar": "الإسلام",
    "en": "Islam"
  },
  "description": {
    "ar": "مقالات عن الإسلام",
    "en": "Articles about Islam"
  },
  "type": "blog",
  "status": "active",
  "parent_id": null,
  "icon": "book",
  "color": "#0056b3",
  "display_order": 0
}
```

### Update Category
```http
PUT /admin/categories/{id}
Content-Type: application/json

{
  "name": {
    "ar": "الإسلام المحدث",
    "en": "Updated Islam"
  },
  "status": "active"
}
```

### Delete Category
```http
DELETE /admin/categories/{id}
```

### Restore Category
```http
POST /admin/categories/{id}/restore
```

### Duplicate Category
```http
POST /admin/categories/{id}/duplicate
```

**Response:**
```json
{
  "success": true,
  "message": "Category duplicated successfully",
  "data": {
    "id": 15,
    "name": "Islam (Copy)",
    "status": "inactive",
    ...
  }
}
```

### Bulk Delete Categories
```http
POST /admin/categories/bulk-delete
Content-Type: application/json

{
  "ids": [1, 2, 3]
}
```

### Reorder Categories
```http
POST /admin/categories/reorder
Content-Type: application/json

{
  "items": [
    {"id": 1, "order": 0},
    {"id": 2, "order": 1},
    {"id": 3, "order": 2}
  ]
}
```

---

## API Endpoints - Tags

### List Tags
```http
GET /admin/tags?per_page=50&search=prophet&type=article
```

**Response:**
```json
{
  "success": true,
  "data": [...],
  "stats": {
    "total": 250,
    "total_uses": 1500,
    "most_used": {
      "id": 5,
      "name": "Prophet Muhammad",
      "usage_count": 156
    },
    "recent": 15
  },
  "meta": {
    "current_page": 1,
    "per_page": 50,
    "total": 250,
    "last_page": 5
  }
}
```

### Search Tags (Autocomplete)
```http
GET /admin/tags/search?q=prophet&type=article&limit=10
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "slug": "prophet-muhammad",
      "name": "Prophet Muhammad",
      "type": "article",
      "usage_count": 156
    }
  ]
}
```

### Get Unused Tags
```http
GET /admin/tags/unused?per_page=50
```

### Create Tag
```http
POST /admin/tags
Content-Type: application/json

{
  "name": {
    "ar": "النبي محمد",
    "en": "Prophet Muhammad"
  },
  "description": {
    "en": "Articles about Prophet Muhammad"
  },
  "type": "article",
  "color": "#218838"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Tag created successfully",
  "data": {
    "id": 25,
    "slug": "prophet-muhammad",
    "name": "Prophet Muhammad",
    ...
  }
}
```

### Update Tag
```http
PUT /admin/tags/{id}
Content-Type: application/json

{
  "name": {
    "ar": "النبي محمد ﷺ",
    "en": "Prophet Muhammad ﷺ"
  },
  "color": "#0056b3"
}
```

### Delete Tag
```http
DELETE /admin/tags/{id}
```

### Merge Tags
```http
POST /admin/tags/merge
Content-Type: application/json

{
  "source_tag_id": 10,
  "destination_tag_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully merged tags. 45 articles moved.",
  "count": 45
}
```

### Bulk Delete Tags
```http
POST /admin/tags/bulk-delete
Content-Type: application/json

{
  "ids": [1, 2, 3]
}
```

---

## Frontend Usage Examples

### Category Tree Component (Alpine.js)

```html
<div x-data="categoryTree()">
  <!-- Tree container -->
  <div x-show="loading">Loading...</div>

  <div x-show="!loading">
    <template x-for="category in tree" :key="category.id">
      <div class="category-item" :style="`padding-left: ${category.depth * 20}px`">
        <i :data-lucide="category.icon"></i>
        <span x-text="category.name"></span>
        <span class="badge" :style="`background-color: ${category.color}`">
          <span x-text="category.articles_count"></span> articles
        </span>
      </div>
    </template>
  </div>
</div>

<script>
function categoryTree() {
  return {
    tree: [],
    loading: true,

    init() {
      this.fetchTree();
    },

    async fetchTree() {
      try {
        const response = await fetch('/admin/categories/tree?language=ar&type=blog');
        const data = await response.json();
        this.tree = data.data;
      } catch (error) {
        console.error('Failed to load category tree:', error);
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>
```

### Tag Autocomplete Component (Alpine.js)

```html
<div x-data="tagAutocomplete()">
  <input
    type="text"
    x-model="query"
    @input.debounce.300ms="search"
    placeholder="Search tags..."
  />

  <div x-show="results.length > 0" class="autocomplete-dropdown">
    <template x-for="tag in results" :key="tag.id">
      <div @click="selectTag(tag)" class="autocomplete-item">
        <span x-text="tag.name"></span>
        <span class="badge" :style="`background-color: ${tag.color}`">
          <span x-text="tag.usage_count"></span> uses
        </span>
      </div>
    </template>
  </div>
</div>

<script>
function tagAutocomplete() {
  return {
    query: '',
    results: [],
    selectedTags: [],

    async search() {
      if (this.query.length < 2) {
        this.results = [];
        return;
      }

      try {
        const response = await fetch(
          `/admin/tags/search?q=${encodeURIComponent(this.query)}&type=article&limit=10`
        );
        const data = await response.json();
        this.results = data.data;
      } catch (error) {
        console.error('Failed to search tags:', error);
      }
    },

    selectTag(tag) {
      this.selectedTags.push(tag);
      this.query = '';
      this.results = [];
    }
  }
}
</script>
```

---

## Common Use Cases

### 1. Display Category Breadcrumb

```php
// Controller
$category = Category::findOrFail($id);
$breadcrumb = $category->getBreadcrumb('en');

// Blade view
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    @foreach($breadcrumb as $item)
      <li class="breadcrumb-item">{{ $item }}</li>
    @endforeach
  </ol>
</nav>
```

### 2. Category Tree Dropdown

```php
// Controller
$tree = Category::getTree(null, 'ar');

// Blade view (recursive)
@foreach($tree as $category)
  <option value="{{ $category['id'] }}"
          style="padding-left: {{ $category['depth'] * 20 }}px">
    {{ str_repeat('—', $category['depth']) }} {{ $category['name'] }}
  </option>
  @if($category['has_children'])
    <!-- Recursively include children -->
  @endif
@endforeach
```

### 3. Popular Tags Cloud

```php
// Controller
$popularTags = Tag::popular(20)->get();

// Blade view
<div class="tag-cloud">
  @foreach($popularTags as $tag)
    <a href="{{ route('articles.by-tag', $tag->slug) }}"
       class="tag"
       style="background-color: {{ $tag->color }}; font-size: {{ 0.8 + ($tag->usage_count / 100) }}rem">
      {{ $tag->getNameInLanguage('ar') }}
      <span class="count">{{ $tag->usage_count }}</span>
    </a>
  @endforeach
</div>
```

### 4. Article Form with Tag Autocomplete

```html
<form x-data="articleForm()">
  <!-- Tag input -->
  <div class="form-group">
    <label>Tags</label>
    <input type="text"
           x-model="tagQuery"
           @input.debounce.300ms="searchTags"
           placeholder="Type to search tags..." />

    <!-- Autocomplete results -->
    <div x-show="tagResults.length > 0">
      <template x-for="tag in tagResults" :key="tag.id">
        <div @click="addTag(tag)" class="tag-suggestion">
          <span x-text="tag.name"></span>
        </div>
      </template>
    </div>

    <!-- Selected tags -->
    <div class="selected-tags">
      <template x-for="tag in selectedTags" :key="tag.id">
        <span class="tag" :style="`background-color: ${tag.color}`">
          <span x-text="tag.name"></span>
          <button @click="removeTag(tag.id)">×</button>
        </span>
      </template>
    </div>
  </div>
</form>
```

---

## Database Queries Examples

### Get all articles in a category and its subcategories

```php
// Using nested set
$category = Category::findOrFail($id);
$articles = Article::whereHas('category', function($q) use ($category) {
    $q->where('lft', '>=', $category->lft)
      ->where('rgt', '<=', $category->rgt);
})->get();
```

### Get most used tags in last 30 days

```php
$popularTags = Tag::whereHas('articles', function($q) {
    $q->where('created_at', '>=', now()->subDays(30));
})
->withCount('articles')
->orderByDesc('articles_count')
->take(10)
->get();
```

### Get categories with article count

```php
$categories = Category::withCount('articles')
    ->having('articles_count', '>', 0)
    ->orderByDesc('articles_count')
    ->get();
```

---

## Color Scheme

### Categories
- Default: `#0056b3` (Blue)
- Can be customized per category

### Tags
- Default: `#218838` (Green)
- Can be customized per tag

### Gradient (for UI elements)
- Primary: `from-[#0056b3] to-[#218838]`

### Usage in Tailwind
```html
<!-- Category badge -->
<span class="badge" style="background-color: {{ $category->color }}">
  {{ $category->getNameInLanguage('ar') }}
</span>

<!-- Tag with gradient hover -->
<a href="#"
   class="tag hover:bg-gradient-to-r hover:from-[#0056b3] hover:to-[#218838]"
   style="background-color: {{ $tag->color }}">
  {{ $tag->getNameInLanguage('ar') }}
</a>
```

---

## Summary

This quick reference provides all essential methods, endpoints, and usage examples for the Categories & Tags Management module. For detailed implementation details, see `CATEGORIES-TAGS-IMPLEMENTATION-SUMMARY.md`.

**Key Points:**
- All methods support multi-language (ar, en, fr, tr, ur)
- Categories use nested set model for efficient hierarchical queries
- Tags track usage count for performance
- Both support soft deletes
- All routes are protected by auth and admin middleware
- API returns standardized JSON responses
- Supports RTL layouts and blue-green color scheme
