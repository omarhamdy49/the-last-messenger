<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTafsir;
use App\Domain\Quran\Models\VerseTranslation;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Admin — read-only Islamic-data import dashboards.
 *
 * Mirrors the coverage checks of `islamic-data:verify`
 * (App\Console\Commands\IslamicData\VerifyCommand) as web screens:
 * per-collection hadith coverage vs configured floors + source file
 * counts, and Quran totals vs canonical expectations.
 *
 * Per-run import history lives on the Hadith import-runs screen, which reads
 * `hadith_import_runs` — the table that replaced `hadith_sync_logs`.
 */
class ImportStatusController extends Controller
{
    /**
     * Coverage status page (Quran totals + per-collection hadith table).
     */
    public function index(): View
    {
        return view('admin.content.imports.index', [
            'quranChecks' => $this->quranChecks(),
            'hadithRows' => $this->hadithRows(),
        ]);
    }

    /**
     * Quran coverage checks (same queries as islamic-data:verify).
     *
     * @return array<int, array{label: string, actual: int|string, expected: string, passes: bool}>
     */
    private function quranChecks(): array
    {
        $expectedSurahs = (int) config('islamic-data.expected.surahs');
        $expectedVerses = (int) config('islamic-data.expected.verses');

        $surahs = Surah::count();
        $verses = Verse::count();
        $tafsirRows = VerseTafsir::count();

        $translationsByLang = VerseTranslation::query()
            ->selectRaw('lang, COUNT(*) AS row_count')
            ->groupBy('lang')
            ->pluck('row_count', 'lang')
            ->all();

        $checks = [
            $this->check(__('admin.content_imports_surahs'), $surahs, "= {$expectedSurahs}", $surahs === $expectedSurahs),
            $this->check(__('admin.content_imports_verses'), $verses, "= {$expectedVerses}", $verses === $expectedVerses),
            $this->check(__('admin.content_imports_tafsir_rows'), $tafsirRows, '> 0', $tafsirRows > 0),
        ];

        foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $lang) {
            $rowCount = (int) ($translationsByLang[$lang] ?? 0);
            $checks[] = $this->check(
                __('admin.content_imports_translations')." [{$lang}]",
                $rowCount,
                "= {$expectedVerses}",
                $rowCount === $expectedVerses,
            );
        }

        return $checks;
    }

    /**
     * Per-collection hadith coverage (DB counts vs floors + file counts).
     *
     * @return array<int, array{directory: string, source_slug: string, files: int|null, floor: int, db: int, passes: bool}>
     */
    private function hadithRows(): array
    {
        $source = (string) config('islamic-data.hadith_production_dir');
        $map = (array) config('islamic-data.hadith_collection_map');
        $floors = (array) config('islamic-data.expected_hadiths');

        // hadith_hadiths carries collection_id directly, so this needs no join
        // through books and chapters — and must not use one: chapter_id is NULL
        // for collections with has_chapters = 0, which an inner chapter join
        // would silently count as zero coverage.
        $dbCounts = HadithCollection::query()
            ->leftJoin('hadith_hadiths', 'hadith_hadiths.collection_id', '=', 'hadith_collections.id')
            ->selectRaw('hadith_collections.code, COUNT(hadith_hadiths.id) AS hadith_count')
            ->groupBy('hadith_collections.code')
            ->pluck('hadith_count', 'code')
            ->all();

        $rows = [];
        $totalFloor = 0;
        $totalDb = 0;

        // The production directory name is also the collection `code` on which
        // the rebuilt schema keys, so the map's value is only a display label.
        foreach ($map as $directory => $sourceSlug) {
            $dirPath = rtrim($source, '/')."/{$directory}";
            $fileCount = is_dir($dirPath) ? count(glob($dirPath.'/*.json') ?: []) : null;
            $floor = (int) ($floors[$directory] ?? 0);
            $dbCount = (int) ($dbCounts[$directory] ?? 0);

            $totalFloor += $floor;
            $totalDb += $dbCount;

            $rows[] = [
                'directory' => $directory,
                'source_slug' => $sourceSlug,
                'files' => $fileCount,
                'floor' => $floor,
                'db' => $dbCount,
                'passes' => $floor > 0 && $dbCount >= $floor,
            ];
        }

        $hadithsMissingArabic = Hadith::query()
            ->whereDoesntHave('texts', fn ($query) => $query->where('locale', 'ar'))
            ->count();
        $knownMissingArabic = (int) config('islamic-data.known_missing_arabic');

        $rows[] = [
            'directory' => 'TOTAL',
            'source_slug' => '',
            'files' => null,
            'floor' => $totalFloor,
            'db' => $totalDb,
            'passes' => $totalDb >= $totalFloor,
        ];

        $rows[] = [
            'directory' => __('admin.content_imports_missing_arabic'),
            'source_slug' => '',
            'files' => null,
            'floor' => $knownMissingArabic,
            'db' => $hadithsMissingArabic,
            'passes' => $hadithsMissingArabic <= $knownMissingArabic,
        ];

        return $rows;
    }

    /**
     * @return array{label: string, actual: int|string, expected: string, passes: bool}
     */
    private function check(string $label, int|string $actual, string $expected, bool $passes): array
    {
        return [
            'label' => $label,
            'actual' => $actual,
            'expected' => $expected,
            'passes' => $passes,
        ];
    }
}
