<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Media API Resource
 *
 * Transform media model for JSON responses
 */
class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $publicKey = $this->publicRouteKey();

        $variants = collect($this->metadata['variants'] ?? [])
            ->map(function (array $variant, string $name): array {
                return array_merge($variant, [
                    'url' => route('admin.media-library.variant', [
                        'media' => $this->publicRouteKey(),
                        'variant' => $name,
                    ]),
                ]);
            })
            ->all();

        return [
            'id' => $this->id,
            'public_key' => $publicKey,
            'title' => $this->metadata['display_title'] ?? ($this->original_filename ? pathinfo($this->original_filename, PATHINFO_FILENAME) : $this->filename),
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'path' => $this->path,
            'src' => $this->url,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'preview_url' => $this->preview_url,
            'download_url' => route('admin.media-library.download', ['media' => $publicKey]),
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_readable_size' => $this->human_readable_size,
            'width' => $this->width,
            'height' => $this->height,
            'disk' => $this->disk,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'type' => $this->type,
            'type_label' => __("media.types.{$this->type}"),
            'collection_name' => $this->collection_name,
            'tags' => $this->tags ?? [],
            'metadata' => $this->metadata ?? [],
            'variants' => $variants,
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'is_audio' => $this->type === 'audio',
            'is_document' => $this->isDocument(),
            'usage_count' => $this->usage_count,
            'mediable' => $this->when($this->mediable_type && $this->mediable_id, [
                'type' => $this->mediable_type,
                'id' => $this->mediable_id,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
}
