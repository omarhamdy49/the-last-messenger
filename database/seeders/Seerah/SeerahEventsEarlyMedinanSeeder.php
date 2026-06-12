<?php

declare(strict_types=1);

namespace Database\Seeders\Seerah;

/**
 * Batch 4 of 5 — The early Medinan period: from the change of the qiblah to
 * the aftermath of the Confederates (624–627 CE / 2–5 AH).
 */
class SeerahEventsEarlyMedinanSeeder extends SeerahEventSeederBase
{
    protected function events(): array
    {
        return [
            [
                'slug' => 'tahwil-al-qibla',
                'event_type' => 'revelation',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'high',
                'hijri_date' => 'شعبان 2 هـ',
                'hijri_year' => 2,
                'hijri_month' => 8,
                'gregorian_year' => 624,
                'location' => 'مسجد القبلتين بالمدينة المنورة',
                'latitude' => 24.4842000,
                'longitude' => 39.5938000,
                'display_order' => 370,
                'translations' => [
                    'ar' => [
                        'title' => 'تحويل القبلة إلى المسجد الحرام',
                        'description' => 'صلى رسول الله ﷺ بعد مقدمه المدينة نحو بيت المقدس ستة عشر أو سبعة عشر شهرًا، وكان يحب أن يُصرف إلى الكعبة قبلة أبيه إبراهيم عليه السلام، فكان يكثر النظر إلى السماء رجاءَ أن ينزل الوحي بذلك، فأنزل الله تعالى: ﴿قَدْ نَرَى تَقَلُّبَ وَجْهِكَ فِي السَّمَاءِ فَلَنُوَلِّيَنَّكَ قِبْلَةً تَرْضَاهَا فَوَلِّ وَجْهَكَ شَطْرَ الْمَسْجِدِ الْحَرَامِ وَحَيْثُ مَا كُنتُمْ فَوَلُّوا وُجُوهَكُمْ شَطْرَهُ﴾. وكان ذلك في شعبان من السنة الثانية للهجرة، والنبي ﷺ — على المشهور — في مسجد بني سلمة وقد صلى بالناس ركعتين من الظهر، فاستدار في صلاته واستدارت معه الصفوف نحو الكعبة، فسمي ذلك المسجد «مسجد القبلتين». ومرّ رجل بقوم من الأنصار في صلاة العصر فقال: أشهد لقد صليت مع رسول الله ﷺ قبل مكة، فداروا كما هم نحو الكعبة. وقد جعل الله هذا التحويل فتنةً وتمحيصًا؛ فقال السفهاء من اليهود والمنافقين: ما ولّاهم عن قبلتهم التي كانوا عليها؟ فأنزل الله: ﴿قُل لِّلَّهِ الْمَشْرِقُ وَالْمَغْرِبُ يَهْدِي مَن يَشَاءُ إِلَى صِرَاطٍ مُّسْتَقِيمٍ﴾، وبيّن أنه ما جعل القبلة الأولى إلا ليعلم من يتبع الرسول ممن ينقلب على عقبيه. وكان في تحويل القبلة إعلان لاستقلال الأمة المسلمة بشخصيتها وشعائرها، وربطٌ لها بالبيت العتيق أول بيت وُضع للناس، وتمهيد رمزي لعودة المسلمين إلى مكة فاتحين.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Change of the Qiblah to the Sacred Mosque',
                        'description' => 'After arriving in Medina, the Prophet ﷺ prayed toward Jerusalem for sixteen or seventeen months, while longing to face the Kaaba, the qiblah of his forefather Ibrahim. He would turn his face toward the sky in hope, until God revealed: "We have certainly seen the turning of your face toward the heaven, and We will surely turn you to a qiblah you will be pleased with. So turn your face toward the Sacred Mosque, and wherever you are, turn your faces toward it" (al-Baqarah 144). This occurred in Sha\'ban of the second year after the Hijra. According to the well-known account, he ﷺ was leading the noon prayer in the mosque of Banu Salamah and had completed two units when the command came; he turned within the prayer, and the rows turned with him toward the Kaaba — hence the mosque\'s name, "Mosque of the Two Qiblahs." A man passed some Ansar at the afternoon prayer and testified he had prayed with the Prophet ﷺ facing Mecca, so they too rotated as they stood. God made the change a test: the foolish among the Jews and hypocrites scoffed, "What has turned them from their former qiblah?" — and God answered: "To God belong the east and the west; He guides whom He wills to a straight path." The change proclaimed the Muslim community\'s independent identity and rites, bound it to the Ancient House — the first house established for humanity — and symbolically heralded the Muslims\' eventual return to Mecca.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-badr',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => '17 رمضان 2 هـ',
                'hijri_year' => 2,
                'hijri_month' => 9,
                'hijri_day' => 17,
                'gregorian_date' => '624-03-13',
                'gregorian_year' => 624,
                'location' => 'بدر جنوب غرب المدينة',
                'latitude' => 23.7800000,
                'longitude' => 38.7906000,
                'statistics' => [
                    'muslim_forces' => 313,
                    'enemy_forces' => 950,
                    'muslim_martyrs' => 14,
                    'enemy_casualties' => 70,
                    'prisoners' => 70,
                    'duration_days' => 1,
                ],
                'display_order' => 380,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة بدر الكبرى (يوم الفرقان)',
                        'description' => 'في السابع عشر من رمضان سنة اثنتين للهجرة وقعت أول معركة فاصلة في تاريخ الإسلام. خرج رسول الله ﷺ في ثلاثمائة وبضعة عشر رجلًا يعترضون عير قريش العائدة من الشام بقيادة أبي سفيان، فأفلتت العير، وجاء النفير من مكة في نحو ألف مقاتل بقيادة أبي جهل مصرّين على القتال. فاستشار ﷺ أصحابه، فقال المقداد: لا نقول لك كما قال قوم موسى اذهب أنت وربك فقاتلا، وقال سعد بن معاذ عن الأنصار: امضِ بنا حيث شئت، فلو استعرضت بنا هذا البحر فخضته لخضناه معك. فسبق المسلمون إلى ماء بدر، وبنى لهم سعد عريشًا للنبي ﷺ، فبات ليلته يصلي ويدعو: «اللهم إن تهلك هذه العصابة لا تُعبد في الأرض»، حتى سقط رداؤه وقال له أبو بكر: كفاك مناشدتك ربك. وأمد الله المؤمنين بألف من الملائكة مردفين، وألقى النعاس عليهم أمنة وأنزل عليهم المطر تطهيرًا وتثبيتًا. وبدأت المعركة بمبارزة قُتل فيها صناديد قريش عتبة وشيبة ابنا ربيعة والوليد بن عتبة على يد حمزة وعلي وعبيدة بن الحارث، ثم رمى ﷺ القوم بحفنة من حصباء وقال: «شاهت الوجوه»، فانهزم المشركون هزيمة منكرة؛ قُتل منهم سبعون فيهم أبو جهل وأمية بن خلف، وأُسر سبعون، واستشهد من المسلمين أربعة عشر. وسمى الله هذا اليوم «يوم الفرقان» إذ فرّق بين الحق والباطل وأعزّ الإسلام وأهله: ﴿وَلَقَدْ نَصَرَكُمُ اللَّهُ بِبَدْرٍ وَأَنتُمْ أَذِلَّةٌ﴾.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Battle of Badr (The Day of the Criterion)',
                        'description' => 'On 17 Ramadan, 2 AH (13 March 624), the first decisive battle in Islamic history took place. The Prophet ﷺ marched out with just over three hundred men to intercept the Quraysh caravan returning from Syria under Abu Sufyan. The caravan escaped, but a relief army of nearly one thousand warriors marched from Mecca under Abu Jahl, determined to fight. The Prophet ﷺ consulted his companions: al-Miqdad declared, "We will not say as the people of Musa said — go, you and your Lord, and fight"; and Sa\'d ibn Mu\'adh spoke for the Ansar: "Lead us where you will — were you to plunge into this sea, we would plunge in with you." The Muslims reached the wells of Badr first, and a shelter was built for the Prophet ﷺ, who spent the night in prayer: "O God, if this band perishes, You will not be worshipped on earth," until his cloak slipped and Abu Bakr consoled him. God reinforced the believers with a thousand angels rank upon rank, cast drowsiness over them as security, and sent rain to purify and steady them. The battle opened with duels in which Hamza, Ali, and Ubaydah slew the champions Utbah, Shaybah, and al-Walid. The Prophet ﷺ cast a handful of pebbles, saying "May the faces be disfigured!" — and Quraysh broke in rout: seventy killed, including Abu Jahl and Umayyah ibn Khalaf, seventy captured, against fourteen Muslim martyrs. God named it the Day of the Criterion: "God already gave you victory at Badr while you were few" (Al Imran 123).',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'الخروج لاعتراض العير', 'description' => 'خرج ﷺ في ثلاثمائة وبضعة عشر رجلًا لاعتراض عير قريش، فأفلتت العير وأقبل النفير في نحو ألف مقاتل.'],
                        'en' => ['title' => 'March to intercept the caravan', 'description' => 'He ﷺ set out with some 313 men to intercept the Quraysh caravan; it escaped, but an army of one thousand advanced instead.'],
                    ],
                    [
                        'ar' => ['title' => 'الشورى وموقف الأنصار', 'description' => 'استشار ﷺ أصحابه فقال سعد بن معاذ: امضِ بنا حيث شئت، فلو خضت بنا هذا البحر لخضناه معك.'],
                        'en' => ['title' => 'Consultation and the Ansar\'s resolve', 'description' => 'Sa\'d ibn Mu\'adh pledged: "Lead us where you will — were you to plunge into this sea, we would plunge in with you."'],
                    ],
                    [
                        'ar' => ['title' => 'ليلة الدعاء في العريش', 'description' => 'بات ﷺ يناشد ربه: اللهم إن تهلك هذه العصابة لا تُعبد في الأرض، حتى سقط رداؤه عن منكبيه.'],
                        'en' => ['title' => 'The night of supplication', 'description' => 'He ﷺ pleaded through the night: "O God, if this band perishes, You will not be worshipped on earth."'],
                    ],
                    [
                        'ar' => ['title' => 'المبارزة ومصرع صناديد قريش', 'description' => 'برز حمزة وعلي وعبيدة لعتبة وشيبة والوليد فقتلوهم، وكانت فاتحة النصر.'],
                        'en' => ['title' => 'The duels', 'description' => 'Hamza, Ali, and Ubaydah slew Quraysh\'s champions Utbah, Shaybah, and al-Walid in single combat, opening the victory.'],
                    ],
                    [
                        'ar' => ['title' => 'مدد الملائكة والنصر المبين', 'description' => 'أمدّ الله المؤمنين بألف من الملائكة مردفين، فانهزمت قريش وقُتل سبعون من صناديدها فيهم أبو جهل.'],
                        'en' => ['title' => 'Angelic reinforcement and triumph', 'description' => 'God reinforced the believers with a thousand angels; Quraysh broke, losing seventy chiefs including Abu Jahl.'],
                    ],
                    [
                        'ar' => ['title' => 'الأسرى وفداؤهم', 'description' => 'أُسر سبعون من قريش، فقبل ﷺ الفداء، وجعل فداء بعضهم تعليم عشرة من غلمان المدينة الكتابة.'],
                        'en' => ['title' => 'The prisoners and their ransom', 'description' => 'Seventy Quraysh were captured; some ransomed themselves by teaching ten children of Medina to read and write.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'قائد المسلمين يوم بدر، بات ليلته يدعو ربه حتى نزل النصر.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Commander of the Muslims at Badr, who spent the night in supplication until victory descended.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'حمزة بن عبد المطلب', 'description' => 'أسد الله، أبلى يوم بدر بلاءً حسنًا وقتل شيبة بن ربيعة في المبارزة.'],
                            'en' => ['name' => 'Hamza ibn Abdul-Muttalib', 'description' => 'The Lion of God, who excelled at Badr and slew Shaybah ibn Rabi\'ah in the opening duel.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'علي بن أبي طالب', 'description' => 'قتل الوليد بن عتبة في مبارزة بدر وكان من أبطال اليوم.'],
                            'en' => ['name' => 'Ali ibn Abi Talib', 'description' => 'Slew al-Walid ibn Utbah in the duel at Badr and was among the heroes of the day.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'سعد بن معاذ', 'description' => 'سيد الأوس، قال كلمة الأنصار الفاصلة قبل المعركة وبنى العريش للنبي ﷺ.'],
                            'en' => ['name' => 'Sa\'d ibn Mu\'adh', 'description' => 'Chief of the Aws, who voiced the Ansar\'s decisive pledge and built the Prophet\'s shelter.'],
                        ],
                    ],
                    [
                        'role' => 'enemy',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو جهل عمرو بن هشام', 'description' => 'قائد جيش قريش يوم بدر، قُتل في المعركة وكان أشد الناس عداوة للإسلام.'],
                            'en' => ['name' => 'Abu Jahl (Amr ibn Hisham)', 'description' => 'Commander of the Quraysh army at Badr, slain in the battle — Islam\'s fiercest enemy.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'fard-siyam-ramadan',
                'event_type' => 'revelation',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => 'شعبان 2 هـ',
                'hijri_year' => 2,
                'hijri_month' => 8,
                'gregorian_year' => 624,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 390,
                'translations' => [
                    'ar' => [
                        'title' => 'فرض صيام رمضان وزكاة الفطر',
                        'description' => 'في شعبان من السنة الثانية للهجرة فرض الله على المسلمين صيام شهر رمضان، فأنزل قوله تعالى: ﴿يَا أَيُّهَا الَّذِينَ آمَنُوا كُتِبَ عَلَيْكُمُ الصِّيَامُ كَمَا كُتِبَ عَلَى الَّذِينَ مِن قَبْلِكُمْ لَعَلَّكُمْ تَتَّقُونَ﴾ إلى قوله: ﴿شَهْرُ رَمَضَانَ الَّذِي أُنزِلَ فِيهِ الْقُرْآنُ هُدًى لِّلنَّاسِ وَبَيِّنَاتٍ مِّنَ الْهُدَى وَالْفُرْقَانِ فَمَن شَهِدَ مِنكُمُ الشَّهْرَ فَلْيَصُمْهُ﴾. وقد مرّ تشريع الصيام بتدرج حكيم يراعي النفوس؛ فقد كان المسلمون أولَ الأمر مخيّرين بين الصيام والفدية بإطعام مسكين، ثم نُسخ التخيير وتعيّن الصوم على القادر المقيم الصحيح، ورُخّص للمريض والمسافر الفطر والقضاء، وللشيخ الكبير الفدية. وكان هذا الفرض ثالثَ أركان الإسلام العملية بعد الصلاة والزكاة، ومدرسةً سنوية للتقوى وضبط النفس ومواساة الفقراء. وفي السنة نفسها شرع رسول الله ﷺ زكاة الفطر طهرةً للصائم من اللغو والرفث وطعمةً للمساكين، صاعًا من تمر أو شعير عن كل مسلم صغير أو كبير حر أو عبد، وصلى ﷺ بالمسلمين أول صلاة عيد فطر في تاريخ الإسلام. ومن لطائف القدر أن أول رمضان فرض فيه الصيام كان هو نفسه رمضان الذي وقعت في وسطه غزوة بدر الكبرى؛ فجمع المسلمون بين جهاد النفس بالصوم وجهاد العدو بالسيف، وانتصروا في الميدانين معًا.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Ordinance of Fasting Ramadan and Zakat al-Fitr',
                        'description' => 'In Sha\'ban of the second year after the Hijra, God ordained the fast of Ramadan: "O you who believe, fasting has been prescribed for you as it was prescribed for those before you, that you may attain piety... The month of Ramadan in which the Quran was sent down as guidance for humanity — so whoever witnesses the month shall fast it" (al-Baqarah 183-185). The legislation unfolded with wise gradualism: at first Muslims could choose between fasting and feeding a poor person in compensation; then the option was abrogated and fasting became binding on the healthy resident, with concessions for the sick and the traveler to make up the days, and compensation for the aged. The fast became the third practical pillar of Islam after prayer and almsgiving — an annual school of God-consciousness, self-restraint, and compassion for the poor. In the same year the Prophet ﷺ instituted Zakat al-Fitr — a measure of dates or barley for every Muslim, young or old, free or bonded — as purification for the fasting person and food for the needy, and he led the first Eid al-Fitr prayer in Islamic history. By a remarkable decree of providence, the very first obligatory Ramadan was the month in which the Battle of Badr was fought: the Muslims combined the struggle against the self through fasting with the struggle on the battlefield — and triumphed in both.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-bani-qaynuqa',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => 'شوال 2 هـ',
                'hijri_year' => 2,
                'hijri_month' => 10,
                'gregorian_year' => 624,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'statistics' => [
                    'duration_days' => 15,
                ],
                'display_order' => 400,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة بني قينقاع',
                        'description' => 'كان بنو قينقاع أول من نقض العهد من يهود المدينة بعد وثيقة الصحيفة، وكانوا أشجع يهود المدينة وأهل صياغة وتجارة، وسوقهم من أشهر أسواقها. فلما انتصر المسلمون في بدر أظهروا الحسد والبغي وأخذوا يؤذون المسلمين ويتطاولون، فجمعهم رسول الله ﷺ في سوقهم ووعظهم وحذرهم أن يصيبهم ما أصاب قريشًا، فقالوا في غرور: يا محمد، لا يغرنّك أنك لقيت قومًا لا علم لهم بالحرب فأصبت منهم فرصة؛ إنا والله لئن حاربتنا لتعلمنّ أنا نحن الناس! فأنزل الله: ﴿قُل لِّلَّذِينَ كَفَرُوا سَتُغْلَبُونَ وَتُحْشَرُونَ إِلَى جَهَنَّمَ﴾. ثم كان الحادث الذي فجّر الموقف؛ إذ عمد رجل منهم إلى امرأة مسلمة جاءت تبيع في سوقهم فعقد طرف ثوبها وهي لا تشعر، فلما قامت انكشفت عورتها فضحكوا منها، فصاحت، فوثب رجل من المسلمين على الصائغ فقتله، فاجتمعوا على المسلم فقتلوه، فكان ذلك إعلان نقض العهد. فسار إليهم رسول الله ﷺ في شوال من السنة الثانية فتحصنوا في حصونهم، فحاصرهم خمس عشرة ليلة حتى قذف الله في قلوبهم الرعب فنزلوا على حكمه. وقام عبد الله بن أُبيّ بن سلول رأس المنافقين — وكانوا حلفاءه — يلحّ على النبي ﷺ في شأنهم، فوهبهم له وأمرهم أن يجلوا عن المدينة، فخرجوا إلى أذرعات بالشام، وخلصت المدينة من أول بؤر الغدر فيها.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Expedition against Banu Qaynuqa',
                        'description' => 'Banu Qaynuqa were the first of Medina\'s Jewish tribes to break the covenant of the Charter. The boldest of the city\'s Jews, they were smiths and merchants whose market was among Medina\'s most famous. After the Muslim victory at Badr they grew openly envious and hostile. The Prophet ﷺ gathered and admonished them in their market, warning them against the fate of Quraysh, but they answered with arrogance: "Muhammad, do not be deluded by having met a people ignorant of war. By God, if you fight us, you will learn that we are the real men!" — whereupon God revealed: "Say to those who disbelieve: you will be overcome and gathered into Hell" (Al Imran 12). Then came the incident that ignited the conflict: a goldsmith of theirs pinned the hem of a Muslim woman\'s garment as she sat in their market, so that when she rose she was exposed, and they laughed at her. She cried out; a Muslim man leapt upon the goldsmith and killed him, and they in turn killed the Muslim — an open declaration of war on the covenant. In Shawwal of 2 AH the Prophet ﷺ marched against them; they shut themselves in their fortresses, and he besieged them fifteen nights until God cast terror into their hearts and they surrendered to his judgment. Abdullah ibn Ubayy, head of the hypocrites and their old ally, importuned the Prophet ﷺ on their behalf, so he spared them and ordered their exile; they departed to Adhri\'at in Syria, ridding Medina of its first nest of treachery.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'zawaj-ali-wa-fatima',
                'event_type' => 'marriage',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => '2 هـ',
                'hijri_year' => 2,
                'gregorian_year' => 624,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 410,
                'translations' => [
                    'ar' => [
                        'title' => 'زواج علي بن أبي طالب وفاطمة الزهراء',
                        'description' => 'في السنة الثانية للهجرة تزوج علي بن أبي طالب فاطمةَ الزهراء بنت رسول الله ﷺ سيدة نساء العالمين، وكانت أحب بناته إليه وأشبههنّ به هديًا وسمتًا. وقد خطبها قبله أبو بكر ثم عمر فردّهما النبي ﷺ ردًّا جميلًا، فلما تقدم علي — وكان ربيبه نشأ في بيته — سأله ﷺ: «هل عندك من شيء؟» قال: ما عندي يا رسول الله إلا فرسي ودرعي، فأمره أن يبيع درعه الحُطمية، فباعها — واشتراها منه عثمان بن عفان — وجاء بثمنها إلى رسول الله ﷺ فجعله مهرها وأمر أن يُجهَّز به بيتهما، فكان جهاز سيدة نساء أهل الجنة من أبسط ما يكون: خميلة ووسادة من أدم حشوها ليف وقربة ورحى وجرّتان. وأولم علي وليمة متواضعة، ودعا لهما النبي ﷺ بدعائه المبارك ونضح عليهما من الماء وقال: «اللهم بارك فيهما وبارك عليهما وبارك لهما في نسلهما». وكان هذا الزواج المبارك أنموذجًا نبويًّا في الزهد والبساطة وبُعد التكاليف عن روح الشرع، ومنه جاءت ذرية النبي ﷺ؛ فولدت فاطمة الحسن والحسين سيدي شباب أهل الجنة وزينب وأم كلثوم، وانحصر نسل رسول الله ﷺ في عقبها، فكل شريف من آل البيت يرجع نسبه إلى هذا البيت الطاهر الذي قام على التقوى والمودة.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Marriage of Ali and Fatimah al-Zahra',
                        'description' => 'In the second year after the Hijra, Ali ibn Abi Talib married Fatimah al-Zahra, daughter of the Messenger of God ﷺ and mistress of the women of the worlds — the dearest of his daughters and the most like him in bearing and character. Abu Bakr and then Umar had sought her hand, and the Prophet ﷺ gently declined; when Ali — who had grown up in the Prophet\'s own household — came forward, the Prophet ﷺ asked: "Do you possess anything?" He answered: "Nothing, O Messenger of God, but my horse and my coat of mail." The Prophet ﷺ instructed him to sell the mail; Uthman ibn Affan bought it, and its price became the dowry and furnished their home. The trousseau of the mistress of the women of Paradise was of the utmost simplicity: a velvet cloth, a leather pillow stuffed with palm fiber, a waterskin, a handmill, and two jars. Ali gave a modest wedding feast, and the Prophet ﷺ blessed the couple, sprinkling water over them and praying: "O God, bless them both, bless what is between them, and bless their offspring." This blessed marriage stands as the prophetic model of simplicity and freedom from burdensome custom. From it came the Prophet\'s progeny: Fatimah bore al-Hasan and al-Husayn — the masters of the youth of Paradise — and Zaynab and Umm Kulthum, and the Prophet\'s lineage continued exclusively through her descendants.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'علي بن أبي طالب', 'description' => 'ابن عم النبي ﷺ وربيبه، تزوج فاطمة بمهر درعه الحطمية.'],
                            'en' => ['name' => 'Ali ibn Abi Talib', 'description' => 'The Prophet\'s cousin, raised in his household, who married Fatimah with the price of his coat of mail as dowry.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'فاطمة الزهراء', 'description' => 'بنت رسول الله ﷺ وسيدة نساء العالمين، أم الحسن والحسين.'],
                            'en' => ['name' => 'Fatimah al-Zahra', 'description' => 'Daughter of the Messenger of God ﷺ, mistress of the women of the worlds, mother of al-Hasan and al-Husayn.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-uhud',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => '7 شوال 3 هـ',
                'hijri_year' => 3,
                'hijri_month' => 10,
                'hijri_day' => 7,
                'gregorian_date' => '625-03-23',
                'gregorian_year' => 625,
                'location' => 'جبل أحد شمال المدينة المنورة',
                'latitude' => 24.5085000,
                'longitude' => 39.6155000,
                'statistics' => [
                    'muslim_forces' => 700,
                    'enemy_forces' => 3000,
                    'muslim_martyrs' => 70,
                    'enemy_casualties' => 22,
                    'duration_days' => 1,
                ],
                'display_order' => 420,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة أحد',
                        'description' => 'خرجت قريش في شوال من السنة الثالثة للهجرة في ثلاثة آلاف مقاتل بقيادة أبي سفيان تطلب ثأر بدر، ومعهم النساء يضربن الدفوف ويحرّضن، حتى نزلوا قرب جبل أحد. فاستشار رسول الله ﷺ أصحابه: أيتحصنون بالمدينة أم يخرجون إليهم؟ وكان رأيه البقاء، لكن شبابًا فاتهم بدر تحمسوا للخروج، فلبس ﷺ لأْمَته — وما كان لنبي لبس لأمته أن يضعها حتى يحكم الله — وخرج في ألف، فانخذل عبد الله بن أبي بثلث الجيش رجوعًا، فبقي ﷺ في سبعمائة. ووضع خمسين راميًا على جبل الرماة بقيادة عبد الله بن جبير وأمرهم: «لا تبرحوا وإن رأيتمونا تخطفنا الطير». وبدأت المعركة بنصر للمسلمين حتى انكشف المشركون عن معسكرهم، فلما رأى الرماة الغنيمة ترك أكثرُهم الجبل ظنًّا أن المعركة انتهت، فدار خالد بن الوليد — وكان يومئذ على شرك — بخيل المشركين من خلف الجبل فأطبق على المسلمين، وانقلب النصر محنةً؛ استشهد سبعون من خيار الصحابة فيهم حمزة بن عبد المطلب — مثّلت به هند ولاكت كبده — ومصعب بن عمير حامل اللواء وأنس بن النضر وسعد بن الربيع، وشُجّ وجه رسول الله ﷺ وكُسرت رَباعيته ودخلت حلقتا المغفر في وجنته، وصاح الشيطان: قُتل محمد، حتى ثبّت اللهُ المؤمنين. ودافع عنه نفرٌ أبلوا بلاءً عظيمًا منهم طلحة بن عبيد الله — شُلّت يده دونه — وأبو دجانة وأم عمارة نسيبة بنت كعب. وأنزل الله في أحد ستين آية من آل عمران فيها العتاب والتمحيص والدروس: ﴿وَلَا تَهِنُوا وَلَا تَحْزَنُوا وَأَنتُمُ الْأَعْلَوْنَ إِن كُنتُم مُّؤْمِنِينَ﴾، فكانت أحد مدرسة الأمة الكبرى في ثمن مخالفة القيادة وفي الثبات عند المحن.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Battle of Uhud',
                        'description' => 'In Shawwal of 3 AH, Quraysh marched with three thousand warriors under Abu Sufyan to avenge Badr, their women beating tambourines to spur them on, and encamped near Mount Uhud. The Prophet ﷺ consulted his companions on whether to defend from within Medina — his own preference — or march out; the eager young men who had missed Badr prevailed. He donned his armor — and a prophet who has put on his armor does not lay it down until God judges — and marched with a thousand men, of whom Abdullah ibn Ubayy deserted with a third, leaving seven hundred. He posted fifty archers on the hill under Abdullah ibn Jubayr with the strict command: "Do not leave your position, even if you see the birds snatching us." The battle opened with a Muslim victory; the enemy fled their camp. But seeing the spoils, most archers abandoned the hill thinking the fight was won — and Khalid ibn al-Walid, then still a polytheist, swept the cavalry around the mountain and fell upon the Muslims from behind. Triumph turned to ordeal: seventy of the finest companions fell, among them Hamza — whose body Hind mutilated — Mus\'ab ibn Umayr the standard-bearer, Anas ibn al-Nadr, and Sa\'d ibn al-Rabi\'. The Prophet\'s ﷺ face was gashed, his tooth broken, helmet rings driven into his cheek, while Satan cried "Muhammad is slain!" Heroes shielded him — Talhah whose hand was paralyzed defending him, Abu Dujanah, and Nusaybah bint Ka\'b. God revealed sixty verses of Al Imran about Uhud, full of admonition and lessons: "Do not weaken and do not grieve, for you will be superior if you are believers." Uhud became the Ummah\'s great school on the price of disobeying command and on steadfastness in trial.',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'الشورى والخروج من المدينة', 'description' => 'استشار ﷺ أصحابه فمال الشباب إلى الخروج، فلبس لأمته وخرج في ألف، وانخذل ابن أبي بثلث الجيش.'],
                        'en' => ['title' => 'Consultation and the march out', 'description' => 'After consultation the army marched out a thousand strong; Abdullah ibn Ubayy deserted with a third of the force.'],
                    ],
                    [
                        'ar' => ['title' => 'وصية الرماة على الجبل', 'description' => 'وضع ﷺ خمسين راميًا على الجبل وأمرهم ألا يبرحوا مكانهم وإن رأوا الطير تخطف المسلمين.'],
                        'en' => ['title' => 'The archers\' orders', 'description' => 'Fifty archers were posted on the hill with strict orders never to leave, "even if you see the birds snatching us."'],
                    ],
                    [
                        'ar' => ['title' => 'النصر الأول وانكشاف المشركين', 'description' => 'حمل المسلمون حملة صادقة فانهزم المشركون أول النهار وتركوا معسكرهم وغنائمهم.'],
                        'en' => ['title' => 'The initial victory', 'description' => 'The Muslims\' first charge broke the polytheists, who fled leaving their camp and spoils behind.'],
                    ],
                    [
                        'ar' => ['title' => 'مخالفة الرماة والتفاف خالد', 'description' => 'نزل أكثر الرماة للغنيمة، فدار خالد بن الوليد بالخيل من خلف الجبل وأطبق على المسلمين.'],
                        'en' => ['title' => 'The archers descend; Khalid\'s flanking charge', 'description' => 'Most archers left for the spoils; Khalid ibn al-Walid swept the cavalry around the mountain and struck from the rear.'],
                    ],
                    [
                        'ar' => ['title' => 'استشهاد حمزة وسبعين من الصحابة', 'description' => 'استشهد سبعون من خيار الصحابة فيهم حمزة سيد الشهداء ومصعب بن عمير حامل اللواء.'],
                        'en' => ['title' => 'The martyrdom of Hamza and seventy companions', 'description' => 'Seventy of the finest companions fell, among them Hamza, Master of Martyrs, and the standard-bearer Mus\'ab ibn Umayr.'],
                    ],
                    [
                        'ar' => ['title' => 'ثبات النبي ﷺ ومن حوله', 'description' => 'شُجّ وجهه ﷺ وكسرت رباعيته وثبت يقاتل، ودافع عنه طلحة وأبو دجانة وأم عمارة حتى انحاز إلى الجبل.'],
                        'en' => ['title' => 'The Prophet\'s steadfastness', 'description' => 'Wounded in the face, he ﷺ stood firm while Talhah, Abu Dujanah, and Umm Umarah shielded him until the Muslims regrouped on the mountain.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'قاد المسلمين يوم أحد وثبت حين انكشفوا، وشُجّ وجهه الشريف وكسرت رباعيته.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Led the Muslims at Uhud and stood firm when the lines broke, suffering wounds to his face.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'حمزة بن عبد المطلب', 'description' => 'سيد الشهداء، استشهد يوم أحد على يد وحشي ومثّلت به هند بنت عتبة.'],
                            'en' => ['name' => 'Hamza ibn Abdul-Muttalib', 'description' => 'The Master of Martyrs, slain at Uhud by Wahshi; his body was mutilated by Hind bint Utbah.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'مصعب بن عمير', 'description' => 'حامل لواء المسلمين يوم أحد، قاتل حتى قُطعت يداه واستشهد دون رسول الله ﷺ.'],
                            'en' => ['name' => 'Mus\'ab ibn Umayr', 'description' => 'Standard-bearer of the Muslims at Uhud, who fought until both hands were severed and died defending the Prophet ﷺ.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'نسيبة بنت كعب (أم عمارة)', 'description' => 'قاتلت يوم أحد دون رسول الله ﷺ بالسيف والقوس حتى جُرحت اثني عشر جرحًا.'],
                            'en' => ['name' => 'Nusaybah bint Ka\'b (Umm Umarah)', 'description' => 'Fought at Uhud in direct defense of the Prophet ﷺ with sword and bow, sustaining twelve wounds.'],
                        ],
                    ],
                    [
                        'role' => 'enemy',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو سفيان بن حرب', 'description' => 'قائد قريش يوم أحد طلبًا لثأر بدر، أسلم بعد ذلك عام الفتح.'],
                            'en' => ['name' => 'Abu Sufyan ibn Harb', 'description' => 'Commander of Quraysh at Uhud, seeking revenge for Badr; he later embraced Islam at the Conquest of Mecca.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-hamra-al-asad',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => '8 شوال 3 هـ',
                'hijri_year' => 3,
                'hijri_month' => 10,
                'hijri_day' => 8,
                'gregorian_year' => 625,
                'location' => 'حمراء الأسد جنوب المدينة',
                'latitude' => 24.3500000,
                'longitude' => 39.5700000,
                'statistics' => [
                    'muslim_forces' => 630,
                    'duration_days' => 3,
                ],
                'display_order' => 430,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة حمراء الأسد',
                        'description' => 'في صبيحة اليوم التالي لأحد مباشرة، والجراح لم تجف بعد، أذّن مؤذن رسول الله ﷺ في الناس بطلب العدو، وأعلن ألا يخرج معه إلا من شهد القتال بالأمس، فخرج المسلمون على ما بهم من القرح والإعياء، استجابةً لله وللرسول، حتى بلغوا حمراء الأسد على نحو ثمانية أميال من المدينة. وكانت قريش في طريق عودتها قد تلاومت؛ قال بعضهم لبعض: لا محمدًا قتلتم ولا الكواعب أردفتم، أصبتم القوم ثم تركتموهم وقد بقي منهم رؤوس يجمعون لكم، فهمّوا بالرجوع لاستئصال المدينة. فمرّ بهم معبد بن أبي معبد الخزاعي — وخزاعة عيبة نصح لرسول الله ﷺ — وقد رأى عسكر المسلمين بحمراء الأسد، فقال لأبي سفيان: إن محمدًا قد خرج في أصحابه يطلبكم في جمع لم أر مثله قط، يتحرّقون عليكم تحرّقًا، قد اجتمع معه من كان تخلف عنه! فكسر ذلك من عزم قريش وقذف الله الرعب في قلوبهم فانصرفوا إلى مكة مسرعين. وأقام رسول الله ﷺ بحمراء الأسد ثلاثة أيام يوقد النيران ليُرى مكانه ثم رجع. وأنزل الله في أهل هذه الغزوة: ﴿الَّذِينَ اسْتَجَابُوا لِلَّهِ وَالرَّسُولِ مِن بَعْدِ مَا أَصَابَهُمُ الْقَرْحُ لِلَّذِينَ أَحْسَنُوا مِنْهُمْ وَاتَّقَوْا أَجْرٌ عَظِيمٌ﴾. فكانت حمراء الأسد ضربة معنوية استعاد بها المسلمون هيبتهم غداة أحد، وأثبتت أن جراح الأمس لم تنل من عزيمتهم شيئًا.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Expedition of Hamra al-Asad',
                        'description' => 'On the very morning after Uhud, with wounds still fresh, the Prophet\'s ﷺ crier summoned the people to pursue the enemy — and only those who had fought the previous day were permitted to march. Despite their injuries and exhaustion, the Muslims responded to God and His Messenger, advancing to Hamra al-Asad, some eight miles from Medina. Quraysh, on their homeward road, had begun to reproach one another: "You killed neither Muhammad nor captured the maidens; you struck them and then left while their leaders survive to gather against you" — and they contemplated returning to wipe out Medina. Then Ma\'bad ibn Abi Ma\'bad al-Khuza\'i — Khuza\'ah being trusted well-wishers of the Prophet ﷺ — who had seen the Muslim camp, told Abu Sufyan: "Muhammad has come out after you with a host the like of which I have never seen, burning with fury against you; all who stayed behind have joined him!" This shattered Quraysh\'s resolve; God cast terror into their hearts and they hurried back to Mecca. The Prophet ﷺ remained at Hamra al-Asad three days, lighting great fires to mark his presence, then returned. Of these believers God revealed: "Those who responded to God and the Messenger after injury had struck them — for those among them who did good and feared God is a great reward" (Al Imran 172). Hamra al-Asad restored Muslim prestige the morning after Uhud, proving their resolve undiminished.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'al-raji-wa-bir-mauna',
                'event_type' => 'delegation',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => 'صفر 4 هـ',
                'hijri_year' => 4,
                'hijri_month' => 2,
                'gregorian_year' => 625,
                'location' => 'الرجيع وبئر معونة في نجد والحجاز',
                'display_order' => 440,
                'translations' => [
                    'ar' => [
                        'title' => 'مأساتا الرجيع وبئر معونة',
                        'description' => 'في صفر من السنة الرابعة للهجرة وقعت مأساتان أليمتان غدر فيهما أعداء الإسلام بدعاته العزّل. أما الأولى فإن رهطًا من عَضَل والقارة قدموا على رسول الله ﷺ فقالوا: إن فينا إسلامًا فابعث معنا نفرًا يفقهوننا في الدين، فبعث معهم ستة من أصحابه — وقيل عشرة — عليهم مرثد بن أبي مرثد وفيهم خبيب بن عدي وزيد بن الدثنة وعاصم بن ثابت، فلما كانوا على ماء الرجيع غدروا بهم واستصرخوا عليهم هذيلًا، فقاتل الصحابة حتى قُتل أكثرهم، وأُسر خبيب وزيد فبيعا بمكة لمن يثأرون لقتلى بدر. فأما خبيب فصلبوه، وكان أول من سنّ الصلاة ركعتين عند القتل، وقال: ولستُ أبالي حين أُقتل مسلمًا * على أي شقٍّ كان لله مصرعي. وأما زيد فقال له أبو سفيان قبيل قتله: أتحب أن محمدًا عندنا مكانك وأنك في أهلك؟ فقال: والله ما أحب أن محمدًا يُشاك بشوكة وأنا جالس في أهلي! فقال أبو سفيان: ما رأيت أحدًا يحب أحدًا كحب أصحاب محمد محمدًا. وأما الثانية فإن أبا براء عامر بن مالك سأل النبي ﷺ أن يبعث قراء إلى أهل نجد وأجارهم، فبعث سبعين من خيرة القراء، فلما نزلوا بئر معونة غدر بهم عامر بن الطفيل واستنفر عليهم عُصيّة ورِعلًا وذكوان، فاستشهدوا جميعًا إلا رجلًا أو رجلين، فيهم حرام بن ملحان الذي قال حين طُعن: فزتُ ورب الكعبة! فحزن النبي ﷺ حزنًا شديدًا وقنت شهرًا يدعو على قتلتهم.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Tragedies of al-Raji and Bir Ma\'unah',
                        'description' => 'In Safar of 4 AH two grievous tragedies befell unarmed teachers of Islam through treachery. First, men of Adal and al-Qarah came to the Prophet ﷺ claiming their people inclined to Islam and asking for instructors. He sent six (some say ten) companions under Marthad ibn Abi Marthad, including Khubayb ibn Adi, Zayd ibn al-Dathinnah, and Asim ibn Thabit. At the water of al-Raji the guides betrayed them, summoning Hudhayl; most companions died fighting, while Khubayb and Zayd were captured and sold in Mecca to those avenging their dead of Badr. Khubayb, before his crucifixion, prayed two units — the first to establish this practice for the condemned — and recited: "I care not, when I am killed a Muslim, on which side my fall for God shall be." When Abu Sufyan asked Zayd before his death: "Would you not wish Muhammad were with us in your place while you sat among your family?" he answered: "By God, I would not wish Muhammad pricked by a single thorn while I sat at home!" — prompting Abu Sufyan\'s words: "I have never seen anyone loved as Muhammad\'s companions love Muhammad." In the second tragedy, Abu Bara Amir ibn Malik requested reciters for the people of Najd under his protection. Seventy of the finest Quran reciters were sent; at Bir Ma\'unah, Amir ibn al-Tufayl treacherously rallied the clans of Usayyah, Ri\'l, and Dhakwan against them, and all but one or two were martyred — among them Haram ibn Milhan, who cried as he was speared: "I have won, by the Lord of the Kaaba!" The Prophet ﷺ grieved deeply and invoked God against the killers in the dawn prayer for a full month.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-bani-al-nadir',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => 'ربيع الأول 4 هـ',
                'hijri_year' => 4,
                'hijri_month' => 3,
                'gregorian_year' => 625,
                'location' => 'ديار بني النضير جنوب المدينة',
                'latitude' => 24.4400000,
                'longitude' => 39.6300000,
                'statistics' => [
                    'duration_days' => 6,
                ],
                'display_order' => 450,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة بني النضير',
                        'description' => 'في ربيع الأول من السنة الرابعة خرج رسول الله ﷺ إلى بني النضير يستعينهم في دية قتيلين، فجلس إلى جدار من بيوتهم، فخلا بعضهم ببعض وقالوا: إنكم لن تجدوا الرجل على مثل حاله هذه، فمن رجل يعلو على هذا البيت فيلقي عليه صخرة فيريحنا منه؟ فانتدب لذلك عمرو بن جحاش. فجاء الوحيُ النبيَّ ﷺ بما همّوا به من الغدر، فقام مسرعًا راجعًا إلى المدينة وأمر بحربهم لنقضهم العهد. فتحصنوا في حصونهم، ودسّ إليهم عبد الله بن أبي رأس المنافقين: اثبتوا وتمنّعوا فإن معي ألفين يدخلون معكم حصونكم، فغرّهم وعدُه — وقد كذّبه القرآن: ﴿لَئِنْ أُخْرِجُوا لَا يَخْرُجُونَ مَعَهُمْ وَلَئِن قُوتِلُوا لَا يَنصُرُونَهُمْ﴾. فحاصرهم رسول الله ﷺ ستَّ ليال وأمر بقطع بعض نخلهم وتحريقه إمعانًا في زلزلتهم، فأنزل الله: ﴿مَا قَطَعْتُم مِّن لِّينَةٍ أَوْ تَرَكْتُمُوهَا قَائِمَةً عَلَى أُصُولِهَا فَبِإِذْنِ اللَّهِ﴾. وقذف الله في قلوبهم الرعب فسألوا الجلاء على أن لهم ما حملت الإبل إلا السلاح، فخرجوا يخربون بيوتهم بأيديهم لئلا ينتفع بها المسلمون، وساروا إلى خيبر وأذرعات، وفيهم ساداتهم حيي بن أخطب وسلّام بن أبي الحقيق. وكانت أموالهم أول فيء خالص لرسول الله ﷺ لم يوجف عليه المسلمون بخيل ولا ركاب، فقسمه في المهاجرين ليستغنوا عن مواساة الأنصار. وفيهم نزلت سورة الحشر كاملة تقص نبأهم: ﴿هُوَ الَّذِي أَخْرَجَ الَّذِينَ كَفَرُوا مِنْ أَهْلِ الْكِتَابِ مِن دِيَارِهِمْ لِأَوَّلِ الْحَشْرِ﴾.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Expedition against Banu al-Nadir',
                        'description' => 'In Rabi\' al-Awwal of 4 AH, the Prophet ﷺ visited Banu al-Nadir seeking their help with blood-money for two slain men. As he sat by a wall of their houses, they conspired among themselves: "You will never find the man more exposed than now — who will climb onto this house and drop a rock upon him to rid us of him?" Amr ibn Jahhash volunteered. Revelation informed the Prophet ﷺ of their treachery; he rose at once, returned to Medina, and declared war upon them for breaking the covenant. They shut themselves in their fortresses, encouraged by a secret message from the arch-hypocrite Abdullah ibn Ubayy promising two thousand men — a promise the Quran exposed as a lie: "If they are expelled, they will not depart with them, and if they are fought, they will not aid them" (al-Hashr 12). The Prophet ﷺ besieged them six nights and ordered some palms cut down and burned to break their resistance — concerning which God revealed: "Whatever palm trees you cut down or left standing on their roots, it was by God\'s permission." Terror seized their hearts and they sued for exile, allowed to take whatever their camels could carry except weapons. They demolished their own houses with their own hands rather than leave them to the Muslims, departing for Khaybar and Syria with their chiefs Huyayy ibn Akhtab and Sallam ibn Abi al-Huqayq. Their property became the first pure fay (spoils without battle) for the Prophet ﷺ, distributed among the Muhajirun. Surah al-Hashr was revealed entirely about them: "It is He who expelled the disbelievers of the People of the Book from their homes at the first gathering."',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-bani-al-mustaliq',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => 'شعبان 5 هـ',
                'hijri_year' => 5,
                'hijri_month' => 8,
                'gregorian_year' => 627,
                'location' => 'ماء المريسيع في ناحية قديد',
                'latitude' => 22.3500000,
                'longitude' => 39.1500000,
                'statistics' => [
                    'muslim_forces' => 700,
                    'duration_days' => 2,
                ],
                'display_order' => 460,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة بني المصطلق وحادثة الإفك',
                        'description' => 'بلغ رسولَ الله ﷺ في شعبان من السنة الخامسة أن الحارث بن أبي ضرار سيدَ بني المصطلق من خزاعة يجمع لحربه، فبادره ﷺ فخرج إليه حتى لقيه على ماء المريسيع من ناحية قديد، فتزلزل جمعُ الحارث وانفضّ عنه من معه من العرب، وانتصر المسلمون في قتال يسير وغنموا وسبوا، وكان في السبي جويرية بنت الحارث، فكاتبها ﷺ وتزوجها فأعتق المسلمون بسببها مائة من بني المصطلق وقالوا: أصهار رسول الله ﷺ لا يُسترقّون، فما عُرفت امرأة أعظم بركة على قومها منها. وفي هذه الغزوة كاد المنافقون يوقعون الفتنة؛ إذ ازدحم أجيرٌ لعمر مع رجل من الخزرج على الماء، فصرخ هذا: يا للأنصار، وهذا: يا للمهاجرين، فقال عبد الله بن أبي: لئن رجعنا إلى المدينة ليُخرجنّ الأعزُّ منها الأذل! فأنزل الله سورة المنافقون تكذيبًا له. وفي طريق العودة وقعت حادثة الإفك؛ إذ تخلفت أم المؤمنين عائشة رضي الله عنها عن الجيش في التماس عقد لها فحُمل هودجها وهم يحسبونها فيه، فلما رجعت أقامها على المنزل صفوانُ بن المعطل فجاء بها على بعيره، فتكلم أهل الإفك بقيادة ابن أبي فيما برّأها الله منه، وظلت شهرًا تبكي حتى أنزل الله براءتها من فوق سبع سماوات في عشر آيات من سورة النور: ﴿إِنَّ الَّذِينَ جَاءُوا بِالْإِفْكِ عُصْبَةٌ مِّنكُمْ﴾، فكانت دروس هذه الغزوة في فضح النفاق وخطر الإشاعة من أبلغ دروس السيرة.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Expedition against Banu al-Mustaliq and the Slander Incident',
                        'description' => 'In Sha\'ban of 5 AH, news reached the Prophet ﷺ that al-Harith ibn Abi Dirar, chief of Banu al-Mustaliq of Khuza\'ah, was massing forces against him. He ﷺ moved first, meeting them at the water of al-Muraysi\' near Qudayd; al-Harith\'s allies scattered, and the Muslims prevailed in a brief engagement, taking spoils and captives. Among the captives was Juwayriyyah bint al-Harith; the Prophet ﷺ ransomed and married her, whereupon the Muslims freed a hundred households of her tribe, saying: "The in-laws of the Messenger of God shall not be enslaved" — no woman brought greater blessing upon her people. During this campaign the hypocrites nearly kindled civil strife: a quarrel at the water between a hireling of Umar and a Khazraji man drew tribal war-cries, and Abdullah ibn Ubayy declared: "When we return to Medina, the mightier will expel the meaner!" — and God revealed Surah al-Munafiqun exposing him. On the return journey came the Incident of the Slander (al-Ifk): Aisha, Mother of the Believers, left the camp to search for her lost necklace; her litter was carried off with the army assuming her inside. Safwan ibn al-Mu\'attal found her and brought her on his camel, and the slanderers, led by Ibn Ubayy, spread the lie from which God Himself absolved her. After a month of tears, her innocence was proclaimed from above the seven heavens in ten verses of Surah al-Nur: "Indeed, those who brought the falsehood are a group among you." The campaign\'s lessons on hypocrisy and the danger of rumor are among the most profound of the Seerah.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-al-khandaq',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => 'شوال 5 هـ',
                'hijri_year' => 5,
                'hijri_month' => 10,
                'gregorian_year' => 627,
                'location' => 'شمال المدينة المنورة',
                'latitude' => 24.4847000,
                'longitude' => 39.6022000,
                'statistics' => [
                    'muslim_forces' => 3000,
                    'enemy_forces' => 10000,
                    'muslim_martyrs' => 6,
                    'enemy_casualties' => 8,
                    'duration_days' => 27,
                ],
                'display_order' => 470,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة الخندق (الأحزاب)',
                        'description' => 'في شوال من السنة الخامسة للهجرة تألّب على المسلمين أعظم تحالف عرفته الجزيرة؛ إذ سعى نفر من يهود بني النضير المُجلَين — على رأسهم حيي بن أخطب — في تحزيب الأحزاب، فحرّضوا قريشًا وغطفان وبني سُليم وغيرهم حتى اجتمع نحو عشرة آلاف مقاتل زحفوا إلى المدينة. فاستشار رسول الله ﷺ أصحابه، فأشار سلمان الفارسي بخطة لم تعرفها العرب: «يا رسول الله، إنا كنا بفارس إذا حوصرنا خندقنا علينا»، فأمر ﷺ بحفر الخندق شمالي المدينة من حرّة إلى حرّة، وعمل فيه بيده الشريفة ينقل التراب حتى وارى التراب جلدة بطنه وهم يرتجزون: «اللهم لا عيش إلا عيش الآخرة فاغفر للأنصار والمهاجرة». وظهرت في الحفر معجزات؛ إذ ضرب ﷺ الصخرة التي أعيت الصحابة فبرقت ثلاثًا وبشّر عند كل برقة بفتح اليمن والشام والمشرق وقصور كسرى وقيصر، وكثّر الطعام القليل لأهل الخندق جميعًا في بيت جابر. فلما وصلت الأحزاب فوجئوا بالخندق، وحوصرت المدينة قرابة شهر في برد وجوع وخوف شديد: ﴿وَإِذْ زَاغَتِ الْأَبْصَارُ وَبَلَغَتِ الْقُلُوبُ الْحَنَاجِرَ﴾، وزاد الكربَ نقضُ بني قريظة للعهد من داخل المدينة. واقتحم فوارس من قريش الخندقَ فيهم عمرو بن عبد ودّ فارس العرب، فبارزه علي بن أبي طالب فقتله. ثم أتى الفرجُ من حيث دبّر الله؛ أسلم نُعيم بن مسعود سرًّا فخذّل بين الأحزاب وقريظة حتى تفرقت كلمتهم، وأرسل الله عليهم ريحًا صرصرًا وجنودًا لم يروها قلعت خيامهم وأكفأت قدورهم، فارتحلوا خائبين. وقال ﷺ: «الآن نغزوهم ولا يغزوننا»، وصدق الله: ﴿وَرَدَّ اللَّهُ الَّذِينَ كَفَرُوا بِغَيْظِهِمْ لَمْ يَنَالُوا خَيْرًا وَكَفَى اللَّهُ الْمُؤْمِنِينَ الْقِتَالَ﴾.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Battle of the Trench (al-Khandaq / al-Ahzab)',
                        'description' => 'In Shawwal of 5 AH the greatest coalition Arabia had known massed against the Muslims. Exiled chiefs of Banu al-Nadir, led by Huyayy ibn Akhtab, forged the confederacy, inciting Quraysh, Ghatafan, Banu Sulaym and others until some ten thousand warriors marched on Medina. The Prophet ﷺ consulted his companions, and Salman al-Farisi proposed a stratagem unknown to the Arabs: "O Messenger of God, in Persia when besieged we would dig a trench around ourselves." The Prophet ﷺ ordered the trench dug across Medina\'s open northern approach and labored in it himself, dust covering his very skin, as the believers chanted: "O God, there is no life but the life of the Hereafter — forgive the Ansar and the Muhajirah." Miracles attended the digging: he ﷺ shattered the rock that had defeated the companions, and at each of three flashes foretold the conquest of Yemen, Syria, and the East with the palaces of Khosrau and Caesar; and he multiplied a small meal in Jabir\'s house to feed all the workers. The confederates, stunned by the trench, settled into a siege of nearly a month amid cold, hunger, and dread — "when eyes shifted and hearts reached the throats" (al-Ahzab 10) — worsened by Banu Qurayzah\'s treacherous breach of covenant inside the city. Horsemen led by Amr ibn Abd Wudd, champion of the Arabs, leapt the trench; Ali ibn Abi Talib met and slew him in single combat. Relief then came as God ordained: Nu\'aym ibn Mas\'ud, a secret convert, sowed distrust between the confederates and Qurayzah until their unity dissolved, and God sent upon them a furious wind and unseen hosts that tore their tents and overturned their cooking-pots. They withdrew in failure, and the Prophet ﷺ declared: "Now we shall raid them, and they shall never again raid us." "And God repelled those who disbelieved, in their rage, having gained no good — and God sufficed the believers in battle" (al-Ahzab 25).',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'تحزيب الأحزاب', 'description' => 'سعى حيي بن أخطب وأشراف يهود في جمع قريش وغطفان وحلفائهما حتى زحف نحو عشرة آلاف على المدينة.'],
                        'en' => ['title' => 'Forging the confederacy', 'description' => 'Huyayy ibn Akhtab and exiled Jewish chiefs united Quraysh, Ghatafan, and allies — ten thousand strong — against Medina.'],
                    ],
                    [
                        'ar' => ['title' => 'مشورة سلمان وحفر الخندق', 'description' => 'أشار سلمان الفارسي بحفر خندق شمالي المدينة، فعمل فيه النبي ﷺ بيده مع أصحابه.'],
                        'en' => ['title' => 'Salman\'s counsel and digging the trench', 'description' => 'Salman al-Farisi proposed the Persian stratagem of a trench; the Prophet ﷺ dug alongside his companions with his own hands.'],
                    ],
                    [
                        'ar' => ['title' => 'معجزة الصخرة والبشارات الثلاث', 'description' => 'ضرب ﷺ الصخرة فبرقت ثلاثًا، وبشّر بفتح اليمن والشام والمشرق وقصور كسرى وقيصر.'],
                        'en' => ['title' => 'The rock and the three glad tidings', 'description' => 'Striking the unbreakable rock, he ﷺ foretold at each flash the conquests of Yemen, Syria, and the East.'],
                    ],
                    [
                        'ar' => ['title' => 'نقض بني قريظة للعهد', 'description' => 'نقضت بنو قريظة عهدها من داخل المدينة بإغراء حيي بن أخطب، فاشتد الكرب على المسلمين.'],
                        'en' => ['title' => 'Banu Qurayzah\'s treachery', 'description' => 'Seduced by Huyayy ibn Akhtab, Banu Qurayzah broke their covenant from within, deepening the Muslims\' peril.'],
                    ],
                    [
                        'ar' => ['title' => 'مبارزة علي لعمرو بن عبد ودّ', 'description' => 'اقتحم عمرو بن عبد ودّ فارس العرب الخندق فبارزه علي بن أبي طالب فقتله، فانكسرت همة الفرسان.'],
                        'en' => ['title' => 'Ali\'s duel with Amr ibn Abd Wudd', 'description' => 'When the Arab champion Amr leapt the trench, Ali ibn Abi Talib slew him in single combat, breaking the raiders\' spirit.'],
                    ],
                    [
                        'ar' => ['title' => 'تخذيل نعيم والريح المرسلة', 'description' => 'خذّل نعيم بن مسعود بين الأحزاب وقريظة، وأرسل الله ريحًا وجنودًا لم يروها فانصرف الأحزاب خائبين.'],
                        'en' => ['title' => 'Nu\'aym\'s stratagem and the divine wind', 'description' => 'Nu\'aym ibn Mas\'ud split the coalition, and God sent a furious wind and unseen hosts that drove the confederates away.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'قاد الدفاع عن المدينة وعمل في حفر الخندق بيده الشريفة.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Led the defense of Medina and dug the trench with his own hands.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'سلمان الفارسي', 'description' => 'صاحب فكرة الخندق، وفيه قال ﷺ: سلمان منا أهل البيت.'],
                            'en' => ['name' => 'Salman al-Farisi', 'description' => 'Author of the trench stratagem, of whom the Prophet ﷺ said: "Salman is one of us, the People of the House."'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'علي بن أبي طالب', 'description' => 'قتل فارس العرب عمرو بن عبد ودّ في مبارزة الخندق المشهورة.'],
                            'en' => ['name' => 'Ali ibn Abi Talib', 'description' => 'Slew the Arab champion Amr ibn Abd Wudd in the famous duel at the trench.'],
                        ],
                    ],
                    [
                        'role' => 'enemy',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'حيي بن أخطب', 'description' => 'سيد بني النضير ومحرّك تحالف الأحزاب ومُغري بني قريظة بنقض العهد.'],
                            'en' => ['name' => 'Huyayy ibn Akhtab', 'description' => 'Chief of Banu al-Nadir, architect of the confederate coalition and instigator of Qurayzah\'s treachery.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-bani-qurayza',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => 'ذو القعدة 5 هـ',
                'hijri_year' => 5,
                'hijri_month' => 11,
                'gregorian_year' => 627,
                'location' => 'ديار بني قريظة جنوب شرق المدينة',
                'latitude' => 24.4300000,
                'longitude' => 39.6500000,
                'statistics' => [
                    'muslim_forces' => 3000,
                    'duration_days' => 25,
                ],
                'display_order' => 480,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة بني قريظة',
                        'description' => 'لما رجع رسول الله ﷺ من الخندق ووضع السلاح أتاه جبريل عليه السلام ظهرًا فقال: أوَقد وضعتَ السلاح؟ والله ما وضعَته الملائكة؛ اخرج إلى بني قريظة فإني عامد إليهم فمزلزل بهم. فأذّن ﷺ في الناس: «من كان سامعًا مطيعًا فلا يصلينّ العصر إلا في بني قريظة»، فنهض المسلمون من فورهم على ما بهم من جهد الحصار. وكانت بنو قريظة قد نقضت عهدها المؤكد في أحرج ساعات الخندق، وهمّت بضرب المسلمين من الظهر والنساء والذراري في الآطام بلا حماية، فلم يكن غدرهم ذنبًا سياسيًّا فحسب بل خيانة عظمى في ساعة حرب. فحاصرهم رسول الله ﷺ خمسًا وعشرين ليلة حتى اشتد عليهم الحصار وقذف الله في قلوبهم الرعب، فنزلوا على حكم سعد بن معاذ سيد الأوس حلفائهم القدامى، ظنًّا أنه سيحابيهم كما فعل ابن أبي بحلفائه من قينقاع. وكان سعد قد أصيب بسهم في أكحله يوم الخندق، فحُمل إليهم فحكم فيهم بحكم التوراة نفسها في أهل الغدر: أن يُقتل المقاتلون وتُسبى الذراري وتقسم الأموال، فقال له ﷺ: «لقد حكمتَ فيهم بحكم الله من فوق سبعة أرقعة». ثم ما لبث سعد أن انفجر جرحه فمات شهيدًا، فاهتز لموته عرش الرحمن كما صح في الحديث. وبانتهاء أمر قريظة خلت المدينة من كيد يهودها الثلاث، وتفرغ المسلمون لما بعدها من الفتوح، وأنزل الله: ﴿وَأَنزَلَ الَّذِينَ ظَاهَرُوهُم مِّنْ أَهْلِ الْكِتَابِ مِن صَيَاصِيهِمْ﴾.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Expedition against Banu Qurayzah',
                        'description' => 'When the Prophet ﷺ returned from the Trench and laid down his arms, Jibril came to him at noon: "Have you laid down your weapons? By God, the angels have not laid down theirs. March on Banu Qurayzah, for I go before you to shake their fortresses." The Prophet ﷺ proclaimed: "Let no one who hears and obeys pray the afternoon prayer except at Banu Qurayzah," and the Muslims marched at once despite the exhaustion of the siege. Banu Qurayzah had broken their solemn covenant at the Trench\'s most desperate hour, threatening the Muslims\' rear and the unprotected women and children in the strongholds — not merely a political offense but high treason in wartime. The Prophet ﷺ besieged them twenty-five nights until terror filled their hearts and they surrendered to the arbitration of Sa\'d ibn Mu\'adh, chief of their old allies the Aws, expecting leniency like that once won by Ibn Ubayy for Qaynuqa. Sa\'d — wounded in the arm by an arrow at the Trench — was carried to them and pronounced judgment in accordance with the Torah\'s own law for wartime treachery: the fighting men executed, the dependents taken captive, the property divided. The Prophet ﷺ said: "You have judged among them by the judgment of God from above the seven heavens." Sa\'d\'s wound soon reopened and he died a martyr — at whose death the Throne of the Most Merciful shook, as authentically narrated. With Qurayzah\'s end, Medina was freed of intrigue within, and God revealed: "And He brought down those of the People of the Book who supported them from their fortresses" (al-Ahzab 26).',
                        'content' => null,
                    ],
                ],
            ],
        ];
    }
}
