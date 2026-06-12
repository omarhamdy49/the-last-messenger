<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Quran\Models\RevelationContext;
use App\Domain\Quran\Models\Surah;
use Illuminate\Database\Seeder;

/**
 * Seeds historically established Asbab al-Nuzul (revelation contexts).
 *
 * Entries are limited to well-known, classically documented cases cited from
 * al-Wahidi's "Asbab al-Nuzul" and the Sahih collections. Arabic + English
 * content is provided; fr/tr/ur rows are created empty with is_ready=false.
 *
 * Run directly (NOT registered in DatabaseSeeder):
 *   php artisan db:seed --class=QuranRevelationContextSeeder
 */
class QuranRevelationContextSeeder extends Seeder
{
    public function run(): void
    {
        $surahsByNumber = Surah::query()
            ->select(['id', 'revelation_order'])
            ->get()
            ->keyBy('revelation_order');

        $seeded = 0;

        foreach ($this->contexts() as $index => $entry) {
            $surah = $surahsByNumber->get($entry['surah']);

            if ($surah === null) {
                $this->command?->warn("Surah {$entry['surah']} not found — skipping {$entry['event_key']}");

                continue;
            }

            /** @var RevelationContext $context */
            $context = RevelationContext::query()->updateOrCreate(
                ['event_key' => $entry['event_key']],
                [
                    'surah_id' => $surah->id,
                    'verse_from' => $entry['verse_from'],
                    'verse_to' => $entry['verse_to'],
                    'period' => $entry['period'],
                    'display_order' => $index + 1, // chronological position
                    'is_active' => true,
                ]
            );

            foreach (['ar', 'en'] as $locale) {
                $context->translations()->updateOrCreate(
                    ['revelation_context_id' => $context->id, 'locale' => $locale],
                    [
                        'title' => $entry[$locale]['title'],
                        'narrative' => $entry[$locale]['narrative'],
                        'source_reference' => $entry[$locale]['source'],
                        'is_ready' => true,
                    ]
                );
            }

            // Placeholder rows for the remaining locales — content pending translation.
            foreach (['fr', 'tr', 'ur'] as $locale) {
                $context->translations()->updateOrCreate(
                    ['revelation_context_id' => $context->id, 'locale' => $locale],
                    ['is_ready' => false]
                );
            }

            $seeded++;
        }

        $this->command?->info("Seeded {$seeded} revelation contexts.");
    }

