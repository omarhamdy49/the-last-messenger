<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Enums\CharType;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Word>
 */
class WordFactory extends Factory
{
    protected $model = Word::class;

    public function definition(): array
    {
        return [
            'id' => fn (): int => ((int) Word::query()->max('id')) + 1,
            'verse_id' => Verse::factory(),
            'position' => fn (array $a): int => ((int) Word::query()->where('verse_id', $a['verse_id'])->max('position')) + 1,
            'location' => function (array $a): string {
                $key = (string) Verse::query()->whereKey($a['verse_id'])->value('verse_key');

                return $key.':'.$a['position'];
            },
            'char_type' => CharType::WORD,
            'text_uthmani' => 'ٱللَّهِ',
            'text_uthmani_simple' => 'الله',
            'text_qpc_hafs' => 'ٱللَّهِ',
            'code_v2' => 'ﱁ',
            'v2_page' => 1,
            'page_number' => 1,
            'line_number' => 2,
            'audio_url' => fn (array $a): string => sprintf('https://verses.quran.foundation/wbw/001_001_%03d.mp3', (int) $a['position']),
        ];
    }

    public function end(): static
    {
        return $this->state(fn (): array => ['char_type' => CharType::END, 'text_uthmani' => '١', 'audio_url' => null]);
    }
}
