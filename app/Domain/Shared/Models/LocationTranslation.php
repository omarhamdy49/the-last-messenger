<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LocationTranslation Model
 * Translatable fields: name, description
 *
 * @property int $id
 * @property int $location_id
 * @property string $locale
 * @property string $name
 * @property string|null $description
 * @property bool $is_ready
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class LocationTranslation extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'location_id',
        'locale',
        'name',
        'description',
        'is_ready',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_ready' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function scopeByLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function scopeReady(Builder $query): Builder
    {
        return $query->where('is_ready', true);
    }
}
