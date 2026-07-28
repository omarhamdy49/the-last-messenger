<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resolves `hadith_companions.sahaba_id` and `hadith_narrators.sahaba_id` — the
 * two columns that turn a narration attribution from a bare name string into a
 * real relation onto the Sahaba module.
 *
 * Matching is deliberately conservative: a row is linked only when its
 * normalised name resolves to exactly ONE sahaba record. Ambiguous names
 * (several people share them) and unmatched names are left NULL, because a
 * wrong link misattributes a narration — worse than no link at all.
 *
 * Dry-run by default; pass --apply to write.
 */
class LinkHadithRelationsCommand extends Command
{
    protected $signature = 'hadith:link-relations
        {--apply : write the resolved links (default is a dry run)}
        {--show=15 : how many unmatched samples to print per table}';

    protected $description = 'Resolve hadith textual references into real foreign keys (companions/narrators → sahaba, related verses → quran)';

    /**
     * Isnad chains store transmission verbs and connectives inside the narrator
     * name ("سمعت محمد بن المنكدر"). They carry no identity and must not take
     * part in matching.
     */
    private const AR_NOISE_TOKENS = [
        'سمعت', 'حدثنا', 'حدثني', 'اخبرنا', 'اخبرني', 'انبانا', 'انبانى', 'ثنا', 'نا',
        'عن', 'قال', 'قالت', 'يعني', 'رضي', 'الله', 'عنه', 'عنها', 'عنهم', 'عنهما',
        'صلى', 'عليه', 'وسلم', 'مولى', 'رحمه',
    ];

    private const EN_NOISE_TOKENS = [
        'narrated', 'reported', 'heard', 'from', 'that', 'said', 'may', 'pleased', 'with', 'him', 'her',
    ];

    /** Shortest name that may resolve at all — a lone given name identifies nobody. */
    private const EXACT_MIN_TOKENS = 2;

    /** Shortest name that may resolve by lineage prefix rather than equality. */
    private const PREFIX_MIN_TOKENS = 3;

    /** @var array<string, list<string>> first-two-tokens => arabic index keys */
    private array $arBuckets = [];

    /** @var array<string, list<string>> first-two-tokens => english index keys */
    private array $enBuckets = [];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $sample = (int) $this->option('show');

        [$arIndex, $enIndex] = $this->buildSahabaIndexes();
        $this->info(sprintf('Sahaba index: %s arabic keys, %s english keys.', number_format(count($arIndex)), number_format(count($enIndex))));

        $totals = [];

        foreach (['hadith_companions', 'hadith_narrators'] as $table) {
            $totals[$table] = $this->linkTable($table, $arIndex, $enIndex, $apply, $sample);
        }

        $this->newLine();
        $this->table(
            ['table', 'rows', 'already linked', 'unique match', 'ambiguous', 'no match'],
            array_map(
                fn (string $t) => [
                    $t,
                    number_format($totals[$t]['rows']),
                    number_format($totals[$t]['already']),
                    number_format($totals[$t]['unique']),
                    number_format($totals[$t]['ambiguous']),
                    number_format($totals[$t]['none']),
                ],
                array_keys($totals),
            ),
        );

        $this->linkRelatedVerses($apply);

        if (! $apply) {
            $this->warn('Dry run — nothing written. Re-run with --apply to persist.');
        }

