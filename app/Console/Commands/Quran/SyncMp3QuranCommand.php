<?php

declare(strict_types=1);

namespace App\Console\Commands\Quran;

use App\Domain\Quran\Infrastructure\Mp3Quran\Mp3QuranCatalogue;
use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterTranslation;
use App\Domain\Quran\Services\Mp3QuranReciterImporter;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Quran\Services\ReciterNameMatcher;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Makes mp3quran.net the whole-surah audio source for every reciter we can
 * match there.
 *
 * Not just a gap-filler: per-ayah highlighting reads mp3quran's timings, and a
 * timing file only lines up with the recording it was made from. Our previous
 * quranicaudio URLs are a different take by the same qari — al-Baqarah runs
 * 199 minutes there against 111 on mp3quran — so a mixed library highlights the
 * wrong ayah. Mapped reciters are therefore switched over wholesale, and
 * `--keep-existing` is the escape hatch.
 *
 * Conservative where it counts. A wrong name match would play another qari's
 * voice under a reciter's name, so anything short of certain is printed for a
 * human instead of written, and two sample files are HEAD-checked before any
 * of a reciter's URLs are stored — a reciter is switched over completely or
 * not at all. Idempotent: a second run writes nothing.
 *
 * Examples:
 *   php artisan quran:sync-mp3quran --dry-run
 *   php artisan quran:sync-mp3quran
 *   php artisan quran:sync-mp3quran --only=6,29 --keep-existing
 *   php artisan quran:sync-mp3quran --map=36:106       # resolve an ambiguous one
 *   php artisan quran:sync-mp3quran --import-new       # add the rest of the catalogue
 *   php artisan quran:sync-mp3quran --fresh --force    # rebuild it from scratch
 */
class SyncMp3QuranCommand extends Command
{
    use ConfirmableTrait;

    protected $hidden = true;

    protected $signature = 'quran:sync-mp3quran
        {--dry-run : Preview only — list every change and write nothing}
        {--import-new : Also add every mp3quran recording we do not have yet as a new, publicly visible reciter}
        {--inactive : Import new reciters hidden from the public site, for review before publishing}
        {--fresh : Delete the reciters imported from mp3quran and rebuild them from scratch. Portraits, ordering, featured flags, slugs and per-verse audio folders are preserved; anything an admin added that mp3quran does not know is not. Implies --import-new}
        {--keep-existing : Keep surahs that already have audio instead of switching them to mp3quran}
        {--only= : Limit the run to these reciter ids, comma separated (blank = all reciters)}
        {--map=* : Resolve a reciter the matcher was unsure about, as reciterId:mp3quranReadId (comma separated for several)}
        {--force : Skip the production confirmation prompt}';

    protected $description = 'Match our reciters to mp3quran.net, import their whole-surah audio, and optionally add or rebuild the whole catalogue';

    /**
     * mp3quran rewaya ids. 21/22 are not riwayat at all — they are how the
     * catalogue files a mu'allim or mujawwad recording, which is always Hafs
     * here, so style is matched before riwayah.
     */
    private const WARSH = [2, 10, 18];

    private const MUJAWWAD = [22];

    private const MUALLIM = [21];

    /** @var array<string, string|null> folder => the URL that failed, or null */
    private array $probed = [];

