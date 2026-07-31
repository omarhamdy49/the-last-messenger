<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Services;

use App\Domain\Genealogy\Enums\RelationshipType;
use App\Domain\Genealogy\Models\Relationship;
use App\Domain\Genealogy\Repositories\PersonRepositoryInterface;
use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiCategory;
use App\Domain\Sahaba\Models\SahabiTranslation;
use App\Domain\Sahaba\Repositories\SahabiRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Imports scraper JSON in two passes:
 * 1. Upsert every full identity and translation.
 * 2. Resolve parentage, children and normalized relationship edges.
 *
 * This guarantees relation targets are present regardless of file order.
 */
final class SahabaJsonImportService
{
    private const UNKNOWN_TEXT_VALUES = [
        'unknown', 'n/a', 'na', 'not available', 'not known', 'not specified', 'unspecified',
        'مجهول', 'غير معروف', 'غير معلوم', 'غير محدد', 'لا يعلم',
    ];

    private const TRANSLATION_FIELDS = [
        'name',
        'full_name',
        'kunya',
        'laqab',
        'title',
        'biography',
        'birth_place',
        'death_place',
        'burial_place',
        'occupation',
        'notes',
        'virtues',
        'famous_narrations',
        'martyrdom',
    ];

    /**
     * The scraper JSON contract (docs/SAHABA_SCHEMA_AND_FETCH_OPTIONS.md) still
     * emits the legacy `classification.person_type` vocabulary — this importer
     * is the anti-corruption layer that translates it to the short internal
     * enum. Already-short values pass through unchanged.
     */
    private const PERSON_TYPE_MAP = [
        'companion_male' => 'sahabi',
        'companion_female' => 'sahabi',
        'tribe_leader' => 'tribe',
        'historical_figure' => 'historical',
        'prophet' => 'prophet',
        'family' => 'family',
        'sahabi' => 'sahabi',
        'tribe' => 'tribe',
        'historical' => 'historical',
    ];

    /**
     * Same translation for `classification.status` -> the short category key
     * stored in `sahabi_categories.key` (replaces the old single-value
     * `sahaba.status` column).
     */
    private const STATUS_TO_CATEGORY_MAP = [
        'ten_promised_paradise' => 'ten_promised',
        'badr_participant' => 'badr',
        'uhud_participant' => 'uhud',
        'early_muslim' => 'early_muslim',
        'muhajir' => 'muhajir',
        'ansar' => 'ansar',
        'tabi' => 'tabi',
        'ten_promised' => 'ten_promised',
        'badr' => 'badr',
        'uhud' => 'uhud',
    ];

    public function __construct(
        private readonly SahabiRepositoryInterface $sahabiRepository,
        private readonly PersonRepositoryInterface $personRepository,
    ) {}

