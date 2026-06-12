<?php

declare(strict_types=1);

namespace Database\Seeders\Seerah;

/**
 * Batch 5 of 5 — From al-Hudaybiyyah to the Prophet's ﷺ passing
 * (628–632 CE / 6–11 AH): the treaty, Khaybar, the letters to the kings,
 * Mu'tah, the Conquest of Mecca, Hunayn, Tabuk, the Year of Delegations,
 * the Farewell Pilgrimage, and the death of the Prophet ﷺ.
 */
class SeerahEventsFinalYearsSeeder extends SeerahEventSeederBase
{
    protected function events(): array
    {
        return [
            [
                'slug' => 'sulh-al-hudaybiyya',
                'event_type' => 'treaty',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => 'ذو القعدة 6 هـ',
                'hijri_year' => 6,
                'hijri_month' => 11,
                'gregorian_year' => 628,
                'location' => 'الحديبية غرب مكة',
                'latitude' => 21.4486000,
                'longitude' => 39.6164000,
                'statistics' => [
                    'muslim_forces' => 1400,
                    'duration_days' => 20,
                    'truce_years' => 10,
                ],
                'display_order' => 490,
                'translations' => [
                    'ar' => [
                        'title' => 'صلح الحديبية (الفتح المبين)',
                        'description' => 'في ذي القعدة من السنة السادسة للهجرة خرج رسول الله ﷺ في ألف وأربعمائة من أصحابه معتمرين لا يريدون حربًا، ساقوا الهدي وأحرموا من ذي الحليفة، فصدّتهم قريش عن البيت ونزل ﷺ بالحديبية. وأرسل عثمان بن عفان سفيرًا إلى مكة، فأرجف بأنه قُتل، فدعا رسول الله ﷺ أصحابه إلى البيعة فبايعوه تحت الشجرة على ألا يفروا — وهي بيعة الرضوان التي أنزل الله فيها: ﴿لَقَدْ رَضِيَ اللَّهُ عَنِ الْمُؤْمِنِينَ إِذْ يُبَايِعُونَكَ تَحْتَ الشَّجَرَةِ﴾. ثم جاء سهيل بن عمرو مفاوضًا، فجرى الصلح على وضع الحرب عشر سنين، وأن يرجع المسلمون عامهم ذلك ويعتمروا من قابل، وأن من أتى محمدًا من قريش بغير إذن وليه ردّه عليهم ومن جاء قريشًا ممن مع محمد لم يردوه، وأن من شاء دخل في عقد محمد ومن شاء دخل في عقد قريش. وشقّت هذه الشروط على المسلمين حتى قال عمر: علامَ نعطي الدنية في ديننا؟ ولما أمرهم ﷺ أن ينحروا ويحلقوا لم يقم أحد لشدة ما بهم، فأشارت عليه أم سلمة أن يبدأ بنفسه، فنحر وحلق فتتابع الناس. وفي طريق العودة نزلت سورة الفتح: ﴿إِنَّا فَتَحْنَا لَكَ فَتْحًا مُّبِينًا﴾، فقال ﷺ: «لقد أُنزلت عليّ الليلة سورة هي أحب إليّ مما طلعت عليه الشمس». وصدق الله؛ فقد أمِن الناس بالهدنة والتقوا وتحدثوا، فدخل في الإسلام في عامين بعدها أكثر ممن دخل فيه قبل ذلك، وكان الصلح مقدمة فتح مكة.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Treaty of al-Hudaybiyyah (The Manifest Victory)',
                        'description' => 'In Dhu al-Qa\'dah of 6 AH the Prophet ﷺ set out with 1,400 companions intending pilgrimage, not war — driving sacrificial animals and entering ihram at Dhu al-Hulayfah. Quraysh barred them from the House, and he ﷺ encamped at al-Hudaybiyyah. He sent Uthman ibn Affan as envoy to Mecca; when a rumor spread that Uthman had been killed, the Prophet ﷺ called his companions to a pledge, and they swore under the tree never to flee — the Pledge of Ridwan, of which God revealed: "God was certainly pleased with the believers when they pledged allegiance to you under the tree" (al-Fath 18). Suhayl ibn Amr then came to negotiate, and a treaty was concluded: a ten-year truce; the Muslims to return that year and perform umrah the next; any Qurayshi coming to Muhammad without his guardian\'s leave to be returned, while any Muslim going to Quraysh would not be; and the tribes free to join either side\'s covenant. The terms grieved the Muslims — Umar protested, "Why should we accept humiliation in our religion?" — and when the Prophet ﷺ ordered them to sacrifice and shave, none rose, until Umm Salamah counselled him to begin with himself; he did, and the people followed. On the road home Surah al-Fath descended: "Indeed, We have given you a manifest victory." He ﷺ said: "Tonight a surah has been revealed to me dearer to me than all the sun rises upon." And so it proved: under the truce people met and talked freely, and more entered Islam in the following two years than in all the years before — the treaty was the very gateway to the Conquest of Mecca.',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'الخروج معتمرين وسوق الهدي', 'description' => 'خرج ﷺ في ألف وأربعمائة محرمين بالعمرة ساقوا الهدي معلنين أنهم لا يريدون حربًا.'],
                        'en' => ['title' => 'Setting out for umrah', 'description' => 'He ﷺ marched with 1,400 pilgrims in ihram, driving sacrificial animals to proclaim peaceful intent.'],
                    ],
                    [
                        'ar' => ['title' => 'بروك القصواء ونزول الحديبية', 'description' => 'بركت ناقته القصواء دون مكة فقال: حبسها حابس الفيل، ونزل بأقصى الحديبية.'],
                        'en' => ['title' => 'Al-Qaswa kneels at al-Hudaybiyyah', 'description' => 'His camel knelt before Mecca; he said: "He who restrained the elephant has restrained her," and camped at al-Hudaybiyyah.'],
                    ],
                    [
                        'ar' => ['title' => 'سفارة عثمان وإشاعة مقتله', 'description' => 'أرسل ﷺ عثمان بن عفان إلى قريش، فاحتبسوه وأرجف بمقتله، فاشتد الموقف.'],
                        'en' => ['title' => 'Uthman\'s embassy', 'description' => 'Uthman was sent to Quraysh; they detained him and a rumor of his death spread, bringing matters to a head.'],
                    ],
                    [
                        'ar' => ['title' => 'بيعة الرضوان تحت الشجرة', 'description' => 'بايع الصحابة رسول الله ﷺ تحت الشجرة على الموت وألا يفروا، فرضي الله عنهم ونزلت فيهم الآية.'],
                        'en' => ['title' => 'The Pledge of Ridwan', 'description' => 'Under the tree the companions pledged to the Prophet ﷺ never to flee — a pledge God Himself declared His pleasure with.'],
                    ],
                    [
                        'ar' => ['title' => 'التفاوض وكتابة الصلح', 'description' => 'جاء سهيل بن عمرو فجرى الصلح على هدنة عشر سنين بشروط ظاهرها ضيم وباطنها الفتح.'],
                        'en' => ['title' => 'Negotiating the treaty', 'description' => 'Suhayl ibn Amr negotiated a ten-year truce whose terms seemed harsh outwardly but concealed the coming victory.'],
                    ],
                    [
                        'ar' => ['title' => 'النحر والحلق ونزول سورة الفتح', 'description' => 'بدأ ﷺ بنفسه فنحر وحلق بمشورة أم سلمة فتتابع الناس، ونزلت في الطريق سورة الفتح.'],
                        'en' => ['title' => 'The sacrifice and Surah al-Fath', 'description' => 'On Umm Salamah\'s counsel he ﷺ sacrificed and shaved first and all followed; Surah al-Fath descended on the road home.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'قائد المسلمين في الحديبية، قبل شروط الصلح ببصيرة النبوة فكان فتحًا مبينًا.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Led the Muslims at al-Hudaybiyyah, accepting the treaty with prophetic foresight — and it proved a manifest victory.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عثمان بن عفان', 'description' => 'سفير رسول الله ﷺ إلى قريش، وبسبب إشاعة مقتله كانت بيعة الرضوان.'],
                            'en' => ['name' => 'Uthman ibn Affan', 'description' => 'The Prophet\'s ﷺ envoy to Quraysh; the rumor of his death occasioned the Pledge of Ridwan.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أم سلمة', 'description' => 'أم المؤمنين، أشارت على النبي ﷺ بالرأي الحكيم يوم الحديبية فحُلّت الأزمة.'],
                            'en' => ['name' => 'Umm Salamah', 'description' => 'Mother of the Believers whose wise counsel at al-Hudaybiyyah resolved the companions\' hesitation.'],
                        ],
                    ],
                    [
                        'role' => 'enemy',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'سهيل بن عمرو', 'description' => 'مفاوض قريش الذي كتب الصلح، أسلم بعد الفتح وحسن إسلامه.'],
                            'en' => ['name' => 'Suhayl ibn Amr', 'description' => 'Quraysh\'s negotiator who drafted the treaty; he later embraced Islam after the Conquest.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'rasail-ila-al-muluk',
                'event_type' => 'delegation',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'hijri_date' => 'محرم 7 هـ',
                'hijri_year' => 7,
                'hijri_month' => 1,
                'gregorian_year' => 628,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 500,
                'translations' => [
                    'ar' => [
                        'title' => 'رسائل النبي ﷺ إلى الملوك والأمراء',
                        'description' => 'لما وضعت هدنة الحديبية أوزار الحرب مع قريش، تفرّغ رسول الله ﷺ لإبلاغ دعوته ملوكَ الأرض، إعلانًا لعالمية الرسالة. فاتخذ خاتمًا من فضة نقشه «محمد رسول الله» لما قيل له إن الملوك لا يقرؤون كتابًا إلا مختومًا، وبعث في محرم سنة سبع رُسُلَه: دحية بن خليفة الكلبي إلى هرقل عظيم الروم، وعبد الله بن حذافة السهمي إلى كسرى عظيم فارس، وعمرو بن أمية الضمري إلى النجاشي ملك الحبشة، وحاطب بن أبي بلتعة إلى المقوقس عظيم القبط بمصر، وشجاع بن وهب إلى الحارث الغساني ملك تخوم الشام، وسليط بن عمرو إلى هوذة بن علي صاحب اليمامة. وكان نص كتابه إلى هرقل: «بسم الله الرحمن الرحيم، من محمد عبد الله ورسوله إلى هرقل عظيم الروم، سلام على من اتبع الهدى، أما بعد فإني أدعوك بدعاية الإسلام؛ أسلِم تسلَم يؤتك الله أجرك مرتين، فإن توليت فإن عليك إثم الأريسيين، و﴿يَا أَهْلَ الْكِتَابِ تَعَالَوْا إِلَى كَلِمَةٍ سَوَاءٍ بَيْنَنَا وَبَيْنَكُمْ﴾». فأكرم هرقلُ والمقوقسُ والنجاشيُّ الرسلَ — وأسلم النجاشي — وأما كسرى فمزّق الكتاب، فقال ﷺ: «مزّق الله ملكه»، فكان كما قال؛ إذ قُتل كسرى على يد ابنه ثم تمزقت إمبراطورية فارس بالفتح الإسلامي. وكانت هذه الرسائل نقلة استراتيجية أخرجت الدعوة من محيط الجزيرة إلى أفق العالم.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Prophet\'s ﷺ Letters to the Kings and Rulers',
                        'description' => 'Once the truce of al-Hudaybiyyah stilled the war with Quraysh, the Prophet ﷺ turned to proclaiming his message to the kings of the earth, declaring the universality of the call. Told that kings read no letter unless sealed, he had a silver ring engraved "Muhammad, Messenger of God." In Muharram of 7 AH his envoys went forth: Dihyah al-Kalbi to Heraclius, emperor of Byzantium; Abdullah ibn Hudhafah to Khosrow, emperor of Persia; Amr ibn Umayyah to the Negus of Abyssinia; Hatib ibn Abi Balta\'ah to al-Muqawqis, ruler of the Copts of Egypt; Shuja\' ibn Wahb to al-Harith the Ghassanid on the Syrian marches; and Salit ibn Amr to Hawdhah of al-Yamamah. His letter to Heraclius read: "In the name of God, the Most Gracious, the Most Merciful. From Muhammad, servant of God and His Messenger, to Heraclius, the great one of Rome: peace upon whoever follows the guidance. I call you with the call of Islam — embrace Islam and you will be safe, and God will give you your reward twice over; but if you turn away, upon you is the sin of your subjects. \'O People of the Book, come to a word common between us and you\'" (Al Imran 64). Heraclius, al-Muqawqis, and the Negus honoured the envoys — the Negus embraced Islam — while Khosrow tore the letter, and the Prophet ﷺ said: "May God tear his kingdom apart" — and so it was: Khosrow was slain by his own son, and Persia\'s empire was torn apart by the Islamic conquests. These letters carried the call beyond Arabia to the horizon of the world.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-khaybar',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'hijri_date' => 'محرم 7 هـ',
                'hijri_year' => 7,
                'hijri_month' => 1,
                'gregorian_year' => 628,
                'location' => 'خيبر شمال المدينة المنورة',
                'latitude' => 25.7008000,
                'longitude' => 39.2925000,
                'statistics' => [
                    'muslim_forces' => 1400,
                    'muslim_martyrs' => 16,
                    'enemy_casualties' => 93,
                    'duration_days' => 30,
                    'fortresses' => 8,
                ],
                'display_order' => 510,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة خيبر',
                        'description' => 'كانت خيبر معقل يهود الجزيرة بعد جلاء بني النضير، وحصونها الثمانية مشحونة بالسلاح والرجال، ومنها دُبّرت الأحزاب وأُلّبت غطفان. فلما رجع رسول الله ﷺ من الحديبية آمنًا من قريش سار إلى خيبر في محرم سنة سبع في ألف وأربعمائة ممن شهدوا بيعة الرضوان خاصة، وفيهم وعدُ الله بمغانمها: ﴿وَعَدَكُمُ اللَّهُ مَغَانِمَ كَثِيرَةً تَأْخُذُونَهَا فَعَجَّلَ لَكُمْ هَٰذِهِ﴾. فحاصر الحصون حصنًا حصنًا، فاستعصى حصن ناعم ثم القموص، فقال ﷺ: «لأعطين الراية غدًا رجلًا يحب الله ورسوله ويحبه الله ورسوله، يفتح الله على يديه»، فبات الناس يدوكون ليلتهم أيهم يُعطاها، فدعا عليَّ بن أبي طالب — وهو أرمد — فبصق في عينيه فبرأ، وأعطاه الراية، فقتل مرحبًا فارس اليهود في المبارزة وفتح الله على يديه. وتساقطت الحصون حتى سأل أهلها الصلح على حقن دمائهم، وعاملهم ﷺ على أن يعملوا في أرضها بشطر ثمرها. وفيها أُهديت له شاة مسمومة فأمسك وأخبره الذراع بالسم، وأعلم الله نبيه فنجا. وفي خيبر تزوج ﷺ صفية بنت حيي بعد أن أعتقها وجعل عتقها صداقها. وقدم في أثنائها جعفر بن أبي طالب من الحبشة فقال ﷺ: «ما أدري بأيهما أنا أُسرّ: بفتح خيبر أم بقدوم جعفر».',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Battle of Khaybar',
                        'description' => 'After the exile of Banu al-Nadir, Khaybar became the stronghold of the Jews of Arabia — eight fortresses packed with men and arms, from which the Confederates had been orchestrated and Ghatafan incited. Returning from al-Hudaybiyyah secure on the Quraysh front, the Prophet ﷺ marched on Khaybar in Muharram of 7 AH with 1,400 men — those of the Pledge of Ridwan alone, to whom God had promised its spoils: "God has promised you abundant spoils which you will take, and has hastened this for you" (al-Fath 20). He reduced the fortresses one by one; when Na\'im and al-Qamus resisted, he ﷺ declared: "Tomorrow I shall give the banner to a man who loves God and His Messenger and whom God and His Messenger love; God will grant victory at his hands." The companions passed the night wondering who it would be; he summoned Ali ibn Abi Talib — suffering inflamed eyes — spat lightly upon them and they healed, and gave him the banner. Ali slew Marhab, the champion of the Jews, in single combat, and God opened the fortress at his hands. The strongholds fell until the people sued for terms sparing their lives, and the Prophet ﷺ let them work the land for half its produce. There a poisoned sheep was offered to him; the shoulder itself informed him of the poison by God\'s leave, and he was spared. At Khaybar he married Safiyyah bint Huyayy, making her freedom her dower. During the campaign Ja\'far ibn Abi Talib arrived from Abyssinia, and the Prophet ﷺ said: "I know not which gives me more joy — the conquest of Khaybar or the coming of Ja\'far."',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'قائد المسلمين في خيبر، حاصر الحصون حتى فتحها الله عليه.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Commanded the Muslims at Khaybar, besieging the fortresses until God granted their conquest.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'علي بن أبي طالب', 'description' => 'حامل الراية يوم خيبر، قتل مرحبًا وفتح الله الحصن على يديه.'],
                            'en' => ['name' => 'Ali ibn Abi Talib', 'description' => 'Bearer of the banner at Khaybar, who slew Marhab; God opened the fortress at his hands.'],
                        ],
                    ],
                    [
                        'role' => 'enemy',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'مرحب اليهودي', 'description' => 'فارس يهود خيبر وأشدهم بأسًا، قتله علي بن أبي طالب في المبارزة.'],
                            'en' => ['name' => 'Marhab', 'description' => 'The champion warrior of the Jews of Khaybar, slain by Ali ibn Abi Talib in single combat.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'umrat-al-qada',
                'event_type' => 'personal',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'medium',
                'hijri_date' => 'ذو القعدة 7 هـ',
                'hijri_year' => 7,
                'hijri_month' => 11,
                'gregorian_year' => 629,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 520,
                'translations' => [
                    'ar' => [
                        'title' => 'عمرة القضاء',
                        'description' => 'في ذي القعدة من السنة السابعة — بعد عام كامل من صدّ الحديبية وعلى موجب شرط الصلح — خرج رسول الله ﷺ معتمرًا في ألفين من أصحابه، فيهم كثير ممن صُدّ معه عامَ أول، قاضيًا عمرته التي حيل بينه وبينها. وأخلت له قريش مكة ثلاثة أيام وصعدوا الجبال يتربصون، وقد بلغهم أن وهنًا أصاب المسلمين من حمّى يثرب، فأمر ﷺ أصحابه أن يرملوا — يسرعوا في مشيهم — في الأشواط الثلاثة الأولى ويكشفوا عن مناكبهم اليمنى ليُري المشركين قوتهم، فقال المشركون: هؤلاء الذين زعمتم أن الحمى وهنتهم؟! هؤلاء أجلد من كذا! فبقيت سنة الرمَل في الطواف إلى اليوم ذكرى ذلك الموقف. وطاف ﷺ بالبيت على راحلته يستلم الركن بمحجنه، وبلال يؤذن فوق الكعبة بمرأى صناديد قريش. وفي هذه العمرة تزوج رسول الله ﷺ ميمونة بنت الحارث آخر أزواجه، وخطبها له العباس عمه. ولما انقضت الأيام الثلاثة جاءه سهيل بن عمرو وحويطب يأمرانه بالخروج وفق الشرط، فخرج ﷺ وفيًّا بعهده لم يزد ساعة، فضرب للأمة مثلًا في الوفاء بالعهد حتى مع من صدّه عن بيت الله. وكانت العمرة استعراضًا سلميًّا لقوة الإسلام مهّد القلوب لفتح قريب.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Umrah of Fulfilment (Umrat al-Qada)',
                        'description' => 'In Dhu al-Qa\'dah of 7 AH — a full year after being turned back at al-Hudaybiyyah, and exactly as the treaty stipulated — the Prophet ﷺ set out with two thousand companions to fulfil the umrah he had been denied. Quraysh vacated Mecca for three days and watched from the mountains, having heard that the fever of Yathrib had weakened the Muslims. So the Prophet ﷺ ordered his companions to stride briskly (raml) in the first three circuits of the Kaaba and bare their right shoulders, displaying their vigor — and the polytheists exclaimed: "Are these the men you claimed the fever had wasted? They are hardier than such-and-such!" The practice of raml in tawaf remains to this day a memorial of that moment. He ﷺ circled the House on his mount, touching the corner with his staff, while Bilal gave the call to prayer from atop the Kaaba in full view of the chiefs of Quraysh. During this umrah the Prophet ﷺ married Maymunah bint al-Harith, the last of his wives, his uncle al-Abbas arranging the match. When the three days ended, Suhayl ibn Amr and Huwaytib came bidding him depart per the treaty, and he ﷺ left faithfully without lingering an hour — setting the Ummah an example of keeping covenant even with those who had barred him from God\'s House. The umrah was a peaceful display of Islam\'s strength that softened hearts for the conquest soon to come.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-mutah',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'hijri_date' => 'جمادى الأولى 8 هـ',
                'hijri_year' => 8,
                'hijri_month' => 5,
                'gregorian_year' => 629,
                'location' => 'مؤتة قرب الكرك ببلاد الشام',
                'latitude' => 31.0297000,
                'longitude' => 35.6936000,
                'statistics' => [
                    'muslim_forces' => 3000,
                    'enemy_forces' => 200000,
                    'muslim_martyrs' => 12,
                    'duration_days' => 7,
                ],
                'display_order' => 530,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة مؤتة',
                        'description' => 'كان سببها أن رسول الله ﷺ بعث الحارث بن عمير الأزدي بكتابه إلى عظيم بصرى، فعرض له شرحبيل بن عمرو الغساني عامل قيصر فقتله صبرًا — ولم يُقتل لرسول الله ﷺ رسول غيره — فاشتد ذلك عليه وجهّز جيشًا من ثلاثة آلاف في جمادى الأولى سنة ثمان، وأمّر زيد بن حارثة وقال: «إن أُصيب زيد فجعفر، فإن أُصيب جعفر فعبد الله بن رواحة» — ترتيب نبوي أنبأ بالشهادة قبل وقوعها. فلما بلغوا مؤتة وجدوا جموع الروم ومتنصّرة العرب في مائتي ألف، فتشاور المسلمون، فحسم ابن رواحة الموقف: ما نقاتل الناس بعدد ولا قوة، إنما نقاتلهم بهذا الدين؛ فإنما هي إحدى الحسنيين: ظهور أو شهادة. فقاتل زيد بالراية حتى استُشهد، فأخذها جعفر فقاتل حتى قُطعت يمينه فأخذها بشماله فقُطعت فاحتضنها بعضديه حتى قُتل — فأبدله الله جناحين يطير بهما في الجنة فسُمي الطيار — ثم أخذها ابن رواحة فاستُشهد. ثم اصطلح الناس على خالد بن الوليد، فانحاز بالجيش ودبّر انسحابًا مظفرًا حفظ به المسلمين، وفيه قال ﷺ: «حتى أخذ الراية سيف من سيوف الله ففتح الله على يديه». ونعى رسول الله ﷺ الشهداء الثلاثة بالمدينة قبل أن يأتيه الخبر، ودمعت عيناه. وكانت مؤتة أول صدام كبير مع الروم، وأكسبت المسلمين هيبة مهّدت لفتوح الشام.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Battle of Mu\'tah',
                        'description' => 'Its cause: the Prophet ﷺ sent al-Harith ibn Umayr with a letter to the ruler of Busra, but Shurahbil the Ghassanid, Caesar\'s governor, seized and executed him — the only envoy of the Prophet ﷺ ever slain. Grieved, the Prophet ﷺ equipped an army of three thousand in Jumada al-Ula of 8 AH, appointing Zayd ibn Harithah and saying: "If Zayd falls, then Ja\'far; if Ja\'far falls, then Abdullah ibn Rawahah" — a prophetic ordering that foretold their martyrdom. At Mu\'tah they found the hosts of Byzantium and Arab Christian allies, said to number two hundred thousand. The Muslims took counsel, and Ibn Rawahah settled it: "We fight people by no numbers or strength; we fight them by this religion. It is one of two excellent ends: victory or martyrdom." Zayd fought with the banner until he fell; Ja\'far took it and fought until his right hand was severed, then held it in his left until that too was cut, then clasped it with his upper arms until he was killed — and God gave him two wings to fly with in Paradise, whence his title "al-Tayyar." Ibn Rawahah then took it and was martyred. The men then agreed on Khalid ibn al-Walid, who executed a masterly fighting withdrawal that saved the army — of which the Prophet ﷺ said: "Then the banner was taken by a sword among the swords of God, and God granted victory at his hands." In Medina, the Prophet ﷺ announced the three martyrs\' deaths before any news arrived, his eyes streaming. Mu\'tah was the first great clash with Byzantium, winning the Muslims an awe that paved the way for the conquests of Syria.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'زيد بن حارثة', 'description' => 'حِبّ رسول الله ﷺ ومولاه، الأمير الأول يوم مؤتة، قاتل بالراية حتى استُشهد.'],
                            'en' => ['name' => 'Zayd ibn Harithah', 'description' => 'The beloved freedman of the Prophet ﷺ and first commander at Mu\'tah, who fought with the banner until martyred.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'جعفر بن أبي طالب', 'description' => 'ذو الجناحين، قُطعت يداه وهو يحمل الراية حتى استُشهد، فأبدله الله جناحين في الجنة.'],
                            'en' => ['name' => 'Ja\'far ibn Abi Talib', 'description' => 'Lost both hands holding the banner before falling; God gave him two wings in Paradise — "al-Tayyar."'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عبد الله بن رواحة', 'description' => 'الشاعر الأنصاري، الأمير الثالث يوم مؤتة، حسم الشورى بكلمته واستُشهد بالراية.'],
                            'en' => ['name' => 'Abdullah ibn Rawahah', 'description' => 'The Ansari poet and third commander, whose word decided the council; he fell with the banner.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'خالد بن الوليد', 'description' => 'سيف الله المسلول، أنقذ الجيش بانسحاب مظفر في أول مشاهده مسلمًا.'],
                            'en' => ['name' => 'Khalid ibn al-Walid', 'description' => 'The Drawn Sword of God, who saved the army with a masterly withdrawal in his first battle as a Muslim.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'fath-makkah',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => '20 رمضان 8 هـ',
                'hijri_year' => 8,
                'hijri_month' => 9,
                'hijri_day' => 20,
                'gregorian_date' => '630-01-11',
                'gregorian_year' => 630,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'statistics' => [
                    'muslim_forces' => 10000,
                    'muslim_martyrs' => 2,
                    'enemy_casualties' => 12,
                    'duration_days' => 1,
                    'idols_destroyed' => 360,
                ],
                'display_order' => 540,
                'translations' => [
                    'ar' => [
                        'title' => 'فتح مكة (الفتح الأعظم)',
                        'description' => 'نقضت قريش صلح الحديبية حين أعانت بني بكر على خزاعة حلفاء رسول الله ﷺ، وقتلوهم في الحرم، فجاء عمرو بن سالم الخزاعي مستصرخًا، فقال ﷺ: «نُصرت يا عمرو بن سالم». وندمت قريش فأرسلت أبا سفيان يجدد العهد فرجع بغير شيء. وتجهز رسول الله ﷺ في عشرة آلاف في رمضان سنة ثمان وسأل الله أن يُعمّي خبره على قريش، حتى نزل بمرّ الظهران، فخرج أبو سفيان يتجسس فأخذه العباس وأجاره وأدخله على النبي ﷺ فأسلم، وأعلن ﷺ: «من دخل دار أبي سفيان فهو آمن، ومن أغلق بابه فهو آمن، ومن دخل المسجد فهو آمن». ودخل ﷺ مكة من أعلاها مطأطئ الرأس على راحلته تواضعًا لله حتى إن ذقنه ليكاد يمس واسطة الرحل، يقرأ سورة الفتح، وما قاتلت إلا فرقة خالد قتالًا يسيرًا. فطاف بالبيت وحوله ثلاثمائة وستون صنمًا، فجعل يطعنها بعود في يده ويقول: ﴿جَاءَ الْحَقُّ وَزَهَقَ الْبَاطِلُ إِنَّ الْبَاطِلَ كَانَ زَهُوقًا﴾، وصلى بلال فوق الكعبة وأذّن. ثم قام في قريش وقد ملك رقاب من آذوه وأخرجوه عشرين سنة فقال: «ما تظنون أني فاعل بكم؟» قالوا: أخ كريم وابن أخ كريم. قال: «اذهبوا فأنتم الطلقاء» — فكان أعظم عفو في التاريخ عند أعظم مقدرة. وكسّرت قريش أصنامها ودخل الناس في دين الله أفواجًا، وبقيت مكة دار إسلام إلى يوم الدين.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Conquest of Mecca (The Greatest Victory)',
                        'description' => 'Quraysh broke the treaty of al-Hudaybiyyah by aiding Banu Bakr against Khuza\'ah, the Prophet\'s ﷺ allies, slaying them even within the Sanctuary. Amr ibn Salim al-Khuza\'i came crying for help, and the Prophet ﷺ answered: "You are helped, O Amr ibn Salim." Regretful, Quraysh sent Abu Sufyan to renew the pact; he returned empty-handed. The Prophet ﷺ mobilized ten thousand men in Ramadan of 8 AH, praying that God veil his march from Quraysh, and camped at Marr al-Zahran. Abu Sufyan, out scouting, was taken by al-Abbas, given protection, brought before the Prophet ﷺ — and embraced Islam. The Prophet ﷺ proclaimed: "Whoever enters the house of Abu Sufyan is safe; whoever shuts his door is safe; whoever enters the Mosque is safe." He entered Mecca from its heights, head bowed upon his mount in humility before God until his beard nearly touched the saddle, reciting Surah al-Fath; only Khalid\'s column met brief resistance. He circled the House, around which stood three hundred and sixty idols, striking them with the staff in his hand and reciting: "Truth has come and falsehood has vanished; indeed falsehood is ever bound to vanish" (al-Isra 81). Bilal ascended the Kaaba and gave the call to prayer. Then he ﷺ stood before Quraysh — now utterly at the mercy of the man they had persecuted and exiled for twenty years — and asked: "What do you suppose I shall do with you?" They said: "A noble brother, son of a noble brother." He said: "Go — you are the freed ones." It was history\'s greatest pardon at the moment of greatest power. Quraysh shattered their idols, the people entered God\'s religion in throngs, and Mecca remained an abode of Islam until the end of time.',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'نقض قريش للصلح', 'description' => 'أعانت قريش بني بكر على خزاعة حلفاء النبي ﷺ وقتلوهم في الحرم، فسقط الصلح.'],
                        'en' => ['title' => 'Quraysh breaks the treaty', 'description' => 'Quraysh armed Banu Bakr against the Prophet\'s ﷺ allies Khuza\'ah, slaying them in the Sanctuary and voiding the truce.'],
                    ],
                    [
                        'ar' => ['title' => 'المسير في عشرة آلاف', 'description' => 'خرج ﷺ في رمضان سنة 8هـ في عشرة آلاف، ودعا الله أن يُعمّي الأخبار على قريش.'],
                        'en' => ['title' => 'The march of ten thousand', 'description' => 'In Ramadan 8 AH he ﷺ marched with ten thousand men, praying that the news be veiled from Quraysh.'],
                    ],
                    [
                        'ar' => ['title' => 'إسلام أبي سفيان بمر الظهران', 'description' => 'أجار العباسُ أبا سفيان وأدخله على النبي ﷺ فأسلم، وجُعلت داره مأمنًا لأهل مكة.'],
                        'en' => ['title' => 'Abu Sufyan embraces Islam', 'description' => 'Protected by al-Abbas, Abu Sufyan came before the Prophet ﷺ and embraced Islam; his house was declared a sanctuary.'],
                    ],
                    [
                        'ar' => ['title' => 'دخول مكة متواضعًا لله', 'description' => 'دخل ﷺ من أعلى مكة مطأطئ الرأس على راحلته يقرأ سورة الفتح، فما كان فتحٌ أرحم منه.'],
                        'en' => ['title' => 'The humble entry', 'description' => 'He ﷺ entered Mecca head bowed upon his mount, reciting Surah al-Fath — no conquest was ever more merciful.'],
                    ],
                    [
                        'ar' => ['title' => 'تطهير الكعبة من الأصنام', 'description' => 'حطّم ﷺ ثلاثمائة وستين صنمًا حول البيت وهو يتلو: جاء الحق وزهق الباطل، وأذّن بلال فوق الكعبة.'],
                        'en' => ['title' => 'Cleansing the Kaaba', 'description' => 'Three hundred and sixty idols fell as he ﷺ recited "Truth has come and falsehood has vanished"; Bilal called the adhan atop the Kaaba.'],
                    ],
                    [
                        'ar' => ['title' => 'العفو العام: اذهبوا فأنتم الطلقاء', 'description' => 'عفا ﷺ عن قريش كلها عند تمام المقدرة، فدخل الناس في دين الله أفواجًا.'],
                        'en' => ['title' => 'The general amnesty', 'description' => '"Go — you are the freed ones": total pardon at the moment of total power, and the people entered Islam in throngs.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'دخل مكة فاتحًا متواضعًا وعفا عن قريش العفو العام يوم الفتح.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Entered Mecca as a humble conqueror and granted Quraysh the general amnesty on the day of victory.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'العباس بن عبد المطلب', 'description' => 'عم النبي ﷺ، أجار أبا سفيان وأدخله عليه فأسلم قبيل الفتح.'],
                            'en' => ['name' => 'Al-Abbas ibn Abd al-Muttalib', 'description' => 'The Prophet\'s ﷺ uncle, who protected Abu Sufyan and brought him to embrace Islam before the conquest.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'خالد بن الوليد', 'description' => 'قائد المجنبة اليمنى يوم الفتح، لقي مقاومة يسيرة بالخندمة فحسمها.'],
                            'en' => ['name' => 'Khalid ibn al-Walid', 'description' => 'Commanded the right flank at the conquest, swiftly quelling the only brief resistance at al-Khandamah.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'بلال بن رباح', 'description' => 'مؤذن رسول الله ﷺ، صعد فوق الكعبة يوم الفتح فأذّن بمرأى قريش.'],
                            'en' => ['name' => 'Bilal ibn Rabah', 'description' => 'The Prophet\'s ﷺ muezzin, who ascended the Kaaba on the day of conquest and gave the call to prayer before all Quraysh.'],
                        ],
                    ],
                    [
                        'role' => 'ally',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو سفيان بن حرب', 'description' => 'زعيم قريش، أسلم ليلة الفتح وجُعلت داره مأمنًا لأهل مكة.'],
                            'en' => ['name' => 'Abu Sufyan ibn Harb', 'description' => 'Chief of Quraysh, who embraced Islam on the eve of the conquest; his house was made a sanctuary.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-hunayn',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'hijri_date' => '10 شوال 8 هـ',
                'hijri_year' => 8,
                'hijri_month' => 10,
                'hijri_day' => 10,
                'gregorian_year' => 630,
                'location' => 'وادي حنين بين مكة والطائف',
                'latitude' => 21.4436000,
                'longitude' => 40.0581000,
                'statistics' => [
                    'muslim_forces' => 12000,
                    'enemy_forces' => 20000,
                    'muslim_martyrs' => 4,
                    'enemy_casualties' => 70,
                    'prisoners' => 6000,
                    'duration_days' => 1,
                ],
                'display_order' => 550,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة حنين',
                        'description' => 'لما فتح الله مكة اجتمعت هوازن وثقيف في عشرين ألفًا بقيادة مالك بن عوف النصري، وساقوا معهم أموالهم ونساءهم وأبناءهم ليقاتل الرجال عن حُرُماتهم، فخرج إليهم رسول الله ﷺ في اثني عشر ألفًا — عشرة آلاف من أهل المدينة وألفان من مسلمة الفتح — حتى قال بعضهم: لن نُغلب اليوم من قلة! فوكَلهم الله إلى كلمتهم ليعلموا أن النصر من عنده وحده. فلما انحدروا في وادي حنين غبَشًا رشقتهم هوازن — وكانوا رماة — من مضايق الوادي رشقًا واحدًا، فانكشف الناس راجعين لا يلوي أحد على أحد. وثبت رسول الله ﷺ على بغلته البيضاء يركضها نحو العدو وهو يقول: «أنا النبي لا كذب، أنا ابن عبد المطلب»، وحوله نفر من المهاجرين وأهل بيته، وأمر عمه العباس — وكان جهير الصوت — فنادى: يا أصحاب السمرة! يا أهل بيعة الرضوان! فأقبل الناس يقولون: لبيك لبيك، وتراجعوا تراجع البقر على أولادها حتى احتدم القتال، وأخذ ﷺ قبضة من تراب فرمى بها وجوه القوم فما بقي منهم أحد إلا امتلأت عيناه ترابًا، وأنزل الله جنودًا لم يروها، فانهزمت هوازن هزيمة منكرة. وغنم المسلمون ستة آلاف سبي وأموالًا عظيمة، ثم جاء وفد هوازن مسلمين فردّ عليهم ﷺ سباياهم، وتألّف قلوب مسلمة الفتح بالعطاء. وأنزل الله: ﴿وَيَوْمَ حُنَيْنٍ إِذْ أَعْجَبَتْكُمْ كَثْرَتُكُمْ فَلَمْ تُغْنِ عَنكُمْ شَيْئًا﴾.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Battle of Hunayn',
                        'description' => 'After the conquest of Mecca, Hawazin and Thaqif mustered twenty thousand under Malik ibn Awf, driving their wealth, women and children behind them so their men would fight to the last. The Prophet ﷺ marched out with twelve thousand — ten thousand from Medina and two thousand newly-Muslim Meccans — until some said: "We shall not be beaten today for want of numbers!" So God left them to their word, that they might learn victory is from Him alone. As they descended the valley of Hunayn in the dawn dimness, Hawazin\'s archers loosed upon them from the defiles in a single volley, and the army broke and fled, none heeding another. The Prophet ﷺ stood firm on his white mule, spurring it toward the enemy, declaring: "I am the Prophet, no lie; I am the son of Abd al-Muttalib," with a handful of Muhajirun and his household around him. He ordered his uncle al-Abbas, whose voice was mighty, to call out: "O companions of the acacia tree! O people of the Pledge of Ridwan!" — and the men turned back crying "At your service!", wheeling round as a she-camel turns to her calf, until the battle raged. He ﷺ cast a handful of dust into the enemies\' faces, and not a man of them but his eyes filled with it; God sent down hosts unseen, and Hawazin broke in utter rout. The Muslims took six thousand captives and vast spoils; when Hawazin\'s delegation came as Muslims, the Prophet ﷺ returned their captives, and he reconciled the hearts of the new Meccan Muslims with generous gifts. God revealed: "And on the day of Hunayn, when your great number pleased you — but it availed you nothing" (al-Tawbah 25).',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'ثبت يوم حنين حين انكشف الناس وقال: أنا النبي لا كذب، أنا ابن عبد المطلب.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Stood firm at Hunayn when the lines broke: "I am the Prophet, no lie; I am the son of Abd al-Muttalib."'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'العباس بن عبد المطلب', 'description' => 'نادى الناس بصوته الجهير يوم حنين فثابوا إلى رسول الله ﷺ.'],
                            'en' => ['name' => 'Al-Abbas ibn Abd al-Muttalib', 'description' => 'His mighty voice rallied the scattered army back to the Prophet ﷺ at Hunayn.'],
                        ],
                    ],
                    [
                        'role' => 'enemy',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'مالك بن عوف النصري', 'description' => 'قائد هوازن يوم حنين، أسلم بعدُ فردّ عليه النبي ﷺ أهله وماله.'],
                            'en' => ['name' => 'Malik ibn Awf al-Nasri', 'description' => 'Commander of Hawazin at Hunayn; he later embraced Islam and the Prophet ﷺ restored his family and wealth.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ghazwat-tabuk',
                'event_type' => 'battle',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'is_pivotal' => true,
                'hijri_date' => 'رجب 9 هـ',
                'hijri_year' => 9,
                'hijri_month' => 7,
                'gregorian_year' => 630,
                'location' => 'تبوك شمال الحجاز',
                'latitude' => 28.3838000,
                'longitude' => 36.5550000,
                'statistics' => [
                    'muslim_forces' => 30000,
                    'duration_days' => 20,
                    'distance_km' => 700,
                ],
                'display_order' => 560,
                'translations' => [
                    'ar' => [
                        'title' => 'غزوة تبوك (غزوة العسرة)',
                        'description' => 'بلغ رسولَ الله ﷺ أن الروم تجمع الجموع بالشام تريد غزو المدينة، فأعلن النفير في رجب سنة تسع — على غير عادته في التورية — لبُعد الشُّقّة وشدة الحر وجدْب البلاد وطيب الثمار، فكانت تمحيصًا للمؤمنين وفضيحة للمنافقين الذين تخلفوا واعتذروا بالأعذار الكاذبة. وحضّ ﷺ على النفقة، فجاء عثمان بألف دينار وجهّز ثلث الجيش فقال ﷺ: «ما ضرّ عثمان ما عمل بعد اليوم»، وجاء أبو بكر بماله كله وعمر بنصفه، وجاء البكّاؤون يسألون الحُملان فلم يجد لهم فتولوا وأعينهم تفيض من الدمع حزنًا، فأنزل الله عذرهم. وسار ﷺ في ثلاثين ألفًا — أعظم جيوش الإسلام إلى يومئذ — في حر شديد وعسرة من الماء والزاد حتى سُمي «جيش العسرة». فلما بلغ تبوك لم يجد للروم جمعًا؛ إذ ألقى الله الرعب في قلوبهم فتفرقوا في بلادهم، فأقام بها عشرين ليلة يصالح أهل الأطراف: صالح يوحنّا بن رؤبة صاحب أيلة وأهل جرباء وأذرح على الجزية، ودانت تخوم الشام للمسلمين بغير قتال. وفي العودة تخلّف عن موكبه كعب بن مالك وصاحباه ممن صدقوا فنزلت توبتهم في القرآن: ﴿وَعَلَى الثَّلَاثَةِ الَّذِينَ خُلِّفُوا﴾. وكانت تبوك آخر غزوات رسول الله ﷺ، وفيها نزل أكثر سورة التوبة، وثبتت بها هيبة الدولة المسلمة في وجه أعظم قوة في الأرض.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Expedition of Tabuk (The Army of Hardship)',
                        'description' => 'Word reached the Prophet ﷺ that Byzantium was massing forces in Syria to strike Medina. He proclaimed a general mobilization in Rajab of 9 AH — openly, contrary to his custom of concealment — because of the vast distance, the fierce heat, the drought, and the ripening of the harvest: a test that sifted the believers and exposed the hypocrites who stayed behind with false excuses. He ﷺ urged contributions: Uthman brought a thousand dinars and equipped a third of the army — "Nothing Uthman does after today can harm him," said the Prophet ﷺ — Abu Bakr brought all his wealth, Umar half of his; and the "Weepers" came begging for mounts, turning away with eyes overflowing with tears when none could be found, and God revealed their excuse. The Prophet ﷺ marched with thirty thousand — the greatest Muslim army yet — through scorching heat and such scarcity of water and provisions that it was named the Army of Hardship. Reaching Tabuk, he found no Byzantine host: God had cast dread into their hearts and they had scattered into their lands. He remained twenty nights, making treaties with the border rulers — Yuhanna ibn Ru\'bah of Aylah and the peoples of Jarba and Adhruh accepted the jizyah — and the marches of Syria submitted without battle. On the return, Ka\'b ibn Malik and his two truthful companions who had lagged behind had their repentance revealed in the Quran: "And to the three who were left behind..." (al-Tawbah 118). Tabuk was the last expedition of the Messenger of God ﷺ; most of Surah al-Tawbah descended concerning it, and by it the Muslim state\'s authority stood firm before the greatest power on earth.',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'النفير العام وإعلان الوجهة', 'description' => 'أعلن ﷺ وجهته إلى الروم على غير عادته لبعد الشقة وشدة الساعة، فكانت تمحيصًا للصفوف.'],
                        'en' => ['title' => 'The open mobilization', 'description' => 'Breaking his custom of secrecy, he ﷺ declared the destination — a sifting of ranks in the hour of hardship.'],
                    ],
                    [
                        'ar' => ['title' => 'تجهيز جيش العسرة', 'description' => 'جهّز عثمان ثلث الجيش وجاء أبو بكر بماله كله وعمر بنصفه، ورجع البكّاؤون بدموعهم.'],
                        'en' => ['title' => 'Equipping the Army of Hardship', 'description' => 'Uthman outfitted a third of the army, Abu Bakr gave all his wealth, Umar half — and the Weepers turned away in tears.'],
                    ],
                    [
                        'ar' => ['title' => 'المسير في الحر والعسرة', 'description' => 'سار ثلاثون ألفًا سبعمائة كيلومتر في قيظ الصيف وقلة الظهر والماء، يتعاقب النفر على البعير الواحد.'],
                        'en' => ['title' => 'The march through the heat', 'description' => 'Thirty thousand men crossed seven hundred kilometres of summer desert, several sharing a single camel.'],
                    ],
                    [
                        'ar' => ['title' => 'الإقامة بتبوك وتفرق الروم', 'description' => 'قذف الله الرعب في قلوب الروم فلم يلقَ جمعًا، وأقام ﷺ عشرين ليلة سيد الموقف.'],
                        'en' => ['title' => 'Twenty nights at Tabuk', 'description' => 'God cast dread into Byzantium\'s hearts; no army appeared, and he ﷺ held the field for twenty nights unchallenged.'],
                    ],
                    [
                        'ar' => ['title' => 'صلح أيلة وجرباء وأذرح', 'description' => 'صالح أمراء التخوم على الجزية فدانت أطراف الشام للمسلمين بغير قتال.'],
                        'en' => ['title' => 'Treaties with the border rulers', 'description' => 'Aylah, Jarba and Adhruh accepted terms, and the Syrian marches submitted without a blow.'],
                    ],
                    [
                        'ar' => ['title' => 'توبة الثلاثة الذين خُلِّفوا', 'description' => 'صدق كعب بن مالك وصاحباه فهُجروا خمسين ليلة حتى نزلت توبتهم قرآنًا يُتلى.'],
                        'en' => ['title' => 'The repentance of the three', 'description' => 'Ka\'b ibn Malik and his two companions spoke truth, endured fifty nights of boycott, and their pardon descended as Quran.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'قاد جيش العسرة بنفسه في آخر غزواته إلى تخوم الروم.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Personally led the Army of Hardship to the Byzantine marches in his final expedition.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عثمان بن عفان', 'description' => 'جهّز ثلث جيش العسرة بماله، فقال ﷺ: ما ضرّ عثمان ما عمل بعد اليوم.'],
                            'en' => ['name' => 'Uthman ibn Affan', 'description' => 'Equipped a third of the Army of Hardship — "Nothing Uthman does after today can harm him."'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'كعب بن مالك', 'description' => 'الصادق الذي تخلّف بلا عذر فصدق، ونزلت توبته في سورة التوبة.'],
                            'en' => ['name' => 'Ka\'b ibn Malik', 'description' => 'The truthful one who lagged behind without excuse, confessed honestly, and whose pardon was revealed in Surah al-Tawbah.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'aam-al-wufud',
                'event_type' => 'delegation',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'hijri_date' => '9-10 هـ',
                'hijri_year' => 9,
                'gregorian_year' => 631,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'statistics' => [
                    'delegations_count' => 70,
                ],
                'display_order' => 570,
                'translations' => [
                    'ar' => [
                        'title' => 'عام الوفود',
                        'description' => 'لما فتح الله مكة وفرغ رسول الله ﷺ من تبوك وأسلمت ثقيف، عرفت العرب أنه لا طاقة لها بحرب صاحب الحرم، فأقبلت إليه وفود القبائل من أقطار الجزيرة تعلن إسلامها، حتى سميت السنتان التاسعة والعاشرة «عام الوفود»، وفيهما نزل قوله تعالى: ﴿إِذَا جَاءَ نَصْرُ اللَّهِ وَالْفَتْحُ وَرَأَيْتَ النَّاسَ يَدْخُلُونَ فِي دِينِ اللَّهِ أَفْوَاجًا﴾. زاد عددها على سبعين وفدًا: وفد ثقيف بعد حصار الطائف وقد أبوا أولًا ثم أسلموا وهدموا اللات بأيديهم، ووفد بني تميم الذين نادوه من وراء الحجرات وفيهم خطيبهم وشاعرهم ففاخرهم ففاقهم خطيبُه ثابت بن قيس وشاعرُه حسان، ووفد عبد القيس من البحرين الذين قال لهم: «مرحبًا بالوفد غير الخزايا ولا الندامى»، ووفد بني حنيفة وفيهم مسيلمة الذي تنبّأ بعدُ فكذبه الله، ووفد نجران من نصارى اليمن الذين باهلهم فأبوا المباهلة وصالحوا على الجزية — وفيهم نزل صدر سورة آل عمران — ووفد طيّئ وفيهم زيد الخيل الذي سماه «زيد الخير»، ووفد الأشعريين وأهل اليمن الذين قال فيهم: «أتاكم أهل اليمن أرقّ أفئدة وألين قلوبًا، الإيمان يمانٍ والحكمة يمانية». وكان ﷺ يكرم الوفود ويجيزهم ويعلمهم الدين ويبعث معهم المعلمين، فدخلت الجزيرة كلها في الإسلام في أقل من عامين.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Year of Delegations',
                        'description' => 'With Mecca conquered, Tabuk concluded, and Thaqif\'s submission, the Arabs knew that no tribe could withstand the master of the Sanctuary, and tribal delegations streamed to Medina from every corner of the peninsula to declare their Islam — so the ninth and tenth years were named "the Year of Delegations," concerning which God revealed: "When the victory of God has come and the conquest, and you see the people entering the religion of God in throngs" (al-Nasr 1-2). More than seventy delegations came: Thaqif after the siege of Ta\'if, who first refused, then submitted and demolished their idol al-Lat with their own hands; Banu Tamim, who called out from behind the chambers and matched orators and poets with the Muslims — and were outdone by Thabit ibn Qays and Hassan; Abd al-Qays from Bahrain, whom he greeted: "Welcome to the delegation, neither disgraced nor regretful"; Banu Hanifah, among whom was Musaylimah who later claimed prophethood and was exposed as a liar; the Christians of Najran, who declined the mutual imprecation (mubahalah) and accepted treaty terms — the opening of Surah Al Imran descended concerning them; Tayy, among them Zayd al-Khayl whom he renamed "Zayd al-Khayr" (Zayd of Goodness); and the Ash\'aris and the people of Yemen, of whom he said: "The people of Yemen have come to you, tenderest of heart and softest of soul; faith is Yemeni and wisdom is Yemeni." He ﷺ honoured the delegations, gave them gifts, taught them the religion and sent teachers back with them — and within less than two years the whole peninsula had entered Islam.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'hajjat-al-wida',
                'event_type' => 'personal',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => '9 ذو الحجة 10 هـ',
                'hijri_year' => 10,
                'hijri_month' => 12,
                'hijri_day' => 9,
                'gregorian_date' => '632-03-06',
                'gregorian_year' => 632,
                'location' => 'مكة المكرمة وعرفات',
                'latitude' => 21.3549000,
                'longitude' => 39.9841000,
                'statistics' => [
                    'pilgrims' => 100000,
                ],
                'display_order' => 580,
                'translations' => [
                    'ar' => [
                        'title' => 'حجة الوداع وخطبتها الجامعة',
                        'description' => 'في ذي القعدة من السنة العاشرة أذّن رسول الله ﷺ في الناس أنه حاجّ هذا العام، فقدم المدينةَ خلقٌ كثير كلهم يلتمس أن يأتمّ به، وخرج معه نحو مائة ألف أو يزيدون، فحج بهم حجته الوحيدة بعد الهجرة وعلّمهم مناسكهم قولًا وعملًا وقال: «خذوا عني مناسككم». ووقف بعرفة يوم الجمعة التاسع من ذي الحجة فخطب الناس الخطبة الجامعة التي قرر فيها أصول الإسلام وحقوق الإنسان: «أيها الناس إن دماءكم وأموالكم وأعراضكم عليكم حرام كحرمة يومكم هذا في شهركم هذا في بلدكم هذا... ألا كل شيء من أمر الجاهلية تحت قدميّ موضوع، وربا الجاهلية موضوع... فاتقوا الله في النساء فإنكم أخذتموهن بأمان الله... إن ربكم واحد وإن أباكم واحد، لا فضل لعربي على عجمي ولا لأبيض على أسود إلا بالتقوى... وقد تركت فيكم ما لن تضلوا بعده إن اعتصمتم به: كتاب الله». ثم استشهدهم: «ألا هل بلغت؟» قالوا: نعم، قال: «اللهم اشهد، فليبلغ الشاهد الغائب». وفي ذلك الموقف نزل قوله تعالى: ﴿الْيَوْمَ أَكْمَلْتُ لَكُمْ دِينَكُمْ وَأَتْمَمْتُ عَلَيْكُمْ نِعْمَتِي وَرَضِيتُ لَكُمُ الْإِسْلَامَ دِينًا﴾، فبكى أبو بكر إذ فهم أنه نعيُ رسول الله ﷺ؛ فما الكمال إلا ودونه الرحيل. فكانت حجة الوداع خاتمة البلاغ ووصية النبي ﷺ الجامعة للأمة إلى يوم القيامة.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Farewell Pilgrimage and Its Universal Sermon',
                        'description' => 'In Dhu al-Qa\'dah of 10 AH the Prophet ﷺ announced that he would perform the Hajj that year, and multitudes flocked to Medina, all longing to follow him. He set out with some one hundred thousand or more, leading them in his only pilgrimage after the Hijra, teaching them their rites by word and deed: "Take your rites from me." On Friday the ninth of Dhu al-Hijjah he stood at Arafat and delivered the comprehensive sermon in which he laid down the foundations of Islam and the rights of man: "O people, your blood, your property and your honour are as sacred to one another as this day of yours, in this month of yours, in this city of yours... Every matter of the Age of Ignorance is abolished beneath my feet, and the usury of that age is abolished... Fear God concerning women, for you have taken them by the trust of God... Your Lord is One and your father is one: no Arab has superiority over a non-Arab, nor white over black, save by piety... I have left among you that which, if you hold fast to it, you will never go astray: the Book of God." Then he called them to witness: "Have I conveyed the message?" They answered: "Yes." He said: "O God, bear witness — and let the present convey it to the absent." At that station descended the words: "This day I have perfected your religion for you, completed My favour upon you, and approved Islam as your religion" (al-Ma\'idah 3) — and Abu Bakr wept, understanding it as the announcement of the Prophet\'s ﷺ approaching departure, for after perfection comes only leave-taking. The Farewell Pilgrimage was the seal of the message and the Prophet\'s ﷺ universal testament to the Ummah until the Day of Judgement.',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'الإعلان والخروج في مائة ألف', 'description' => 'أذّن ﷺ في الناس بالحج فخرج معه نحو مائة ألف من أقطار الجزيرة.'],
                        'en' => ['title' => 'The announcement and the march', 'description' => 'He ﷺ proclaimed the pilgrimage, and some one hundred thousand set out with him from all Arabia.'],
                    ],
                    [
                        'ar' => ['title' => 'تعليم المناسك', 'description' => 'حج بالناس وعلّمهم مناسكهم قولًا وفعلًا وقال: خذوا عني مناسككم.'],
                        'en' => ['title' => 'Teaching the rites', 'description' => 'He led the rites in person, instructing by word and deed: "Take your rites from me."'],
                    ],
                    [
                        'ar' => ['title' => 'خطبة عرفات الجامعة', 'description' => 'قرر حرمة الدماء والأموال والأعراض، ووضع الربا ومآثر الجاهلية، وأوصى بالنساء وبكتاب الله.'],
                        'en' => ['title' => 'The sermon at Arafat', 'description' => 'He proclaimed the sanctity of life, property and honour, abolished usury and pagan claims, and enjoined the Book of God.'],
                    ],
                    [
                        'ar' => ['title' => 'إعلان المساواة بين الناس', 'description' => 'لا فضل لعربي على عجمي ولا لأبيض على أسود إلا بالتقوى — ميثاق المساواة الخالد.'],
                        'en' => ['title' => 'The charter of equality', 'description' => '"No Arab over non-Arab, no white over black, save by piety" — the timeless charter of human equality.'],
                    ],
                    [
                        'ar' => ['title' => 'نزول آية إكمال الدين', 'description' => 'نزلت بعرفة: اليوم أكملت لكم دينكم — فبكى أبو بكر إذ فهم دنوّ الأجل.'],
                        'en' => ['title' => 'The verse of perfection', 'description' => '"This day I have perfected your religion for you" descended at Arafat — and Abu Bakr wept, sensing the farewell.'],
                    ],
                    [
                        'ar' => ['title' => 'الإشهاد والتبليغ', 'description' => 'قال: ألا هل بلغت؟ اللهم اشهد، فليبلغ الشاهد الغائب — فكانت وصيته للأمة كلها.'],
                        'en' => ['title' => 'The witnessing', 'description' => '"Have I conveyed? O God, bear witness — let the present convey it to the absent": his testament to all generations.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'حج حجته الوحيدة وخطب خطبة الوداع الجامعة بعرفات.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Performed his only pilgrimage and delivered the universal Farewell Sermon at Arafat.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو بكر الصديق', 'description' => 'بكى عند نزول آية إكمال الدين إذ فهم منها نعي رسول الله ﷺ.'],
                            'en' => ['name' => 'Abu Bakr al-Siddiq', 'description' => 'Wept at the verse of the religion\'s perfection, understanding it as the Prophet\'s ﷺ farewell.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'wafat-al-nabi',
                'event_type' => 'death',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => '12 ربيع الأول 11 هـ',
                'hijri_year' => 11,
                'hijri_month' => 3,
                'hijri_day' => 12,
                'gregorian_date' => '632-06-08',
                'gregorian_year' => 632,
                'location' => 'المدينة المنورة، حجرة عائشة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 590,
                'translations' => [
                    'ar' => [
                        'title' => 'وفاة الرسول ﷺ والتحاقه بالرفيق الأعلى',
                        'description' => 'لما أكمل الله الدين وأتمّ النعمة دنا أجل رسول الله ﷺ، وبدأ به مرضه في أواخر صفر سنة إحدى عشرة بعد رجوعه من البقيع، فاشتد به الوجع في بيت ميمونة، فاستأذن أزواجه أن يُمرَّض في بيت عائشة، فخرج بين العباس وعلي معصوب الرأس تخط قدماه الأرض. ولما ثقل عن الخروج أمر: «مروا أبا بكر فليصلِّ بالناس»، فصلى بهم أبو بكر سبع عشرة صلاة في حياته ﷺ — إشارة قدرية إلى الخلافة. وفي يوم الاثنين كشف ستر الحجرة والناس صفوف خلف أبي بكر في صلاة الفجر، فتبسم يضحك سرورًا بهم، فما رُئي منظر كان أعجب إليهم من وجهه ذلك اليوم، ثم أرخى الستر. واشتد به الوجع فجعل يدخل يده في ركوة ماء يمسح بها وجهه ويقول: «لا إله إلا الله، إن للموت سكرات»، ثم خُيّر فاختار، وجعل يقول وبصره شاخص: «مع الذين أنعم الله عليهم من النبيين والصديقين والشهداء والصالحين... اللهم الرفيق الأعلى»، حتى قُبض ضحى يوم الاثنين الثاني عشر من ربيع الأول سنة 11هـ وهو ابن ثلاث وستين، ورأسه بين سحر عائشة ونحرها. فاضطرب المسلمون حتى قال عمر: ما مات رسول الله! فقام أبو بكر فقال كلمته الفاصلة: «من كان يعبد محمدًا فإن محمدًا قد مات، ومن كان يعبد الله فإن الله حي لا يموت»، وتلا: ﴿وَمَا مُحَمَّدٌ إِلَّا رَسُولٌ قَدْ خَلَتْ مِن قَبْلِهِ الرُّسُلُ﴾. وغُسّل ﷺ في ثيابه وصلى عليه المسلمون أرسالًا بلا إمام، ودُفن حيث قُبض في حجرة عائشة، وقال أنس: لما كان اليوم الذي دخل فيه المدينة أضاء منها كل شيء، فلما كان اليوم الذي مات فيه أظلم منها كل شيء.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Passing of the Prophet ﷺ to the Highest Companion',
                        'description' => 'When God had perfected the religion and completed His favour, the appointed term of His Messenger ﷺ drew near. His illness began in late Safar of 11 AH after his return from al-Baqi cemetery; the pain intensified in Maymunah\'s house, and he asked his wives\' leave to be nursed in Aisha\'s chamber, walking out supported between al-Abbas and Ali, his head bound, his feet dragging the ground. Too weak to come out to prayer, he commanded: "Tell Abu Bakr to lead the people in prayer" — and Abu Bakr led seventeen prayers in the Prophet\'s ﷺ lifetime, a providential sign of the caliphate to come. On the Monday he lifted the curtain of the chamber as the people stood in rows behind Abu Bakr at dawn prayer, and he smiled with joy at the sight of them — never had they beheld a sight more wondrous than his face that day — then let the curtain fall. As the agony intensified he dipped his hand in a vessel of water, wiping his face and saying: "There is no god but God; indeed death has its agonies." Then he was given the choice and chose, saying with his gaze fixed upward: "With those upon whom God has bestowed favour — the prophets, the truthful, the martyrs and the righteous... O God, the Highest Companion" — until he passed in the forenoon of Monday, 12 Rabi\' al-Awwal, 11 AH (8 June 632), aged sixty-three, his head resting against Aisha. The Muslims reeled — Umar declared he had not died — until Abu Bakr rose with the decisive words: "Whoever worshipped Muhammad, Muhammad has died; and whoever worships God, God is Living and does not die," reciting: "Muhammad is no more than a Messenger; messengers have passed away before him" (Al Imran 144). He ﷺ was washed in his garments, the Muslims prayed over him in successive groups with no imam, and he was buried where he died, in Aisha\'s chamber. Anas said: "On the day he entered Medina, everything in it was illuminated; on the day he died, everything in it went dark."',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'بداية المرض', 'description' => 'بدأ به الوجع أواخر صفر 11هـ بعد زيارته البقيع واستغفاره لأهله.'],
                        'en' => ['title' => 'The illness begins', 'description' => 'The pain began in late Safar 11 AH, after his visit to al-Baqi where he prayed for its people.'],
                    ],
                    [
                        'ar' => ['title' => 'التمريض في بيت عائشة', 'description' => 'استأذن أزواجه فمُرّض في حجرة عائشة، يخرج بين العباس وعلي معصوب الرأس.'],
                        'en' => ['title' => 'Nursing in Aisha\'s chamber', 'description' => 'With his wives\' consent he was nursed in Aisha\'s chamber, supported between al-Abbas and Ali.'],
                    ],
                    [
                        'ar' => ['title' => 'إمامة أبي بكر بالناس', 'description' => 'أمر: مروا أبا بكر فليصل بالناس — فصلى بهم سبع عشرة صلاة في حياته ﷺ.'],
                        'en' => ['title' => 'Abu Bakr leads the prayer', 'description' => '"Tell Abu Bakr to lead the people in prayer" — seventeen prayers in the Prophet\'s ﷺ own lifetime.'],
                    ],
                    [
                        'ar' => ['title' => 'الابتسامة الأخيرة يوم الاثنين', 'description' => 'كشف الستر فرأى الناس صفوفًا في الفجر فتبسم سرورًا، ثم أرخى الستر — وكان آخر العهد به.'],
                        'en' => ['title' => 'The last smile', 'description' => 'He lifted the curtain at dawn, smiled with joy at the rows of worshippers, and let it fall — their last sight of him.'],
                    ],
                    [
                        'ar' => ['title' => 'الرفيق الأعلى', 'description' => 'قُبض ضحى الاثنين 12 ربيع الأول وهو يقول: اللهم الرفيق الأعلى، ورأسه في حجر عائشة.'],
                        'en' => ['title' => 'The Highest Companion', 'description' => 'He passed at forenoon on Monday 12 Rabi\' al-Awwal, saying "O God, the Highest Companion," resting against Aisha.'],
                    ],
                    [
                        'ar' => ['title' => 'موقف أبي بكر والدفن', 'description' => 'ثبّت أبو بكر الأمة: من كان يعبد محمدًا فإن محمدًا قد مات — ودُفن ﷺ حيث قُبض بحجرة عائشة.'],
                        'en' => ['title' => 'Abu Bakr steadies the Ummah', 'description' => '"Whoever worshipped Muhammad, Muhammad has died" — and he ﷺ was buried where he passed, in Aisha\'s chamber.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'قُبض إلى الرفيق الأعلى يوم الاثنين 12 ربيع الأول 11هـ وهو ابن ثلاث وستين.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Passed to the Highest Companion on Monday 12 Rabi\' al-Awwal 11 AH, aged sixty-three.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو بكر الصديق', 'description' => 'صلى بالناس في مرضه ﷺ وثبّت الأمة يوم وفاته بكلمته الفاصلة.'],
                            'en' => ['name' => 'Abu Bakr al-Siddiq', 'description' => 'Led the prayers during the Prophet\'s ﷺ illness and steadied the Ummah with his decisive words at his death.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عائشة بنت أبي بكر', 'description' => 'أم المؤمنين، قُبض النبي ﷺ في حجرتها ورأسه بين سحرها ونحرها ودُفن فيها.'],
                            'en' => ['name' => 'Aisha bint Abi Bakr', 'description' => 'Mother of the Believers, in whose chamber the Prophet ﷺ passed away resting against her, and where he was buried.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'sariyyat-usama',
                'event_type' => 'delegation',
                'era' => 'medina',
                'phase' => 'final_years',
                'importance' => 'medium',
                'hijri_date' => 'صفر 11 هـ',
                'hijri_year' => 11,
                'hijri_month' => 2,
                'gregorian_year' => 632,
                'location' => 'الجُرف ثم أرض البلقاء بالشام',
                'latitude' => 24.5247000,
                'longitude' => 39.5772000,
                'statistics' => [
                    'muslim_forces' => 3000,
                ],
                'display_order' => 600,
                'translations' => [
                    'ar' => [
                        'title' => 'بعث أسامة بن زيد — آخر جيوش النبي ﷺ',
                        'description' => 'في صفر من السنة الحادية عشرة عقد رسول الله ﷺ لأسامة بن زيد بن حارثة لواء جيشٍ أمره أن يوطئ الخيل تخوم البلقاء والداروم من أرض فلسطين، ثأرًا لأبيه زيد ومن استُشهد معه بمؤتة، وضمّ إليه جلّة المهاجرين والأنصار فيهم عمر بن الخطاب. وكان أسامة شابًّا في الثامنة عشرة، فطعن بعض الناس في إمارته لحداثة سنه، فخطب ﷺ — وهو في مرضه — فقال: «إن تطعنوا في إمارته فقد كنتم تطعنون في إمارة أبيه من قبل، وايم الله إن كان لخليقًا للإمارة، وإن كان لمن أحب الناس إليّ، وإن هذا لمن أحب الناس إليّ بعده». فعسكر أسامة بالجُرف، فلما اشتد برسول الله ﷺ وجعه توقف الجيش، ثم قُبض ﷺ قبل أن يسير. فلما ولي أبو بكر أصرّ على إنفاذ البعث رغم ارتداد العرب وإشارة بعض الصحابة بحبسه، وقال كلمته المشهورة: «والله لا أحلّ عقدة عقدها رسول الله ﷺ، ولو جرّت الكلاب بأرجل أمهات المؤمنين لأنفذت جيش أسامة»، ومشى يشيّعه وأسامة راكب. فسار الجيش أربعين يومًا ورجع سالمًا غانمًا، فكان لإنفاذه أثر عظيم في هيبة الدولة؛ إذ قالت العرب: ما خرج هؤلاء من قوة إلا وعندهم منعة شديدة. فكان بعث أسامة وصية النبي ﷺ العسكرية الأخيرة، وأول قرارات الصدّيق نفاذًا لأمر صاحبه ﷺ.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Expedition of Usamah ibn Zayd — the Prophet\'s ﷺ Last Army',
                        'description' => 'In Safar of 11 AH the Prophet ﷺ tied the banner of an army for Usamah ibn Zayd ibn Harithah, commanding him to carry the cavalry to the marches of al-Balqa and al-Darum in the land of Palestine — retribution for his father Zayd and the martyrs of Mu\'tah. He placed under him the foremost of the Muhajirun and Ansar, Umar ibn al-Khattab among them. Usamah was a youth of eighteen, and some criticized his command for his young age; so the Prophet ﷺ, though ill, addressed the people: "If you impugn his command, you impugned his father\'s command before him. By God, he was worthy of command and among the dearest of people to me — and this one is among the dearest to me after him." Usamah encamped at al-Jurf, but as the Prophet\'s ﷺ illness worsened the army halted, and he ﷺ passed away before it marched. When Abu Bakr assumed the caliphate, he insisted on dispatching the expedition despite the apostasy sweeping Arabia and the counsel of some companions to hold it back, declaring famously: "By God, I will not untie a knot tied by the Messenger of God ﷺ — even if dogs were to drag at the feet of the Mothers of the Believers, I would send forth Usamah\'s army." He walked alongside as Usamah rode. The army campaigned forty days and returned safe with spoils, and its dispatch profoundly restored the state\'s prestige — the Arabs said: "These people would not send such a force unless they possessed formidable strength." It was the Prophet\'s ﷺ final military testament and the first command of al-Siddiq executed in obedience to his Companion ﷺ.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أسامة بن زيد', 'description' => 'الحِبّ ابن الحِبّ، أمّره النبي ﷺ على آخر جيوشه وهو ابن ثماني عشرة.'],
                            'en' => ['name' => 'Usamah ibn Zayd', 'description' => 'The beloved son of the beloved, appointed by the Prophet ﷺ over his last army at eighteen.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو بكر الصديق', 'description' => 'أنفذ بعث أسامة بعد وفاة النبي ﷺ وأبى أن يحل عقدة عقدها رسول الله.'],
                            'en' => ['name' => 'Abu Bakr al-Siddiq', 'description' => 'Dispatched Usamah\'s army after the Prophet\'s ﷺ death, refusing to untie a knot the Messenger had tied.'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
