# Media Picker Component - Integration Guide

## Overview

A comprehensive, reusable media selection modal system with tabbed interface for browsing and uploading media. Built with Alpine.js, Tailwind CSS, and Laravel Blade components.

## File Structure

```
resources/
├── views/
│   └── components/
│       └── admin/
│           ├── media-picker-modal.blade.php          # Main modal component
│           └── selected-media-preview.blade.php      # Selected items preview
├── js/
│   └── admin/
│       └── media-picker.js                           # JavaScript API
└── views/
    └── admin/
        └── examples/
            └── media-picker-demo.blade.php           # Integration examples
```

## Features

### Core Features
- **Tabbed Interface**: Browse existing media or upload new files
- **Selection Modes**: Single or multiple selection
- **Type Filtering**: Filter by image, video, or all types
- **Search**: Real-time search by filename
- **Upload**: Drag-and-drop or click to upload with progress tracking
- **Preview Panel**: Live preview of selected items with metadata
- **Responsive**: Mobile-optimized with touch support
- **Dark/Light Mode**: Full theme support

### Advanced Features
- **Lazy Loading**: Pagination with "Load More" button
- **Glassmorphism UI**: Modern backdrop blur effects
- **Netflix-style Grid**: Premium card-based layout
- **Smooth Animations**: Enter/exit transitions and hover effects
- **Keyboard Support**: ESC to close modal
- **Event System**: Custom events for integration
- **Auto-selection**: Newly uploaded files auto-select in single mode

## Quick Start

### 1. Add Media Picker to Your Layout

Add the modal component before the closing `</body>` tag in your admin layout:

```blade
<!-- resources/views/layouts/admin.blade.php -->
<x-admin.media-picker-modal />

@stack('scripts')
</body>
</html>
```

### 2. Include JavaScript API (Optional)

If you want to use the standalone JavaScript API, include it in your admin layout:

```blade
<!-- resources/views/layouts/admin.blade.php -->
@push('scripts')
<script src="{{ asset('js/admin/media-picker.js') }}"></script>
@endpush
```

### 3. Use in Your Views

```blade
<!-- Simple button integration -->
<button onclick="MediaPicker.open({
    mode: 'single',
    type: 'image',
    onSelect: (media) => {
        document.getElementById('featured_image_id').value = media.id;
        document.getElementById('preview').src = media.url;
    }
})">
    Choose Featured Image
</button>

<input type="hidden" id="featured_image_id" name="featured_image_id">
<img id="preview" class="w-32 h-32 object-cover rounded-lg">
```

## JavaScript API

### Global API

The `MediaPicker` object is available globally:

```javascript
window.MediaPicker = {
    open(options),      // Open the modal
    close(),            // Close the modal
    getSelected()       // Get currently selected items
}
```

### Options Object

```javascript
{
    mode: 'single',           // 'single' or 'multiple'
    type: 'all',              // 'all', 'image', 'video'
    onSelect: (media) => {},  // Callback when items are selected
    onClose: () => {},        // Callback when modal is closed
    collection: 'gallery',    // Filter by collection (optional)
    maxSelection: 10          // Max items in multiple mode (optional)
}
```

## Usage Examples

### Example 1: Single Image Selection

```blade
<div x-data="{ selectedImage: null }">
    <button @click="MediaPicker.open({
        mode: 'single',
        type: 'image',
        onSelect: (media) => {
            selectedImage = media;
        }
    })"
            class="btn btn-primary">
        Choose Image
    </button>

    <div x-show="selectedImage">
        <img :src="selectedImage?.url" class="w-32 h-32 object-cover rounded-lg">
        <p x-text="selectedImage?.original_filename"></p>
    </div>
</div>
```

### Example 2: Multiple Images (Gallery Builder)

```blade
<div x-data="{ galleryImages: [] }">
    <button @click="MediaPicker.open({
        mode: 'multiple',
        type: 'image',
        onSelect: (images) => {
            galleryImages = images;
        }
    })">
        Build Gallery
    </button>

    <div class="grid grid-cols-4 gap-4">
        <template x-for="image in galleryImages" :key="image.id">
            <img :src="image.url" class="w-full aspect-square object-cover rounded-lg">
        </template>
    </div>
</div>
```

