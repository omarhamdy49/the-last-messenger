# ImageUploadService - Complete Documentation

## Overview

The `ImageUploadService` is a production-ready service for handling image uploads in The Last Messenger platform. It provides automatic validation, optimization, thumbnail generation, and storage management for article images.

## Installation

The service requires Intervention Image library (already installed):

```bash
composer require intervention/image
```

## Features

- **Multi-Format Support**: JPEG, PNG, WebP, GIF
- **Automatic Validation**: File type, size, and dimension checks
- **Thumbnail Generation**: Creates 3 size variants (thumbnail, medium, large)
- **Image Optimization**: Automatic compression to reduce file size
- **Smart Storage**: Date-based directory organization (YYYY/MM/DD)
- **Unique Naming**: UUID-based filenames prevent collisions
- **Old Image Cleanup**: Automatic deletion of replaced images
- **Comprehensive Error Handling**: Custom exceptions for different failure scenarios

## File Structure

```
app/
├── Services/
│   ├── ImageUploadService.php      # Main service class
│   └── ImageUploadResult.php        # Result DTO
└── Exceptions/
    ├── ImageUploadException.php     # Base exception
    ├── ImageValidationException.php # Validation errors
    ├── ImageCompressionException.php # Processing errors
    └── ImageStorageException.php     # Storage errors
```

## Storage Structure

```
storage/app/public/articles/
└── 2025/11/01/
    ├── article-550e8400-e29b-41d4-a716-446655440000.jpg
    ├── article-550e8400-e29b-41d4-a716-446655440000-thumbnail.jpg  (150x150)
    ├── article-550e8400-e29b-41d4-a716-446655440000-medium.jpg     (400x300)
    └── article-550e8400-e29b-41d4-a716-446655440000-large.jpg      (1200x800)
```

## Configuration

### Image Size Variants

```php
const SIZES = [
    'thumbnail' => ['width' => 150, 'height' => 150, 'mode' => 'crop'],    // Square crop
    'medium' => ['width' => 400, 'height' => 300, 'mode' => 'fit'],       // 16:9 aspect
    'large' => ['width' => 1200, 'height' => 800, 'mode' => 'fit'],       // 16:9 aspect
];
```

### Validation Rules

```php
const IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
const MAX_FILE_SIZE = 2097152;  // 2MB
const MIN_FILE_SIZE = 10240;    // 10KB
const MIN_DIMENSIONS = 200;     // 200x200 pixels
const MAX_DIMENSIONS = 4000;    // 4000x4000 pixels
```

### Compression Settings

```php
const COMPRESSION_QUALITY = 85;  // JPEG quality (1-100)
```

## Usage Examples

