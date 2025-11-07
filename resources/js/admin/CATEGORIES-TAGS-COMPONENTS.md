# Categories & Tags JavaScript Components

Advanced interactivity components for the Categories & Tags module with drag-and-drop, autocomplete, and picker functionality.

## Components Overview

### 1. Category Tree (`category-tree.js`)
Sortable, drag-and-drop hierarchical tree component using SortableJS.

### 2. Icon Picker (`icon-picker.js`)
Searchable modal for selecting Lucide icons with category grouping.

### 3. Color Picker (`color-picker.js`)
Color picker with preset palette, custom input, and recent colors.

### 4. Tag Autocomplete (`tag-autocomplete.js`)
Autocomplete component for tag selection with multi-select and inline creation.

---

## 1. Category Tree Component

### Features
- Drag and drop to reorder categories
- Drag to change parent (nest/unnest)
- Visual feedback during drag (ghost element, drop zones)
- Expand/collapse nodes with localStorage persistence
- AJAX calls to update server after reorder
- Smooth animations
- RTL support
- Touch support for mobile
- Maximum depth validation (prevents nesting beyond 5 levels)

### Dependencies
```html
<!-- Add to your layout -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
```

### Usage

#### HTML Structure
```html
<div id="category-tree" class="p-6"></div>
```

#### JavaScript Initialization
```javascript
document.addEventListener('DOMContentLoaded', () => {
    const tree = new CategoryTree(document.getElementById('category-tree'), {
        maxDepth: 5,
        apiUrl: '/admin/categories',
        animation: true,
        animationDuration: 300,
        onReorder: (data) => {
            console.log('Category reordered:', data);
        },
        onError: (error) => {
            console.error('Error:', error);
        }
    });
});
```

### API Endpoints Required

#### GET `/admin/categories/tree`
Returns hierarchical category tree.

**Response:**
```json
{
  "categories": [
    {
      "id": 1,
      "name": "Islamic History",
      "description": "Historical events and context",
      "icon": "book",
      "color": "#0056b3",
      "articles_count": 15,
      "children": [
        {
          "id": 3,
          "name": "Early Islamic Period",
          "icon": "calendar",
          "color": "#218838",
          "articles_count": 8,
          "children": []
        }
      ]
    }
  ]
}
```

#### POST `/admin/categories/reorder`
Updates category position and parent.

**Request:**
```json
{
  "id": 3,
  "parent_id": 1,
  "position": 0
}
```

**Response:**
```json
{
  "success": true,
  "message": "Category updated successfully"
}
```

#### DELETE `/admin/categories/{id}`
Deletes a category.

**Response:**
```json
{
  "success": true,
  "message": "Category deleted successfully"
}
```

### Methods

```javascript
// Refresh the tree
tree.refresh();

// Destroy the component
tree.destroy();
```

### CSS Customization
```css
/* Ghost element during drag */
.sortable-ghost {
    opacity: 0.4;
    background-color: #e3f2fd;
}

/* Chosen element being dragged */
.sortable-chosen {
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
}

/* Element being dragged */
.sortable-drag {
    opacity: 0;
}
```

---

## 2. Icon Picker Component

### Features
- Modal with searchable icon grid (100+ popular Lucide icons)
- Search filter (debounced 300ms)
- Click to select
- Preview selected icon
- Category grouping (UI, Content, Navigation, Communication, Users, Actions, Settings, Status, Time, Technology, Religious)
- Keyboard navigation (Escape to close)
- RTL support

### Usage

#### HTML Structure
```html
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Category Icon
    </label>

    <div class="flex items-center gap-4">
        <!-- Hidden input to store icon name -->
        <input type="hidden" name="icon" id="category-icon" value="folder">

        <!-- Trigger button -->
        <button
            type="button"
            id="icon-picker-trigger"
            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
        >
            Choose Icon
        </button>

        <!-- Preview container -->
        <div id="icon-preview"></div>
    </div>
</div>
```

#### JavaScript Initialization
```javascript
document.addEventListener('DOMContentLoaded', () => {
    const picker = new IconPicker(document.getElementById('category-icon'), {
        trigger: document.getElementById('icon-picker-trigger'),
        preview: document.getElementById('icon-preview'),
        onSelect: (iconName) => {
            console.log('Icon selected:', iconName);
        }
    });
});
```

### Available Icon Categories

