<?php

declare(strict_types=1);

namespace App\Domain\Genealogy\Models;

use App\Domain\Sahaba\Models\Sahabi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Backward-compatible genealogy name for the canonical Sahabi model.
 *
 * There is intentionally no `people` table or second identity store. New code
 * should use Sahabi directly; this alias keeps existing genealogy APIs stable.
 */
class Person extends Sahabi
{
    protected $table = 'sahaba';

    public function father(): BelongsTo
    {
        return $this->belongsTo(self::class, 'father_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mother_id');
    }

    public function childrenAsFather(): HasMany
    {
        return $this->hasMany(self::class, 'father_id');
    }

    public function childrenAsMother(): HasMany
    {
        return $this->hasMany(self::class, 'mother_id');
    }

    /**
     * Compatibility relation: the genealogy record already is the Sahabi.
     */
    public function sahabi(): BelongsTo
    {
        return $this->belongsTo(self::class, 'id', 'id');
    }
}
