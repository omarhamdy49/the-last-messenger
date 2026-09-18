<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Models\AudioFile;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Verse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudioFile>
 */
class AudioFileFactory extends Factory
{
    protected $model = AudioFile::class;

    public function definition(): array
    {
        return [
            'resource_id' => QuranResource::factory()->recitation(),
            'verse_id' => Verse::factory(),
            'url' => function (array $a): string {
                $key = (string) Verse::query()->whereKey($a['verse_id'])->value('verse_key');
                [$c, $v] = array_map('intval', explode(':', $key.':0'));

                return sprintf('https://verses.quran.foundation/Alafasy/mp3/%03d%03d.mp3', $c, $v);
            },
            'format' => 'mp3',
            'is_local' => false,
        ];
    }
}
