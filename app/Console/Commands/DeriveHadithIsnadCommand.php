<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Normalizer;

/**
 * Builds the transmission chain for hadiths that have none.
 *
 * 45,000 of 51,436 hadiths carry no `hadith_isnad` rows at all, even though
 * their Arabic text states the chain in full — "حدثنا A، أخبرنا B، عن C، قال D".
 * This parses that chain into ordered links so the isnad is a relation rather
 * than a sentence.
 *
 * Only hadiths that currently have no chain are touched; the 6,436 already
 * populated are left exactly as they are.
 *
 * Dry-run by default; pass --apply to write.
 */
class DeriveHadithIsnadCommand extends Command
{
    protected $signature = 'hadith:derive-isnad
        {--apply : write the derived chains (default is a dry run)}
        {--show=8 : how many parsed chains to print}';

    protected $description = 'Parse each hadith\'s Arabic isnad into ordered narrator links';

    /**
     * Transmission verbs, longest first so "حدثني" is not matched as "حدثنا".
     * The value is the canonical method recorded on the link, matching the
     * vocabulary already in `hadith_isnad.transmission_method`.
     */
    private const TRANSMISSION_VERBS = [
        'حدثنا' => 'haddathana',
        'حدثني' => 'haddathani',
        'اخبرنا' => 'akhbarana',
        'اخبرني' => 'akhbarani',
        'انبانا' => "anba'ana",
        'سمعت' => "sami'tu",
        'عن' => "'an",
    ];

    /**
     * قال introduces speech, not a transmission link — "عن قيس، قال قام أبو
     * بكر" is Qays reporting, not Qays handing on to someone called "Abu Bakr
     * stood". Treated as a chain verb it swallows the matn as narrator names.
     * The final attribution is captured separately by hadith:derive-companions.
     */
    private const SPEECH_MARKERS = ['قال ', 'قالت ', 'يقول '];

    /** Names are short; anything longer is a clause the parser ran into. */
    private const MAX_NAME_TOKENS = 8;

    /** Relative references identify nobody and must not become narrator rows. */
    private const RELATIVE = [
        'ابيه', 'ابيها', 'امه', 'جده', 'جدها', 'عمه', 'خاله', 'ابنه', 'ابنته', 'زوجها',
        'مولاه', 'رجل', 'امراه', 'رجال', 'بعض', 'ابي', 'ام', 'اخيه', 'اخته', 'نحوه', 'مثله',
    ];

    /** Where the chain stops and the report begins. */
    private const MATN_MARKERS = [
        'ان رسول الله', 'قال رسول الله', 'ان النبي', 'قال النبي', 'عن النبي', 'عن رسول الله',
        'سمعت رسول الله', 'ان النبي صلى',
    ];

    /** A parse longer than this is runaway, not an isnad. */
    private const MAX_CHAIN = 12;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $alreadyChained = DB::table('hadith_isnad')->distinct()->pluck('hadith_id')->flip();

        $stats = ['scanned' => 0, 'parsed' => 0, 'no_chain_found' => 0, 'links' => 0];
        $chains = [];
        $samples = [];

        DB::table('hadith_translations')
            ->where('language_code', 'ar')
            ->select('hadith_id', 'text')
            ->orderBy('hadith_id')
            ->chunk(5000, function ($rows) use (&$stats, &$chains, &$samples, $alreadyChained) {
                foreach ($rows as $row) {
                    $hadithId = (int) $row->hadith_id;
                    if ($alreadyChained->has($hadithId) || isset($chains[$hadithId])) {
                        continue;
                    }

                    $stats['scanned']++;
                    $chain = $this->parseChain((string) $row->text);

                    if ($chain === []) {
                        $stats['no_chain_found']++;

                        continue;
                    }

                    $stats['parsed']++;
                    $stats['links'] += count($chain);
                    $chains[$hadithId] = $chain;

                    if (count($samples) < (int) $this->option('show')) {
                        $samples[] = "h#{$hadithId}: ".implode('  →  ', array_map(
                            fn (array $link) => "{$link['method']}:{$link['name']}",
                            $chain,
                        ));
                    }
                }
            });

        $this->table(
            ['metric', 'value'],
            [
                ['hadiths without a chain', number_format($stats['scanned'])],
                ['chain parsed from text', number_format($stats['parsed'])],
                ['no chain found in text', number_format($stats['no_chain_found'])],
                ['links to create', number_format($stats['links'])],
                ['average chain length', $stats['parsed'] > 0 ? round($stats['links'] / $stats['parsed'], 1) : 0],
            ],
        );

