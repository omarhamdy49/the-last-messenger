<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Consolidate the duplicated genealogy identity into `sahaba`.
 *
 * `sahaba` becomes the canonical historical-person record used by the Sahaba
 * catalogue and genealogy graph. Hadith role records may point to a Sahabi,
 * but later narrators remain independent and are never misclassified.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->extendSahaba();
        $this->extendSahabiTranslations();
        $this->addHadithLinks();

        $consolidate = function (): void {
            $personMap = $this->mergePeopleIntoSahaba();
            $this->remapParentage($personMap);
            $this->remapRelationships($personMap);
            $this->linkHadithRoles();
            $this->deduplicateLinkedHadithRoles();
        };

        // MySQL implicitly commits the DDL in remapRelationships().
        if (DB::getDriverName() === 'mysql') {
            $consolidate();
        } else {
            DB::transaction($consolidate);
        }

        $this->enforceCanonicalConstraints();

        Schema::dropIfExists('person_translations');
        Schema::dropIfExists('people');
    }

    /**
     * This is a one-way consolidation. Recreating two identity stores in down()
     * would silently reintroduce divergence and cannot restore deleted duplicates.
     */
    public function down(): void {}

    private function extendSahaba(): void
    {
        if (! Schema::hasColumn('sahaba', 'person_type')) {
            Schema::table('sahaba', function (Blueprint $table): void {
                $table->string('person_type', 50)->default('companion_male')->after('status')->index();
                $table->boolean('is_sahabi')->default(true)->after('person_type')->index();
                $table->integer('generation')->nullable()->after('is_sahabi')->index();
                $table->boolean('is_verified')->default(false)->after('generation')->index();
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE sahaba MODIFY status ENUM(
                    'prophet_companion',
                    'ten_promised_paradise',
                    'badr_participant',
                    'uhud_participant',
                    'early_muslim',
                    'muhajir',
                    'ansar',
                    'tabi',
                    'genealogy_member'
                ) NOT NULL DEFAULT 'prophet_companion'"
            );
        }
    }

    private function extendSahabiTranslations(): void
    {
        if (! Schema::hasColumn('sahabi_translations', 'title')) {
            Schema::table('sahabi_translations', function (Blueprint $table): void {
                $table->string('title')->nullable()->after('laqab');
                $table->string('birth_place')->nullable()->after('biography');
                $table->string('death_place')->nullable()->after('birth_place');
                $table->string('burial_place')->nullable()->after('death_place');
                $table->string('occupation')->nullable()->after('burial_place');
                $table->text('notes')->nullable()->after('occupation');
            });
        }
    }

    /**
     * No-op since the 2026-07-30 Hadith rebuild.
     *
     * This previously bolted a `sahaba_id` FK onto `hadith_companions` and `hadith_narrators`.
     * Both tables were removed: companions collapsed into `hadith_scholars.is_companion`, and
     * narrators into `hadith_scholars`, which declares `sahaba_id` in its own create migration
     * (2026_07_30_000004). Nothing to add here any more.
     *
     * See specs/001-hadith-rebuild/data-model.md.
     */
    private function addHadithLinks(): void
    {
        // intentionally empty
    }

    /**
     * @return array<int, int> Legacy people.id => canonical sahaba.id
     */
    private function mergePeopleIntoSahaba(): array
    {
        $map = [];
        $sahabaBySlug = DB::table('sahaba')->pluck('id', 'slug')->map(fn ($id): int => (int) $id)->all();

        foreach (DB::table('people')->orderBy('id')->get() as $person) {
            $sahabiId = $person->sahaba_id !== null
                ? (int) $person->sahaba_id
                : ($sahabaBySlug[$person->slug] ?? null);

            $isSahabi = in_array($person->person_type, ['companion_male', 'companion_female'], true);

            if ($sahabiId === null || ! DB::table('sahaba')->where('id', $sahabiId)->exists()) {
                $sahabiId = (int) DB::table('sahaba')->insertGetId([
                    'slug' => $this->uniqueSahabaSlug((string) $person->slug),
                    'gender' => $person->gender,
                    'status' => $isSahabi ? 'prophet_companion' : 'genealogy_member',
                    'person_type' => $person->person_type,
                    'is_sahabi' => $isSahabi,
                    'generation' => $person->generation,
                    'is_verified' => false,
                    'birth_date_hijri' => $person->birth_date_hijri,
                    'death_date_hijri' => $person->death_date_hijri,
                    'birth_date_gregorian' => $person->birth_date_gregorian,
                    'death_date_gregorian' => $person->death_date_gregorian,
                    'profile_image' => $person->profile_image,
                    'gallery' => $person->gallery,
                    'views_count' => $person->views_count ?? 0,
                    'is_featured' => $person->is_featured ?? false,
                    'hadith_count' => 0,
                    'importance_rank' => 0,
                    'created_at' => $person->created_at ?? now(),
                    'updated_at' => now(),
                    'deleted_at' => $person->deleted_at,
                ]);
            } else {
                $existing = DB::table('sahaba')->where('id', $sahabiId)->first();

                DB::table('sahaba')->where('id', $sahabiId)->update([
                    'person_type' => $person->person_type,
                    'is_sahabi' => $isSahabi || (bool) $existing->is_sahabi,
                    'generation' => $existing->generation ?? $person->generation,
                    'birth_date_hijri' => $existing->birth_date_hijri ?? $person->birth_date_hijri,
                    'death_date_hijri' => $existing->death_date_hijri ?? $person->death_date_hijri,
                    'birth_date_gregorian' => $existing->birth_date_gregorian ?? $person->birth_date_gregorian,
                    'death_date_gregorian' => $existing->death_date_gregorian ?? $person->death_date_gregorian,
                    'profile_image' => $existing->profile_image ?? $person->profile_image,
                    'gallery' => $existing->gallery ?? $person->gallery,
                    'updated_at' => now(),
                ]);
            }

            $map[(int) $person->id] = $sahabiId;
            $sahabaBySlug[(string) $person->slug] = $sahabiId;
            $this->mergePersonTranslations($person, $sahabiId);
        }

        return $map;
    }

    private function mergePersonTranslations(object $person, int $sahabiId): void
    {
        $localizedFullName = $this->decodeLocalized($person->full_name);
        $localizedKunya = $this->decodeLocalized($person->kunya);
        $localizedLaqab = $this->decodeLocalized($person->laqab);

        foreach (DB::table('person_translations')->where('person_id', $person->id)->get() as $translation) {
            $existing = DB::table('sahabi_translations')
                ->where('sahabi_id', $sahabiId)
                ->where('locale', $translation->locale)
                ->first();

            $values = [
                'name' => $existing->name ?? $translation->name,
                'full_name' => $existing->full_name ?? ($localizedFullName[$translation->locale] ?? null),
                'kunya' => $existing->kunya ?? ($localizedKunya[$translation->locale] ?? null),
                'laqab' => $existing->laqab ?? ($localizedLaqab[$translation->locale] ?? null),
                'title' => $existing->title ?? $translation->title,
                'biography' => $existing->biography ?? $translation->biography,
                'birth_place' => $existing->birth_place ?? $translation->birth_place,
                'death_place' => $existing->death_place ?? $translation->death_place,
                'burial_place' => $existing->burial_place ?? $translation->burial_place,
                'occupation' => $existing->occupation ?? $translation->occupation,
                'notes' => $existing->notes ?? $translation->notes,
                'is_ready' => (bool) (($existing->is_ready ?? false) || $translation->is_ready),
                'updated_at' => now(),
            ];

            DB::table('sahabi_translations')->updateOrInsert(
                ['sahabi_id' => $sahabiId, 'locale' => $translation->locale],
                array_merge($values, ['created_at' => $existing->created_at ?? now()]),
            );
        }
    }

    /**
     * @param  array<int, int>  $personMap
     */
    private function remapParentage(array $personMap): void
    {
        foreach (DB::table('people')->select(['id', 'father_id', 'mother_id'])->get() as $person) {
            $sahabiId = $personMap[(int) $person->id] ?? null;
            if ($sahabiId === null) {
                continue;
            }

            DB::table('sahaba')->where('id', $sahabiId)->update([
                'father_id' => $person->father_id ? ($personMap[(int) $person->father_id] ?? null) : null,
                'mother_id' => $person->mother_id ? ($personMap[(int) $person->mother_id] ?? null) : null,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $personMap
     */
    private function remapRelationships(array $personMap): void
    {
        if ($this->foreignKeyReferences('relationships', 'person_id', 'sahaba')) {
            return;
        }

        $legacy = DB::table('relationships')->orderBy('id')->get();

        Schema::table('relationships', function (Blueprint $table): void {
            $table->dropForeign(['person_id']);
            $table->dropForeign(['related_person_id']);
        });

        DB::table('relationships')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE relationships MODIFY relationship_type
                ENUM('spouse','sibling','uncle','aunt','cousin') NOT NULL"
            );
        }

        foreach ($legacy as $relationship) {
            $personId = $personMap[(int) $relationship->person_id] ?? null;
            $relatedId = $personMap[(int) $relationship->related_person_id] ?? null;

            if ($personId === null || $relatedId === null || $personId === $relatedId) {
                continue;
            }

            $type = match ($relationship->relationship_type) {
                'husband', 'wife' => 'spouse',
                'brother', 'sister' => 'sibling',
                'cousin' => 'cousin',
                'uncle' => 'uncle',
                'aunt' => 'aunt',
                default => null,
            };

            if ($type === null) {
                continue;
            }

            if (in_array($type, ['spouse', 'sibling', 'cousin'], true) && $personId > $relatedId) {
                [$personId, $relatedId] = [$relatedId, $personId];
            }

            DB::table('relationships')->updateOrInsert(
                [
                    'person_id' => $personId,
                    'related_person_id' => $relatedId,
                    'relationship_type' => $type,
                ],
                [
                    'notes' => $relationship->notes,
                    'created_at' => $relationship->created_at ?? now(),
                    'updated_at' => now(),
                ],
            );
        }

        Schema::table('relationships', function (Blueprint $table): void {
            $table->foreign('person_id')->references('id')->on('sahaba')->cascadeOnDelete();
            $table->foreign('related_person_id')->references('id')->on('sahaba')->cascadeOnDelete();
        });
    }

    /**
     * No-op since the 2026-07-30 Hadith rebuild.
     *
     * This previously backfilled `sahaba_id` onto `hadith_companions` and
     * `hadith_narrators` by name matching. Both tables were dropped; the
     * rebuilt `hadith_scholars` is populated by the sunnah.com import, which
     * has no legacy rows to reconcile.
     */
    private function linkHadithRoles(): void
    {
        // intentionally empty
    }

    /**
     * No-op since the 2026-07-30 Hadith rebuild: there are no linked hadith
     * role rows to deduplicate. `data:dedupe` handles `hadith_scholars`.
     */
    private function deduplicateLinkedHadithRoles(): void
    {
        // intentionally empty
    }

    private function enforceCanonicalConstraints(): void
    {
        if (! $this->foreignKeyReferences('sahaba', 'father_id', 'sahaba')) {
            Schema::table('sahaba', function (Blueprint $table): void {
                $table->foreign('father_id')->references('id')->on('sahaba')->nullOnDelete();
                $table->foreign('mother_id')->references('id')->on('sahaba')->nullOnDelete();
            });
        }

        // The hadith side of this constraint now lives in the hadith_scholars
        // create migration, which declares sahaba_id UNIQUE directly.
    }

    /**
     * @return array<string, string>
     */
    private function decodeLocalized(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function uniqueSahabaSlug(string $candidate): string
    {
        $base = Str::slug($candidate) ?: 'genealogy-person';
        $slug = $base;
        $suffix = 2;

        while (DB::table('sahaba')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function normalizeName(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $name) ?? $name;
        $name = str_replace(['أ', 'إ', 'آ', 'ٱ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ا', 'ي', 'ه'], $name);
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    private function foreignKeyReferences(string $table, string $column, string $referencedTable): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->where('REFERENCED_TABLE_NAME', $referencedTable)
            ->exists();
    }
};
