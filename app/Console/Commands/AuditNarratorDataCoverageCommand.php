<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JsonException;
use RuntimeException;
use Throwable;

final class AuditNarratorDataCoverageCommand extends Command
{
    protected $signature = 'hadith:audit-narrator-data
                            {--phase=before : Report label, normally before or after}
                            {--output= : JSON output path}
                            {--baseline= : Earlier JSON report used to calculate deltas}';

    protected $description = 'Inventory empty, null, and unknown narrator/Sahaba data without writing to the database';

    /** @var array<string, list<string>> */
    private const COLUMNS = [
        'sahaba' => [
            'external_id', 'source_url', 'slug', 'gender', 'person_type', 'is_sahabi', 'generation', 'is_verified',
            'birth_date_hijri', 'death_date_hijri', 'birth_date_gregorian', 'death_date_gregorian',
            'age_at_death', 'birth_place', 'death_place', 'burial_place', 'father_id', 'mother_id',
            'tribe', 'clan', 'conversion_date_hijri', 'conversion_order', 'roles',
            'reported_hadith_count',
        ],
        'sahabi_translations' => [
            'locale', 'name', 'full_name', 'kunya', 'laqab', 'title', 'biography', 'birth_place',
            'death_place', 'burial_place', 'occupation', 'notes', 'virtues', 'famous_narrations',
            'martyrdom', 'is_ready',
        ],
        'relationships' => ['person_id', 'related_person_id', 'relationship_type', 'notes'],
        'hadith_scholars' => [
            'external_id', 'slug', 'name', 'full_name', 'kunya', 'biography', 'birth_year_hijri',
            'death_year_hijri', 'generation', 'reliability', 'is_companion', 'sahaba_id', 'source_url',
        ],
        'hadith_hadiths' => ['sunnah_urn', 'sunnah_urn_en', 'primary_narrator_id', 'display_grade'],
        'hadith_texts' => ['locale', 'narrated_by', 'sanad', 'translator', 'source_url'],
    ];

    /** @var list<string> */
    private const UNKNOWN_VALUES = [
        'unknown', 'n/a', 'na', 'not available', 'not known', 'not specified', 'unspecified',
        'مجهول', 'غير معروف', 'غير معلوم', 'غير محدد', 'لا يعلم',
    ];

    public function handle(): int
    {
        $phase = trim((string) $this->option('phase')) ?: 'before';
        $output = (string) ($this->option('output') ?: storage_path("app/audits/narrator-data-{$phase}.json"));

        try {
            $report = [
                'schema_version' => '1.0',
                'generated_at' => now()->toIso8601String(),
                'phase' => $phase,
                'read_only' => true,
                'tables' => $this->auditTables(),
            ];

            $baselinePath = (string) $this->option('baseline');
            if ($baselinePath !== '') {
                $report['baseline'] = realpath($baselinePath) ?: $baselinePath;
                $report['changes'] = $this->compareWithBaseline($report['tables'], $baselinePath);
            }

            File::ensureDirectoryExists(dirname($output));
            $written = File::put(
                $output,
                json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n",
            );
            if ($written === false) {
                throw new RuntimeException("Unable to write report: {$output}");
            }
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Table', 'Rows', 'Audited columns', 'Missing columns'],
            collect($report['tables'])->map(
                fn (array $table, string $name): array => [
                    $name,
                    $table['rows'],
                    count($table['columns']),
                    count($table['missing_columns']),
                ],
            )->values()->all(),
        );
        $this->line("Report: {$output}");

        return self::SUCCESS;
    }

    /** @return array<string, array<string, mixed>> */
    private function auditTables(): array
    {
        $tables = [];

        foreach (self::COLUMNS as $table => $requestedColumns) {
            if (! Schema::hasTable($table)) {
                $tables[$table] = [
                    'exists' => false,
                    'rows' => 0,
                    'columns' => [],
                    'missing_columns' => $requestedColumns,
                    'missing_column_resources' => collect($requestedColumns)->mapWithKeys(
                        fn (string $column): array => [$column => $this->trustedResource($table, $column)],
                    )->all(),
                ];

                continue;
            }

            $tables[$table] = $this->auditTable($table, $requestedColumns);
        }

        return $tables;
    }

    /** @param list<string> $requestedColumns
     * @return array<string, mixed>
     */
    private function auditTable(string $table, array $requestedColumns): array
    {
        $availableColumns = Schema::getColumnListing($table);
        $columns = array_values(array_intersect($requestedColumns, $availableColumns));
        $missingColumns = array_values(array_diff($requestedColumns, $availableColumns));
        $observations = $this->columnStateCounts($table, $columns);

        return [
            'exists' => true,
            'rows' => $observations['rows'],
            'columns' => collect($observations['counts'])->map(
                fn (array $counts, string $column): array => $this->columnReport(
                    $table,
                    $column,
                    $counts,
                    $observations['rows'],
                ),
            )->all(),
            'missing_columns' => $missingColumns,
            'missing_column_resources' => collect($missingColumns)->mapWithKeys(
                fn (string $column): array => [$column => $this->trustedResource($table, $column)],
            )->all(),
        ];
    }

    /** @param list<string> $columns
     * @return array{rows: int, counts: array<string, array<string, int>>}
     */
    private function columnStateCounts(string $table, array $columns): array
    {
        if ($columns === []) {
            return ['rows' => DB::table($table)->count(), 'counts' => []];
        }

        $counts = array_fill_keys($columns, [
            'populated' => 0,
            'null' => 0,
            'empty' => 0,
            'unknown' => 0,
        ]);
        $rows = 0;

        foreach (DB::table($table)->select($columns)->cursor() as $record) {
            $rows++;
            foreach ($columns as $column) {
                $counts[$column][$this->valueState($record->{$column})]++;
            }
        }

        return ['rows' => $rows, 'counts' => $counts];
    }

