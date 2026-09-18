<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\Verse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Verse>
 */
class VerseFactory extends Factory
{
    protected $model = Verse::class;

    /**
     * Al-Fatihah, so tests that render or search real Arabic have real text.
     *
     * @var array<int, array{uthmani: string, simple: string}>
     */
    private const FATIHAH = [
        1 => ['uthmani' => 'بِسْمِ ٱللَّهِ ٱلرَّحْمَـٰنِ ٱلرَّحِيمِ', 'simple' => 'بسم الله الرحمن الرحيم'],
        2 => ['uthmani' => 'ٱلْحَمْدُ لِلَّهِ رَبِّ ٱلْعَـٰلَمِينَ', 'simple' => 'الحمد لله رب العالمين'],
        3 => ['uthmani' => 'ٱلرَّحْمَـٰنِ ٱلرَّحِيمِ', 'simple' => 'الرحمن الرحيم'],
        4 => ['uthmani' => 'مَـٰلِكِ يَوْمِ ٱلدِّينِ', 'simple' => 'مالك يوم الدين'],
        5 => ['uthmani' => 'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ', 'simple' => 'إياك نعبد وإياك نستعين'],
        6 => ['uthmani' => 'ٱهْدِنَا ٱلصِّرَٰطَ ٱلْمُسْتَقِيمَ', 'simple' => 'اهدنا الصراط المستقيم'],
        7 => ['uthmani' => 'صِرَٰطَ ٱلَّذِينَ أَنْعَمْتَ عَلَيْهِمْ غَيْرِ ٱلْمَغْضُوبِ عَلَيْهِمْ وَلَا ٱلضَّآلِّينَ', 'simple' => 'صراط الذين أنعمت عليهم غير المغضوب عليهم ولا الضالين'],
    ];

    public function definition(): array
    {
        return [
            'id' => fn (): int => ((int) Verse::query()->max('id')) + 1,
            'chapter_id' => Chapter::factory(),
            'verse_number' => fn (array $a): int => ((int) Verse::query()->where('chapter_id', $a['chapter_id'])->max('verse_number')) + 1,
            'verse_key' => fn (array $a): string => $a['chapter_id'].':'.$a['verse_number'],
            'verse_index' => fn (array $a): int => (int) $a['id'],
            'juz_number' => 1,
            'hizb_number' => 1,
            'rub_el_hizb_number' => 1,
            'ruku_number' => 1,
            'manzil_number' => 1,
            'page_number' => fn (array $a): int => (int) $a['chapter_id'],
            'text_uthmani' => fn (array $a): string => $this->text($a, 'uthmani'),
            'text_uthmani_simple' => fn (array $a): string => $this->text($a, 'simple'),
            'text_imlaei_simple' => fn (array $a): string => $this->text($a, 'simple'),
            'text_uthmani_tajweed' => fn (array $a): string => $this->text($a, 'uthmani'),
            'code_v2' => 'ﱁ ﱂ',
            'v2_page' => fn (array $a): int => (int) $a['chapter_id'],
            'is_locked' => false,
        ];
    }

    public function verseNumber(int $number): static
    {
        return $this->state(fn (): array => ['verse_number' => $number]);
    }

    public function sajdah(int $number = 1): static
    {
        return $this->state(fn (): array => ['sajdah_type' => 'recommended', 'sajdah_number' => $number]);
    }

    /**
     * @param  array<string, mixed>  $a
     */
    private function text(array $a, string $form): string
    {
        if ((int) $a['chapter_id'] === 1 && isset(self::FATIHAH[(int) $a['verse_number']])) {
            return self::FATIHAH[(int) $a['verse_number']][$form];
        }

        return $form === 'simple' ? 'قل هو الله أحد' : 'قُلْ هُوَ ٱللَّهُ أَحَدٌ';
    }
}
