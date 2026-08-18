<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Shared\Services\LocationResolver;
use App\Domain\Shared\Services\ResolutionOutcome;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resolve existing free-text place values onto canonical locations.
 *
 * WRITE RULES (all three are load-bearing):
 *
 *  1. Writes only where the foreign key IS NULL. An editor's manual assignment
 *     is never overwritten, so the command stays safe to re-run forever.
 *  2. Ambiguous or unmatched text is left NULL. A wrong birthplace on a
 *     companion of the Prophet ﷺ is a content-accuracy defect, not a rounding
 *     error — it goes to the editor queue instead of being guessed.
 *  3. Nothing is deleted, dropped or truncated, so this does not trip the
 *     destructive-command guard in constitution VIII. It still ships --dry-run.
 *
 * The report itemises every row into exactly one bucket and FAILS if the buckets
 * do not sum to the number examined — constitution X forbids a discrepancy that
 * is averaged or summarised away.
 */
class BackfillLocationsCommand extends Command
{
    protected $signature = 'locations:backfill
                            {--dry-run : Report what would change and write nothing}';

    protected $description = 'Resolve free-text place values onto canonical locations';

    private const CHUNK = 500;

    public function handle(LocationResolver $resolver): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (DB::table('locations')->where('is_active', true)->count() === 0) {
            $this->error('No active locations. Run `php artisan db:seed --class=LocationSeeder` first.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN — nothing will be written.');
        }

        $reports = [
            $this->backfillText($resolver, 'seerah_events', 'location', 'location_id', $dryRun),
            $this->backfillJsonText($resolver, 'muslims', 'birth_place', 'birth_place_id', $dryRun),
            $this->backfillSurahs($dryRun),
        ];

        $this->newLine();
        $this->renderReport($reports);

