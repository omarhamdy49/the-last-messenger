# Media Upload System - Quick Start Guide

## Setup (One-time)

### 1. Create Storage Symbolic Link
```bash
php artisan storage:link
```

### 2. Verify Permissions
```bash
chmod -R 775 storage
chmod -R 775 public/storage
```

### 3. Check PHP Configuration
Ensure these settings in `php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
```

---

## Quick API Reference

### Base URL
```
/su/portal/v1/admin/media
```

### Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/upload` | Upload file |
| GET | `/list` | List files |
| DELETE | `/{id}` | Delete file |
| POST | `/reorder` | Reorder files |

---

## Upload Example (cURL)

```bash
curl -X POST http://localhost:8000/su/portal/v1/admin/media/upload \
  -H "Cookie: laravel_session=YOUR_SESSION" \
  -F "file=@image.jpg" \
  -F "model_type=article" \
  -F "model_id=1" \
  -F "collection=featured_image"
```

---

## Collection Names by Model

### Article
- `featured_image` (single)
- `gallery` (multiple)

### MediaProgram
- `thumbnail` (single)
- `banner` (single)

### Sahabi
- `profile_image` (single)

---

## Supported File Types

### Images (max 10MB)
- jpg, jpeg, png, webp, gif

### Videos (max 100MB)
- mp4, webm, mov

### Documents (max 100MB)
- pdf, doc, docx

---

## Image Conversions

All uploaded images automatically generate 3 sizes:

| Size | Dimensions |
|------|------------|
| thumb | 150x150 |
| medium | 600x400 |
| large | 1200x800 |

---

## Usage in Blade Templates

```blade
{{-- Get first media from collection --}}
@php
    $featured = $article->getFirstMedia('featured_image');
@endphp

{{-- Display image --}}
@if($featured)
    <img src="{{ $featured->getUrl() }}" alt="Featured">

    {{-- Display thumbnail --}}
    <img src="{{ $featured->getUrl('thumb') }}" alt="Thumbnail">
@endif

{{-- Gallery --}}
@foreach($article->getMedia('gallery') as $image)
    <img src="{{ $image->getUrl('medium') }}" alt="Gallery">
@endforeach
```

---

## Usage in Controllers

```php
// Add media
$article->addMedia($request->file('image'))
    ->toMediaCollection('featured_image');

// Get media URL
$url = $article->getFirstMedia('featured_image')?->getUrl();

// Get thumbnail URL
$thumbUrl = $article->getFirstMedia('featured_image')?->getUrl('thumb');

// Delete media
$article->clearMediaCollection('featured_image');
```

---

## JavaScript (Axios) Example

```javascript
// Upload
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('model_type', 'article');
formData.append('model_id', 5);
formData.append('collection', 'featured_image');

await axios.post('/su/portal/v1/admin/media/upload', formData, {
  headers: {
    'Content-Type': 'multipart/form-data',
    'X-CSRF-TOKEN': csrfToken
  }
});

// List
const response = await axios.get('/su/portal/v1/admin/media/list', {
  params: {
    model_type: 'article',
    model_id: 5,
    collection: 'gallery'
  }
});

// Delete
await axios.delete(`/su/portal/v1/admin/media/${mediaId}`, {
  headers: { 'X-CSRF-TOKEN': csrfToken }
});
```

---

## Authentication Required

All endpoints require:
- ✅ Active session (logged in)
- ✅ One of these roles: `admin`, `super_admin`, `editor`

---

## Common Errors

### 413 Payload Too Large
- File exceeds 100MB
- Solution: Reduce file size or increase limits

### 415 Unsupported Media Type
- File type not allowed
- Solution: Convert to supported format (jpg, png, mp4, etc.)

### 404 Model Not Found
- Invalid model_id
- Solution: Verify the ID exists in database

### 403 Forbidden
- User doesn't have required role
- Solution: Assign admin/super_admin/editor role

---

## Testing Checklist

- [ ] Upload image to article featured_image
- [ ] Upload multiple images to article gallery
- [ ] List all media for article
- [ ] Delete media file
- [ ] Reorder gallery images
- [ ] Verify thumbnail/medium/large conversions generated
- [ ] Test file size limit (reject >100MB)
- [ ] Test invalid file type (reject .exe)
- [ ] Test without authentication (redirect to login)

---

## Files Reference

**Controller:** `/app/Http/Controllers/Admin/MediaUploadController.php`
**Request:** `/app/Http/Requests/Admin/UploadMediaRequest.php`
**Config:** `/config/media-library.php`
**Routes:** `/routes/admin.php` (lines 81-87)

**Full Documentation:** `MEDIA-UPLOAD-SYSTEM.md`
