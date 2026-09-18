<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Database\Factories\Quran\ChapterAudioFileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A whole-chapter audio file of a chapter reciter.
 *
 * @property int $id
 * @property int $resource_id
 * @property int $chapter_id
 * @property int|null $qf_id
 * @property string $url absolute
 * @property int|null $file_size bytes
 * @property string $format
 * @property int|null $duration_ms
 * @property bool $is_local
 * @property bool $has_timestamps
 */
class ChapterAudioFile extends Model
{
    /** @use HasFactory<ChapterAudioFileFactory> */
    use HasFactory;

    protected $table = 'quran_chapter_audio_files';

    protected $fillable = [
        'resource_id', 'chapter_id', 'qf_id', 'url', 'file_size', 'format', 'duration_ms', 'is_local', 'has_timestamps',
    ];

    protected $casts = [
        'resource_id' => 'integer',
        'chapter_id' => 'integer',
        'qf_id' => 'integer',
        'file_size' => 'integer',
        'duration_ms' => 'integer',
        'is_local' => 'boolean',
        'has_timestamps' => 'boolean',
    ];

    protected static function newFactory(): ChapterAudioFileFactory
    {
        return ChapterAudioFileFactory::new();
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(QuranResource::class, 'resource_id');
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function verseTimestamps(): HasMany
    {
        return $this->hasMany(ChapterAudioTimestamp::class, 'chapter_audio_file_id')->orderBy('timestamp_from');
    }
}
