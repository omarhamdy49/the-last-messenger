# Remaining Controllers Update Guide

## Overview

This guide provides templates and instructions for updating the remaining controllers (MediaProgramController and SahabiController) to use the new media library system.

---

## MediaProgramController Updates

### Step 1: Update MediaProgramService

**File:** `app/Domain/Media/Services/MediaProgramService.php`

Add these methods:

```php
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Handle thumbnail upload or media selection
 *
 * @param MediaProgram $program
 * @param UploadedFile|null $file Uploaded file
 * @param int|null $mediaId Existing media ID
 * @return void
 */
public function handleThumbnail(MediaProgram $program, ?UploadedFile $file = null, ?int $mediaId = null): void
{
    if ($file) {
        // Upload new file
        $program->addMedia($file)
            ->toMediaCollection('thumbnail', 'public');
    } elseif ($mediaId) {
        // Copy existing media from library
        $media = Media::find($mediaId);
        if ($media) {
            $media->copy($program, 'thumbnail');
        }
    }
}

/**
 * Handle banner image upload or media selection
 *
 * @param MediaProgram $program
 * @param UploadedFile|null $file Uploaded file
 * @param int|null $mediaId Existing media ID
 * @return void
 */
public function handleBanner(MediaProgram $program, ?UploadedFile $file = null, ?int $mediaId = null): void
{
    if ($file) {
        // Upload new file
        $program->addMedia($file)
            ->toMediaCollection('banner', 'public');
    } elseif ($mediaId) {
        // Copy existing media from library
        $media = Media::find($mediaId);
        if ($media) {
            $media->copy($program, 'banner');
        }
    }
}

/**
 * Clear media collection
 *
 * @param MediaProgram $program
 * @param string $collectionName
 * @return void
 */
public function clearMediaCollection(MediaProgram $program, string $collectionName): void
{
    $program->clearMediaCollection($collectionName);
}
```

### Step 2: Update MediaProgramController

**File:** `app/Http/Controllers/Admin/MediaProgramController.php`

**Store Method:**
```php
public function store(StoreMediaProgramRequest $request): RedirectResponse
{
    $program = $this->mediaProgramService->create($request->validated());

    // Handle thumbnail (file upload or media picker selection)
    if ($request->hasFile('thumbnail')) {
        $this->mediaProgramService->handleThumbnail(
            $program,
            $request->file('thumbnail')
        );
    } elseif ($request->has('thumbnail_media_id')) {
        $this->mediaProgramService->handleThumbnail(
            $program,
            null,
            $request->input('thumbnail_media_id')
        );
    }

    // Handle banner image
    if ($request->hasFile('banner_image')) {
        $this->mediaProgramService->handleBanner(
            $program,
            $request->file('banner_image')
        );
    } elseif ($request->has('banner_image_media_id')) {
        $this->mediaProgramService->handleBanner(
            $program,
            null,
            $request->input('banner_image_media_id')
        );
    }

    // Log the creation
    $this->logger->logCreate($program, $request->user());

    return redirect()
        ->route('admin.media-programs.index')
        ->with('success', 'تم إنشاء البرنامج بنجاح');
}
```

**Update Method:**
```php
public function update(UpdateMediaProgramRequest $request, int $id): RedirectResponse
{
    // Get old values before update
    $oldProgram = $this->mediaProgramService->find($id);

    if (!$oldProgram) {
        abort(404, 'Media program not found');
    }

    $oldValues = $oldProgram->toArray();

    // Perform update
    $this->mediaProgramService->update($id, $request->validated());

    // Reload program
    $program = $this->mediaProgramService->find($id);

    // Handle thumbnail
    if ($request->hasFile('thumbnail')) {
        $this->mediaProgramService->handleThumbnail(
            $program,
            $request->file('thumbnail')
        );
    } elseif ($request->has('thumbnail_media_id')) {
        $this->mediaProgramService->handleThumbnail(
            $program,
            null,
            $request->input('thumbnail_media_id')
        );
    }

    // Handle banner
    if ($request->hasFile('banner_image')) {
        $this->mediaProgramService->handleBanner(
            $program,
            $request->file('banner_image')
        );
    } elseif ($request->has('banner_image_media_id')) {
        $this->mediaProgramService->handleBanner(
            $program,
            null,
            $request->input('banner_image_media_id')
        );
    }

    // Reload program with fresh data
    $program = $this->mediaProgramService->find($id, ['episodes']);

    // Log the update with diff
    $this->logger->logUpdate($program, $oldValues, $program->toArray(), $request->user());

    return redirect()
        ->route('admin.media-programs.index')
        ->with('success', 'تم تحديث البرنامج بنجاح');
}
```

**Destroy Method:**
```php
public function destroy(int $id): RedirectResponse
{
    // Get model before deletion
    $program = $this->mediaProgramService->find($id);

    if (!$program) {
        abort(404, 'Media program not found');
    }

    // Clear media collections
    $this->mediaProgramService->clearMediaCollection($program, 'thumbnail');
    $this->mediaProgramService->clearMediaCollection($program, 'banner');

    // Perform deletion
    $this->mediaProgramService->delete($id);

    // Log the deletion
    $this->logger->logDelete($program, request()->user());

    return redirect()
        ->route('admin.media-programs.index')
        ->with('success', 'تم حذف البرنامج بنجاح');
}
```

