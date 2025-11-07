# DataTable Component API Documentation

## Overview

A powerful, feature-rich data table component system with sorting, filtering, pagination, bulk actions, and export capabilities. Built with Laravel Blade, Alpine.js, and Tailwind CSS, featuring Netflix-style design with glassmorphism effects and blue-green gradient theming.

---

## File Structure

```
resources/views/components/admin/
├── data-table.blade.php        # Main DataTable component
├── table-header.blade.php      # Table header with sortable columns
└── table-row.blade.php         # Table row with actions and badges
```

---

## Components

### 1. DataTable Component

**File:** `/resources/views/components/admin/data-table.blade.php`

#### Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `columns` | array | Yes | `[]` | Column definitions (see Column Configuration) |
| `data` | Collection\|array | Yes | `[]` | Table data |
| `actions` | array | No | `[]` | Row actions: `['view', 'edit', 'delete', 'duplicate']` |
| `bulkActions` | array | No | `[]` | Bulk actions: `['delete', 'publish', 'export']` |
| `searchable` | bool | No | `true` | Enable search functionality |
| `filterable` | bool | No | `false` | Enable filters (reserved for future use) |
| `exportable` | bool | No | `true` | Enable export dropdown |
| `perPageOptions` | array | No | `[10, 25, 50, 100]` | Per-page selector options |
| `emptyTitle` | string | No | `'No Data Found'` | Empty state title |
| `emptyMessage` | string | No | `'There are no records...'` | Empty state message |
| `emptyIcon` | string | No | `'inbox'` | Empty state Lucide icon name |
| `editRoute` | string | No | `null` | Edit route pattern (e.g., `/admin/articles/{id}/edit`) |
| `deleteRoute` | string | No | `null` | Delete route pattern |
| `viewRoute` | string | No | `null` | View route pattern |

#### Basic Usage

```blade
<x-admin.data-table
    :columns="$columns"
    :data="$articles"
    :actions="['view', 'edit', 'delete']"
    :bulkActions="['delete', 'publish']"
/>
```

#### Advanced Usage

```blade
<x-admin.data-table
    :columns="$columns"
    :data="$articles"
    :actions="['view', 'edit', 'duplicate', 'delete']"
    :bulkActions="['delete', 'publish', 'export']"
    :searchable="true"
    :exportable="true"
    :per-page-options="[5, 10, 25, 50]"
    edit-route="/admin/articles/{id}/edit"
    delete-route="/admin/articles/{id}"
    view-route="/admin/articles/{id}"
    empty-title="No Articles Found"
    empty-message="Start by creating your first article."
    empty-icon="file-text"
>
    Articles Management
</x-admin.data-table>
```

---

### 2. Column Configuration

Each column in the `columns` array is an associative array with the following properties:

#### Column Properties

| Property | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `key` | string | Yes | - | Data key to access in row object |
| `label` | string | Yes | - | Display label for column header |
| `sortable` | bool | No | `false` | Enable sorting for this column |
| `width` | string | No | `'auto'` | CSS width (e.g., `'80px'`, `'200px'`) |
| `type` | string | No | `'text'` | Data type (see Column Types below) |
| `align` | string | No | `'left'` | Text alignment: `'left'`, `'center'`, `'right'` |
| `truncate` | int | No | `null` | Max characters before truncation |
| `badge` | bool | No | `false` | Display value as badge |
| `badgeColors` | array | No | `[...]` | Badge color mapping (see Badge Colors) |

#### Column Types

##### 1. Text (Default)
```php
['key' => 'title', 'label' => 'Title', 'type' => 'text']
```
- Displays plain text
- Optional truncation: `'truncate' => 50`
- Title attribute shows full text on hover

##### 2. Number
```php
['key' => 'views', 'label' => 'Views', 'type' => 'number']
```
- Formatted with thousand separators
- Gradient text styling
- Example: `12,345`

##### 3. Image
```php
['key' => 'thumbnail', 'label' => 'Image', 'type' => 'image']
```
- Displays 48x48px rounded thumbnail
- Fallback icon if image missing
- Object-fit: cover

##### 4. Badge
```php
[
    'key' => 'status',
    'label' => 'Status',
    'badge' => true,
    'badgeColors' => [
        'published' => 'from-green-500 to-emerald-600',
        'draft' => 'from-gray-500 to-gray-600',
        'pending' => 'from-yellow-500 to-orange-600',
        'archived' => 'from-red-500 to-rose-600',
    ]
]
```
- Pill-shaped badge with gradient
- Animated pulse dot indicator
- Hover scale effect

