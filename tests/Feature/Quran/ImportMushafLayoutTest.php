<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportMushafLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_it_imports_words_with_layout_fields_from_the_api(): void
    {
        $this->seedVerses();
        Http::fake([
            'api.quran.com/*' => Http::response($this->pageTwoPayload()),
        ]);

        $exit = $this->artisan('quran:import-mushaf-layout', ['--pages' => '2'])->run();

        $this->assertSame(0, $exit);
        $this->assertSame(4, Word::count());

        $word = Word::where('location', '2:1:1')->firstOrFail();
        $this->assertSame('الٓمٓ', $word->text_uthmani);
        $this->assertSame('word', $word->char_type);
        $this->assertSame(3, $word->line_number);
        $this->assertSame(2, $word->page_number);
        $this->assertSame(2, $word->surah_number);

        $this->assertSame('end', Word::where('location', '2:1:2')->value('char_type'));
    }

    public function test_repository_orders_a_shared_line_by_verse_then_position(): void
    {
        $this->seedVerses();
        Http::fake(['api.quran.com/*' => Http::response($this->pageTwoPayload())]);
        $this->artisan('quran:import-mushaf-layout', ['--pages' => '2'])->run();

        $line = app(VerseRepositoryInterface::class)
            ->getMushafWordsBySurah(2)
            ->where('line_number', 3)
            ->pluck('location')
            ->values()
            ->all();

        // Verse 1 (word + end marker) must precede verse 2, even though position resets per verse.
        $this->assertSame(['2:1:1', '2:1:2', '2:2:1', '2:2:2'], $line);
    }

    public function test_it_is_idempotent_when_run_twice(): void
    {
        $this->seedVerses();
        Http::fake(['api.quran.com/*' => Http::response($this->pageTwoPayload())]);

        $this->artisan('quran:import-mushaf-layout', ['--pages' => '2'])->run();
        $this->artisan('quran:import-mushaf-layout', ['--pages' => '2'])->run();

        $this->assertSame(4, Word::count());
    }

    public function test_it_rejects_out_of_range_pages_without_calling_the_api(): void
    {
        Http::fake();

        $exit = $this->artisan('quran:import-mushaf-layout', ['--pages' => '999'])->run();

        $this->assertSame(1, $exit);
        Http::assertNothingSent();
        $this->assertSame(0, Word::count());
    }

    private function seedVerses(): void
    {
        $surah = Surah::create([
            'revelation_place' => 'madinah',
            'revelation_order' => 87,
            'name_ar' => 'البقرة',
            'name_en' => 'Al-Baqarah',
            'transliteration' => 'Al-Baqarah',
            'verses_count' => 286,
        ]);

        foreach ([1, 2] as $number) {
            Verse::create([
                'surah_id' => $surah->id,
                'verse_number' => $number,
                'verse_key' => "2:{$number}",
                'juz_number' => 1,
                'hizb_number' => 1,
                'rub_el_hizb_number' => 1,
                'ruku_number' => 1,
                'page_number' => 2,
            ]);
        }
    }

    /**
     * Minimal quran.com by_page payload: verses 2:1 and 2:2 sharing mushaf line 3.
     *
     * @return array<string, mixed>
     */
    private function pageTwoPayload(): array
    {
        $word = static fn (string $loc, string $text, string $type): array => [
            'location' => $loc,
            'text_uthmani' => $text,
            'char_type_name' => $type,
            'line_number' => 3,
            'page_number' => 2,
        ];

        return [
            'verses' => [
                ['words' => [$word('2:1:1', 'الٓمٓ', 'word'), $word('2:1:2', '١', 'end')]],
                ['words' => [$word('2:2:1', 'ذَٰلِكَ', 'word'), $word('2:2:2', 'ٱلْكِتَٰبُ', 'word')]],
            ],
            'pagination' => ['next_page' => null],
        ];
    }
}
