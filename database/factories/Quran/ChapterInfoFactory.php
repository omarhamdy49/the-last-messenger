<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\ChapterInfo;
use App\Domain\Quran\Models\QuranResource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChapterInfo>
 */
class ChapterInfoFactory extends Factory
{
    protected $model = ChapterInfo::class;

    public function definition(): array
    {
        return [
            'resource_id' => QuranResource::factory()->chapterInfo(),
            'chapter_id' => Chapter::factory(),
            'language_iso' => 'en',
            'text' => '<h2>Name</h2><p>This surah opens the Book.</p>',
            'short_text' => 'This surah opens the Book.',
            'source' => 'Sayyid Abul Ala Maududi - Tafhim al-Quran',
            'is_locked' => false,
        ];
    }
}