        foreach ($reports as $report) {
            if (! $this->reconciles($report)) {
                $this->error(sprintf(
                    'Report for %s does not reconcile: buckets sum to %d but %d rows were examined.',
                    $report['table'],
                    $this->bucketSum($report),
                    $report['examined']
                ));

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Plain varchar source column — Seerah events.
     *
     * @return array<string, mixed>
     */
    private function backfillText(
        LocationResolver $resolver,
        string $table,
        string $textColumn,
        string $keyColumn,
        bool $dryRun,
    ): array {
        $report = $this->emptyReport($table);

        DB::table($table)
            ->select(['id', $textColumn, $keyColumn])
            ->orderBy('id')
            ->chunkById(self::CHUNK, function ($rows) use (&$report, $resolver, $table, $textColumn, $keyColumn, $dryRun): void {
                $updates = [];

                foreach ($rows as $row) {
                    $text = $row->{$textColumn};

                    if ($text === null || trim((string) $text) === '') {
                        continue; // Names no place in text — not part of the queue.
                    }

                    $report['examined']++;

                    if ($row->{$keyColumn} !== null) {
                        $report['already_assigned']++;

                        continue;
                    }

                    $outcome = $resolver->resolve((string) $text);
                    $this->tally($report, $outcome);

                    if ($outcome->isMatched()) {
                        $updates[$row->id] = $outcome->locationId;
                    }
                }

                if (! $dryRun && $updates !== []) {
                    $this->applyUpdates($table, $keyColumn, $updates);
                }
            });

        return $report;
    }

    /**
     * JSON source column — `muslims.birth_place` is `{"en": "Makkah"}`.
     *
     * @return array<string, mixed>
     */
    private function backfillJsonText(
        LocationResolver $resolver,
        string $table,
        string $textColumn,
        string $keyColumn,
        bool $dryRun,
    ): array {
        $report = $this->emptyReport($table);

        DB::table($table)
            ->select(['id', $textColumn, $keyColumn])
            ->orderBy('id')
            ->chunkById(self::CHUNK, function ($rows) use (&$report, $resolver, $table, $keyColumn, $textColumn, $dryRun): void {
                $updates = [];

                foreach ($rows as $row) {
                    $text = $this->firstTranslatedValue($row->{$textColumn});

                    if ($text === null) {
                        continue;
                    }

                    $report['examined']++;

                    if ($row->{$keyColumn} !== null) {
                        $report['already_assigned']++;

                        continue;
                    }

                    $outcome = $resolver->resolve($text);
                    $this->tally($report, $outcome);

                    if ($outcome->isMatched()) {
                        $updates[$row->id] = $outcome->locationId;
                    }
                }

                if (! $dryRun && $updates !== []) {
                    $this->applyUpdates($table, $keyColumn, $updates);
                }
            });

        return $report;
    }

    /**
     * Surahs are deterministic, not alias-matched.
     *
     * `revelation_place` is a clean two-value enum (86 makkah, 28 madinah) and
     * stays exactly as it is — it classifies the *period* of revelation, not a
     * coordinate. This only adds the place reference beside it.
     *
     * @return array<string, mixed>
     */
    private function backfillSurahs(bool $dryRun): array
    {
        $report = $this->emptyReport('quran_surahs');

        $map = [
            'makkah' => DB::table('locations')->where('slug', 'makkah')->value('id'),
            'madinah' => DB::table('locations')->where('slug', 'al-madinah')->value('id'),
        ];

        DB::table('quran_surahs')
            ->select(['id', 'revelation_place', 'revelation_location_id'])
            ->orderBy('id')
            ->chunkById(self::CHUNK, function ($rows) use (&$report, $map, $dryRun): void {
                $updates = [];

                foreach ($rows as $row) {
                    if ($row->revelation_place === null || $row->revelation_place === '') {
                        continue;
                    }

                    $report['examined']++;

                    if ($row->revelation_location_id !== null) {
                        $report['already_assigned']++;

                        continue;
                    }

                    $locationId = $map[strtolower((string) $row->revelation_place)] ?? null;

                    if ($locationId === null) {
                        $report['no_match']++;

                        continue;
                    }

                    $report['resolved']++;
                    $updates[$row->id] = $locationId;
                }

                if (! $dryRun && $updates !== []) {
                    $this->applyUpdates('quran_surahs', 'revelation_location_id', $updates);
                }
            });

        return $report;
    }

    /**
     * One transaction per chunk. A failure rolls that chunk back and leaves the
     * rest of the run intact and re-runnable.
     *
     * Grouped by target id so a chunk of 500 rows becomes at most ~59 statements
     * (one per location) rather than 500 — constitution II.
     *
     * @param  array<int, int>  $updates  row id => location id
     */
    private function applyUpdates(string $table, string $keyColumn, array $updates): void
    {
        $byLocation = [];

        foreach ($updates as $rowId => $locationId) {
            $byLocation[$locationId][] = $rowId;
        }

        DB::transaction(function () use ($table, $keyColumn, $byLocation): void {
            foreach ($byLocation as $locationId => $rowIds) {
                DB::table($table)
                    ->whereIn('id', $rowIds)
                    ->whereNull($keyColumn) // Belt and braces: never overwrite.
                    ->update([$keyColumn => $locationId]);
            }
        });
    }

    /**
     * `{"en": "Makkah"}` → `Makkah`. Prefers Arabic when present.
     */
    private function firstTranslatedValue(mixed $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            if (! is_array($decoded)) {
                return trim($raw) === '' ? null : $raw;
            }

            $raw = $decoded;
        }

        if (! is_array($raw)) {
            return null;
        }

        foreach (['ar', 'en'] as $locale) {
            $value = $raw[$locale] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        foreach ($raw as $value) {
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function tally(array &$report, ResolutionOutcome $outcome): void
    {
        match ($outcome->reason) {
            ResolutionOutcome::MATCHED => $report['resolved']++,
            ResolutionOutcome::AMBIGUOUS => $report['ambiguous']++,
            default => $report['no_match']++,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyReport(string $table): array
    {
        return [
            'table' => $table,
            'examined' => 0,
            'resolved' => 0,
            'no_match' => 0,
            'ambiguous' => 0,
            'already_assigned' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function bucketSum(array $report): int
    {
        return $report['resolved'] + $report['no_match'] + $report['ambiguous'] + $report['already_assigned'];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function reconciles(array $report): bool
    {
        return $this->bucketSum($report) === $report['examined'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $reports
     */
    private function renderReport(array $reports): void
    {
        $this->table(
            ['table', 'examined', 'resolved', 'no-match', 'ambiguous', 'already-assigned'],
            array_map(static fn (array $r): array => [
                $r['table'],
                $r['examined'],
                $r['resolved'],
                $r['no_match'],
                $r['ambiguous'],
                $r['already_assigned'],
            ], $reports)
        );

        $unresolved = array_sum(array_map(
            static fn (array $r): int => $r['no_match'] + $r['ambiguous'],
            $reports
        ));

        if ($unresolved > 0) {
            $this->warn(sprintf(
                '%d rows were left unresolved and need an editor. They are not errors — '.
                'ambiguous and place-less text is deliberately not guessed. '.
                'Find them with the "unmapped" filter in the admin panel.',
                $unresolved
            ));
        }
    }
}
