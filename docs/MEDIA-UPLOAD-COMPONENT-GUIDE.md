# Media Upload Component - Implementation Guide

## Overview

A comprehensive drag-and-drop media upload system for the admin panel with support for images, videos, and documents. Features real-time progress tracking, file validation, error handling, and retry functionality.

## Components Created

### 1. Media Upload Modal Component
**File:** `/resources/views/components/admin/media-upload-modal.blade.php`

A full-featured upload modal with:
- Drag-and-drop file zone with animated border
- File type icons and previews
- Multiple file upload (max 10)
- Progress tracking for each file
- Success/error indicators
- Auto-close on successful upload
- Cancel and retry functionality

### 2. Upload Progress Component
**File:** `/resources/views/components/admin/upload-progress.blade.php`

Individual file upload card displaying:
- File thumbnail or type icon
- Filename and size
- Progress bar with percentage
- Status badges (pending, uploading, success, error)
- Action buttons (remove, cancel, retry)

### 3. Media Upload JavaScript Handler
**File:** `/resources/js/admin/media-upload.js`

Reusable JavaScript functions for:
- File validation (type, size)
- Thumbnail generation for images
- AJAX upload with progress tracking
- Queue management (max 3 concurrent uploads)
- Error handling and retry logic
- Success callbacks

## File Specifications

### Accepted File Types
- **Images:** JPG, PNG, GIF, WEBP (max 10MB)
- **Videos:** MP4, WEBM (max 100MB)
- **Documents:** PDF (max 10MB)

### Validation Rules
- Maximum 10 files per upload session
- Maximum 3 concurrent uploads
- File type validation
- File size validation based on type
- Automatic thumbnail generation for images

## Usage

### Basic Integration

#### 1. Include the Modal in Your Blade Template

```blade
@extends('layouts.admin')

@section('content')
    <div x-data="yourComponent()" @media-uploaded.window="handleUploadSuccess($event.detail)">
        <!-- Your page content -->

        <button @click="$dispatch('open-media-upload')"
                class="px-6 py-3 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="upload" class="w-5 h-5 inline me-2"></i>
            Upload Media
        </button>

        <!-- Include the modal component -->
        <x-admin.media-upload-modal />
    </div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/media-upload.js') }}"></script>
<script>
function yourComponent() {
    return {
        handleUploadSuccess(data) {
            console.log('File uploaded:', data.file);
            console.log('Server response:', data.response);

            // Update your UI with the new media
            // Example: Add to media list
            this.mediaItems.unshift(data.response.media);

            // Show success notification
            alert(`File "${data.file.name}" uploaded successfully!`);
        }
    };
}
</script>
@endpush
```

#### 2. Backend Controller (Laravel Example)

```php
<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB max
        ]);

        $file = $request->file('file');

        // Validate file type
        $mimeType = $file->getMimeType();
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/webm',
            'application/pdf'
        ];

        if (!in_array($mimeType, $allowedTypes)) {
            return response()->json([
                'error' => 'Invalid file type',
                'message' => 'Only images, videos, and PDFs are allowed'
            ], 422);
        }

        // Store file
        $path = $file->store('media', 'public');

        // Generate thumbnail for images
        $thumbnail = null;
        if (str_starts_with($mimeType, 'image/')) {
            // TODO: Generate thumbnail using Intervention Image or similar
            $thumbnail = $path; // Use original for now
        }

        // Create media record
        $media = Media::create([
            'name' => $file->getClientOriginalName(),
            'filename' => basename($path),
            'path' => $path,
            'type' => explode('/', $mimeType)[0], // 'image', 'video', 'document'
            'mime_type' => $mimeType,
            'size' => $file->getSize(),
            'thumbnail' => $thumbnail,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'media' => [
                'id' => $media->id,
                'name' => $media->name,
                'url' => Storage::url($media->path),
                'thumbnail' => $thumbnail ? Storage::url($thumbnail) : null,
                'type' => $media->type,
                'size' => $media->size,
                'dimensions' => $media->dimensions,
                'tags' => $media->tags ?? [],
            ]
        ], 201);
    }
}
```

