<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Enums;

/**
 * Event Type Enum
 * Represents different categories of timeline events
 */
enum EventType: string
{
    case BIRTH = 'birth';
    case DEATH = 'death';
    case BATTLE = 'battle';
    case MIGRATION = 'migration';
    case REVELATION = 'revelation';
    case TREATY = 'treaty';
    case MARRIAGE = 'marriage';
    case MIRACLE = 'miracle';
    case OTHER = 'other';

    /**
     * Get all values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get label for display
     */
    public function label(): string
    {
        return match ($this) {
            self::BIRTH => 'Birth',
            self::DEATH => 'Death',
            self::BATTLE => 'Battle',
            self::MIGRATION => 'Migration',
            self::REVELATION => 'Revelation',
            self::TREATY => 'Treaty',
            self::MARRIAGE => 'Marriage',
            self::MIRACLE => 'Miracle',
            self::OTHER => 'Other Event',
        };
    }

    /**
     * Get Arabic label
     */
    public function labelArabic(): string
    {
        return match ($this) {
            self::BIRTH => 'ولادة',
            self::DEATH => 'وفاة',
            self::BATTLE => 'غزوة/معركة',
            self::MIGRATION => 'هجرة',
            self::REVELATION => 'نزول وحي',
            self::TREATY => 'معاهدة',
            self::MARRIAGE => 'زواج',
            self::MIRACLE => 'معجزة',
            self::OTHER => 'حدث آخر',
        };
    }

    /**
     * Get icon for UI
     */
    public function icon(): string
    {
        return match ($this) {
            self::BIRTH => 'baby',
            self::DEATH => 'cross',
            self::BATTLE => 'swords',
            self::MIGRATION => 'move',
            self::REVELATION => 'book-open',
            self::TREATY => 'file-text',
            self::MARRIAGE => 'heart',
            self::MIRACLE => 'sparkles',
            self::OTHER => 'circle',
        };
    }

    /**
     * Get color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::BIRTH => 'green',
            self::DEATH => 'gray',
            self::BATTLE => 'red',
            self::MIGRATION => 'blue',
            self::REVELATION => 'purple',
            self::TREATY => 'yellow',
            self::MARRIAGE => 'pink',
            self::MIRACLE => 'cyan',
            self::OTHER => 'slate',
        };
    }
}