##### 5. Date
```php
['key' => 'created_at', 'label' => 'Created', 'type' => 'date']
```
- Format: `Jan 15, 2024`
- Localized date formatting

##### 6. DateTime
```php
['key' => 'created_at', 'label' => 'Created', 'type' => 'datetime']
```
- Two-line display
- Line 1: Date (Jan 15, 2024)
- Line 2: Time (10:30 AM)

##### 7. Boolean
```php
['key' => 'featured', 'label' => 'Featured', 'type' => 'boolean']
```
- Yes/No badge with icons
- Green gradient for `true`
- Gray for `false`

#### Example Column Configuration

```php
$columns = [
    [
        'key' => 'id',
        'label' => 'ID',
        'sortable' => true,
        'width' => '80px',
        'type' => 'number',
        'align' => 'center',
    ],
    [
        'key' => 'image',
        'label' => 'Thumbnail',
        'type' => 'image',
        'width' => '100px',
    ],
    [
        'key' => 'title',
        'label' => 'Title',
        'sortable' => true,
        'truncate' => 50,
    ],
    [
        'key' => 'status',
        'label' => 'Status',
        'sortable' => true,
        'badge' => true,
        'badgeColors' => [
            'published' => 'from-green-500 to-emerald-600',
            'draft' => 'from-gray-500 to-gray-600',
        ],
    ],
    [
        'key' => 'views',
        'label' => 'Views',
        'sortable' => true,
        'type' => 'number',
        'align' => 'center',
    ],
    [
        'key' => 'featured',
        'label' => 'Featured',
        'type' => 'boolean',
        'align' => 'center',
    ],
    [
        'key' => 'created_at',
        'label' => 'Created',
        'sortable' => true,
        'type' => 'datetime',
    ],
];
```

---

## Features

### 1. Sorting

- **Click column headers** to sort ascending/descending
- **Visual indicators**: Blue chevron (ascending), Green chevron (descending)
- **Multiple types**: Text, number, date sorting
- **State management**: Persists sort column and direction

#### How It Works
```javascript
// Triggered on column header click
sortBy(column) {
    if (this.sortColumn === column) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        this.sortColumn = column;
        this.sortDirection = 'asc';
    }
    // Sorts filteredData array
}
```

---

### 2. Search

- **Real-time filtering** across all columns
- **300ms debounce** for performance
- **Clear button** when search has value
- **Case-insensitive** matching

#### Search Behavior
```javascript
// Searches all column values
filterData() {
    const query = this.searchQuery.toLowerCase();
    this.filteredData = this.rawData.filter(row => {
        return Object.values(row).some(value => {
            return String(value).toLowerCase().includes(query);
        });
    });
}
```

---

### 3. Pagination

- **Per-page selector**: Choose rows per page
- **Page numbers**: Max 5 visible page buttons
- **Navigation**: First, Previous, Next, Last buttons
- **Info display**: "Showing X to Y of Z"
- **Disabled states**: Buttons disabled at boundaries

#### Pagination Controls
```blade
<select x-model="perPage" @change="changePage(1)">
    <option value="10">10</option>
    <option value="25">25</option>
    <option value="50">50</option>
    <option value="100">100</option>
</select>
```

---

### 4. Bulk Actions

- **Select All checkbox** in header
- **Individual checkboxes** per row
- **Bulk toolbar** appears when items selected
- **Available actions**:
  - Delete (red button with confirmation)
  - Publish (gradient button)
  - Export (gray button)
- **Clear selection** button

#### Bulk Action Usage
```php
:bulkActions="['delete', 'publish', 'export']"
```

#### Selected State Styling
- Rows highlight with blue background
- Blue border on selected rows
- Count badge shows selected items

---

### 5. Row Actions

- **Dropdown menu** per row (three-dot icon)
- **Available actions**:
  - View (blue eye icon)
  - Edit (teal pencil icon)
  - Duplicate (green copy icon)
  - Delete (red trash icon)

#### Action Configuration
```php
:actions="['view', 'edit', 'duplicate', 'delete']"

// Route patterns (replace {id} with row ID)
edit-route="/admin/articles/{id}/edit"
delete-route="/admin/articles/{id}"
view-route="/admin/articles/{id}"
```

#### Delete Confirmation
- Modal popup with warning icon
- "Confirm Deletion" title
- Cancel and Delete buttons
- Prevents accidental deletion

---

### 6. Export

- **Dropdown menu** with three formats
- **CSV Export**: Fully functional, downloads immediately
- **Excel Export**: Placeholder (returns alert)
- **PDF Export**: Placeholder (returns alert)

