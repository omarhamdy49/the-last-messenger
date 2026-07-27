<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * SahabiCategory Model
 *
 * Lookup table for historical-rank categories (badr, ansar, muhajir, ...).
 * A Sahabi can hold several at once via the `sahabi_category` pivot —
 * replaces the old single-value `sahaba.status` enum.
 *
 * @property int $id
 * @property string $key
 * @property int $rank
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SahabiCategory extends Model
{
    protected $table = 'sahabi_categories';

    protected $fillable = [
        'key',
        'rank',
    ];

    protected $casts = [
        'rank' => 'integer',
    ];

    public $timestamps = true;

    public function sahaba(): BelongsToMany
    {
        return $this->belongsToMany(Sahabi::class, 'sahabi_category', 'category_id', 'sahabi_id')
            ->withTimestamps();
    }
}
