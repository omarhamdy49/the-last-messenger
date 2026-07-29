<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Makes stored hadith text identical to Sunnah.com.
 *
 * Two modes:
 *   --from-report  replays the text_mismatch findings the parity audit wrote to
 *                  storage/app/hadith_sunnah_parity/issues.json (fast, targeted)
 *   --collection=  sweeps a whole collection, comparing every hadith it can
 *                  address on Sunnah.com
 *
 * Sunnah.com is treated as authoritative for the text, which is a decision
 * about sourcing, not a scholarly claim: the point is that the site and this
 * database stop disagreeing. Every overwrite is recorded so a change can be
 * traced back to the exact URL it came from.
 *
 * Dry-run by default; pass --apply to write.
 */
class SyncHadithTextFromSunnahCommand extends Command
{
    protected $signature = 'hadith:sync-text
        {--from-report : take the targets from the parity audit report}
        {--collection= : sweep one collection instead (collection code)}
        {--apply : write the corrected text}
        {--delay=400 : milliseconds between API calls}
        {--show=10 : how many changes to print}';

    protected $description = 'Overwrite stored hadith text with Sunnah.com\'s, so the two are identical';

    private const BASE = 'https://api.sunnah.com/v1/';

    private const REPORT = 'hadith_sunnah_parity/issues.json';

