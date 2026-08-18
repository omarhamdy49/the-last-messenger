<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * MenuItemTranslation Model
 *
 * @property int $id
 * @property int $menu_item_id
 * @property string $locale
 * @property string|null $label
 * @property bool $is_ready
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read MenuItem $menuItem
 */
class MenuItemTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'menu_item_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'menu_item_id',
        'locale',
        'label',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'menu_item_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent menu item.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_item_id');
    }

    /**
     * Scope: Filter by locale.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
