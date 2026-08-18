<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Enums\LocationType as T;
use App\Domain\Shared\Models\Location;
use App\Domain\Shared\Services\LocationResolver;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * The canonical place vocabulary.
 *
 * IDEMPOTENT: keyed on `slug`, so re-running updates rather than duplicates.
 * Safe to run against a database that already has editor-curated places — it
 * touches only the slugs it owns.
 *
 * EVERY PLACE CARRIES COORDINATES. A place without them renders a name and no
 * map, which is the defect this feature exists to fix, so the self-check treats
 * a missing coordinate as a seeder bug rather than a tolerable gap.
 *
 * Coordinates are approximate centroids from public geographic data, at the
 * precision a map pin needs — not survey data.
 */
class LocationSeeder extends Seeder
{
    /**
     * slug => [type, parent slug|null, lat, lng, ar, en, [aliases…]]
     *
     * Ordered parents-before-children so the depth walk resolves in one pass.
     *
     * @return array<string, array{0: T, 1: string|null, 2: float, 3: float, 4: string, 5: string, 6: array<int, string>}>
     */
    private function places(): array
    {
        return [
            // ---------------------------------------------------------- roots
            'arabian-peninsula' => [T::REGION, null, 23.8859, 45.0792, 'الجزيرة العربية', 'The Arabian Peninsula', ['شبه الجزيرة العربية', 'جزيرة العرب', 'Arabia']],
            'sham' => [T::REGION, null, 33.5138, 36.2765, 'الشام', 'The Levant', ['بلاد الشام', 'الشأم', 'Syria', 'Greater Syria']],
            'abyssinia' => [T::COUNTRY, null, 9.1450, 40.4897, 'الحبشة', 'Abyssinia', ['أرض الحبشة', 'Abyssinia', 'Ethiopia', 'Habasha']],
            'egypt' => [T::COUNTRY, null, 26.8206, 30.8025, 'مصر', 'Egypt', ['Egypt', 'Misr']],
            'persia' => [T::COUNTRY, null, 32.4279, 53.6880, 'فارس', 'Persia', ['بلاد فارس', 'Persia', 'Iran']],
            'rome' => [T::COUNTRY, null, 41.9028, 12.4964, 'الروم', 'The Roman Empire', ['بلاد الروم', 'Rome', 'Byzantium', 'Byzantine']],
            'iraq' => [T::REGION, null, 33.2232, 43.6793, 'العراق', 'Iraq', ['Iraq']],

            // ------------------------------------------ Arabian Peninsula tier
            'hijaz' => [T::REGION, 'arabian-peninsula', 22.5000, 40.0000, 'الحجاز', 'The Hijaz', ['Hijaz', 'HIjaz', 'Hejaz', 'الحجاز']],
            'najd' => [T::REGION, 'arabian-peninsula', 24.7136, 46.6753, 'نجد', 'Najd', ['Najd', 'Nejd']],
            'yemen' => [T::REGION, 'arabian-peninsula', 15.5527, 48.5164, 'اليمن', 'Yemen', ['Yemen', 'Yeman', 'اليمن']],
            'bahrain-region' => [T::REGION, 'arabian-peninsula', 26.0667, 50.5577, 'البحرين', 'Bahrain', ['Bahrain']],
            'oman' => [T::REGION, 'arabian-peninsula', 21.5126, 55.9233, 'عُمان', 'Oman', ['Oman']],
            'sarat' => [T::REGION, 'arabian-peninsula', 19.5000, 42.0000, 'السراة', 'The Sarat', ['Sarat', 'as-Surat', 'السروات']],

            // --------------------------------------------------- Yemen tier
            'hadramawt' => [T::REGION, 'yemen', 15.9300, 48.7000, 'حضرموت', 'Hadramawt', ['Hadramawt', 'Hadhramaut']],
            'najran' => [T::CITY, 'yemen', 17.4917, 44.1322, 'نجران', 'Najran', ['Najran', 'Najaran']],
            'al-baha' => [T::CITY, 'yemen', 20.0129, 41.4677, 'الباحة', 'Al-Baha', ['Baha', 'Al Baha']],

            // ---------------------------------------------------- Hijaz tier
            'makkah' => [T::CITY, 'hijaz', 21.3891, 39.8579, 'مكة المكرمة', 'Makkah', ['مكة', 'بمكة', 'Makkah', 'Mecca', 'Makka', 'Bakkah']],
            'al-madinah' => [T::CITY, 'hijaz', 24.5247, 39.5692, 'المدينة المنورة', 'Al-Madinah', ['المدينة', 'بالمدينة', 'يثرب', 'طيبة', 'Medinah', 'Medina', 'Madinah', 'Madina', 'Yathrib']],
            'at-taif' => [T::CITY, 'hijaz', 21.2703, 40.4158, 'الطائف', 'At-Taif', ["al-Ta'if", "Ta'if", 'Taif', 'الطائف']],
            'khaybar' => [T::CITY, 'hijaz', 25.6963, 39.2920, 'خيبر', 'Khaybar', ['Khaybar', 'Khaibar']],
            'tabuk' => [T::CITY, 'hijaz', 28.3838, 36.5550, 'تبوك', 'Tabuk', ['Tabuk']],
            'yanbu' => [T::CITY, 'hijaz', 24.0895, 38.0618, 'ينبع', 'Yanbu', ['Yanbu']],
            'jeddah' => [T::CITY, 'hijaz', 21.4858, 39.1925, 'جدة', 'Jeddah', ['Jeddah', 'Jidda']],
            'ash-shuayba' => [T::LANDMARK, 'hijaz', 20.6667, 39.5333, 'ميناء الشعيبة', 'The port of Ash-Shuayba', ['الشعيبة', 'ميناء الشعيبة']],
            'badr' => [T::BATTLEFIELD, 'hijaz', 23.7800, 38.7906, 'بدر', 'Badr', ['بدر', 'ميدان بدر', 'مياه بدر', 'Badr']],
            'uhud' => [T::MOUNTAIN, 'al-madinah', 24.5085, 39.6142, 'جبل أُحُد', 'Mount Uhud', ['أحد', 'جبل أحد', 'Uhud']],
            'al-hudaybiyah' => [T::LANDMARK, 'makkah', 21.4167, 39.6167, 'الحديبية', 'Al-Hudaybiyah', ['الحديبية', 'Hudaybiyah']],
            'hunayn' => [T::VALLEY, 'hijaz', 21.4500, 40.2000, 'وادي حُنين', 'The Valley of Hunayn', ['حنين', 'وادي حنين', 'Hunayn']],
            'al-juhfah' => [T::LANDMARK, 'hijaz', 22.7500, 39.1500, 'الجُحفة', 'Al-Juhfah', ['الجحفة', 'Juhfah']],
            'dhul-hulayfah' => [T::LANDMARK, 'al-madinah', 24.4136, 39.5450, 'ذو الحُليفة', 'Dhul-Hulayfah', ['ذو الحليفة', 'أبيار علي']],
            'qudayd' => [T::LANDMARK, 'hijaz', 22.4000, 39.2500, 'قُدَيْد', 'Qudayd', ['قديد', 'Qudayd']],
            'usfan' => [T::LANDMARK, 'hijaz', 21.9167, 39.3667, 'عُسفان', 'Usfan', ['عسفان', 'Usfan']],
            'dhat-ar-riqa' => [T::BATTLEFIELD, 'najd', 24.9000, 41.0000, 'ذات الرقاع', 'Dhat ar-Riqa', ['ذات الرقاع']],
            'ukaz' => [T::LANDMARK, 'at-taif', 21.5333, 40.2167, 'سوق عكاظ', 'The market of Ukaz', ['عكاظ', 'Ukaz']],

            // ---------------------------------------------------- Makkah tier
            'masjid-al-haram' => [T::MOSQUE, 'makkah', 21.4225, 39.8262, 'المسجد الحرام', 'The Sacred Mosque', ['المسجد الحرام', 'الكعبة المشرفة', 'الكعبة', 'Al-Masjid al-Haram', 'Kaaba']],
            'ghar-hira' => [T::LANDMARK, 'makkah', 21.4580, 39.8614, 'غار حراء', 'The Cave of Hira', ['حراء', 'غار حراء', 'جبل النور', 'Hira']],
            'ghar-thawr' => [T::LANDMARK, 'makkah', 21.3567, 39.8567, 'غار ثور', 'The Cave of Thawr', ['ثور', 'غار ثور', 'جبل ثور', 'Thawr']],
            'as-safa' => [T::LANDMARK, 'makkah', 21.4222, 39.8267, 'الصفا', 'As-Safa', ['الصفا', 'الصفا والمروة', 'As-Safa']],
            'mina' => [T::LANDMARK, 'makkah', 21.4133, 39.8933, 'منى', 'Mina', ['منى', 'Mina']],
            'arafat' => [T::LANDMARK, 'makkah', 21.3550, 39.9842, 'عرفات', 'Arafat', ['عرفة', 'عرفات', 'جبل الرحمة', 'Arafat']],
            'muzdalifah' => [T::LANDMARK, 'makkah', 21.3833, 39.9333, 'مزدلفة', 'Muzdalifah', ['مزدلفة', 'المشعر الحرام', 'Muzdalifah']],
            'wadi-muhassir' => [T::VALLEY, 'makkah', 21.3950, 39.9100, 'وادي مُحَسِّر', 'The Valley of Muhassir', ['وادي محسر', 'محسر']],
            'al-aqabah' => [T::LANDMARK, 'makkah', 21.4183, 39.8933, 'العقبة', 'Al-Aqabah', ['العقبة', 'العقبة بمنى']],
            'shib-abi-talib' => [T::LANDMARK, 'makkah', 21.4239, 39.8300, 'شِعب أبي طالب', 'The Valley of Abu Talib', ['شعب أبي طالب', 'شعب بني هاشم']],

            // ------------------------------------------------- Al-Madinah tier
            'masjid-an-nabawi' => [T::MOSQUE, 'al-madinah', 24.4672, 39.6111, 'المسجد النبوي', "The Prophet's Mosque", ['المسجد النبوي', 'Al-Masjid an-Nabawi']],
            'quba' => [T::MOSQUE, 'al-madinah', 24.4394, 39.6172, 'مسجد قباء', 'The Mosque of Quba', ['قباء', 'مسجد قباء', 'Quba']],
            'baqi-al-gharqad' => [T::LANDMARK, 'al-madinah', 24.4664, 39.6136, 'بقيع الغرقد', 'Baqi al-Gharqad', ['البقيع', 'بقيع الغرقد']],
            'jabal-ar-rumah' => [T::MOUNTAIN, 'al-madinah', 24.5058, 39.6103, 'جبل الرماة', 'The Archers\' Hill', ['جبل الرماة', 'جبل عينين', 'عَيْنَيْن']],
            'hamra-al-asad' => [T::LANDMARK, 'al-madinah', 24.4000, 39.5500, 'حمراء الأسد', 'Hamra al-Asad', ['حمراء الأسد']],
            'banu-qurayzah' => [T::LANDMARK, 'al-madinah', 24.4500, 39.6300, 'حصون بني قريظة', 'The strongholds of Banu Qurayzah', ['بني قريظة', 'حصون بني قريظة']],
            'banu-qaynuqa' => [T::LANDMARK, 'al-madinah', 24.4700, 39.6200, 'حصون بني قينقاع', 'The strongholds of Banu Qaynuqa', ['بني قينقاع', 'حصون بني قينقاع']],
            'banu-an-nadir' => [T::LANDMARK, 'al-madinah', 24.4800, 39.6400, 'ديار بني النضير', 'The quarter of Banu an-Nadir', ['بني النضير']],
            'al-khandaq' => [T::BATTLEFIELD, 'al-madinah', 24.4842, 39.6108, 'الخندق', 'The Trench', ['الخندق', 'الأحزاب']],
            'bir-maunah' => [T::WELL, 'najd', 24.0000, 41.5000, 'بئر معونة', 'The Well of Maunah', ['بئر معونة', 'معونة']],

            // ----------------------------------------------------- Sham tier
            'busra' => [T::CITY, 'sham', 32.5150, 36.4819, 'بُصرى الشام', 'Busra', ['بصرى', 'بصرى الشام', 'Busra']],
            'adhriat' => [T::CITY, 'sham', 32.6189, 36.1021, 'أذرعات', 'Adhriat', ['أذرعات', 'Adhriat', 'Daraa']],
            'bayt-al-maqdis' => [T::CITY, 'sham', 31.7767, 35.2345, 'بيت المقدس', 'Jerusalem', ['بيت المقدس', 'القدس', 'إيلياء', 'Jerusalem']],
            'masjid-al-aqsa' => [T::MOSQUE, 'bayt-al-maqdis', 31.7761, 35.2358, 'المسجد الأقصى', 'Al-Aqsa Mosque', ['المسجد الأقصى', 'Al-Aqsa']],
            'mutah' => [T::BATTLEFIELD, 'sham', 31.1000, 35.7000, 'مؤتة', 'Mutah', ['مؤتة', 'Mutah']],
        ];
    }

