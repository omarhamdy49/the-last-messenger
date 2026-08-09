<?php

declare(strict_types=1);

namespace App\Domain\People\Import;

use App\Domain\People\Enums\Generation;
use App\Domain\People\Models\IslamicRole;
use App\Domain\People\Models\Muslim;
use App\Domain\People\Models\MuslimSource;
use App\Domain\People\ValueObjects\MuslimProfile;
use App\Domain\Shared\Support\ArabicNormalizer;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

final class MuslimScholarsSahabaImportService
{
    public const SOURCE = 'muslimscholars.info';

    /** @var list<string> */
    private const HEADERS = [
        'source_id', 'source_url', 'source_status', 'action',
        'existing_muslim_id', 'existing_match_reason', 'slug', 'thumb',
        'name_ar', 'name_en', 'kunya', 'bio', 'content', 'birth_place',
        'gender', 'birth_year_hijri', 'death_year_hijri', 'generation',
        'search_name_ar', 'search_name_en', 'father_id', 'mother_id',
        'tree_display_order', 'hide_from_tree', 'is_published', 'role_slug',
        'role_is_featured', 'role_sort_order', 'source_lineage_en',
        'source_birth_raw', 'source_death_raw', 'source_interests',
        'source_tags', 'source_resources',
    ];

    public function __construct(
        private readonly MuslimScholarsSahabaIdentityMap $identityMap,
        private readonly ArabicNormalizer $normalizer,
    ) {}

