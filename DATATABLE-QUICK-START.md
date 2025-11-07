# DataTable Quick Start Guide

## Installation

The DataTable component is already installed. No additional dependencies needed!

**Files Created:**
- `/resources/views/components/admin/data-table.blade.php`
- `/resources/views/components/admin/table-header.blade.php`
- `/resources/views/components/admin/table-row.blade.php`
- `/resources/views/admin/data-table-demo.blade.php` (demo page)

---

## 1. Basic Usage (5 Minutes)

### Step 1: Define Columns

```php
// In your controller
$columns = [
    ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '80px'],
    ['key' => 'title', 'label' => 'Title', 'sortable' => true],
    ['key' => 'status', 'label' => 'Status', 'badge' => true],
    ['key' => 'created_at', 'label' => 'Created', 'type' => 'date'],
];
```

### Step 2: Get Your Data

```php
// Using Eloquent
$articles = Article::all();

// Or array
$articles = [
    ['id' => 1, 'title' => 'Article 1', 'status' => 'published', 'created_at' => now()],
    ['id' => 2, 'title' => 'Article 2', 'status' => 'draft', 'created_at' => now()],
];
```

### Step 3: Use Component

```blade
<x-admin.data-table
    :columns="$columns"
    :data="$articles"
/>
```

**Done!** You now have a fully functional data table.

---

## 2. Add Actions (2 Minutes)

```blade
<x-admin.data-table
    :columns="$columns"
    :data="$articles"
    :actions="['view', 'edit', 'delete']"
    edit-route="/admin/articles/{id}/edit"
    view-route="/admin/articles/{id}"
/>
```

This adds a dropdown menu with View, Edit, and Delete buttons for each row.

---

## 3. Add Bulk Actions (1 Minute)

```blade
<x-admin.data-table
    :columns="$columns"
    :data="$articles"
    :bulkActions="['delete', 'publish']"
/>
```

This adds checkboxes and a bulk action toolbar.

---

## 4. Column Types Cheat Sheet

```php
// TEXT (default)
['key' => 'title', 'label' => 'Title']

// NUMBER (formatted with commas)
['key' => 'views', 'label' => 'Views', 'type' => 'number']

// IMAGE (thumbnail)
['key' => 'thumbnail', 'label' => 'Image', 'type' => 'image']

// BADGE (status pill)
['key' => 'status', 'label' => 'Status', 'badge' => true]

// DATE (Jan 15, 2024)
['key' => 'created_at', 'label' => 'Created', 'type' => 'date']

// DATETIME (date + time)
['key' => 'created_at', 'label' => 'Created', 'type' => 'datetime']

// BOOLEAN (Yes/No badge)
['key' => 'featured', 'label' => 'Featured', 'type' => 'boolean']
```

---

## 5. Common Patterns

### Pattern 1: Blog/Articles Table

```php
$columns = [
    ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '80px', 'type' => 'number'],
    ['key' => 'image', 'label' => 'Image', 'type' => 'image'],
    ['key' => 'title', 'label' => 'Title', 'sortable' => true, 'truncate' => 50],
    ['key' => 'author', 'label' => 'Author', 'sortable' => true],
    ['key' => 'status', 'label' => 'Status', 'badge' => true],
    ['key' => 'views', 'label' => 'Views', 'sortable' => true, 'type' => 'number'],
    ['key' => 'created_at', 'label' => 'Created', 'type' => 'datetime'],
];

$articles = Article::with('author')->get()->map(fn($a) => [
    'id' => $a->id,
    'image' => $a->thumbnail_url,
    'title' => $a->title,
    'author' => $a->author->name,
    'status' => $a->status,
    'views' => $a->views_count,
    'created_at' => $a->created_at,
]);
```

### Pattern 2: Users Table

```php
$columns = [
    ['key' => 'id', 'label' => 'ID', 'width' => '80px', 'type' => 'number'],
    ['key' => 'avatar', 'label' => 'Avatar', 'type' => 'image'],
    ['key' => 'name', 'label' => 'Name', 'sortable' => true],
    ['key' => 'email', 'label' => 'Email', 'sortable' => true],
    ['key' => 'role', 'label' => 'Role', 'badge' => true],
    ['key' => 'verified', 'label' => 'Verified', 'type' => 'boolean'],
    ['key' => 'created_at', 'label' => 'Joined', 'type' => 'date'],
];
```

### Pattern 3: Products/Items Table

```php
$columns = [
    ['key' => 'sku', 'label' => 'SKU', 'sortable' => true],
    ['key' => 'image', 'label' => 'Image', 'type' => 'image'],
    ['key' => 'name', 'label' => 'Product Name', 'sortable' => true],
    ['key' => 'price', 'label' => 'Price', 'type' => 'number'],
    ['key' => 'stock', 'label' => 'Stock', 'type' => 'number'],
    ['key' => 'in_stock', 'label' => 'Available', 'type' => 'boolean'],
];
```

---

## 6. Customization

### Custom Badge Colors

