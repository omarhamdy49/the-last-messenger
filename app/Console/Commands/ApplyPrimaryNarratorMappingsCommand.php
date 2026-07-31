<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithScholar;
use Illuminate\Console\Command;
use JsonException;
use RuntimeException;

final class ApplyPrimaryNarratorMappingsCommand extends Command
{
    protected $signature = 'hadith:apply-primary-narrators
                            {path : Exact-match primary_narrator_mappings.jsonl file}
                            {--dry-run : Audit mappings without database writes}
                            {--report= : JSON report path; defaults beside the mapping file}';

    protected $description = 'Apply source-verified exact narrator mappings without overwriting conflicts';

    public function handle(): int
    {
        $path = realpath((string) $this->argument('path'));
        if ($path === false || ! is_file($path)) {
            $this->error('Mapping file not found.');

            return self::FAILURE;
        }

        try {
            $rows = $this->readRows($path);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $hadiths = Hadith::query()
            ->select(['id', 'sunnah_urn', 'primary_narrator_id'])
            ->whereIn('sunnah_urn', array_column($rows, 'sunnah_urn'))
            ->get()
            ->keyBy('sunnah_urn');
        $scholars = HadithScholar::query()
            ->select(['id', 'external_id'])
            ->whereIn('external_id', array_column($rows, 'scholar_external_id'))
            ->get()
            ->keyBy('external_id');

        $report = [
            'rows' => count($rows),
            'would_link' => 0,
            'linked' => 0,
            'already_linked' => 0,
            'missing_hadith' => [],
            'missing_scholar' => [],
            'conflicts' => [],
        ];
        $dryRun = (bool) $this->option('dry-run');

        foreach ($rows as $row) {
            $hadith = $hadiths->get($row['sunnah_urn']);
            $scholar = $scholars->get($row['scholar_external_id']);

            if ($hadith === null) {
                $report['missing_hadith'][] = $this->reviewRow($row);

                continue;
            }
            if ($scholar === null) {
                $report['missing_scholar'][] = $this->reviewRow($row);

                continue;
            }
            if ($hadith->primary_narrator_id !== null && $hadith->primary_narrator_id !== $scholar->id) {
                $report['conflicts'][] = array_merge($this->reviewRow($row), [
                    'existing_scholar_id' => $hadith->primary_narrator_id,
                    'mapped_scholar_id' => $scholar->id,
                ]);

                continue;
            }
            if ($hadith->primary_narrator_id === $scholar->id) {
                $report['already_linked']++;

                continue;
            }

            $report['would_link']++;
            if (! $dryRun) {
                $updated = Hadith::query()->whereKey($hadith->id)->whereNull('primary_narrator_id')->update([
                    'primary_narrator_id' => $scholar->id,
                ]);
                if ($updated === 1) {
                    $hadith->primary_narrator_id = $scholar->id;
                    $report['linked']++;
                } else {
                    $report['conflicts'][] = array_merge($this->reviewRow($row), [
                        'existing_scholar_id' => Hadith::query()->whereKey($hadith->id)->value('primary_narrator_id'),
                        'mapped_scholar_id' => $scholar->id,
                    ]);
                }
            }
        }

        $report['missing_hadith_count'] = count($report['missing_hadith']);
        $report['missing_scholar_count'] = count($report['missing_scholar']);
        $report['conflict_count'] = count($report['conflicts']);
        $reportPath = (string) ($this->option('report') ?: dirname($path).'/_primary_narrator_ingest_report.json');
        $written = file_put_contents(
            $reportPath,
            json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n",
        );
        if ($written === false) {
            $this->error("Unable to write report: {$reportPath}");

            return self::FAILURE;
        }

        $this->table(['Metric', 'Count'], collect($report)
            ->filter(fn ($value): bool => is_int($value))
            ->map(fn (int $value, string $key): array => [$key, $value])
            ->values()
            ->all());
        $this->line("Report: {$reportPath}");

        return $report['missing_hadith_count'] === 0
            && $report['missing_scholar_count'] === 0
            && $report['conflict_count'] === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    /** @return list<array<string, mixed>> */
    private function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read mapping file.');
        }

        $rows = [];
        $scholarByUrn = [];
        $lineNumber = 0;
        try {
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                if (trim($line) === '') {
                    continue;
                }
                try {
                    $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new RuntimeException("Invalid JSON on line {$lineNumber}", 0, $exception);
                }
                if (! is_array($row)
                    || ($row['status'] ?? null) !== 'matched'
                    || ! is_int($row['sunnah_urn'] ?? null)
                    || $row['sunnah_urn'] <= 0
                    || ! is_string($row['scholar_external_id'] ?? null)
                    || preg_match('/^muslimscholars:\d+$/', $row['scholar_external_id']) !== 1) {
                    throw new RuntimeException("Invalid exact mapping on line {$lineNumber}");
                }

                $urn = $row['sunnah_urn'];
                if (isset($scholarByUrn[$urn])) {
                    if ($scholarByUrn[$urn] !== $row['scholar_external_id']) {
                        throw new RuntimeException("Conflicting narrator mappings for Sunnah URN {$urn}");
                    }

                    continue;
                }
                $scholarByUrn[$urn] = $row['scholar_external_id'];
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /** @return array<string, mixed> */
    private function reviewRow(array $row): array
    {
        return [
            'sunnah_urn' => $row['sunnah_urn'],
            'source_url' => $row['source_url'] ?? null,
            'scholar_external_id' => $row['scholar_external_id'],
            'scholar_source_url' => $row['scholar_source_url'] ?? null,
            'source_aliases' => $row['source_aliases'] ?? [],
        ];
    }
}