#### 3. Route Definition

```php
// routes/web.php
use App\Http\Controllers\Admin\MediaController;

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::post('/media/upload', [MediaController::class, 'upload'])->name('admin.media.upload');
});
```

## Advanced Usage

### Programmatic Upload Without Modal

```javascript
// Get files from input
const files = document.querySelector('#file-input').files;

// Upload with callbacks
uploadMediaFiles(Array.from(files), {
    onFileProgress: (file, progress) => {
        console.log(`${file.name}: ${progress}%`);
    },
    onFileSuccess: (file, response) => {
        console.log('Success:', file.name, response);
    },
    onFileError: (file, error) => {
        console.error('Error:', file.name, error);
    },
    onComplete: (results) => {
        console.log('All uploads complete');
        console.log(`Success: ${results.successful.length}`);
        console.log(`Failed: ${results.failed.length}`);
    }
});
```

### Custom File Validation

```javascript
// Override validation in your component
function customValidation(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB

    if (file.size > maxSize) {
        return { valid: false, error: 'File too large' };
    }

    if (!file.type.startsWith('image/')) {
        return { valid: false, error: 'Only images allowed' };
    }

    return { valid: true };
}
```

### Listen for Upload Events

```javascript
// Multiple upload listeners
window.addEventListener('media-uploaded', (event) => {
    const { file, response } = event.detail;

    // Update media gallery
    refreshMediaGallery();

    // Show notification
    showNotification(`${file.name} uploaded successfully!`);

    // Track analytics
    trackUpload(file.type, file.size);
});
```

## API Reference

### JavaScript Functions

#### `validateMediaFile(file)`
Validates a file before upload.

**Parameters:**
- `file` (File): The file to validate

**Returns:** `{ valid: boolean, error: string|null }`

#### `generateImageThumbnail(file)`
Generates a thumbnail for an image file.

**Parameters:**
- `file` (File): The image file

**Returns:** `Promise<string>` - Base64 encoded thumbnail

#### `uploadMediaFile(file, onProgress, onSuccess, onError)`
Uploads a single file.

**Parameters:**
- `file` (File): The file to upload
- `onProgress` (Function): Progress callback
- `onSuccess` (Function): Success callback
- `onError` (Function): Error callback

**Returns:** `XMLHttpRequest` - XHR object for abort functionality

#### `uploadMediaFiles(files, callbacks)`
Uploads multiple files with queue management.

**Parameters:**
- `files` (Array<File>): Array of files
- `callbacks` (Object): Callback functions

**Returns:** `Promise<Object>` - Upload results

#### `formatBytes(bytes, decimals)`
Formats bytes to human-readable string.

**Parameters:**
- `bytes` (number): Number of bytes
- `decimals` (number): Decimal places (default: 2)

**Returns:** `string` - Formatted string (e.g., "1.5 MB")

#### `getFileTypeIcon(mimeType)`
Gets Lucide icon name for file type.

**Parameters:**
- `mimeType` (string): MIME type

**Returns:** `string` - Icon name

### Alpine.js Events

#### `open-media-upload`
Opens the media upload modal.

```blade
<button @click="$dispatch('open-media-upload')">Upload</button>
```

#### `media-uploaded`
Fired when a file is successfully uploaded.

```blade
<div @media-uploaded.window="handleUpload($event.detail)">
```

**Event Detail:**
```javascript
{
    file: {
        id: number,
        name: string,
        size: number,
        type: string,
        thumbnail: string|null
    },
    response: {
        // Server response data
    }
}
```

## Styling

### Color Scheme (Blue-Green Gradient)

```css
/* Primary gradient - used for CTAs and success states */
.bg-gradient-to-r.from-[#0056b3].to-[#218838]

/* Teal accent - hover states and highlights */
.text-[#64CCC5]

/* Error states - red */
.bg-red-500, .text-red-600
```