    public function handle(): int
    {
        try {
            $catalogue = Mp3QuranCatalogue::fetch();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $reciters = Reciter::query()
            ->recitationsOnly()
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('id')
            ->get();

        $only = array_filter(array_map('intval', explode(',', (string) $this->option('only'))));
        $manual = $this->manualMap();
        $dryRun = (bool) $this->option('dry-run');
        $keep = (bool) $this->option('keep-existing');
        $fresh = (bool) $this->option('fresh');
        $inactive = (bool) $this->option('inactive');
        // A rebuild that did not put the reciters back would leave the site
        // with no audio at all, so --fresh always runs the import.
        $importNew = $fresh || (bool) $this->option('import-new');

        // An empty table is only a problem when this run has no way to fill it.
        if ($reciters->isEmpty() && ! $importNew) {
            $this->error('No active reciters found — run quran:import-reciters first, or pass --import-new.');

            return self::FAILURE;
        }

        if ($fresh && ! $dryRun && ! $this->confirmToProceed('Deleting every reciter imported from mp3quran')) {
            return self::FAILURE;
        }

        $preserved = $fresh ? $this->freshen($catalogue, $dryRun) : [];

        if ($fresh) {
            // Everything mapped is gone (or would be); the loop below has
            // nothing left to reconcile and the import rebuilds from the
            // snapshot. Filtered in a dry run too, so the preview matches.
            $reciters = $reciters->whereNull('mp3quran_folder');
        }

        $summary = [];
        $folders = [];
        $examples = [];
        $ambiguous = [];
        $unmatched = [];
        $created = 0;
        $replaced = 0;
        $kept = 0;

        foreach ($reciters as $reciter) {
            if ($only && ! in_array($reciter->id, $only, true)) {
                continue;
            }

            $name = $reciter->getDisplayName('ar');
            [$moshaf, $note] = $this->resolve($reciter, $name, $catalogue, $manual, $ambiguous, $unmatched);

            if ($moshaf === null) {
                continue;
            }

            $readId = $catalogue->readIdFor($moshaf['server']);
            $folders[$moshaf['server']][] = $reciter->slug;

            $existing = Recitation::query()
                ->where('reciter_id', $reciter->id)
                ->where('format', 'mp3')
                ->pluck('audio_url', 'surah_id')
                ->all();

            $writes = [];
            $overwrites = 0;
            $untouched = 0;

            foreach (Mp3QuranCatalogue::audioUrls($moshaf) as $surahId => $url) {
                $current = $existing[$surahId] ?? null;

                if ($current === $url) {
                    continue;
                }

                if ($current !== null && $current !== '') {
                    if ($keep) {
                        $untouched++;

                        continue;
                    }

                    $overwrites++;

                    if (count($examples[$reciter->slug] ?? []) < 2) {
                        $examples[$reciter->slug][] = "surah {$surahId}: {$current}  →  {$url}";
                    }
                }

                $writes[$surahId] = $url;
            }

            $kept += $untouched;
            $newRows = count($writes) - $overwrites;

            $mappingStale = $reciter->mp3quran_read_id !== $readId
                || $reciter->mp3quran_folder !== $moshaf['server'];

            if ($writes === [] && ! $mappingStale) {
                $summary[] = [$reciter->slug, $moshaf['name'], (string) ($readId ?? '—'), 'up to date', '0', '0'];

                continue;
            }

            // Only spend HEAD requests when we are about to trust this folder.
            $badUrl = $this->probe($moshaf);

            if ($badUrl !== null) {
                $this->warn("Skipped {$reciter->slug}: {$badUrl} is not reachable — nothing written, existing audio left alone.");
                unset($examples[$reciter->slug]);

                continue;
            }

            $created += $newRows;
            $replaced += $overwrites;

            $summary[] = [
                $reciter->slug,
                $moshaf['name'].($note !== '' ? " ({$note})" : ''),
                (string) ($readId ?? '—'),
                match (true) {
                    $writes === [] => 'mapping only',
                    $dryRun => 'would write',
                    default => 'written',
                },
                (string) $newRows,
                $overwrites.($untouched > 0 ? " ({$untouched} kept)" : ''),
            ];

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($reciter, $moshaf, $readId, $writes) {
                $reciter->forceFill([
                    'mp3quran_read_id' => $readId,
                    'mp3quran_folder' => $moshaf['server'],
                ])->save();

                foreach ($writes as $surahId => $url) {
                    Recitation::updateOrCreate(
                        ['reciter_id' => $reciter->id, 'surah_id' => $surahId, 'format' => 'mp3'],
                        ['audio_url' => $url],
                    );
                }
            });
        }

        $imported = [];
        $importedUrls = 0;

        if ($importNew && $only !== []) {
            $this->warn('--only limits the run to reciters we already have, so --import-new was skipped.');
        } elseif ($importNew) {
            [$imported, $importedUrls] = $this->importNew($catalogue, $preserved, $dryRun, $inactive);
        }

        $this->report($summary, $examples, $folders, $ambiguous, $unmatched, $imported, $importedUrls, $created, $replaced, $kept, $dryRun, $keep);

        if (! $dryRun && ($created > 0 || $replaced > 0 || $importedUrls > 0)) {
            QuranCacheKeys::flushReciters();
        }

        return self::SUCCESS;
    }