### Step 3: Update Form Requests

**StoreMediaProgramRequest** and **UpdateMediaProgramRequest**:

Add these validation rules:

```php
// Thumbnail upload (when uploading file directly)
'thumbnail' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],

// Media library selection (when selecting from media picker)
'thumbnail_media_id' => ['nullable', 'exists:media,id'],

// Banner image upload
'banner_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],

// Banner media selection
'banner_image_media_id' => ['nullable', 'exists:media,id'],
```

Add validation messages:

```php
// Thumbnail validation messages
'thumbnail.image' => 'The thumbnail must be a valid image file.',
'thumbnail.mimes' => 'The thumbnail must be in JPEG, PNG, WebP, or GIF format.',
'thumbnail.max' => 'The thumbnail size cannot exceed 10MB.',
'thumbnail_media_id.exists' => 'The selected thumbnail does not exist.',

// Banner validation messages
'banner_image.image' => 'The banner image must be a valid image file.',
'banner_image.mimes' => 'The banner image must be in JPEG, PNG, WebP, or GIF format.',
'banner_image.max' => 'The banner image size cannot exceed 10MB.',
'banner_image_media_id.exists' => 'The selected banner image does not exist.',
```

---

## SahabiController Updates

### Step 1: Update SahabiService

**File:** `app/Domain/Sahaba/Services/SahabiService.php`

Add these methods:

```php
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Handle profile image upload or media selection
 *
 * @param Sahabi $sahabi
 * @param UploadedFile|null $file Uploaded file
 * @param int|null $mediaId Existing media ID
 * @return void
 */
public function handleProfileImage(Sahabi $sahabi, ?UploadedFile $file = null, ?int $mediaId = null): void
{
    if ($file) {
        // Upload new file
        $sahabi->addMedia($file)
            ->toMediaCollection('image', 'public');
    } elseif ($mediaId) {
        // Copy existing media from library
        $media = Media::find($mediaId);
        if ($media) {
            $media->copy($sahabi, 'image');
        }
    }
}

/**
 * Clear media collection
 *
 * @param Sahabi $sahabi
 * @param string $collectionName
 * @return void
 */
public function clearMediaCollection(Sahabi $sahabi, string $collectionName): void
{
    $sahabi->clearMediaCollection($collectionName);
}
```

### Step 2: Update SahabiController

**File:** `app/Http/Controllers/Admin/SahabiController.php`

**Store Method:**
```php
public function store(StoreSahabiRequest $request): RedirectResponse
{
    $sahabi = $this->sahabiService->create($request->validated());

    // Handle profile image (file upload or media picker selection)
    if ($request->hasFile('image')) {
        $this->sahabiService->handleProfileImage(
            $sahabi,
            $request->file('image')
        );
    } elseif ($request->has('image_media_id')) {
        $this->sahabiService->handleProfileImage(
            $sahabi,
            null,
            $request->input('image_media_id')
        );
    }

    // Log the creation
    $this->logger->logCreate($sahabi, $request->user());

    return redirect()->route('admin.sahaba.index')
        ->with('success', 'تم إنشاء الصحابي بنجاح');
}
```

**Update Method:**
```php
public function update(UpdateSahabiRequest $request, int $id): RedirectResponse
{
    // Get old values before update
    $oldSahabi = $this->sahabiService->find($id);

    if (!$oldSahabi) {
        abort(404, 'Sahabi not found');
    }

    $oldValues = $oldSahabi->toArray();

    // Perform update
    $this->sahabiService->update($id, $request->validated());

    // Reload sahabi
    $sahabi = $this->sahabiService->find($id);

    // Handle profile image
    if ($request->hasFile('image')) {
        $this->sahabiService->handleProfileImage(
            $sahabi,
            $request->file('image')
        );
    } elseif ($request->has('image_media_id')) {
        $this->sahabiService->handleProfileImage(
            $sahabi,
            null,
            $request->input('image_media_id')
        );
    }

    // Reload with fresh data
    $sahabi = $this->sahabiService->find($id);

    // Log the update with diff
    $this->logger->logUpdate($sahabi, $oldValues, $sahabi->toArray(), $request->user());

    return redirect()->route('admin.sahaba.index')
        ->with('success', 'تم تحديث الصحابي بنجاح');
}
```

**Destroy Method:**
```php
public function destroy(int $id): RedirectResponse
{
    // Get model before deletion
    $sahabi = $this->sahabiService->find($id);

    if (!$sahabi) {
        abort(404, 'Sahabi not found');
    }

    // Clear media collection
    $this->sahabiService->clearMediaCollection($sahabi, 'image');

    // Perform deletion
    $this->sahabiService->delete($id);

    // Log the deletion
    $this->logger->logDelete($sahabi, request()->user());

    return redirect()->route('admin.sahaba.index')
        ->with('success', 'تم حذف الصحابي بنجاح');
}
```

