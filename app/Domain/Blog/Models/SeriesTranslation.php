<?php

declare(strict_types=1);

namespace App\Domain\Blog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SeriesTranslation Model
 * Translatable fields: title, description
 *
 * @property int $id
 * @property int $series_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 * @property bool $is_ready
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SeriesTranslation extends Model
{
    protected $fillable = [
        'series_id',
        'locale',
        'title',
        'description',
        'is_ready',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function scopeByLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }

    public function scopeReady($query)
    {
        return $query->where('is_ready', true);
    }
}
