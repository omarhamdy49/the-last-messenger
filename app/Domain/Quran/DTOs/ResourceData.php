<?php

declare(strict_types=1);

namespace App\Domain\Quran\DTOs;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\RecitationStyle;

/**
 * Editor-controlled fields of a catalogue resource. `kind`, `qf_id` and `source`
 * are never editable.
 */
final readonly class ResourceData
{
    /**
     * @param  array<string, string|null>  $nameTranslations
     * @param  array<string, string|null>  $descriptionTranslations
     */
    public function __construct(
        public string $slug,
        public string $name,
        public array $nameTranslations,
        public array $descriptionTranslations,
        public ?string $authorName,
        public ?string $languageIso,
        public ?RecitationStyle $style,
        public ?string $qirat,
        public ?string $info,
        public ?string $imagePath,
        public bool $removeImage,
        public bool $isActive,
        public bool $isDefault,
        public bool $syncEnabled,
        public int $displayOrder,
        public ?ExternalSource $externalSource,
        public ?string $externalRefId,
        public bool $storeAudioLocally,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated, ?string $imagePath = null): self
    {
        $strings = static fn (mixed $values): array => array_map(
            static fn ($v): ?string => $v === null ? null : (string) $v,
            is_array($values) ? $values : [],
        );

        $source = isset($validated['external_source']) && $validated['external_source'] !== ''
            ? ExternalSource::from((string) $validated['external_source'])
            : null;

        return new self(
            slug: (string) $validated['slug'],
            name: (string) $validated['name'],
            nameTranslations: $strings($validated['name_translations'] ?? []),
            descriptionTranslations: $strings($validated['description_translations'] ?? []),
            authorName: isset($validated['author_name']) && $validated['author_name'] !== '' ? (string) $validated['author_name'] : null,
            languageIso: isset($validated['language_iso']) && $validated['language_iso'] !== '' ? (string) $validated['language_iso'] : null,
            style: isset($validated['style']) && $validated['style'] !== '' ? RecitationStyle::from((string) $validated['style']) : null,
            qirat: isset($validated['qirat']) && $validated['qirat'] !== '' ? (string) $validated['qirat'] : null,
            info: isset($validated['info']) && $validated['info'] !== '' ? (string) $validated['info'] : null,
            imagePath: $imagePath,
            removeImage: (bool) ($validated['remove_image'] ?? false),
            isActive: (bool) ($validated['is_active'] ?? false),
            isDefault: (bool) ($validated['is_default'] ?? false),
            syncEnabled: (bool) ($validated['sync_enabled'] ?? false),
            displayOrder: (int) ($validated['display_order'] ?? 0),
            externalSource: $source,
            externalRefId: $source !== null ? (string) ($validated['external_ref_id'] ?? '') : null,
            storeAudioLocally: (bool) ($validated['store_audio_locally'] ?? false),
        );
    }
}
