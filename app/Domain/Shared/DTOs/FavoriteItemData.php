<?php

declare(strict_types=1);

namespace App\Domain\Shared\DTOs;

use App\Domain\Shared\Enums\FavorableType;

/**
 * View-ready representation of a single bookmarked item.
 *
 * Decouples the favorites views from each module's internal model/translation
 * structure: the service flattens the polymorphic target into this stable shape
 * (title, excerpt, link, icon) so a Blade template never reaches into another
 * domain module's relations.
 */
final readonly class FavoriteItemData
{
    public function __construct(
        public int $favoriteId,
        public FavorableType $type,
        public int $contentId,
        public string $title,
        public ?string $excerpt,
        public ?string $url,
        public string $icon,
        public string $typeLabelKey,
        public string $createdAt,
        public bool $isOrphaned = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'favorite_id' => $this->favoriteId,
            'type' => $this->type->value,
            'content_id' => $this->contentId,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
            'icon' => $this->icon,
            'type_label_key' => $this->typeLabelKey,
            'created_at' => $this->createdAt,
            'is_orphaned' => $this->isOrphaned,
        ];
    }
}
