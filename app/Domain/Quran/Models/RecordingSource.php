<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecordingSource extends Model
{
    protected $table = 'quran_recording_sources';

    protected $fillable = ['recording_id', 'provider', 'kind', 'source_key', 'external_reciter_id', 'external_recording_id', 'external_timing_id', 'base_url', 'metadata', 'priority', 'is_active', 'revision'];

    protected $casts = ['recording_id' => 'integer', 'metadata' => 'array', 'priority' => 'integer', 'is_active' => 'boolean', 'revision' => 'integer'];

    protected static function booted(): void
    {
        static::updating(function (self $source): void {
            if ($source->isDirty(['base_url', 'external_timing_id', 'metadata', 'provider'])) {
                $source->revision = ((int) $source->getOriginal('revision')) + 1;
            }
        });
        static::saved(fn () => QuranCacheKeys::flushReciters());
        static::deleted(fn () => QuranCacheKeys::flushReciters());
    }

    public function recording(): BelongsTo
    {
        return $this->belongsTo(Reciter::class, 'recording_id');
    }

    public function recitations(): HasMany
    {
        return $this->hasMany(Recitation::class, 'source_id');
    }
}
