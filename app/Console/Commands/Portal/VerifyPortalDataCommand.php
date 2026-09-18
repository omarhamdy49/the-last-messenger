<?php

declare(strict_types=1);

namespace App\Console\Commands\Portal;

use Illuminate\Console\Command;

/**
 * Validates a portal-data export on its own terms — reading only the JSON on
 * disk, never the database. That is the point: it is the same check the
 * production side can run before importing, and it proves the export is
 * self-contained rather than merely consistent with the machine that made it.
 *
 * Checks: manifest completeness, per-file sha256, row counts, JSON validity,
 * and every declared foreign key resolving inside the export.
 */
class VerifyPortalDataCommand extends Command
{
    protected $signature = 'portal:verify
        {--path= : export directory (default: <workspace root>/portal-data)}
        {--show=10 : how many broken references to print per relation}';

    protected $description = 'Verify a portal-data export: checksums, row counts and cross-table relations';

    /**
     * child table => [child column => parent table]. Nulls are allowed; a
     * non-null value that does not resolve is a failure.
     *
     * @var array<string, array<string, string>>
     */
    private const RELATIONS = [
        'language_translations' => ['language_id' => 'languages'],
        'quran_chapter_translations' => ['chapter_id' => 'quran_chapters'],
        'quran_verses' => ['chapter_id' => 'quran_chapters'],
        'quran_words' => ['verse_id' => 'quran_verses'],
        'quran_word_glosses' => ['word_id' => 'quran_words', 'resource_id' => 'quran_resources'],
        'quran_resource_translations' => ['resource_id' => 'quran_resources'],
        'quran_translations' => ['resource_id' => 'quran_resources', 'verse_id' => 'quran_verses'],
        'quran_tafsirs' => ['resource_id' => 'quran_resources', 'verse_id' => 'quran_verses'],
        'quran_chapter_infos' => ['resource_id' => 'quran_resources', 'chapter_id' => 'quran_chapters'],
        'quran_mushaf_pages' => ['mushaf_id' => 'quran_mushafs'],
        'quran_mushaf_words' => ['mushaf_id' => 'quran_mushafs', 'verse_id' => 'quran_verses'],
        'quran_audio_files' => ['resource_id' => 'quran_resources', 'verse_id' => 'quran_verses'],
        'quran_chapter_audio_files' => ['resource_id' => 'quran_resources', 'chapter_id' => 'quran_chapters'],
        'quran_chapter_audio_timestamps' => ['chapter_audio_file_id' => 'quran_chapter_audio_files', 'verse_id' => 'quran_verses'],
        'quran_revelation_context_translations' => ['revelation_context_id' => 'quran_revelation_contexts'],

        'sahaba' => ['father_id' => 'sahaba', 'mother_id' => 'sahaba'],
        'sahabi_translations' => ['sahabi_id' => 'sahaba'],
        'sahabi_category' => ['sahabi_id' => 'sahaba', 'category_id' => 'sahabi_categories'],
        'relationships' => ['person_id' => 'sahaba', 'related_person_id' => 'sahaba'],

        'hadith_books' => ['collection_id' => 'hadith_collections'],
        'hadith_chapters' => ['book_id' => 'hadith_books'],
        // The column this export exists to carry: a scholar attribution that
        // resolves onto the Sahaba module instead of being a loose string.
        'hadith_scholars' => ['sahaba_id' => 'sahaba'],
        'hadith_hadiths' => [
            'collection_id' => 'hadith_collections',
            'book_id' => 'hadith_books',
            'chapter_id' => 'hadith_chapters',
        ],
        'hadith_texts' => ['hadith_id' => 'hadith_hadiths'],
        'hadith_gradings' => [
            'hadith_id' => 'hadith_hadiths',
            'scholar_id' => 'hadith_scholars',
        ],
    ];

    public function handle(): int
    {
        $root = rtrim($this->option('path') ?: dirname(base_path()).'/portal-data', '/');
        $manifestPath = $root.'/manifest.json';

        if (! is_file($manifestPath)) {
            $this->error("No manifest at {$manifestPath} — run portal:export first.");

            return self::FAILURE;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest['datasets'] ?? null)) {
            $this->error('Manifest is unreadable or has no datasets.');

            return self::FAILURE;
        }

        $failures = [];
        $tableDir = [];

