<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class HadithRebuildRepository
{
    private const JSON_COLUMNS = [
        'hadith_collections' => ['name', 'description'],
        'hadith_books' => ['title', 'description'],
        'hadith_chapters' => ['name', 'description'],
        'hadith_scholars' => ['name', 'full_name', 'kunya', 'biography'],
        'hadith_themes' => ['name'],
        'hadith_narrators' => ['name', 'full_name', 'kunya', 'biography'],
        'hadith_related_verses' => ['relevance'],
        'hadith_applications' => ['category', 'description', 'example'],
        'hadith_fiqh_rulings' => ['title', 'ruling', 'details'],
        'hadith_sync_logs' => ['metadata', 'summary'],
    ];

    private const HADITH_CHILD_TABLES = [
        'hadith_related_verses',
        'hadith_fiqh_rulings',
        'hadith_applications',
        'hadith_commentaries',
        'hadith_keywords',
        'hadith_hadith_themes',
        'hadith_isnad',
        'hadith_gradings',
        'hadith_sources',
        'hadith_translations',
    ];

    private const SHARED_TABLES = [
        'hadith_scholars',
        'hadith_themes',
        'hadith_narrators',
    ];

    public function quranVerseIds(array $coordinates): array
    {
        if ($coordinates === []) {
            return [];
        }

        $surahs = array_values(array_unique(array_column($coordinates, 'surah')));
        $ayahs = array_values(array_unique(array_column($coordinates, 'ayah')));
        $requested = array_fill_keys(array_map(
            fn (array $coordinate): string => "{$coordinate['surah']}:{$coordinate['ayah']}",
            $coordinates,
        ), true);
        $ids = [];

        foreach (DB::table('quran_verses')
            ->whereIn('surah_id', $surahs)
            ->whereIn('verse_number', $ayahs)
            ->get(['id', 'surah_id', 'verse_number']) as $verse) {
            $key = "{$verse->surah_id}:{$verse->verse_number}";
            if (isset($requested[$key])) {
                $ids[$key] = (int) $verse->id;
            }
        }

        return $ids;
    }

    public function replaceCollection(string $collection, array $tables): array
    {
        $expected = array_map('count', $tables);

        return DB::transaction(function () use ($collection, $tables, $expected): array {
            $this->deleteCollection($collection);

            foreach ($tables as $table => $rows) {
                $this->writeRows($table, $rows);
            }

            $actual = $this->collectionCounts($collection);
            foreach ($expected as $table => $count) {
                if (($actual[$table] ?? null) !== $count) {
                    throw new RuntimeException(
                        "{$table} count assertion failed: portal {$count}, database ".($actual[$table] ?? 'missing'),
                    );
                }
            }

            return [
                'database' => [
                    'name' => DB::connection()->getDatabaseName(),
                    'host' => config('database.connections.mysql.host'),
                    'port' => (string) config('database.connections.mysql.port'),
                ],
                'rows' => $actual,
            ];
        });
    }

    public function collectionCounts(string $collection): array
    {
        $collectionId = DB::table('hadith_collections')->where('code', $collection)->value('id');
        if ($collectionId === null) {
            return array_fill_keys(array_keys($this->emptyCounts()), 0);
        }

        $bookIds = DB::table('hadith_books')->where('collection_id', $collectionId)->pluck('id');
        $chapterIds = DB::table('hadith_chapters')->whereIn('book_id', $bookIds)->pluck('id');
        $hadithIds = DB::table('hadith_hadiths')->whereIn('chapter_id', $chapterIds)->pluck('id');
        $narratorIds = DB::table('hadith_isnad')->whereIn('hadith_id', $hadithIds)->pluck('narrator_id')
            ->merge(DB::table('hadith_hadiths')->whereIn('id', $hadithIds)->whereNotNull('primary_narrator_id')
                ->pluck('primary_narrator_id'))
            ->unique();
        $scholarIds = DB::table('hadith_gradings')->whereIn('hadith_id', $hadithIds)->whereNotNull('scholar_id')
            ->pluck('scholar_id')
            ->merge(DB::table('hadith_commentaries')->whereIn('hadith_id', $hadithIds)->whereNotNull('scholar_id')
                ->pluck('scholar_id'))
            ->merge(DB::table('hadith_fiqh_rulings')->whereIn('hadith_id', $hadithIds)->whereNotNull('scholar_id')
                ->pluck('scholar_id'))
            ->unique();
        $themeIds = DB::table('hadith_hadith_themes')->whereIn('hadith_id', $hadithIds)
            ->pluck('hadith_theme_id')
            ->unique();

        return [
            'hadith_collections' => 1,
            'hadith_books' => $bookIds->count(),
            'hadith_chapters' => $chapterIds->count(),
            'hadith_scholars' => $scholarIds->count(),
            'hadith_themes' => $themeIds->count(),
            'hadith_narrators' => $narratorIds->count(),
            'hadith_hadiths' => $hadithIds->count(),
            'hadith_translations' => $this->childCount('hadith_translations', $hadithIds),
            'hadith_sources' => $this->childCount('hadith_sources', $hadithIds),
            'hadith_gradings' => $this->childCount('hadith_gradings', $hadithIds),
            'hadith_isnad' => $this->childCount('hadith_isnad', $hadithIds),
            'hadith_hadith_themes' => $this->childCount('hadith_hadith_themes', $hadithIds),
            'hadith_keywords' => $this->childCount('hadith_keywords', $hadithIds),
            'hadith_commentaries' => $this->childCount('hadith_commentaries', $hadithIds),
            'hadith_related_verses' => $this->childCount('hadith_related_verses', $hadithIds),
            'hadith_applications' => $this->childCount('hadith_applications', $hadithIds),
            'hadith_fiqh_rulings' => $this->childCount('hadith_fiqh_rulings', $hadithIds),
            'hadith_sync_logs' => DB::table('hadith_sync_logs')
                ->where('source_collection_code', $collection)
                ->count(),
        ];
    }

    private function deleteCollection(string $collection): void
    {
        DB::table('hadith_sync_logs')->where('source_collection_code', $collection)->delete();

        $collectionId = DB::table('hadith_collections')->where('code', $collection)->value('id');
        if ($collectionId === null) {
            return;
        }

        $bookIds = DB::table('hadith_books')->where('collection_id', $collectionId)->pluck('id');
        $chapterIds = DB::table('hadith_chapters')->whereIn('book_id', $bookIds)->pluck('id');
        $hadithIds = DB::table('hadith_hadiths')->whereIn('chapter_id', $chapterIds)->pluck('id');
        $narratorIds = DB::table('hadith_isnad')->whereIn('hadith_id', $hadithIds)->pluck('narrator_id')
            ->merge(DB::table('hadith_hadiths')->whereIn('id', $hadithIds)->whereNotNull('primary_narrator_id')
                ->pluck('primary_narrator_id'))
            ->unique();
        $scholarIds = DB::table('hadith_gradings')->whereIn('hadith_id', $hadithIds)->whereNotNull('scholar_id')
            ->pluck('scholar_id')
            ->merge(DB::table('hadith_commentaries')->whereIn('hadith_id', $hadithIds)->whereNotNull('scholar_id')
                ->pluck('scholar_id'))
            ->merge(DB::table('hadith_fiqh_rulings')->whereIn('hadith_id', $hadithIds)->whereNotNull('scholar_id')
                ->pluck('scholar_id'))
            ->unique();
        $themeIds = DB::table('hadith_hadith_themes')->whereIn('hadith_id', $hadithIds)
            ->pluck('hadith_theme_id')
            ->unique();

        foreach (self::HADITH_CHILD_TABLES as $table) {
            DB::table($table)->whereIn('hadith_id', $hadithIds)->delete();
        }

        DB::table('hadith_hadiths')->whereIn('id', $hadithIds)->delete();
        DB::table('hadith_chapters')->whereIn('id', $chapterIds)->delete();
        DB::table('hadith_books')->whereIn('id', $bookIds)->delete();
        DB::table('hadith_collections')->where('id', $collectionId)->delete();

        $this->deleteUnusedNarrators($narratorIds);
        $this->deleteUnusedScholars($scholarIds);
        $this->deleteUnusedThemes($themeIds);
    }

    private function deleteUnusedNarrators(iterable $ids): void
    {
        DB::table('hadith_narrators')
            ->whereIn('id', $ids)
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('hadith_isnad')
                ->whereColumn('hadith_isnad.narrator_id', 'hadith_narrators.id'))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('hadith_hadiths')
                ->whereColumn('hadith_hadiths.primary_narrator_id', 'hadith_narrators.id'))
            ->delete();
    }

    private function deleteUnusedScholars(iterable $ids): void
    {
        DB::table('hadith_scholars')
            ->whereIn('id', $ids)
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('hadith_gradings')
                ->whereColumn('hadith_gradings.scholar_id', 'hadith_scholars.id'))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('hadith_commentaries')
                ->whereColumn('hadith_commentaries.scholar_id', 'hadith_scholars.id'))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('hadith_fiqh_rulings')
                ->whereColumn('hadith_fiqh_rulings.scholar_id', 'hadith_scholars.id'))
            ->delete();
    }

    private function deleteUnusedThemes(iterable $ids): void
    {
        DB::table('hadith_themes')
            ->whereIn('id', $ids)
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('hadith_hadith_themes')
                ->whereColumn('hadith_hadith_themes.hadith_theme_id', 'hadith_themes.id'))
            ->delete();
    }

    private function writeRows(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $databaseRows = array_map(fn (array $row): array => $this->databaseRow($table, $row), $rows);

        foreach (array_chunk($databaseRows, 1000) as $chunk) {
            if (in_array($table, self::SHARED_TABLES, true)) {
                DB::table($table)->upsert($chunk, ['id'], array_values(array_diff(array_keys($chunk[0]), ['id'])));
            } else {
                DB::table($table)->insert($chunk);
            }
        }
    }

    private function databaseRow(string $table, array $row): array
    {
        foreach (self::JSON_COLUMNS[$table] ?? [] as $column) {
            if (is_array($row[$column] ?? null)) {
                $row[$column] = json_encode(
                    $row[$column],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                );
            }
        }

        return $row;
    }

    private function childCount(string $table, iterable $hadithIds): int
    {
        return DB::table($table)->whereIn('hadith_id', $hadithIds)->count();
    }

    private function emptyCounts(): array
    {
        return array_fill_keys([
            'hadith_collections',
            'hadith_books',
            'hadith_chapters',
            'hadith_scholars',
            'hadith_themes',
            'hadith_narrators',
            'hadith_hadiths',
            'hadith_translations',
            'hadith_sources',
            'hadith_gradings',
            'hadith_isnad',
            'hadith_hadith_themes',
            'hadith_keywords',
            'hadith_commentaries',
            'hadith_related_verses',
            'hadith_applications',
            'hadith_fiqh_rulings',
            'hadith_sync_logs',
        ], 0);
    }
}
