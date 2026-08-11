<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\ReciterTranslation;
use App\Domain\Quran\Services\QuranCacheKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports the everyayah.com reciter catalogue into quran_reciters.
 *
 * everyayah is the per-verse audio source: each entry is a folder of
 * {SSS}{AAA}.mp3 files. Whole-surah audio is a separate source (see
 * quran:import-recitations) and covers only a handful of these reciters —
 * everyone else plays a surah by chaining their per-verse files.
 *
 * The catalogue lists the same reciter once per bitrate; only the highest is
 * imported. Reciter ids are the catalogue's own keys, so a row is traceable
 * back to its source entry and re-running updates in place.
 *
 * Examples:
 *   php artisan quran:import-reciters
 *   php artisan quran:import-reciters --fresh   # wipe reciters + recitations first
 */
class ImportQuranRecitersCommand extends Command
{
    protected $signature = 'quran:import-reciters
        {--fresh : Truncate reciters, their translations and quran_recitations before importing}';

    protected $description = 'Import the everyayah.com reciter catalogue into quran_reciters';

    private const CATALOGUE = 'https://everyayah.com/data/recitations.js';

    /**
     * Arabic names, keyed by everyayah catalogue id.
     *
     * Deliberately partial. Entries with no Arabic name here fall back to the
     * English name with is_ready = false on the Arabic translation row, which
     * flags them for a human translator — an invented Arabic spelling for a
     * reciter would be worse than an obviously untranslated one.
     *
     * @var array<int, string>
     */
    private const NAMES_AR = [
        2 => 'عبد الباسط عبد الصمد (مرتل)',
        3 => 'عبد الباسط عبد الصمد (مجود)',
        6 => 'عبد الله بصفر',
        8 => 'عبد الرحمن السديس',
        9 => 'عبد الباسط عبد الصمد',
        11 => 'أبو بكر الشاطري',
        12 => 'أحمد بن علي العجمي',
        13 => 'أحمد بن علي العجمي',
        15 => 'مشاري بن راشد العفاسي',
        16 => 'سعد الغامدي',
        18 => 'هاني الرفاعي',
        20 => 'محمود خليل الحصري',
        22 => 'محمود خليل الحصري (مجود)',
        25 => 'علي بن عبد الرحمن الحذيفي',
        27 => 'إبراهيم الأخضر',
        29 => 'ماهر المعيقلي',
        31 => 'محمد صديق المنشاوي',
        33 => 'محمد صديق المنشاوي (مجود)',
        34 => 'محمد صديق المنشاوي (مرتل)',
        36 => 'محمد محمود الطبلاوي',
        37 => 'محمد أيوب',
        41 => 'محمد جبريل',
        42 => 'مصطفى إسماعيل',
        44 => 'سعود الشريم',
        51 => 'صلاح عبد الرحمن بوخاطر',
        52 => 'عبد المحسن القاسم',
        53 => 'عبد الله عواد الجهني',
        54 => 'صلاح البدير',
        55 => 'عبد الله المطرود',
        56 => 'أحمد نعينع',
        57 => 'محمد عبد الكريم',
        58 => 'خليفة الطنيجي',
        59 => 'محمود علي البنا',
        60 => 'إبراهيم الدوسري (ورش)',
        61 => 'ياسين الجزائري (ورش)',
        62 => 'عبد الباسط عبد الصمد (ورش)',
        65 => 'محمود خليل الحصري (معلم)',
        66 => 'خالد عبد الله القحطاني',
        67 => 'ياسر الدوسري',
        68 => 'ناصر القطامي',
        69 => 'علي حجاج السويسي',
        70 => 'سهل ياسين',
        71 => 'أحمد بن علي العجمي',
        74 => 'ياسر سلامة',
        75 => 'أكرم العلاقمي',
        76 => 'علي جابر',
        77 => 'فارس عباد',
        79 => 'أيمن سويد',
    ];

    /** Catalogue ids surfaced first in the picker, in this order. */
    private const FEATURED = [15, 20, 8, 44, 16, 2, 34, 29, 67, 68];

    /**
     * Catalogue entries that duplicate another entry — the same person and
     * style published under a different host or bitrate (verified by
     * comparing everyayah subfolders and Wikidata identities). Skipped on
     * import, and deactivated if a previous run already created them.
     *
     * 12, 13 → 71 (al-Ajamy via QuranExplorer / ketaballah mirrors)
     * 31     → 34 (Menshawi 32kbps duplicate of Minshawy murattal)
     */
    private const DUPLICATE_IDS = [12, 13, 31];

