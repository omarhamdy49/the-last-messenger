<?php

declare(strict_types=1);

namespace App\Domain\Media\DTOs;

use App\Http\Requests\Admin\MediaProgram\UpdateMediaEpisodeRequest;
use Illuminate\Http\UploadedFile;

/**
 * Typed payload for updating a MediaEpisode.
 */
final readonly class UpdateMediaEpisodeData
{
    public function __construct(
        public int $seasonNumber,
        public int $episodeNumber,
        public string $slug,
        public array $title,
        public array $description,
        public array $transcript,
        public ?string $videoUrl,
        public ?int $durationSeconds,
        public bool $isFeatured,
        public ?UploadedFile $thumbnail,
        public ?int $thumbnailId,
        public bool $isPublished,
        public ?string $publishAt,
        public array $seoTitle,
        public array $seoDescription,
        public array $seoKeywords,
    ) {}

    public static function fromRequest(UpdateMediaEpisodeRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            seasonNumber: (int) ($validated['season_number'] ?? 1),
            episodeNumber: (int) $validated['episode_number'],
            slug: $validated['slug'],
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            transcript: $validated['transcript'] ?? [],
            videoUrl: $validated['video_url'] ?? null,
            durationSeconds: isset($validated['duration_seconds']) ? (int) $validated['duration_seconds'] : null,
            isFeatured: $request->boolean('is_featured'),
            thumbnail: $request->hasFile('thumbnail') ? $request->file('thumbnail') : null,
            thumbnailId: isset($validated['thumbnail_id']) ? (int) $validated['thumbnail_id'] : null,
            isPublished: $request->boolean('is_published'),
            publishAt: $validated['publish_at'] ?? null,
            seoTitle: $validated['seo_title'] ?? [],
            seoDescription: $validated['seo_description'] ?? [],
            seoKeywords: $validated['seo_keywords'] ?? [],
        );
    }
}