    public function run(): void
    {
        $definitions = $this->places();

        $this->selfCheck($definitions);

        $ids = [];

        foreach ($definitions as $slug => [$type, $parentSlug, $lat, $lng, $ar, $en, $aliases]) {
            if ($parentSlug !== null && ! isset($ids[$parentSlug])) {
                throw new RuntimeException(
                    "LocationSeeder: '{$slug}' names parent '{$parentSlug}', which has not been created yet. ".
                    'Parents must be listed before their children.'
                );
            }

            $location = Location::updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => $type->value,
                    'parent_id' => $parentSlug === null ? null : $ids[$parentSlug],
                    'latitude' => number_format($lat, 7, '.', ''),
                    'longitude' => number_format($lng, 7, '.', ''),
                    'aliases' => array_values(array_unique($aliases)),
                    'is_active' => true,
                ]
            );

            $location->syncTranslations(['name' => ['ar' => $ar, 'en' => $en]]);

            $ids[$slug] = $location->id;
        }

        app(LocationResolver::class)->forget();

        $this->command?->info(sprintf('Seeded %d canonical locations.', count($ids)));
    }

    /**
     * Abort on a defect rather than pick a winner.
     *
     * Constitution X: a duplicate alias makes resolution non-deterministic, and
     * silently choosing one of the two is exactly the kind of quiet wrong answer
     * this whole feature is built to avoid.
     *
     * @param  array<string, array{0: T, 1: string|null, 2: float, 3: float, 4: string, 5: string, 6: array<int, string>}>  $definitions
     */
    private function selfCheck(array $definitions): void
    {
        $problems = [];
        $aliasOwner = [];

        foreach ($definitions as $slug => [$type, $parentSlug, $lat, $lng, $ar, $en, $aliases]) {
            if (trim($ar) === '') {
                $problems[] = "'{$slug}' has no Arabic name (Arabic is the default and fallback locale).";
            }

            if ($lat === 0.0 || $lng === 0.0) {
                $problems[] = "'{$slug}' has no coordinates — it would render a name with no map.";
            }

            if ($parentSlug !== null && ! isset($definitions[$parentSlug])) {
                $problems[] = "'{$slug}' names an unknown parent '{$parentSlug}'.";
            }

            if ($parentSlug === $slug) {
                $problems[] = "'{$slug}' is its own parent.";
            }

            foreach (array_merge($aliases, [$ar, $en]) as $alias) {
                if (trim((string) $alias) === '') {
                    $problems[] = "'{$slug}' has an empty alias.";

                    continue;
                }

                $key = LocationResolver::normalise($alias);

                if (isset($aliasOwner[$key]) && $aliasOwner[$key] !== $slug) {
                    $problems[] = "alias '{$alias}' is claimed by both '{$aliasOwner[$key]}' and '{$slug}'.";
                }

                $aliasOwner[$key] = $slug;
            }
        }

        // A cycle among the definitions themselves, before anything is written.
        foreach ($definitions as $slug => $definition) {
            $seen = [$slug => true];
            $current = $definition[1];

            while ($current !== null) {
                if (isset($seen[$current])) {
                    $problems[] = "parent chain starting at '{$slug}' contains a cycle.";
                    break;
                }

                $seen[$current] = true;
                $current = $definitions[$current][1] ?? null;
            }
        }

        if ($problems !== []) {
            throw new RuntimeException(
                "LocationSeeder self-check failed:\n  - ".implode("\n  - ", array_unique($problems))
            );
        }
    }
}