    public function handle(): int
    {
        try {
            $response = Http::timeout(30)->retry(3, 800)->get(self::CATALOGUE);
        } catch (Throwable $e) {
            $this->error('Could not fetch the everyayah catalogue: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $response->ok()) {
            $this->error('Could not fetch the everyayah catalogue: HTTP '.$response->status());

            return self::FAILURE;
        }

        $entries = $this->pickHighestBitrate($response->json() ?? []);

        if ($entries === []) {
            $this->error('Catalogue parsed but contained no reciters — aborting rather than wiping.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->truncate();
        }

        $counts = ['recitation' => 0, 'translation' => 0];

        foreach ($entries as $id => $entry) {
            if (in_array($id, self::DUPLICATE_IDS, true)) {
                Reciter::whereKey($id)->update(['is_active' => false]);

                continue;
            }

            $subfolder = (string) $entry['subfolder'];
            $kind = $this->classify($subfolder);
            $name = $this->cleanName((string) $entry['name']);

            // The catalogue id is the primary key, so it is assigned directly —
            // updateOrCreate would drop it, since a primary key is not fillable.
            $reciter = Reciter::find($id) ?? new Reciter;
            $reciter->id = $id;
            $reciter->fill([
                'slug' => Str::slug($name) ?: 'reciter-'.$id,
                'kind' => $kind,
                'style' => $this->style($entry['name'].' '.$subfolder),
                'riwayah' => $this->riwayah($subfolder, $kind),
                'everyayah_folder' => $subfolder,
                'is_active' => true,
                'is_featured' => in_array($id, self::FEATURED, true),
                'display_order' => ($pos = array_search($id, self::FEATURED, true)) === false ? 100 + $id : $pos + 1,
                'followers_count' => 0,
            ])->save();

            $nameAr = self::NAMES_AR[$id] ?? null;

            ReciterTranslation::updateOrCreate(
                ['reciter_id' => $id, 'locale' => 'ar'],
                ['name' => $nameAr ?? $name, 'is_ready' => $nameAr !== null],
            );
            ReciterTranslation::updateOrCreate(
                ['reciter_id' => $id, 'locale' => 'en'],
                ['name' => $name, 'is_ready' => true],
            );

            $counts[$kind]++;
        }

        // Reciter lists and the surah audio map are cached on the reader.
        QuranCacheKeys::flushReciters();

        $total = array_sum($counts);
        $this->info("Imported {$total} reciters — {$counts['recitation']} recitations, {$counts['translation']} translations.");

        $missing = $total - count(array_intersect(array_keys($entries), array_keys(self::NAMES_AR)));
        if ($missing > 0) {
            $this->warn("{$missing} have no Arabic name yet (reciter_translations.is_ready = false for locale ar).");
        }

        return self::SUCCESS;
    }

    /**
     * The catalogue lists a reciter once per available bitrate; keep the best.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array{name: string, subfolder: string, bitrate: string}>
     */
    private function pickHighestBitrate(array $payload): array
    {
        $best = [];

        foreach ($payload as $key => $entry) {
            if ($key === 'ayahCount' || ! is_array($entry) || ! isset($entry['name'], $entry['subfolder'])) {
                continue;
            }

            $name = (string) $entry['name'];
            // The bitrate field occasionally disagrees with the folder name; it is
            // only ever used to rank variants of the same reciter, so that is fine.
            $rate = (int) preg_replace('/\D.*$/', '', (string) ($entry['bitrate'] ?? '0'));

            if (! isset($best[$name]) || $rate > $best[$name]['rate']) {
                $best[$name] = ['id' => (int) $key, 'rate' => $rate, 'entry' => $entry];
            }
        }

        $out = [];
        foreach ($best as $pick) {
            $out[$pick['id']] = $pick['entry'];
        }
        ksort($out);

        return $out;
    }

    /**
     * Classify from the subfolder, never the name: "Balayev" reads like a qari
     * but lives under translations/azerbaijani/, while "Aziz Alili" carries no
     * marker yet is a genuine Arabic recitation.
     */
    private function classify(string $subfolder): string
    {
        foreach (['translations/', 'English/', 'MultiLanguage/'] as $prefix) {
            if (str_starts_with($subfolder, $prefix)) {
                return 'translation';
            }
        }

        return 'recitation';
    }

    private function riwayah(string $subfolder, string $kind): ?string
    {
        if ($kind === 'translation') {
            return null;
        }

        return str_starts_with($subfolder, 'warsh/') ? "Warsh 'an Nafi'" : "Hafs 'an 'Asim";
    }

    private function style(string $haystack): ?string
    {
        foreach (['mujawwad', 'muallim', 'murattal'] as $style) {
            if (str_contains(mb_strtolower($haystack), $style)) {
                return $style;
            }
        }

        return null;
    }

    /**
     * Catalogue names are inconsistent: some carry their own bitrate
     * ("Parhizgar_64Kbps"), some use underscores ("Yasser_Ad-Dussary").
     */
    private function cleanName(string $name): string
    {
        $name = preg_replace('/[_\s]*\d+\s*kbps$/i', '', $name) ?? $name;
        $name = str_replace('_', ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    private function truncate(): void
    {
        $this->warn('Wiping quran_recitations, reciter_translations and quran_reciters…');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('quran_recitations')->truncate();
        DB::table('reciter_translations')->truncate();
        DB::table('quran_reciters')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
