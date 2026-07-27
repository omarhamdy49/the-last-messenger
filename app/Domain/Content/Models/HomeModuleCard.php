<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;

/**
 * HomeModuleCard Model
 *
 * Homepage module-discovery card (Seerah / Hadith / Quran / ...).
 * Translatable title/description live in home_module_card_translations.
 *
 * @property int $id
 * @property string $key
 * @property HomeModuleCardPlacement $placement
 * @property string|null $icon
 * @property string|null $image
 * @property string|null $target_url Route name or absolute url
 * @property string|null $accent_color
 * @property bool $is_featured
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class HomeModuleCard extends Model
{
    use HasLocalizedContent;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'home_module_cards';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'placement',
        'icon',
        'image',
        'target_url',
        'accent_color',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'placement' => HomeModuleCardPlacement::class,
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return HomeModuleCardTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'home_module_card_id';
    }

    /**
     * Scope: Only active cards.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Order for display.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
