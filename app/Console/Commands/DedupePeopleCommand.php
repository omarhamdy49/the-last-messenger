<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Normalizer;

/**
 * Merges duplicate person records in two places:
 *
 *  - `sahaba` — the scraped source produced the same person several times,
 *    visible in the slug suffixes the importer added (`abu-aziz-ibn-umair-2`).
 *  - `hadith_companions` — the same narrator appears once per transliteration
 *    used across collections ("Abu Hurayrah" / "Abu Huraira" / "Abu Hurairah").
 *
 * Sahaba are merged first so companion merges inherit already-canonical ids.
 *
 * Identity is decided on the ENGLISH name, which is populated and consistently
 * transliterated across the dataset. The Arabic name is used only as a veto,
 * never as evidence: some rows carry a corrupted Arabic name shared with a
 * sibling, so agreeing on Arabic alone would merge two different people.
 *
 * Dry-run by default; pass --apply to write.
 */
class DedupePeopleCommand extends Command
{
    protected $signature = 'data:dedupe
        {--apply : perform the merges (default is a dry run)}
        {--only= : limit to "sahaba" or "narrations"}
        {--drop-nameless : also detach and delete companion rows that carry no name at all}
        {--show=12 : how many merge groups to print}';

    protected $description = 'Merge duplicate sahaba and hadith companion records, repointing every reference';

    /** Placeholders standing in for "narrator not identified" — never merge these into a person. */
    private const NON_PERSON = [
        'companion', 'companions', 'various companions', 'unknown', 'not specified', 'someone',
        'صحابي', 'صحابية', 'الصحابة', 'غير معروف', 'غير محدد',
    ];

    /** Identifying words a name without a patronymic needs before it may absorb lineage variants. */
    private const MIN_LINEAGE_TOKENS = 3;

    /** Where each narration-source table's rows are actually used. */
    private const NARRATION_USAGE = [
        'hadith_companions' => ['hadith_hadiths', 'companion_id'],
        'hadith_narrators' => ['hadith_isnad', 'narrator_id'],
    ];

