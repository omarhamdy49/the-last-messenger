<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Repositories\LocationRepositoryInterface;

/**
 * Maps free-text place strings onto canonical locations.
 *
 * TWO RULES, BOTH LOAD-BEARING.
 *
 * 1. THE DEEPEST PLACE WINS, alias length only breaks ties. Place names nest:
 *    "المسجد النبوي بالمدينة المنورة" contains both the mosque and the city, and
 *    the mosque is the answer (FR-010, "the most specific matching place").
 *
 *    Note this is NOT plain longest-alias-first, which the first draft used and
 *    which is wrong here: "المدينة المنورة" is 15 characters against the mosque's
 *    13, so the longer string is the LESS specific place. Hierarchy depth is the
 *    real specificity signal; string length is a proxy that inverts exactly when
 *    a landmark sits in a city with a two-word name.
 *
 * 2. AMBIGUITY IS NEVER GUESSED. When the text names two distinct places
 *    ("Makkah or Medinah", "Yemen/Makkah", "من مكة إلى الحبشة") the result is
 *    NULL with an AMBIGUOUS reason, not a coin flip. A wrong birthplace on a
 *    companion of the Prophet ﷺ is a content-accuracy defect, not a rounding
 *    error — it goes to an editor instead.
 *
 * Matching runs in PHP against a cached vocabulary of ~55 rows, not in SQL:
 * MySQL collation folding is not dependable across the Arabic diacritics present
 * in the source. See specs/003-locations-module/research.md R-04 and R-05.
 */
class LocationResolver
{
    /**
     * Alias index, most-specific first: greatest hierarchy depth, then longest
     * normalised alias.
     *
     * @var array<int, array{alias: string, normalised: string, location_id: int, depth: int}>|null
     */
    private ?array $index = null;

    public function __construct(
        private readonly LocationRepositoryInterface $locations,
    ) {}

    /**
     * Fold away everything that varies without changing which place is meant.
     *
     * Arabic: strip tashkeel and tatweel, unify the alef forms, ة→ه, ى→ي.
     * Latin: lowercase, drop a leading definite article ("al-", "as-", "at-", …).
     * Both: collapse whitespace.
     */
    public static function normalise(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // Tashkeel (U+064B–U+0652), superscript alef (U+0670) and tatweel.
        $value = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $value) ?? $value;

        $value = strtr($value, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
            'ة' => 'ه',
            'ى' => 'ي',
        ]);

        $value = mb_strtolower($value, 'UTF-8');

        // Arabic-article transliterations that vary by source: al-, as-, ash-,
        // at-, ad-, an-, ar-, az-. Only stripped from Latin text; the Arabic ال
        // is kept because it is part of the alias as written.
        $value = preg_replace('/^(al|as|ash|at|ad|an|ar|az)[-\x{2019}\']/u', '', $value) ?? $value;

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Resolve one source string.
     *
     * @param  string|null  $text  The free-text place value from a content row
     */
    public function resolve(?string $text): ResolutionOutcome
    {
        $needle = self::normalise($text);

        if ($needle === '') {
            return ResolutionOutcome::noMatch();
        }

        $hits = [];

        foreach ($this->index() as $entry) {
            if ($entry['normalised'] === '') {
                continue;
            }

            if (str_contains($needle, $entry['normalised'])) {
                $hits[] = $entry;
            }
        }

        if ($hits === []) {
            return ResolutionOutcome::noMatch();
        }

        // The index is ordered most-specific-first, so the first hit wins.
        $best = $hits[0];

        // More than one DISTINCT place matched, and the extra one is not simply
        // an ancestor of the best hit (which is the normal nesting case —
        // "المسجد النبوي بالمدينة" legitimately matches both the mosque and its
        // parent city). A genuinely different branch means the text names two
        // places, so we refuse.
        foreach ($hits as $hit) {
            if ($hit['location_id'] === $best['location_id']) {
                continue;
            }

            if ($this->isAncestorOf($hit['location_id'], $best['location_id'])) {
                continue;
            }

            // Subsumed: the other hit's alias sits INSIDE the alias we matched.
            // "العقبة بمنى" is al-Aqabah's own registered name, so the "منى"
            // inside it is not a second place being named — it is part of how
            // this place is written. Without this, every alias that embeds a
            // sibling's name reports a false ambiguity.
            if (str_contains($best['normalised'], $hit['normalised'])) {
                continue;
            }

            return ResolutionOutcome::ambiguous();
        }

        return ResolutionOutcome::matched($best['location_id'], $best['alias']);
    }

    /**
     * Drop the memoised index. Used after the vocabulary changes mid-process
     * (the seeder, and the admin CRUD).
     */
    public function forget(): void
    {
        $this->index = null;
        $this->locations->clearCache();
    }

    /**
     * @return array<int, array{alias: string, normalised: string, location_id: int, depth: int}>
     */
    private function index(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }

        $entries = [];

        /** @var Location $location */
        foreach ($this->locations->vocabulary() as $location) {
            $candidates = $location->aliasList();

            // The translated names are aliases too — an editor should not have to
            // repeat the Arabic name in the alias list for it to match.
            foreach ($location->translations as $translation) {
                if (is_string($translation->name) && trim($translation->name) !== '') {
                    $candidates[] = $translation->name;
                }
            }

            foreach ($candidates as $alias) {
                $normalised = self::normalise($alias);

                if ($normalised === '') {
                    continue;
                }

                $entries[] = [
                    'alias' => $alias,
                    'normalised' => $normalised,
                    'location_id' => (int) $location->getKey(),
                    'depth' => (int) $location->depth,
                ];
            }
        }

        // Deepest first, then longest alias. This ordering IS the specificity
        // rule — see the class docblock for why depth has to lead.
        usort(
            $entries,
            static fn (array $a, array $b): int => [$b['depth'], mb_strlen($b['normalised'])]
                <=> [$a['depth'], mb_strlen($a['normalised'])]
        );

        return $this->index = $entries;
    }

    /**
     * Is $candidateId an ancestor of $locationId in the place hierarchy?
     *
     * Reads from the already-loaded vocabulary, so this costs no query.
     */
    private function isAncestorOf(int $candidateId, int $locationId): bool
    {
        $byId = $this->locations->vocabulary()->keyBy('id');
        $currentId = $byId->get($locationId)?->parent_id;
        $hops = 0;

        while ($currentId !== null && $hops < 16) {
            if ((int) $currentId === $candidateId) {
                return true;
            }

            $currentId = $byId->get($currentId)?->parent_id;
            $hops++;
        }

        return false;
    }
}
