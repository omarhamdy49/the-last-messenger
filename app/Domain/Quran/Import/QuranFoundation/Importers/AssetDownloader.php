<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Importers;

use App\Domain\Quran\DTOs\ImportReport;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Local copies of the assets QF snapshots do not contain: verse images (public disk)
 * and mushaf fonts (public/fonts/quran). Existing files are skipped unless forced;
 * every failed URL is itemised.
 */
final class AssetDownloader
{
    public const FONT_VERSIONS = ['v1', 'v2', 'hafs', 'indopak'];

    private const PAGES = 604;

    /** Single-file Unicode fonts, relative to quran.qf.fonts_base. */
    private const SINGLE_FONTS = [
        'hafs' => ['uthmanic_hafs/UthmanicHafs1Ver18.woff2' => 'hafs/UthmanicHafs1Ver18.woff2'],
        'indopak' => ['nastaleeq/indopak/indopak-nastaleeq-waqf-lazim-v4.2.1.woff2' => 'indopak/indopak-nastaleeq-waqf-lazim-v4.2.1.woff2'],
    ];

    public function images(bool $force = false): ImportReport
    {
        $report = new ImportReport('assets:images');
        $disk = Storage::disk('public');
        $dir = trim((string) config('quran.assets.images_path'), '/');

        DB::table('quran_verses')->select(['id', 'chapter_id', 'verse_number', 'image_url', 'image_path'])
            ->whereNotNull('image_url')->orderBy('id')
            ->chunkById(500, function ($verses) use ($disk, $dir, $force, $report): void {
                foreach ($verses as $verse) {
                    $report->expected++;
                    $path = "{$dir}/{$verse->chapter_id}_{$verse->verse_number}.png";

                    if (! $force && $disk->exists($path)) {
                        $report->unchanged++;
                        if ($verse->image_path !== $path) {
                            DB::table('quran_verses')->where('id', $verse->id)->update(['image_path' => $path]);
                        }

                        continue;
                    }

                    $url = str_starts_with((string) $verse->image_url, '//') ? 'https:'.$verse->image_url : (string) $verse->image_url;
                    $body = $this->download($url, $report);
                    if ($body === null) {
                        continue;
                    }

                    $disk->put($path, $body);
                    DB::table('quran_verses')->where('id', $verse->id)->update(['image_path' => $path]);
                    $report->written++;
                }
            });

        return $report;
    }

    /**
     * @param  list<string>  $versions  v1|v2|hafs|indopak
     */
    public function fonts(array $versions, bool $force = false): ImportReport
    {
        $report = new ImportReport('assets:fonts');
        $base = rtrim((string) config('quran.qf.fonts_base'), '/');
        $root = public_path(trim((string) config('quran.assets.fonts_public_path'), '/'));

        foreach ($versions as $version) {
            if (! in_array($version, self::FONT_VERSIONS, true)) {
                $report->fail("font:{$version}", 'unknown font version');

                continue;
            }

            $files = isset(self::SINGLE_FONTS[$version])
                ? self::SINGLE_FONTS[$version]
                : $this->pageFonts($version);

            $failedBefore = count($report->failed);
            foreach ($files as $remote => $local) {
                $report->expected++;
                $target = "{$root}/{$local}";

                if (! $force && is_file($target)) {
                    $report->unchanged++;

                    continue;
                }

                $body = $this->download("{$base}/{$remote}", $report);
                if ($body === null) {
                    continue;
                }

                if (! is_dir(dirname($target))) {
                    mkdir(dirname($target), 0775, true);
                }
                file_put_contents($target, $body);
                $report->written++;
            }

            if (count($report->failed) === $failedBefore) {
                DB::table('quran_mushafs')->where('font_version', $version)->update(['fonts_downloaded_at' => now()]);
            }
        }

        return $report;
    }

    /**
     * @return array<string, string> remote path => local path
     */
    private function pageFonts(string $version): array
    {
        $files = [];
        for ($page = 1; $page <= self::PAGES; $page++) {
            $files["{$version}/woff2/p{$page}.woff2"] = "{$version}/woff2/p{$page}.woff2";
        }

        return $files;
    }

    private function download(string $url, ImportReport $report): ?string
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::timeout(60)->get($url);
            } catch (ConnectionException) {
                if ($attempt === 2) {
                    $report->fail($url, 'connection failed');
                }

                continue;
            }

            if ($response->successful() && $response->body() !== '') {
                return $response->body();
            }

            if ($attempt === 2 || $response->status() < 500) {
                $report->fail($url, 'HTTP '.$response->status());

                return null;
            }
        }

        return null;
    }
}
