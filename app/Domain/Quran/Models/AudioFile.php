<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Database\Factories\Quran\AudioFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One verse's audio in an ayah-by-ayah recitation.
 *
 * @property int $id
 * @property int $resource_id
 * @property int $verse_id
 * @property int|null $qf_id
 * @property string $url absolute
 * @property string|null $format
 * @property int|null $duration_ms
 * @property list<array{0: int, 1: int, 2: int}>|null $segments [word_index, start_ms, end_ms]
 * @property bool $is_local
 */
class AudioFile extends Model
{
    /** @use HasFactory<AudioFileFactory> */
    use HasFactory;

    protected $table = 'quran_audio_files';

    protected $fillable = ['resource_id', 'verse_id', 'qf_id', 'url', 'format', 'duration_ms', 'segments', 'is_local'];

    protected $casts = [
        'resource_id' => 'integer',
        'verse_id' => 'integer',
        'qf_id' => 'integer',
        'duration_ms' => 'integer',
        'segments' => 'array',
        'is_local' => 'boolean',
    ];

    protected static function newFactory(): AudioFileFactory
    {
        return AudioFileFactory::new();
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(QuranResource::class, 'resource_id');
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }
}
