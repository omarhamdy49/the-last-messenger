<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import;

use App\Domain\Hadith\Enums\GradeScope;
use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Services\ArabicTextNormalizer;

/**
 * Turns one free-text source grade string into a LIST of claims.
 *
 * A sample of nine book pages yielded 170 distinct strings, so this cannot be a
 * lookup table. The previous importer lowercased each distinct string into a
 * taxonomy row and produced 566 junk categories; this keeps `raw_grade`
 * verbatim, maps to the five-value enum, and reports anything it cannot read
 * rather than inventing a category (FR-007).
 *
 * Returning a list matters: "Sanad Da'if wal-Hadith Hasan" asserts two different
 * things, and a normaliser returning one verdict silently discards a claim on
 * every such record.
 */
final class GradeNormalizer
{
    /**
     * Verdict keywords, checked longest-first so "hasan sahih" is not matched by
     * the "sahih" rule. Arabic terms are matched after normalisation, which folds
     * diacritics and the Farsi yeh — the source carries صحيح, صَحِيح and صحیح for
     * one verdict.
     *
     * @var list<array{pattern: string, grade: HadithGrade}>
     */
    private const VERDICTS = [
        // Compound English verdicts first.
        ['pattern' => 'hasan sahih', 'grade' => HadithGrade::HASAN],
        ['pattern' => 'sahih hasan', 'grade' => HadithGrade::HASAN],
        ['pattern' => 'hasan gharib', 'grade' => HadithGrade::HASAN],
        ['pattern' => 'hasan li ghairihi', 'grade' => HadithGrade::HASAN],
        ['pattern' => 'daif jiddan', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'daif munkar', 'grade' => HadithGrade::DAIF],
        // "Agreed upon by al-Bukhari and Muslim" is the strongest authentication.
        ['pattern' => 'muttafaqun alayh', 'grade' => HadithGrade::SAHIH],
        ['pattern' => 'muttafaq alayh', 'grade' => HadithGrade::SAHIH],
        ['pattern' => 'mawdu', 'grade' => HadithGrade::MAWDU],
        ['pattern' => 'mawdoo', 'grade' => HadithGrade::MAWDU],
        // Surfaced by an import run's unmapped_grades: the source also spells it
        // without the w. Exactly what FR-007's reporting exists to catch.
        ['pattern' => 'maudu', 'grade' => HadithGrade::MAWDU],
        ['pattern' => 'maudoo', 'grade' => HadithGrade::MAWDU],
        ['pattern' => 'fabricated', 'grade' => HadithGrade::MAWDU],
        ['pattern' => 'matruk', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'munkar', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'shadh', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'daif', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'dhaif', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'weak', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'hasan', 'grade' => HadithGrade::HASAN],
        ['pattern' => 'sahih', 'grade' => HadithGrade::SAHIH],
        ['pattern' => 'authentic', 'grade' => HadithGrade::SAHIH],
        // Arabic, post-normalisation (ta marbuta -> ha, Farsi yeh -> ya).
        ['pattern' => 'حسن صحيح', 'grade' => HadithGrade::HASAN],
        ['pattern' => 'صحيح لغيره', 'grade' => HadithGrade::SAHIH],
        ['pattern' => 'حسن لغيره', 'grade' => HadithGrade::HASAN],
        ['pattern' => 'متفق عليه', 'grade' => HadithGrade::SAHIH],
        ['pattern' => 'ضعيف جدا', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'موضوع', 'grade' => HadithGrade::MAWDU],
        ['pattern' => 'متروك', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'منكر', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'شاذ', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'ضعيف', 'grade' => HadithGrade::DAIF],
        ['pattern' => 'حسن', 'grade' => HadithGrade::HASAN],
        ['pattern' => 'صحيح', 'grade' => HadithGrade::SAHIH],
    ];

    /**
     * Markers that scope a verdict to the chain rather than the text. `إسنادہ`
     * carries an Urdu heh, folded to ha by the normaliser.
     *
     * @var list<string>
     */
    private const ISNAD_MARKERS = [
        'isnad', 'isnaad', 'sanad', 'in chain', 'chain is', 'its chain',
        'اسناده', 'اسانيد', 'اسناد', 'سنده', 'سند',
    ];

    /** Splits a string that asserts one thing about the chain and another about the text. */
    private const SCOPE_SPLIT = '/\s*(?:wal-|wa al-|wa\'l-|,|;|\||\bو\b)\s*(?=hadith|the hadith|الحديث|المتن)/iu';

    /** Numbered multi-verdict strings: "1: Hasan<br>2: Da'if". */
    private const PART_SPLIT = '/(?:<br\s*\/?>|\n|\r\n)+/i';

    /** @var list<string> */
    private array $unmapped = [];

    public function __construct(private readonly ArabicTextNormalizer $normalizer) {}