    public function handle(): int
    {
        $apiKey = (string) config('sunnah.api.key');
        if ($apiKey === '') {
            $this->error('SUNNAH_API_KEY is not configured.');

            return self::FAILURE;
        }

        $targets = $this->option('from-report')
            ? $this->targetsFromReport()
            : $this->targetsFromCollection((string) $this->option('collection'));

        if ($targets === []) {
            $this->warn('Nothing to do.');

            return self::SUCCESS;
        }

        $this->info('Checking '.number_format(count($targets)).' hadith(s) against Sunnah.com...');

        $apply = (bool) $this->option('apply');
        $delay = max(0, (int) $this->option('delay')) * 1000;

        $stats = ['checked' => 0, 'identical' => 0, 'updated' => 0, 'not_found' => 0, 'failed' => 0];
        $samples = [];
        $bar = $this->output->createProgressBar(count($targets));

        foreach ($targets as $target) {
            $bar->advance();
            $stats['checked']++;

            try {
                $remote = $this->fetch($apiKey, $target['collection'], $target['number']);
            } catch (Throwable $e) {
                $stats['failed']++;

                continue;
            }

            if ($remote === null) {
                $stats['not_found']++;

                continue;
            }

            $changes = $this->diff($target['hadith_id'], $remote);

            if ($changes === []) {
                $stats['identical']++;

                continue;
            }

            $stats['updated']++;
            if (count($samples) < (int) $this->option('show')) {
                $samples[] = sprintf(
                    'h#%-6s %s:%s  %s',
                    $target['hadith_id'], $target['collection'], $target['number'],
                    implode(', ', array_keys($changes)),
                );
            }

            if ($apply) {
                $this->write($target['hadith_id'], $changes, $target['collection'], $target['number']);
            }

            usleep($delay);
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['metric', 'count'],
            array_map(fn (string $k) => [$k, number_format($stats[$k])], array_keys($stats)),
        );

        foreach ($samples as $sample) {
            $this->line('   '.$sample);
        }

        if (! $apply) {
            $this->newLine();
            $this->warn('Dry run — nothing written. Re-run with --apply to overwrite.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{hadith_id:int, collection:string, number:string}>
     */
    private function targetsFromReport(): array
    {
        $path = storage_path('app/'.self::REPORT);
        if (! is_file($path)) {
            $this->error("No parity report at {$path} — run scripts/audit_sunnah_api_parity.php first.");

            return [];
        }

        $report = json_decode((string) file_get_contents($path), true);
        $rows = is_array($report) && ! array_is_list($report)
            ? array_merge(...array_map(fn ($v) => is_array($v) ? $v : [$v], array_values($report)))
            : (array) $report;

        $targets = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ($row['type'] ?? null) !== 'text_mismatch') {
                continue;
            }

            $collection = $this->collectionFromSourceFile((string) ($row['source_file'] ?? ''));
            $hadithId = (int) ($row['hadith_id'] ?? 0);
            $number = (string) ($row['hadith_number'] ?? '');

            if ($collection === null || $hadithId === 0 || $number === '') {
                continue;
            }

            $targets[$hadithId] = ['hadith_id' => $hadithId, 'collection' => $collection, 'number' => $number];
        }

        return array_values($targets);
    }

    /**
     * @return list<array{hadith_id:int, collection:string, number:string}>
     */
    private function targetsFromCollection(string $collectionCode): array
    {
        if ($collectionCode === '') {
            $this->error('Pass --collection=<collection-code> or --from-report.');

            return [];
        }

        return DB::table('hadith_hadiths as h')
            ->join('hadith_chapters as ch', 'ch.id', '=', 'h.chapter_id')
            ->join('hadith_books as b', 'b.id', '=', 'ch.book_id')
            ->join('hadith_collections as c', 'c.id', '=', 'b.collection_id')
            ->where('c.code', $collectionCode)
            ->whereNotNull('h.hadith_number')
            ->select('h.id', 'h.hadith_number')
            ->orderBy('h.id')
            ->get()
            ->map(fn ($row) => [
                'hadith_id' => (int) $row->id,
                'collection' => $this->apiSlug($collectionCode),
                'number' => (string) $row->hadith_number,
            ])
            ->all();
    }

    private function collectionFromSourceFile(string $sourceFile): ?string
    {
        $slug = strtok($sourceFile, '/');

        return $slug === false || $slug === '' ? null : $this->apiSlug($slug);
    }

    private function apiSlug(string $slug): string
    {
        return match ($slug) {
            'ahmed', 'musnad-ahmad-ibn-hanbal' => 'ahmad',
            'aladab_almufrad', 'adab' => 'adab',
            'bulugh_almaram' => 'bulugh',
            'mishkat_almasabih' => 'mishkat',
            'riyad_assalihin', 'riyadussalihin' => 'riyadussalihin',
            'shamail_muhammadiyah' => 'shamail',
            default => $slug,
        };
    }

    /**
     * @return array{ar:string, en:string}|null
     */
    private function fetch(string $apiKey, string $collection, string $number): ?array
    {
        $response = Http::withHeaders(['X-API-Key' => $apiKey])
            ->timeout(30)
            ->retry(3, 1500)
            ->get(self::BASE."hadiths/{$collection}/{$number}");

        if ($response->status() === 404) {
            return null;
        }
        if (! $response->ok()) {
            throw new \RuntimeException('HTTP '.$response->status());
        }

        $payload = $response->json();
        $text = ['ar' => '', 'en' => ''];

        foreach (($payload['hadith'] ?? []) as $entry) {
            $language = strtolower((string) ($entry['lang'] ?? ''));
            if (isset($text[$language]) && $text[$language] === '') {
                $text[$language] = $this->plainText((string) ($entry['body'] ?? ''));
            }
        }

        return ($text['ar'] === '' && $text['en'] === '') ? null : $text;
    }

    /**
     * Sunnah.com serves the body as HTML; the database stores plain text.
     */
    private function plainText(string $html): string
    {
        $text = preg_replace('/<[^>]+>/u', ' ', $html) ?? $html;
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /**
     * @param  array{ar:string, en:string}  $remote
     * @return array<string, string> language => corrected text
     */
    private function diff(int $hadithId, array $remote): array
    {
        $stored = DB::table('hadith_translations')
            ->where('hadith_id', $hadithId)
            ->whereIn('locale', ['ar', 'en'])
            ->pluck('text', 'locale');

        $changes = [];
        foreach ($remote as $language => $text) {
            if ($text === '') {
                continue;
            }
            $current = trim(preg_replace('/\s+/u', ' ', (string) $stored->get($language)) ?? '');
            if ($current !== $text) {
                $changes[$language] = $text;
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, string>  $changes
     */
    private function write(int $hadithId, array $changes, string $collection, string $number): void
    {
        DB::transaction(function () use ($hadithId, $changes, $collection, $number) {
            foreach ($changes as $language => $text) {
                $exists = DB::table('hadith_translations')
                    ->where('hadith_id', $hadithId)
                    ->where('locale', $language)
                    ->exists();

                $attributes = [
                    'text' => $text,
                    'source_url' => "https://sunnah.com/{$collection}:{$number}",
                    'updated_at' => now(),
                ];

                $exists
                    ? DB::table('hadith_translations')
                        ->where('hadith_id', $hadithId)->where('locale', $language)
                        ->update($attributes)
                    : DB::table('hadith_translations')->insert($attributes + [
                        'hadith_id' => $hadithId,
                        'locale' => $language,
                        'created_at' => now(),
                    ]);
            }

            if (isset($changes['ar'])) {
                DB::table('hadith_hadiths')->where('id', $hadithId)->update([
                    'text_hash' => hash('sha256', trim($changes['ar'])),
                    'updated_at' => now(),
                ]);
            }

            DB::table('hadith_sources')->upsert([[
                'hadith_id' => $hadithId,
                'source_system' => 'sunnah',
                'source_type' => 'website',
                'external_id' => "{$collection}:{$number}",
                'url' => "https://sunnah.com/{$collection}:{$number}",
                'verification_method' => 'sunnah_api_text_sync',
                'verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['source_system', 'external_id'], [
                'hadith_id',
                'url',
                'verification_method',
                'verified_at',
                'updated_at',
            ]);
        });
    }
}