### Example 3: Form Integration with Hidden Input

```blade
<form action="/articles/store" method="POST" x-data="{ featuredImage: null }">
    @csrf

    <!-- Hidden input for form submission -->
    <input type="hidden"
           name="featured_image_id"
           :value="featuredImage?.id">

    <!-- Trigger button -->
    <button type="button"
            @click="MediaPicker.open({
                mode: 'single',
                type: 'image',
                onSelect: (media) => {
                    featuredImage = media;
                }
            })"
            class="btn btn-secondary">
        <i data-lucide="image-plus"></i>
        Choose Featured Image
    </button>

    <!-- Preview -->
    <div x-show="featuredImage" class="mt-3">
        <div class="relative inline-block">
            <img :src="featuredImage?.url"
                 class="w-32 h-32 object-cover rounded-lg">
            <button type="button"
                    @click="featuredImage = null"
                    class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        Save Article
    </button>
</form>
```

### Example 4: Video Selection

```blade
<div x-data="{ selectedVideo: null }">
    <button @click="MediaPicker.open({
        mode: 'single',
        type: 'video',
        onSelect: (media) => {
            selectedVideo = media;
        }
    })">
        Choose Video
    </button>

    <div x-show="selectedVideo">
        <video :src="selectedVideo?.url" controls class="w-full max-w-2xl"></video>
    </div>
</div>
```

### Example 5: Vanilla JavaScript (No Alpine.js)

```html
<button id="choose-btn">Choose Media</button>
<div id="preview"></div>

<script>
document.getElementById('choose-btn').addEventListener('click', () => {
    MediaPicker.open({
        mode: 'single',
        type: 'image',
        onSelect: (media) => {
            document.getElementById('preview').innerHTML = `
                <img src="${media.url}" class="w-32 h-32 object-cover rounded-lg">
            `;
        }
    });
});
</script>
```

### Example 6: Multiple Selection with Hidden Inputs

```blade
<form x-data="{ selectedMedia: [] }">
    <!-- Hidden inputs for each selected item -->
    <template x-for="(media, index) in selectedMedia" :key="media.id">
        <input type="hidden" name="media_ids[]" :value="media.id">
    </template>

    <button type="button" @click="MediaPicker.open({
        mode: 'multiple',
        type: 'all',
        onSelect: (mediaArray) => {
            selectedMedia = mediaArray;
        }
    })">
        Select Media
    </button>

    <div class="flex gap-2 mt-3">
        <template x-for="media in selectedMedia" :key="media.id">
            <img :src="media.url" class="w-20 h-20 object-cover rounded">
        </template>
    </div>
</form>
```

### Example 7: With Event Listeners

```javascript
// Listen for media selection events
window.addEventListener('media-selected', (event) => {
    console.log('Media selected:', event.detail);
});

// Open picker
MediaPicker.open({
    mode: 'single',
    type: 'image',
    onSelect: (media) => {
        console.log('Selected via callback:', media);
    }
});
```

### Example 8: jQuery Integration

```javascript
// Attach media picker to button
$('#choose-media-btn').mediaPicker({
    mode: 'multiple',
    type: 'image',
    onSelect: function(selected) {
        console.log('Selected:', selected);
    }
});

// Listen for selection event
$('#choose-media-btn').on('media-selected', function(e, selected) {
    console.log('Media selected:', selected);
});
```

## Integration with Existing Forms

### Article Create/Edit Forms