    /** @param array<string, int> $counts
     * @return array<string, mixed>
     */
    private function columnReport(string $table, string $column, array $counts, int $rows): array
    {
        $missing = $counts['null'] + $counts['empty'] + $counts['unknown'];

        return [
            ...$counts,
            'missing' => $missing,
            'populated_percent' => $this->percent($counts['populated'], $rows),
            'missing_percent' => $this->percent($missing, $rows),
            'trusted_resource' => $this->trustedResource($table, $column),
        ];
    }

    private function valueState(mixed $fieldValue): string
    {
        if ($fieldValue === null) {
            return 'null';
        }
        if (is_bool($fieldValue) || is_int($fieldValue) || is_float($fieldValue)) {
            return 'populated';
        }
        if (is_array($fieldValue)) {
            return $this->collectionState($fieldValue);
        }
        if (! is_string($fieldValue)) {
            return 'populated';
        }

        $trimmed = trim($fieldValue);
        if ($trimmed === '') {
            return 'empty';
        }

        if (in_array(mb_strtolower($trimmed), self::UNKNOWN_VALUES, true)) {
            return 'unknown';
        }

        if (in_array($trimmed[0], ['[', '{'], true)) {
            try {
                $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);

                return $decoded === null ? 'null' : $this->valueState($decoded);
            } catch (JsonException) {
                // A non-JSON text value beginning with a bracket is populated.
            }
        }

        return 'populated';
    }

    /** @param array<mixed> $fieldValues */
    private function collectionState(array $fieldValues): string
    {
        if ($fieldValues === []) {
            return 'empty';
        }

        $states = array_map(fn (mixed $fieldValue): string => $this->valueState($fieldValue), $fieldValues);
        if (in_array('populated', $states, true)) {
            return 'populated';
        }
        if (in_array('unknown', $states, true)) {
            return 'unknown';
        }
        if (in_array('empty', $states, true)) {
            return 'empty';
        }

        return 'null';
    }

    /** @return array<string, string|null> */
    private function trustedResource(string $table, string $column): array
    {
        if (in_array("{$table}.{$column}", ['sahaba.slug', 'hadith_scholars.slug', 'sahabi_translations.is_ready'], true)) {
            return [
                'name' => 'Application-derived',
                'endpoint' => null,
                'identity_endpoint' => null,
                'availability' => 'derived_from_acquired_record',
            ];
        }

        if ($table === 'hadith_hadiths' && $column === 'primary_narrator_id') {
            return [
                'name' => 'Sunnah.com attribution + MuslimScholars identity',
                'endpoint' => 'https://sunnah.com/{collection}:{hadith}',
                'identity_endpoint' => 'https://muslimscholars.info/manage.php?submit=scholar&ID={id}',
                'availability' => 'exact_verified_match_only',
            ];
        }

        if ($table === 'hadith_scholars' && $column === 'sahaba_id') {
            return [
                'name' => 'MuslimScholars shared external identity',
                'endpoint' => 'https://muslimscholars.info/manage.php?submit=scholar&ID={id}',
                'identity_endpoint' => null,
                'availability' => 'same_external_id_or_exact_unique_review',
            ];
        }

        if (in_array($table, ['hadith_hadiths', 'hadith_texts'], true)) {
            return [
                'name' => 'Sunnah.com',
                'endpoint' => 'https://sunnah.com/{collection}:{hadith}',
                'identity_endpoint' => null,
                'availability' => $column === 'translator'
                    ? 'unavailable_in_selected_source'
                    : 'source_optional',
            ];
        }

        $unavailable = [
            'sahaba.birth_date_gregorian', 'sahaba.death_date_gregorian', 'sahaba.age_at_death',
            'sahaba.burial_place', 'sahaba.conversion_date_hijri', 'sahaba.conversion_order',
            'sahabi_translations.burial_place', 'sahabi_translations.notes',
            'relationships.notes',
        ];

        return [
            'name' => 'MuslimScholars.info',
            'endpoint' => 'https://muslimscholars.info/manage.php?submit=scholar&ID={id}',
            'identity_endpoint' => null,
            'availability' => in_array("{$table}.{$column}", $unavailable, true)
                ? 'unavailable_in_selected_source'
                : 'source_optional',
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $currentTables
     * @return array<string, array<string, mixed>>
     */
    private function compareWithBaseline(array $currentTables, string $baselinePath): array
    {
        $contents = File::get($baselinePath);
        $baseline = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($baseline) || ! is_array($baseline['tables'] ?? null)) {
            throw new RuntimeException('Baseline is not a narrator data audit report.');
        }

        $changes = [];
        foreach ($currentTables as $table => $current) {
            $before = $baseline['tables'][$table] ?? [];
            $changes[$table] = [
                'rows_delta' => (int) $current['rows'] - (int) ($before['rows'] ?? 0),
                'columns' => [],
            ];
            foreach ($current['columns'] as $column => $counts) {
                $prior = $before['columns'][$column] ?? [];
                $changes[$table]['columns'][$column] = [
                    'populated_delta' => $counts['populated'] - (int) ($prior['populated'] ?? 0),
                    'missing_delta' => $counts['missing'] - (int) ($prior['missing'] ?? 0),
                    'populated_percent_delta' => round(
                        $counts['populated_percent'] - (float) ($prior['populated_percent'] ?? 0),
                        2,
                    ),
                ];
            }
        }

        return $changes;
    }

    private function percent(int $count, int $total): float
    {
        return $total === 0 ? 0.0 : round($count * 100 / $total, 2);
    }
}