    /**
     * Delete the reciters this command imported, keeping a snapshot of
     * everything mp3quran cannot tell us again.
     *
     * Scope is deliberately "has an mp3quran folder" and nothing wider. The
     * rest of the table — recited translations, qaris that only exist in the
     * everyayah catalogue — could not be rebuilt from this API, so a rebuild
     * that removed them would be a one-way loss.
     *
     * @return array<string, list<array<string, mixed>>> folder => preserved columns,
     *                                                   a list because two of our
     *                                                   rows can share one recording
     */
    private function freshen(Mp3QuranCatalogue $catalogue, bool $dryRun): array
    {
        // Only delete a reciter whose recording is still in the catalogue and
        // still answering. Anything else could not be rebuilt, and a rebuild
        // that loses rows is worse than one that leaves them alone.
        $targets = Reciter::query()
            ->whereNotNull('mp3quran_folder')
            ->with('translations')
            ->get();

        $stranded = $targets->filter(function (Reciter $reciter) use ($catalogue) {
            $moshaf = collect($catalogue->moshafs())->firstWhere('server', $reciter->mp3quran_folder);

            return $moshaf === null || $this->probe($moshaf) !== null;
        });

        if ($stranded->isNotEmpty()) {
            $this->warn($stranded->count().' mapped reciters are not rebuildable right now (recording gone from the catalogue or unreachable) and are being left as they are: '.$stranded->pluck('slug')->implode(', '));
            $targets = $targets->reject(fn (Reciter $reciter) => $stranded->contains($reciter));
        }

        if ($targets->isEmpty()) {
            $this->line('Nothing to rebuild — no reciter is mapped to mp3quran yet.');

            return [];
        }

        $ids = $targets->pluck('id')->all();
        $preserved = [];

        foreach ($targets as $reciter) {
            $preserved[$reciter->mp3quran_folder][] = Mp3QuranReciterImporter::snapshot($reciter);
        }

        $recitations = Recitation::query()->whereIn('reciter_id', $ids)->count();
        $translations = ReciterTranslation::query()->whereIn('reciter_id', $ids)->count();
        $untouched = Reciter::query()->whereNull('mp3quran_folder')->count();

        $this->warn(sprintf(
            '%s %d reciters, %d recitation rows and %d translations.',
            $dryRun ? 'Would delete' : 'Deleting',
            count($ids),
            $recitations,
            $translations,
        ));
        $this->line('Kept and re-applied by folder: slug, portrait, country, featured flag, display order, follower count, per-verse (everyayah) folder and translations.');
        $this->line('Lost: anything else an admin edited by hand on those rows.');
        $this->line("{$untouched} reciters are not from mp3quran and are left alone — this command could not rebuild them.");

        if (! $dryRun) {
            // Children first: both FKs cascade today, but an ordered delete
            // does not depend on that staying true.
            DB::transaction(function () use ($ids) {
                Recitation::query()->whereIn('reciter_id', $ids)->delete();
                ReciterTranslation::query()->whereIn('reciter_id', $ids)->delete();
                Reciter::query()->whereIn('id', $ids)->delete();
            });

            QuranCacheKeys::flushReciters();
        }

        return $preserved;
    }