    /**
     * @param  callable(string, int, int, string): void|null  $progress
     * @return array<string, mixed>
     */
    public function importDirectory(
        string $directory,
        bool $dryRun = false,
        bool $existingOnly = false,
        bool $overwriteRelations = false,
        bool $stopOnError = false,
        ?callable $progress = null,
    ): array {
        $files = $this->jsonFiles($directory);
        $existingExternalIds = $existingOnly
            ? Sahabi::query()->whereNotNull('external_id')->pluck('external_id')->flip()->all()
            : [];

        $stats = [
            'files' => count($files),
            'validated' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'placeholders_created' => 0,
            'translations_upserted' => 0,
            'parents_linked' => 0,
            'children_linked' => 0,
            'relationships_upserted' => 0,
            'relation_conflicts' => 0,
            'relation_conflict_details' => [],
            'failed' => 0,
            'errors' => [],
        ];

        if ($dryRun) {
            foreach ($files as $index => $file) {
                try {
                    $this->readRecord($file, $existingOnly);
                    $stats['validated']++;
                    if ($progress !== null) {
                        $progress('validate', $index + 1, count($files), basename($file));
                    }
                } catch (Throwable $exception) {
                    $this->recordFailure($stats, $file, $exception);
                    if ($stopOnError) {
                        throw $exception;
                    }
                }
            }

            return $stats;
        }

        $relationFiles = [];

        // Pass 1: all full identities exist before any relation is applied.
        foreach ($files as $index => $file) {
            try {
                $record = $this->readRecord($file, $existingOnly);
                $stats['validated']++;
                if ($existingOnly && ! isset($existingExternalIds[$record['external_id']])) {
                    $stats['skipped']++;
                } else {
                    DB::transaction(function () use ($record, &$stats): void {
                        $this->upsertFullIdentity($record, $stats);
                    });
                    $relationFiles[] = $file;
                }
                if ($progress !== null) {
                    $progress('identity', $index + 1, count($files), basename($file));
                }
            } catch (Throwable $exception) {
                $this->recordFailure($stats, $file, $exception);
                if ($stopOnError) {
                    throw $exception;
                }
            }
        }

        if ($overwriteRelations) {
            // Apply inferred links first so authoritative lineage can overwrite them.
            $this->processRelationPass(
                $relationFiles,
                'relations',
                function (array $record) use (&$stats): void {
                    $this->applyRelationshipEdgesAndChildren($record, true, $stats);
                },
                $stats,
                $stopOnError,
                $progress,
            );
            $this->processRelationPass(
                $relationFiles,
                'lineage',
                function (array $record) use (&$stats): void {
                    $this->applyExplicitParents($record, true, $stats);
                },
                $stats,
                $stopOnError,
                $progress,
            );
        } else {
            // Preserve existing data, but give explicit lineage priority over child hints.
            $this->processRelationPass(
                $relationFiles,
                'lineage',
                function (array $record) use (&$stats): void {
                    $this->applyExplicitParents($record, false, $stats);
                },
                $stats,
                $stopOnError,
                $progress,
            );
            $this->processRelationPass(
                $relationFiles,
                'relations',
                function (array $record) use (&$stats): void {
                    $this->applyRelationshipEdgesAndChildren($record, false, $stats);
                },
                $stats,
                $stopOnError,
                $progress,
            );
        }

        $this->sahabiRepository->clearCache();
        $this->personRepository->clearCache();
        foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
            Cache::forget("public_genealogy_index_v4_{$locale}");
        }

