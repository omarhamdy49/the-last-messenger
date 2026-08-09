<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\SurahInfo;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Seeds quran_surah_info with editorial name meanings + summaries (ar, en)
 * from database/data/quran/surah_info.php.
 *
 * The revelation context line (place + verse count) is composed from the
 * quran_surahs table so the info never contradicts the seeded surah data.
 *
 * Idempotent: rows are updateOrCreate'd on the (surah_id, lang) unique key.
 */
class QuranSurahInfoSeeder extends Seeder
{
    private const SOURCE = 'Editorial summary based on classical tafsir literature';

    public function run(): void
    {
        $dataPath = database_path('data/quran/surah_info.php');

        if (! is_readable($dataPath)) {
            throw new RuntimeException("Surah info data file not found: {$dataPath}");
        }

        /** @var array<int, array{meaning_en: string, summary_en: string, meaning_ar: string, summary_ar: string}> $surahInfoData */
        $surahInfoData = require $dataPath;

        if (count($surahInfoData) !== 114) {
            throw new RuntimeException('Expected info entries for 114 surahs, found '.count($surahInfoData).'.');
        }

        $surahs = Surah::query()
            ->select(['id', 'revelation_place', 'verses_count'])
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $written = 0;

        foreach ($surahInfoData as $surahNumber => $info) {
            $surah = $surahs->get($surahNumber);

            if ($surah === null) {
                throw new RuntimeException("Surah {$surahNumber} not found in quran_surahs — seed surahs first.");
            }

            SurahInfo::updateOrCreate(
                ['surah_id' => $surah->id, 'lang' => 'en'],
                [
                    'desc' => $info['meaning_en'],
                    'content' => $this->revelationContextEn($surah).' '.$info['summary_en'],
                    'source' => self::SOURCE,
                    'active' => true,
                ],
            );

            SurahInfo::updateOrCreate(
                ['surah_id' => $surah->id, 'lang' => 'ar'],
                [
                    'desc' => $info['meaning_ar'],
                    'content' => $this->revelationContextAr($surah).' '.$info['summary_ar'],
                    'source' => self::SOURCE,
                    'active' => true,
                ],
            );

            $written += 2;
        }

        $this->command?->info("Quran surah info seeded: {$written} rows (114 surahs × ar/en).");
    }

    private function revelationContextEn(Surah $surah): string
    {
        // Compared through the enum: the stored value is 'madinah', so the old
        // `=== 'Medina'` test never matched and labelled all 114 surahs Meccan.
        $place = RevelationPlace::fromExternal($surah->revelation_place)->labelEn();

        return "A {$place} surah of {$surah->verses_count} verses.";
    }

    private function revelationContextAr(Surah $surah): string
    {
        $place = RevelationPlace::fromExternal($surah->revelation_place)->label();

        return "سورة {$place}، عدد آياتها {$surah->verses_count}.";
    }
}
