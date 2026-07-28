<?php

declare(strict_types=1);

namespace App\Console\Commands\Portal;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Dumps the verified Quran, Hadith and Sahaba datasets to plain JSON so they
 * can be loaded into a fresh production database.
 *
 * Primary keys are preserved verbatim — the whole point of the export is that
 * `hadith_isnad.narrator_id`, `hadith_companions.sahaba_id`, `quran_verse_translations.verse_id`
 * and every other foreign key still resolve on the other side. Tables are
 * listed parent-before-child so a plain sequential insert satisfies every
 * constraint without deferring checks.
 */
class ExportPortalDataCommand extends Command
{
    protected $signature = 'portal:export
        {--path= : output directory (default: <workspace root>/portal-data)}
        {--chunk=5000 : rows per part file}
        {--dataset=* : limit to these datasets (quran, hadith, sahaba, shared)}';

    protected $description = 'Export verified Quran/Hadith/Sahaba data to portal-data/ as JSON for production import';

    /**
     * Tables per dataset, in insert order: a table never appears before
     * something it references.
     *
     * @var array<string, list<string>>
     */
    private const DATASETS = [
        'shared' => [
            'languages',
            'language_translations',
        ],
        'quran' => [
            'quran_surahs',
            'quran_verses',
            'quran_words',
            'quran_surah_info',
            'quran_translation_resources',
            'quran_verse_translations',
            'quran_tafsir_resources',
            'quran_verse_tafsirs',
            'quran_reciters',
            'reciter_translations',
            'quran_recitations',
            'quran_revelation_contexts',
            'quran_revelation_context_translations',
        ],
        'sahaba' => [
            'sahaba',
            'sahabi_translations',
            'sahabi_categories',
            'sahabi_category',
            'relationships',
            'sahabi_battles',
            'sahabi_timeline_items',
            'sahabi_timeline_item_translations',
        ],
        'hadith' => [
            'hadith_collections',
            'hadith_books',
            'hadith_chapters',
            'hadith_chapter_translations',
            'hadith_scholars',
            'hadith_grading_levels',
            'hadith_themes',
            'hadith_theme_translations',
            'hadith_narrators',
            'hadith_companions',
            'hadith_hadiths',
            'hadith_translations',
            'hadith_sources',
            'hadith_source_translations',
            'hadith_gradings',
            'hadith_isnad',
            'hadith_hadith_themes',
            'hadith_keywords',
            'hadith_commentaries',
            'hadith_applications',
            'hadith_fiqh_rulings',
            'hadith_related_verses',
        ],
    ];

    /**
     * Columns holding machine-readable enum/type/code values, forced to lower
     * case on export so the production side never has to case-fold on read.
     *
     * Free-text scholarly content is deliberately NOT in this list — see
     * PRESERVED_CASE_COLUMNS.
     *
     * @var array<string, list<string>>
     */
    private const LOWERCASE_COLUMNS = [
        'languages' => ['code', 'direction'],
        'language_translations' => ['locale'],
        'quran_surahs' => ['revelation_place'],
        'quran_words' => ['char_type', 'location'],
        'quran_surah_info' => ['lang'],
        'quran_translation_resources' => ['slug', 'language', 'direction'],
        'quran_verse_translations' => ['lang'],
        'quran_tafsir_resources' => ['slug', 'language'],
        'quran_verse_tafsirs' => ['lang'],
        'quran_reciters' => ['slug', 'style', 'riwayah'],
        'reciter_translations' => ['locale'],
        'quran_revelation_contexts' => ['period', 'event_key'],
        'quran_revelation_context_translations' => ['locale'],
        'sahaba' => ['slug', 'external_id', 'gender', 'person_type'],
        'sahabi_translations' => ['locale'],
        'sahabi_categories' => ['key'],
        'relationships' => ['relationship_type'],
        'sahabi_timeline_item_translations' => ['locale'],
        'hadith_collections' => ['slug_ar', 'slug_en', 'abbreviation', 'canonical_code', 'source_slug'],
        'hadith_books' => ['slug_ar', 'slug_en'],
        'hadith_chapter_translations' => ['locale'],
        'hadith_theme_translations' => ['locale'],
        'hadith_themes' => ['review_status'],
        'hadith_narrators' => ['reliability_grade', 'school', 'region', 'source_system'],
        'hadith_hadiths' => ['verification_status', 'source_verification_method', 'grading_agreement'],
        'hadith_translations' => ['language_code'],
        'hadith_sources' => ['source_type', 'verification_method'],
        'hadith_source_translations' => ['locale'],
        'hadith_gradings' => ['review_status'],
        'hadith_isnad' => ['transmission_method'],
        'hadith_keywords' => ['review_status'],
        'hadith_commentaries' => ['language_code', 'review_status'],
        'hadith_applications' => ['review_status'],
        'hadith_fiqh_rulings' => ['language_code', 'review_status'],
        'hadith_related_verses' => ['review_status'],
    ];

