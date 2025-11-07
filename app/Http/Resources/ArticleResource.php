<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thumb' => $this->thumb ? asset('storage/' . $this->thumb) : null,
            'read_time' => $this->read_time,
            'views' => $this->views,
            'is_featured' => $this->is_featured,
            'is_pinned' => $this->is_pinned,
            'hijri_date' => $this->hijri_date,
            'statistics' => $this->statistics,
            'references' => $this->references,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Writer/Author
            'writer' => $this->when($this->relationLoaded('writer'), function () {
                return [
                    'id' => $this->writer->id,
                    'name' => $this->writer->name,
                    'email' => $this->writer->email,
                ];
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
            'locales' => $this->when($this->relationLoaded('locales'), function () {
                return $this->locales->mapWithKeys(function ($locale) {
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
                            'seo_title' => $locale->seo_title,
                            'seo_description' => $locale->seo_description,
                            'is_ready' => $locale->is_ready,
                            'url' => route('articles.show', ['locale' => $locale->locale, 'slug' => $locale->slug]),
                        ]
                    ];
                });
            }),

            // Media/Gallery
            'media' => $this->when($this->relationLoaded('media'), function () {
                return $this->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                        'url' => $media->getUrl(),
                    ];
                });
            }),

            // Gallery (legacy support)
            'gallery' => $this->when($this->relationLoaded('gallery'), function () {
                return $this->gallery->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'image' => $item->image ? asset('storage/' . $item->image) : null,
                        'caption' => $item->caption,
                        'order' => $item->order,
                    ];
                });
            }),

            // Version History
            'versions' => $this->when($this->relationLoaded('versions'), function () {
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
            'current_version' => $this->when($this->relationLoaded('versions'), function () {
                return $this->getCurrentVersionNumber();
            }),

            // URLs
            'urls' => [
                'admin_edit' => route('admin.articles.edit', $this->id),
                'admin_show' => route('admin.articles.show', $this->id),
                'preview' => route('admin.articles.preview', $this->id),
            ],
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
