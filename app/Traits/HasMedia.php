<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasMedia
{
    /**
     * Get all media for the model
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Get media by collection name
     */
    public function getMedia(string $collectionName = 'default'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->media()->where('collection_name', $collectionName)->get();
    }

    /**
     * Get the first media item from a collection
     */
    public function getFirstMedia(string $collectionName = 'default'): ?Media
    {
        return $this->media()->where('collection_name', $collectionName)->first();
    }

    /**
     * Get the main/featured image
     */
    public function featuredImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')
            ->where('collection_name', 'featured')
            ->latest();
    }

    /**
     * Get the URL of the first media item in a collection
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $default = ''): string
    {
        $media = $this->getFirstMedia($collectionName);

        return $media ? $media->url : $default;
    }

    /**
     * Add media to the model
     */
    public function addMedia(string $path, array $attributes = []): Media
    {
        return $this->media()->create($attributes + [
            'path' => $path,
            'collection_name' => $attributes['collection_name'] ?? 'default',
        ]);
    }

    /**
     * Clear all media from a collection
     */
    public function clearMediaCollection(string $collectionName = 'default'): void
    {
        $this->media()->where('collection_name', $collectionName)->delete();
    }
}