        foreach ($samples as $sample) {
            $this->line('   '.$sample);
        }

        if (! $apply) {
            $this->newLine();
            $this->warn('Dry run — nothing written. Re-run with --apply to persist.');

            return self::SUCCESS;
        }

        $this->persist($chains);

        return self::SUCCESS;
    }

    /**
     * @return list<array{position:int, method:string, name:string}>
     */
    private function parseChain(string $text): array
    {
        $text = $this->normaliseForParsing($text);

        $cut = mb_strlen($text);
        foreach ([...self::MATN_MARKERS, ...self::SPEECH_MARKERS] as $marker) {
            $position = mb_strpos($text, $marker);
            if ($position !== false && $position > 0 && $position < $cut) {
                $cut = $position;
            }
        }
        $chainText = mb_substr($text, 0, $cut);

        if (trim($chainText) === '') {
            return [];
        }

        $verbs = implode('|', array_map('preg_quote', array_keys(self::TRANSMISSION_VERBS)));
        if (preg_match_all('/\b('.$verbs.')\s+([^،,\.]{3,70})/u', $chainText, $matches, PREG_SET_ORDER) === false) {
            return [];
        }

        $chain = [];
        $position = 0;
        $seen = [];

        foreach ($matches as $match) {
            $position++;
            if ($position > self::MAX_CHAIN) {
                break;
            }

            $name = $this->cleanName($match[2]);
            if ($name === null) {
                continue;
            }

            // The same narrator repeated by a parsing artifact adds nothing.
            $key = $this->foldArabic($name);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $chain[] = [
                'position' => $position,
                'method' => self::TRANSMISSION_VERBS[$match[1]],
                'name' => $name,
            ];
        }

        return $chain;
    }

    private function cleanName(string $raw): ?string
    {
        // "إسماعيل يعني ابن أبي خالد" — the aside identifies the same man.
        $name = preg_replace('/\s*يعني\s*/u', ' ', $raw) ?? $raw;
        $name = preg_replace('/\s*(رضي الله عنه[ما]?|رضى الله عنه[ما]?|رحمه الله|صلى الله عليه وسلم)\s*/u', ' ', $name) ?? $name;
        $name = preg_replace('/[^\p{Arabic}\s]/u', ' ', $name) ?? $name;
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

        if (mb_strlen($name) < 3 || count(explode(' ', $name)) > self::MAX_NAME_TOKENS) {
            return null;
        }

        return in_array($this->foldArabic($name), self::RELATIVE, true) ? null : $name;
    }

    /**
     * @param  array<int, list<array{position:int, method:string, name:string}>>  $chains
     */
    private function persist(array $chains): void
    {
        $this->info('Resolving narrators...');

        $byKey = [];
        foreach (DB::table('hadith_narrators')->select('id', 'name_ar')->get() as $row) {
            $key = $this->foldArabic((string) $row->name_ar);
            if ($key !== '') {
                $byKey[$key] ??= (int) $row->id;
            }
        }

        $created = 0;
        $links = 0;

        foreach (array_chunk($chains, 2000, true) as $batch) {
            DB::transaction(function () use ($batch, &$byKey, &$created, &$links) {
                $rows = [];

                foreach ($batch as $hadithId => $chain) {
                    foreach ($chain as $link) {
                        $key = $this->foldArabic($link['name']);
                        $narratorId = $byKey[$key] ?? null;

                        if ($narratorId === null) {
                            $narratorId = (int) DB::table('hadith_narrators')->insertGetId([
                                'name_ar' => $link['name'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $byKey[$key] = $narratorId;
                            $created++;
                        }

                        $rows[] = [
                            'hadith_id' => $hadithId,
                            'narrator_id' => $narratorId,
                            'position_in_chain' => $link['position'],
                            'transmission_method' => $link['method'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                foreach (array_chunk($rows, 1000) as $insert) {
                    DB::table('hadith_isnad')->insert($insert);
                }
                $links += count($rows);
            });
        }

        $this->info(sprintf(
            '  wrote %s isnad links across %s hadiths (%s narrators created).',
            number_format($links), number_format(count($chains)), number_format($created),
        ));
    }

    private function normaliseForParsing(string $text): string
    {
        if (class_exists(Normalizer::class)) {
            $text = Normalizer::normalize($text, Normalizer::FORM_KC) ?: $text;
        }

        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text) ?? $text;
        $text = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $text) ?? $text;
        $text = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $text);
        $text = str_replace('ى', 'ي', $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private function foldArabic(string $value): string
    {
        if (trim($value) === '') {
            return '';
        }

        $value = $this->normaliseForParsing($value);
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
