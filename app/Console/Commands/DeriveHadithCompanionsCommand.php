<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Normalizer;

/**
 * Re-derives which companion narrated each hadith by reading the isnad out of
 * the hadith's own text.
 *
 * The imported `companion_id` values do not come from the text and are wrong
 * for the overwhelming majority of rows — Muslim 1014b, whose text reads
 * "عن أبي هريرة", was attributed to Umar ibn al-Khattab. Measured against the
 * attribution stated in the English translation, 3,891 of 4,471 checkable rows
 * disagreed. That is why this rewrites rather than fills gaps.
 *
 * Two independent readings are taken:
 *   - the English translation, which opens with an attribution formula
 *     ("Narrated Abu Huraira:", "Anas b. Malik reported:")
 *   - the Arabic isnad, whose last عن link before the matn is the companion
 *
 * A hadith is only rewritten when a name is found. Identity is taken from one
 * script per hadith — English where the translation names someone, Arabic
 * otherwise — so the two readings are never silently blended. The Arabic name
 * is written onto an English-identified companion only once most of that
 * companion's hadiths agree on it.
 *
 * Dry-run by default; pass --apply to write.
 */
class DeriveHadithCompanionsCommand extends Command
{
    protected $signature = 'hadith:derive-companions
        {--apply : write the derived attributions (default is a dry run)}
        {--show=15 : how many samples to print}';

    protected $description = 'Re-derive each hadith\'s narrating companion from its own isnad text';

    /** Openings that are grammar, not a narrator. */
    private const NOT_A_NAME = [
        'it', 'it was', 'this', 'that', 'there', 'the', 'a', 'an', 'he', 'she', 'they', 'we', 'i',
        'another', 'similar', 'the same', 'a similar', 'allah', 'the prophet', 'the messenger',
        'a man', 'a woman', 'someone', 'the same as', 'this hadith', 'a similar hadith',
    ];

    /**
     * Sentences that open by referring to the report rather than naming anyone
     * ("This hadith has been reported…", "The same tradition is related…").
     * Without this the phrase itself becomes a 777-hadith "narrator".
     */
    private const NOT_A_NAME_PREFIX = [
        'this ', 'these ', 'the same', 'a similar', 'another ', 'it ', 'there ', 'a version',
        'a hadith', 'the above', 'the tradition', 'the narration', 'a tradition', 'a narration',
        'the hadith', 'a report', 'the report', 'a chain', 'the chain', 'a like',
        // Malik's Muwatta opens every report with its own transmitter —
        // "Yahya related to me from Malik from X from Y". Yahya ibn Yahya
        // al-Laythi transmitted the book; he is not the companion. Rejecting
        // him here lets the Arabic isnad supply the real attribution.
        'yahya related', 'yahya said', 'yahya bin yahya related', 'malik related',
    ];

    /**
     * Arabic relative references. The isnad often ends "عن أبيه" — "from his
     * father" — which identifies nobody on its own.
     */
    private const ARABIC_RELATIVE = [
        'ابيه', 'ابيها', 'امه', 'جده', 'جدها', 'عمه', 'خاله', 'ابنه', 'ابنته', 'زوجها', 'مولاه',
        'رجل', 'امراه', 'رجال', 'بعض', 'اصحاب النبي', 'ابي', 'ام', 'اخيه', 'اخته',
    ];

    /** Share of a companion's hadiths that must share one Arabic reading before it is written in as their name. */
    private const ARABIC_CORROBORATION = 0.6;

