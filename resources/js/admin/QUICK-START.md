# Categories & Tags Components - Quick Start

## Files Created

1. **category-tree.js** (22KB) - Sortable hierarchical tree with drag-and-drop
2. **icon-picker.js** (25KB) - Searchable icon picker modal (100+ Lucide icons)
3. **color-picker.js** (19KB) - Color picker with presets and recent colors
4. **tag-autocomplete.js** (23KB) - Multi-select tag autocomplete with inline creation

## Quick Setup

### 1. Add SortableJS Dependency

Add to your layout file (only needed for Category Tree):

```html
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
```

### 2. Include Components in app.js

Edit `/Volumes/Data/work/projects/the-last-massenger/the-last-messenger-be/resources/js/admin/app.js`:

```javascript
// Import category/tag components
import './category-tree.js';
import './icon-picker.js';
import './color-picker.js';
import './tag-autocomplete.js';
```

### 3. Basic Usage Examples

#### Category Tree (Index Page)
```html
<div id="category-tree"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    new CategoryTree(document.getElementById('category-tree'), {
        apiUrl: '/admin/categories'
    });
});
</script>
```

#### Icon Picker (Create/Edit Form)
```html
<input type="hidden" name="icon" id="category-icon" value="folder">
<button type="button" id="icon-picker-trigger">Choose Icon</button>
<div id="icon-preview"></div>

<script>
new IconPicker(document.getElementById('category-icon'), {
    trigger: document.getElementById('icon-picker-trigger'),
    preview: document.getElementById('icon-preview')
});
</script>
```

#### Color Picker (Create/Edit Form)
```html
<input type="text" name="color" id="category-color" value="#0056b3" class="hidden">
<button type="button" id="color-picker-trigger">Choose Color</button>
<div id="color-preview"></div>

<script>
new ColorPicker(document.getElementById('category-color'), {
    trigger: document.getElementById('color-picker-trigger'),
    preview: document.getElementById('color-preview')
});
</script>
```

#### Tag Autocomplete (Create/Edit Form)
```html
<div id="tag-autocomplete-container"></div>

<script>
new TagAutocomplete(document.getElementById('tag-autocomplete-container'), {
    inputName: 'tags',
    apiUrl: '/admin/tags/search'
});
</script>
```

## API Endpoints Required

### Category Tree
- `GET /admin/categories/tree` - Get hierarchical tree
- `POST /admin/categories/reorder` - Update position/parent
- `DELETE /admin/categories/{id}` - Delete category

### Tag Autocomplete
- `GET /admin/tags/search?q={query}` - Search tags

## Key Features

### Category Tree
- Drag to reorder within same level
- Drag to nest under another category
- Max depth: 5 levels (configurable)
- Expand/collapse with state persistence
- Real-time AJAX updates

### Icon Picker
- 100+ popular Lucide icons
- 12 categories (UI, Content, Navigation, etc.)
- Search with debouncing
- Live preview

### Color Picker
- 22 preset colors (blue-green theme)
- Custom HEX input
- Recent colors (max 10)
- Copy to clipboard

### Tag Autocomplete
- Debounced search (300ms)
- Create new tags inline
- Multi-select with pills
- Keyboard navigation (arrows, enter, backspace)
- Duplicate prevention

## All Components Support

- Dark/Light mode (automatic from Alpine store)
- RTL layouts
- Keyboard navigation
- Error handling with toast notifications
- LocalStorage for state persistence
- Mobile touch support
- Smooth animations

## Styling

All components use Tailwind CSS classes and follow the project's blue-green gradient design system:

- Primary: `#0056b3` (blue) to `#218838` (green)
- Accent: `#64CCC5` (teal)
- Gradients: `from-[#0056b3] to-[#218838]`

## Testing Checklist

- [ ] Category tree loads and displays hierarchy
- [ ] Drag and drop works (reorder and nest)
- [ ] Expand/collapse state persists
- [ ] Icon picker opens and icons are searchable
- [ ] Color picker shows presets and accepts custom colors
- [ ] Tag autocomplete searches and creates new tags
- [ ] All components work in dark mode
- [ ] RTL layout works correctly
- [ ] Mobile touch interactions work
- [ ] AJAX calls succeed with proper CSRF token

## Common Issues

**Icons not showing:** Run `lucide.createIcons()` after dynamic content updates

**CSRF errors:** Ensure `<meta name="csrf-token" content="{{ csrf_token() }}">` in layout

**SortableJS not found:** Add CDN script before initializing CategoryTree

**Dark mode toggle not working:** Check Alpine store is initialized

## Documentation

Full documentation: `/Volumes/Data/work/projects/the-last-massenger/the-last-messenger-be/resources/js/admin/CATEGORIES-TAGS-COMPONENTS.md`
