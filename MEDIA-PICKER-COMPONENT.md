# Media Picker Component Documentation

A reusable media picker component for selecting images from the media library across different pages and models.

## Installation

1. **Include the JavaScript file** in your layout:
```blade
<script src="{{ asset('js/media-picker.js') }}"></script>
```

## Usage

### Single Image Picker

For selecting a single image (e.g., featured image, profile photo):

```blade
<x-media-picker
    mode="single"
    name="featured_image_id"
    label="الصورة المميزة"
    :selected="$article->featuredImage ?? null"
/>
```

### Multiple Images Picker (Gallery)

For selecting multiple images (e.g., product gallery, article gallery):

```blade
<x-media-picker
    mode="multiple"
    name="gallery_ids"
    label="معرض الصور"
    :selected-items="$article->gallery ?? []"
/>
```

## Component Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `mode` | string | `'single'` | Selection mode: `'single'` or `'multiple'` |
| `name` | string | `'media_id'` | Form field name for submission |
| `label` | string | `'اختر من مكتبة الوسائط'` | Label text |
| `selected` | object\|null | `null` | Pre-selected media (single mode) |
| `selectedItems` | array | `[]` | Pre-selected media items (multiple mode) |
| `previewSize` | string | `'default'` | Preview size: `'default'`, `'large'`, `'small'` |

## Examples

### Example 1: Article Featured Image

```blade
<!-- In create.blade.php -->
<div class="sidebar-card">
    <h3 class="text-lg font-bold mb-4">الصورة المميزة</h3>

    <x-media-picker
        mode="single"
        name="featured_image_id"
        label="اختر الصورة المميزة"
        preview-size="default"
    />
</div>
```

### Example 2: Article Gallery

```blade
<!-- In article form -->
<div class="mb-6">
    <x-media-picker
        mode="multiple"
        name="gallery_images"
        label="معرض الصور"
        :selected-items="old('gallery_images', $article->gallery ?? [])"
    />
</div>
```

### Example 3: User Profile Photo

```blade
<!-- In user profile edit -->
<x-media-picker
    mode="single"
    name="avatar_id"
    label="صورة الملف الشخصي"
    preview-size="small"
    :selected="$user->avatar"
/>
```

### Example 4: Product Images

```blade
<!-- In product form -->
<x-media-picker
    mode="multiple"
    name="product_images"
    label="صور المنتج"
    :selected-items="$product->images ?? []"
/>
```

## Events

The component dispatches custom events that you can listen to:

```javascript
// Listen for media selection (single mode)
document.addEventListener('media-selected', (event) => {
    console.log('Selected media:', event.detail);
});

// Listen for media removal (single mode)
document.addEventListener('media-removed', (event) => {
    console.log('Media removed');
});

// Listen for media items selection (multiple mode)
document.addEventListener('media-items-selected', (event) => {
    console.log('Selected items:', event.detail);
});

// Listen for media item removal (multiple mode)
document.addEventListener('media-item-removed', (event) => {
    console.log('Removed item at index:', event.detail.index);
});
```

## Server-Side Handling

### Storing Single Media

```php
// In your controller
public function store(Request $request)
{
    $validated = $request->validate([
        'featured_image_id' => 'nullable|exists:media,id',
        // ... other fields
    ]);

    $article = Article::create($validated);

    return redirect()->route('admin.articles.index');
}
```

### Storing Multiple Media

```php
// In your controller
public function store(Request $request)
{
    $validated = $request->validate([
        'gallery_images' => 'nullable|array',
        'gallery_images.*' => 'exists:media,id',
        // ... other fields
    ]);

    $article = Article::create($validated);

    // Attach gallery images
    if ($request->has('gallery_images')) {
        $article->gallery()->sync($request->gallery_images);
    }

    return redirect()->route('admin.articles.index');
}
```

## Customization

### Custom Preview Size

Add custom classes in your blade file:

```blade
<x-media-picker
    mode="single"
    name="banner_id"
    preview-size="large"
/>
```

The `preview-size` property accepts:
- `default`: 16:9 aspect ratio
- `small`: Square (1:1) aspect ratio
- `large`: Fixed height of 384px (h-96)

### Styling

The component uses Tailwind CSS classes. You can override styles by adding custom CSS:

```css
.image-preview {
    /* Your custom styles */
}

.gradient-button {
    /* Custom button gradient */
}
```

## Features

✅ **Single & Multiple Selection**
✅ **Real-time Search**
✅ **Filter by Type** (image/video/document)
✅ **Filter by Date** (today/week/month)
✅ **Pagination** (12 items per page)
✅ **Loading States**
✅ **Empty States**
✅ **Preview with Remove Option**
✅ **Dark Mode Support**
✅ **RTL Layout**
✅ **Keyboard Navigation** (ESC to close)
✅ **Responsive Design**
✅ **File Size Display**
✅ **Visual Selection Feedback**

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Dependencies

- Alpine.js 3.x
- Tailwind CSS 3.x
- Laravel 10+

## Troubleshooting

### Modal doesn't open

Make sure you've included the JavaScript file:
```blade
<script src="{{ asset('js/media-picker.js') }}"></script>
```

### Images not loading

Check the API endpoint in `media-picker.js`:
```javascript
const response = await fetch('/su/portal/v1/admin/simple-media', {
    // ...
});
```

### Form submission not working

Ensure the `name` attribute is set correctly:
```blade
<x-media-picker
    name="featured_image_id"  <!-- This must match your database column -->
    mode="single"
/>
```

## License

This component is part of The Last Messenger project.