    /**
     * @return list<array{grade: HadithGrade, scope: GradeScope, part_label: ?string}>
     */
    public function normalize(string $rawGrade, ?string $locale = null): array
    {
        $raw = trim($rawGrade);

        if ($raw === '') {
            return [];
        }

        $claims = [];

        foreach ($this->splitParts($raw) as [$label, $segment]) {
            foreach ($this->splitScopes($segment) as $piece) {
                $grade = $this->matchVerdict($piece);

                if ($grade === null) {
                    continue;
                }

                $claims[] = [
                    'grade' => $grade,
                    'scope' => $this->matchScope($piece),
                    'part_label' => $label,
                ];
            }
        }

        if ($claims === []) {
            // Recorded, never guessed into a category. Surfaced on the import run
            // so the rules can be improved without re-reading the corpus (FR-007).
            $this->unmapped[] = $raw;

            return [[
                'grade' => HadithGrade::UNKNOWN,
                'scope' => GradeScope::HADITH,
                'part_label' => null,
            ]];
        }

        return $this->deduplicate($claims);
    }

    /**
     * Every raw string this run could not interpret, in encounter order.
     *
     * @return list<string>
     */
    public function unmappedGrades(): array
    {
        return array_values(array_unique($this->unmapped));
    }

    public function forgetUnmapped(): void
    {
        $this->unmapped = [];
    }

    /**
     * @return list<array{0: ?string, 1: string}> [part label, segment]
     */
    private function splitParts(string $raw): array
    {
        $segments = preg_split(self::PART_SPLIT, $raw) ?: [$raw];
        $parts = [];
        $labelled = count($segments) > 1;

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            // "1: Hasan" -> label "1". Only when the string really had several
            // segments; a lone "1: Hasan" is one claim about the whole hadith.
            if ($labelled && preg_match('/^\s*(\d+)\s*[:.)-]\s*(.+)$/u', $segment, $m) === 1) {
                $parts[] = [$m[1], $m[2]];

                continue;
            }

            $parts[] = [$labelled ? null : null, $segment];
        }

        return $parts === [] ? [[null, $raw]] : $parts;
    }

    /**
     * @return list<string>
     */
    private function splitScopes(string $segment): array
    {
        $pieces = preg_split(self::SCOPE_SPLIT, $segment) ?: [$segment];

        return array_values(array_filter(array_map('trim', $pieces), static fn (string $p): bool => $p !== ''));
    }

    private function matchVerdict(string $piece): ?HadithGrade
    {
        $haystack = $this->comparable($piece);

        foreach (self::VERDICTS as $verdict) {
            if (str_contains($haystack, $this->comparable($verdict['pattern']))) {
                return $verdict['grade'];
            }
        }

        return null;
    }

    private function matchScope(string $piece): GradeScope
    {
        $haystack = $this->comparable($piece);

        foreach (self::ISNAD_MARKERS as $marker) {
            if (str_contains($haystack, $this->comparable($marker))) {
                return GradeScope::ISNAD;
            }
        }

        return GradeScope::HADITH;
    }

    /**
     * Folds a string to the form the patterns are written in: Arabic letters
     * normalised, Latin lowercased and stripped of the macrons, apostrophes and
     * hyphens the transliterations disagree on — "Da'if", "Daif", "Da`īf",
     * "Dha'if" are one verdict.
     */
    private function comparable(string $value): string
    {
        // Apostrophes and hyphens go FIRST: the Arabic normaliser turns them into
        // spaces, which would split "Da'if" into "da if" and stop it matching.
        $value = (string) preg_replace("/[`'’‘\-_]/u", '', $value);
        $value = $this->normalizer->normalize($value);
        $value = mb_strtolower($value, 'UTF-8');

        $value = strtr($value, [
            'ī' => 'i', 'ĩ' => 'i', 'í' => 'i', 'î' => 'i',
            'ā' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
            'ū' => 'u', 'ú' => 'u', 'û' => 'u',
            'ē' => 'e', 'ō' => 'o', 'ḥ' => 'h', 'ṣ' => 's',
            'ḍ' => 'd', 'ṭ' => 't', 'ẓ' => 'z', 'ʿ' => '', 'ʾ' => '',
        ]);

        // Attribution is not part of the verdict: "(Al-Albani)" and
        // "(Darussalam)" say who judged, not what was judged.
        $value = (string) preg_replace('/\([^)]*\)/u', ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * @param  list<array{grade: HadithGrade, scope: GradeScope, part_label: ?string}>  $claims
     * @return list<array{grade: HadithGrade, scope: GradeScope, part_label: ?string}>
     */
    private function deduplicate(array $claims): array
    {
        $unique = [];

        foreach ($claims as $claim) {
            $key = $claim['grade']->value.'|'.$claim['scope']->value.'|'.($claim['part_label'] ?? '');
            $unique[$key] ??= $claim;
        }

        return array_values($unique);
    }
}