    /** Every column that points at `sahaba.id`. */
    private const SAHABA_REFERENCES = [
        ['sahabi_translations', 'sahabi_id'],
        ['sahabi_category', 'sahabi_id'],
        ['sahabi_battles', 'sahabi_id'],
        ['sahabi_timeline_items', 'sahabi_id'],
        ['relationships', 'person_id'],
        ['relationships', 'related_person_id'],
        ['sahaba', 'father_id'],
        ['sahaba', 'mother_id'],
        ['hadith_companions', 'sahaba_id'],
        ['hadith_narrators', 'sahaba_id'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $only = $this->option('only');

        if ($only === null || $only === 'sahaba') {
            $this->mergeSahaba($apply);
        }

        if ($only === null || $only === 'narrations') {
            $this->mergeNarrationSources('hadith_companions', $apply);
            $this->mergeNarrationSources('hadith_narrators', $apply);
            $this->handleNamelessCompanions($apply);
        }

        if (! $apply) {
            $this->newLine();
            $this->warn('Dry run — nothing written. Re-run with --apply to persist.');
        }

        return self::SUCCESS;
    }

    // ---------------------------------------------------------------- sahaba

    private function mergeSahaba(bool $apply): void
    {
        $names = $this->sahabaNames();
        $people = DB::table('sahaba')
            ->select('id', 'slug', 'gender', 'father_id', 'death_date_hijri')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $groups = [];
        foreach ($people as $person) {
            $key = $this->foldEnglish($names[$person->id]['en'] ?? '');
            if ($key !== '' && substr_count($key, ' ') >= 1) {
                $groups[$key][] = (int) $person->id;
            }
        }

        $this->joinLineageVariants($groups);

        $merges = [];
        $vetoed = ['gender' => 0, 'arabic' => 0, 'father' => 0, 'death' => 0, 'numbered' => 0];

        foreach (array_filter($groups, fn ($g) => count($g) > 1) as $ids) {
            sort($ids);
            $anchor = $ids[0];
            $group = [$anchor];

            foreach (array_slice($ids, 1) as $id) {
                $veto = $this->vetoSahabaMerge($people[$anchor], $people[$id], $names[$anchor] ?? [], $names[$id] ?? []);
                if ($veto !== null) {
                    $vetoed[$veto]++;

                    continue;
                }
                $group[] = $id;
            }

            if (count($group) > 1) {
                $merges[] = $group;
            }
        }

        $rows = array_sum(array_map('count', $merges));
        $this->info(sprintf(
            'sahaba: %d duplicate groups, %d rows → %d canonical (%d removed).',
            count($merges), $rows, count($merges), $rows - count($merges),
        ));
        $this->line('  vetoed pairs: '.json_encode($vetoed));

        $this->printGroups($merges, fn (int $id) => sprintf(
            '#%-6s %-34s %s', $id, mb_substr((string) $people[$id]->slug, 0, 32), mb_substr($names[$id]['en'] ?? '—', 0, 34),
        ));

        if (! $apply || $merges === []) {
            return;
        }

        DB::transaction(function () use ($merges) {
            foreach ($merges as $group) {
                $canonical = $this->pickCanonicalSahabi($group);
                foreach ($group as $id) {
                    if ($id !== $canonical) {
                        $this->absorbSahabi($canonical, $id);
                    }
                }
            }
        });

        $this->info('  merged.');
    }

    /**
     * The same person is often recorded twice with different amounts of
     * lineage — "al-Bara' ibn 'Azib" and "al-Bara' bin Azib bin al-Harith".
     * Fold the shorter key into the longer one when EXACTLY ONE longer key
     * extends it, so an ambiguous stem never absorbs several people.
     *
     * The short side must be specific enough to do the absorbing — see
     * mayAbsorbLineage(). "Abu Bakr" prefixes dozens of men and never claims
     * any of them.
     *
     * @param  array<string, list<int>>  $groups
     */
    private function joinLineageVariants(array &$groups): void
    {
        $keys = array_keys($groups);
        $buckets = [];
        foreach ($keys as $key) {
            $tokens = explode(' ', $key);
            $buckets[$tokens[0].' '.($tokens[1] ?? '')][] = $key;
        }

        foreach ($keys as $short) {
            if (! $this->mayAbsorbLineage($short)) {
                continue;
            }

            $tokens = explode(' ', $short);
            $extensions = [];
            foreach ($buckets[$tokens[0].' '.($tokens[1] ?? '')] ?? [] as $candidate) {
                if ($candidate !== $short && str_starts_with($candidate, $short.' ')) {
                    $extensions[] = $candidate;
                }
            }

            if (count($extensions) !== 1 || ! isset($groups[$short])) {
                continue;
            }

            $long = $extensions[0];
            $groups[$long] = array_merge($groups[$long] ?? [], $groups[$short]);
            unset($groups[$short]);
        }
    }

    /**
     * @param  array<string, string>  $anchorNames
     * @param  array<string, string>  $otherNames
     * @return string|null veto reason, or null when the two may merge
     */
    private function vetoSahabaMerge(object $anchor, object $other, array $anchorNames, array $otherNames): ?string
    {
        if ($anchor->gender && $other->gender && $anchor->gender !== $other->gender) {
            return 'gender';
        }

        if ($anchor->father_id && $other->father_id && $anchor->father_id !== $other->father_id) {
            return 'father';
        }

        $anchorDeath = trim((string) $anchor->death_date_hijri);
        $otherDeath = trim((string) $other->death_date_hijri);
        if ($anchorDeath !== '' && $otherDeath !== '' && $anchorDeath !== $otherDeath) {
            return 'death';
        }

        // The source disambiguates same-named people with a trailing marker
        // ("'Amra bint Mas'ud #1" … "#5"). Different markers, different women.
        $anchorMark = $this->disambiguator($anchorNames['en'] ?? '');
        $otherMark = $this->disambiguator($otherNames['en'] ?? '');
        if ($anchorMark !== $otherMark) {
            return 'numbered';
        }

        // Arabic may extend the name with further lineage, but must not contradict it.
        $anchorArabic = $this->foldArabic($anchorNames['ar'] ?? '');
        $otherArabic = $this->foldArabic($otherNames['ar'] ?? '');
        $compatible = $anchorArabic === '' || $otherArabic === '' || $anchorArabic === $otherArabic
            || str_starts_with($anchorArabic, $otherArabic.' ')
            || str_starts_with($otherArabic, $anchorArabic.' ');

        return $compatible ? null : 'arabic';
    }

    /**
     * Keep whichever record is referenced most; ties go to the lower id, which
     * is the earlier — and generally richer — import.
     *
     * @param  list<int>  $group
     */
    private function pickCanonicalSahabi(array $group): int
    {
        $best = $group[0];
        $bestScore = -1;

        foreach ($group as $id) {
            $score = 0;
            foreach (self::SAHABA_REFERENCES as [$table, $column]) {
                $score += DB::table($table)->where($column, $id)->count();
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $id;
            }
        }

        return $best;
    }

    private function absorbSahabi(int $canonical, int $duplicate): void
    {
        // Translations: keep the canonical's locale row, but fill any field it
        // left empty from the duplicate before discarding it.
        foreach (DB::table('sahabi_translations')->where('sahabi_id', $duplicate)->get() as $translation) {
            $existing = DB::table('sahabi_translations')
                ->where('sahabi_id', $canonical)->where('locale', $translation->locale)->first();

            if ($existing === null) {
                DB::table('sahabi_translations')->where('id', $translation->id)->update(['sahabi_id' => $canonical]);

                continue;
            }

            $fill = [];
            foreach ((array) $translation as $column => $value) {
                if (in_array($column, ['id', 'sahabi_id', 'locale', 'created_at', 'updated_at'], true)) {
                    continue;
                }
                if (trim((string) $value) !== '' && trim((string) ($existing->$column ?? '')) === '') {
                    $fill[$column] = $value;
                }
            }
            if ($fill !== []) {
                DB::table('sahabi_translations')->where('id', $existing->id)->update($fill);
            }
            DB::table('sahabi_translations')->where('id', $translation->id)->delete();
        }

        $this->fillEmptySahabiColumns($canonical, $duplicate);

        // Category pivot: repoint, dropping rows that would collide.
        $held = DB::table('sahabi_category')->where('sahabi_id', $canonical)->pluck('category_id')->all();
        DB::table('sahabi_category')->where('sahabi_id', $duplicate)->whereIn('category_id', $held)->delete();
        DB::table('sahabi_category')->where('sahabi_id', $duplicate)->update(['sahabi_id' => $canonical]);

        $this->repointRelationships($canonical, $duplicate);

        DB::table('sahabi_battles')->where('sahabi_id', $duplicate)->update(['sahabi_id' => $canonical]);
        DB::table('sahabi_timeline_items')->where('sahabi_id', $duplicate)->update(['sahabi_id' => $canonical]);

        // Parent pointers — never let the merge make someone their own parent.
        DB::table('sahaba')->where('father_id', $duplicate)->update(['father_id' => $canonical]);
        DB::table('sahaba')->where('mother_id', $duplicate)->update(['mother_id' => $canonical]);
        DB::table('sahaba')->where('id', $canonical)->where('father_id', $canonical)->update(['father_id' => null]);
        DB::table('sahaba')->where('id', $canonical)->where('mother_id', $canonical)->update(['mother_id' => null]);

        $this->repointUniqueSahabaLink('hadith_companions', $canonical, $duplicate);
        $this->repointUniqueSahabaLink('hadith_narrators', $canonical, $duplicate);

        DB::table('sahaba')->where('id', $duplicate)->delete();
    }

    private function fillEmptySahabiColumns(int $canonical, int $duplicate): void
    {
        $keep = DB::table('sahaba')->where('id', $canonical)->first();
        $drop = DB::table('sahaba')->where('id', $duplicate)->first();
        if ($keep === null || $drop === null) {
            return;
        }

        $skip = ['id', 'slug', 'external_id', 'created_at', 'updated_at', 'deleted_at', 'father_id', 'mother_id', 'views_count'];
        $fill = [];
        foreach ((array) $drop as $column => $value) {
            if (in_array($column, $skip, true) || $value === null || trim((string) $value) === '') {
                continue;
            }
            $current = $keep->$column ?? null;
            if ($current === null || trim((string) $current) === '' || $current === 0 || $current === '0') {
                $fill[$column] = $value;
            }
        }

        if ($fill !== []) {
            DB::table('sahaba')->where('id', $canonical)->update($fill);
        }
    }

    /**
     * Relationship rows are unique on (person, related_person, type) and must
     * never point a person at themselves once the two ends collapse.
     */
    private function repointRelationships(int $canonical, int $duplicate): void
    {
        foreach (['person_id' => 'related_person_id', 'related_person_id' => 'person_id'] as $column => $other) {
            foreach (DB::table('relationships')->where($column, $duplicate)->get() as $row) {
                $otherEnd = (int) $row->$other;
                if ($otherEnd === $canonical) {
                    DB::table('relationships')->where('id', $row->id)->delete();

                    continue;
                }

                $collides = DB::table('relationships')
                    ->where($column, $canonical)
                    ->where($other, $otherEnd)
                    ->where('relationship_type', $row->relationship_type)
                    ->exists();

                $collides
                    ? DB::table('relationships')->where('id', $row->id)->delete()
                    : DB::table('relationships')->where('id', $row->id)->update([$column => $canonical]);
            }
        }
    }

    /**
     * `sahaba_id` is UNIQUE on both hadith tables, so only one row may keep the
     * link. The one carrying more narrations keeps it.
     */
    private function repointUniqueSahabaLink(string $table, int $canonical, int $duplicate): void
    {
        $holder = DB::table($table)->where('sahaba_id', $duplicate)->first();
        if ($holder === null) {
            return;
        }

        $incumbent = DB::table($table)->where('sahaba_id', $canonical)->first();
        if ($incumbent === null) {
            DB::table($table)->where('id', $holder->id)->update(['sahaba_id' => $canonical]);

            return;
        }

        [$weightColumn, $weightTable] = $table === 'hadith_companions'
            ? ['companion_id', 'hadith_hadiths']
            : ['narrator_id', 'hadith_isnad'];

        $holderWeight = DB::table($weightTable)->where($weightColumn, $holder->id)->count();
        $incumbentWeight = DB::table($weightTable)->where($weightColumn, $incumbent->id)->count();

        if ($holderWeight > $incumbentWeight) {
            DB::table($table)->where('id', $incumbent->id)->update(['sahaba_id' => null]);
            DB::table($table)->where('id', $holder->id)->update(['sahaba_id' => $canonical]);
        } else {
            DB::table($table)->where('id', $holder->id)->update(['sahaba_id' => null]);
        }
    }

    // --------------------------------------------------- companions/narrators

    /**
     * Both narration-source tables have the same defect and the same shape: one
     * row per spelling a collection happened to use. Companions vary by
     * transliteration ("Abu Hurayrah"/"Abu Huraira"); narrators additionally
     * vary by Arabic grammatical case, since the name is lifted straight out of
     * the isnad — أبو / أبي / أبا هريرة are one man in three declensions.
     */
    private function mergeNarrationSources(string $table, bool $apply): void
    {
        [$childTable, $childColumn] = self::NARRATION_USAGE[$table];

        $rows = DB::table($table.' as p')
            ->leftJoin($childTable.' as u', "u.{$childColumn}", '=', 'p.id')
            ->selectRaw('p.id, p.name_ar, p.name_en, p.sahaba_id, count(u.id) uses')
            ->groupBy('p.id', 'p.name_ar', 'p.name_en', 'p.sahaba_id')
            ->get()
            ->keyBy('id');

        // Union-find: rows join a cluster if they agree on either name axis.
        $parent = [];
        $find = function (int $x) use (&$parent, &$find): int {
            while (($parent[$x] ?? $x) !== $x) {
                $x = $parent[$x] = $parent[$parent[$x]] ?? $parent[$x];
            }

            return $x;
        };

        $seen = ['ar' => [], 'en' => []];
        $skipped = ['nameless' => 0, 'placeholder' => 0, 'single_token' => 0];

        foreach ($rows as $row) {
            $parent[$row->id] ??= (int) $row->id;

            $arabic = $this->foldArabic((string) $row->name_ar);
            $english = $this->foldEnglish((string) $row->name_en);

            if ($arabic === '' && $english === '') {
                $skipped['nameless']++;

                continue;
            }
            if ($this->isPlaceholder((string) $row->name_ar) || $this->isPlaceholder((string) $row->name_en)) {
                $skipped['placeholder']++;

                continue;
            }
            if (max($this->identifyingTokens($arabic), $this->identifyingTokens($english)) < 2) {
                $skipped['single_token']++;

                continue;
            }

            foreach (['ar' => $arabic, 'en' => $english] as $axis => $key) {
                if ($key === '') {
                    continue;
                }
                if (isset($seen[$axis][$key])) {
                    $a = $find($seen[$axis][$key]);
                    $b = $find((int) $row->id);
                    if ($a !== $b) {
                        $parent[$b] = $a;
                    }
                } else {
                    $seen[$axis][$key] = (int) $row->id;
                }
            }
        }

        $clusters = [];
        foreach ($rows as $row) {
            $clusters[$find((int) $row->id)][] = (int) $row->id;
        }
        $merges = array_values(array_filter($clusters, fn ($g) => count($g) > 1));

        $affected = array_sum(array_map(
            fn ($g) => array_sum(array_map(fn ($id) => (int) $rows[$id]->uses, $g)),
            $merges,
        ));

        $this->newLine();
        $this->info(sprintf(
            '%s: %d duplicate groups, %d rows → %d canonical (%d removed), covering %s %s.',
            $table, count($merges), array_sum(array_map('count', $merges)), count($merges),
            array_sum(array_map('count', $merges)) - count($merges), number_format($affected),
            $table === 'hadith_companions' ? 'hadiths' : 'isnad links',
        ));
        $this->line('  left alone: '.json_encode($skipped));

        $this->printGroups($merges, fn (int $id) => sprintf(
            '#%-5s %-30s %-26s uses=%-5s sahaba=%s',
            $id, mb_substr((string) $rows[$id]->name_ar, 0, 28), mb_substr((string) $rows[$id]->name_en, 0, 24),
            $rows[$id]->uses, $rows[$id]->sahaba_id ?? '—',
        ));

        if (! $apply || $merges === []) {
            return;
        }

        DB::transaction(function () use ($merges, $rows, $table) {
            foreach ($merges as $group) {
                usort($group, fn ($a, $b) => (int) $rows[$b]->uses <=> (int) $rows[$a]->uses ?: $a <=> $b);
                $canonical = $group[0];

                foreach (array_slice($group, 1) as $duplicate) {
                    $this->absorbNarrationSource($table, $canonical, $duplicate);
                }
            }
        });

        $this->info('  merged.');
    }

    private function absorbNarrationSource(string $table, int $canonical, int $duplicate): void
    {
        [$childTable, $childColumn] = self::NARRATION_USAGE[$table];

        $keep = DB::table($table)->where('id', $canonical)->first();
        $drop = DB::table($table)->where('id', $duplicate)->first();
        if ($keep === null || $drop === null) {
            return;
        }

        $fill = [];
        foreach ((array) $drop as $column => $value) {
            if (in_array($column, ['id', 'created_at', 'updated_at'], true) || $value === null || trim((string) $value) === '') {
                continue;
            }
            // sahaba_id is UNIQUE — the duplicate's link may only move across
            // if the canonical has none.
            if ($column === 'sahaba_id' && $keep->sahaba_id !== null) {
                continue;
            }
            $current = $keep->$column ?? null;
            if ($current === null || trim((string) $current) === '') {
                $fill[$column] = $value;
            }
        }

        DB::table($table)->where('id', $duplicate)->update(['sahaba_id' => null]);
        if ($fill !== []) {
            DB::table($table)->where('id', $canonical)->update($fill);
        }

        DB::table($childTable)->where($childColumn, $duplicate)->update([$childColumn => $canonical]);
        DB::table($table)->where('id', $duplicate)->delete();
    }

    /**
     * Rows with no name in any language: the `companion_id` on those hadiths
     * points at nothing readable, which is a false relation rather than data.
     */
    private function handleNamelessCompanions(bool $apply): void
    {
        $nameless = DB::table('hadith_companions')
            ->where(fn ($q) => $q->whereNull('name_ar')->orWhere('name_ar', ''))
            ->where(fn ($q) => $q->whereNull('name_en')->orWhere('name_en', ''))
            ->where(fn ($q) => $q->whereNull('full_name_ar')->orWhere('full_name_ar', ''))
            ->where(fn ($q) => $q->whereNull('full_name_en')->orWhere('full_name_en', ''))
            ->pluck('id');

        if ($nameless->isEmpty()) {
            return;
        }

        $hadiths = DB::table('hadith_hadiths')->whereIn('companion_id', $nameless)->count();

        $this->newLine();
        $this->line(sprintf(
            '<comment>hadith_companions</comment>: %s rows carry no name in any language, attached to %s hadiths.',
            number_format($nameless->count()), number_format($hadiths),
        ));

        if (! $this->option('drop-nameless')) {
            $this->line('  left in place — re-run with --drop-nameless to detach and remove them.');

            return;
        }

        if (! $apply) {
            $this->line('  would detach those hadiths and delete the rows.');

            return;
        }

        DB::transaction(function () use ($nameless) {
            DB::table('hadith_hadiths')->whereIn('companion_id', $nameless)->update(['companion_id' => null]);
            DB::table('hadith_companions')->whereIn('id', $nameless)->delete();
        });

        $this->info('  detached and removed.');
    }

    // ---------------------------------------------------------------- shared

    /**
     * @return array<int, array<string, string>>
     */
    private function sahabaNames(): array
    {
        $names = [];
        DB::table('sahabi_translations')
            ->select('sahabi_id', 'locale', 'name')
            ->orderBy('id')
            ->chunk(5000, function ($rows) use (&$names) {
                foreach ($rows as $row) {
                    $names[(int) $row->sahabi_id][$row->locale] = (string) $row->name;
                }
            });

        return $names;
    }

    /**
     * Trailing source marker used to tell same-named people apart, e.g. the
     * "#3" in "'Amra bint Mas'ud #3".
     */
    private function disambiguator(string $name): string
    {
        return preg_match('/#\s*(\d+)\s*$/', trim($name), $m) === 1 ? $m[1] : '';
    }

    private function isPlaceholder(string $name): bool
    {
        return in_array(mb_strtolower(trim($name)), self::NON_PERSON, true);
    }

    /**
     * Words that carry identity. Only the structural particles are excluded —
     * a kunya ("Abu Hurayrah") or a matronymic ("Umm Salama") names a specific
     * person and must count, or the two spellings of Abu Hurayrah never meet.
     */
    private function identifyingTokens(string $folded): int
    {
        $particles = ['bin', 'al', 'بن', 'ال'];

        return count(array_filter(
            explode(' ', $folded),
            fn ($token) => $token !== '' && ! in_array($token, $particles, true),
        ));
    }

    /**
     * Whether a name is specific enough to absorb longer-lineage variants of
     * itself. A patronymic chain ("al-Bara' bin Azib") identifies one man; a
     * bare kunya ("Abu Bakr") prefixes dozens, so it may only match exactly.
     */
    private function mayAbsorbLineage(string $folded): bool
    {
        $tokens = explode(' ', $folded);

        return in_array('bin', $tokens, true) || $this->identifyingTokens($folded) >= self::MIN_LINEAGE_TOKENS;
    }

    private function foldArabic(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }

        if (class_exists(Normalizer::class)) {
            $value = Normalizer::normalize($value, Normalizer::FORM_KC) ?: $value;
        }

        $value = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $value) ?? '';
        $value = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $value);
        $value = str_replace(['ى'], 'ي', $value);
        $value = str_replace(['ة'], 'ه', $value);
        $value = str_replace(['ؤ'], 'و', $value);
        $value = str_replace(['ئ'], 'ي', $value);
        $value = preg_replace('/[^\p{Arabic}\s]/u', ' ', $value) ?? '';

        $tokens = [];
        foreach (preg_split('/\s+/u', trim($value)) ?: [] as $token) {
            if ($token === '') {
                continue;
            }
            $tokens[] = match ($token) {
                'ابن' => 'بن',
                'ابي', 'ابا' => 'ابو',
                default => $token,
            };
        }

        return implode(' ', $tokens);
    }

    /**
     * Folds the transliteration variance that makes the same name look like
     * several: Hurayrah/Huraira/Hurairah, Uthman/Othman, Siddiq/Siddique.
     *
     * Deliberately does NOT strip bare trailing vowels — that would fold
     * "Abu Bakra" into "Abu Bakr", two different men.
     */
    private function foldEnglish(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }

        $value = mb_strtolower(trim($value));
        $value = str_replace(['`', "'", '’', '‘', '-', '.', ',', '_', '(', ')', '/'], ' ', $value);
        $value = preg_replace('/[^a-z\s]/', ' ', $value) ?? '';

        $tokens = [];
        foreach (preg_split('/\s+/', trim($value)) ?: [] as $token) {
            if ($token === '') {
                continue;
            }

            $token = match ($token) {
                'ibn', 'bin', 'b' => 'bin',
                'abi', 'aba' => 'abu',
                'as', 'ash', 'ar', 'az', 'at', 'ad', 'an', 'el', 'ul' => 'al',
                'umm' => 'um',
                default => $token,
            };

            if (strlen($token) > 2) {
                $token = preg_replace('/(.)\1+/', '$1', $token) ?? $token;
                $token = str_replace(['ay', 'ei', 'ey'], 'ai', $token);
                $token = preg_replace('/([aeiou])h$/', '$1', $token) ?? $token;
                $token = str_replace('y', 'i', $token);
                $token = str_replace('o', 'u', $token);
            }

            $tokens[] = $token;
        }

        return implode(' ', $tokens);
    }

    /**
     * @param  list<list<int>>  $groups
     */
    private function printGroups(array $groups, callable $describe): void
    {
        $limit = (int) $this->option('show');
        foreach (array_slice($groups, 0, $limit) as $group) {
            $this->line('   ---');
            foreach ($group as $id) {
                $this->line('      '.$describe($id));
            }
        }

        if (count($groups) > $limit) {
            $this->line('   … '.(count($groups) - $limit).' more group(s)');
        }
    }
}
