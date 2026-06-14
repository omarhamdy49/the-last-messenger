<?php

declare(strict_types=1);

namespace App\Domain\Media\DTOs;

use App\Http\Requests\Admin\LiveChannel\StoreLiveChannelRequest;
use Illuminate\Http\UploadedFile;

/**
 * Typed payload for creating a LiveChannel.
 * Built from the validated admin FormRequest so services never touch HTTP.
 */
final readonly class CreateLiveChannelData
{
    public function __construct(
        public ?string $slug,
        public array $title,
        public array $description,
        public ?string $streamingUrl,
        public string $status,
        public bool $isPublished,
        public int $displayOrder,
        public ?UploadedFile $thumbnail,
        public ?int $thumbnailId,
    ) {}

    public static function fromRequest(StoreLiveChannelRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            slug: filled($validated['slug'] ?? null) ? $validated['slug'] : null,
            title: $validated['title'] ?? [],
            description: $validated['description'] ?? [],
            streamingUrl: $validated['streaming_url'] ?? null,
            status: $validated['status'] ?? 'offline',
            isPublished: $request->boolean('is_published'),
            displayOrder: (int) ($validated['display_order'] ?? 0),
            thumbnail: $request->hasFile('thumbnail') ? $request->file('thumbnail') : null,
            thumbnailId: isset($validated['thumbnail_id']) ? (int) $validated['thumbnail_id'] : null,
        );
    }
}
