<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Sahaba\Services\SahabiIdentityMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

final class ImportHadithScholarsCommand extends Command
{
    protected $signature = 'hadith:import-scholars
                            {path : Directory containing MuslimScholars JSON profiles}
                            {--dry-run : Build mappings and reports without database writes}
                            {--required-mappings= : Exact mapping JSONL whose attributed scholars must be imported}
                            {--report= : JSON report path; defaults inside the input directory}';

    protected $description = 'Import narrator profiles and link verified companion identities';

    /** @var array<string, list<int>> */
    private array $scholarAliases = [];

    /** @var array<string, list<int>> */
    private array $sahabaAliases = [];

    /** @var array<string, int> */
    private array $sahabaExternalIds = [];

    /** @var array<string, bool> */
    private array $usedSlugs = [];

    public function handle(): int
    {
        $directory = realpath((string) $this->argument('path'));
        if ($directory === false || ! is_dir($directory)) {
            $this->error('Directory not found.');

            return self::FAILURE;
        }

        try {
            $files = $this->jsonFiles($directory);
            $requiredExternalIds = $this->requiredExternalIds((string) $this->option('required-mappings'));
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $scholars = HadithScholar::query()
            ->select(['id', 'external_id', 'slug', 'name', 'full_name', 'kunya'])
            ->get()
            ->keyBy('id');
        $byExternalId = $scholars->whereNotNull('external_id')->keyBy('external_id');
        $this->indexExistingScholars($scholars);
        $this->indexSahaba();

        $report = $this->emptyReport(count($files));
        $dryRun = (bool) $this->option('dry-run');
        $report['required_mapping_ids'] = count($requiredExternalIds);
        $seenRequiredExternalIds = [];

        foreach ($files as $file) {
            try {
                $record = $this->readRecord($file);
                $report['valid']++;

                $externalId = (string) $record['external_id'];
                $isRequired = isset($requiredExternalIds[$externalId]);
                if ($isRequired) {
                    $seenRequiredExternalIds[$externalId] = true;
                }

                $isNarrator = (bool) ($record['narrator_metadata']['is_narrator'] ?? false);
                if (! $isNarrator && ! $isRequired) {
                    $report['skipped_non_narrator']++;

                    continue;
                }
                if ($isRequired && ! $isNarrator) {
                    $report['included_required_attribution']++;
                }

                $report['narrators']++;
                $aliases = $this->recordAliases($record);
                $scholar = $byExternalId->get($externalId);

                if ($scholar !== null) {
                    $report['matched_scholar_external_id']++;
                } else {
                    $candidateIds = $this->candidateIds($this->scholarAliases, $aliases);
                    if (count($candidateIds) === 1) {
                        $candidate = $scholars->get($candidateIds[0]);
                        if ($candidate !== null && $candidate->external_id === null) {
                            $scholar = $candidate;
                            $report['matched_scholar_exact_alias']++;
                        } elseif ($candidate !== null) {
                            $report['scholar_identity_conflicts'][] = [
                                'external_id' => $externalId,
                                'source_url' => $record['acquisition']['source_url'] ?? null,
                                'candidate_id' => $candidate->id,
                                'candidate_external_id' => $candidate->external_id,
                            ];
                            $report['skipped_identity_conflict']++;
                            if ($isRequired) {
                                $report['blocked_required_profiles'][] = $externalId;
                            }

                            continue;
                        }
                    } elseif ($candidateIds !== []) {
                        $report['ambiguous_scholars'][] = [
                            'external_id' => $externalId,
                            'source_url' => $record['acquisition']['source_url'] ?? null,
                            'candidate_ids' => $candidateIds,
                        ];
                        $report['skipped_ambiguous_scholar']++;
                        if ($isRequired) {
                            $report['blocked_required_profiles'][] = $externalId;
                        }

                        continue;
                    }
                }

                $sahabaId = $this->resolveSahabaId($record, $aliases, $report);
                $scholar === null ? $report['would_create']++ : $report['would_update']++;

                if (! $dryRun) {
                    $scholar ??= new HadithScholar;
                    $this->fillScholar($scholar, $record, $sahabaId);
                    $scholar->save();
                    $scholars->put($scholar->id, $scholar);
                    $byExternalId->put($externalId, $scholar);
                }
            } catch (Throwable $exception) {
                $report['errors'][] = [
                    'file' => basename($file),
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $report['missing_required_profiles'] = array_values(array_diff(
            array_keys($requiredExternalIds),
            array_keys($seenRequiredExternalIds),
        ));
        sort($report['missing_required_profiles']);
        $report['missing_required_profile_count'] = count($report['missing_required_profiles']);
        $report['blocked_required_profiles'] = array_values(array_unique($report['blocked_required_profiles']));
        sort($report['blocked_required_profiles']);
        $report['blocked_required_profile_count'] = count($report['blocked_required_profiles']);
        $report['failed'] = count($report['errors']);
        $reportPath = (string) ($this->option('report') ?: $directory.'/_hadith_scholar_import_report.json');
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

        return $report['failed'] === 0
            && $report['missing_required_profile_count'] === 0
            && $report['blocked_required_profile_count'] === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    /** @return list<string> */
    private function jsonFiles(string $directory): array
    {
        $files = glob($directory.DIRECTORY_SEPARATOR.'*.json') ?: [];
        $files = array_values(array_filter(
            $files,
            fn (string $file): bool => ! str_starts_with(basename($file), '_'),
        ));
        sort($files, SORT_NATURAL);

        if ($files === []) {
            throw new RuntimeException('No profile JSON files found.');
        }

        return $files;
    }

    /** @return array<string, mixed> */
    private function readRecord(string $file): array
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException("Unable to read scholar profile {$file}");
        }

        try {
            $record = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid JSON in {$file}", 0, $exception);
        }

        if (! is_array($record)
            || ! in_array($record['entity'] ?? null, ['scholar', 'sahabi'], true)
            || ! is_string($record['external_id'] ?? null)
            || preg_match('/^muslimscholars:\d+$/', $record['external_id']) !== 1
            || ! is_string($record['acquisition']['source_url'] ?? null)
            || ! str_starts_with($record['acquisition']['source_url'], 'https://muslimscholars.info/')
            || ! is_array($record['translations'] ?? null)) {
            throw new RuntimeException("Invalid scholar profile in {$file}");
        }

        if ($this->recordAliases($record) === []) {
            throw new RuntimeException("Scholar profile has no usable name in {$file}");
        }

        return $record;
    }

    /** @return array<string, true> */
    private function requiredExternalIds(string $path): array
    {
        if ($path === '') {
            return [];
        }

        $resolved = realpath($path);
        if ($resolved === false || ! is_file($resolved)) {
            throw new RuntimeException('Required mapping file not found.');
        }

        $handle = fopen($resolved, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read required mapping file.');
        }

        $externalIds = [];
        $lineNumber = 0;
        try {
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                if (trim($line) === '') {
                    continue;
                }
                $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($row)
                    || ($row['status'] ?? null) !== 'matched'
                    || ! is_string($row['scholar_external_id'] ?? null)
                    || preg_match('/^muslimscholars:\d+$/', $row['scholar_external_id']) !== 1) {
                    throw new RuntimeException("Invalid exact mapping on line {$lineNumber}");
                }
                $externalIds[$row['scholar_external_id']] = true;
            }
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid JSON on line {$lineNumber}", 0, $exception);
        } finally {
            fclose($handle);
        }

        return $externalIds;
    }

    /** @param Collection<int, HadithScholar> $scholars */
    private function indexExistingScholars(Collection $scholars): void
    {
        foreach ($scholars as $scholar) {
            $this->usedSlugs[$scholar->slug] = true;
            foreach (['name', 'full_name', 'kunya'] as $field) {
                foreach ($scholar->getTranslations($field) as $alias) {
                    $this->addAlias($this->scholarAliases, $alias, $scholar->id);
                }
            }
        }
    }

    private function indexSahaba(): void
    {
        $sahaba = DB::table('sahaba')
            ->select(['id', 'external_id'])
            ->where('is_sahabi', true)
            ->get();

        foreach ($sahaba as $sahabi) {
            if ($sahabi->external_id !== null) {
                $this->sahabaExternalIds[(string) $sahabi->external_id] = (int) $sahabi->id;
            }
        }

        DB::table('sahabi_translations')
            ->join('sahaba', 'sahaba.id', '=', 'sahabi_translations.sahabi_id')
            ->where('sahaba.is_sahabi', true)
            ->select(['sahabi_id', 'name', 'full_name', 'kunya', 'laqab'])
            ->orderBy('sahabi_id')
            ->get()
            ->each(function (object $translation): void {
                foreach (['name', 'full_name', 'kunya', 'laqab'] as $field) {
                    $this->addAlias($this->sahabaAliases, $translation->{$field}, (int) $translation->sahabi_id);
                }
            });
    }

    /** @param array<string, list<int>> $index */
    private function addAlias(array &$index, mixed $alias, int $id): void
    {
        if (! is_string($alias) || ($normalized = SahabiIdentityMatcher::normalize($alias)) === '') {
            return;
        }

        $index[$normalized] ??= [];
        if (! in_array($id, $index[$normalized], true)) {
            $index[$normalized][] = $id;
        }
    }

    /** @return list<string> */
    private function recordAliases(array $record): array
    {
        $aliases = [];
        foreach ($record['translations'] as $translation) {
            if (! is_array($translation)) {
                continue;
            }
            foreach (['name', 'full_name', 'kunya', 'laqab'] as $field) {
                if (is_string($translation[$field] ?? null)) {
                    $aliases[] = $translation[$field];
                }
            }
        }

        return array_values(array_unique($aliases));
    }

    /** @param array<string, list<int>> $index
     * @param  list<string>  $aliases
     * @return list<int>
     */
    private function candidateIds(array $index, array $aliases): array
    {
        $ids = [];
        foreach ($aliases as $alias) {
            foreach ($index[SahabiIdentityMatcher::normalize($alias)] ?? [] as $id) {
                $ids[$id] = true;
            }
        }

        $result = array_map('intval', array_keys($ids));
        sort($result);

        return $result;
    }

    private function resolveSahabaId(array $record, array $aliases, array &$report): ?int
    {
        if (! (bool) ($record['classification']['is_sahabi'] ?? false)) {
            return null;
        }

        $externalId = (string) $record['external_id'];
        if (isset($this->sahabaExternalIds[$externalId])) {
            $report['matched_sahaba_external_id']++;

            return $this->sahabaExternalIds[$externalId];
        }

        $candidateIds = $this->candidateIds($this->sahabaAliases, $aliases);
        if (count($candidateIds) === 1) {
            $report['matched_sahaba_exact_alias']++;

            return $candidateIds[0];
        }

        $key = $candidateIds === [] ? 'unmatched_sahaba' : 'ambiguous_sahaba';
        $report[$key][] = [
            'external_id' => $externalId,
            'source_url' => $record['acquisition']['source_url'] ?? null,
            'candidate_ids' => $candidateIds,
        ];

        return null;
    }

    private function fillScholar(HadithScholar $scholar, array $record, ?int $sahabaId): void
    {
        $externalId = (string) $record['external_id'];
        $scholar->fill([
            'external_id' => $externalId,
            'slug' => $scholar->exists ? $scholar->slug : $this->uniqueSlug((string) ($record['slug'] ?? ''), $externalId),
            'name' => $this->translations($record, 'name'),
            'full_name' => $this->translations($record, 'full_name') ?: null,
            'kunya' => $this->translations($record, 'kunya') ?: null,
            'biography' => $this->translations($record, 'biography') ?: null,
            'birth_year_hijri' => $this->hijriYear($record['life']['birth_date_hijri'] ?? null),
            'death_year_hijri' => $this->hijriYear($record['life']['death_date_hijri'] ?? null),
            'generation' => mb_substr((string) ($record['narrator_metadata']['generation_label'] ?? ''), 0, 50) ?: null,
            'reliability' => mb_substr((string) ($record['narrator_metadata']['reliability'] ?? ''), 0, 100) ?: null,
            'is_companion' => (bool) ($record['classification']['is_sahabi'] ?? false),
            'sahaba_id' => $sahabaId ?? $scholar->sahaba_id,
            'source_url' => $record['acquisition']['source_url'] ?? null,
        ]);
    }

    /** @return array<string, string> */
    private function translations(array $record, string $field): array
    {
        $values = [];
        foreach ($record['translations'] as $locale => $translation) {
            $value = is_array($translation) ? ($translation[$field] ?? null) : null;
            if (is_string($value) && trim($value) !== '') {
                $values[(string) $locale] = trim($value);
            }
        }

        return $values;
    }

    private function hijriYear(mixed $value): ?int
    {
        if (! is_string($value) || preg_match('/(\d+)\s*(AH|BH)/i', $value, $match) !== 1) {
            return null;
        }

        return strtoupper($match[2]) === 'BH' ? -(int) $match[1] : (int) $match[1];
    }

    private function uniqueSlug(string $desired, string $externalId): string
    {
        $base = mb_substr(Str::slug($desired) ?: 'scholar-'.Str::after($externalId, ':'), 0, 240);
        $slug = $base;
        $suffix = 2;
        while (isset($this->usedSlugs[$slug])) {
            $slug = $base.'-'.$suffix++;
        }
        $this->usedSlugs[$slug] = true;

        return $slug;
    }

    /** @return array<string, mixed> */
    private function emptyReport(int $files): array
    {
        return [
            'files' => $files,
            'valid' => 0,
            'narrators' => 0,
            'skipped_non_narrator' => 0,
            'required_mapping_ids' => 0,
            'included_required_attribution' => 0,
            'would_create' => 0,
            'would_update' => 0,
            'matched_scholar_external_id' => 0,
            'matched_scholar_exact_alias' => 0,
            'matched_sahaba_external_id' => 0,
            'matched_sahaba_exact_alias' => 0,
            'skipped_identity_conflict' => 0,
            'skipped_ambiguous_scholar' => 0,
            'missing_required_profile_count' => 0,
            'blocked_required_profile_count' => 0,
            'failed' => 0,
            'ambiguous_scholars' => [],
            'scholar_identity_conflicts' => [],
            'ambiguous_sahaba' => [],
            'unmatched_sahaba' => [],
            'missing_required_profiles' => [],
            'blocked_required_profiles' => [],
            'errors' => [],
        ];
    }
}
