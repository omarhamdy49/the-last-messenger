# Media Picker - Quick Reference Card

## Installation

```blade
<!-- Add to admin layout before </body> -->
<x-admin.media-picker-modal />
```

## Basic Usage

### Single Image
```javascript
MediaPicker.open({
    mode: 'single',
    type: 'image',
    onSelect: (media) => {
        console.log(media.id, media.url);
    }
});
```

### Multiple Images
```javascript
MediaPicker.open({
    mode: 'multiple',
    type: 'image',
    onSelect: (images) => {
        images.forEach(img => console.log(img.id));
    }
});
```

### Video Selection
```javascript
MediaPicker.open({
    mode: 'single',
    type: 'video',
    onSelect: (video) => {
        console.log(video.url);
    }
});
```

### All Media Types
```javascript
MediaPicker.open({
    mode: 'multiple',
    type: 'all',
    onSelect: (items) => {
        console.log(items);
    }
});
```

## Form Integration

### Hidden Input Pattern
```blade
<div x-data="{ media: null }">
    <input type="hidden" name="media_id" :value="media?.id">

    <button type="button" @click="MediaPicker.open({
        mode: 'single',
        type: 'image',
        onSelect: (m) => { media = m; }
    })">
        Choose Media
    </button>

    <img x-show="media" :src="media?.url">
</div>
```

### Multiple Selection
```blade
<div x-data="{ items: [] }">
    <template x-for="item in items" :key="item.id">
        <input type="hidden" name="media_ids[]" :value="item.id">
    </template>

    <button @click="MediaPicker.open({
        mode: 'multiple',
        onSelect: (selected) => { items = selected; }
    })">
        Select Media
    </button>
</div>
```

## Vanilla JavaScript

```javascript
document.getElementById('btn').addEventListener('click', () => {
    MediaPicker.open({
        mode: 'single',
        type: 'image',
        onSelect: (media) => {
            document.getElementById('preview').src = media.url;
        }
    });
});
```

## API Reference

### Methods
```javascript
MediaPicker.open(options)    // Open modal
MediaPicker.close()          // Close modal
MediaPicker.getSelected()    // Get current selection
```

### Options
```javascript
{
    mode: 'single' | 'multiple',
    type: 'all' | 'image' | 'video',
    onSelect: Function,
    onClose: Function,
    collection: String,
    maxSelection: Number
}
```

### Media Object
```javascript
{
    id: Number,
    filename: String,
    original_filename: String,
    url: String,
    mime_type: String,
    type: String,
    size: Number,
    width: Number,
    height: Number,
    alt_text: String,
    caption: String
}
```

## Common Patterns

### Article Featured Image
```blade
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
```

### Product Gallery
```blade
<button onclick="MediaPicker.open({
    mode: 'multiple',
    type: 'image',
    onSelect: (images) => {
        // Build gallery HTML
        const html = images.map(img =>
            `<img src='${img.url}' />`
        ).join('');
        document.getElementById('gallery').innerHTML = html;
    }
})">
    Build Gallery
</button>
```

### Video Embed
```blade
<button onclick="MediaPicker.open({
    mode: 'single',
    type: 'video',
    onSelect: (video) => {
        document.getElementById('video-player').src = video.url;
    }
})">
    Select Video
</button>
```

## Events

```javascript
// Listen for selection
window.addEventListener('media-selected', (e) => {
    console.log('Selected:', e.detail);
});

// Listen for open
window.addEventListener('media-picker-open', (e) => {
    console.log('Opened with options:', e.detail);
});

// Listen for close
window.addEventListener('media-picker-close', () => {
    console.log('Closed');
});
```

## Inline Button Examples

### Basic
```html
<button onclick="MediaPicker.open({
    mode: 'single',
    onSelect: (m) => console.log(m)
})">
    Choose Media
</button>
```

### With Preview
```html
<button onclick="MediaPicker.open({
    mode: 'single',
    type: 'image',
    onSelect: (m) => {
        document.getElementById('img').src = m.url;
        document.getElementById('input').value = m.id;
    }
})">
    Choose Image
</button>

<input type="hidden" id="input" name="image_id">
<img id="img">
```

## Troubleshooting

### Modal not opening?
- Check Alpine.js is loaded
- Verify component is included: `<x-admin.media-picker-modal />`

### Icons not showing?
- Call `lucide.createIcons()` after dynamic updates

### Upload failing?
- Check CSRF token: `<meta name="csrf-token">`
- Verify route: `POST /admin/media`

## File Paths

```
resources/views/components/admin/
├── media-picker-modal.blade.php
└── selected-media-preview.blade.php

resources/js/admin/
└── media-picker.js

resources/views/admin/examples/
└── media-picker-demo.blade.php
```

## Demo Page

Visit: `/admin/examples/media-picker-demo`

---

**Full Documentation**: `MEDIA-PICKER-INTEGRATION.md`