    /**
     * Well-established asbab al-nuzul, ordered chronologically.
     *
     * @return array<int, array<string, mixed>>
     */
    private function contexts(): array
    {
        return [
            [
                'surah' => 96, 'verse_from' => 1, 'verse_to' => 5, 'period' => 'makki', 'event_key' => 'hira-first-revelation',
                'ar' => [
                    'title' => 'بدء الوحي في غار حراء',
                    'narrative' => 'نزلت أول آيات القرآن على النبي ﷺ وهو يتعبد في غار حراء، حين جاءه جبريل عليه السلام فقال له: اقرأ، فقال: ما أنا بقارئ، فغطّه حتى بلغ منه الجهد ثم أرسله، حتى قال له في الثالثة: ﴿اقْرَأْ بِاسْمِ رَبِّكَ الَّذِي خَلَقَ﴾. فرجع بها رسول الله ﷺ يرجف فؤاده إلى خديجة رضي الله عنها.',
                    'source' => 'صحيح البخاري (3)، صحيح مسلم (160)',
                ],
                'en' => [
                    'title' => 'The first revelation at the cave of Hira',
                    'narrative' => 'The first verses of the Quran were revealed while the Prophet ﷺ was devoting himself to worship in the cave of Hira. Jibril came to him and said "Read!", and he replied "I am not a reader." Jibril embraced him until he was exhausted, then on the third time recited: "Read in the name of your Lord who created." The Prophet ﷺ returned to Khadijah with his heart trembling.',
                    'source' => 'Sahih al-Bukhari (3), Sahih Muslim (160)',
                ],
            ],
            [
                'surah' => 74, 'verse_from' => 1, 'verse_to' => 7, 'period' => 'makki', 'event_key' => 'muddaththir-resumption',
                'ar' => [
                    'title' => 'نزول "يا أيها المدثر" بعد فترة الوحي الأولى',
                    'narrative' => 'بعد فترة من انقطاع الوحي، سمع النبي ﷺ صوتاً من السماء فرفع بصره فإذا الملَك الذي جاءه بحراء جالس على كرسي بين السماء والأرض، فرعب منه ورجع فقال: زمّلوني زمّلوني، فأنزل الله: ﴿يَا أَيُّهَا الْمُدَّثِّرُ * قُمْ فَأَنذِرْ﴾، فحمي الوحي وتتابع.',
                    'source' => 'صحيح البخاري (4)، صحيح مسلم (161)',
                ],
                'en' => [
                    'title' => 'The revelation of "O you wrapped in garments" after the first pause',
                    'narrative' => 'After a pause in revelation, the Prophet ﷺ heard a voice from the sky and saw the angel who had come to him at Hira seated on a throne between heaven and earth. Terrified, he returned home saying "Wrap me up, wrap me up!", whereupon Allah revealed: "O you who covers himself, arise and warn." Revelation then continued in succession.',
                    'source' => 'Sahih al-Bukhari (4), Sahih Muslim (161)',
                ],
            ],
            [
                'surah' => 93, 'verse_from' => 1, 'verse_to' => 3, 'period' => 'makki', 'event_key' => 'duha-revelation-pause',
                'ar' => [
                    'title' => 'فترة الوحي ونزول سورة الضحى',
                    'narrative' => 'احتبس الوحي عن النبي ﷺ ليلتين أو ثلاثاً، فقالت امرأة من المشركين: ما أرى صاحبك إلا قد ودّعك وقلاك، فأنزل الله: ﴿وَالضُّحَى * وَاللَّيْلِ إِذَا سَجَى * مَا وَدَّعَكَ رَبُّكَ وَمَا قَلَى﴾ تطميناً للنبي ﷺ ورداً على المشركين.',
                    'source' => 'صحيح البخاري (1124)، صحيح مسلم (1797)، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'The pause in revelation and Surah Ad-Duha',
                    'narrative' => 'Revelation ceased for two or three nights, and a woman of the polytheists said: "I see that your companion has abandoned and forsaken you." Allah then revealed: "By the morning brightness, and by the night when it covers with darkness — your Lord has not forsaken you, nor does He hate you," reassuring the Prophet ﷺ and refuting the polytheists.',
                    'source' => 'Sahih al-Bukhari (1124), Sahih Muslim (1797), al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 26, 'verse_from' => 214, 'verse_to' => null, 'period' => 'makki', 'event_key' => 'warn-nearest-kin-safa',
                'ar' => [
                    'title' => 'إنذار العشيرة الأقربين على الصفا',
                    'narrative' => 'لما نزلت ﴿وَأَنذِرْ عَشِيرَتَكَ الْأَقْرَبِينَ﴾ صعد النبي ﷺ على الصفا فجعل ينادي بطون قريش: يا بني فهر، يا بني عدي، حتى اجتمعوا، فأنذرهم عذاب الله إن لم يؤمنوا، فقال أبو لهب: تباً لك سائر اليوم، ألهذا جمعتنا؟',
                    'source' => 'صحيح البخاري (4770)، صحيح مسلم (208)',
                ],
                'en' => [
                    'title' => 'Warning the nearest kin from Mount Safa',
                    'narrative' => 'When "And warn your closest kindred" was revealed, the Prophet ﷺ climbed Mount Safa and called out to the clans of Quraysh until they gathered. He warned them of Allah\'s punishment if they did not believe. Abu Lahab said: "May you perish for the rest of the day! Is this why you gathered us?"',
                    'source' => 'Sahih al-Bukhari (4770), Sahih Muslim (208)',
                ],
            ],
            [
                'surah' => 111, 'verse_from' => 1, 'verse_to' => 5, 'period' => 'makki', 'event_key' => 'abu-lahab-masad',
                'ar' => [
                    'title' => 'أبو لهب وسورة المسد',
                    'narrative' => 'لما قال أبو لهب للنبي ﷺ بعد دعوته قريشاً على الصفا: تباً لك، ألهذا جمعتنا؟ أنزل الله سورة كاملة في شأنه: ﴿تَبَّتْ يَدَا أَبِي لَهَبٍ وَتَبَّ﴾، تخبر بهلاكه وهلاك امرأته حمالة الحطب.',
                    'source' => 'صحيح البخاري (4971)، صحيح مسلم (208)، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'Abu Lahab and Surah Al-Masad',
                    'narrative' => 'When Abu Lahab responded to the Prophet\'s call on Mount Safa by saying "May you perish! Is this why you gathered us?", Allah revealed an entire surah about him: "May the hands of Abu Lahab be ruined, and ruined is he" — announcing his doom and that of his wife, the carrier of firewood.',
                    'source' => 'Sahih al-Bukhari (4971), Sahih Muslim (208), al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 108, 'verse_from' => 1, 'verse_to' => 3, 'period' => 'makki', 'event_key' => 'kawthar-al-as-ibn-wail',
                'ar' => [
                    'title' => 'الرد على من وصف النبي ﷺ بالأبتر',
                    'narrative' => 'لما توفي ابن النبي ﷺ قال العاص بن وائل السهمي: دعوه فإنما هو رجل أبتر لا عقب له، فأنزل الله سورة الكوثر: ﴿إِنَّا أَعْطَيْنَاكَ الْكَوْثَرَ﴾ تعزيةً للنبي ﷺ وإخباراً بأن شانئه هو الأبتر المقطوع عن كل خير.',
                    'source' => 'أسباب النزول للواحدي، تفسير الطبري',
                ],
                'en' => [
                    'title' => 'The reply to those who called the Prophet ﷺ "cut off"',
                    'narrative' => 'When the Prophet\'s son died, al-\'As ibn Wa\'il al-Sahmi said: "Leave him, for he is a man cut off with no descendants." Allah then revealed Surah Al-Kawthar: "Indeed, We have granted you al-Kawthar" — consoling the Prophet ﷺ and declaring that his enemy is the one truly cut off from all good.',
                    'source' => 'al-Wahidi: Asbab al-Nuzul, Tafsir al-Tabari',
                ],
            ],
            [
                'surah' => 80, 'verse_from' => 1, 'verse_to' => 10, 'period' => 'makki', 'event_key' => 'abasa-ibn-umm-maktum',
                'ar' => [
                    'title' => 'عتاب في شأن عبد الله بن أم مكتوم',
                    'narrative' => 'جاء عبد الله بن أم مكتوم الأعمى إلى النبي ﷺ يقول: يا رسول الله أرشدني، وعند النبي ﷺ رجل من عظماء المشركين يدعوه إلى الإسلام، فجعل النبي ﷺ يُعرض عنه ويُقبل على الآخر، فأنزل الله: ﴿عَبَسَ وَتَوَلَّى * أَن جَاءَهُ الْأَعْمَى﴾، فكان النبي ﷺ بعدها يكرمه.',
                    'source' => 'سنن الترمذي (3331)، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'The gentle reproach concerning Abdullah ibn Umm Maktum',
                    'narrative' => 'The blind companion Abdullah ibn Umm Maktum came to the Prophet ﷺ saying "O Messenger of Allah, guide me," while the Prophet ﷺ was occupied inviting one of the chiefs of the polytheists to Islam. He turned away from the blind man, so Allah revealed: "He frowned and turned away because there came to him the blind man." The Prophet ﷺ honoured him ever after.',
                    'source' => 'Sunan al-Tirmidhi (3331), al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 75, 'verse_from' => 16, 'verse_to' => 19, 'period' => 'makki', 'event_key' => 'qiyamah-hastening-revelation',
                'ar' => [
                    'title' => 'تحريك النبي ﷺ لسانه ليحفظ الوحي',
                    'narrative' => 'كان النبي ﷺ إذا نزل عليه الوحي يحرك لسانه وشفتيه يريد حفظه مخافة أن يتفلّت منه، فأنزل الله: ﴿لَا تُحَرِّكْ بِهِ لِسَانَكَ لِتَعْجَلَ بِهِ * إِنَّ عَلَيْنَا جَمْعَهُ وَقُرْآنَهُ﴾، فكان بعدها يستمع فإذا انطلق جبريل قرأه كما وعده الله.',
                    'source' => 'صحيح البخاري (5)، صحيح مسلم (448)',
                ],
                'en' => [
                    'title' => 'The Prophet ﷺ moving his tongue to memorise the revelation',
                    'narrative' => 'When revelation descended, the Prophet ﷺ would move his tongue and lips trying to retain it, fearing it would slip away. Allah revealed: "Do not move your tongue with it to hasten it. Indeed, upon Us is its collection and its recitation." Thereafter he would listen, and once Jibril departed he would recite it as Allah had promised.',
                    'source' => 'Sahih al-Bukhari (5), Sahih Muslim (448)',
                ],
            ],
            [
                'surah' => 18, 'verse_from' => 9, 'verse_to' => 26, 'period' => 'makki', 'event_key' => 'kahf-quraysh-questions',
                'ar' => [
                    'title' => 'أسئلة قريش الثلاثة وقصة أصحاب الكهف',
                    'narrative' => 'بعثت قريش إلى أحبار يهود بالمدينة تسألهم عما تمتحن به النبي ﷺ، فقالوا: سلوه عن فتية ذهبوا في الدهر الأول، وعن رجل طوّاف بلغ مشارق الأرض ومغاربها، وعن الروح. فنزلت قصة أصحاب الكهف وذي القرنين، وقوله: ﴿وَيَسْأَلُونَكَ عَنِ الرُّوحِ﴾.',
                    'source' => 'تفسير الطبري، أسباب النزول للواحدي، سيرة ابن إسحاق',
                ],
                'en' => [
                    'title' => 'The three questions of Quraysh and the People of the Cave',
                    'narrative' => 'Quraysh sent envoys to the Jewish rabbis of Madinah asking how to test the Prophet ﷺ. They said: "Ask him about young men who vanished in ancient times, about a traveller who reached the east and west of the earth, and about the Spirit." The story of the People of the Cave and Dhul-Qarnayn was then revealed, along with "They ask you about the Spirit."',
                    'source' => 'Tafsir al-Tabari, al-Wahidi: Asbab al-Nuzul, Sirat Ibn Ishaq',
                ],
            ],
            [
                'surah' => 17, 'verse_from' => 1, 'verse_to' => null, 'period' => 'makki', 'event_key' => 'isra-miraj',
                'ar' => [
                    'title' => 'رحلة الإسراء والمعراج',
                    'narrative' => 'نزل صدر سورة الإسراء في معجزة الإسراء، حيث أُسري بالنبي ﷺ ليلاً من المسجد الحرام إلى المسجد الأقصى، ثم عُرج به إلى السماوات العلى، وفُرضت عليه الصلوات الخمس، وعاد من ليلته، فكذّبته قريش وصدّقه أبو بكر فسُمّي الصدّيق.',
                    'source' => 'صحيح البخاري (3887)، صحيح مسلم (164)، السيرة النبوية لابن هشام',
                ],
                'en' => [
                    'title' => 'The night journey and ascension (al-Isra wal-Mi\'raj)',
                    'narrative' => 'The opening of Surah Al-Isra was revealed concerning the miracle of the night journey, when the Prophet ﷺ was taken by night from the Sacred Mosque to al-Aqsa Mosque, then ascended through the heavens where the five daily prayers were ordained. He returned the same night; Quraysh disbelieved him while Abu Bakr affirmed it, earning the title al-Siddiq.',
                    'source' => 'Sahih al-Bukhari (3887), Sahih Muslim (164), Sirat Ibn Hisham',
                ],
            ],
            [
                'surah' => 2, 'verse_from' => 144, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'qibla-change',
                'ar' => [
                    'title' => 'تحويل القبلة إلى المسجد الحرام',
                    'narrative' => 'كان النبي ﷺ يصلي نحو بيت المقدس ستة عشر شهراً بعد الهجرة، وكان يحب أن يُوجَّه إلى الكعبة، ويقلّب وجهه في السماء، فأنزل الله: ﴿قَدْ نَرَى تَقَلُّبَ وَجْهِكَ فِي السَّمَاءِ فَلَنُوَلِّيَنَّكَ قِبْلَةً تَرْضَاهَا﴾، فحُوّلت القبلة إلى المسجد الحرام.',
                    'source' => 'صحيح البخاري (399)، صحيح مسلم (525)',
                ],
                'en' => [
                    'title' => 'The change of the qibla to the Sacred Mosque',
                    'narrative' => 'For sixteen months after the Hijrah the Prophet ﷺ prayed towards Jerusalem, while longing to face the Ka\'bah, turning his face towards the sky. Allah revealed: "We have certainly seen the turning of your face toward the heaven, and We will surely turn you to a qibla with which you will be pleased" — and the qibla was changed to the Sacred Mosque.',
                    'source' => 'Sahih al-Bukhari (399), Sahih Muslim (525)',
                ],
            ],
            [
                'surah' => 8, 'verse_from' => 1, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'anfal-badr-spoils',
                'ar' => [
                    'title' => 'سؤال الصحابة عن غنائم بدر',
                    'narrative' => 'اختلف المسلمون يوم بدر في الغنائم: قال الشبّان الذين قاتلوا: هي لنا، وقال الشيوخ الذين ثبتوا عند الرايات: كنا ردءاً لكم، فسألوا رسول الله ﷺ، فأنزل الله: ﴿يَسْأَلُونَكَ عَنِ الْأَنفَالِ قُلِ الْأَنفَالُ لِلَّهِ وَالرَّسُولِ﴾ فقسمها رسول الله ﷺ بينهم.',
                    'source' => 'مسند أحمد (22762)، سنن أبي داود (2737)، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'The companions asking about the spoils of Badr',
                    'narrative' => 'After the battle of Badr the Muslims disputed over the spoils: the young men who fought claimed them, while the elders who held the standards said "We were your support." They asked the Messenger of Allah ﷺ, and Allah revealed: "They ask you about the spoils of war. Say: the spoils are for Allah and the Messenger" — and he divided them among the Muslims.',
                    'source' => 'Musnad Ahmad (22762), Sunan Abi Dawud (2737), al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 2, 'verse_from' => 187, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'fasting-nights-relief',
                'ar' => [
                    'title' => 'الرخصة في ليالي الصيام',
                    'narrative' => 'كان الصحابة إذا نام أحدهم قبل أن يفطر لم يأكل ليلته ويومه حتى يمسي، وشقّ ذلك عليهم، حتى وقع بعضهم في الحرج، فأنزل الله: ﴿أُحِلَّ لَكُمْ لَيْلَةَ الصِّيَامِ الرَّفَثُ إِلَى نِسَائِكُمْ﴾ إلى قوله: ﴿وَكُلُوا وَاشْرَبُوا حَتَّى يَتَبَيَّنَ لَكُمُ الْخَيْطُ الْأَبْيَضُ مِنَ الْخَيْطِ الْأَسْوَدِ مِنَ الْفَجْرِ﴾، ففرحوا بها فرحاً شديداً.',
                    'source' => 'صحيح البخاري (1915)، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'The concession granted for the nights of fasting',
                    'narrative' => 'In the early period, if a companion slept before breaking his fast, he would not eat until the following evening. This caused great hardship, until Allah revealed: "It has been made permissible for you the night preceding fasting to go to your wives... and eat and drink until the white thread of dawn becomes distinct from the black thread" — and they rejoiced greatly.',
                    'source' => 'Sahih al-Bukhari (1915), al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 3, 'verse_from' => 121, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'uhud-battle-morning',
                'ar' => [
                    'title' => 'غزوة أحد وترتيب الصفوف',
                    'narrative' => 'نزلت هذه الآية والآيات بعدها تصف وقائع غزوة أحد في السنة الثالثة للهجرة، تذكر خروج النبي ﷺ من بيته صباحاً يُبوّئ المؤمنين مقاعد للقتال ويرتب صفوفهم لملاقاة جيش قريش، وما جرى في الغزوة من ابتلاء وتمحيص.',
                    'source' => 'صحيح البخاري (4043)، أسباب النزول للواحدي، تفسير الطبري',
                ],
                'en' => [
                    'title' => 'The battle of Uhud and arranging the ranks',
                    'narrative' => 'This verse and those following it describe the events of the battle of Uhud in the third year after the Hijrah, recalling how the Prophet ﷺ left his home in the morning to position the believers for battle and arrange their ranks to meet the army of Quraysh, and the trials and testing that took place during the battle.',
                    'source' => 'Sahih al-Bukhari (4043), al-Wahidi: Asbab al-Nuzul, Tafsir al-Tabari',
                ],
            ],
            [
                'surah' => 4, 'verse_from' => 95, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'mujahideen-excused-disabled',
                'ar' => [
                    'title' => 'استثناء أولي الضرر من القاعدين',
                    'narrative' => 'لما نزلت ﴿لَا يَسْتَوِي الْقَاعِدُونَ مِنَ الْمُؤْمِنِينَ﴾ جاء عبد الله بن أم مكتوم وهو أعمى فقال: يا رسول الله، لو أستطيع الجهاد لجاهدت، فأنزل الله: ﴿غَيْرُ أُولِي الضَّرَرِ﴾ والنبي ﷺ يُملي على زيد بن ثابت، فألحقها في الآية.',
                    'source' => 'صحيح البخاري (2832)، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'The exemption of the disabled from those who stay behind',
                    'narrative' => 'When "Not equal are those believers who sit at home" was revealed, the blind companion Abdullah ibn Umm Maktum said: "O Messenger of Allah, if I were able to fight I would." Allah then revealed the words "except those with disability" while the Prophet ﷺ was dictating to Zayd ibn Thabit, who appended them to the verse.',
                    'source' => 'Sahih al-Bukhari (2832), al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 33, 'verse_from' => 37, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'zaynab-marriage-adoption',
                'ar' => [
                    'title' => 'زواج النبي ﷺ من زينب بنت جحش وإبطال التبني',
                    'narrative' => 'نزلت في شأن زواج النبي ﷺ من زينب بنت جحش بعد أن طلّقها زيد بن حارثة الذي كان يُدعى زيد بن محمد قبل إبطال التبني: ﴿فَلَمَّا قَضَى زَيْدٌ مِّنْهَا وَطَرًا زَوَّجْنَاكَهَا لِكَيْ لَا يَكُونَ عَلَى الْمُؤْمِنِينَ حَرَجٌ فِي أَزْوَاجِ أَدْعِيَائِهِمْ﴾، فأبطل الله بذلك عادة التبني الجاهلية.',
                    'source' => 'صحيح البخاري (7420)، تفسير ابن كثير',
                ],
                'en' => [
                    'title' => 'The Prophet\'s marriage to Zaynab bint Jahsh and the abolition of adoption',
                    'narrative' => 'This verse was revealed concerning the Prophet\'s marriage to Zaynab bint Jahsh after Zayd ibn Harithah — formerly called Zayd ibn Muhammad before adoption was abolished — divorced her: "So when Zayd had no longer any need for her, We married her to you, so that there be no discomfort upon the believers concerning the wives of their adopted sons." Thus Allah abolished the pre-Islamic custom of adoption.',
                    'source' => 'Sahih al-Bukhari (7420), Tafsir Ibn Kathir',
                ],
            ],
            [
                'surah' => 24, 'verse_from' => 11, 'verse_to' => 20, 'period' => 'madani', 'event_key' => 'ifk-incident-aisha',
                'ar' => [
                    'title' => 'حادثة الإفك وبراءة عائشة رضي الله عنها',
                    'narrative' => 'نزلت هذه الآيات في براءة أم المؤمنين عائشة رضي الله عنها مما رماها به أهل الإفك من المنافقين في أعقاب غزوة بني المصطلق، حين تخلفت عن الجيش في رجوعها فحملها صفوان بن المعطل، فخاض الخائضون، فأنزل الله براءتها من فوق سبع سماوات: ﴿إِنَّ الَّذِينَ جَاءُوا بِالْإِفْكِ عُصْبَةٌ مِّنكُمْ﴾.',
                    'source' => 'صحيح البخاري (4141)، صحيح مسلم (2770)',
                ],
                'en' => [
                    'title' => 'The slander incident and the vindication of Aisha',
                    'narrative' => 'These verses were revealed declaring the innocence of the Mother of the Believers, Aisha, from the slander spread by the hypocrites after the campaign against Banu al-Mustaliq. She had been left behind by the army and was escorted back by Safwan ibn al-Mu\'attal, and the slanderers spread their lie. Allah revealed her vindication from above the seven heavens: "Indeed, those who came with falsehood are a group among you."',
                    'source' => 'Sahih al-Bukhari (4141), Sahih Muslim (2770)',
                ],
            ],
            [
                'surah' => 58, 'verse_from' => 1, 'verse_to' => 4, 'period' => 'madani', 'event_key' => 'mujadila-khawlah-zihar',
                'ar' => [
                    'title' => 'مجادلة خولة بنت ثعلبة وتحريم الظهار',
                    'narrative' => 'ظاهر أوس بن الصامت من امرأته خولة بنت ثعلبة فقال لها: أنتِ عليّ كظهر أمي، فجاءت تشتكي إلى رسول الله ﷺ وتجادله وتقول: إن لي صبية صغاراً، فسمع الله شكواها وأنزل: ﴿قَدْ سَمِعَ اللَّهُ قَوْلَ الَّتِي تُجَادِلُكَ فِي زَوْجِهَا﴾، وشرع كفارة الظهار.',
                    'source' => 'سنن أبي داود (2214)، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'The pleading of Khawlah bint Tha\'labah and the ruling on zihar',
                    'narrative' => 'Aws ibn al-Samit pronounced zihar upon his wife Khawlah bint Tha\'labah, saying "You are to me like my mother\'s back." She came pleading to the Messenger of Allah ﷺ, arguing her case and saying "I have small children." Allah heard her complaint and revealed: "Allah has certainly heard the speech of the one who argues with you concerning her husband" — and legislated the expiation for zihar.',
                    'source' => 'Sunan Abi Dawud (2214), al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 48, 'verse_from' => 1, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'hudaybiyyah-treaty',
                'ar' => [
                    'title' => 'صلح الحديبية والفتح المبين',
                    'narrative' => 'نزلت سورة الفتح على النبي ﷺ في طريق عودته من الحديبية في السنة السادسة للهجرة، بعد الصلح الذي رآه بعض الصحابة ضيماً، فقال ﷺ: "لقد أنزلت عليّ الليلة سورة لهي أحب إليّ مما طلعت عليه الشمس"، ثم قرأ: ﴿إِنَّا فَتَحْنَا لَكَ فَتْحًا مُّبِينًا﴾.',
                    'source' => 'صحيح البخاري (4177)، صحيح مسلم (1786)',
                ],
                'en' => [
                    'title' => 'The treaty of Hudaybiyyah and the clear victory',
                    'narrative' => 'Surah Al-Fath was revealed to the Prophet ﷺ on his way back from Hudaybiyyah in the sixth year after the Hijrah, following the treaty that some companions saw as a setback. He ﷺ said: "Tonight a surah has been revealed to me that is dearer to me than all that the sun rises upon," then recited: "Indeed, We have given you a clear victory."',
                    'source' => 'Sahih al-Bukhari (4177), Sahih Muslim (1786)',
                ],
            ],
            [
                'surah' => 2, 'verse_from' => 158, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'safa-marwah-sai',
                'ar' => [
                    'title' => 'السعي بين الصفا والمروة',
                    'narrative' => 'تحرّج المسلمون من السعي بين الصفا والمروة لما كان عليهما في الجاهلية من الأصنام، فسألت عائشة رضي الله عنها... وأنزل الله: ﴿إِنَّ الصَّفَا وَالْمَرْوَةَ مِن شَعَائِرِ اللَّهِ فَمَنْ حَجَّ الْبَيْتَ أَوِ اعْتَمَرَ فَلَا جُنَاحَ عَلَيْهِ أَن يَطَّوَّفَ بِهِمَا﴾، فبيّن أن السعي بينهما من شعائر الله.',
                    'source' => 'صحيح البخاري (1643)، صحيح مسلم (1277)',
                ],
                'en' => [
                    'title' => 'Walking between Safa and Marwah',
                    'narrative' => 'The Muslims felt hesitant about walking between Safa and Marwah because idols had stood upon them in the pre-Islamic era. Allah then revealed: "Indeed, Safa and Marwah are among the symbols of Allah. So whoever makes Hajj to the House or performs Umrah — there is no blame upon him for walking between them" — clarifying that the sa\'i is among the rites of Allah.',
                    'source' => 'Sahih al-Bukhari (1643), Sahih Muslim (1277)',
                ],
            ],
            [
                'surah' => 5, 'verse_from' => 90, 'verse_to' => 91, 'period' => 'madani', 'event_key' => 'khamr-prohibition',
                'ar' => [
                    'title' => 'تحريم الخمر والميسر',
                    'narrative' => 'كان عمر بن الخطاب رضي الله عنه يقول: اللهم بيّن لنا في الخمر بياناً شافياً، فنزلت آية البقرة ثم آية النساء، حتى نزلت آية المائدة: ﴿إِنَّمَا الْخَمْرُ وَالْمَيْسِرُ وَالْأَنصَابُ وَالْأَزْلَامُ رِجْسٌ مِّنْ عَمَلِ الشَّيْطَانِ فَاجْتَنِبُوهُ﴾، فقال عمر: انتهينا انتهينا.',
                    'source' => 'سنن أبي داود (3670)، سنن الترمذي (3049)، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'The prohibition of wine and gambling',
                    'narrative' => 'Umar ibn al-Khattab used to pray: "O Allah, give us a clear and decisive ruling on wine." The verse of Al-Baqarah was revealed, then that of An-Nisa, until the verse of Al-Ma\'idah came down: "Wine, gambling, idols and divining arrows are but defilement from the work of Satan, so avoid it" — whereupon Umar said: "We have desisted, we have desisted."',
                    'source' => 'Sunan Abi Dawud (3670), Sunan al-Tirmidhi (3049), al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 9, 'verse_from' => 118, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'three-left-behind-tabuk',
                'ar' => [
                    'title' => 'توبة الثلاثة الذين خُلِّفوا عن تبوك',
                    'narrative' => 'تخلّف كعب بن مالك وهلال بن أمية ومرارة بن الربيع عن غزوة تبوك من غير عذر، فأمر النبي ﷺ بهجرهم خمسين ليلة حتى ضاقت عليهم الأرض بما رحبت، ثم أنزل الله توبتهم: ﴿وَعَلَى الثَّلَاثَةِ الَّذِينَ خُلِّفُوا﴾، فكانت من أعظم قصص التوبة والصدق.',
                    'source' => 'صحيح البخاري (4418)، صحيح مسلم (2769)',
                ],
                'en' => [
                    'title' => 'The repentance of the three left behind at Tabuk',
                    'narrative' => 'Ka\'b ibn Malik, Hilal ibn Umayyah and Murarah ibn al-Rabi\' remained behind from the campaign of Tabuk without excuse. The Prophet ﷺ ordered they be shunned for fifty nights until the earth, vast as it is, closed in upon them. Then Allah revealed their acceptance: "And [He also forgave] the three who were left behind" — one of the greatest accounts of repentance and truthfulness.',
                    'source' => 'Sahih al-Bukhari (4418), Sahih Muslim (2769)',
                ],
            ],
            [
                'surah' => 3, 'verse_from' => 1, 'verse_to' => 83, 'period' => 'madani', 'event_key' => 'najran-christians-delegation',
                'ar' => [
                    'title' => 'وفد نصارى نجران',
                    'narrative' => 'قدم وفد نصارى نجران إلى المدينة في السنة التاسعة وجادلوا النبي ﷺ في شأن عيسى عليه السلام، فأنزل الله صدر سورة آل عمران إلى بضع وثمانين آية رداً على حجتهم وبياناً لحقيقة عيسى عليه السلام: ﴿إِنَّ مَثَلَ عِيسَى عِندَ اللَّهِ كَمَثَلِ آدَمَ خَلَقَهُ مِن تُرَابٍ﴾، ودعاهم إلى المباهلة فأبوا وصالحوا.',
                    'source' => 'سيرة ابن إسحاق، تفسير الطبري، تفسير ابن كثير',
                ],
                'en' => [
                    'title' => 'The delegation of the Christians of Najran',
                    'narrative' => 'The Christian delegation of Najran came to Madinah in the ninth year and debated with the Prophet ﷺ concerning Jesus. Allah revealed the opening of Surah Aal-Imran — some eighty verses — answering their arguments and clarifying the truth about Jesus: "Indeed, the example of Jesus to Allah is like that of Adam; He created him from dust." He invited them to mutual imprecation (mubahalah); they declined and made peace.',
                    'source' => 'Sirat Ibn Ishaq, Tafsir al-Tabari, Tafsir Ibn Kathir',
                ],
            ],
            [
                'surah' => 5, 'verse_from' => 3, 'verse_to' => null, 'period' => 'madani', 'event_key' => 'arafah-religion-completed',
                'ar' => [
                    'title' => 'إكمال الدين يوم عرفة في حجة الوداع',
                    'narrative' => 'نزلت يوم الجمعة بعرفة في حجة الوداع سنة عشر: ﴿الْيَوْمَ أَكْمَلْتُ لَكُمْ دِينَكُمْ وَأَتْمَمْتُ عَلَيْكُمْ نِعْمَتِي وَرَضِيتُ لَكُمُ الْإِسْلَامَ دِينًا﴾. قال يهودي لعمر: لو علينا معشر يهود نزلت هذه الآية لاتخذنا ذلك اليوم عيداً، فقال عمر: إنها نزلت في يوم عيدين: يوم جمعة ويوم عرفة.',
                    'source' => 'صحيح البخاري (45)، صحيح مسلم (3017)',
                ],
                'en' => [
                    'title' => 'The completion of the religion on the day of Arafah',
                    'narrative' => 'Revealed on a Friday at Arafah during the Farewell Pilgrimage in the tenth year: "This day I have perfected for you your religion and completed My favour upon you and have approved for you Islam as religion." A Jewish man said to Umar: "Had this verse been revealed to us Jews, we would have taken that day as a festival." Umar replied: "It was revealed on a day of two festivals — a Friday and the day of Arafah."',
                    'source' => 'Sahih al-Bukhari (45), Sahih Muslim (3017)',
                ],
            ],
            [
                'surah' => 2, 'verse_from' => 278, 'verse_to' => 279, 'period' => 'madani', 'event_key' => 'riba-final-prohibition',
                'ar' => [
                    'title' => 'آيات تحريم الربا',
                    'narrative' => 'نزلت في أواخر ما نزل من القرآن، في بقايا ربا الجاهلية، وكان العباس بن عبد المطلب وغيره لهم أموال في الربا، فأنزل الله: ﴿يَا أَيُّهَا الَّذِينَ آمَنُوا اتَّقُوا اللَّهَ وَذَرُوا مَا بَقِيَ مِنَ الرِّبَا﴾، وأعلن النبي ﷺ في حجة الوداع: وربا الجاهلية موضوع، وأول ربا أضع ربانا: ربا العباس بن عبد المطلب.',
                    'source' => 'صحيح مسلم (1218)، تفسير الطبري، أسباب النزول للواحدي',
                ],
                'en' => [
                    'title' => 'The verses prohibiting usury',
                    'narrative' => 'Among the last of the Quran to be revealed, concerning the remaining usury of the pre-Islamic era. Al-Abbas ibn Abd al-Muttalib and others still held usurious claims, so Allah revealed: "O you who believe, fear Allah and give up what remains of usury." In the Farewell Pilgrimage the Prophet ﷺ declared: "The usury of the days of ignorance is abolished, and the first usury I abolish is our own — that of al-Abbas ibn Abd al-Muttalib."',
                    'source' => 'Sahih Muslim (1218), Tafsir al-Tabari, al-Wahidi: Asbab al-Nuzul',
                ],
            ],
            [
                'surah' => 110, 'verse_from' => 1, 'verse_to' => 3, 'period' => 'madani', 'event_key' => 'nasr-victory-farewell',
                'ar' => [
                    'title' => 'سورة النصر ونعي النبي ﷺ',
                    'narrative' => 'نزلت بعد فتح مكة ودخول الناس في دين الله أفواجاً: ﴿إِذَا جَاءَ نَصْرُ اللَّهِ وَالْفَتْحُ﴾. وكان ابن عباس رضي الله عنهما يقول: هي أجل رسول الله ﷺ أعلمه الله له — أي إشارة إلى اقتراب وفاته ﷺ — ووافقه عمر بن الخطاب على هذا الفهم.',
                    'source' => 'صحيح البخاري (4294)، تفسير الطبري',
                ],
                'en' => [
                    'title' => 'Surah An-Nasr and the announcement of the Prophet\'s approaching death',
                    'narrative' => 'Revealed after the conquest of Makkah, when people entered the religion of Allah in multitudes: "When the victory of Allah has come and the conquest." Ibn Abbas said it signalled the approaching end of the Messenger of Allah ﷺ, of which Allah informed him — an understanding Umar ibn al-Khattab affirmed.',
                    'source' => 'Sahih al-Bukhari (4294), Tafsir al-Tabari',
                ],
            ],
        ];
    }
}