- **UI**: folder, file, book, bookmark, tag, star, heart, flag
- **Content**: image, video, music, film, camera, mic, headphones
- **Navigation**: home, menu, compass, map, map-pin, navigation
- **Communication**: mail, message-square, phone, bell
- **Users**: user, users, user-plus, user-check, user-x
- **Actions**: plus, minus, x, check, edit, trash-2, copy, save, download, upload, search, filter, refresh-cw, share-2
- **Settings**: settings, sliders, toggle-left, lock, unlock, eye, eye-off
- **Status**: info, alert-circle, alert-triangle, help-circle, check-circle-2, x-circle
- **Time**: calendar, clock, calendar-days
- **Technology**: smartphone, tablet, laptop, monitor, wifi, globe, link, database, server, cloud
- **Religious**: book-marked, bookmark-plus, library, award, crown

### Methods

```javascript
// Open the modal
picker.open();

// Close the modal
picker.close();

// Destroy the component
picker.destroy();
```

---

## 3. Color Picker Component

### Features
- Preset palette (22 colors in blue-green theme)
- Custom color input (HEX format with validation)
- Live preview
- Recently used colors (localStorage, max 10)
- Copy hex to clipboard
- Dropdown interface
- RTL support

### Usage

#### HTML Structure
```html
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Category Color
    </label>

    <div class="flex items-center gap-4">
        <!-- Input to store color -->
        <input
            type="text"
            name="color"
            id="category-color"
            value="#0056b3"
            class="hidden"
        >

        <!-- Trigger button -->
        <button
            type="button"
            id="color-picker-trigger"
            class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
        >
            Choose Color
        </button>

        <!-- Preview container -->
        <div id="color-preview"></div>
    </div>
</div>
```

#### JavaScript Initialization
```javascript
document.addEventListener('DOMContentLoaded', () => {
    const picker = new ColorPicker(document.getElementById('category-color'), {
        trigger: document.getElementById('color-picker-trigger'),
        preview: document.getElementById('color-preview'),
        onSelect: (color) => {
            console.log('Color selected:', color);
        }
    });
});
```

### Preset Colors

**Primary Colors:**
- `#0056b3` - Primary Blue
- `#218838` - Primary Green
- `#64CCC5` - Accent Teal

**Blue Variations:**
- `#003d82` - Dark Blue
- `#0069d9` - Medium Blue
- `#4A90E2` - Light Blue
- `#ADD8E6` - Sky Blue

**Green Variations:**
- `#155724` - Dark Green
- `#28a745` - Medium Green
- `#5CB85C` - Light Green
- `#90EE90` - Pale Green

**Teal/Cyan:**
- `#17a2b8` - Cyan
- `#20C997` - Teal
- `#40E0D0` - Turquoise

**Accent Colors:**
- `#6610f2` - Purple
- `#E83E8C` - Pink
- `#fd7e14` - Orange
- `#ffc107` - Yellow
- `#dc3545` - Red

### Methods

```javascript
// Select a color programmatically
picker.selectColor('#0056b3');

// Open the dropdown
picker.open();

// Close the dropdown
picker.close();

// Toggle dropdown
picker.toggle();

// Copy color to clipboard
picker.copyToClipboard('#0056b3');

// Clear recent colors
picker.clearRecentColors();

// Destroy the component
picker.destroy();
```

---

## 4. Tag Autocomplete Component

### Features
- Search tags as user types (debounced AJAX)
- Create new tag inline
- Multi-select (tag pills with gradient background)
- Keyboard navigation (Arrow keys, Enter, Escape, Backspace)
- Remove tags with X button or backspace
- Visual feedback on hover/focus
- RTL support
- Duplicate prevention

### Usage

#### HTML Structure
```html
<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Tags
    </label>

    <!-- Container for the autocomplete component -->
    <div id="tag-autocomplete-container"></div>
</div>
```

#### JavaScript Initialization
```javascript
document.addEventListener('DOMContentLoaded', () => {
    const tagAutocomplete = new TagAutocomplete(
        document.getElementById('tag-autocomplete-container'),
        {
            inputName: 'tags',
            selectedTags: [
                { id: 1, name: 'Islamic History' },
                { id: 2, name: 'Hadith' }
            ],
            apiUrl: '/admin/tags/search',
            searchDelay: 300,
            minSearchLength: 2,
            allowCreate: true,
            onTagAdded: (tag) => {
                console.log('Tag added:', tag);
            },
            onTagRemoved: (tag) => {
                console.log('Tag removed:', tag);
            }
        }
    );
});
```

### API Endpoint Required

#### GET `/admin/tags/search?q={query}`
Returns matching tags.

**Response:**
```json
{
  "tags": [
    {
      "id": 1,
      "name": "Islamic History",
      "articles_count": 15
    },
    {
      "id": 2,
      "name": "Hadith",
      "articles_count": 23
    }
  ]
}
```

### Methods

