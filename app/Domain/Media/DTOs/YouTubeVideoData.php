<?php

declare(strict_types=1);

namespace App\Domain\Media\DTOs;

/**
 * Normalized metadata for a single YouTube video, parsed from
 * `yt-dlp --dump-single-json`. The transcript is fetched separately
 * (best-effort) and merged in.
 */
final readonly class YouTubeVideoData
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public ?int $durationSeconds,
        public ?string $channel,
        public ?string $uploadDate, // raw YYYYMMDD from yt-dlp
        public ?string $thumbnailUrl,
        public ?string $transcript = null,
    ) {}

    /**
     * @param  array<string, mixed>  $json
     */
    public static function fromArray(array $json): self
    {
        return new self(
            id: (string) ($json['id'] ?? ''),
            title: trim((string) ($json['title'] ?? '')),
            description: trim((string) ($json['description'] ?? '')),
            durationSeconds: isset($json['duration']) ? (int) $json['duration'] : null,
            channel: $json['channel'] ?? $json['uploader'] ?? null,
            uploadDate: isset($json['upload_date']) ? (string) $json['upload_date'] : null,
            thumbnailUrl: isset($json['thumbnail']) ? (string) $json['thumbnail'] : null,
        );
    }

    /** Replace the title with the one the admin confirmed in the import modal. */
    public function withTitle(string $title): self
    {
        return new self(
            id: $this->id,
            title: $title,
            description: $this->description,
            durationSeconds: $this->durationSeconds,
            channel: $this->channel,
            uploadDate: $this->uploadDate,
            thumbnailUrl: $this->thumbnailUrl,
            transcript: $this->transcript,
        );
    }

    public function withTranscript(?string $transcript): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            description: $this->description,
            durationSeconds: $this->durationSeconds,
            channel: $this->channel,
            uploadDate: $this->uploadDate,
            thumbnailUrl: $this->thumbnailUrl,
            transcript: $transcript,
        );
    }

    /** Four-digit publication year, when known. */
    public function year(): ?int
    {
        if ($this->uploadDate !== null && strlen($this->uploadDate) >= 4) {
            return (int) substr($this->uploadDate, 0, 4);
        }

        return null;
    }
}
