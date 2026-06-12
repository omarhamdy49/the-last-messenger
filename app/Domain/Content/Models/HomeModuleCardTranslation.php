<?php

declare(strict_types=1);

namespace App\Domain\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HomeModuleCardTranslation Model
 *
 * @property int $id
 * @property int $home_module_card_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property bool $is_ready
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read HomeModuleCard $card
 */
class HomeModuleCardTranslation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'home_module_card_translations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'home_module_card_id',
        'locale',
        'title',
        'description',
        'is_ready',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'home_module_card_id' => 'integer',
        'is_ready' => 'boolean',
    ];

    /**
     * Get the parent card.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(HomeModuleCard::class, 'home_module_card_id');
    }

    /**
     * Scope: Filter by locale.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
