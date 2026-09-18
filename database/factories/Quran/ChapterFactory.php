<?php

declare(strict_types=1);

namespace Database\Factories\Quran;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\Verse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Chapters are keyed on their number. By default the next free number is used, so
 * `Chapter::factory()->create()` works repeatedly in one test; use `number()` when a
 * test needs a specific chapter.
 *
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    /**
     * Real names for the first chapters keep rendered pages readable in tests.
     *
     * @var array<int, array{arabic: string, simple: string, en: string, verses: int}>
     */
    private const KNOWN = [
        1 => ['arabic' => 'الفاتحة', 'simple' => 'Al-Fatihah', 'en' => 'The Opener', 'verses' => 7],
        2 => ['arabic' => 'البقرة', 'simple' => 'Al-Baqarah', 'en' => 'The Cow', 'verses' => 286],
        3 => ['arabic' => 'آل عمران', 'simple' => "Ali 'Imran", 'en' => 'Family of Imran', 'verses' => 200],
        112 => ['arabic' => 'الإخلاص', 'simple' => 'Al-Ikhlas', 'en' => 'The Sincerity', 'verses' => 4],
    ];

    public function definition(): array
    {
        $id = ((int) Chapter::query()->max('id')) + 1;

        return $this->attributesFor(min($id, Chapter::COUNT));
    }

    public function number(int $id): static
    {
        return $this->state(fn (): array => $this->attributesFor($id));
    }

    /**
     * Create `$count` consecutive verses (keys "{chapter}:1"…) after the chapter.
     */
    public function withVerses(int $count): static
    {
        return $this->afterCreating(function (Chapter $chapter) use ($count): void {
            for ($n = 1; $n <= $count; $n++) {
                Verse::factory()->for($chapter)->verseNumber($n)->create();
            }
            $chapter->update(['verses_count' => $count]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFor(int $id): array
    {
        $known = self::KNOWN[$id] ?? null;

        return [
            'id' => $id,
            'revelation_place' => $id % 3 === 0 ? RevelationPlace::MADINAH : RevelationPlace::MAKKAH,
            'revelation_order' => $id,
            'bismillah_pre' => $id !== 1 && $id !== 9,
            'name_simple' => $known['simple'] ?? 'Surah '.$id,
            'name_complex' => $known['simple'] ?? 'Surah '.$id,
            'name_arabic' => $known['arabic'] ?? 'سورة '.$id,
            'verses_count' => $known['verses'] ?? 10,
            'first_page' => max(1, $id),
            'last_page' => max(1, $id),
            'is_published' => true,
        ];
    }
}