        return self::SUCCESS;
    }

    /**
     * `hadith_related_verses` records the Quran reference as a surah number and
     * an ayah number but leaves `quran_verse_id` null, so the citation is text
     * rather than a join. Both numbers are clean integers here, which makes the
     * resolution exact: verse_key is "surah:ayah".
     */
    private function linkRelatedVerses(bool $apply): void
    {
        $verseIds = DB::table('quran_verses')->pluck('id', 'verse_key');

        $resolved = [];
        $unresolvable = 0;

        DB::table('hadith_related_verses')
            ->whereNull('quran_verse_id')
            ->select('id', 'surah', 'ayah')
            ->orderBy('id')
            ->chunk(2000, function ($rows) use (&$resolved, &$unresolvable, $verseIds) {
                foreach ($rows as $row) {
                    $key = ((int) $row->surah).':'.((int) $row->ayah);
                    if (isset($verseIds[$key])) {
                        $resolved[$row->id] = (int) $verseIds[$key];
                    } else {
                        $unresolvable++;
                    }
                }
            });

        $this->newLine();
        $this->line(sprintf(
            '<comment>hadith_related_verses</comment> — %s resolvable to a quran verse, %s with an out-of-range surah:ayah.',
            number_format(count($resolved)),
            number_format($unresolvable),
        ));

        if (! $apply || $resolved === []) {
            return;
        }

        DB::transaction(function () use ($resolved) {
            foreach ($resolved as $rowId => $verseId) {
                DB::table('hadith_related_verses')->where('id', $rowId)->update(['quran_verse_id' => $verseId]);
            }
        });

        $this->info('hadith_related_verses: wrote '.number_format(count($resolved)).' quran_verse_id links.');
    }

    /**
     * @param  array<string, array<int, true>>  $arIndex
     * @param  array<string, array<int, true>>  $enIndex
     * @return array{rows:int, already:int, unique:int, ambiguous:int, none:int}
     */
    private function linkTable(string $table, array $arIndex, array $enIndex, bool $apply, int $sample): array
    {
        $stats = ['rows' => 0, 'already' => 0, 'unique' => 0, 'ambiguous' => 0, 'none' => 0];
        $resolved = [];
        $unmatched = [];

        DB::table($table)
            ->select('id', 'sahaba_id', 'name_ar', 'name_en', 'full_name_ar', 'full_name_en')
            ->orderBy('id')
            ->chunk(2000, function ($rows) use (&$stats, &$resolved, &$unmatched, $arIndex, $enIndex, $sample) {
                foreach ($rows as $row) {
                    $stats['rows']++;

                    if ($row->sahaba_id !== null) {
                        $stats['already']++;

                        continue;
                    }

                    $candidates = $this->candidatesFor($row, $arIndex, $enIndex);

                    if ($candidates === []) {
                        $stats['none']++;
                        if (count($unmatched) < $sample) {
                            $unmatched[] = trim(($row->name_ar ?: '—').'  |  '.($row->name_en ?: '—'));
                        }

                        continue;
                    }

                    if (count($candidates) > 1) {
                        $stats['ambiguous']++;

                        continue;
                    }

                    $stats['unique']++;
                    $resolved[$row->id] = (int) reset($candidates);
                }
            });

        if ($apply && $resolved !== []) {
            $this->persist($table, $resolved);
        }

        $this->newLine();
        $this->line("<comment>{$table}</comment> — unmatched samples:");
        foreach ($unmatched as $u) {
            $this->line('   '.$u);
        }

        return $stats;
    }

    /**
     * Candidate sahaba ids for one companion/narrator row. Arabic is tried
     * first: it is the canonical script here, so an Arabic hit is stronger
     * evidence than a transliteration hit.
     *
     * @param  array<string, array<int, true>>  $arIndex
     * @param  array<string, array<int, true>>  $enIndex
     * @return array<int, int>
     */
    private function candidatesFor(object $row, array $arIndex, array $enIndex): array
    {
        foreach ([$row->name_ar, $row->full_name_ar] as $value) {
            $hit = $this->lookup($this->normaliseArabic((string) $value), $arIndex, $this->arBuckets);
            if ($hit !== []) {
                return $hit;
            }
        }

        foreach ([$row->name_en, $row->full_name_en] as $value) {
            $hit = $this->lookup($this->normaliseEnglish((string) $value), $enIndex, $this->enBuckets);
            if ($hit !== []) {
                return $hit;
            }
        }

        return [];
    }

    /**
     * Exact match first, then a lineage-prefix match.
     *
     * Sahaba records carry the full lineage ("علي بن أبي طالب بن عبد المطلب")
     * while hadith attributions use the short form ("علي بن أبي طالب"), so
     * string equality alone finds almost nothing. A prefix match is only
     * attempted from PREFIX_MIN_TOKENS names — a one- or two-token fragment
     * like "قيس" prefixes dozens of different people and must not resolve.
     *
     * @param  array<string, array<int, true>>  $index
     * @param  array<string, list<string>>  $buckets
     * @return array<int, int>
     */
    private function lookup(string $key, array $index, array $buckets): array
    {
        if ($key === '') {
            return [];
        }

        $tokens = explode(' ', $key);
        if (count($tokens) < self::EXACT_MIN_TOKENS) {
            return [];
        }

        if (isset($index[$key])) {
            return array_keys($index[$key]);
        }

        if (count($tokens) < self::PREFIX_MIN_TOKENS) {
            return [];
        }

        // One direction only: the sahaba record may EXTEND the queried name
        // with further lineage, never truncate it. The reverse would let the
        // bare record "Abu Bakr" claim "Abu Bakr al-Siddiq" — a different man.
        $bucket = $tokens[0].' '.$tokens[1];
        $matched = [];
        foreach ($buckets[$bucket] ?? [] as $candidate) {
            if (str_starts_with($candidate, $key.' ')) {
                foreach (array_keys($index[$candidate]) as $id) {
                    $matched[$id] = $id;
                }
            }
        }

        return array_values($matched);
    }

    /**
     * @param  array<int, int>  $resolved  companion/narrator id => sahaba id
     */
    private function persist(string $table, array $resolved): void
    {
        // sahaba_id carries a UNIQUE index, so two rows may not resolve to the
        // same person — and this data has duplicate rows for the same narrator
        // ("Abu Hurayrah" / "Abu Huraira" / "Huraira"). Give the link to
        // whichever duplicate carries the most narrations, so the relation
        // lands where it is actually used.
        $weights = $this->usageWeights($table);
        uksort($resolved, fn (int $a, int $b) => ($weights[$b] ?? 0) <=> ($weights[$a] ?? 0) ?: $a <=> $b);

        // Seed with what is already persisted, so a re-run cannot collide with
        // links an earlier run wrote.
        $seen = DB::table($table)->whereNotNull('sahaba_id')->pluck('sahaba_id')
            ->flip()->map(fn () => true)->all();

        $writes = [];
        foreach ($resolved as $rowId => $sahabaId) {
            if (isset($seen[$sahabaId])) {
                continue;
            }
            $seen[$sahabaId] = true;
            $writes[$rowId] = $sahabaId;
        }

        $skipped = count($resolved) - count($writes);
        if ($skipped > 0) {
            $this->warn("{$table}: {$skipped} rows dropped — they resolved to a sahaba already claimed by another row (sahaba_id is unique).");
        }

        DB::transaction(function () use ($table, $writes) {
            foreach (array_chunk($writes, 500, true) as $chunk) {
                foreach ($chunk as $rowId => $sahabaId) {
                    DB::table($table)->where('id', $rowId)->update(['sahaba_id' => $sahabaId]);
                }
            }
        });

        $this->info("{$table}: wrote ".number_format(count($writes)).' links.');
    }

    /**
     * How many narrations each companion/narrator row actually carries.
     *
     * @return array<int, int>
     */
    private function usageWeights(string $table): array
    {
        return $table === 'hadith_companions'
            ? DB::table('hadith_hadiths')
                ->whereNotNull('companion_id')
                ->selectRaw('companion_id, count(*) n')
                ->groupBy('companion_id')
                ->pluck('n', 'companion_id')
                ->map(fn ($n) => (int) $n)
                ->all()
            : DB::table('hadith_isnad')
                ->selectRaw('narrator_id, count(*) n')
                ->groupBy('narrator_id')
                ->pluck('n', 'narrator_id')
                ->map(fn ($n) => (int) $n)
                ->all();
    }

    /**
     * @return array{0: array<string, array<int, true>>, 1: array<string, array<int, true>>}
     */
    private function buildSahabaIndexes(): array
    {
        $ar = [];
        $en = [];

        DB::table('sahabi_translations')
            ->select('sahabi_id', 'locale', 'name', 'full_name')
            ->orderBy('id')
            ->chunk(5000, function ($rows) use (&$ar, &$en) {
                foreach ($rows as $row) {
                    foreach ([$row->name, $row->full_name] as $value) {
                        if (! $value) {
                            continue;
                        }

                        if ($row->locale === 'ar') {
                            $key = $this->normaliseArabic((string) $value);
                            if ($key !== '') {
                                $ar[$key][(int) $row->sahabi_id] = true;
                            }
                        } elseif ($row->locale === 'en') {
                            $key = $this->normaliseEnglish((string) $value);
                            if ($key !== '') {
                                $en[$key][(int) $row->sahabi_id] = true;
                            }
                        }
                    }
                }
            });

        $this->arBuckets = $this->bucketise($ar);
        $this->enBuckets = $this->bucketise($en);

        return [$ar, $en];
    }

    /**
     * Group index keys by their first two tokens so a prefix match scans a
     * handful of candidates instead of the whole index.
     *
     * @param  array<string, array<int, true>>  $index
     * @return array<string, list<string>>
     */
    private function bucketise(array $index): array
    {
        $buckets = [];

        foreach (array_keys($index) as $key) {
            $tokens = explode(' ', $key);
            if (count($tokens) < 2) {
                continue;
            }
            $buckets[$tokens[0].' '.$tokens[1]][] = $key;
        }

        return $buckets;
    }

    private function normaliseArabic(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }

        // Some rows store Arabic presentation forms (ﺑ instead of ب); NFKC
        // folds them back to the plain letters the rest of the data uses.
        if (class_exists(\Normalizer::class)) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_KC) ?: $value;
        }

        // Harakat, tatweel and Quranic annotation marks carry no identity.
        $value = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $value) ?? '';
        $value = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $value);
        $value = str_replace(['ى'], 'ي', $value);
        $value = str_replace(['ة'], 'ه', $value);
        $value = str_replace(['ؤ'], 'و', $value);
        $value = str_replace(['ئ'], 'ي', $value);
        $value = preg_replace('/[^\p{Arabic}\s]/u', ' ', $value) ?? '';

        $tokens = [];
        foreach (preg_split('/\s+/u', trim($value)) ?: [] as $token) {
            $token = trim($token);
            if ($token === '' || in_array($token, self::AR_NOISE_TOKENS, true)) {
                continue;
            }
            // ابن and بن are the same lineage particle; ابي/ابا are cases of ابو.
            $token = match ($token) {
                'ابن' => 'بن',
                'ابي', 'ابا' => 'ابو',
                default => $token,
            };
            $tokens[] = $token;
        }

        return implode(' ', $tokens);
    }

    private function normaliseEnglish(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }

        $value = mb_strtolower($value);
        $value = str_replace(['`', "'", '’', '‘', '-', '.', ',', '_'], ' ', $value);
        $value = preg_replace('/[^a-z\s]/', ' ', $value) ?? '';

        $tokens = [];
        foreach (preg_split('/\s+/', trim($value)) ?: [] as $token) {
            if ($token === '' || in_array($token, self::EN_NOISE_TOKENS, true)) {
                continue;
            }
            $token = match ($token) {
                'ibn', 'bin', 'b' => 'bin',
                'abi', 'aba' => 'abu',
                'as', 'ash', 'ar', 'az', 'at', 'ad', 'an', 'el' => 'al',
                default => $token,
            };
            $tokens[] = $token;
        }

        return implode(' ', $tokens);
    }
}
