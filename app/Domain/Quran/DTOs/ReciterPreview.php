<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

use App\Domain\Quran\Enums\RecitationStyle;

/**
 * What a source says about a reciter before anything is written (admin "Lookup").
 */
final readonly class ReciterPreview
{
    /**
     * @param  list<string>  $previewUrls
     */
    public function __construct(
        public string $name,
        public ?RecitationStyle $style,
        public ?string $qirat,
        public int $filesCount,
        public bool $hasTimestamps,
        public array $previewUrls,
    ) {}

    /**
     * @return array{name: string, style: ?string, qirat: ?string, files_count: int, has_timestamps: bool, preview_urls: list<string>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'style' => $this->style?->value,
            'qirat' => $this->qirat,
            'files_count' => $this->filesCount,
            'has_timestamps' => $this->hasTimestamps,
            'preview_urls' => $this->previewUrls,
        ];
    }
}