### Dark Mode Support

All components support dark mode automatically through Alpine.js `darkMode` variable:

```html
:class="darkMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'"
```

### Animations

```css
/* Fade in with upward movement */
.animate-fadeInUp

/* Fade in */
.animate-fadeIn

/* Slide in from right */
.animate-slideInRight

/* Shimmer effect */
.animate-shimmer

/* Spin animation (for loading) */
.animate-spin
```

## Customization

### Change Upload Endpoint

Edit `/resources/js/admin/media-upload.js`:

```javascript
const MediaUploadConfig = {
    uploadUrl: '/your-custom-endpoint',
    // ... other config
};
```

### Change Max File Sizes

```javascript
const MediaUploadConfig = {
    maxImageSize: 20 * 1024 * 1024, // 20MB
    maxVideoSize: 200 * 1024 * 1024, // 200MB
    // ...
};
```

### Add Custom File Types

```javascript
const MediaUploadConfig = {
    acceptedTypes: {
        images: ['image/jpeg', 'image/png'],
        videos: ['video/mp4'],
        documents: ['application/pdf'],
        archives: ['application/zip', 'application/x-rar'] // NEW
    }
};
```

## Troubleshooting

### Upload Not Working

1. **Check CSRF Token:**
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

2. **Check PHP Upload Limits:**
```ini
; php.ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

3. **Check Laravel Config:**
```bash
php artisan config:cache
```

### Files Not Showing

1. Create symbolic link for storage:
```bash
php artisan storage:link
```

2. Check file permissions:
```bash
chmod -R 775 storage/app/public
```

### Progress Bar Not Moving

Check browser console for errors. Ensure XHR upload events are supported.

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

Requires:
- ES6 JavaScript
- CSS Grid
- Flexbox
- XHR Level 2 (for upload progress)

## Security Considerations

1. **Always validate files on the server**
2. **Use secure file storage (outside public directory)**
3. **Sanitize filenames**
4. **Check file contents, not just extensions**
5. **Implement rate limiting**
6. **Scan for viruses/malware**
7. **Set appropriate CORS headers**

## Performance Tips

1. **Compress images before upload** (client-side)
2. **Use WebP format** for images when possible
3. **Implement chunk uploads** for large files
4. **Use CDN** for serving uploaded media
5. **Enable browser caching**
6. **Optimize thumbnails** (lazy loading)

## Example: Complete Integration

See `/resources/views/admin/media/index.blade.php` for a complete working example with:
- Media library listing
- Search and filters
- Grid and list views
- Bulk actions
- Upload integration
- Success handling

## Files Overview

```
resources/
├── views/
│   └── components/
│       └── admin/
│           ├── media-upload-modal.blade.php   # Main modal component
│           └── upload-progress.blade.php       # Progress card component
└── js/
    └── admin/
        └── media-upload.js                     # JavaScript utilities
```

## API Response Format

Expected server response for successful upload:

```json
{
    "success": true,
    "message": "File uploaded successfully",
    "media": {
        "id": 123,
        "name": "my-image.jpg",
        "url": "/storage/media/my-image.jpg",
        "thumbnail": "/storage/media/thumbnails/my-image.jpg",
        "type": "image",
        "mime_type": "image/jpeg",
        "size": 2500000,
        "dimensions": "1920x1080",
        "tags": ["seerah", "battle"]
    }
}
```

Expected error response:

```json
{
    "error": "Validation failed",
    "message": "File size exceeds 10MB",
    "errors": {
        "file": ["The file may not be greater than 10240 kilobytes."]
    }
}
```

## Support

For issues or questions:
1. Check browser console for errors
2. Review Laravel logs: `storage/logs/laravel.log`
3. Verify network requests in browser DevTools
4. Ensure all dependencies are loaded (Alpine.js, Lucide icons)

---

**Note:** This component integrates seamlessly with the existing admin panel design system and follows the project's blue-green color scheme.
