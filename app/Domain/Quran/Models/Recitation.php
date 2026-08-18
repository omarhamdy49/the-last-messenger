<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recitation Model (Audio recording of a Surah by a Reciter)
 *
 * @property int $id
 * @property int $reciter_id
 * @property int $surah_id
 * @property string $audio_url
 * @property string $format
 * @property string|null $quality
 * @property int|null $duration_seconds
 * @property int|null $file_size_kb
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Recitation extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'quran_recitations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'reciter_id',
        'surah_id',
        'audio_url',
        'format',
        'quality',
        'duration_seconds',
        'file_size_kb',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'duration_seconds' => 'integer',
        'file_size_kb' => 'integer',
    ];

    /**
     * Get the reciter that owns this recitation.
     */
    public function reciter(): BelongsTo
    {
        return $this->belongsTo(Reciter::class, 'reciter_id');
    }

    /**
     * Get the surah this recitation is for.
     */
    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'surah_id');
    }
}
