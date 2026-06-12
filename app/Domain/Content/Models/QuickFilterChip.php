<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;

/**
 * QuickFilterChip Model
 *
 * Hero quick-search chip on the public home page. The translatable label
 * lives in quick_filter_chip_translations.
 *
 * @property int $id
 * @property string $key
 * @property string|null $target_query Search query the chip triggers
 * @property string|null $target_url Route name or absolute url
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class QuickFilterChip extends Model
{
    use HasLocalizedContent;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quick_filter_chips';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'target_query',
        'target_url',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return QuickFilterChipTranslation::class;
    }

    /**
     * Get the list of translatable field names.
     */
    public function getTranslatableFields(): array
    {
        return ['label'];
    }

    /**
     * Get the foreign key name for translations.
     */
    public function getTranslationForeignKey(): string
    {
        return 'quick_filter_chip_id';
    }

    /**
     * Scope: Only active chips.
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
