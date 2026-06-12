<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Models;

use App\Domain\Shared\Traits\HasLocalizedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SahabiTimelineItem Model
 *
 * A single entry on a companion's personal timeline (birth, conversion,
 * battles, death...). Localized title/description live in
 * sahabi_timeline_item_translations.
 *
 * @property int $id
 * @property int $sahabi_id
 * @property int $position
 * @property string|null $hijri_date
 * @property int|null $hijri_year Negative values denote years before Hijra
 * @property string|null $color
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Sahabi $sahabi
 */
class SahabiTimelineItem extends Model
{
    use HasLocalizedContent;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sahabi_timeline_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'sahabi_id',
        'position',
        'hijri_date',
        'hijri_year',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sahabi_id' => 'integer',
        'position' => 'integer',
        'hijri_year' => 'integer',
    ];

    /**
     * Get the translation model class.
     */
    public function getTranslationModelClass(): string
    {
        return SahabiTimelineItemTranslation::class;
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
        return 'sahabi_timeline_item_id';
    }

    /**
     * Get the companion this timeline item belongs to.
     */
    public function sahabi(): BelongsTo
    {
        return $this->belongsTo(Sahabi::class, 'sahabi_id');
    }

    /**
     * Scope: Order by timeline position.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