        return $stats;
    }

    /**
     * @return array<int, string>
     */
    private function jsonFiles(string $directory): array
    {
        if (! is_dir($directory)) {
            throw new RuntimeException("Directory not found: {$directory}");
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'*.json') ?: [];
        $files = array_values(array_filter(
            $files,
            static fn (string $file): bool => ! str_starts_with(basename($file), '_'),
        ));
        sort($files, SORT_NATURAL);

        if ($files === []) {
            throw new RuntimeException("No JSON files found in directory: {$directory}");
        }

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    private function readRecord(string $file, bool $allowScholar = false): array
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException("Unable to read {$file}");
        }

        try {
            $record = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid JSON in {$file}: {$exception->getMessage()}", 0, $exception);
        }

        if (! is_array($record)) {
            throw new RuntimeException("JSON root must be an object in {$file}");
        }

        foreach (['external_id', 'slug', 'classification', 'translations'] as $field) {
            if (! array_key_exists($field, $record) || $record[$field] === null || $record[$field] === '') {
                throw new RuntimeException("Missing required field {$field} in {$file}");
            }
        }

        if (($record['entity'] ?? null) !== 'sahabi' && ! ($allowScholar && ($record['entity'] ?? null) === 'scholar')) {
            throw new RuntimeException("Unsupported entity in {$file}");
        }

        if (empty($record['translations']['en']['name']) && empty($record['translations']['ar']['name'])) {
            throw new RuntimeException("At least one translated name is required in {$file}");
        }

        return $record;
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $stats
     */
    private function upsertFullIdentity(array $record, array &$stats): Sahabi
    {
        $externalId = (string) $record['external_id'];
        $sahabi = Sahabi::withTrashed()->where('external_id', $externalId)->first();

        if ($sahabi === null) {
            $candidate = Sahabi::withTrashed()
                ->where('slug', (string) $record['slug'])
                ->whereNull('external_id')
                ->first();
            $sahabi = $candidate ?? new Sahabi;
        }

        $created = ! $sahabi->exists;
        $classification = is_array($record['classification']) ? $record['classification'] : [];
        $life = is_array($record['life'] ?? null) ? $record['life'] : [];
        $tribal = is_array($record['tribal_affiliation'] ?? null) ? $record['tribal_affiliation'] : [];
        $history = is_array($record['islamic_history'] ?? null) ? $record['islamic_history'] : [];
        $translations = is_array($record['translations']) ? $record['translations'] : [];
        $acquisition = is_array($record['acquisition'] ?? null) ? $record['acquisition'] : [];

        $incomingPersonType = self::PERSON_TYPE_MAP[$classification['person_type'] ?? ''] ?? 'sahabi';
        $personType = ! $created && $incomingPersonType === 'historical' && ! $sahabi->is_sahabi
            ? $sahabi->person_type->value
            : $incomingPersonType;

        $sahabi->fill([
            'external_id' => $externalId,
            'source_url' => $this->nullableString($acquisition['source_url'] ?? null),
            'slug' => $this->uniqueSlug((string) $record['slug'], $sahabi->getKey()),
            'gender' => $this->enumValue($classification['gender'] ?? null, ['male', 'female'], 'male'),
            'person_type' => $personType,
            'is_sahabi' => (bool) ($classification['is_sahabi'] ?? true),
            'generation' => $this->nullableInt($classification['generation'] ?? null),
            'is_verified' => (bool) ($classification['is_verified'] ?? false),
            'birth_date_hijri' => $this->nullableString($life['birth_date_hijri'] ?? null),
            'death_date_hijri' => $this->nullableString($life['death_date_hijri'] ?? null),
            'birth_date_gregorian' => $this->validDate($life['birth_date_gregorian'] ?? null),
            'death_date_gregorian' => $this->validDate($life['death_date_gregorian'] ?? null),
            'age_at_death' => $this->nullableInt($life['age_at_death'] ?? null),
            'birth_place' => $this->nullableString($translations['en']['birth_place'] ?? null),
            'death_place' => $this->nullableString($translations['en']['death_place'] ?? null),
            'burial_place' => $this->nullableString($translations['en']['burial_place'] ?? null),
            'tribe' => $this->nullableString($tribal['tribe'] ?? null),
            'clan' => $this->nullableString($tribal['clan'] ?? null),
            'conversion_date_hijri' => $this->nullableString($history['conversion_date_hijri'] ?? null),
            'conversion_order' => $this->nullableInt($history['conversion_order'] ?? null),
            'roles' => is_array($history['roles'] ?? null) ? array_values($history['roles']) : null,
            'reported_hadith_count' => $this->nullableInt($history['hadith_count'] ?? null) ?? $sahabi->reported_hadith_count ?? 0,
        ]);
        $sahabi->save();

        if ($sahabi->trashed()) {
            $sahabi->restore();
        }

        $created ? $stats['created']++ : $stats['updated']++;
        $this->upsertTranslations($sahabi, $translations, $stats);
        $this->applyCategory($sahabi, $classification['status'] ?? null);

        return $sahabi;
    }

    /**
     * Add the category implied by the source's `classification.status`, if
     * any. Additive only (never removes an existing category on re-import) —
     * matches the upsert semantics of the rest of this pass.
     */
    private function applyCategory(Sahabi $sahabi, mixed $status): void
    {
        $categoryKey = is_string($status) ? (self::STATUS_TO_CATEGORY_MAP[$status] ?? null) : null;

        if ($categoryKey === null) {
            return;
        }

        $category = CompanionStatus::from($categoryKey);
        $categoryId = SahabiCategory::firstOrCreate(
            ['key' => $category->value],
            ['rank' => $category->rank()],
        )->id;

        $sahabi->categories()->syncWithoutDetaching([$categoryId]);
    }

    /**
     * @param  array<string, mixed>  $translations
     * @param  array<string, mixed>  $stats
     */
    private function upsertTranslations(Sahabi $sahabi, array $translations, array &$stats): void
    {
        foreach ($translations as $locale => $fields) {
            if (! is_string($locale) || ! is_array($fields)) {
                continue;
            }

            $values = [];
            foreach (self::TRANSLATION_FIELDS as $field) {
                $value = $this->nullableString($fields[$field] ?? null);
                if ($value !== null) {
                    $values[$field] = $value;
                }
            }

            if ($values === []) {
                continue;
            }

            $values['is_ready'] = ! empty($values['name']);
            SahabiTranslation::updateOrCreate(
                ['sahabi_id' => $sahabi->id, 'locale' => mb_substr($locale, 0, 5)],
                $values,
            );
            $stats['translations_upserted']++;
        }
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $stats
     */
    private function applyExplicitParents(array $record, bool $overwrite, array &$stats): void
    {
        $sahabi = Sahabi::where('external_id', (string) $record['external_id'])->firstOrFail();
        $lineage = is_array($record['lineage'] ?? null) ? $record['lineage'] : [];

        $father = $this->resolveRelatedIdentity(
            $lineage['father_ref'] ?? null,
            $lineage['father_name'] ?? null,
            'male',
            $stats,
        );
        $mother = $this->resolveRelatedIdentity(
            $lineage['mother_ref'] ?? null,
            $lineage['mother_name'] ?? null,
            'female',
            $stats,
        );

        $this->assignParent($sahabi, 'father_id', $father, $overwrite, $stats);
        $this->assignParent($sahabi, 'mother_id', $mother, $overwrite, $stats);
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $stats
     */
    private function applyRelationshipEdgesAndChildren(
        array $record,
        bool $overwrite,
        array &$stats,
    ): void {
        $sahabi = Sahabi::where('external_id', (string) $record['external_id'])->firstOrFail();

        foreach ($record['relationships'] ?? [] as $relationship) {
            if (! is_array($relationship)) {
                continue;
            }

            $type = RelationshipType::tryFrom((string) ($relationship['type'] ?? ''));
            if ($type === null) {
                continue;
            }

            $related = $this->resolveRelatedIdentity(
                $relationship['related_ref'] ?? null,
                $relationship['related_name'] ?? null,
                null,
                $stats,
            );

            if ($related === null || $related->is($sahabi)) {
                continue;
            }

            [$personId, $relatedId] = in_array(
                $type,
                [RelationshipType::SPOUSE, RelationshipType::SIBLING, RelationshipType::COUSIN],
                true,
            )
                ? [min($sahabi->id, $related->id), max($sahabi->id, $related->id)]
                : [$sahabi->id, $related->id];

            Relationship::updateOrCreate(
                [
                    'person_id' => $personId,
                    'related_person_id' => $relatedId,
                    'relationship_type' => $type->value,
                ],
                ['notes' => $this->nullableString($relationship['notes'] ?? null)],
            );
            $stats['relationships_upserted']++;
        }

        foreach ($record['genealogy_hints']['children'] ?? [] as $childData) {
            if (! is_array($childData)) {
                continue;
            }

            $child = $this->resolveRelatedIdentity(
                $childData['external_id'] ?? null,
                $childData['name'] ?? null,
                null,
                $stats,
            );
            if ($child === null || $child->is($sahabi)) {
                continue;
            }

            $column = $sahabi->gender === 'female' ? 'mother_id' : 'father_id';
            $before = $child->{$column};
            $this->assignParent($child, $column, $sahabi, $overwrite, $stats);
            if ($before !== $child->fresh()->{$column}) {
                $stats['children_linked']++;
            }
        }
    }

    /**
     * @param  array<int, string>  $files
     * @param  callable(array<string, mixed>): void  $operation
     * @param  array<string, mixed>  $stats
     * @param  callable(string, int, int, string): void|null  $progress
     */
    private function processRelationPass(
        array $files,
        string $phase,
        callable $operation,
        array &$stats,
        bool $stopOnError,
        ?callable $progress,
    ): void {
        foreach ($files as $index => $file) {
            try {
                DB::transaction(function () use ($file, $operation): void {
                    $operation($this->readRecord($file, true));
                });
                if ($progress !== null) {
                    $progress($phase, $index + 1, count($files), basename($file));
                }
            } catch (Throwable $exception) {
                $this->recordFailure($stats, $file, $exception);
                if ($stopOnError) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function resolveRelatedIdentity(
        mixed $externalId,
        mixed $name,
        ?string $gender,
        array &$stats,
    ): ?Sahabi {
        $externalId = $this->nullableString($externalId);
        $name = $this->nullableString($name);
        if ($externalId === null && $name !== null) {
            $externalId = 'derived-name:'.sha1(mb_strtolower($name));
        }

        if ($externalId !== null) {
            $existing = Sahabi::where('external_id', $externalId)->first();
            if ($existing !== null) {
                if ($existing->source_url === null && ($sourceUrl = $this->sourceUrl($externalId)) !== null) {
                    $existing->update(['source_url' => $sourceUrl]);
                }

                return $existing;
            }
        }

        if ($name !== null) {
            $matches = Sahabi::query()
                ->whereHas('translations', fn ($query) => $query->where('name', $name))
                ->limit(2)
                ->get();
            if ($matches->count() === 1) {
                $match = $matches->first();
                if ($externalId === null || $match->external_id === $externalId) {
                    return $match;
                }

                if ($match->external_id === null) {
                    $match->update([
                        'external_id' => $externalId,
                        'source_url' => $this->sourceUrl($externalId),
                    ]);

                    return $match;
                }
            }
        }

        if ($externalId === null && $name === null) {
            return null;
        }

        $slugBase = Str::slug($name ?? '');
        if ($slugBase === '') {
            $slugBase = 'related-'.Str::after((string) $externalId, ':');
        }

        $placeholder = Sahabi::create([
            'external_id' => $externalId,
            'source_url' => $this->sourceUrl($externalId),
            'slug' => $this->uniqueSlug($slugBase),
            'gender' => $gender ?? $this->inferGender($name),
            'person_type' => 'family',
            'is_sahabi' => false,
            'is_verified' => false,
        ]);

        if ($name !== null) {
            SahabiTranslation::create([
                'sahabi_id' => $placeholder->id,
                'locale' => preg_match('/\p{Arabic}/u', $name) ? 'ar' : 'en',
                'name' => $name,
                'is_ready' => true,
            ]);
            $stats['translations_upserted']++;
        }

        $stats['placeholders_created']++;

        return $placeholder;
    }

    private function sourceUrl(?string $externalId): ?string
    {
        if ($externalId === null || preg_match('/^muslimscholars:(\d+)$/', $externalId, $match) !== 1) {
            return null;
        }

        return "https://muslimscholars.info/manage.php?submit=scholar&ID={$match[1]}";
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function assignParent(
        Sahabi $child,
        string $column,
        ?Sahabi $parent,
        bool $overwrite,
        array &$stats,
    ): void {
        if ($parent === null || $parent->is($child)) {
            return;
        }

        $current = $child->{$column};
        if ($current !== null && (int) $current !== $parent->id && ! $overwrite) {
            $stats['relation_conflicts']++;
            $stats['relation_conflict_details'][] = [
                'child_id' => $child->id,
                'child_external_id' => $child->external_id,
                'parent_column' => $column,
                'existing_parent_id' => (int) $current,
                'existing_parent_external_id' => Sahabi::query()->whereKey($current)->value('external_id'),
                'proposed_parent_id' => $parent->id,
                'proposed_parent_external_id' => $parent->external_id,
            ];

            return;
        }

        if ((int) $current !== $parent->id) {
            $child->update([$column => $parent->id]);
            $stats['parents_linked']++;
        }
    }

    private function uniqueSlug(string $desired, ?int $ignoreId = null): string
    {
        $base = Str::slug($desired) ?: 'sahabi';
        $slug = $base;
        $suffix = 2;

        while (Sahabi::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function enumValue(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' || in_array(mb_strtolower($value), self::UNKNOWN_TEXT_VALUES, true)
            ? null
            : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : null;
    }

    private function validDate(mixed $value): ?string
    {
        $value = $this->nullableString($value);
        if ($value === null) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
    }

    private function inferGender(?string $name): string
    {
        if ($name !== null && preg_match('/\b(?:bint|umm)\b|\bبنت\b|\bأم\b/ui', $name)) {
            return 'female';
        }

        return 'male';
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function recordFailure(array &$stats, string $file, Throwable $exception): void
    {
        $stats['failed']++;
        $stats['errors'][] = [
            'file' => basename($file),
            'message' => $exception->getMessage(),
        ];
    }
}
