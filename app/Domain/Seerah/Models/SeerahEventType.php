<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SeerahEventType Model
 *
 * DB-driven taxonomy for Seerah timeline events. Replaces the former static
 * EventType enum so editors can manage types from the admin portal. The display
 * name is localized via the per-locale translations table (HasLocalizedContent).
 *
 * @property int $id
 * @property string $slug
 * @property string|null $icon
 * @property string|null $color
 * @property string|null $gradient
 * @property bool $is_active
 * @property int $display_order
 */
class SeerahEventType extends Model
{
    use HasFactory, HasLocalizedContent;

    /**
     * @var string
     */
    protected $table = 'seerah_event_types';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'slug',
        'icon',
        'color',
        'gradient',
        'is_active',
        'display_order',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return SeerahEventTypeTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['name'];
    }

    /**
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'seerah_event_type_id';
    }

    /**
     * Events that belong to this type.
     */
    public function events(): HasMany
    {
        return $this->hasMany(SeerahEvent::class, 'seerah_event_type_id');
    }

    /**
     * Scope: active types only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: ordered by display order then slug.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('slug');
    }
}
