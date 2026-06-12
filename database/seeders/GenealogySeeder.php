<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Genealogy\Models\Person;
use App\Domain\Genealogy\Models\PersonTranslation;
use App\Domain\Genealogy\Models\Relationship;
use App\Domain\Genealogy\Repositories\PersonRepositoryInterface;
use Database\Seeders\Data\Genealogy\AncestorsData;
use Database\Seeders\Data\Genealogy\ProphetHouseholdData;
use Database\Seeders\Data\Genealogy\RelationshipsData;
use Database\Seeders\Data\Genealogy\RelativesData;
use Database\Seeders\Data\Genealogy\WivesData;
use Illuminate\Database\Seeder;

/**
 * Genealogy Seeder
 *
 * Seeds the Prophet's ﷺ family tree: paternal ancestor chain (Adnan → Abdullah),
 * mothers and grandmothers, wives, children, grandchildren, uncles, aunts, cousins
 * and in-laws — with Arabic + English translations and the full relationship graph.
 *
 * Idempotent: people are upserted on slug, translations on (person_id, locale),
 * relationships on (person_id, related_person_id, relationship_type).
 */
class GenealogySeeder extends Seeder
{
    /** @var array<string, int> */
    private array $idsBySlug = [];

    /** @var array<string, string> */
    private array $genderBySlug = [];

    public function run(): void
    {
        $people = array_merge(
            AncestorsData::people(),
            RelativesData::people(),
            WivesData::people(),
            ProphetHouseholdData::people(),
        );

        $this->seedPeople($people);
        $this->linkParents($people);
        $this->seedParentChildRelationships($people);
        $this->seedSpouseRelationships(RelationshipsData::spouses());
        $this->seedSiblingRelationships(RelationshipsData::siblingGroups());
        $this->seedUncleAuntRelationships(RelationshipsData::unclesAndAuntsOfTheProphet());
        $this->seedCousinRelationships(RelationshipsData::cousinsOfTheProphet());
        $this->seedGrandparentRelationships(RelationshipsData::grandparentLinks());

        app(PersonRepositoryInterface::class)->clearCache();

        $this->command?->info(sprintf(
            '✅ Genealogy seeded: %d people, %d translations, %d relationships.',
            Person::count(),
            PersonTranslation::count(),
            Relationship::count(),
        ));
    }

    /**
     * Upsert people and their AR/EN translations.
     *
     * @param  array<int, array<string, mixed>>  $people
     */
    private function seedPeople(array $people): void
    {
        foreach ($people as $row) {
            $person = Person::withTrashed()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'person_type' => $row['type'],
                    'gender' => $row['gender'],
                    'generation' => $row['generation'],
                    'is_featured' => $row['featured'] ?? false,
                    'full_name' => $this->encodeLocalizedJson($row['full_name'] ?? null),
                    'kunya' => $this->encodeLocalizedJson($row['kunya'] ?? null),
                    'laqab' => $this->encodeLocalizedJson($row['laqab'] ?? null),
                    // Hijri columns are varchar in DB but date-cast on the model,
                    // so real hijri year strings cannot be stored safely. Hijri
                    // years are kept inside the biography text instead.
                    'birth_date_gregorian' => $this->yearToDate($row['birth_year'] ?? null),
                    'death_date_gregorian' => $this->yearToDate($row['death_year'] ?? null),
                ],
            );

            if ($person->trashed()) {
                $person->restore();
            }

            $this->idsBySlug[$row['slug']] = $person->id;
            $this->genderBySlug[$row['slug']] = $row['gender'];

