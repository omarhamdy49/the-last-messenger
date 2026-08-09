<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Local-development fixtures.
 *
 * Seeds the minimum authentic content needed to exercise the article wizard's
 * cross-module embeds (Quran verses, hadiths, seerah events) and its taxonomy
 * pickers. Every write is an upsert keyed on the natural key, so the seeder is
 * safely re-runnable.
 *
 * Not intended for production: run explicitly with
 *   php artisan db:seed --class=DevContentFixturesSeeder
 */
final class DevContentFixturesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedWriters();
        $this->seedCategories();
        $this->seedTags();
        $this->seedQuran();
        $this->seedHadith();
        $this->seedSeerah();
    }

    private function now(): string
    {
        return now()->toDateTimeString();
    }

    /**
     * The article form's author dropdown is fed by User::role('writer'), so it
     * stays empty until at least one user actually holds that role.
     */
    private function seedWriters(): void
    {
        $writers = [
            ['name' => 'د. أحمد المصري', 'email' => 'writer.ahmed@example.test'],
            ['name' => 'أ. فاطمة الزهراء', 'email' => 'writer.fatima@example.test'],
        ];

        foreach ($writers as $writer) {
            $user = User::firstOrCreate(
                ['email' => $writer['email']],
                ['name' => $writer['name'], 'password' => Hash::make('password'), 'email_verified_at' => now()]
            );

            if (! $user->hasRole('writer')) {
                $user->assignRole('writer');
            }
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['slug' => 'seerah-nabawiyyah', 'icon' => 'book-open', 'color' => '#0056b3', 'order' => 1,
                'ar' => 'السيرة النبوية', 'en' => 'Prophetic Biography'],
            ['slug' => 'aqeedah', 'icon' => 'heart', 'color' => '#218838', 'order' => 2,
                'ar' => 'العقيدة', 'en' => 'Creed'],
            ['slug' => 'akhlaq', 'icon' => 'sparkles', 'color' => '#64CCC5', 'order' => 3,
                'ar' => 'الأخلاق والآداب', 'en' => 'Ethics & Manners'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'type' => 'blog',
                    'status' => 'active',
                    'parent_id' => null,
                    'depth' => 0,
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'display_order' => $category['order'],
                    'updated_at' => $this->now(),
                    'created_at' => $this->now(),
                ]
            );

            $id = (int) DB::table('categories')->where('slug', $category['slug'])->value('id');

            foreach (['ar' => $category['ar'], 'en' => $category['en']] as $locale => $name) {
                DB::table('category_translations')->updateOrInsert(
                    ['category_id' => $id, 'locale' => $locale],
                    ['name' => $name, 'is_ready' => 1, 'updated_at' => $this->now(), 'created_at' => $this->now()]
                );
            }
        }
    }

    private function seedTags(): void
    {
        $tags = [
            ['slug' => 'makkah', 'ar' => 'مكة المكرمة', 'en' => 'Makkah', 'color' => '#0056b3'],
            ['slug' => 'madinah', 'ar' => 'المدينة المنورة', 'en' => 'Madinah', 'color' => '#218838'],
            ['slug' => 'sahaba', 'ar' => 'الصحابة', 'en' => 'Companions', 'color' => '#64CCC5'],
            ['slug' => 'hijrah', 'ar' => 'الهجرة', 'en' => 'Hijrah', 'color' => '#b45309'],
            ['slug' => 'tafsir', 'ar' => 'التفسير', 'en' => 'Tafsir', 'color' => '#6d28d9'],
        ];

        foreach ($tags as $tag) {
            DB::table('tags')->updateOrInsert(
                ['slug' => $tag['slug']],
                // 'article' matches StoreTagRequest's default — anything else is
                // filtered out of the article form by ArticleRepository::getFormTags().
                ['type' => 'article', 'color' => $tag['color'], 'updated_at' => $this->now(), 'created_at' => $this->now()]
            );

            $id = (int) DB::table('tags')->where('slug', $tag['slug'])->value('id');

            foreach (['ar' => $tag['ar'], 'en' => $tag['en']] as $locale => $name) {
                DB::table('tag_translations')->updateOrInsert(
                    ['tag_id' => $id, 'locale' => $locale],
                    ['name' => $name, 'is_ready' => 1, 'updated_at' => $this->now(), 'created_at' => $this->now()]
                );
            }
        }
    }

    /**
     * Three real surahs with their verses and Arabic/English text, enough for the
     * verse picker to browse, paginate and embed.
     */
    private function seedQuran(): void
    {
        $arabicResource = $this->translationResource('quran-uthmani', 'المصحف العثماني', 'ar', 'rtl', true);
        $englishResource = $this->translationResource('sahih-international', 'Sahih International', 'en', 'ltr', false);

        $surahs = [
            [
                'id' => 1, 'name_ar' => 'الفاتحة', 'name_en' => 'Al-Fatihah', 'transliteration' => 'Al-Fātiḥah',
                'revelation_place' => 'makkah', 'revelation_order' => 5, 'bismillah_pre' => 0, 'page' => 1, 'juz' => 1,
                'verses' => [
                    ['بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', 'In the name of Allah, the Entirely Merciful, the Especially Merciful.'],
                    ['الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ', '[All] praise is [due] to Allah, Lord of the worlds -'],
                    ['الرَّحْمَٰنِ الرَّحِيمِ', 'The Entirely Merciful, the Especially Merciful,'],
                    ['مَالِكِ يَوْمِ الدِّينِ', 'Sovereign of the Day of Recompense.'],
                    ['إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ', 'It is You we worship and You we ask for help.'],
                    ['اهْدِنَا الصِّرَاطَ الْمُسْتَقِيمَ', 'Guide us to the straight path -'],
                    ['صِرَاطَ الَّذِينَ أَنْعَمْتَ عَلَيْهِمْ غَيْرِ الْمَغْضُوبِ عَلَيْهِمْ وَلَا الضَّالِّينَ', 'The path of those upon whom You have bestowed favor, not of those who have evoked [Your] anger or of those who are astray.'],
                ],
            ],
            [
                'id' => 112, 'name_ar' => 'الإخلاص', 'name_en' => 'Al-Ikhlas', 'transliteration' => 'Al-Ikhlāṣ',
                'revelation_place' => 'makkah', 'revelation_order' => 22, 'bismillah_pre' => 1, 'page' => 604, 'juz' => 30,
                'verses' => [
                    ['قُلْ هُوَ اللَّهُ أَحَدٌ', 'Say, "He is Allah, [who is] One,'],
                    ['اللَّهُ الصَّمَدُ', 'Allah, the Eternal Refuge.'],
                    ['لَمْ يَلِدْ وَلَمْ يُولَدْ', 'He neither begets nor is born,'],
                    ['وَلَمْ يَكُن لَّهُ كُفُوًا أَحَدٌ', 'Nor is there to Him any equivalent."'],
                ],
            ],
            [
                'id' => 103, 'name_ar' => 'العصر', 'name_en' => 'Al-Asr', 'transliteration' => 'Al-ʿAṣr',
                'revelation_place' => 'makkah', 'revelation_order' => 13, 'bismillah_pre' => 1, 'page' => 601, 'juz' => 30,
                'verses' => [
                    ['وَالْعَصْرِ', 'By time,'],
                    ['إِنَّ الْإِنسَانَ لَفِي خُسْرٍ', 'Indeed, mankind is in loss,'],
                    ['إِلَّا الَّذِينَ آمَنُوا وَعَمِلُوا الصَّالِحَاتِ وَتَوَاصَوْا بِالْحَقِّ وَتَوَاصَوْا بِالصَّبْرِ', 'Except for those who have believed and done righteous deeds and advised each other to truth and advised each other to patience.'],
                ],
            ],
        ];

        foreach ($surahs as $surah) {
            DB::table('quran_surahs')->updateOrInsert(
                ['id' => $surah['id']],
                [
                    'revelation_place' => $surah['revelation_place'],
                    'revelation_order' => $surah['revelation_order'],
                    'bismillah_pre' => $surah['bismillah_pre'],
                    'name_ar' => $surah['name_ar'],
                    'name_en' => $surah['name_en'],
                    'transliteration' => $surah['transliteration'],
                    'verses_count' => count($surah['verses']),
                    'pages' => json_encode([$surah['page'], $surah['page']]),
                    'updated_at' => $this->now(),
                    'created_at' => $this->now(),
                ]
            );

            foreach ($surah['verses'] as $index => [$arabic, $english]) {
                $number = $index + 1;
                $key = $surah['id'].':'.$number;

                DB::table('quran_verses')->updateOrInsert(
                    ['verse_key' => $key],
                    [
                        'surah_id' => $surah['id'],
                        'verse_number' => $number,
                        'juz_number' => $surah['juz'],
                        'hizb_number' => (int) ceil($surah['juz'] * 2),
                        'rub_el_hizb_number' => (int) ceil($surah['juz'] * 8),
                        'ruku_number' => 1,
                        'manzil_number' => 1,
                        'sajdah_number' => null,
                        'page_number' => $surah['page'],
                        'updated_at' => $this->now(),
                        'created_at' => $this->now(),
                    ]
                );

                $verseId = (int) DB::table('quran_verses')->where('verse_key', $key)->value('id');

                foreach ([[$arabicResource, 'ar', $arabic], [$englishResource, 'en', $english]] as [$resourceId, $lang, $text]) {
                    DB::table('quran_verse_translations')->updateOrInsert(
                        ['verse_id' => $verseId, 'resource_id' => $resourceId, 'lang' => $lang],
                        [
                            'text' => $text,
                            'text_notashkeel' => preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}]/u', '', $text),
                            'updated_at' => $this->now(),
                            'created_at' => $this->now(),
                        ]
                    );
                }
            }
        }
    }

    private function translationResource(string $slug, string $name, string $language, string $direction, bool $isDefault): int
    {
        DB::table('quran_translation_resources')->updateOrInsert(
            ['slug' => $slug],
            [
                'name' => json_encode(['ar' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
                'author' => $name,
                'language' => $language,
                'direction' => $direction,
                'is_default' => $isDefault ? 1 : 0,
                'is_active' => 1,
                'display_order' => $isDefault ? 0 : 1,
                'updated_at' => $this->now(),
                'created_at' => $this->now(),
            ]
        );

        return (int) DB::table('quran_translation_resources')->where('slug', $slug)->value('id');
    }

    private function seedHadith(): void
    {
        $collections = [
            ['slug' => 'bukhari', 'ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari', 'order' => 1],
            ['slug' => 'muslim', 'ar' => 'صحيح مسلم', 'en' => 'Sahih Muslim', 'order' => 2],
        ];

        foreach ($collections as $collection) {
            DB::table('hadith_collections')->updateOrInsert(
                ['slug' => $collection['slug']],
                [
                    'title_ar' => $collection['ar'],
                    'title_en' => $collection['en'],
                    'reference_template' => $collection['en'].' {number}',
                    'has_books' => 1,
                    'has_chapters' => 0,
                    'sort_order' => $collection['order'],
                    'is_featured' => 1,
                    'is_published' => 1,
                    'total_hadith' => 0,
                    'updated_at' => $this->now(),
                    'created_at' => $this->now(),
                ]
            );
        }

        $bukhariId = (int) DB::table('hadith_collections')->where('slug', 'bukhari')->value('id');
        $muslimId = (int) DB::table('hadith_collections')->where('slug', 'muslim')->value('id');

        $books = [
            [$bukhariId, '1', 'كتاب بدء الوحي', 'Revelation'],
            [$bukhariId, '2', 'كتاب الإيمان', 'Belief'],
            [$muslimId, '1', 'كتاب الإيمان', 'The Book of Faith'],
        ];

        foreach ($books as [$collectionId, $number, $titleAr, $titleEn]) {
            DB::table('hadith_books')->updateOrInsert(
                ['collection_id' => $collectionId, 'book_number' => $number],
                [
                    'title_ar' => $titleAr,
                    'title_en' => $titleEn,
                    'sort_order' => (int) $number,
                    'is_published' => 1,
                    'updated_at' => $this->now(),
                    'created_at' => $this->now(),
                ]
            );
        }

        $bukhariBook1 = (int) DB::table('hadith_books')->where('collection_id', $bukhariId)->where('book_number', '1')->value('id');
        $bukhariBook2 = (int) DB::table('hadith_books')->where('collection_id', $bukhariId)->where('book_number', '2')->value('id');
        $muslimBook1 = (int) DB::table('hadith_books')->where('collection_id', $muslimId)->where('book_number', '1')->value('id');

        $hadiths = [
            [$bukhariId, $bukhariBook1, '1', 'إِنَّمَا الأَعْمَالُ بِالنِّيَّاتِ، وَإِنَّمَا لِكُلِّ امْرِئٍ مَا نَوَى', 'Actions are but by intention, and every man shall have only that which he intended.', 'sahih', 'عمر بن الخطاب رضي الله عنه'],
            [$bukhariId, $bukhariBook2, '8', 'بُنِيَ الإِسْلاَمُ عَلَى خَمْسٍ: شَهَادَةِ أَنْ لاَ إِلَهَ إِلاَّ اللَّهُ وَأَنَّ مُحَمَّدًا رَسُولُ اللَّهِ، وَإِقَامِ الصَّلاَةِ، وَإِيتَاءِ الزَّكَاةِ، وَالْحَجِّ، وَصَوْمِ رَمَضَانَ', 'Islam is built upon five: the testimony that there is no god but Allah and that Muhammad is the Messenger of Allah, establishing the prayer, paying the zakat, the pilgrimage, and fasting Ramadan.', 'sahih', 'عبد الله بن عمر رضي الله عنهما'],
            [$bukhariId, $bukhariBook2, '13', 'لاَ يُؤْمِنُ أَحَدُكُمْ حَتَّى يُحِبَّ لأَخِيهِ مَا يُحِبُّ لِنَفْسِهِ', 'None of you truly believes until he loves for his brother what he loves for himself.', 'sahih', 'أنس بن مالك رضي الله عنه'],
            [$muslimId, $muslimBook1, '8', 'الإِيمَانُ أَنْ تُؤْمِنَ بِاللَّهِ وَمَلاَئِكَتِهِ وَكُتُبِهِ وَرُسُلِهِ وَالْيَوْمِ الآخِرِ، وَتُؤْمِنَ بِالْقَدَرِ خَيْرِهِ وَشَرِّهِ', 'Faith is that you believe in Allah, His angels, His books, His messengers, the Last Day, and that you believe in divine decree, both its good and its evil.', 'sahih', 'عمر بن الخطاب رضي الله عنه'],
            [$muslimId, $muslimBook1, '35', 'الطُّهُورُ شَطْرُ الإِيمَانِ', 'Purity is half of faith.', 'sahih', 'أبو مالك الأشعري رضي الله عنه'],
        ];

        foreach ($hadiths as $index => [$collectionId, $bookId, $number, $textAr, $textEn, $grade, $narrator]) {
            $referenceKey = $collectionId.':'.$number;

            DB::table('hadiths')->updateOrInsert(
                ['collection_id' => $collectionId, 'reference_key' => $referenceKey],
                [
                    'book_id' => $bookId,
                    'chapter_id' => null,
                    'number' => $number,
                    'in_book_number' => $number,
                    'sort_order' => $index + 1,
                    'grade' => $grade,
                    'raw_grade' => 'صحيح',
                    'text_ar' => $textAr,
                    'text_en' => $textEn,
                    'search_ar' => $textAr,
                    'search_en' => $textEn,
                    'narrated_by' => $narrator,
                    'canonical_reference' => 'Sahih '.$number,
                    'is_published' => 1,
                    'updated_at' => $this->now(),
                    'created_at' => $this->now(),
                ]
            );
        }

        foreach ([$bukhariId, $muslimId] as $collectionId) {
            DB::table('hadith_collections')->where('id', $collectionId)->update([
                'total_hadith' => DB::table('hadiths')->where('collection_id', $collectionId)->count(),
            ]);
        }
    }

    private function seedSeerah(): void
    {
        $types = [
            ['slug' => 'battle', 'icon' => 'swords', 'color' => 'red', 'ar' => 'غزوة', 'en' => 'Battle'],
            ['slug' => 'revelation', 'icon' => 'book-open', 'color' => 'emerald', 'ar' => 'وحي', 'en' => 'Revelation'],
            ['slug' => 'migration', 'icon' => 'route', 'color' => 'blue', 'ar' => 'هجرة', 'en' => 'Migration'],
        ];

        foreach ($types as $order => $type) {
            DB::table('seerah_event_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'icon' => $type['icon'],
                    'color' => $type['color'],
                    'gradient' => null,
                    'is_active' => 1,
                    'display_order' => $order + 1,
                    'updated_at' => $this->now(),
                    'created_at' => $this->now(),
                ]
            );

            $id = (int) DB::table('seerah_event_types')->where('slug', $type['slug'])->value('id');

            foreach (['ar' => $type['ar'], 'en' => $type['en']] as $locale => $name) {
                DB::table('seerah_event_type_translations')->updateOrInsert(
                    ['seerah_event_type_id' => $id, 'locale' => $locale],
                    ['name' => $name, 'is_ready' => 1, 'updated_at' => $this->now(), 'created_at' => $this->now()]
                );
            }
        }

        $events = [
            [
                'slug' => 'first-revelation', 'type' => 'revelation', 'hijri_year' => -13, 'gregorian_year' => 610,
                'location' => 'غار حراء، مكة', 'lat' => 21.4577, 'lng' => 39.8579, 'importance' => 'high', 'pivotal' => 1,
                'ar' => ['نزول الوحي', 'أول لقاء بين النبي محمد ﷺ وجبريل عليه السلام في غار حراء.'],
                'en' => ['The First Revelation', 'The first encounter between the Prophet Muhammad ﷺ and the Angel Jibril in the cave of Hira.'],
            ],
            [
                'slug' => 'hijrah-to-madinah', 'type' => 'migration', 'hijri_year' => 1, 'gregorian_year' => 622,
                'location' => 'من مكة إلى المدينة', 'lat' => 24.4686, 'lng' => 39.6142, 'importance' => 'high', 'pivotal' => 1,
                'ar' => ['الهجرة إلى المدينة', 'هجرة النبي ﷺ وصحبه من مكة إلى يثرب، وبها بدأ التقويم الهجري.'],
                'en' => ['The Migration to Madinah', 'The migration of the Prophet ﷺ and his companions from Makkah to Yathrib, which begins the Hijri calendar.'],
            ],
            [
                'slug' => 'battle-of-badr', 'type' => 'battle', 'hijri_year' => 2, 'gregorian_year' => 624,
                'location' => 'بدر', 'lat' => 23.7772, 'lng' => 38.7906, 'importance' => 'high', 'pivotal' => 1,
                'ar' => ['غزوة بدر الكبرى', 'أول معركة فاصلة بين المسلمين وقريش في السنة الثانية للهجرة.'],
                'en' => ['The Battle of Badr', 'The first decisive battle between the Muslims and Quraysh, in the second year after the Hijrah.'],
            ],
        ];

        foreach ($events as $order => $event) {
            $typeId = (int) DB::table('seerah_event_types')->where('slug', $event['type'])->value('id');

            DB::table('seerah_events')->updateOrInsert(
                ['slug' => $event['slug']],
                [
                    'hijri_year' => $event['hijri_year'],
                    'gregorian_year' => $event['gregorian_year'],
                    'seerah_event_type_id' => $typeId,
                    'importance' => $event['importance'],
                    'status' => 'published',
                    'location' => $event['location'],
                    'latitude' => $event['lat'],
                    'longitude' => $event['lng'],
                    'display_order' => $order + 1,
                    'is_featured' => 1,
                    'is_pivotal' => $event['pivotal'],
                    'updated_at' => $this->now(),
                    'created_at' => $this->now(),
                ]
            );

            $id = (int) DB::table('seerah_events')->where('slug', $event['slug'])->value('id');

            foreach (['ar' => $event['ar'], 'en' => $event['en']] as $locale => [$title, $description]) {
                DB::table('seerah_event_translations')->updateOrInsert(
                    ['seerah_event_id' => $id, 'locale' => $locale],
                    [
                        'title' => $title,
                        'description' => $description,
                        'content' => $description,
                        'is_ready' => 1,
                        'updated_at' => $this->now(),
                        'created_at' => $this->now(),
                    ]
                );
            }
        }
    }
}