        $this->info('Checking files and checksums...');
        foreach ($manifest['datasets'] as $dataset => $tables) {
            foreach ($tables as $table => $meta) {
                $dir = "{$root}/{$dataset}/{$table}";
                $tableDir[$table] = $dir;
                $failures = array_merge($failures, $this->checkFiles($dir, $table, $meta));
            }
        }

        $this->info('Loading primary keys...');
        $parents = [];
        foreach (array_unique(array_merge(...array_values(array_map('array_values', self::RELATIONS)))) as $parent) {
            if (! isset($tableDir[$parent])) {
                continue;
            }
            $parents[$parent] = $this->loadIds($tableDir[$parent]);
        }

        $this->info('Checking relations...');
        $rows = [];
        foreach (self::RELATIONS as $child => $columns) {
            if (! isset($tableDir[$child])) {
                continue;
            }

            $broken = $this->checkRelations($tableDir[$child], $columns, $parents);

            foreach ($columns as $column => $parent) {
                $result = $broken[$column];
                $rows[] = [
                    "{$child}.{$column}",
                    "→ {$parent}",
                    number_format($result['checked']),
                    $result['missing'] === 0 ? 'ok' : '<fg=red>'.number_format($result['missing']).' broken</>',
                ];
                if ($result['missing'] > 0) {
                    $failures[] = "{$child}.{$column} → {$parent}: {$result['missing']} unresolved (e.g. ".implode(', ', array_slice($result['samples'], 0, (int) $this->option('show'))).')';
                }
            }
        }

        $this->table(['relation', 'target', 'non-null values', 'status'], $rows);

        if ($failures !== []) {
            $this->newLine();
            $this->error(count($failures).' problem(s):');
            foreach ($failures as $failure) {
                $this->line('  '.$failure);
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Export verified: every file matches its checksum and every declared relation resolves.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return list<string>
     */
    private function checkFiles(string $dir, string $table, array $meta): array
    {
        $failures = [];
        $counted = 0;

        foreach (($meta['files'] ?? []) as $name => $expected) {
            $path = "{$dir}/{$name}";

            if (! is_file($path)) {
                $failures[] = "{$table}: missing {$name}";

                continue;
            }

            if (hash_file('sha256', $path) !== $expected) {
                $failures[] = "{$table}: checksum mismatch on {$name}";

                continue;
            }

            $payload = json_decode((string) file_get_contents($path), true);
            if (! is_array($payload['rows'] ?? null)) {
                $failures[] = "{$table}: {$name} is not valid export JSON";

                continue;
            }

            $counted += count($payload['rows']);
        }

        if ($counted !== ($meta['rows'] ?? -1)) {
            $failures[] = "{$table}: manifest says {$meta['rows']} rows, files hold {$counted}";
        }

        return $failures;
    }

    /**
     * @return array<int, true>
     */
    private function loadIds(string $dir): array
    {
        $ids = [];

        foreach (glob($dir.'/part-*.json') ?: [] as $path) {
            $payload = json_decode((string) file_get_contents($path), true);
            foreach ($payload['rows'] ?? [] as $row) {
                if (isset($row['id'])) {
                    $ids[(int) $row['id']] = true;
                }
            }
        }

        return $ids;
    }

    /**
     * @param  array<string, string>  $columns
     * @param  array<string, array<int, true>>  $parents
     * @return array<string, array{checked:int, missing:int, samples:list<int>}>
     */
    private function checkRelations(string $dir, array $columns, array $parents): array
    {
        $result = [];
        foreach ($columns as $column => $parent) {
            $result[$column] = ['checked' => 0, 'missing' => 0, 'samples' => []];
        }

        foreach (glob($dir.'/part-*.json') ?: [] as $path) {
            $payload = json_decode((string) file_get_contents($path), true);

            foreach ($payload['rows'] ?? [] as $row) {
                foreach ($columns as $column => $parent) {
                    $value = $row[$column] ?? null;
                    if ($value === null || ! isset($parents[$parent])) {
                        continue;
                    }

                    $result[$column]['checked']++;

                    if (! isset($parents[$parent][(int) $value])) {
                        $result[$column]['missing']++;
                        if (count($result[$column]['samples']) < 20) {
                            $result[$column]['samples'][] = (int) $value;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
