<?php

declare(strict_types=1);

namespace App\Domain\Media\DTOs;

use App\Http\Requests\Admin\MediaProgram\UpdateMediaEpisodeRequest;

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
        );
    }
}
