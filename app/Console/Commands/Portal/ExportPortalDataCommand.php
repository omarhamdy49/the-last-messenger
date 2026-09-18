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
 * `hadith_texts.hadith_id`, `hadith_scholars.sahaba_id`, `quran_verse_translations.verse_id`
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
            'quran_chapters',
            'quran_chapter_translations',
            'quran_sections',
            'quran_verses',
            'quran_words',
            'quran_languages',
            'quran_resources',
            'quran_resource_translations',
            'quran_word_glosses',
            'quran_translations',
            'quran_tafsirs',
            'quran_chapter_infos',
            'quran_mushafs',
            'quran_mushaf_pages',
            'quran_mushaf_words',
            'quran_audio_files',
            'quran_chapter_audio_files',
            'quran_chapter_audio_timestamps',
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
            'hadith_scholars',
            'hadith_hadiths',
            'hadith_texts',
            'hadith_gradings',
            'hadith_import_runs',
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
        'quran_chapters' => ['revelation_place'],
        'quran_chapter_translations' => ['locale'],
        'quran_sections' => ['type'],
        'quran_words' => ['char_type', 'location'],
        'quran_languages' => ['iso_code', 'direction'],
        'quran_resources' => ['kind', 'slug', 'source', 'external_source', 'style', 'language_iso'],
        'quran_resource_translations' => ['locale'],
        'quran_chapter_infos' => ['language_iso'],
        'quran_mushafs' => ['mapping_mode'],
        'quran_mushaf_words' => ['char_type'],
        'sahaba' => ['slug', 'external_id', 'gender', 'person_type'],
        'sahabi_translations' => ['locale'],
        'sahabi_categories' => ['key'],
        'relationships' => ['relationship_type'],
        'sahabi_timeline_item_translations' => ['locale'],
        'hadith_collections' => ['code'],
        'hadith_scholars' => ['slug'],
        'hadith_hadiths' => ['display_grade'],
        'hadith_texts' => ['locale'],
        'hadith_gradings' => ['grade', 'scope', 'locale'],
        'hadith_import_runs' => ['collection_code', 'status'],
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
        'hadith_gradings' => ['raw_grade'],
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

        // `hadith_scholars.sahaba_id` is the only hadith→sahaba edge that
        // survives the rebuild: transmission chains and per-hadith primary
        // narrators have no verified extraction source and are not stored.
        return [
            'hadith_scholars_linked_to_sahaba' => DB::table('hadith_scholars')->whereNotNull('sahaba_id')->count(),
            'gradings_attributed_to_a_scholar' => DB::table('hadith_gradings')->whereNotNull('scholar_id')->count(),
            'gradings_reaching_sahaba_via_scholar' => DB::table('hadith_gradings as g')
                ->join('hadith_scholars as s', 's.id', '=', 'g.scholar_id')
                ->whereNotNull('s.sahaba_id')->count(),
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
