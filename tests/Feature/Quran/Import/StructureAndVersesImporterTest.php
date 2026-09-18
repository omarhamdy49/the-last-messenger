<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Import\QuranFoundation\Importers\StructureImporter;
use App\Domain\Quran\Import\QuranFoundation\Importers\VersesImporter;
use App\Domain\Quran\Import\QuranFoundation\Importers\WordsImporter;
use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\Section;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use App\Domain\Quran\Models\WordGloss;
use Database\Seeders\QuranMushafSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

/**
 * Structure, verses and words from trimmed real QF production responses
 * (tests/Fixtures/qf). Structure fixtures are whole, so the gates see real totals.
 */
final class StructureAndVersesImporterTest extends TestCase
{
    use FakesQuranFoundation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpQuranFoundation();
        $this->seed(QuranMushafSeeder::class);
    }

    public function test_structure_import_fills_every_axis_and_passes_the_gates(): void
    {
        $this->fakeStructure();

        $report = app(StructureImporter::class)->run();

        $this->assertTrue($report->passed(), json_encode($report->toArray(), JSON_PRETTY_PRINT) ?: '');
        $this->assertSame(114, Chapter::query()->count());
        $this->assertSame(570, DB::table('quran_chapter_translations')->count());
        $this->assertSame(30, Section::query()->ofType(SectionType::JUZ)->count());
        $this->assertSame(558, Section::query()->ofType(SectionType::RUKU)->count());
        $this->assertSame(604, DB::table('quran_mushaf_pages')->where('mushaf_id', 1)->count());
        $this->assertSame(80, DB::table('quran_languages')->count());

        $fatihah = Chapter::query()->with('translations')->findOrFail(1);
        $this->assertSame('Al-Fatihah', $fatihah->name_simple);
        $this->assertSame('The Opener', $fatihah->displayName('en'));
        $this->assertSame('Bakara', Chapter::query()->with('translations')->findOrFail(2)->displayName('tr'));
        // Real QF returns English for 112 of 114 Arabic names; Arabic comes from name_arabic.
        $this->assertSame('البقرة', DB::table('quran_chapter_translations')->where(['chapter_id' => 2, 'locale' => 'ar'])->value('name'));
        $this->assertSame(['1' => '1-7', '2' => '1-141'], Section::query()->ofType(SectionType::JUZ)->where('number', 1)->firstOrFail()->verse_mapping);
    }

    public function test_structure_import_is_idempotent(): void
    {
        $this->fakeStructure();
        app(StructureImporter::class)->run();

        $second = app(StructureImporter::class)->run();

        $this->assertTrue($second->passed());
        $this->assertSame(0, $second->written);
        $this->assertSame(0, $second->updated);
    }

    public function test_a_truncated_section_list_fails_its_gate(): void
    {
        $this->fakeStructure(['juzs' => ['juzs' => array_slice($this->qfFixture('juzs')['juzs'], 0, 10)]]);

        $report = app(StructureImporter::class)->run();

        $this->assertFalse($report->passed());
        $this->assertContains(false, array_column(array_filter($report->gates, static fn (array $g): bool => str_starts_with($g['message'], 'juzs')), 'passed'));
    }

    public function test_verses_import_writes_metadata_and_every_script_for_a_chapter(): void
    {
        $this->fakeStructure();
        app(StructureImporter::class)->run();
        $this->fakeVersesChapter1();

        $report = app(VersesImporter::class)->run(1, 1);

        $this->assertTrue($report->passed(), json_encode($report->toArray(), JSON_PRETTY_PRINT) ?: '');
        $this->assertSame(7, Verse::query()->count());

        $verse = Verse::query()->where('verse_key', '1:1')->firstOrFail();
        $this->assertSame($this->qfFixture('quran-verses-uthmani-chapter-1')['verses'][0]['text_uthmani'], $verse->text_uthmani);
        $this->assertStringContainsString('<tajweed class=ham_wasl>', (string) $verse->text_uthmani_tajweed);
        $this->assertSame('ﱁ ﱂ ﱃ ﱄ ﱅ', $verse->code_v2);
        $this->assertSame('https://c22506.r6.cf1.rackcdn.com/1_1.png', $verse->image_url);
        $this->assertSame(1, $verse->verse_index);
        foreach (Script::cases() as $script) {
            $this->assertNotNull($verse->textFor($script), $script->value.' missing');
        }
    }

    public function test_verses_import_keeps_an_editor_locked_verse(): void
    {
        $this->fakeStructure();
        app(StructureImporter::class)->run();
        $this->fakeVersesChapter1();
        app(VersesImporter::class)->run(1, 1);

        Verse::query()->where('verse_key', '1:2')->update(['text_uthmani' => 'edited', 'is_locked' => true]);

        $report = app(VersesImporter::class)->run(1, 1);

        $this->assertSame('edited', Verse::query()->where('verse_key', '1:2')->value('text_uthmani'));
        $this->assertSame(1, $report->skippedLocked);
    }

    public function test_verses_import_requires_structure_first(): void
    {
        $report = app(VersesImporter::class)->run(1, 1);

        $this->assertFalse($report->passed());
        $this->assertSame('chapters', $report->failed[0]['key']);
    }

    public function test_words_import_stores_words_and_only_genuine_glosses(): void
    {
        $this->fakeStructure();
        app(StructureImporter::class)->run();
        $this->fakeVersesChapter1();
        app(VersesImporter::class)->run(1, 1);

        $report = app(WordsImporter::class)->run(1, 1, ['en', 'ar']);

        $this->assertTrue($report->passed(), json_encode($report->toArray(), JSON_PRETTY_PRINT) ?: '');
        $this->assertSame($this->fixtureWordCount(), Word::query()->count());

        $first = Word::query()->findOrFail(1);
        $this->assertSame('1:1:1', $first->location);
        $this->assertSame('https://verses.quran.foundation/wbw/001_001_001.mp3', $first->audio_url);
        $this->assertSame('ﱁ', $first->code_v2);

        $this->assertSame('In (the) name', WordGloss::query()->where('word_id', 1)->where('kind', 'translation')->where('language_iso', 'en')->value('text'));
        $this->assertSame("bis'mi", WordGloss::query()->where('word_id', 1)->where('kind', 'transliteration')->value('text'));
        // QF answered `language=ar` with English glosses: none may be stored as Arabic.
        $this->assertSame(0, WordGloss::query()->where('language_iso', 'ar')->count());

        $again = app(WordsImporter::class)->run(1, 1, ['en', 'ar']);
        $this->assertSame(0, $again->written + $again->updated);
    }

    private function fixtureWordCount(): int
    {
        return array_sum(array_map(
            static fn (array $verse): int => count($verse['words']),
            $this->qfFixture('verses-by-chapter-1-words-en')['verses'],
        ));
    }

    /**
     * @param  array<string, array<string, mixed>>  $overrides  path => body
     */
    private function fakeStructure(array $overrides = []): void
    {
        $bodies = [
            'chapters?language=en' => $this->qfFixture('chapters-en'),
            'chapters?language=ar' => $this->qfFixture('chapters-ar'),
            'chapters?language=fr' => $this->qfFixture('chapters-fr'),
            'chapters?language=tr' => $this->qfFixture('chapters-tr'),
            'chapters?language=ur' => $this->qfFixture('chapters-ur'),
            'juzs' => $this->qfFixture('juzs'),
            'hizbs' => $this->qfFixture('hizbs'),
            'rub_el_hizbs' => $this->qfFixture('rub_el_hizbs'),
            'manzils' => $this->qfFixture('manzils'),
            'rukus' => $this->qfFixture('rukus'),
            'pages?mushaf=1' => $this->qfFixture('pages'),
            'resources/languages' => $this->qfFixture('languages'),
        ];

        foreach ($overrides as $path => $body) {
            $bodies[$path] = $body;
        }

        Http::fake(function ($request) use ($bodies) {
            $path = urldecode(str_replace('https://apis.test/content/api/v4/', '', $request->url()));

            // null lets a later fake answer; stray requests are blocked by the trait.
            return isset($bodies[$path]) ? Http::response($bodies[$path]) : null;
        });
    }

    private function fakeVersesChapter1(): void
    {
        $fixtures = [];
        foreach (Script::cases() as $script) {
            $fixtures['quran/verses/'.$script->qfPath().'?chapter_number=1'] = $this->qfFixture('quran-verses-'.$script->qfPath().'-chapter-1');
        }
        $meta = $this->qfFixture('verses-by-chapter-1-meta');
        $wordsEn = $this->qfFixture('verses-by-chapter-1-words-en');
        $wordsAr = $this->qfFixture('verses-by-chapter-1-words-ar');
        // The Arabic words fixture only carries a few fields; tests import words from `en` first.

        Http::fake(function ($request) use ($fixtures, $meta, $wordsEn, $wordsAr) {
            $url = urldecode($request->url());
            $path = str_replace('https://apis.test/content/api/v4/', '', $url);

            if (isset($fixtures[$path])) {
                return Http::response($fixtures[$path]);
            }
            if (str_starts_with($path, 'verses/by_chapter/1') && str_contains($path, 'words=true')) {
                return Http::response(str_contains($path, 'language=ar') ? $wordsAr : $wordsEn);
            }
            if (str_starts_with($path, 'verses/by_chapter/1')) {
                return Http::response($meta);
            }

            return null;
        });
    }
}