    /**
     * Add every recording in the catalogue we do not already have, one reciter
     * row per recording.
     *
     * Prints a line per reciter rather than a table: this walks ~250 entries,
     * and "[12/250]" is what drives the progress bar in the admin panel.
     *
     * @param  array<string, list<array<string, mixed>>>  $preserved
     * @param  bool  $inactive  import hidden from the public site
     * @return array{0: array<string, int>, 1: int}
     */
    private function importNew(Mp3QuranCatalogue $catalogue, array $preserved, bool $dryRun, bool $inactive): array
    {
        $known = Reciter::query()->whereNotNull('mp3quran_folder')->pluck('mp3quran_folder')->flip()->all();

        // In a --fresh dry run the rows are still there; treat them as gone so
        // the preview matches what a real run would do.
        $known = array_diff_key($known, $preserved);

        $importer = new Mp3QuranReciterImporter($catalogue);
        $pending = array_filter(
            $catalogue->moshafs(),
            static fn (array $moshaf) => ! isset($known[$moshaf['server']]),
        );

        $counts = ['created' => 0, 'restored' => 0, 'skipped' => 0];
        $urls = 0;
        $total = count($pending);
        $seen = 0;

        foreach ($pending as $moshaf) {
            $seen++;
            $badUrl = $this->probe($moshaf);

            if ($badUrl !== null) {
                $counts['skipped']++;
                $this->warn("[{$seen}/{$total}] skipped {$moshaf['reciter']} — {$badUrl} is not reachable.");

                continue;
            }

            // Usually one row per recording, but a rebuild puts back however
            // many of ours pointed at this folder.
            foreach ($preserved[$moshaf['server']] ?? [null] as $kept) {
                $preview = $importer->preview($moshaf, $kept);

                $counts[$preview['restored'] ? 'restored' : 'created']++;
                $urls += count($moshaf['surahs']);

                $this->line(sprintf(
                    '[%d/%d] %s %s — %s (%d surahs)',
                    $seen,
                    $total,
                    $dryRun ? 'would add' : 'added',
                    $preview['slug'],
                    $preview['name_ar'],
                    count($moshaf['surahs']),
                ));

                if (! $dryRun) {
                    DB::transaction(fn () => $importer->import($moshaf, $preview, $kept, ! $inactive));
                }
            }
        }

        return [$counts, $urls];
    }

    /**
     * Decide which mp3quran recording, if any, is this reciter's. Anything
     * inconclusive is pushed onto the report lists and returns null.
     *
     * @param  array<int, int>  $manual
     * @param  list<array{reciter: string, lines: list<string>}>  $ambiguous
     * @param  list<string>  $unmatched
     * @return array{0: array<string, mixed>|null, 1: string}
     */
    private function resolve(
        Reciter $reciter,
        string $name,
        Mp3QuranCatalogue $catalogue,
        array $manual,
        array &$ambiguous,
        array &$unmatched,
    ): array {
        if (isset($manual[$reciter->id])) {
            $moshaf = $catalogue->moshaf($manual[$reciter->id]);

            if ($moshaf === null) {
                $unmatched[] = "#{$reciter->id} {$reciter->slug} (--map read id {$manual[$reciter->id]} is not in the catalogue)";

                return [null, ''];
            }

            return [$moshaf, 'manual'];
        }

        if (ReciterNameMatcher::normalize($name) === '') {
            // Recited translations and latin-only names are not in this
            // catalogue; forcing them onto a qari is the failure mode here.
            $unmatched[] = "#{$reciter->id} {$reciter->slug} (no Arabic name — not an mp3quran reciter)";

            return [null, ''];
        }

        $ranked = ReciterNameMatcher::rank($name, $catalogue->reciters());
        $best = $ranked[0] ?? null;

        if ($best === null || $best['score'] < ReciterNameMatcher::REVIEW_THRESHOLD) {
            $unmatched[] = "#{$reciter->id} {$reciter->slug} — {$name}";

            return [null, ''];
        }

        if ($best['score'] < ReciterNameMatcher::AUTO_THRESHOLD) {
            $ambiguous[] = [
                'reciter' => "#{$reciter->id} {$name}",
                'lines' => $this->resolutionHints($reciter->id, $ranked, $catalogue),
            ];

            return [null, ''];
        }

        [$moshaf, $note] = $this->pickMoshaf($reciter, $name, $catalogue->moshafsFor($best['id']), $catalogue);

        if ($moshaf === null) {
            $unmatched[] = "#{$reciter->id} {$reciter->slug} — matched {$best['name']} but {$note}";
        }

        return [$moshaf, $note];
    }

