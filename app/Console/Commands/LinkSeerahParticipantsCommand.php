<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Links `seerah_participants` rows to their `muslims` record by name.
 *
 * Exact normalised matching only, and only when the match is UNIQUE. No fuzzy
 * distance, no "closest" pick, no first-of-several: a participant pointed at the
 * wrong person is worse than one left unlinked, because the wrong link looks
 * finished and the review queue no longer shows it.
 *
 * Everything it will not resolve is reported by reason so an editor can finish
 * the tail by hand in the panel, exactly as locations:backfill does.
 */
class LinkSeerahParticipantsCommand extends Command
{
    protected $signature = 'seerah:link-participants
        {--dry-run : Report what would change and write nothing}
        {--show-unmatched=0 : List this many unmatched names}';

    protected $description = 'ربط مشاركي أحداث السيرة بسجلاتهم في وحدة المسلمين';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // Index every muslim by normalised Arabic AND English name. A name that
        // maps to more than one record is dropped from the index entirely — that
        // is the ambiguity we refuse to guess through.
        $index = [];

        DB::table('muslims')->select('id', 'name_ar', 'name_en')->orderBy('id')
            ->chunk(2000, function ($people) use (&$index): void {
                foreach ($people as $person) {
                    foreach ([$person->name_ar, $person->name_en] as $name) {
                        $key = $this->normalise((string) $name);

                        if ($key === '') {
                            continue;
                        }

                        $index[$key][(int) $person->id] = true;
                    }
                }
            });

        $rows = DB::table('seerah_participants as p')
            ->leftJoin('seerah_participant_translations as ar', function ($join): void {
                $join->on('ar.seerah_participant_id', '=', 'p.id')->where('ar.locale', '=', 'ar');
            })
            ->leftJoin('seerah_participant_translations as en', function ($join): void {
                $join->on('en.seerah_participant_id', '=', 'p.id')->where('en.locale', '=', 'en');
            })
            ->whereNull('p.participant_id')
            ->get(['p.id', 'p.role', 'ar.name as name_ar', 'en.name as name_en']);

        $linked = 0;
        $ambiguous = 0;
        $unmatched = [];

        foreach ($rows as $row) {
            $ids = [];

            foreach ([$row->name_ar, $row->name_en] as $name) {
                $key = $this->normalise((string) $name);

                if ($key === '' || ! isset($index[$key])) {
                    continue;
                }

                $ids += $index[$key];
            }

            if ($ids === []) {
                $unmatched[] = trim((string) $row->name_ar).($row->role ? "  [{$row->role}]" : '');

                continue;
            }

            if (count($ids) > 1) {
                $ambiguous++;

                continue;
            }

            $linked++;

            if (! $dry) {
                DB::table('seerah_participants')->where('id', $row->id)->update([
                    'participant_id' => array_key_first($ids),
                    'participant_type' => 'person',
                    'updated_at' => now(),
                ]);
            }
        }

        $already = DB::table('seerah_participants')->whereNotNull('participant_id')->count();

        $this->newLine();
        $this->table(
            ['', $dry ? 'Would link' : 'Linked'],
            [
                ['Linked to a unique record', number_format($linked)],
                ['Ambiguous — name matches several people', number_format($ambiguous)],
                ['No match — likely non-Muslim or a tribe', number_format(count($unmatched))],
                ['Already linked before this run', number_format($already)],
            ],
        );

        $show = (int) $this->option('show-unmatched');

        if ($show > 0 && $unmatched !== []) {
            $this->newLine();
            $this->comment('Unmatched (these stay free text and are flagged «يحتاج مراجعة» in the panel):');

            foreach (array_slice(array_values(array_unique($unmatched)), 0, $show) as $name) {
                $this->line('  · '.$name);
            }
        }

        $this->newLine();
        $this->info('Nothing is guessed: ambiguous and unmatched rows keep their free-text name for an editor.');

        return self::SUCCESS;
    }

    /** Harakat, tatweel and the alef/ya variants differ between the two tables. */
    private function normalise(string $value): string
    {
        $value = preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $value) ?? $value;
        $value = strtr($value, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه']);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim(mb_strtolower($value));
    }
}
