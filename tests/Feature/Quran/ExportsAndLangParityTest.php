<?php

declare(strict_types=1);

namespace Tests\Feature\Quran;

use App\Domain\Quran\Models\Chapter;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\Translation;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Services\QuranDashboardStatsProvider;
use App\Domain\Shared\Services\ExportGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * T088 export adapters + dashboard tile, T089 translation key parity for the 006 admin.
 */
final class ExportsAndLangParityTest extends TestCase
{
    use RefreshDatabase;

    private const LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    private const NEW_GROUPS = ['manage', 'resource_kinds', 'sections', 'styles', 'external_sources', 'scripts', 'export'];

    public function test_chapters_export_has_arabic_headings_and_one_row_per_chapter(): void
    {
        foreach ([1, 2, 3] as $n) {
            Chapter::factory()->number($n)->create();
        }

        $exporter = app(ExportGateway::class)->buildExporter('quran_chapters', 'csv', 'ar', [], ['id', 'name_arabic', 'verses_count', 'is_published']);

        $this->assertSame(['المعرف', 'الاسم العربي', 'عدد الآيات', 'منشورة'], $exporter->headings());
        $rows = $exporter->collection();
        $this->assertCount(3, $rows);
        $this->assertSame(1, $exporter->map($rows->first())[0]);
    }

    public function test_every_quran_module_is_registered(): void
    {
        $keys = app(ExportGateway::class)->getRegisteredModuleKeys();

        foreach (['quran_chapters', 'quran_verses', 'quran_translations', 'quran_tafsirs', 'quran_reciters'] as $key) {
            $this->assertContains($key, $keys);
        }
    }

    public function test_translation_export_needs_a_resource_and_strips_html(): void
    {
        Chapter::factory()->number(1)->withVerses(2)->create();
        $resource = QuranResource::factory()->translation()->create(['name' => 'Saheeh']);
        foreach (Verse::query()->pluck('id') as $verseId) {
            Translation::factory()->create(['resource_id' => $resource->id, 'verse_id' => $verseId, 'text' => 'Praise <sup foot_note="1">1</sup>']);
        }

        $exporter = app(ExportGateway::class)->buildExporter('quran_translations', 'csv', 'en', ['resource_id' => $resource->id]);
        $rows = $exporter->collection();
        $this->assertCount(2, $rows);
        $this->assertSame(['1:1', 'Saheeh', 'Praise 1', 'No'], $exporter->map($rows->first()));

        $this->expectException(InvalidArgumentException::class);
        app(ExportGateway::class)->buildExporter('quran_translations', 'csv', 'en')->collection();
    }

    public function test_dashboard_stats_count_the_corpus_and_active_resources(): void
    {
        Chapter::factory()->number(1)->withVerses(7)->create();
        QuranResource::factory()->translation()->create();
        QuranResource::factory()->chapterReciter()->create();

        $stats = app(QuranDashboardStatsProvider::class)->getStats();

        $this->assertSame(7, $stats['total']);
        $this->assertSame(1, $stats['chapters']);
        $this->assertSame(1, $stats['active_translations']);
        $this->assertSame(1, $stats['active_reciters']);
    }

    public function test_new_quran_lang_groups_have_identical_keys_in_every_locale(): void
    {
        $reference = null;
        foreach (self::LOCALES as $locale) {
            $file = require lang_path("{$locale}/quran.php");
            $keys = array_keys(Arr::dot(Arr::only($file, self::NEW_GROUPS)));
            sort($keys);
            $reference ??= $keys;
            $this->assertSame($reference, $keys, "resources/lang/{$locale}/quran.php differs from ar");
        }
    }

    public function test_every_manage_key_used_in_code_exists(): void
    {
        $files = array_merge(
            glob(resource_path('views/admin/quran/*/*.blade.php')) ?: [],
            glob(resource_path('views/components/admin/quran/*.blade.php')) ?: [],
            [resource_path('views/components/admin/sidebar.blade.php')],
            glob(app_path('Http/Controllers/Admin/Quran/*.php')) ?: [],
            glob(app_path('Http/Requests/Admin/Quran/*.php')) ?: [],
        );

        $missing = [];
        foreach ($files as $file) {
            preg_match_all("/quran\.(manage|export)\.([a-z0-9_]+)(?=['\"])/", (string) file_get_contents($file), $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $key = "quran.{$m[1]}.{$m[2]}";
                foreach (['ar', 'en'] as $locale) {
                    if (! trans()->has($key, $locale, false)) {
                        $missing[] = "{$key} ({$locale}) in ".basename($file);
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }

    /**
     * The same check for the public views. A key missing in both ar and en renders as
     * the raw `quran.public.…` string in the page — which is how two verse-modal tabs
     * shipped unlabelled.
     */
    public function test_every_public_key_used_in_a_view_exists(): void
    {
        $files = array_merge(
            glob(resource_path('views/public/quran/*.blade.php')) ?: [],
            glob(resource_path('views/public/quran/*/*.blade.php')) ?: [],
            glob(resource_path('views/components/public/quran/*.blade.php')) ?: [],
            glob(resource_path('views/components/*.blade.php')) ?: [],
            glob(resource_path('views/components/site/*.blade.php')) ?: [],
        );

        $missing = [];
        foreach ($files as $file) {
            preg_match_all("/quran\.public\.([a-z0-9_]+)(?=['\"])/", (string) file_get_contents($file), $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $key = "quran.public.{$m[1]}";
                foreach (self::LOCALES as $locale) {
                    if (! trans()->has($key, $locale, false)) {
                        $missing[] = "{$key} ({$locale}) in ".basename($file);
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)));
    }
}
