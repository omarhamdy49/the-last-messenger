<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterAudioFile;
use App\Domain\Quran\Models\ChapterAudioTimestamp;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Verse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChapterAudioFile>
 */
class ChapterAudioFileFactory extends Factory
{
    protected $model = ChapterAudioFile::class;

    public function definition(): array
    {
        return [
            'resource_id' => QuranResource::factory()->chapterReciter(),
            'chapter_id' => Chapter::factory(),
            'url' => fn (array $a): string => sprintf('https://download.quranicaudio.com/qdc/test/%d.mp3', (int) $a['chapter_id']),
            'file_size' => 710784,
            'format' => 'mp3',
            'is_local' => false,
            'has_timestamps' => false,
        ];
    }

    /**
     * One 5-second timestamp row per existing verse of the chapter, with two word
     * segments each.
     */
    public function withTimestamps(): static
    {
        return $this->afterCreating(function (ChapterAudioFile $file): void {
            $verses = Verse::query()->select(['id', 'verse_number'])
                ->where('chapter_id', $file->chapter_id)->orderBy('verse_number')->get();

            foreach ($verses as $i => $verse) {
                $from = $i * 5000;
                ChapterAudioTimestamp::query()->create([
                    'chapter_audio_file_id' => $file->id,
                    'verse_id' => $verse->id,
                    'timestamp_from' => $from,
                    'timestamp_to' => $from + 5000,
                    'duration' => 5000,
                    'segments' => [[1, $from, $from + 2500], [2, $from + 2500, $from + 5000]],
                ]);
            }

            $file->update(['has_timestamps' => $verses->isNotEmpty()]);
        });
    }
}
