<?php

declare(strict_types=1);

namespace Tests\Feature\Quran\Import;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\ResourceSource;
use App\Domain\Quran\Import\QuranFoundation\Importers\CatalogueImporter;
use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Services\QuranCacheKeys;
use Database\Seeders\QuranMushafSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Quran\Import\Concerns\FakesQuranFoundation;
use Tests\TestCase;

final class CatalogueImporterTest extends TestCase
{
    use FakesQuranFoundation;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpQuranFoundation();
        $this->seed(QuranMushafSeeder::class);
        DB::table('quran_languages')->insert([
            ['id' => 38, 'name' => 'English', 'iso_code' => 'en', 'direction' => 'ltr'],
            ['id' => 9, 'name' => 'Arabic', 'iso_code' => 'ar', 'direction' => 'rtl'],
            ['id' => 174, 'name' => 'Urdu', 'iso_code' => 'ur', 'direction' => 'rtl'],
        ]);
        $this->fakeCatalogue();
    }

    public function test_it_imports_every_kind_without_enabling_anything(): void
    {
        $report = app(CatalogueImporter::class)->run();

        $this->assertTrue($report->passed(), json_encode($report->toArray(), JSON_PRETTY_PRINT) ?: '');

        $saheeh = QuranResource::query()->ofKind(ResourceKind::TRANSLATION)->where('qf_id', 20)->firstOrFail();
        $this->assertSame('Saheeh International', $saheeh->name);
        $this->assertSame('en', $saheeh->language_iso);
        $this->assertSame(ResourceSource::QF, $saheeh->source);
        $this->assertFalse($saheeh->is_active);
        $this->assertFalse($saheeh->sync_enabled);

        $this->assertSame(1, QuranResource::query()->ofKind(ResourceKind::VERSE_MEDIA)->count());
        $this->assertGreaterThan(0, QuranResource::query()->ofKind(ResourceKind::CHAPTER_INFO)->count());
        $this->assertSame(QuranResource::query()->count(), QuranResource::query()->where('is_active', false)->count());
    }

    public function test_reciters_get_style_qirat_arabic_name_and_a_qf_fetch_source(): void
    {
        app(CatalogueImporter::class)->run();

        $abdulBasit = QuranResource::query()->ofKind(ResourceKind::CHAPTER_RECITER)->where('qf_id', 1)->with('translations')->firstOrFail();
        $this->assertSame(RecitationStyle::MUJAWWAD, $abdulBasit->style);
        $this->assertSame('Hafs', $abdulBasit->qirat);
        $this->assertSame(ExternalSource::QF, $abdulBasit->external_source);
        $this->assertSame('1', $abdulBasit->external_ref_id);
        $this->assertSame('عبد الباسط عبد الصمد - مجود', $abdulBasit->displayName('ar'));

        // Maher al-Muaiqly has no Arabic name in QF (translated_name stays English): no fake Arabic row.
        $maher = QuranResource::query()->ofKind(ResourceKind::CHAPTER_RECITER)->where('qf_id', 159)->with('translations')->firstOrFail();
        $this->assertNull($maher->translations->firstWhere('locale', 'ar'));
        $this->assertTrue($maher->metadata['sync_available']);
        $this->assertFalse($abdulBasit->fresh()->metadata['sync_available']);
    }

    public function test_reimport_preserves_editor_choices(): void
    {
        app(CatalogueImporter::class)->run();
        $saheeh = QuranResource::query()->ofKind(ResourceKind::TRANSLATION)->where('qf_id', 20)->firstOrFail();
        $saheeh->update(['is_active' => true, 'is_default' => true, 'sync_enabled' => true, 'display_order' => 9]);

        $second = app(CatalogueImporter::class)->run();

        $this->assertTrue($second->passed());
        $saheeh->refresh();
        $this->assertTrue($saheeh->is_active);
        $this->assertTrue($saheeh->is_default);
        $this->assertTrue($saheeh->sync_enabled);
        $this->assertSame(9, $saheeh->display_order);
        $this->assertSame(0, $second->written);
    }

    public function test_a_qf_slug_never_overwrites_a_manual_resource(): void
    {
        $manual = QuranResource::factory()->translation()->manual()->create(['slug' => 'en-sahih-international', 'name' => 'Our own edition']);

        app(CatalogueImporter::class)->run();

        $this->assertSame('Our own edition', $manual->fresh()->name);
        $this->assertSame('en-sahih-international-20', QuranResource::query()->ofKind(ResourceKind::TRANSLATION)->where('qf_id', 20)->value('slug'));
    }

    public function test_info_text_is_stored_and_a_404_info_is_not_a_failure(): void
    {
        $report = app(CatalogueImporter::class)->run();

        $this->assertTrue($report->passed());
        $this->assertSame('<p>Tafsir Ibn Kathir info.</p>', QuranResource::query()->ofKind(ResourceKind::TAFSIR)->where('qf_id', 169)->value('info'));
        $this->assertNotEmpty(array_filter($report->notes, static fn (string $n): bool => str_contains($n, 'no QF info text')));
    }

    public function test_discovery_creates_sync_only_resources_and_mushafs(): void
    {
        $report = app(CatalogueImporter::class)->run();

        $this->assertTrue($report->passed());
        $this->assertTrue(QuranResource::query()->ofKind(ResourceKind::WORD_TRANSLATION)->where('qf_id', 59)->exists());
        $this->assertTrue(QuranResource::query()->ofKind(ResourceKind::WORD_TRANSLITERATION)->where('qf_id', 60)->exists());
        $this->assertTrue(Mushaf::query()->whereKey(10)->exists());
        $this->assertFalse(Mushaf::query()->findOrFail(10)->is_active);
        $this->assertSame('QCF V2', Mushaf::query()->findOrFail(1)->name); // seeded row untouched
        $this->assertIsArray(Cache::get(QuranCacheKeys::recitationStyles()));
    }

    public function test_preview_lists_only_rows_not_yet_imported(): void
    {
        QuranResource::factory()->translation()->qf(20)->create(['slug' => 'x']);

        $preview = app(CatalogueImporter::class)->preview(ResourceKind::TRANSLATION);

        $this->assertNotContains(20, array_column($preview, 'qf_id'));
        $this->assertContains(85, array_column($preview, 'qf_id'));
    }

    private function fakeCatalogue(): void
    {
        $catalogue = [];
        foreach (['translation', 'tafsir', 'chapter_info', 'verse_media', 'recitation', 'chapter_reciter'] as $kind) {
            $catalogue[$kind] = [
                'en' => $this->qfFixture("catalogue-{$kind}-en"),
                'ar' => $this->qfFixture("catalogue-{$kind}-ar"),
            ];
        }
        $paths = [
            'translations' => 'translation', 'tafsirs' => 'tafsir', 'chapter_infos' => 'chapter_info',
            'verse_media' => 'verse_media', 'recitations' => 'recitation', 'chapter_reciters' => 'chapter_reciter',
        ];
        $styles = $this->qfFixture('recitation-styles');

        Http::fake(function ($request) use ($catalogue, $paths, $styles) {
            $path = urldecode(str_replace('https://apis.test/content/api/v4/', '', $request->url()));

            if ($path === 'resources/recitation_styles') {
                return Http::response($styles);
            }
            if (preg_match('#^resources/tafsirs/169/info#', $path)) {
                return Http::response(['info' => ['id' => 169, 'info' => '<p>Tafsir Ibn Kathir info.</p>']]);
            }
            if (preg_match('#^resources/[a-z_]+/\d+/info#', $path)) {
                return Http::response(['message' => 'not found', 'type' => 'not_found', 'success' => false], 404);
            }
            if (str_starts_with($path, 'resources/sync')) {
                return Http::response(['sync' => [
                    'has_more' => false, 'next_page_url' => null, 'next_sync_token' => 't',
                    'mutations' => [
                        ['type' => 'RESOURCE_CREATE', 'resource_group' => 'word_by_word_translations', 'resource_id' => 59],
                        ['type' => 'RESOURCE_CREATE', 'resource_group' => 'word_by_word_transliterations', 'resource_id' => 60],
                        ['type' => 'RESOURCE_CREATE', 'resource_group' => 'mushafs', 'resource_id' => 1],
                        ['type' => 'RESOURCE_CREATE', 'resource_group' => 'mushafs', 'resource_id' => 10],
                        ['type' => 'RESOURCE_CREATE', 'resource_group' => 'chapter_recitations', 'resource_id' => 159],
                    ],
                ]]);
            }
            if (preg_match('#^resources/([a-z_]+)\?language=([a-z]+)$#', $path, $m) && isset($paths[$m[1]])) {
                $kind = $paths[$m[1]];

                // fr/tr/ur: QF answers with English names; reuse the English fixture.
                return Http::response($catalogue[$kind][$m[2]] ?? $catalogue[$kind]['en']);
            }

            return null;
        });
    }
}