    /**
     * Case-bearing content that must survive the export untouched. Recorded in
     * the manifest so the exclusion is a stated decision rather than an
     * oversight: these hold scholarly prose ("Sahih [al-Bukhari (7164)]"),
     * not enum tokens.
     *
     * @var array<string, list<string>>
     */
    private const PRESERVED_CASE_COLUMNS = [
        'hadith_hadiths' => ['grading_status'],
        'hadith_grading_levels' => ['grade_ar', 'grade_en'],
    ];

    public function handle(): int
    {
        $root = $this->resolveRoot();
        $chunk = max(500, (int) $this->option('chunk'));
        $only = (array) $this->option('dataset');

        $datasets = $only === []
            ? array_keys(self::DATASETS)
            : array_values(array_intersect(array_keys(self::DATASETS), array_map('strtolower', $only)));

        if ($datasets === []) {
            $this->error('No known dataset selected. Valid: '.implode(', ', array_keys(self::DATASETS)));

            return self::FAILURE;
        }

        $this->info("Exporting to {$root}");

        $manifest = [
            'format_version' => 1,
            'generated_at' => now()->toIso8601String(),
            'source' => [
                'database' => DB::connection()->getDatabaseName(),
                'driver' => DB::connection()->getDriverName(),
                'server_version' => DB::selectOne('SELECT VERSION() v')->v ?? null,
            ],
            'conventions' => [
                'ids_preserved' => true,
                'insert_order' => 'tables within each dataset are listed parent-first; import shared, then quran, then sahaba, then hadith',
                'lowercased_columns' => 'enum/type/code/status/method/locale/slug values are lower-cased on export',
                'preserved_case_columns' => self::PRESERVED_CASE_COLUMNS,
                'json_columns' => 'exported decoded; re-encode before insert',
                'part_files' => "each part is {table, part, parts, count, rows:[...]} with {$chunk} rows max",
            ],
            'datasets' => [],
        ];

        foreach ($datasets as $dataset) {
            $manifest['datasets'][$dataset] = [];

            foreach (self::DATASETS[$dataset] as $table) {
                try {
                    $manifest['datasets'][$dataset][$table] = $this->exportTable($root, $dataset, $table, $chunk);
                } catch (Throwable $e) {
                    $this->error("  {$table}: {$e->getMessage()}");

                    return self::FAILURE;
                }
            }
        }

        $manifest['integrity'] = $this->integrityReport($datasets);

        // A partial run (--dataset=quran) must not silently drop the datasets a
        // previous run already wrote to disk.
        if ($only !== [] && is_file($root.'/manifest.json')) {
            $previous = json_decode((string) file_get_contents($root.'/manifest.json'), true);
            if (is_array($previous['datasets'] ?? null)) {
                $manifest['datasets'] += $previous['datasets'];
            }
        }

        file_put_contents(
            $root.'/manifest.json',
            json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n",
        );

        $this->newLine();
        $this->info('Wrote manifest.json');

        return self::SUCCESS;
    }