    /** Where the chain stops and the report itself begins. */
    private const MATN_MARKERS = [
        'ان رسول الله', 'قال رسول الله', 'ان النبي', 'قال النبي', 'عن النبي', 'عن رسول الله',
        'سمعت رسول الله', 'ان رسول اللـه', 'قال ', 'قالت ',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info('Reading hadith texts...');
        $texts = $this->loadTexts();

        $stats = [
            'hadiths' => count($texts),
            'from_english' => 0,
            'from_arabic_only' => 0,
            'no_attribution' => 0,
        ];

        $derived = [];
        $samples = [];

        foreach ($texts as $hadithId => $byLanguage) {
            $english = isset($byLanguage['en']) ? $this->englishNarrator($byLanguage['en']) : null;
            $arabic = isset($byLanguage['ar']) ? $this->arabicNarrator($byLanguage['ar']) : null;

            if ($english === null && $arabic === null) {
                $stats['no_attribution']++;

                continue;
            }

            if ($english !== null) {
                $stats['from_english']++;
            } else {
                $stats['from_arabic_only']++;
            }

            $derived[$hadithId] = ['en' => $english, 'ar' => $arabic];

            if (count($samples) < (int) $this->option('show')) {
                $samples[] = sprintf('h#%-6s en="%s"  ar="%s"', $hadithId, $english ?? '—', $arabic ?? '—');
            }
        }

        $this->reportExtraction($stats, $samples);

        $comparison = $this->compareWithStored($derived);
        $this->reportComparison($comparison);

        if (! $apply) {
            $this->newLine();
            $this->warn('Dry run — nothing written. Re-run with --apply to rewrite companion attributions.');

            return self::SUCCESS;
        }

        $this->rewrite($derived);

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function loadTexts(): array
    {
        $texts = [];

        DB::table('hadith_translations')
            ->whereIn('language_code', ['ar', 'en'])
            ->select('hadith_id', 'language_code', 'text')
            ->orderBy('hadith_id')
            ->chunk(20000, function ($rows) use (&$texts) {
                foreach ($rows as $row) {
                    // Keep the first translation per language; later ones are
                    // alternate renderings of the same chain.
                    $texts[(int) $row->hadith_id][$row->language_code] ??= (string) $row->text;
                }
            });

        return $texts;
    }

    private function englishNarrator(string $text): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        // The book's own transmitter opens every report in some collections;
        // the check has to look at the sentence, not at the name it yields.
        if ($this->readsAsAReference($text)) {
            return null;
        }

        $patterns = [
            '/^Narrated\s+(?:by\s+)?(.{2,60}?)\s*[:,.]/iu',
            '/^It (?:was|has been) narrated (?:on the authority of|from|that)\s+(.{2,60}?)\s*(?:,|that\b|who\b|as saying|:)/iu',
            '/^(?:On the authority of|Reported by|It is reported from)\s+(.{2,60}?)\s*[:,.]/iu',
            '/^(.{2,60}?)\s+(?:reported|narrated|said|related|relates)\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) !== 1) {
                continue;
            }

            $name = preg_replace('/\s*\([^)]*\)/u', ' ', trim($matches[1])) ?? '';
            $name = preg_replace('/\b(?:may Allah be pleased with (?:him|her|them)|radiallahu[^,]*)\b/iu', ' ', $name) ?? $name;
            $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name, " \t\n\r-–—,;:");
            // "'Ali said" — the verb was inside the captured span.
            $name = trim(preg_replace('/\s+(?:said|reported|narrated|related|relates|says)$/iu', '', $name) ?? $name);