            foreach (['ar', 'en'] as $locale) {
                PersonTranslation::updateOrCreate(
                    ['person_id' => $person->id, 'locale' => $locale],
                    array_merge($row[$locale], ['is_ready' => true]),
                );
            }
        }
    }

    /**
     * Set denormalized father_id / mother_id once all people exist.
     *
     * @param  array<int, array<string, mixed>>  $people
     */
    private function linkParents(array $people): void
    {
        foreach ($people as $row) {
            $fatherId = isset($row['father']) ? ($this->idsBySlug[$row['father']] ?? null) : null;
            $motherId = isset($row['mother']) ? ($this->idsBySlug[$row['mother']] ?? null) : null;

            if ($fatherId === null && $motherId === null) {
                continue;
            }

            Person::query()
                ->whereKey($this->idsBySlug[$row['slug']])
                ->update(['father_id' => $fatherId, 'mother_id' => $motherId]);
        }
    }

    /**
     * Derive father/mother + son/daughter relationship rows from parent links.
     *
     * @param  array<int, array<string, mixed>>  $people
     */
    private function seedParentChildRelationships(array $people): void
    {
        foreach ($people as $row) {
            $childType = $this->genderBySlug[$row['slug']] === 'male' ? 'son' : 'daughter';

            if (isset($row['father'], $this->idsBySlug[$row['father']])) {
                $this->relate($row['slug'], $row['father'], 'father');
                $this->relate($row['father'], $row['slug'], $childType);
            }

            if (isset($row['mother'], $this->idsBySlug[$row['mother']])) {
                $this->relate($row['slug'], $row['mother'], 'mother');
                $this->relate($row['mother'], $row['slug'], $childType);
            }
        }
    }

    /**
     * @param  array<int, array{husband: string, wife: string, notes?: string}>  $spouses
     */
    private function seedSpouseRelationships(array $spouses): void
    {
        foreach ($spouses as $pair) {
            $this->relate($pair['husband'], $pair['wife'], 'wife', $pair['notes'] ?? null);
            $this->relate($pair['wife'], $pair['husband'], 'husband', $pair['notes'] ?? null);
        }
    }

    /**
     * @param  array<int, array<int, string>>  $groups
     */
    private function seedSiblingRelationships(array $groups): void
    {
        foreach ($groups as $group) {
            foreach ($group as $personSlug) {
                foreach ($group as $siblingSlug) {
                    if ($personSlug === $siblingSlug) {
                        continue;
                    }

                    $type = $this->genderBySlug[$siblingSlug] === 'male' ? 'brother' : 'sister';
                    $this->relate($personSlug, $siblingSlug, $type);
                }
            }
        }
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function seedUncleAuntRelationships(array $slugs): void
    {
        foreach ($slugs as $slug) {
            $type = $this->genderBySlug[$slug] === 'male' ? 'uncle' : 'aunt';
            $this->relate(RelationshipsData::PROPHET_SLUG, $slug, $type);
            $this->relate($slug, RelationshipsData::PROPHET_SLUG, 'nephew');
        }
    }

    /**
     * @param  array<int, string>  $slugs
     */
    private function seedCousinRelationships(array $slugs): void
    {
        foreach ($slugs as $slug) {
            $this->relate(RelationshipsData::PROPHET_SLUG, $slug, 'cousin');
            $this->relate($slug, RelationshipsData::PROPHET_SLUG, 'cousin');
        }
    }

    /**
     * Direct grandparent ↔ grandchild edges (grandfather/grandmother on the
     * grandparent side, grandson/granddaughter on the grandchild side).
     *
     * @param  array<int, array{grandparent: string, grandchild: string}>  $links
     */
    private function seedGrandparentRelationships(array $links): void
    {
        foreach ($links as $link) {
            $grandparentType = $this->genderBySlug[$link['grandparent']] === 'male' ? 'grandfather' : 'grandmother';
            $grandchildType = $this->genderBySlug[$link['grandchild']] === 'male' ? 'grandson' : 'granddaughter';

            $this->relate($link['grandchild'], $link['grandparent'], $grandparentType);
            $this->relate($link['grandparent'], $link['grandchild'], $grandchildType);
        }
    }

    /**
     * Idempotent relationship insert keyed on the table's unique index.
     */
    private function relate(string $personSlug, string $relatedSlug, string $type, ?string $notes = null): void
    {
        Relationship::query()->firstOrCreate(
            [
                'person_id' => $this->idsBySlug[$personSlug],
                'related_person_id' => $this->idsBySlug[$relatedSlug],
                'relationship_type' => $type,
            ],
            ['notes' => $notes],
        );
    }

    /**
     * @param  array<string, string>|null  $localized
     */
    private function encodeLocalizedJson(?array $localized): ?string
    {
        if ($localized === null) {
            return null;
        }

        return json_encode($localized, JSON_UNESCAPED_UNICODE) ?: null;
    }

    /**
     * Conventional year (CE) → first-of-year date for the gregorian date columns.
     */
    private function yearToDate(?int $year): ?string
    {
        return $year === null ? null : sprintf('%04d-01-01', $year);
    }
}