```javascript
// Get selected tags
const tags = tagAutocomplete.getSelectedTags();

// Set selected tags programmatically
tagAutocomplete.setSelectedTags([
    { id: 1, name: 'Tag 1' },
    { id: 2, name: 'Tag 2' }
]);

// Clear all tags
tagAutocomplete.clearTags();

// Destroy the component
tagAutocomplete.destroy();
```

### Keyboard Navigation

- **Arrow Down**: Focus next result
- **Arrow Up**: Focus previous result
- **Enter**: Select focused result or create new tag
- **Escape**: Close dropdown
- **Backspace** (when input is empty): Remove last tag

### Form Submission

The component creates a hidden input with JSON value:

```json
[
  {
    "id": 1,
    "name": "Islamic History",
    "isNew": false
  },
  {
    "id": null,
    "name": "New Tag",
    "isNew": true
  }
]
```

Handle in Laravel controller:
```php
$tagsData = json_decode($request->input('tags'), true);

foreach ($tagsData as $tagData) {
    if ($tagData['isNew']) {
        // Create new tag
        $tag = Tag::create(['name' => $tagData['name']]);
        $tagIds[] = $tag->id;
    } else {
        $tagIds[] = $tagData['id'];
    }
}

$article->tags()->sync($tagIds);
```

---

## Complete Integration Example

### Blade Template (create.blade.php)

```html
@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create Category</h1>

    <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Category Name
            </label>
            <input
                type="text"
                name="name"
                required
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-600"
            >
        </div>

        <!-- Icon Picker -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Icon
            </label>
            <div class="flex items-center gap-4">
                <input type="hidden" name="icon" id="category-icon" value="folder">
                <button
                    type="button"
                    id="icon-picker-trigger"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                >
                    Choose Icon
                </button>
                <div id="icon-preview"></div>
            </div>
        </div>

        <!-- Color Picker -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Color
            </label>
            <div class="flex items-center gap-4">
                <input type="text" name="color" id="category-color" value="#0056b3" class="hidden">
                <button
                    type="button"
                    id="color-picker-trigger"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                >
                    Choose Color
                </button>
                <div id="color-preview"></div>
            </div>
        </div>

        <!-- Tag Autocomplete -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Tags
            </label>
            <div id="tag-autocomplete-container"></div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-4">
            <a
                href="{{ route('admin.categories.index') }}"
                class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-shadow"
            >
                Create Category
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<!-- SortableJS (only needed for category tree) -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Icon Picker
    new IconPicker(document.getElementById('category-icon'), {
        trigger: document.getElementById('icon-picker-trigger'),
        preview: document.getElementById('icon-preview')
    });

    // Color Picker
    new ColorPicker(document.getElementById('category-color'), {
        trigger: document.getElementById('color-picker-trigger'),
        preview: document.getElementById('color-preview')
    });

    // Tag Autocomplete
    new TagAutocomplete(document.getElementById('tag-autocomplete-container'), {
        inputName: 'tags',
        apiUrl: '/admin/tags/search',
        allowCreate: true
    });
});
</script>
@endpush
```

### Index Page with Category Tree

```html
@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categories</h1>
        <a
            href="{{ route('admin.categories.create') }}"
            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-shadow"
        >
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>Create Category</span>
        </a>
    </div>

    <!-- Category Tree -->
    <div id="category-tree"></div>
</div>
@endsection

@push('scripts')
<!-- SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tree = new CategoryTree(document.getElementById('category-tree'), {
        maxDepth: 5,
        apiUrl: '/admin/categories',
        onReorder: (data) => {
            console.log('Category reordered:', data);
        }
    });
});
</script>
@endpush
```

---

## Browser Compatibility

All components are compatible with:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Performance Notes

- **Debouncing**: Search inputs are debounced (300ms) to reduce API calls
- **LocalStorage**: Expanded states and recent colors are persisted
- **Lazy Loading**: Icons are initialized only when needed
- **Memory Management**: All components have `destroy()` methods for cleanup

## Accessibility

- Keyboard navigation supported
- ARIA labels on interactive elements
- Focus management in modals
- Screen reader friendly

## RTL Support

All components support RTL layouts automatically by detecting:
```javascript
document.documentElement.getAttribute('dir') === 'rtl'
```

## Troubleshooting

### Icons not appearing
Ensure Lucide icons are initialized:
```javascript
if (window.lucide && window.lucide.createIcons) {
    window.lucide.createIcons();
}
```

### Drag and drop not working
Check that SortableJS is loaded:
```html
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
```

### AJAX requests failing
Ensure CSRF token is included:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### Dark mode not working
Check that Alpine store is initialized and dark mode classes are applied:
```javascript
console.log(Alpine.store('admin').darkMode);
```
