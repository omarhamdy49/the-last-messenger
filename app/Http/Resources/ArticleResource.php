<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdminRequest = $request->routeIs('admin.*');

        return [
            'id' => $this->id,
            'thumb' => $this->thumb ? asset('storage/'.$this->thumb) : null,
            'read_time' => $this->read_time,
            'views' => $this->views,
            'is_featured' => $this->is_featured,
            'is_pinned' => $this->is_pinned,
            'hijri_date' => $this->hijri_date,
            'statistics' => $this->statistics,
            'references' => $this->references,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Writer/Author
            'writer' => $this->when($this->relationLoaded('writer'), function () use ($isAdminRequest) {
                return array_filter([
                    'id' => $this->writer->id,
                    'name' => $this->writer->name,
                    'email' => $isAdminRequest ? $this->writer->email : null,
                ], static fn (mixed $value): bool => $value !== null);
            }),

            // Primary Category
            'category' => $this->when($this->relationLoaded('category'), function () {
                return $this->category ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ] : null;
            }),

            // Additional Categories
            'categories' => $this->when($this->relationLoaded('categories'), function () {
                return $this->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ];
                });
            }),

            // Tags
            'tags' => $this->when($this->relationLoaded('tags'), function () {
                return $this->tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'slug' => $tag->slug,
                        'name' => $tag->getTranslations('name'),
                    ];
                });
            }),

            // Locales (translations)
            'locales' => $this->when($this->relationLoaded('locales'), function () use ($isAdminRequest) {
                $locales = $isAdminRequest
                    ? $this->locales
                    : $this->locales->where('is_ready', true);

                return $locales->mapWithKeys(function ($locale) {
                    return [
                        $locale->locale => [
                            'title' => $locale->title,
                            'slug' => $locale->slug,
                            'excerpt' => $locale->excerpt,
                            'description' => $locale->description,
                            'content' => [
                                'body' => $locale->content['body'] ?? null,
                                'toc' => $locale->content['toc'] ?? [],
                                'summary' => $locale->content['summary'] ?? null,
                            ],
                            'seo' => [
                                'title' => $locale->seo_title,
                                'description' => $locale->seo_description,
                            ],
                            'seo_title' => $locale->seo_title,
                            'seo_description' => $locale->seo_description,
                            'is_ready' => $locale->is_ready,
                            'url' => Route::has('articles.show')
                                ? route('articles.show', ['locale' => $locale->locale, 'slug' => $locale->slug])
                                : null,
                        ],
                    ];
                });
            }),

            'media_items' => $this->when($this->relationLoaded('mediaItems'), function () {
                return MediaResource::collection($this->mediaItems);
            }),

            // Gallery (legacy support)
            'gallery' => $this->when($this->relationLoaded('gallery'), function () {
                return $this->gallery->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'image' => $item->image ? asset('storage/'.$item->image) : null,
                        'caption' => $item->caption,
                        'order' => $item->order,
                    ];
                });
            }),

            'seerah_events' => $this->when($this->relationLoaded('seerahEvents'), function () {
                return $this->seerahEvents->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'slug' => $event->slug,
                        'title' => $event->getTranslations('title'),
                        'featured_image' => $event->getFirstMediaUrl('featured_image') ?: $event->featured_image,
                    ];
                });
            }),

            // Version History
            'versions' => $this->when($isAdminRequest && $this->relationLoaded('versions'), function () {
                return $this->versions->map(function ($version) {
                    return [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'change_type' => $version->change_type,
                        'change_summary' => $version->change_summary,
                        'changed_by' => $version->changedBy ? [
                            'id' => $version->changedBy->id,
                            'name' => $version->changedBy->name,
                        ] : null,
                        'created_at' => $version->created_at->toIso8601String(),
                    ];
                });
            }),

            // Computed properties
            'current_version' => $this->when($isAdminRequest && $this->relationLoaded('versions'), function () {
                return $this->getCurrentVersionNumber();
            }),

            // URLs
            'urls' => $this->when($isAdminRequest, function () {
                return [
                    'admin_edit' => route('admin.articles.edit', $this->id),
                    'admin_show' => route('admin.articles.show', $this->id),
                    'preview' => Route::has('admin.articles.preview')
                        ? route('admin.articles.preview', $this->id)
                        : null,
                ];
            }),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