    public function preview(string $path): MuslimScholarsSahabaPreview
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Import file is not readable: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Could not open import file: {$path}");
        }

        try {
            $headers = fgetcsv($handle, null, ',', '"', '');
            if ($headers !== self::HEADERS) {
                throw new RuntimeException('Import CSV headers do not match the reviewed mapping.');
            }

            $rows = [];
            $reviewRows = [];
            $sourceIds = [];
            $arabicNames = 0;
            $line = 1;

            while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $line++;
                if ($values === [null] || $values === []) {
                    continue;
                }
                if (count($values) !== count(self::HEADERS)) {
                    throw new RuntimeException("CSV line {$line} has an unexpected column count.");
                }

                /** @var array<string, string|null> $row */
                $row = array_combine(self::HEADERS, array_map(
                    static fn (mixed $value): ?string => $value === '' || $value === null ? null : (string) $value,
                    $values,
                ));
                $this->validateRow($row, $line, $sourceIds);

                $sourceIds[$row['source_id']] = true;
                if ($row['name_ar'] !== null) {
                    $arabicNames++;
                }

                if ($row['action'] === 'review_existing') {
                    $reviewRows[] = $row;
                } else {
                    $rows[] = $row;
                }
            }
        } finally {
            fclose($handle);
        }

        $datasetHash = hash_file('sha256', $path);
        if ($datasetHash === false) {
            throw new RuntimeException('Could not hash the import CSV.');
        }

        return new MuslimScholarsSahabaPreview(
            path: $path,
            datasetHash: $datasetHash,
            sourceSnapshotHash: $this->sourceSnapshotHash($path),
            rows: $rows,
            reviewRows: $reviewRows,
            arabicNames: $arabicNames,
        );
    }

    public function plan(MuslimScholarsSahabaPreview $preview): MuslimScholarsSahabaImportPlan
    {
        $allRows = $preview->allRows();

        /** @var array<string, int> $imported */
        $imported = MuslimSource::query()
            ->where('source', self::SOURCE)
            ->pluck('muslim_id', 'external_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        /** @var array<string, Muslim> $peopleBySlug */
        $peopleBySlug = Muslim::query()->get()->keyBy('slug')->all();
        $reviewedTargets = [];
        $reservedTargetIds = [];
        foreach ($allRows as $row) {
            foreach ($this->identityMap->slugsFor($row['source_id']) as $slug) {
                $target = $peopleBySlug[$slug] ?? null;
                if ($target instanceof Muslim) {
                    $reviewedTargets[$row['source_id']] = $target->id;
                    $reservedTargetIds[$target->id] = true;

                    break;
                }
            }
        }

        $eligible = Muslim::query()
            ->whereDoesntHave('sources', fn ($query) => $query->where('source', self::SOURCE))
            ->where(function ($query): void {
                $query->where('generation', Generation::Companion->value)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('slug', 'sahabi'));
            })
            ->get();

        $localArabic = [];
        $localEnglish = [];
        foreach ($eligible as $person) {
            if (isset($reservedTargetIds[$person->id])) {
                continue;
            }
            $arabic = $this->normalizer->normalize($person->name_ar);
            $english = $this->normalizer->normalize($person->name_en);
            if ($arabic !== null && $arabic !== '') {
                $localArabic[$arabic][] = $person->id;
            }
            if ($english !== null && $english !== '') {
                $localEnglish[$english][] = $person->id;
            }
        }

        $sourceArabicFrequency = [];
        $sourceEnglishFrequency = [];
        foreach ($allRows as $row) {
            $arabic = $this->normalizer->normalize($row['name_ar']);
            $english = $this->normalizer->normalize($row['name_en']);
            if ($arabic !== null && $arabic !== '') {
                $sourceArabicFrequency[$arabic] = ($sourceArabicFrequency[$arabic] ?? 0) + 1;
            }
            if ($english !== null && $english !== '') {
                $sourceEnglishFrequency[$english] = ($sourceEnglishFrequency[$english] ?? 0) + 1;
            }
        }

        $createRows = [];
        $mergeRows = [];
        $refreshRows = [];
        $claimedTargetIds = [];

        foreach ($allRows as $row) {
            $sourceId = $row['source_id'];
            if (isset($imported[$sourceId])) {
                $refreshRows[] = ['row' => $row, 'muslim_id' => $imported[$sourceId]];

                continue;
            }

            $reviewedTarget = $reviewedTargets[$sourceId] ?? null;
            if ($reviewedTarget !== null) {
                $mergeRows[] = [
                    'row' => $row,
                    'muslim_id' => $reviewedTarget,
                    'reason' => 'reviewed_source_to_slug',
                ];
                $claimedTargetIds[$reviewedTarget] = true;

                continue;
            }

            if ($this->identityMap->mustRemainDistinct($sourceId)) {
                $createRows[] = $row;

                continue;
            }

            $candidateIds = [];
            $reasons = [];
            $arabic = $this->normalizer->normalize($row['name_ar']);
            if ($arabic !== null
                && ($sourceArabicFrequency[$arabic] ?? 0) === 1
                && count($localArabic[$arabic] ?? []) === 1) {
                $candidateIds[] = $localArabic[$arabic][0];
                $reasons[] = 'unique_exact_arabic';
            }
            $english = $this->normalizer->normalize($row['name_en']);
            if ($english !== null
                && ($sourceEnglishFrequency[$english] ?? 0) === 1
                && count($localEnglish[$english] ?? []) === 1) {
                $candidateIds[] = $localEnglish[$english][0];
                $reasons[] = 'unique_exact_english';
            }

            $candidateIds = array_values(array_unique($candidateIds));
            if (count($candidateIds) === 1 && ! isset($claimedTargetIds[$candidateIds[0]])) {
                $mergeRows[] = [
                    'row' => $row,
                    'muslim_id' => $candidateIds[0],
                    'reason' => implode('+', $reasons),
                ];
                $claimedTargetIds[$candidateIds[0]] = true;
            } else {
                $createRows[] = $row;
            }
        }

        return new MuslimScholarsSahabaImportPlan($createRows, $mergeRows, $refreshRows);
    }

    /** @param null|callable(int, int): void $progress */
    public function import(
        MuslimScholarsSahabaPreview $preview,
        ?callable $progress = null,
    ): MuslimScholarsSahabaImportResult {
        $role = IslamicRole::query()->where('slug', 'sahabi')->first();
        if (! $role instanceof IslamicRole) {
            throw new RuntimeException('The sahabi role is missing. Run IslamicRoleSeeder first.');
        }

        $plan = $this->plan($preview);
        $this->assertSlugsAvailable($plan->createRows);
        $processed = 0;

        foreach (array_chunk($plan->createRows, 250) as $chunk) {
            DB::transaction(function () use ($chunk, $role, $preview, $plan, &$processed, $progress): void {
                foreach ($chunk as $row) {
                    $muslim = Muslim::query()->create($this->muslimAttributes($row));
                    $this->assignRoleAndSource($muslim, $row, $preview, $role);
                    $processed++;
                    if ($progress !== null) {
                        $progress($processed, $plan->total());
                    }
                }
            });
        }

        foreach (array_chunk($plan->mergeRows, 100) as $chunk) {
            DB::transaction(function () use ($chunk, $role, $preview, $plan, &$processed, $progress): void {
                foreach ($chunk as $item) {
                    $muslim = Muslim::query()->find($item['muslim_id']);
                    if (! $muslim instanceof Muslim) {
                        throw new RuntimeException("Merge target {$item['muslim_id']} no longer exists.");
                    }
                    $this->updateExistingMuslim($muslim, $item['row'], $preview, $role);
                    $processed++;
                    if ($progress !== null) {
                        $progress($processed, $plan->total());
                    }
                }
            });
        }

        foreach (array_chunk($plan->refreshRows, 100) as $chunk) {
            DB::transaction(function () use ($chunk, $role, $preview, $plan, &$processed, $progress): void {
                foreach ($chunk as $item) {
                    $muslim = Muslim::query()->find($item['muslim_id']);
                    if (! $muslim instanceof Muslim) {
                        throw new RuntimeException("Imported target {$item['muslim_id']} no longer exists.");
                    }
                    $this->updateExistingMuslim($muslim, $item['row'], $preview, $role);
                    $processed++;
                    if ($progress !== null) {
                        $progress($processed, $plan->total());
                    }
                }
            });
        }

        return new MuslimScholarsSahabaImportResult(
            created: count($plan->createRows),
            mergedExisting: count($plan->mergeRows),
            refreshedImported: count($plan->refreshRows),
        );
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<string, bool>  $sourceIds
     */
    private function validateRow(array $row, int $line, array $sourceIds): void
    {
        $sourceId = $row['source_id'];
        if ($sourceId === null || preg_match('/^\d+$/', $sourceId) !== 1) {
            throw new RuntimeException("CSV line {$line} has an invalid source_id.");
        }
        if (isset($sourceIds[$sourceId])) {
            throw new RuntimeException("CSV line {$line} repeats source_id {$sourceId}.");
        }
        if (! in_array($row['action'], ['insert_draft', 'review_existing'], true)) {
            throw new RuntimeException("CSV line {$line} has an unsupported action.");
        }
        if ($row['name_ar'] === null && $row['name_en'] === null) {
            throw new RuntimeException("CSV line {$line} has no name.");
        }
        if ($row['role_slug'] !== 'sahabi' || $row['generation'] !== Generation::Companion->value) {
            throw new RuntimeException("CSV line {$line} is not mapped as a Sahabi companion.");
        }
        if ($row['is_published'] !== '0' || $row['hide_from_tree'] !== '0') {
            throw new RuntimeException("CSV line {$line} is not mapped as an unpublished, visible-tree draft.");
        }
        if ($row['source_url'] === null || ! str_starts_with($row['source_url'], 'https://muslimscholars.info/')) {
            throw new RuntimeException("CSV line {$line} has an invalid source URL.");
        }
        if ($row['slug'] === null || mb_strlen($row['slug']) > 200) {
            throw new RuntimeException("CSV line {$line} has an invalid slug.");
        }
        if (($row['name_ar'] !== null && mb_strlen($row['name_ar']) > 200)
            || ($row['name_en'] !== null && mb_strlen($row['name_en']) > 200)) {
            throw new RuntimeException("CSV line {$line} has a name longer than the database limit.");
        }

        foreach (['kunya', 'bio', 'content', 'birth_place', 'source_resources'] as $field) {
            $this->decodeJson($row[$field], $field, $line);
        }
    }

    /** @param list<array<string, string|null>> $rows */
    private function assertSlugsAvailable(array $rows): void
    {
        foreach (array_chunk(array_column($rows, 'slug'), 500) as $slugs) {
            $collision = Muslim::withTrashed()->whereIn('slug', $slugs)->value('slug');
            if ($collision !== null) {
                throw new RuntimeException("A new import row conflicts with existing slug: {$collision}");
            }
        }
    }

    /** @param array<string, string|null> $row @return array<string, mixed> */
    private function muslimAttributes(array $row): array
    {
        $attributes = [
            'slug' => $row['slug'],
            'thumb' => $row['thumb'],
            'name_ar' => $row['name_ar'],
            'name_en' => $row['name_en'],
            'kunya' => $this->decodeJson($row['kunya'], 'kunya'),
            'bio' => $this->decodeJson($row['bio'], 'bio'),
            'content' => $this->decodeJson($row['content'], 'content'),
            'birth_place' => $this->decodeJson($row['birth_place'], 'birth_place'),
            'gender' => $this->inferredGender($row),
            'birth_year_hijri' => $this->integerOrNull($row['birth_year_hijri']),
            'death_year_hijri' => $this->integerOrNull($row['death_year_hijri']),
            'generation' => Generation::Companion->value,
            'profile' => $this->profileData($row),
            'father_id' => null,
            'mother_id' => null,
            'tree_display_order' => null,
            'hide_from_tree' => false,
            'is_published' => false,
        ];

        // Passing null through Spatie Translatable creates {"ar": null}.
        // Omit absent translation fields so the database stores a true NULL.
        foreach (['kunya', 'bio', 'content', 'birth_place'] as $field) {
            if ($attributes[$field] === null) {
                unset($attributes[$field]);
            }
        }

        return $attributes;
    }

    /** @param array<string, string|null> $row @return array<string, mixed> */
    private function sourceMetadata(array $row): array
    {
        return [
            'name_ar' => $row['name_ar'],
            'name_en' => $row['name_en'],
            'kunya' => $this->decodeJson($row['kunya'], 'kunya'),
            'status' => $row['source_status'],
            'lineage_en' => $row['source_lineage_en'],
            'birth_raw' => $row['source_birth_raw'],
            'death_raw' => $row['source_death_raw'],
            'interests' => $row['source_interests'],
            'tags' => $row['source_tags'] === null ? [] : explode('|', $row['source_tags']),
            'resources' => $this->decodeJson($row['source_resources'], 'source_resources') ?? [],
        ];
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function updateExistingMuslim(
        Muslim $muslim,
        array $row,
        MuslimScholarsSahabaPreview $preview,
        IslamicRole $role,
    ): void {
        $attributes = [
            'name_ar' => $this->preferLonger($muslim->name_ar, $row['name_ar']),
            'name_en' => $muslim->name_en ?? $row['name_en'],
            'kunya' => $this->mergeTranslations($muslim, 'kunya', $row['kunya']),
            'bio' => $this->mergeTranslations($muslim, 'bio', $row['bio']),
            'content' => $this->mergeTranslations($muslim, 'content', $row['content']),
            'birth_place' => $this->mergeTranslations($muslim, 'birth_place', $row['birth_place']),
            'gender' => $muslim->gender?->value ?? $this->inferredGender($row),
            'birth_year_hijri' => $muslim->birth_year_hijri ?? $this->integerOrNull($row['birth_year_hijri']),
            'death_year_hijri' => $muslim->death_year_hijri ?? $this->integerOrNull($row['death_year_hijri']),
            'generation' => Generation::Companion->value,
            'profile' => $this->mergedProfileData($muslim, $row),
        ];

        foreach (['kunya', 'bio', 'content', 'birth_place'] as $field) {
            if ($attributes[$field] === null) {
                unset($attributes[$field]);
            }
        }

        $muslim->update($attributes);
        $this->assignRoleAndSource($muslim, $row, $preview, $role);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function assignRoleAndSource(
        Muslim $muslim,
        array $row,
        MuslimScholarsSahabaPreview $preview,
        IslamicRole $role,
    ): void {
        $muslim->roles()->syncWithoutDetaching([
            $role->id => ['is_featured' => false, 'sort_order' => 0],
        ]);
        $this->attachSource($muslim, $row, $preview);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function attachSource(
        Muslim $muslim,
        array $row,
        MuslimScholarsSahabaPreview $preview,
    ): void {
        MuslimSource::query()->updateOrCreate(
            [
                'source' => self::SOURCE,
                'external_id' => $row['source_id'],
            ],
            [
                'muslim_id' => $muslim->id,
                'source_url' => $row['source_url'],
                'dataset_hash' => $preview->datasetHash,
                'source_snapshot_hash' => $preview->sourceSnapshotHash,
                'metadata' => $this->sourceMetadata($row),
            ],
        );
    }

    private function preferLonger(?string $existing, ?string $incoming): ?string
    {
        if ($incoming === null || ($existing !== null && mb_strlen($existing) >= mb_strlen($incoming))) {
            return $existing;
        }

        return $incoming;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mergeTranslations(Muslim $muslim, string $field, ?string $incoming): ?array
    {
        /** @var array<string, mixed> $existing */
        $existing = $muslim->getTranslations($field);
        $incomingTranslations = $this->decodeJson($incoming, $field) ?? [];

        foreach ($incomingTranslations as $locale => $value) {
            if (! isset($existing[$locale]) || $existing[$locale] === '') {
                $existing[$locale] = $value;
            }
        }

        return $existing === [] ? null : $existing;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function inferredGender(array $row): string
    {
        return $row['gender'] === 'female' ? 'female' : 'male';
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, string>
     */
    private function profileData(array $row): array
    {
        return array_filter([
            'full_name_ar' => $row['name_ar'],
            'full_name_en' => $row['source_lineage_en'] ?? $row['name_en'],
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, string|null>  $row
     * @return array<string, string|int|null>
     */
    private function mergedProfileData(Muslim $muslim, array $row): array
    {
        $profile = $muslim->profile instanceof MuslimProfile
            ? $muslim->profile->toArray()
            : [];

        foreach ($this->profileData($row) as $key => $value) {
            if (! isset($profile[$key]) || $profile[$key] === '') {
                $profile[$key] = $value;
            }
        }

        return $profile;
    }

    private function integerOrNull(?string $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    /** @return array<mixed>|null */
    private function decodeJson(?string $value, string $field, ?int $line = null): ?array
    {
        if ($value === null) {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $location = $line === null ? '' : " on CSV line {$line}";
            throw new RuntimeException("Invalid JSON in {$field}{$location}.", 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException("JSON field {$field} must contain an object or array.");
        }

        return $decoded;
    }

    private function sourceSnapshotHash(string $csvPath): ?string
    {
        $summaryPath = dirname($csvPath).DIRECTORY_SEPARATOR.'summary.json';
        if (! is_file($summaryPath)) {
            return null;
        }

        try {
            $summary = json_decode((string) file_get_contents($summaryPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        $hash = $summary['source']['response_sha256'] ?? null;

        return is_string($hash) && preg_match('/^[a-f0-9]{64}$/', $hash) === 1 ? $hash : null;
    }
}
