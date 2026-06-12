<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\Surah;
use Illuminate\Database\Seeder;

/**
 * Quran Reciter Seeder
 *
 * Seeds famous Quran reciters (all reciting the riwayah of Hafs 'an 'Asim),
 * their AR/EN translations, and one per-surah recitation row per reciter.
 *
 * Audio strategy:
 * - Per-surah audio (stored in quran_recitations): full-surah mp3 files
 *   hosted on mp3quran.net / quranicaudio.com — one verified base URL per
 *   reciter, file name = zero-padded surah number.
 * - Per-verse audio (NOT stored): resolved dynamically from everyayah.com via
 *   App\Domain\Quran\Services\VerseAudioUrlResolver using the
 *   `everyayah_folder` column seeded here.
 *
 * Idempotent: reciters keyed by slug, translations by (reciter_id, locale),
 * recitations upserted on the (reciter_id, surah_id, format) unique index.
 */
class QuranReciterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $surahIds = Surah::query()->orderBy('id')->pluck('id')->all();

        if ($surahIds === []) {
            $this->command->warn('⚠ No surahs found — seeding reciters without recitations.');
        }

        $recitersSeeded = 0;
        $recitationsSeeded = 0;

        foreach ($this->reciters() as $definition) {
            $reciter = Reciter::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'style' => $definition['style'],
                    'riwayah' => "Hafs 'an 'Asim",
                    'everyayah_folder' => $definition['everyayah_folder'],
                    'country' => $definition['country'],
                    'is_featured' => $definition['is_featured'],
                    'is_active' => true,
                    'display_order' => $definition['display_order'],
                    'followers_count' => $definition['followers_count'],
                ]
            );

            $reciter->syncTranslations([
                'name' => $definition['name'],
                'description' => $definition['description'],
            ]);

            $recitersSeeded++;

            if ($surahIds !== []) {
                $recitationsSeeded += $this->seedRecitations($reciter, $definition['surah_audio_base'], $surahIds);
            }
        }

        $this->command->info("✅ Seeded {$recitersSeeded} reciters with translations.");
        $this->command->info("✅ Upserted {$recitationsSeeded} per-surah recitations.");
    }

    /**
     * Upsert one recitation row per surah for a reciter.
     *
     * @param  array<int>  $surahIds
     */
    private function seedRecitations(Reciter $reciter, string $surahAudioBase, array $surahIds): int
    {
        $now = now();

        $rows = array_map(fn (int $surahId): array => [
            'reciter_id' => $reciter->id,
            'surah_id' => $surahId,
            'audio_url' => sprintf('%s/%03d.mp3', rtrim($surahAudioBase, '/'), $surahId),
            'format' => 'mp3',
            'quality' => 'high',
            'created_at' => $now,
            'updated_at' => $now,
        ], $surahIds);

        return Recitation::upsert(
            $rows,
            ['reciter_id', 'surah_id', 'format'],
            ['audio_url', 'quality', 'updated_at']
        );
    }

    /**
     * Real, historically accurate reciter data.
     *
     * `country` holds ISO 3166-1 alpha-2 codes (existing column stores free
     * strings; codes keep filtering locale-independent).
     *
     * @return array<int, array<string, mixed>>
     */
    private function reciters(): array
    {
        return [
            [
                'slug' => 'mishary-rashid-alafasy',
                'name' => ['ar' => 'مشاري بن راشد العفاسي', 'en' => 'Mishary Rashid Alafasy'],
                'description' => [
                    'ar' => 'قارئ ومنشد كويتي وُلد عام 1976، درس في كلية القرآن الكريم بالجامعة الإسلامية بالمدينة المنورة، وهو إمام مسجد الدولة الكبير في الكويت، ويُعد من أكثر قراء العالم الإسلامي استماعاً في العصر الحديث.',
                    'en' => 'Kuwaiti reciter born in 1976. He studied at the College of the Holy Quran of the Islamic University of Madinah and serves as imam of the Grand Mosque of Kuwait. He is among the most widely heard reciters in the modern Muslim world.',
                ],
                'style' => 'murattal',
                'country' => 'KW',
                'is_featured' => true,
                'display_order' => 1,
                'followers_count' => 12500000,
                'everyayah_folder' => 'Alafasy_128kbps',
                'surah_audio_base' => 'https://server8.mp3quran.net/afs',
            ],
            [
                'slug' => 'abdul-basit-abdus-samad-murattal',
                'name' => ['ar' => 'عبد الباسط عبد الصمد (مرتل)', 'en' => 'Abdul Basit Abdus Samad (Murattal)'],
                'description' => [
                    'ar' => 'قارئ مصري (1927–1988) لُقّب بـ«الصوت الذهبي» و«الحنجرة الذهبية»، وكان أول نقيب لقراء مصر. يُعد من أعظم قراء القرن العشرين، وهذا مصحفه المرتل برواية حفص عن عاصم.',
                    'en' => 'Legendary Egyptian reciter (1927–1988) known as the "Golden Throat" and the first president of the Egyptian Reciters\' Union. He is regarded as one of the greatest reciters of the twentieth century. This is his murattal recording in the riwayah of Hafs \'an \'Asim.',
                ],
                'style' => 'murattal',
                'country' => 'EG',
                'is_featured' => true,
                'display_order' => 2,
                'followers_count' => 6800000,
                'everyayah_folder' => 'Abdul_Basit_Murattal_192kbps',
                'surah_audio_base' => 'https://server7.mp3quran.net/basit',
            ],
            [
                'slug' => 'abdul-basit-abdus-samad-mujawwad',
                'name' => ['ar' => 'عبد الباسط عبد الصمد (مجود)', 'en' => 'Abdul Basit Abdus Samad (Mujawwad)'],
                'description' => [
                    'ar' => 'التسجيل المجوّد للقارئ المصري عبد الباسط عبد الصمد (1927–1988)، صاحب الأداء المقامي الفريد الذي اشتهر في المحافل الدولية، برواية حفص عن عاصم.',
                    'en' => 'The mujawwad (melodic, slow-paced) recording of the Egyptian master Abdul Basit Abdus Samad (1927–1988), whose unique maqam-based delivery made him famous at international gatherings, in the riwayah of Hafs \'an \'Asim.',
                ],
                'style' => 'mujawwad',
                'country' => 'EG',
                'is_featured' => false,
                'display_order' => 3,
                'followers_count' => 3400000,
                'everyayah_folder' => 'Abdul_Basit_Mujawwad_128kbps',
                'surah_audio_base' => 'https://download.quranicaudio.com/quran/abdulbaset_mujawwad',
            ],
            [
                'slug' => 'mahmoud-khalil-al-husary',
                'name' => ['ar' => 'محمود خليل الحصري', 'en' => 'Mahmoud Khalil Al-Husary'],
                'description' => [
                    'ar' => 'قارئ مصري (1917–1980) من كبار علماء التجويد، شغل منصب شيخ عموم المقارئ المصرية، وكان أول من سجّل المصحف المرتل كاملاً برواية حفص عن عاصم.',
                    'en' => 'Egyptian master reciter (1917–1980) and authority on tajwid who served as head of the Egyptian school of reciters. He was the first to record the complete murattal Quran in the riwayah of Hafs \'an \'Asim.',
                ],
                'style' => 'murattal',
                'country' => 'EG',
                'is_featured' => false,
                'display_order' => 4,
                'followers_count' => 4200000,
                'everyayah_folder' => 'Husary_128kbps',
                'surah_audio_base' => 'https://server13.mp3quran.net/husr',
            ],
            [
                'slug' => 'mohamed-siddiq-el-minshawi',
                'name' => ['ar' => 'محمد صديق المنشاوي', 'en' => 'Mohamed Siddiq El-Minshawi'],
                'description' => [
                    'ar' => 'قارئ مصري (1920–1969) من أعلام دولة التلاوة المصرية، اشتهر بصوته الخاشع وأدائه المتقن، ويُعد مع الحصري وعبد الباسط ومصطفى إسماعيل من أعظم قراء القرن العشرين.',
                    'en' => 'Egyptian reciter (1920–1969), one of the giants of the Egyptian school of recitation, renowned for his humble, moving voice and masterful delivery. Together with Al-Husary, Abdul Basit and Mustafa Ismail he is counted among the greatest reciters of the twentieth century.',
                ],
                'style' => 'murattal',
                'country' => 'EG',
                'is_featured' => false,
                'display_order' => 5,
                'followers_count' => 3900000,
                'everyayah_folder' => 'Minshawy_Murattal_128kbps',
                'surah_audio_base' => 'https://server10.mp3quran.net/minsh',
            ],
            [
                'slug' => 'abdurrahman-as-sudais',
                'name' => ['ar' => 'عبد الرحمن السديس', 'en' => 'Abdurrahman As-Sudais'],
                'description' => [
                    'ar' => 'إمام وخطيب المسجد الحرام بمكة المكرمة منذ عام 1984، وُلد عام 1960، وتولى منصب الرئيس العام لشؤون المسجد الحرام والمسجد النبوي.',
                    'en' => 'Imam and khatib of the Grand Mosque (Masjid al-Haram) in Makkah since 1984. Born in 1960, he has also served as General President for the Affairs of the Two Holy Mosques.',
                ],
                'style' => 'murattal',
                'country' => 'SA',
                'is_featured' => true,
                'display_order' => 6,
                'followers_count' => 9800000,
                'everyayah_folder' => 'Abdurrahmaan_As-Sudais_192kbps',
                'surah_audio_base' => 'https://server11.mp3quran.net/sds',
            ],
            [
                'slug' => 'saud-al-shuraim',
                'name' => ['ar' => 'سعود الشريم', 'en' => 'Saud Al-Shuraim'],
                'description' => [
                    'ar' => 'إمام وخطيب المسجد الحرام، وُلد في الرياض عام 1964، وهو قاضٍ سابق وأستاذ بجامعة أم القرى، اشتهرت تلاوته في صلوات التراويح بمكة المكرمة.',
                    'en' => 'Imam and khatib of the Grand Mosque in Makkah. Born in Riyadh in 1964, he is also a former judge and a professor at Umm al-Qura University, widely known for leading Taraweeh prayers in Makkah.',
                ],
                'style' => 'murattal',
                'country' => 'SA',
                'is_featured' => false,
                'display_order' => 7,
                'followers_count' => 4600000,
                'everyayah_folder' => 'Saood_ash-Shuraym_128kbps',
                'surah_audio_base' => 'https://server7.mp3quran.net/shur',
            ],
            [
                'slug' => 'saad-al-ghamdi',
                'name' => ['ar' => 'سعد الغامدي', 'en' => 'Saad Al-Ghamdi'],
                'description' => [
                    'ar' => 'قارئ سعودي وُلد عام 1967، أمَّ المصلين في مساجد الدمام، واشتهرت تلاوته الخاشعة الهادئة في أنحاء العالم الإسلامي.',
                    'en' => 'Saudi reciter born in 1967. He served as imam in the mosques of Dammam and is beloved across the Muslim world for his serene, heartfelt murattal recitation.',
                ],
                'style' => 'murattal',
                'country' => 'SA',
                'is_featured' => false,
                'display_order' => 8,
                'followers_count' => 5400000,
                'everyayah_folder' => 'Ghamadi_40kbps',
                'surah_audio_base' => 'https://server7.mp3quran.net/s_gmd',
            ],
            [
                'slug' => 'abu-bakr-al-shatri',
                'name' => ['ar' => 'أبو بكر الشاطري', 'en' => 'Abu Bakr Al-Shatri'],
                'description' => [
                    'ar' => 'قارئ سعودي وُلد في جدة عام 1970، أمَّ المصلين في عدد من مساجد جدة، ويتميز بصوت ندي وتلاوة مرتلة متقنة برواية حفص عن عاصم.',
                    'en' => 'Saudi reciter born in Jeddah in 1970. He has served as imam of several mosques in Jeddah and is known for his clear, melodious murattal recitation in the riwayah of Hafs \'an \'Asim.',
                ],
                'style' => 'murattal',
                'country' => 'SA',
                'is_featured' => false,
                'display_order' => 9,
                'followers_count' => 2900000,
                'everyayah_folder' => 'Abu_Bakr_Ash-Shaatree_128kbps',
                'surah_audio_base' => 'https://server11.mp3quran.net/shatri',
            ],
            [
                'slug' => 'hani-ar-rifai',
                'name' => ['ar' => 'هاني الرفاعي', 'en' => 'Hani Ar-Rifai'],
                'description' => [
                    'ar' => 'قارئ سعودي وُلد عام 1974، إمام وخطيب جامع عناني بمدينة جدة، يتميز بتلاوة مؤثرة ودعاء خاشع.',
                    'en' => 'Saudi reciter born in 1974, imam and khatib of the Anani Mosque in Jeddah, known for his deeply moving recitation and heartfelt supplications.',
                ],
                'style' => 'murattal',
                'country' => 'SA',
                'is_featured' => false,
                'display_order' => 10,
                'followers_count' => 2100000,
                'everyayah_folder' => 'Hani_Rifai_192kbps',
                'surah_audio_base' => 'https://server8.mp3quran.net/hani',
            ],
            [
                'slug' => 'maher-al-muaiqly',
                'name' => ['ar' => 'ماهر المعيقلي', 'en' => 'Maher Al-Muaiqly'],
                'description' => [
                    'ar' => 'إمام وخطيب المسجد الحرام، وُلد في المدينة المنورة عام 1969، حاصل على الدكتوراه في التفسير، وذاع صيته عالمياً من خلال إمامته لصلوات التراويح بمكة المكرمة.',
                    'en' => 'Imam and khatib of the Grand Mosque in Makkah. Born in Madinah in 1969 and holder of a doctorate in tafsir, he rose to worldwide fame through leading the Taraweeh prayers in Makkah.',
                ],
                'style' => 'murattal',
                'country' => 'SA',
                'is_featured' => false,
                'display_order' => 11,
                'followers_count' => 7200000,
                'everyayah_folder' => 'MaherAlMuaiqly128kbps',
                'surah_audio_base' => 'https://server12.mp3quran.net/maher',
            ],
        ];
    }
}