### 1. Basic Upload in Controller

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Services\ImageUploadService;
use App\Http\Requests\StoreArticleRequest;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function __construct(
        private ImageUploadService $imageService
    ) {}

    public function store(StoreArticleRequest $request): JsonResponse
    {
        try {
            // Upload image if provided
            $imagePaths = null;
            if ($request->hasFile('featured_image')) {
                $result = $this->imageService->uploadArticleImage(
                    $request->file('featured_image')
                );

                $imagePaths = $result->getPaths();
            }

            // Create article
            $article = Article::create([
                'title' => $request->title,
                'content' => $request->content,
                'featured_image' => $imagePaths['original'] ?? null,
                'featured_image_thumbnail' => $imagePaths['thumbnail'] ?? null,
                'featured_image_medium' => $imagePaths['medium'] ?? null,
                'featured_image_large' => $imagePaths['large'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'article' => $article,
                'image_urls' => $result?->getUrls(),
            ]);
        } catch (\App\Exceptions\ImageValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create article',
            ], 500);
        }
    }
}
```

### 2. Update with Old Image Deletion

```php
public function update(UpdateArticleRequest $request, Article $article): JsonResponse
{
    try {
        // Upload new image and delete old one
        if ($request->hasFile('featured_image')) {
            $result = $this->imageService->uploadArticleImage(
                file: $request->file('featured_image'),
                oldImagePath: $article->featured_image,  // Auto-deletes old image
                prefix: 'article'
            );

            $article->update([
                'featured_image' => $result->original,
                'featured_image_thumbnail' => $result->thumbnail,
                'featured_image_medium' => $result->medium,
                'featured_image_large' => $result->large,
            ]);
        }

        return response()->json(['success' => true, 'article' => $article]);
    } catch (\App\Exceptions\ImageValidationException $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
}
```

### 3. Direct Service Usage

```php
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;

$service = new ImageUploadService();

// Upload image
$result = $service->uploadArticleImage($uploadedFile);

// Access different sizes
echo $result->getThumbnailUrl();  // 150x150
echo $result->getMediumUrl();     // 400x300
echo $result->getLargeUrl();      // 1200x800
echo $result->getOriginalUrl();   // Original size

// Get metadata
echo $result->width;               // Original width
echo $result->height;              // Original height
echo $result->mimeType;            // e.g., 'image/jpeg'
echo $result->getFormattedFileSize();  // e.g., '1.5 MB'

// Get all paths
$paths = $result->getPaths();
// [
//   'original' => 'articles/2025/11/01/article-uuid.jpg',
//   'thumbnail' => 'articles/2025/11/01/article-uuid-thumbnail.jpg',
//   'medium' => 'articles/2025/11/01/article-uuid-medium.jpg',
//   'large' => 'articles/2025/11/01/article-uuid-large.jpg',
// ]

// Get all URLs
$urls = $result->getUrls();
```

### 4. Delete Image and Thumbnails

```php
// Delete image with all thumbnails
$deleted = $this->imageService->deleteImage($article->featured_image);

if ($deleted) {
    $article->update(['featured_image' => null]);
}
```

### 5. Get Image Information

```php
// Get image dimensions
$dimensions = $this->imageService->getImageDimensions($imagePath);
echo "Width: {$dimensions['width']}px";
echo "Height: {$dimensions['height']}px";

// Get public URL
$url = $this->imageService->getImageUrl($imagePath);

// Get full file path
$fullPath = $this->imageService->getPublicPath($imagePath);
```

## Model Integration

### Article Model Example

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\ImageUploadService;

class Article extends Model
{
    protected $fillable = [
        'title',
        'content',
        'featured_image',
        'featured_image_thumbnail',
        'featured_image_medium',
        'featured_image_large',
    ];

    /**
     * Get featured image URL (original size)
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->featured_image) {
            return null;
        }
        return asset('storage/' . $this->featured_image);
    }

    /**
     * Get thumbnail URL
     */
    public function getFeaturedImageThumbUrlAttribute(): ?string
    {
        if (!$this->featured_image_thumbnail) {
            return $this->getFallbackImage();
        }
        return asset('storage/' . $this->featured_image_thumbnail);
    }

    /**
     * Get medium size URL
     */
    public function getFeaturedImageMediumUrlAttribute(): ?string
    {
        if (!$this->featured_image_medium) {
            return $this->getFallbackImage();
        }
        return asset('storage/' . $this->featured_image_medium);
    }

    /**
     * Get large size URL
     */
    public function getFeaturedImageLargeUrlAttribute(): ?string
    {
        if (!$this->featured_image_large) {
            return $this->getFallbackImage();
        }
        return asset('storage/' . $this->featured_image_large);
    }

    /**
     * Get fallback placeholder image
     */
    protected function getFallbackImage(): string
    {
        return asset('images/placeholder-article.jpg');
    }

    /**
     * Delete article images when deleting model
     */
    protected static function booted(): void
    {
        static::deleting(function (Article $article) {
            if ($article->featured_image) {
                app(ImageUploadService::class)->deleteImage($article->featured_image);
            }
        });
    }
}
```

### Migration Example

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->after('content');
            $table->string('featured_image_thumbnail')->nullable()->after('featured_image');
            $table->string('featured_image_medium')->nullable()->after('featured_image_thumbnail');
            $table->string('featured_image_large')->nullable()->after('featured_image_medium');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'featured_image',
                'featured_image_thumbnail',
                'featured_image_medium',
                'featured_image_large',
            ]);
        });
    }
};
```

## Error Handling

### Exception Hierarchy

```
ImageUploadException (Base)
├── ImageValidationException (422)
│   ├── Invalid file type
│   ├── File size violations
│   ├── Invalid dimensions
│   └── Corrupted files
├── ImageCompressionException (500)
│   ├── Failed to compress
│   ├── Failed to resize
│   └── Processing errors
└── ImageStorageException (500)
    ├── Failed to save file
    ├── Disk full
    ├── Permission denied
    └── Failed to delete old image