    /**
     * Choose which of a reciter's recordings is the one we already have a row
     * for. A style we hold and mp3quran does not is skipped rather than
     * substituted: a mujawwad reciter must not be given a murattal recording.
     *
     * @param  list<array<string, mixed>>  $moshafs
     * @return array{0: array<string, mixed>|null, 1: string}
     */
    private function pickMoshaf(Reciter $reciter, string $name, array $moshafs, Mp3QuranCatalogue $catalogue): array
    {
        if ($moshafs === []) {
            return [null, 'the catalogue lists no audio for them'];
        }

        $hint = $name.' '.$reciter->slug.' '.($reciter->riwayah ?? '');

        [$wanted, $style] = match (true) {
            (bool) preg_match('/مجود|mujawwad/iu', $hint) => [self::MUJAWWAD, 'mujawwad'],
            (bool) preg_match('/معلم|muallim|mu_allim/iu', $hint) => [self::MUALLIM, 'muallim'],
            (bool) preg_match('/ورش|warsh/iu', $hint) => [self::WARSH, 'warsh'],
            default => [[1], 'murattal'],
        };

        $matching = array_values(array_filter(
            $moshafs,
            static fn (array $moshaf) => in_array($moshaf['rewaya_id'], $wanted, true),
        ));

        if ($matching === []) {
            return [null, "has no {$style} recording there"];
        }

        // Prefer the recording with timing data, then the most complete one, so
        // the choice is stable across runs.
        usort($matching, function (array $a, array $b) use ($catalogue) {
            $timedA = $catalogue->readIdFor($a['server']) === null ? 0 : 1;
            $timedB = $catalogue->readIdFor($b['server']) === null ? 0 : 1;

            return [$timedB, count($b['surahs']), -$b['id']] <=> [$timedA, count($a['surahs']), -$a['id']];
        });

        return [$matching[0], $style];
    }

    /**
     * HEAD the first and last file of a folder. The catalogue lists servers that
     * have been retired, and an unreachable folder must not replace a reciter's
     * working audio with 114 dead URLs.
     *
     * @param  array<string, mixed>  $moshaf
     * @return string|null the first URL that failed, or null when both are live
     */
    private function probe(array $moshaf): ?string
    {
        if (array_key_exists($moshaf['server'], $this->probed)) {
            return $this->probed[$moshaf['server']];
        }

        return $this->probed[$moshaf['server']] = $this->head($moshaf);
    }

    /**
     * @param  array<string, mixed>  $moshaf
     */
    private function head(array $moshaf): ?string
    {
        $urls = Mp3QuranCatalogue::audioUrls($moshaf);

        foreach ([array_key_first($urls), array_key_last($urls)] as $surahId) {
            $url = $urls[$surahId];

            try {
                if (! Http::timeout(15)->retry(2, 500, throw: false)->head($url)->successful()) {
                    return $url;
                }
            } catch (Throwable $e) {
                report($e);

                return $url;
            }
        }

        return null;
    }

    /**
     * Turn a shortlist into copy-pasteable --map arguments, one per recording,
     * so resolving an ambiguous reciter never means reading the API by hand.
     *
     * @param  list<array{id: int, name: string, score: float}>  $ranked
     * @return list<string>
     */
    private function resolutionHints(int $reciterId, array $ranked, Mp3QuranCatalogue $catalogue): array
    {
        $lines = [];

        foreach ($ranked as $candidate) {
            $lines[] = sprintf('  %.2f  %s', $candidate['score'], $candidate['name']);

            foreach ($catalogue->moshafsFor($candidate['id']) as $moshaf) {
                $lines[] = sprintf(
                    '          --map=%d:%d   %s (%d surahs%s)',
                    $reciterId,
                    $moshaf['id'],
                    $moshaf['name'],
                    count($moshaf['surahs']),
                    $catalogue->readIdFor($moshaf['server']) === null ? '' : ', timed',
                );
            }
        }

        return $lines;
    }