```blade
<!-- resources/views/admin/articles/create.blade.php -->
@extends('layouts.admin')

@section('content')
<form action="{{ route('admin.articles.store') }}" method="POST"
      x-data="{
          featuredImage: null,
          galleryImages: []
      }">
    @csrf

    <!-- Title -->
    <div class="mb-6">
        <label class="block mb-2 font-semibold">Title</label>
        <input type="text" name="title" class="form-input w-full">
    </div>

    <!-- Featured Image -->
    <div class="mb-6">
        <label class="block mb-2 font-semibold">Featured Image</label>

        <input type="hidden" name="featured_image_id" :value="featuredImage?.id">

        <button type="button"
                @click="MediaPicker.open({
                    mode: 'single',
                    type: 'image',
                    onSelect: (media) => { featuredImage = media; }
                })"
                class="px-4 py-2 border-2 border-dashed rounded-lg">
            <i data-lucide="image-plus"></i>
            Choose Featured Image
        </button>

        <div x-show="featuredImage" class="mt-3">
            <div class="relative inline-block">
                <img :src="featuredImage?.url" class="w-32 h-32 object-cover rounded-lg">
                <button type="button"
                        @click="featuredImage = null"
                        class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Gallery Images -->
    <div class="mb-6">
        <label class="block mb-2 font-semibold">Gallery Images</label>

        <template x-for="(image, index) in galleryImages" :key="image.id">
            <input type="hidden" name="gallery_images[]" :value="image.id">
        </template>

        <button type="button"
                @click="MediaPicker.open({
                    mode: 'multiple',
                    type: 'image',
                    onSelect: (images) => { galleryImages = images; }
                })"
                class="px-4 py-2 border-2 border-dashed rounded-lg">
            <i data-lucide="images"></i>
            Choose Gallery Images
        </button>

        <div x-show="galleryImages.length > 0" class="mt-3 flex flex-wrap gap-2">
            <template x-for="(image, index) in galleryImages" :key="image.id">
                <div class="relative">
                    <img :src="image.url" class="w-20 h-20 object-cover rounded-lg">
                    <button type="button"
                            @click="galleryImages.splice(index, 1)"
                            class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-red-500 text-white">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Content -->
    <div class="mb-6">
        <label class="block mb-2 font-semibold">Content</label>
        <textarea name="content" rows="10" class="form-input w-full"></textarea>
    </div>

    <!-- Submit -->
    <button type="submit" class="btn btn-primary">
        Create Article
    </button>
</form>

<!-- Include Media Picker Modal -->
<x-admin.media-picker-modal />
@endsection
```

## Media Object Structure

When a media item is selected, it returns an object with the following structure:

```javascript
{
    id: 123,
    filename: "202311071234_image.jpg",
    original_filename: "my-photo.jpg",
    path: "media/images/202311071234_image.jpg",
    url: "https://example.com/storage/media/images/202311071234_image.jpg",
    mime_type: "image/jpeg",
    type: "image",
    size: 524288,                    // bytes
    width: 1920,                     // pixels (images only)
    height: 1080,                    // pixels (images only)
    alt_text: "Description text",
    caption: "Photo caption",
    collection_name: "articles",
    disk: "public",
    metadata: {                      // additional data
        // custom metadata
    },
    created_at: "2023-11-07T10:30:00.000000Z",
    updated_at: "2023-11-07T10:30:00.000000Z"
}
```

## Customization

### Styling

The component uses Tailwind CSS with dark mode support. All colors and styles can be customized by modifying the component files:

- Primary gradient: `from-[#0056b3] to-[#218838]`
- Accent color: `#64CCC5`
- Dark mode: Automatically follows admin layout dark mode

### Modal Size

To change the modal size, modify the max-width class in `media-picker-modal.blade.php`:

```blade
<!-- Default: max-w-7xl -->
<div class="relative w-full max-w-7xl">

<!-- Full width -->
<div class="relative w-full max-w-full">

<!-- Smaller -->
<div class="relative w-full max-w-4xl">
```

### Upload Configuration

Upload settings can be modified in the component or via backend validation:

- **Max file size**: 100MB (configurable in `MediaController.php`)
- **Accepted types**: Based on filter type (image/video/all)
- **Upload endpoint**: `/admin/media` (POST)

### Grid Layout

Modify the grid columns in `media-picker-modal.blade.php`:

```blade
<!-- Default: responsive grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">

<!-- More items per row -->
<div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-3">
```

## Events

### Custom Events

The component dispatches custom events for integration:

```javascript
// Media selected
window.addEventListener('media-selected', (event) => {
    console.log('Selected:', event.detail);
});

// Picker opened
window.addEventListener('media-picker-open', (event) => {
    console.log('Options:', event.detail);
});

// Picker closed
window.addEventListener('media-picker-close', () => {
    console.log('Picker closed');
});
```

## Backend Requirements

### Route

Ensure the media route is registered:

```php
// routes/web.php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('media', MediaController::class);
});
```

### Controller Methods

The component expects these endpoints:

- `GET /admin/media` - List media (with pagination, search, filters)
- `POST /admin/media` - Upload new media
- `DELETE /admin/media/{id}` - Delete media

### Response Format

**GET /admin/media** (JSON response):

```json
{
    "data": [
        {
            "id": 1,
            "filename": "image.jpg",
            "url": "https://...",
            ...
        }
    ],
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
}
```

**POST /admin/media** (JSON response):

```json
{
    "success": true,
    "message": "Media uploaded successfully",
    "data": {
        "id": 1,
        "filename": "image.jpg",
        "url": "https://...",
        ...
    }
}
```

## Troubleshooting

### Modal Not Opening

1. Ensure Alpine.js is loaded
2. Check browser console for JavaScript errors
3. Verify `<x-admin.media-picker-modal />` is included in layout

### Icons Not Showing

Lucide icons need to be initialized after dynamic content:

```javascript
this.$nextTick(() => {
    lucide.createIcons();
});
```

### Upload Not Working

1. Check CSRF token is present: `<meta name="csrf-token" content="{{ csrf_token() }}">`
2. Verify route exists: `POST /admin/media`
3. Check file size and validation rules

### Dark Mode Issues

Ensure the root element has dark mode support:

```html
<html x-data="{ darkMode: localStorage.getItem('darkMode') !== 'false' }"
      :class="darkMode ? 'dark' : ''">
```

## Performance Optimization

### Lazy Loading

The component implements pagination with "Load More" button to avoid loading all media at once.

### Image Optimization

Consider implementing lazy loading for images in the grid:

```blade
<img :src="media.url"
     loading="lazy"
     class="w-full h-full object-cover">
```

### Debounced Search

Search is debounced to avoid excessive API calls:

```blade
<input x-model="searchQuery"
       @input.debounce.300ms="searchMedia()">
```

## Browser Support

- Chrome/Edge: Latest 2 versions
- Firefox: Latest 2 versions
- Safari: Latest 2 versions
- Mobile: iOS Safari, Chrome Android

## Accessibility

- Keyboard navigation (ESC to close)
- Focus management
- ARIA labels (can be added as needed)
- Screen reader support (can be enhanced)

## Future Enhancements

Potential features for future versions:

- [ ] Drag-and-drop reordering in preview panel
- [ ] Bulk actions (delete, move to collection)
- [ ] Advanced filters (date range, file size, dimensions)
- [ ] Image editing (crop, resize, rotate)
- [ ] Folder/collection management
- [ ] Favorite/starred media
- [ ] Media metadata editing in modal
- [ ] Keyboard shortcuts
- [ ] Virtual scrolling for large libraries

## Demo Page

A comprehensive demo page is available at:

```
resources/views/admin/examples/media-picker-demo.blade.php
```

Visit `/admin/examples/media-picker-demo` to see all integration examples.

## Summary

The Media Picker component provides a complete, production-ready solution for media management with:

- **Easy Integration**: Single component include, simple JavaScript API
- **Flexible**: Works with Alpine.js, vanilla JS, or jQuery
- **Feature-Rich**: Search, filter, upload, preview, drag-and-drop
- **Modern UI**: Glassmorphism, dark mode, smooth animations
- **Mobile-Optimized**: Responsive design with touch support
- **Performant**: Lazy loading, debounced search, efficient rendering

For questions or support, refer to the demo page or component source code.
