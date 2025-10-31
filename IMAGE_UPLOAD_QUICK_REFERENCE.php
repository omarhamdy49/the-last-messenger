<?php

/**
 * IMAGE UPLOAD SERVICE - QUICK REFERENCE
 *
 * Copy this code snippet into your ArticleController to integrate image uploads.
 */

namespace App\Http\Controllers\Admin;

use App\Services\ImageUploadService;
use App\Exceptions\ImageValidationException;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        private ImageUploadService $imageService
    ) {}

    /**
     * Store a new article with image upload
     */
    public function store(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:2048',  // 2MB
                'dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000',
            ],
        ]);

        try {
            // Upload image if provided
            $imagePaths = null;
            if ($request->hasFile('featured_image')) {
                $result = $this->imageService->uploadArticleImage(
                    $request->file('featured_image')
                );

                $imagePaths = [
                    'featured_image' => $result->original,
                    'featured_image_thumbnail' => $result->thumbnail,
                    'featured_image_medium' => $result->medium,
                    'featured_image_large' => $result->large,
                ];
            }

            // Create article
            $article = Article::create(array_merge($validated, $imagePaths ?? []));

            return redirect()
                ->route('admin.articles.show', $article)
                ->with('success', 'Article created successfully!');

        } catch (ImageValidationException $e) {
            return back()
                ->withInput()
                ->withErrors(['featured_image' => $e->getMessage()]);
        }
    }

    /**
     * Update article with optional new image
     */
    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:2048',
                'dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000',
            ],
        ]);

        try {
            // Upload new image if provided (old image auto-deleted)
            if ($request->hasFile('featured_image')) {
                $result = $this->imageService->uploadArticleImage(
                    file: $request->file('featured_image'),
                    oldImagePath: $article->featured_image  // Auto-deletes old image
                );

                $validated['featured_image'] = $result->original;
                $validated['featured_image_thumbnail'] = $result->thumbnail;
                $validated['featured_image_medium'] = $result->medium;
                $validated['featured_image_large'] = $result->large;
            }

            $article->update($validated);

            return redirect()
                ->route('admin.articles.show', $article)
                ->with('success', 'Article updated successfully!');

        } catch (ImageValidationException $e) {
            return back()
                ->withInput()
                ->withErrors(['featured_image' => $e->getMessage()]);
        }
    }

    /**
     * Delete article (images auto-deleted via model event)
     */
    public function destroy(Article $article)
    {
        // Delete images manually if not using model events
        if ($article->featured_image) {
            $this->imageService->deleteImage($article->featured_image);
        }

        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'Article deleted successfully!');
    }
}

/**
 * ARTICLE MODEL INTEGRATION
 *
 * Add these methods to your Article model:
 */
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
     * Get featured image URL accessors
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
    }

    public function getFeaturedImageThumbUrlAttribute(): ?string
    {
        return $this->featured_image_thumbnail ? asset('storage/' . $this->featured_image_thumbnail) : null;
    }

    public function getFeaturedImageMediumUrlAttribute(): ?string
    {
        return $this->featured_image_medium ? asset('storage/' . $this->featured_image_medium) : null;
    }

    public function getFeaturedImageLargeUrlAttribute(): ?string
    {
        return $this->featured_image_large ? asset('storage/' . $this->featured_image_large) : null;
    }

    /**
     * Auto-delete images when article is deleted
     */
    protected static function booted(): void
    {
        static::deleting(function (Article $article) {
            if ($article->featured_image) {
                app(\App\Services\ImageUploadService::class)->deleteImage($article->featured_image);
            }
        });
    }
}

/**
 * BLADE VIEW EXAMPLE
 */
?>

<!-- resources/views/admin/articles/create.blade.php -->
<form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <!-- Title -->
    <div class="mb-4">
        <label for="title" class="block mb-2 font-semibold">Title</label>
        <input
            type="text"
            name="title"
            id="title"
            value="{{ old('title') }}"
            class="w-full border rounded px-3 py-2"
            required
        >
        @error('title')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Featured Image -->
    <div class="mb-4">
        <label for="featured_image" class="block mb-2 font-semibold">Featured Image</label>
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
            Formats: JPEG, PNG, WebP, GIF | Max: 2MB | Min: 200x200px | Max: 4000x4000px
        </p>
    </div>

    <!-- Image Preview -->
    <div id="image-preview" class="hidden mb-4">
        <p class="text-sm font-semibold mb-2">Preview:</p>
        <img id="preview-img" src="" alt="Preview" class="max-w-md rounded shadow-lg">
    </div>

    <!-- Content -->
    <div class="mb-4">
        <label for="content" class="block mb-2 font-semibold">Content</label>
        <textarea
            name="content"
            id="content"
            rows="10"
            class="w-full border rounded px-3 py-2"
            required
        >{{ old('content') }}</textarea>
        @error('content')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    <!-- Submit -->
    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
        Create Article
    </button>
</form>

<script>
// Image preview
document.getElementById('featured_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size (2MB = 2097152 bytes)
        if (file.size > 2097152) {
            alert('File size exceeds 2MB limit!');
            this.value = '';
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-img').src = e.target.result;
            document.getElementById('image-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('image-preview').classList.add('hidden');
    }
});
</script>

<?php
/**
 * DISPLAY IMAGES IN VIEWS
 */
?>

<!-- Article List (use thumbnail) -->
<div class="grid grid-cols-3 gap-4">
    @foreach($articles as $article)
        <div class="border rounded overflow-hidden">
            @if($article->featured_image_thumbnail)
                <img
                    src="{{ $article->featured_image_thumb_url }}"
                    alt="{{ $article->title }}"
                    class="w-full h-48 object-cover"
                    loading="lazy"
                >
            @else
                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                    <span class="text-gray-400">No image</span>
                </div>
            @endif
            <div class="p-4">
                <h3 class="font-bold">{{ $article->title }}</h3>
            </div>
        </div>
    @endforeach
</div>

<!-- Article Detail (use large) -->
<article>
    @if($article->featured_image_large)
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

    <h1>{{ $article->title }}</h1>
    <div>{!! $article->content !!}</div>
</article>

<?php
/**
 * API USAGE (JSON Response)
 */
?>

// Return image URLs in API response
return response()->json([
    'id' => $article->id,
    'title' => $article->title,
    'content' => $article->content,
    'images' => [
        'original' => $article->featured_image_url,
        'thumbnail' => $article->featured_image_thumb_url,
        'medium' => $article->featured_image_medium_url,
        'large' => $article->featured_image_large_url,
    ],
]);