    /**
     * @return array<int, int> our reciter id => mp3quran read id
     */
    private function manualMap(): array
    {
        $map = [];

        // Repeatable on the CLI (--map=a --map=b); the admin panel posts one
        // comma-separated field, which Artisan hands over as the same array.
        foreach ((array) $this->option('map') as $pair) {
            [$reciterId, $readId] = array_pad(explode(':', trim((string) $pair), 2), 2, null);

            if (! ctype_digit((string) $reciterId) || ! ctype_digit((string) $readId)) {
                $this->warn("Ignoring --map={$pair} — expected reciterId:readId.");

                continue;
            }

            $map[(int) $reciterId] = (int) $readId;
        }

        return $map;
    }

    /**
     * @param  list<array<int, string>>  $summary
     * @param  array<string, list<string>>  $examples
     * @param  array<string, list<string>>  $folders
     * @param  list<array{reciter: string, lines: list<string>}>  $ambiguous
     * @param  list<string>  $unmatched
     * @param  array<string, int>  $imported
     */
    private function report(
        array $summary,
        array $examples,
        array $folders,
        array $ambiguous,
        array $unmatched,
        array $imported,
        int $importedUrls,
        int $created,
        int $replaced,
        int $kept,
        bool $dryRun,
        bool $keep,
    ): void {
        if ($summary !== []) {
            $this->table(['Reciter', 'mp3quran recording', 'Read', 'Status', 'New', 'Replaced'], $summary);
        }

        if ($examples !== []) {
            $this->newLine();
            $this->line($dryRun
                ? 'Sample of the URLs that would be replaced:'
                : 'Sample of the URLs that were replaced:');

            foreach ($examples as $slug => $pairs) {
                $this->line("  {$slug}");

                foreach ($pairs as $pair) {
                    $this->line("      {$pair}");
                }
            }
        }

        if ($ambiguous !== []) {
            $this->newLine();
            $this->warn(count($ambiguous).' reciters need a human — nothing was written for them.');
            $this->line('Shortlists are ordered by shared words, not by score:');

            foreach ($ambiguous as $row) {
                $this->line("  {$row['reciter']}");

                foreach ($row['lines'] as $line) {
                    $this->line("    {$line}");
                }
            }
        }

        if ($unmatched !== []) {
            $this->newLine();
            $this->warn(count($unmatched).' reciters had no match on mp3quran — their existing audio is untouched:');

            foreach ($unmatched as $line) {
                $this->line("  {$line}");
            }
        }

        $shared = array_filter($folders, static fn (array $slugs) => count($slugs) > 1);

        if ($shared !== []) {
            $this->newLine();
            $this->warn('These folders are shared by more than one of our reciters — check they are really the same recording:');

            foreach ($shared as $folder => $slugs) {
                $this->line('  '.$folder.' — '.implode(', ', $slugs));
            }
        }

        $this->newLine();
        $matched = count($summary);
        $verb = $dryRun ? 'Would map' : 'Mapped';
        $this->info("{$verb} {$matched} reciters — {$created} audio URLs created, {$replaced} replaced.");

        if ($imported !== []) {
            $this->info(sprintf(
                '%s %d new reciters with %d audio URLs (%d rebuilt from the snapshot, %d skipped for unreachable audio).',
                $dryRun ? 'Would add' : 'Added',
                $imported['created'],
                $importedUrls,
                $imported['restored'],
                $imported['skipped'],
            ));

            if ($imported['created'] > 0) {
                $this->info($inactive
                    ? "{$imported['created']} reciters imported hidden — activate them in the admin panel to publish."
                    : "{$imported['created']} reciters imported and visible on the public site: they are in /quran/reciters and in the surah player's picker.");
            }

            if ($imported['created'] > 0 && ! $dryRun) {
                // mp3quran publishes neither, and nothing else here can fill
                // them in — saying the catalogue is complete would be a lie.
                $this->line('New reciters have no portrait or country yet — run quran:enrich-reciters to fill those from Wikidata.');
            }
        }

        if ($keep) {
            $this->line("{$kept} surahs already had audio and were kept (--keep-existing).");
        }

        if ($dryRun) {
            $this->comment('Dry run: nothing was written.');
        }
    }
}
