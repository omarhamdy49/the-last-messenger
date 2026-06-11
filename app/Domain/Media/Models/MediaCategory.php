<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * MediaCategory Model
 *
 * One row per ProgramCategory enum value. `is_featured` controls whether the
 * category row and its filter pill load on the public programs list (/media).
 *
 * @property int $id
 * @property string $key
 * @property bool $is_featured
 * @property int $display_order
 */
class MediaCategory extends Model
{
    protected $table = 'media_categories';

    protected $fillable = [
        'key',
        'is_featured',
        'display_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'display_order' => 'integer',
    ];

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }
}
