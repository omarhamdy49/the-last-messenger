<?php

declare(strict_types=1);

namespace App\Domain\Media\DTOs;

use App\Http\Requests\Admin\LiveChannel\UpdateLiveChannelRequest;
use Illuminate\Http\UploadedFile;

/**
 * Typed payload for updating a LiveChannel.
 */
final readonly class UpdateLiveChannelData
{
    public function __construct(
        public string $slug,
        public array $title,
        public array $description,
        public ?string $streamingUrl,
        /** Null = auto-detect from the URL at read time. */
        public ?string $streamType,
        public string $status,
        public bool $isPublished,
        public int $displayOrder,
        public ?UploadedFile $thumbnail,
        public ?int $thumbnailId,
    ) {}

    public static function fromRequest(UpdateLiveChannelRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            slug: $validated['slug'],
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            streamingUrl: $validated['streaming_url'] ?? null,
            streamType: filled($validated['stream_type'] ?? null) ? $validated['stream_type'] : null,
            status: $validated['status'] ?? 'offline',
            isPublished: $request->boolean('is_published'),
            displayOrder: (int) ($validated['display_order'] ?? 0),
            thumbnail: $request->hasFile('thumbnail') ? $request->file('thumbnail') : null,
            thumbnailId: isset($validated['thumbnail_id']) ? (int) $validated['thumbnail_id'] : null,
        );
    }
}