#### CSV Export Format
```csv
ID,Title,Status,Created
1,"Article Title","published","2024-01-15"
2,"Another Article","draft","2024-02-20"
```

#### Export Implementation
```javascript
downloadCSV(data, filename) {
    // Creates CSV from columns and data
    // Escapes quotes and commas
    // Triggers browser download
}
```

---

### 7. Empty State

- **Centered layout** with icon
- **Custom title and message**
- **Configurable icon** (Lucide icon name)

#### Empty State Props
```blade
empty-title="No Articles Found"
empty-message="Start by creating your first article."
empty-icon="file-text"
```

---

### 8. Loading States

- **Skeleton rows** while loading
- **Loading overlay** with spinner
- **Smooth transitions** between states

---

### 9. Responsive Design

- **Horizontal scroll** on mobile
- **Sticky pagination** footer
- **Stacked layout** on small screens
- **Touch-friendly** buttons and dropdowns

---

### 10. Dark Mode Support

- **Auto-detects** dark mode from Alpine.js context
- **Glassmorphism** effects in both modes
- **Proper contrast** for accessibility
- **Smooth transitions** between themes

---

## Data Structure

### Example Data Array

```php
$articles = [
    [
        'id' => 1,
        'image' => 'https://example.com/image.jpg',
        'title' => 'Article Title',
        'category' => 'Seerah',
        'author' => 'Dr. Ahmed Hassan',
        'status' => 'published',
        'views' => 15420,
        'featured' => true,
        'created_at' => '2024-01-15T10:30:00',
    ],
    // More rows...
];
```

### Laravel Collection

```php
$articles = Article::select([
    'id',
    'thumbnail as image',
    'title',
    'category_name as category',
    'author_name as author',
    'status',
    'views_count as views',
    'is_featured as featured',
    'created_at',
])->get();
```

---

## Styling & Design

### Color Scheme

#### Primary Gradient (Blue-Green)
```css
background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
```

#### Accent Color (Teal)
```css
color: #64CCC5;
```

#### Badge Colors
```php
'published' => 'from-green-500 to-emerald-600',
'draft' => 'from-gray-500 to-gray-600',
'pending' => 'from-yellow-500 to-orange-600',
'archived' => 'from-red-500 to-rose-600',
```

### Design Patterns

1. **Glassmorphism**: Backdrop blur on headers and dropdowns
2. **Hover Effects**: Scale (1.05-1.1) and shadow on interactive elements
3. **Gradient Text**: Applied to numbers and headings
4. **Alternating Rows**: Subtle background color differences
5. **Shadow Hierarchy**: `shadow-lg`, `shadow-xl`, `shadow-2xl`

---

## JavaScript API

### Alpine.js Component

```javascript
function dataTable() {
    return {
        // Data
        rawData: [...],              // Original data
        filteredData: [],            // After search/filter
        paginatedData: [],           // Current page data

        // Selection
        selectedRows: [],            // Array of selected IDs
        selectAll: false,            // Select all checkbox state

        // Sorting
        sortColumn: null,            // Current sort column
        sortDirection: 'asc',        // 'asc' or 'desc'

        // Pagination
        currentPage: 1,              // Current page number
        perPage: 10,                 // Rows per page
        totalPages: 1,               // Total page count

        // Search
        searchQuery: '',             // Search input value

        // UI State
        loading: false,              // Loading indicator

        // Methods
        init(),                      // Initialize component
        filterData(),                // Apply search filter
        sortBy(column),              // Sort by column
        updatePagination(),          // Recalculate pages
        changePage(page),            // Navigate to page
        toggleSelectAll(),           // Toggle all checkboxes
        toggleRowSelection(id),      // Toggle single row
        clearSelection(),            // Clear all selections
        bulkDelete(),                // Delete selected rows
        bulkPublish(),               // Publish selected rows
        exportSelected(),            // Export selected rows
        exportData(format),          // Export all data
        downloadCSV(data, filename), // Download CSV file
    };
}
```

### Computed Properties

```javascript
get pageNumbers() {
    // Returns array of visible page numbers (max 5)
}

get startIndex() {
    // Returns first row index on current page
}

get endIndex() {
    // Returns last row index on current page
}
```

---

## Accessibility

### Keyboard Navigation

- **Tab**: Navigate through interactive elements
- **Enter/Space**: Activate buttons and checkboxes
- **Arrow Keys**: Navigate dropdown menus

### Screen Readers

- Semantic HTML (`<table>`, `<thead>`, `<tbody>`)
- Proper ARIA labels on buttons
- Alt text on images
- Status announcements on actions

### Color Contrast