            if ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 60) {
                continue;
            }
            if ($this->readsAsAReference($name)) {
                continue;
            }
            // A narrator's name starts with a capital or an ayn/hamza mark.
            if (preg_match('/^[A-Z\'`\x{2018}\x{2019}]/u', $name) !== 1) {
                continue;
            }
            // "'Abd Allaah b" — the match stopped mid-lineage. A name ending on
            // a connecting particle is truncated, and folding it would mint a
            // companion row for half a name.
            if (preg_match('/\b(?:b|bin|ibn|abu|abi|al|umm|bint)$/iu', $name) === 1) {
                continue;
            }

            return $name;
        }

        return null;
    }

    private function arabicNarrator(string $text): ?string
    {
        $text = $this->stripArabicMarks($text);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        $cut = mb_strlen($text);
        foreach (self::MATN_MARKERS as $marker) {
            $position = mb_strpos($text, $marker);
            if ($position !== false && $position > 0 && $position < $cut) {
                $cut = $position;
            }
        }

        $chain = mb_substr($text, 0, $cut);

        if (preg_match_all('/\bعن\s+([^،,\.]{3,60})/u', $chain, $matches) !== 1 && empty($matches[1])) {
            return null;
        }

        $last = (string) end($matches[1]);
        $last = preg_replace('/\s*(رضي الله عنه[ما]?|رضى الله عنه[ما]?|رحمه الله)\s*$/u', '', $last) ?? $last;
        // Trim multibyte punctuation with a regex — trim() works on bytes and
        // would slice the leading Arabic letter in half.
        $last = preg_replace('/^[\s\x{200E}\x{200F}،\.]+|[\s\x{200E}\x{200F}،\.]+$/u', '', $last) ?? $last;
        $last = trim(preg_replace('/\s+/u', ' ', $last) ?? $last);

        if (mb_strlen($last) < 3) {
            return null;
        }

        $normalised = $this->foldArabic($last);
        if ($normalised === '' || in_array($normalised, self::ARABIC_RELATIVE, true)) {
            return null;
        }

        return $last;
    }

    /**
     * True when the captured phrase points back at the report instead of
     * naming a narrator.
     */
    private function readsAsAReference(string $name): bool
    {
        $lowered = mb_strtolower($name);

        if (in_array($lowered, self::NOT_A_NAME, true)) {
            return true;
        }

        foreach (self::NOT_A_NAME_PREFIX as $prefix) {
            if (str_starts_with($lowered, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function stripArabicMarks(string $text): string
    {
        if (class_exists(Normalizer::class)) {
            $text = Normalizer::normalize($text, Normalizer::FORM_KC) ?: $text;
        }

        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text) ?? $text;

        return preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $text) ?? $text;
    }

    /**
     * @param  array<int, array<string, string|null>>  $derived
     * @return array{checked:int, agree:int, differ:int, samples:list<string>}
     */
    private function compareWithStored(array $derived): array
    {
        $stored = DB::table('hadith_hadiths as h')
            ->join('hadith_companions as c', 'c.id', '=', 'h.companion_id')
            ->where(fn ($q) => $q->whereNotNull('c.name_en')->where('c.name_en', '!=', ''))
            ->pluck('c.name_en', 'h.id');

        $result = ['checked' => 0, 'agree' => 0, 'differ' => 0, 'samples' => []];

        foreach ($stored as $hadithId => $name) {
            $englishName = $derived[$hadithId]['en'] ?? null;
            if ($englishName === null) {
                continue;
            }

            $result['checked']++;
            if ($this->sameName($this->foldEnglish($englishName), $this->foldEnglish((string) $name))) {
                $result['agree']++;

                continue;
            }

            $result['differ']++;
            if (count($result['samples']) < 10) {
                $result['samples'][] = sprintf('h#%-6s text says "%s" | stored "%s"', $hadithId, $englishName, $name);
            }
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, string|null>>  $derived
     */
    private function rewrite(array $derived): void
    {
        $this->info('Resolving companion records...');

        // Identity is decided in ONE script per hadith — English when the
        // translation names someone, Arabic otherwise. Mixing the two into a
        // single key space lets an English reading and an Arabic reading of
        // different people fuse into one row (a "Yahya" carrying the Arabic
        // name حميد). Keys are namespaced so that cannot happen; rows that do
        // describe the same person are reunited afterwards by data:dedupe.
        $byKey = [];
        foreach (DB::table('hadith_companions')->select('id', 'name_ar', 'name_en')->get() as $row) {
            $english = $this->foldEnglish((string) $row->name_en);
            $arabic = $this->foldArabic((string) $row->name_ar);
            if ($english !== '') {
                $byKey['en:'.$english] ??= (int) $row->id;
            }
            if ($arabic !== '') {
                $byKey['ar:'.$arabic] ??= (int) $row->id;
            }
        }

        $assignments = [];
        $arabicReadings = [];
        $created = 0;

        DB::transaction(function () use ($derived, &$byKey, &$assignments, &$arabicReadings, &$created) {
            foreach ($derived as $hadithId => $names) {
                $key = $names['en'] !== null
                    ? 'en:'.$this->foldEnglish($names['en'])
                    : 'ar:'.$this->foldArabic((string) $names['ar']);

                if ($key === 'en:' || $key === 'ar:') {
                    continue;
                }

                $companionId = $byKey[$key] ?? null;

                if ($companionId === null) {
                    $companionId = (int) DB::table('hadith_companions')->insertGetId([
                        'name_ar' => str_starts_with($key, 'ar:') ? $names['ar'] : null,
                        'name_en' => $names['en'],
                        'verified' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $created++;
                    $byKey[$key] = $companionId;
                }

                $assignments[$companionId][] = $hadithId;

                if ($names['ar'] !== null) {
                    $reading = $this->foldArabic($names['ar']);
                    if ($reading !== '') {
                        $arabicReadings[$companionId][$reading] ??= ['count' => 0, 'text' => $names['ar']];
                        $arabicReadings[$companionId][$reading]['count']++;
                    }
                }
            }

            foreach ($assignments as $companionId => $hadithIds) {
                foreach (array_chunk($hadithIds, 1000) as $chunk) {
                    DB::table('hadith_hadiths')->whereIn('id', $chunk)->update(['companion_id' => $companionId]);
                }
            }

            $this->attachCorroboratedArabicNames($arabicReadings);
        });

        $removed = $this->removeUnreferencedCompanions();
        if ($removed > 0) {
            $this->line("  removed {$removed} companion row(s) left unreferenced by this rewrite.");
        }

        $this->info(sprintf(
            '  rewrote %s hadith attributions across %s companions (%s newly created).',
            number_format(array_sum(array_map('count', $assignments))),
            number_format(count($assignments)),
            number_format($created),
        ));
    }

    /**
     * A rewrite re-points hadiths, which can strand companion rows that nothing
     * references any more — including rows this command itself created on an
     * earlier run. Drop them so re-running does not grow the table.
     */
    private function removeUnreferencedCompanions(): int
    {
        return DB::table('hadith_companions')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('hadith_hadiths')
                ->whereColumn('hadith_hadiths.companion_id', 'hadith_companions.id'))
            ->whereNull('sahaba_id')
            ->delete();
    }

    /**
     * Give an English-identified companion its Arabic name only when the isnad
     * agrees with itself: the same Arabic reading has to account for most of
     * that companion's hadiths. A single stray reading — the English naming the
     * companion while the Arabic chain stopped at a sub-narrator — is then
     * dropped rather than written in as that person's Arabic name.
     *
     * @param  array<int, array<string, array{count:int, text:string}>>  $readings
     */
    private function attachCorroboratedArabicNames(array $readings): void
    {
        $missing = DB::table('hadith_companions')
            ->where(fn ($q) => $q->whereNull('name_ar')->orWhere('name_ar', ''))
            ->pluck('id')
            ->flip();

        foreach ($readings as $companionId => $variants) {
            if (! $missing->has($companionId)) {
                continue;
            }

            $total = array_sum(array_column($variants, 'count'));
            uasort($variants, fn ($a, $b) => $b['count'] <=> $a['count']);
            $modal = reset($variants);

            if ($total > 0 && $modal['count'] / $total >= self::ARABIC_CORROBORATION) {
                DB::table('hadith_companions')->where('id', $companionId)->update(['name_ar' => $modal['text']]);
            }
        }
    }

    /**
     * @param  array<string, int>  $stats
     * @param  list<string>  $samples
     */
    private function reportExtraction(array $stats, array $samples): void
    {
        $this->newLine();
        $this->table(
            ['metric', 'hadiths', 'share'],
            array_map(
                fn (string $key) => [
                    $key,
                    number_format($stats[$key]),
                    $key === 'hadiths' ? '' : round($stats[$key] / max(1, $stats['hadiths']) * 100).'%',
                ],
                array_keys($stats),
            ),
        );

        foreach ($samples as $sample) {
            $this->line('   '.$sample);
        }
    }

    /**
     * @param  array{checked:int, agree:int, differ:int, samples:list<string>}  $comparison
     */
    private function reportComparison(array $comparison): void
    {
        $this->newLine();
        $this->line('<comment>Existing companion_id vs the attribution stated in the text:</comment>');
        $this->line('   checked: '.number_format($comparison['checked']));
        $this->line('   agree:   '.number_format($comparison['agree']));
        $this->line('   <fg=red>differ:  '.number_format($comparison['differ']).'</>');

        foreach ($comparison['samples'] as $sample) {
            $this->line('      '.$sample);
        }
    }

    private function sameName(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_starts_with($a, $b.' ') || str_starts_with($b, $a.' ');
    }

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

    private function foldArabic(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }

        $value = $this->stripArabicMarks($value);
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
}
