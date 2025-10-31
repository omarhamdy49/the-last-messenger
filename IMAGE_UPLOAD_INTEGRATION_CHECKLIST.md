# ImageUploadService Integration Checklist

## Task A6: Image Upload Service - COMPLETE

### Implementation Status: ✓ DONE

All core components have been created and tested. Follow this checklist to integrate with the Article module.

---

## Pre-Integration Verification

### 1. Dependencies Installed ✓
- [x] Intervention/Image 3.x installed (`composer require intervention/image`)
- [x] PHP GD extension available
- [x] Storage symlink created (`php artisan storage:link`)

**Verify:**
```bash
composer show intervention/image
php -m | grep gd
ls -la public/storage
```

### 2. Files Created ✓
- [x] `app/Services/ImageUploadService.php`
- [x] `app/Services/ImageUploadResult.php`
- [x] `app/Exceptions/ImageUploadException.php`
- [x] `app/Exceptions/ImageValidationException.php`
- [x] `app/Exceptions/ImageCompressionException.php`
- [x] `app/Exceptions/ImageStorageException.php`

**Verify:**
```bash
ls -lh app/Services/Image*
ls -lh app/Exceptions/Image*
```

### 3. Service Tests Passed ✓
- [x] Syntax validation passed
- [x] Class autoloading works
- [x] Constants accessible
- [x] Exception hierarchy correct

---

## Integration Steps

### Step 1: Database Migration

**File:** `database/migrations/2025_11_01_000001_add_image_columns_to_articles_table.php.example`

**Action:**
1. Remove `.example` extension from migration file
2. Review migration to ensure it matches your articles table structure
3. Run migration

```bash
mv database/migrations/2025_11_01_000001_add_image_columns_to_articles_table.php.example \
   database/migrations/2025_11_01_000001_add_image_columns_to_articles_table.php

php artisan migrate
```

**Expected Output:**
```
Migrating: 2025_11_01_000001_add_image_columns_to_articles_table
Migrated:  2025_11_01_000001_add_image_columns_to_articles_table (XX.XXms)
```

**Verification:**
```bash
php artisan tinker --execute="
    \$columns = Schema::getColumnListing('articles');
    echo in_array('featured_image', \$columns) ? '✓ Column exists' : '✗ Column missing';
"
```

---

### Step 2: Update Article Model

**File:** `app/Models/Article.php` (or wherever your Article model is)

**Actions:**

#### 2.1 Add to `$fillable` array:
```php
protected $fillable = [
    // ... existing fields
    'featured_image',
    'featured_image_thumbnail',
    'featured_image_medium',
    'featured_image_large',
];
```

#### 2.2 Add URL accessor methods:
Copy from `IMAGE_UPLOAD_QUICK_REFERENCE.php` lines 142-175

```php
public function getFeaturedImageUrlAttribute(): ?string
{
    return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
}

public function getFeaturedImageThumbUrlAttribute(): ?string
{
    return $this->featured_image_thumbnail ?
        asset('storage/' . $this->featured_image_thumbnail) : null;
}

// ... add other accessors
```

#### 2.3 Add model event for auto-cleanup:
```php
protected static function booted(): void
{
    static::deleting(function (Article $article) {
        if ($article->featured_image) {
            app(\App\Services\ImageUploadService::class)
                ->deleteImage($article->featured_image);
        }
    });
}
```

**Verification:**
```bash
php artisan tinker --execute="
    \$article = new App\Models\Article();
    echo method_exists(\$article, 'getFeaturedImageUrlAttribute') ?
        '✓ Accessor exists' : '✗ Accessor missing';
"
```

---

### Step 3: Update Article Controller

**File:** `app/Http/Controllers/Admin/ArticleController.php` (adjust path as needed)

**Actions:**

#### 3.1 Add constructor dependency injection:
```php
public function __construct(
    private ImageUploadService $imageService
) {}
```

#### 3.2 Update `store` method:
See `IMAGE_UPLOAD_QUICK_REFERENCE.php` lines 28-67

**Key points:**
- Check `$request->hasFile('featured_image')`
- Call `$this->imageService->uploadArticleImage()`
- Store all 4 paths (original, thumbnail, medium, large)
- Catch `ImageValidationException` for user errors

#### 3.3 Update `update` method:
See `IMAGE_UPLOAD_QUICK_REFERENCE.php` lines 72-105