- **WCAG AA compliant** color ratios
- **Dark mode** optimized contrast
- **Focus indicators** on all interactive elements

---

## Performance Optimizations

1. **Debounced Search**: 300ms delay prevents excessive filtering
2. **Pagination**: Only renders visible rows
3. **Virtual Scrolling**: (Future enhancement)
4. **Lazy Icon Loading**: Lucide icons initialized after render
5. **Efficient Sorting**: In-place array sorting

---

## Browser Compatibility

- **Chrome**: 90+
- **Firefox**: 88+
- **Safari**: 14+
- **Edge**: 90+

**Required Features:**
- CSS Grid & Flexbox
- ES6 JavaScript
- Alpine.js 3.x
- Tailwind CSS 3.x

---

## Customization

### Custom Badge Colors

```php
'badgeColors' => [
    'approved' => 'from-blue-500 to-indigo-600',
    'rejected' => 'from-red-500 to-pink-600',
    'processing' => 'from-purple-500 to-violet-600',
]
```

### Custom Empty State

```blade
<x-admin.data-table ... >
    <x-slot name="empty">
        <div class="text-center py-12">
            <h3>Custom Empty State</h3>
            <p>Your custom message here</p>
        </div>
    </x-slot>
</x-admin.data-table>
```

### Custom Actions

Add custom action buttons in the dropdown:

```blade
<!-- In table-row.blade.php -->
@if(in_array('custom', $actions))
    <button @click="customAction(row.id)"
            class="w-full flex items-center gap-3 px-4 py-3">
        <i data-lucide="star" class="w-5 h-5 text-yellow-500"></i>
        <span>Custom Action</span>
    </button>
@endif
```

---

## Troubleshooting

### Icons Not Showing

**Problem:** Lucide icons not rendering
**Solution:** Call `lucide.createIcons()` after data updates

```javascript
this.$nextTick(() => lucide.createIcons());
```

### Sorting Not Working

**Problem:** Column not sorting
**Solution:** Ensure `sortable => true` in column config

### Dark Mode Not Working

**Problem:** Dark mode colors not applying
**Solution:** Ensure parent component has Alpine.js dark mode setup

```html
<html x-data="{ darkMode: true }" :class="darkMode ? 'dark' : ''">
```

### Routes Not Working

**Problem:** Edit/Delete links return 404
**Solution:** Check route pattern includes `{id}` placeholder

```php
edit-route="/admin/articles/{id}/edit"  // Correct
edit-route="/admin/articles/edit"       // Wrong
```

---

## Advanced Examples

### With Filters

```blade
<div class="flex gap-4 mb-6">
    <select x-model="statusFilter" @change="applyFilters">
        <option value="">All Status</option>
        <option value="published">Published</option>
        <option value="draft">Draft</option>
    </select>
</div>

<x-admin.data-table :columns="$columns" :data="$articles" />
```

### With Custom Header

```blade
<x-admin.data-table :columns="$columns" :data="$articles">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold">Custom Header</h2>
        <button>Add New</button>
    </div>
</x-admin.data-table>
```

### With Server-Side Pagination

```php
// Controller
$articles = Article::paginate(10);

return view('admin.articles.index', [
    'articles' => $articles->items(),
    'pagination' => [
        'current_page' => $articles->currentPage(),
        'total' => $articles->total(),
        'per_page' => $articles->perPage(),
    ]
]);
```

---

## Best Practices

1. **Keep columns under 10** for better UX
2. **Use truncate** for long text columns
3. **Optimize images** before displaying
4. **Implement server-side search** for large datasets (>1000 rows)
5. **Use badges sparingly** (1-2 per row max)
6. **Test with empty data** to ensure proper empty states
7. **Provide meaningful labels** for accessibility
8. **Test dark mode** thoroughly

---

## Future Enhancements

- [ ] Server-side pagination API integration
- [ ] Advanced filters sidebar
- [ ] Column visibility toggle
- [ ] Column reordering (drag & drop)
- [ ] Row detail expansion
- [ ] Inline editing
- [ ] Multi-column sorting
- [ ] Virtual scrolling for 10K+ rows
- [ ] Export to Excel/PDF (functional)
- [ ] Saved filter presets
- [ ] Column resizing
- [ ] Sticky columns

---

## Support

For issues or questions:
- Check this documentation first
- Review the demo page: `/admin/data-table-demo`
- Inspect browser console for errors
- Ensure Alpine.js and Lucide are loaded

---

## License

Part of **The Last Messenger** project. All rights reserved.

---

**Last Updated:** 2024-11-07
**Version:** 1.0.0
**Component Path:** `/resources/views/components/admin/data-table.blade.php`
