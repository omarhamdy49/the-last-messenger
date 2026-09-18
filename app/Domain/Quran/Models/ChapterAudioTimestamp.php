<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Where one verse starts and ends inside a chapter audio file, with optional
 * word-level segments.
 *
 * @property int $id
 * @property int $chapter_audio_file_id
 * @property int $verse_id
 * @property int $timestamp_from ms
 * @property int $timestamp_to ms
 * @property int|null $duration ms, mirrors source (may be negative in legacy data)
 * @property list<array{0: int, 1: int, 2: int}>|null $segments [word_index, start_ms, end_ms]
 */
class ChapterAudioTimestamp extends Model
{
    protected $table = 'quran_chapter_audio_timestamps';

    protected $fillable = ['chapter_audio_file_id', 'verse_id', 'timestamp_from', 'timestamp_to', 'duration', 'segments'];

    protected $casts = [
        'chapter_audio_file_id' => 'integer',
        'verse_id' => 'integer',
        'timestamp_from' => 'integer',
        'timestamp_to' => 'integer',
        'duration' => 'integer',
        'segments' => 'array',
    ];

    public function chapterAudioFile(): BelongsTo
    {
        return $this->belongsTo(ChapterAudioFile::class, 'chapter_audio_file_id');
    }

    public function verse(): BelongsTo
    {
        return $this->belongsTo(Verse::class, 'verse_id');
    }
}