```

### Catching Specific Exceptions

```php
use App\Exceptions\ImageValidationException;
use App\Exceptions\ImageCompressionException;
use App\Exceptions\ImageStorageException;

try {
    $result = $service->uploadArticleImage($file);
} catch (ImageValidationException $e) {
    // User error - show to user
    return back()->withErrors(['image' => $e->getMessage()]);
} catch (ImageCompressionException $e) {
    // Server error - log and show generic message
    Log::error('Image compression failed', ['error' => $e->getMessage()]);
    return back()->withErrors(['image' => 'Failed to process image']);
} catch (ImageStorageException $e) {
    // Server error - log and notify admin
    Log::critical('Image storage failed', ['error' => $e->getMessage()]);
    return back()->withErrors(['image' => 'Failed to save image']);
}
```

## Validation Messages

The service provides clear, user-friendly error messages:

- `"Invalid image file type. Only JPEG, PNG, WebP, and GIF are allowed."`
- `"File size exceeds 2MB limit. Please upload a smaller image."`
- `"File size is too small. Minimum size is 10KB."`
- `"Image dimensions must be at least 200x200 pixels."`
- `"Image dimensions exceed maximum 4000x4000 pixels."`
- `"Unable to read image file. The file may be corrupted."`
- `"Failed to generate thumbnails: {reason}"`
- `"Failed to store image: {reason}"`

## Form Request Example

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:2048',  // 2MB in KB
                'dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'featured_image.image' => 'The file must be an image.',
            'featured_image.mimes' => 'Only JPEG, PNG, WebP, and GIF images are allowed.',
            'featured_image.max' => 'Image size must not exceed 2MB.',
            'featured_image.dimensions' => 'Image dimensions must be between 200x200 and 4000x4000 pixels.',
        ];
    }
}
```

## Blade View Example

```blade
<!-- Article form with image upload -->
<form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="mb-4">
        <label for="featured_image" class="block mb-2">Featured Image</label>
        <input
            type="file"
            name="featured_image"
            id="featured_image"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="border rounded px-3 py-2"
        >
        @error('featured_image')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-sm text-gray-500 mt-1">
            Accepted formats: JPEG, PNG, WebP, GIF. Max size: 2MB. Min dimensions: 200x200px.
        </p>
    </div>

    <!-- Image preview -->
    <div id="image-preview" class="hidden mb-4">
        <img id="preview-img" src="" alt="Preview" class="max-w-xs rounded shadow">
    </div>

    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">
        Create Article
    </button>
</form>

<script>
document.getElementById('featured_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
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

## Display Images in Views

```blade
<!-- Article card with responsive images -->
<div class="article-card">
    @if($article->featured_image)
        <!-- Use thumbnail for list view -->
        <img
            src="{{ $article->featured_image_thumb_url }}"
            alt="{{ $article->title }}"
            class="w-full h-48 object-cover rounded"
            loading="lazy"
        >
    @else
        <div class="w-full h-48 bg-gray-200 rounded flex items-center justify-center">
            <span class="text-gray-400">No image</span>
        </div>
    @endif

    <h3>{{ $article->title }}</h3>
</div>

<!-- Article detail page with larger image -->
<div class="article-detail">
    @if($article->featured_image)
        <img
            src="{{ $article->featured_image_large_url }}"
            srcset="
                {{ $article->featured_image_medium_url }} 400w,
                {{ $article->featured_image_large_url }} 1200w
            "
            sizes="(max-width: 768px) 400px, 1200px"
            alt="{{ $article->title }}"
            class="w-full rounded-lg shadow-lg mb-6"
        >
    @endif