```php
[
    'key' => 'status',
    'label' => 'Status',
    'badge' => true,
    'badgeColors' => [
        'active' => 'from-green-500 to-emerald-600',
        'inactive' => 'from-gray-500 to-gray-600',
        'suspended' => 'from-red-500 to-rose-600',
    ]
]
```

### Truncate Long Text

```php
['key' => 'description', 'label' => 'Description', 'truncate' => 100]
```

### Center Align

```php
['key' => 'views', 'label' => 'Views', 'align' => 'center']
```

### Fixed Width

```php
['key' => 'id', 'label' => 'ID', 'width' => '80px']
```

---

## 7. Full Example

```php
// Controller
public function index()
{
    $columns = [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'width' => '80px', 'type' => 'number'],
        ['key' => 'thumbnail', 'label' => 'Image', 'type' => 'image'],
        ['key' => 'title', 'label' => 'Title', 'sortable' => true, 'truncate' => 40],
        ['key' => 'category', 'label' => 'Category', 'sortable' => true],
        ['key' => 'status', 'label' => 'Status', 'sortable' => true, 'badge' => true],
        ['key' => 'views', 'label' => 'Views', 'sortable' => true, 'type' => 'number'],
        ['key' => 'featured', 'label' => 'Featured', 'type' => 'boolean'],
        ['key' => 'created_at', 'label' => 'Created', 'sortable' => true, 'type' => 'datetime'],
    ];

    $articles = Article::with('category')->get()->map(fn($a) => [
        'id' => $a->id,
        'thumbnail' => $a->thumbnail_url,
        'title' => $a->title,
        'category' => $a->category->name,
        'status' => $a->status,
        'views' => $a->views_count,
        'featured' => $a->is_featured,
        'created_at' => $a->created_at->toISOString(),
    ]);

    return view('admin.articles.index', compact('columns', 'articles'));
}
```

```blade
{{-- View: resources/views/admin/articles/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.data-table
        :columns="$columns"
        :data="$articles"
        :actions="['view', 'edit', 'duplicate', 'delete']"
        :bulkActions="['delete', 'publish', 'export']"
        :searchable="true"
        :exportable="true"
        :per-page-options="[10, 25, 50]"
        edit-route="/admin/articles/{id}/edit"
        delete-route="/admin/articles/{id}"
        view-route="/admin/articles/{id}"
        empty-title="No Articles Yet"
        empty-message="Create your first article to get started."
        empty-icon="file-text"
    >
        Articles Management
    </x-admin.data-table>
@endsection
```

---

## 8. Features Checklist

- [x] Sorting (click column headers)
- [x] Search (real-time with debounce)
- [x] Pagination (configurable per-page)
- [x] Bulk select (checkboxes)
- [x] Bulk actions (delete, publish, export)
- [x] Row actions (view, edit, delete, duplicate)
- [x] Export (CSV, Excel, PDF)
- [x] Empty state
- [x] Loading state (skeletons)
- [x] Dark mode support
- [x] Responsive (mobile-friendly)
- [x] RTL support
- [x] Alternating row colors
- [x] Hover effects
- [x] Delete confirmation modal
- [x] Action dropdown menu

---

## 9. Tips & Tricks

### Tip 1: Date Formatting
Always send dates in ISO format for proper parsing:
```php
'created_at' => $article->created_at->toISOString()
```

### Tip 2: Image Fallback
If image is null, component shows a placeholder icon automatically.

### Tip 3: Search Performance
For large datasets (1000+ rows), implement server-side search:
```php
Article::where('title', 'like', "%{$search}%")->get();
```

### Tip 4: Badge Colors
Use Tailwind gradient classes for badges:
```php
'from-blue-500 to-indigo-600'  // Blue gradient
'from-red-500 to-pink-600'     // Red gradient
```

### Tip 5: Custom Empty State
Provide context-specific empty states:
```php
empty-title="No Users Found"
empty-message="Invite users to join your platform."
empty-icon="users"
```

---

## 10. Demo Page

Visit the demo page to see all features in action:

```
/admin/data-table-demo
```

Or create a route:
```php
Route::get('/admin/data-table-demo', function () {
    return view('admin.data-table-demo');
})->name('admin.datatable.demo');
```

---

## Troubleshooting

### Icons not showing?
Add this to your layout:
```html
<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
```

### Dark mode not working?
Ensure your layout has Alpine.js dark mode setup:
```html
<html x-data="{ darkMode: localStorage.getItem('darkMode') !== 'false' }"
      :class="darkMode ? 'dark' : ''">
```

### Routes not working?
Check your route pattern includes `{id}`:
```php
edit-route="/admin/articles/{id}/edit"  // Correct
```

---

## Next Steps

1. Check the full documentation: `DATATABLE-API-DOCUMENTATION.md`
2. View the demo page: `/admin/data-table-demo`
3. Customize badge colors for your status values
4. Implement server-side pagination for large datasets
5. Add filters sidebar (coming soon)

---

**Quick Reference:**
- Main Component: `/resources/views/components/admin/data-table.blade.php`
- Demo Page: `/resources/views/admin/data-table-demo.blade.php`
- Full Docs: `/DATATABLE-API-DOCUMENTATION.md`

**Need Help?** Check the API documentation for advanced usage and customization options.