    /**
     * @return array{rows:int, parts:int, columns:list<string>, files:array<string,string>, lowercased:list<string>}
     */
    private function exportTable(string $root, string $dataset, string $table, int $chunk): array
    {
        $dir = "{$root}/{$dataset}/{$table}";
        $this->clearDirectory($dir);

        $types = $this->columnTypes($table);
        $columns = array_keys($types);
        $lowercase = array_values(array_intersect(self::LOWERCASE_COLUMNS[$table] ?? [], $columns));
        $key = in_array('id', $columns, true) ? 'id' : null;

        $rows = DB::table($table)->count();
        $parts = max(1, (int) ceil($rows / $chunk));
        $files = [];

        $buffer = [];
        $part = 0;

        $flush = function () use (&$buffer, &$part, &$files, $dir, $table, $parts) {
            if ($buffer === []) {
                return;
            }
            $part++;
            $name = sprintf('part-%04d.json', $part);
            $this->writePart("{$dir}/{$name}", $table, $part, $parts, $buffer);
            $files[$name] = hash_file('sha256', "{$dir}/{$name}");
            $buffer = [];
        };

        $query = DB::table($table);
        $key === null ? $query->orderBy($columns[0]) : $query->orderBy($key);

        $query->chunk($chunk, function ($records) use (&$buffer, $flush, $types, $lowercase) {
            foreach ($records as $record) {
                $buffer[] = $this->castRow((array) $record, $types, $lowercase);
            }
            $flush();
        });

        // A table with zero rows still gets a part file, so the importer can
        // tell "exported and empty" from "forgot to export".
        if ($rows === 0) {
            $this->writePart("{$dir}/part-0001.json", $table, 1, 1, []);
            $files['part-0001.json'] = hash_file('sha256', "{$dir}/part-0001.json");
        }

        $this->line(sprintf('  %-42s %9s rows  %3d part(s)', $table, number_format($rows), max(1, $part)));

        return [
            'rows' => $rows,
            'parts' => max(1, $part),
            'columns' => $columns,
            'lowercased' => $lowercase,
            'files' => $files,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function writePart(string $path, string $table, int $part, int $parts, array $rows): void
    {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException("cannot write {$path}");
        }

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        fwrite($handle, '{"table":'.json_encode($table).',"part":'.$part.',"parts":'.$parts.',"count":'.count($rows).",\n\"rows\":[\n");
        $last = count($rows) - 1;
        foreach ($rows as $i => $row) {
            fwrite($handle, json_encode($row, $flags).($i === $last ? "\n" : ",\n"));
        }
        fwrite($handle, "]}\n");
        fclose($handle);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $types
     * @param  list<string>  $lowercase
     * @return array<string, mixed>
     */
    private function castRow(array $row, array $types, array $lowercase): array
    {
        foreach ($row as $column => $value) {
            if ($value === null) {
                continue;
            }

            if (str_starts_with($types[$column] ?? '', 'json') && is_string($value)) {
                $decoded = json_decode($value, true);
                $row[$column] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;

                continue;
            }

            if (is_string($value) && in_array($column, $lowercase, true)) {
                $row[$column] = mb_strtolower($value);
            }
        }

        return $row;
    }

    /**
     * @return array<string, string>
     */
    private function columnTypes(string $table): array
    {
        $types = [];
        foreach (DB::select("SHOW COLUMNS FROM `{$table}`") as $column) {
            $types[$column->Field] = strtolower($column->Type);
        }

        return $types;
    }

    /**
     * Counts of the cross-module links the export exists to carry, so a
     * production import can be checked against the source rather than assumed
     * correct.
     *
     * @param  list<string>  $datasets
     * @return array<string, mixed>
     */
    private function integrityReport(array $datasets): array
    {
        if (! in_array('hadith', $datasets, true)) {
            return [];
        }

        return [
            'hadith_companions_linked_to_sahaba' => DB::table('hadith_companions')->whereNotNull('sahaba_id')->count(),
            'hadith_narrators_linked_to_sahaba' => DB::table('hadith_narrators')->whereNotNull('sahaba_id')->count(),
            'hadiths_with_companion' => DB::table('hadith_hadiths')->whereNotNull('companion_id')->count(),
            'hadiths_reaching_sahaba_via_companion' => DB::table('hadith_hadiths as h')
                ->join('hadith_companions as c', 'c.id', '=', 'h.companion_id')
                ->whereNotNull('c.sahaba_id')->count(),
            'isnad_links' => DB::table('hadith_isnad')->count(),
            'isnad_links_reaching_sahaba' => DB::table('hadith_isnad as i')
                ->join('hadith_narrators as n', 'n.id', '=', 'i.narrator_id')
                ->whereNotNull('n.sahaba_id')->count(),
            'hadith_related_quran_verses' => DB::table('hadith_related_verses')->whereNotNull('quran_verse_id')->count(),
        ];
    }

    private function resolveRoot(): string
    {
        $path = $this->option('path')
            ?: dirname(base_path()).'/portal-data';

        if (! is_dir($path) && ! mkdir($path, 0o755, true) && ! is_dir($path)) {
            throw new \RuntimeException("cannot create {$path}");
        }

        return rtrim((string) realpath($path), '/');
    }

    private function clearDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            foreach (glob($dir.'/*.json') ?: [] as $stale) {
                unlink($stale);
            }

            return;
        }

        if (! mkdir($dir, 0o755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("cannot create {$dir}");
        }
    }
}