</div>
```

## Customization

### Change Image Sizes

Edit the `SIZES` constant in `ImageUploadService.php`:

```php
public const SIZES = [
    'thumbnail' => ['width' => 200, 'height' => 200, 'mode' => 'crop'],
    'medium' => ['width' => 600, 'height' => 400, 'mode' => 'fit'],
    'large' => ['width' => 1600, 'height' => 1200, 'mode' => 'fit'],
    'extra_large' => ['width' => 2400, 'height' => 1800, 'mode' => 'fit'], // Add new size
];
```

### Change Compression Quality

Edit the `COMPRESSION_QUALITY` constant:

```php
public const COMPRESSION_QUALITY = 90; // Higher quality, larger file size
```

### Change Storage Path

Edit the `STORAGE_PATH` constant:

```php
public const STORAGE_PATH = 'images/articles'; // Custom path
```

### Use Different Storage Disk

Edit the `STORAGE_DISK` constant:

```php
public const STORAGE_DISK = 's3'; // Use S3 instead of local
```

## Performance Considerations

1. **Image Processing**: Thumbnail generation happens synchronously. For high-traffic sites, consider queuing this operation:

```php
dispatch(function () use ($imagePath) {
    $service->generateThumbnails($imagePath);
})->afterResponse();
```

2. **CDN Integration**: Serve images through a CDN for better performance:

```php
public function getImageUrl(string $path): string
{
    $baseUrl = config('app.cdn_url', Storage::disk(self::STORAGE_DISK)->url(''));
    return $baseUrl . '/' . $path;
}
```

3. **WebP Conversion**: For modern browsers, consider adding WebP conversion:

```php
// Add to generateThumbnails method
$resized->save($thumbnailFullPath . '.webp', format: 'webp');
```

## Testing

### Unit Test Example

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\ImageValidationException;

class ImageUploadServiceTest extends TestCase
{
    protected ImageUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ImageUploadService();
    }

    public function test_uploads_valid_image(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $result = $this->service->uploadArticleImage($file);

        $this->assertNotNull($result->original);
        $this->assertNotNull($result->thumbnail);
        $this->assertNotNull($result->medium);
        $this->assertNotNull($result->large);

        Storage::disk('public')->assertExists($result->original);
        Storage::disk('public')->assertExists($result->thumbnail);
    }

    public function test_rejects_invalid_file_type(): void
    {
        $this->expectException(ImageValidationException::class);

        $file = UploadedFile::fake()->create('test.pdf', 100);
        $this->service->uploadArticleImage($file);
    }

    public function test_rejects_oversized_file(): void
    {
        $this->expectException(ImageValidationException::class);

        $file = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB
        $this->service->uploadArticleImage($file);
    }
}
```

## Troubleshooting

### Issue: "Failed to store image"

**Solution**: Check file permissions on `storage/app/public/` directory:
```bash
chmod -R 775 storage/app/public/
```

### Issue: "Unable to read image file"

**Solution**: Ensure GD or Imagick extension is installed:
```bash
php -m | grep -E "(gd|imagick)"
```

### Issue: Images not showing

**Solution**: Create storage symlink:
```bash
php artisan storage:link
```

### Issue: Out of memory during processing

**Solution**: Increase PHP memory limit in `php.ini`:
```ini
memory_limit = 256M
```

## Security Best Practices

1. **Always validate file types** - Don't trust client-side validation
2. **Check magic bytes** - The service validates MIME types
3. **Limit file sizes** - Prevent DoS attacks
4. **Sanitize filenames** - UUID prevents path traversal
5. **Serve images through CDN** - Protect origin server
6. **Implement rate limiting** - Prevent upload abuse

## Support & Maintenance

For questions or issues with the ImageUploadService:
1. Check this documentation
2. Review error logs in `storage/logs/laravel.log`
3. Verify PHP extensions and dependencies
4. Ensure storage permissions are correct