**Key points:**
- Pass `oldImagePath` parameter to auto-delete old image
- Only upload if new file provided
- Update all 4 path columns

#### 3.4 Update `destroy` method:
See `IMAGE_UPLOAD_QUICK_REFERENCE.php` lines 110-123

**Key points:**
- Delete images before deleting model (if not using model events)
- Call `$this->imageService->deleteImage()`

**Verification:**
```bash
php artisan route:list | grep articles
```

---

### Step 4: Update Form Request

**File:** `app/Http/Requests/StoreArticleRequest.php` and `UpdateArticleRequest.php`

**Actions:**

#### 4.1 Add validation rule:
```php
public function rules(): array
{
    return [
        // ... existing rules
        'featured_image' => [
            'nullable',
            'image',
            'mimes:jpeg,jpg,png,webp,gif',
            'max:2048',  // 2MB
            'dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000',
        ],
    ];
}
```

#### 4.2 Add custom messages:
```php
public function messages(): array
{
    return [
        'featured_image.image' => 'The file must be an image.',
        'featured_image.mimes' => 'Only JPEG, PNG, WebP, and GIF images are allowed.',
        'featured_image.max' => 'Image size must not exceed 2MB.',
        'featured_image.dimensions' => 'Image dimensions must be between 200x200 and 4000x4000 pixels.',
    ];
}
```

---

### Step 5: Update Blade Views

#### 5.1 Create/Edit Form (`resources/views/admin/articles/create.blade.php`)

**Add to form:**
```blade
<form method="POST" enctype="multipart/form-data">
    @csrf

    <!-- ... existing fields ... -->

    <!-- Featured Image Upload -->
    <div class="mb-4">
        <label for="featured_image" class="block mb-2 font-semibold">
            Featured Image
        </label>
        <input
            type="file"
            name="featured_image"
            id="featured_image"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="w-full border rounded px-3 py-2"
        >
        @error('featured_image')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-sm text-gray-500 mt-1">
            Formats: JPEG, PNG, WebP, GIF | Max: 2MB | Min: 200x200px
        </p>
    </div>

    <!-- Image Preview -->
    <div id="image-preview" class="hidden mb-4">
        <p class="text-sm font-semibold mb-2">Preview:</p>
        <img id="preview-img" src="" alt="Preview" class="max-w-md rounded shadow">
    </div>
</form>

<script>
// Image preview
document.getElementById('featured_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2097152) {
            alert('File size exceeds 2MB limit!');
            this.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
});
</script>
```

#### 5.2 Edit Form (`resources/views/admin/articles/edit.blade.php`)

**Add current image display:**
```blade
@if($article->featured_image_thumbnail)
    <div class="mb-4">
        <p class="text-sm font-semibold mb-2">Current Image:</p>
        <img src="{{ $article->featured_image_thumb_url }}"
             alt="Current"
             class="max-w-xs rounded shadow">
    </div>
@endif

<!-- Then add file input as in create form -->
```

#### 5.3 List View (`resources/views/admin/articles/index.blade.php`)

**Display thumbnails:**
```blade
@foreach($articles as $article)
    <div class="article-card">
        @if($article->featured_image_thumbnail)
            <img src="{{ $article->featured_image_thumb_url }}"
                 alt="{{ $article->title }}"
                 class="w-full h-48 object-cover rounded"
                 loading="lazy">
        @else
            <div class="w-full h-48 bg-gray-200 rounded flex items-center justify-center">
                <span class="text-gray-400">No image</span>
            </div>
        @endif
        <h3>{{ $article->title }}</h3>
    </div>
@endforeach
```

#### 5.4 Detail View (`resources/views/admin/articles/show.blade.php`)

**Display large image with responsive sources:**
```blade
@if($article->featured_image_large)
    <img src="{{ $article->featured_image_large_url }}"
         srcset="{{ $article->featured_image_medium_url }} 400w,
                 {{ $article->featured_image_large_url }} 1200w"
         sizes="(max-width: 768px) 400px, 1200px"
         alt="{{ $article->title }}"
         class="w-full rounded-lg shadow-lg mb-6">
@endif
```

---

### Step 6: Testing

#### 6.1 Manual Testing Checklist