### Step 3: Update Form Requests

**StoreSahabiRequest** and **UpdateSahabiRequest**:

Add validation rules:

```php
// Image upload (when uploading file directly)
'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],

// Media library selection (when selecting from media picker)
'image_media_id' => ['nullable', 'exists:media,id'],
```

Add validation messages:

```php
// Image validation messages
'image.image' => 'The profile image must be a valid image file.',
'image.mimes' => 'The profile image must be in JPEG, PNG, WebP, or GIF format.',
'image.max' => 'The profile image size cannot exceed 10MB.',
'image_media_id.exists' => 'The selected profile image does not exist.',
```

---

## Blade View Examples

### Create/Edit Article Form

**`resources/views/admin/articles/create.blade.php`** or **`edit.blade.php`**:

```blade
<!-- Featured Image -->
<div class="mb-4">
    <x-media.picker-button
        name="featured_image"
        label="Featured Image"
        :value="$article->featured_image_url ?? null"
        mode="single"
        accept="image/*"
        help-text="Recommended size: 1200x800px" />
</div>

<!-- OG Image -->
<div class="mb-4">
    <x-media.picker-button
        name="og_image"
        label="OG Image (Social Media)"
        :value="$article->og_image_url ?? null"
        mode="single"
        accept="image/*"
        help-text="Recommended size: 1200x630px for social media" />
</div>

<!-- Gallery -->
<div class="mb-4">
    <x-media.picker-button
        name="gallery"
        label="Gallery Images"
        :value="$article->gallery_images ?? []"
        mode="multiple"
        accept="image/*"
        :max-selection="10"
        help-text="Select up to 10 images for the gallery" />
</div>
```

### Create/Edit Media Program Form

**`resources/views/admin/media-programs/create.blade.php`** or **`edit.blade.php`**:

```blade
<!-- Thumbnail -->
<div class="mb-4">
    <x-media.picker-button
        name="thumbnail"
        label="Program Thumbnail"
        :value="$program->thumbnail_url ?? null"
        mode="single"
        accept="image/*"
        help-text="Recommended size: 400x300px" />
</div>

<!-- Banner Image -->
<div class="mb-4">
    <x-media.picker-button
        name="banner_image"
        label="Banner Image"
        :value="$program->banner_image_url ?? null"
        mode="single"
        accept="image/*"
        help-text="Recommended size: 1920x600px" />
</div>
```

### Create/Edit Sahabi Form

**`resources/views/admin/sahaba/create.blade.php`** or **`edit.blade.php`**:

```blade
<!-- Profile Image -->
<div class="mb-4">
    <x-media.picker-button
        name="image"
        label="Profile Image"
        :value="$sahabi->image_url ?? null"
        mode="single"
        accept="image/*"
        help-text="Recommended size: 400x400px" />
</div>
```

---

## Testing Checklist

### MediaProgramController

- [ ] Create media program with thumbnail upload
- [ ] Create media program with thumbnail from media picker
- [ ] Create media program with banner upload
- [ ] Create media program with banner from media picker
- [ ] Update media program thumbnail
- [ ] Update media program banner
- [ ] Delete media program (verify media cleanup)

### SahabiController

- [ ] Create sahabi with profile image upload
- [ ] Create sahabi with profile image from media picker
- [ ] Update sahabi profile image
- [ ] Delete sahabi (verify media cleanup)

---

## Important Notes

1. **Media Collections**: Ensure models (MediaProgram, Sahabi) have proper media collections registered in their `registerMediaCollections()` method.

2. **Media Conversions**: Configure appropriate conversions for each model's needs (thumbnails, etc.).

3. **Backward Compatibility**: Add accessor methods (e.g., `getThumbnailUrlAttribute()`) for backward compatibility.

4. **Error Handling**: Wrap media operations in try-catch blocks.

5. **Logging**: Ensure all media operations are logged.

6. **Validation**: Update form requests with proper validation rules.

7. **Authorization**: Maintain proper authorization checks in controllers.

---

## Quick Reference

### Pattern for All Controllers

```php
// STORE METHOD
if ($request->hasFile('field_name')) {
    $service->handleMediaField($model, $request->file('field_name'));
} elseif ($request->has('field_name_media_id')) {
    $service->handleMediaField($model, null, $request->input('field_name_media_id'));
}

// UPDATE METHOD
// Same as store

// DESTROY METHOD
$service->clearMediaCollection($model, 'collection_name');
```

### Pattern for All Services

```php
public function handleMediaField(Model $model, ?UploadedFile $file = null, ?int $mediaId = null): void
{
    if ($file) {
        $model->addMedia($file)->toMediaCollection('collection_name', 'public');
    } elseif ($mediaId) {
        $media = Media::find($mediaId);
        if ($media) {
            $media->copy($model, 'collection_name');
        }
    }
}
```

---

**Document Version:** 1.0
**Last Updated:** 2025-11-01
