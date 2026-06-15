<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\VerseTranslation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verifies every verse's Arabic text against the Quran.com Uthmani API,
 * verse by verse, and reports any mismatch or missing/extra verse. Read-only.
 */
class VerifyQuranArabicCommand extends Command
{
    protected $signature = 'quran:verify-arabic {--show=30 : how many mismatches to print} {--chapter= : verify a single chapter}';

    protected $description = 'Verify all verse Arabic text matches quran.com (verse by verse)';

    private const BASE = 'https://api.quran.com/api/v4';

    public function handle(): int
    {
        // Our text keyed by verse_key (ar).
        $ours = VerseTranslation::query()
            ->where('lang', 'ar')
            ->join('quran_verses', 'quran_verses.id', '=', 'quran_verse_translations.verse_id')
            ->pluck('quran_verse_translations.text', 'quran_verses.verse_key');

        $only = $this->option('chapter');
        $chapters = $only ? [(int) $only] : range(1, 114);

        $checked = 0;
        $matched = 0;
        $mismatches = [];
        $missing = [];

        $bar = $this->output->createProgressBar(count($chapters));

        foreach ($chapters as $chapter) {
            try {
                $resp = Http::timeout(30)->retry(3, 600)
                    ->get(self::BASE.'/quran/verses/uthmani', ['chapter_number' => $chapter]);
            } catch (Throwable $e) {
                $this->warn(" chapter {$chapter}: {$e->getMessage()}");
                $bar->advance();

                continue;
            }

            if (! $resp->ok()) {
                $this->warn(" chapter {$chapter}: HTTP {$resp->status()}");
                $bar->advance();

                continue;
            }

            foreach (($resp->json('verses') ?? []) as $row) {
                $key = $row['verse_key'] ?? null;
                $theirs = trim((string) ($row['text_uthmani'] ?? ''));
                if ($key === null) {
                    continue;
                }

                $checked++;

                if (! isset($ours[$key])) {
                    $missing[] = $key;

                    continue;
                }

                if ($this->normalize((string) $ours[$key]) === $this->normalize($theirs)) {
                    $matched++;
                } else {
                    $mismatches[] = ['key' => $key, 'ours' => (string) $ours[$key], 'theirs' => $theirs];
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $ourTotal = $only ? null : $ours->count();

        $this->info("Checked (quran.com): {$checked}");
        $this->info('Matched: '.$matched);
        $this->info('Mismatched: '.count($mismatches));
        $this->info('Missing in our DB: '.count($missing));
        if ($ourTotal !== null) {
            $this->info("Our ar-verse rows total: {$ourTotal}".($ourTotal === 6236 ? ' (expected 6236 ✓)' : ' (EXPECTED 6236 ✗)'));
        }

        $show = (int) $this->option('show');

        if ($missing !== []) {
            $this->newLine();
            $this->warn('Missing verse keys: '.implode(', ', array_slice($missing, 0, $show)));
        }

        if ($mismatches !== []) {
            $this->newLine();
            $this->warn('First '.min($show, count($mismatches)).' mismatches:');
            foreach (array_slice($mismatches, 0, $show) as $m) {
                $this->line("  <fg=yellow>{$m['key']}</>");
                $this->line('    ours  : '.mb_substr($m['ours'], 0, 80));
                $this->line('    quran : '.mb_substr($m['theirs'], 0, 80));
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✓ All checked verses match quran.com.');

        return self::SUCCESS;
    }

    /**
     * Compare on the meaningful text: normalize whitespace only (keep diacritics,
     * since matching quran.com's Uthmani script exactly is the goal).
     */
    private function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