**Create Article with Image:**
- [ ] Navigate to article create form
- [ ] Upload valid JPEG image (< 2MB, > 200x200px)
- [ ] Verify preview shows
- [ ] Submit form
- [ ] Verify article created successfully
- [ ] Check storage directory for 4 files (original + 3 thumbnails)
- [ ] View article and verify image displays

**Validation Testing:**
- [ ] Try uploading PDF file - should reject
- [ ] Try uploading 3MB image - should reject
- [ ] Try uploading 100x100px image - should reject
- [ ] Try uploading 5000x5000px image - should reject
- [ ] Verify error messages are clear and helpful

**Update Article:**
- [ ] Edit existing article with image
- [ ] Upload new image
- [ ] Verify old image files deleted from storage
- [ ] Verify new image displays

**Delete Article:**
- [ ] Delete article that has image
- [ ] Verify all 4 image files deleted from storage

**Storage Location:**
```bash
ls -la storage/app/public/articles/2025/11/01/
# Should see: article-{uuid}.jpg and 3 thumbnail variants
```

#### 6.2 Automated Testing (Optional)

**Create feature test:**
```bash
php artisan make:test ArticleImageUploadTest
```

**Test example:**
```php
public function test_can_upload_article_image(): void
{
    Storage::fake('public');

    $file = UploadedFile::fake()->image('article.jpg', 800, 600);

    $response = $this->post(route('admin.articles.store'), [
        'title' => 'Test Article',
        'content' => 'Test content',
        'featured_image' => $file,
    ]);

    $response->assertRedirect();
    $article = Article::latest()->first();

    $this->assertNotNull($article->featured_image);
    Storage::disk('public')->assertExists($article->featured_image);
    Storage::disk('public')->assertExists($article->featured_image_thumbnail);
}
```

**Run tests:**
```bash
php artisan test --filter ArticleImageUploadTest
```

---

## Post-Integration Verification

### Final Checklist

- [ ] Migration ran successfully
- [ ] Article model has image accessors
- [ ] Controller handles image upload in store/update/destroy
- [ ] Form request validates image properly
- [ ] Create form has file input with preview
- [ ] Edit form shows current image
- [ ] List view displays thumbnails
- [ ] Detail view displays large image
- [ ] Manual testing passed all scenarios
- [ ] Storage directory contains uploaded images
- [ ] Old images deleted on update/delete
- [ ] Error handling works correctly

### Performance Checks

- [ ] Images are optimized (check file sizes in storage)
- [ ] Thumbnails generated correctly
- [ ] Page load times acceptable
- [ ] Consider adding lazy loading to images
- [ ] Consider CDN integration for production

### Security Checks

- [ ] File type validation working
- [ ] File size limits enforced
- [ ] Dimension limits enforced
- [ ] UUID filenames prevent path traversal
- [ ] Storage directory permissions correct (755)

---

## Troubleshooting

### Common Issues

**Issue: "Failed to store image"**
```bash
chmod -R 775 storage/app/public/
```

**Issue: Images not displaying**
```bash
php artisan storage:link
ls -la public/storage
```

**Issue: "Class ImageUploadService not found"**
```bash
composer dump-autoload
```

**Issue: "Failed to generate thumbnails"**
```bash
php -m | grep gd
# If missing, install GD extension
```

**Issue: Memory limit exceeded**
Edit `php.ini`:
```ini
memory_limit = 256M
```

---

## Documentation References

- **Complete Guide:** `IMAGE_UPLOAD_SERVICE_DOCUMENTATION.md`
- **Quick Reference:** `IMAGE_UPLOAD_QUICK_REFERENCE.php`
- **Service Code:** `app/Services/ImageUploadService.php`
- **Summary:** `IMAGE_UPLOAD_SERVICE_SUMMARY.txt`

---

## Support

If you encounter issues:

1. Check error logs: `storage/logs/laravel.log`
2. Review documentation files
3. Verify PHP extensions: `php -m`
4. Check file permissions: `ls -la storage/`
5. Test service directly in Tinker

---

## Completion

When all checkboxes are marked:

1. Test the complete flow (create → view → update → delete)
2. Verify storage cleanup works
3. Check error handling with invalid inputs
4. Review code quality and documentation
5. Mark TASK A6 as COMPLETE

---

**Status:** Ready for integration
**Created:** November 1, 2025
**Service Version:** 1.0.0
**Laravel Version:** 12.x
**PHP Version:** 8.4+
