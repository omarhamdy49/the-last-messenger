<?php

declare(strict_types=1);

namespace Database\Seeders\Seerah;

/**
 * Batch 3 of 5 — From the Year of Sorrow to the founding of the state in
 * Medina (619–623 CE).
 */
class SeerahEventsHijraSeeder extends SeerahEventSeederBase
{
    protected function events(): array
    {
        return [
            [
                'slug' => 'aam-al-huzn',
                'event_type' => 'death',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => '3 ق.هـ',
                'hijri_year' => -3,
                'gregorian_year' => 619,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 250,
                'translations' => [
                    'ar' => [
                        'title' => 'عام الحزن: وفاة أبي طالب وخديجة',
                        'description' => 'في العام العاشر من البعثة، وبعد الخروج من حصار الشعب بقليل، توالت على رسول الله ﷺ مصائب عظام جعلت أهل السير يسمّون ذلك العام «عام الحزن». فقد توفي عمه أبو طالب الذي كان حصنه المنيع وحاميه من بطش قريش منذ جهر بالدعوة؛ فقد كان بمكانته في قريش يذبّ عنه ويمنعه، فلما مات نالت قريش من رسول الله ﷺ ما لم تكن تطمع فيه في حياته، حتى نثر بعض سفهائها التراب على رأسه الشريف. وقد حرص ﷺ على إسلام عمه عند الموت فقال: «يا عم، قل لا إله إلا الله، كلمة أحاجّ لك بها عند الله»، لكن أبا طالب مات على ملة عبد المطلب بعدما غلبه قرناء السوء، فأنزل الله: ﴿إِنَّكَ لَا تَهْدِي مَنْ أَحْبَبْتَ وَلَكِنَّ اللَّهَ يَهْدِي مَن يَشَاءُ﴾. ثم لم يلبث أن توفيت بعده بنحو شهرين — وقيل ثلاثة أيام — أم المؤمنين خديجة بنت خويلد رضي الله عنها، وزيرة الصدق التي آمنت به إذ كفر الناس وصدّقته إذ كذّبوه وواسته بمالها ونفسها، فدفنها ﷺ بالحَجون بيده. فاجتمع على قلبه الشريف فقدُ الناصر الخارجي والسكن الداخلي في عام واحد، واشتد أذى قريش، فكان هذا العام من أشد أعوام السيرة بلاءً، وتمهيدًا لتحوّل عظيم في مسار الدعوة نحو آفاق جديدة خارج مكة.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Year of Sorrow: Deaths of Abu Talib and Khadijah',
                        'description' => 'In the tenth year of prophethood, shortly after the end of the boycott, calamities struck the Prophet ﷺ in succession, leading biographers to name it the Year of Sorrow. His uncle Abu Talib — his impregnable fortress against Quraysh since the call became public — passed away. At his deathbed the Prophet ﷺ pleaded: "O uncle, say there is no deity but God — a word by which I may argue for you before God," but Abu Talib, swayed by his companions, died upon the religion of Abdul-Muttalib, and God revealed: "You do not guide whom you love, but God guides whom He wills." With his protector gone, Quraysh dared abuses they had never attempted before — one fool even poured dust upon his noble head. Barely two months later (some say three days), Khadijah bint Khuwaylid passed away — the minister of truth who believed when people disbelieved, affirmed him when they denied him, and supported him with her wealth and her very self. He ﷺ laid her in the grave at al-Hajun with his own hands. The loss of his outward protector and inward solace in a single year, amid intensifying persecution, made this the hardest year of the Meccan period — and the prelude to a great turning of the mission toward horizons beyond Mecca.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'other',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو طالب بن عبد المطلب', 'description' => 'عم النبي ﷺ وكافله وحاميه، ذبّ عنه أربعين عامًا ومات قبل أن يسلم في العام العاشر من البعثة.'],
                            'en' => ['name' => 'Abu Talib ibn Abdul-Muttalib', 'description' => 'The Prophet\'s uncle, guardian, and protector for decades, who died without embracing Islam in the tenth year of prophethood.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'خديجة بنت خويلد', 'description' => 'أم المؤمنين وأول من آمن، توفيت في عام الحزن بعد ربع قرن من المؤازرة والوفاء.'],
                            'en' => ['name' => 'Khadijah bint Khuwaylid', 'description' => 'Mother of the Believers and the first to believe, who died in the Year of Sorrow after a quarter century of devoted support.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'rihlat-al-taif',
                'event_type' => 'other',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => 'شوال 3 ق.هـ',
                'hijri_year' => -3,
                'hijri_month' => 10,
                'gregorian_year' => 619,
                'location' => 'الطائف',
                'latitude' => 21.2703000,
                'longitude' => 40.4158000,
                'display_order' => 260,
                'translations' => [
                    'ar' => [
                        'title' => 'رحلة الطائف',
                        'description' => 'لما اشتد أذى قريش بعد وفاة أبي طالب خرج رسول الله ﷺ إلى الطائف في شوال من السنة العاشرة للبعثة، ومعه مولاه زيد بن حارثة، يلتمس النصرة من ثقيف ويرجو أن يقبلوا ما جاءهم به من الله. فعمد إلى ثلاثة نفر من ساداتهم هم إخوة عبد ياليل ومسعود وحبيب أبناء عمرو بن عمير، فجلس إليهم ودعاهم إلى الله، فردّوا عليه أقبح الرد؛ قال أحدهم: هو يَمرُط ثياب الكعبة إن كان الله أرسلك! وقال الآخر: أما وجد الله أحدًا غيرك؟! ثم أغروا به سفهاءهم وعبيدهم يسبّونه ويصيحون به ويرمونه بالحجارة حتى أدموا عقبيه الشريفتين، وزيد يقيه بنفسه حتى شُجّ رأسه. فالتجأ ﷺ إلى حائط لعتبة وشيبة ابني ربيعة، ودعا دعاءه المشهور الذي يفيض ضراعةً: «اللهم إليك أشكو ضعف قوتي وقلة حيلتي وهواني على الناس، يا أرحم الراحمين، أنت رب المستضعفين وأنت ربي... إن لم يكن بك عليّ غضب فلا أبالي». فأرسل ابنا ربيعة غلامهما النصراني عدّاسًا بقطف من عنب، فلما سمع عداس النبي ﷺ يذكر يونس بن متّى أكبّ يقبّل رأسه ويديه وقدميه. وفي طريق العودة أرسل الله إليه ملك الجبال يستأذنه أن يطبق الأخشبين على أهل مكة، فقال ﷺ: «بل أرجو أن يُخرج الله من أصلابهم من يعبد الله وحده لا يشرك به شيئًا» — فكان موقفه ذروةً في الرحمة وبُعد النظر.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Journey to Ta\'if',
                        'description' => 'As Quraysh\'s abuse intensified after Abu Talib\'s death, the Prophet ﷺ traveled to Ta\'if in Shawwal of the tenth year of prophethood, accompanied by Zayd ibn Harithah, seeking the support of Thaqif. He approached three of their chiefs — the brothers Abd Yalil, Mas\'ud, and Habib, sons of Amr ibn Umayr — but they answered with the ugliest mockery, then set their fools and slaves upon him, jeering and pelting him with stones until his blessed feet bled, while Zayd shielded him and was wounded in the head. Taking refuge in an orchard, he uttered his famous supplication overflowing with humility: "O God, to You I complain of my weakness, my scarcity of resources, and my insignificance before people. O Most Merciful of the merciful, You are the Lord of the oppressed and You are my Lord... If You are not angry with me, I do not care." The orchard\'s owners sent their Christian servant Addas with grapes; upon hearing the Prophet ﷺ mention Yunus son of Matta, Addas kissed his head, hands, and feet. On the return journey, God sent the Angel of the Mountains offering to crush Mecca between its two mountains. The Prophet ﷺ replied: "Rather, I hope God will bring forth from their descendants those who worship God alone" — a summit of mercy and far-sightedness.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'زيد بن حارثة', 'description' => 'مولى رسول الله ﷺ وحِبّه، رافقه إلى الطائف ووقاه بنفسه من حجارة سفهائها.'],
                            'en' => ['name' => 'Zayd ibn Harithah', 'description' => 'The Prophet\'s beloved freedman, who accompanied him to Ta\'if and shielded him with his own body.'],
                        ],
                    ],
                    [
                        'role' => 'witness',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عدّاس النصراني', 'description' => 'غلام نصراني من نينوى، عرف صدق النبي ﷺ حين ذكر يونس بن متى فقبّل رأسه ويديه.'],
                            'en' => ['name' => 'Addas the Christian', 'description' => 'A Christian servant from Nineveh who recognized the Prophet\'s truthfulness when he mentioned Yunus son of Matta.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'al-isra-wal-miraj',
                'event_type' => 'miracle',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => '27 رجب 2 ق.هـ على المشهور',
                'hijri_year' => -2,
                'hijri_month' => 7,
                'hijri_day' => 27,
                'gregorian_year' => 621,
                'location' => 'من المسجد الحرام إلى المسجد الأقصى',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 270,
                'translations' => [
                    'ar' => [
                        'title' => 'الإسراء والمعراج',
                        'description' => 'بعد عام الحزن ورحلة الطائف أكرم الله نبيه ﷺ بأعظم تكريم؛ فأسرى به ليلًا من المسجد الحرام إلى المسجد الأقصى، ثم عرج به إلى السماوات العلى، قال تعالى: ﴿سُبْحَانَ الَّذِي أَسْرَى بِعَبْدِهِ لَيْلًا مِّنَ الْمَسْجِدِ الْحَرَامِ إِلَى الْمَسْجِدِ الْأَقْصَى الَّذِي بَارَكْنَا حَوْلَهُ لِنُرِيَهُ مِنْ آيَاتِنَا﴾. أُتي ﷺ بالبراق فركبه مع جبريل حتى أتى بيت المقدس، فربطه بالحلقة التي يربط بها الأنبياء، وصلى بالأنبياء إمامًا، فكانت إشارة إلى انتقال القيادة الروحية للبشرية إلى الرسالة الخاتمة. ثم عُرج به إلى السماء الدنيا فلقي آدم، ثم لقي في السماوات يحيى وعيسى ويوسف وإدريس وهارون وموسى وإبراهيم عليهم السلام، حتى انتهى إلى سدرة المنتهى وغشيها من أمر الله ما غشيها، وفرض الله عليه وعلى أمته الصلوات خمسين، فما زال يراجع ربه بإشارة موسى حتى جعلها خمسًا في الأداء وخمسين في الأجر. ولما أصبح ﷺ فحدّث قريشًا كذّبته وارتدّ نفر ممن كان أسلم، وسعوا إلى أبي بكر فقالوا: إن صاحبك يزعم أنه أُسري به الليلة إلى بيت المقدس! قال: إن كان قال فقد صدق، إني لأصدّقه فيما هو أبعد من ذلك، فسمّي من يومها «الصدّيق». وسألته قريش وصف بيت المقدس فجلّاه الله له حتى وصفه لهم وأخبرهم عن عيرهم في الطريق، فما زادهم ذلك إلا نفورًا. وكانت هذه الرحلة تسريةً لقلبه الشريف وتثبيتًا له، وفيها فُرضت الصلاة عماد الدين.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Night Journey and Ascension (Isra and Mi\'raj)',
                        'description' => 'After the Year of Sorrow and the ordeal of Ta\'if, God honored His Prophet ﷺ with the greatest of honors: He carried him by night from the Sacred Mosque to al-Aqsa Mosque, then raised him through the heavens. "Glory be to Him who took His servant by night from the Sacred Mosque to the Farthest Mosque, whose surroundings We have blessed, to show him of Our signs" (al-Isra 1). Mounted on al-Buraq with Jibril, he reached Jerusalem, tethered the steed at the prophets\' ring, and led all the prophets in prayer — a sign that spiritual leadership of humanity had passed to the final message. He then ascended through the heavens, meeting Adam, Yahya and Isa, Yusuf, Idris, Harun, Musa, and Ibrahim, until he reached the Lote Tree of the Utmost Boundary. There God ordained fifty daily prayers, reduced — upon Musa\'s counsel and the Prophet\'s repeated pleading — to five in practice with the reward of fifty. When he told Quraysh the next morning, they mocked him, and some weak believers apostatized. But when they challenged Abu Bakr, he said: "If he said it, he has spoken the truth — I believe him in matters far beyond that," earning the title al-Siddiq. Quraysh demanded a description of Jerusalem, and God displayed it before the Prophet ﷺ until he described it exactly and told them of their own caravans on the road. The journey consoled and fortified his heart, and in it the prayer — the pillar of the religion — was ordained.',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'الإسراء على البراق إلى بيت المقدس', 'description' => 'أُتي ﷺ بالبراق فركبه مع جبريل من المسجد الحرام حتى بلغ المسجد الأقصى وربطه بحلقة الأنبياء.'],
                        'en' => ['title' => 'The night ride on al-Buraq to Jerusalem', 'description' => 'He ﷺ rode al-Buraq with Jibril from the Sacred Mosque to al-Aqsa, tethering it at the prophets\' ring.'],
                    ],
                    [
                        'ar' => ['title' => 'إمامة الأنبياء في الصلاة', 'description' => 'صلى ﷺ بالأنبياء جميعًا إمامًا في المسجد الأقصى، إيذانًا بانتقال القيادة الروحية إلى الرسالة الخاتمة.'],
                        'en' => ['title' => 'Leading the prophets in prayer', 'description' => 'He ﷺ led all the prophets in prayer at al-Aqsa, signifying the passing of spiritual leadership to the final message.'],
                    ],
                    [
                        'ar' => ['title' => 'العروج إلى السماوات السبع', 'description' => 'عُرج به ﷺ فلقي في السماوات آدم ويحيى وعيسى ويوسف وإدريس وهارون وموسى وإبراهيم عليهم السلام.'],
                        'en' => ['title' => 'Ascension through the seven heavens', 'description' => 'He ascended through the heavens, greeting Adam, Yahya, Isa, Yusuf, Idris, Harun, Musa, and Ibrahim.'],
                    ],
                    [
                        'ar' => ['title' => 'سدرة المنتهى', 'description' => 'انتهى ﷺ إلى سدرة المنتهى وقد غشيها من أمر الله ما غشيها، ورأى من آيات ربه الكبرى.'],
                        'en' => ['title' => 'The Lote Tree of the Utmost Boundary', 'description' => 'He reached the Lote Tree enveloped in divine splendor, beholding the greatest signs of his Lord.'],
                    ],
                    [
                        'ar' => ['title' => 'فرض الصلوات الخمس', 'description' => 'فرض الله الصلاة خمسين ثم خففها بمراجعة النبي ﷺ لربه إلى خمس في الأداء وخمسين في الأجر.'],
                        'en' => ['title' => 'The five daily prayers ordained', 'description' => 'God ordained fifty prayers, reduced through the Prophet\'s pleading to five in practice with fiftyfold reward.'],
                    ],
                    [
                        'ar' => ['title' => 'تصديق أبي بكر الصدّيق', 'description' => 'لما كذّبت قريش الخبر قال أبو بكر: إن كان قال فقد صدق، فسمي من يومئذ الصدّيق.'],
                        'en' => ['title' => 'Abu Bakr\'s affirmation', 'description' => 'When Quraysh scoffed, Abu Bakr declared: "If he said it, he has spoken the truth," earning the title al-Siddiq.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'أُسري به من المسجد الحرام إلى الأقصى ثم عُرج به إلى السماوات وفُرضت عليه الصلوات الخمس.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Carried by night to Jerusalem and raised through the heavens, where the five prayers were ordained.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو بكر الصدّيق', 'description' => 'صدّق خبر الإسراء بلا تردد فاستحق لقب الصدّيق.'],
                            'en' => ['name' => 'Abu Bakr al-Siddiq', 'description' => 'Affirmed the Night Journey without hesitation, earning the title "the Truthful Affirmer."'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'ard-al-islam-ala-al-qabail',
                'event_type' => 'other',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'medium',
                'hijri_date' => '3–1 ق.هـ',
                'hijri_year' => -3,
                'gregorian_year' => 620,
                'location' => 'منى وعكاظ ومجنّة حول مكة',
                'latitude' => 21.4133000,
                'longitude' => 39.8933000,
                'display_order' => 280,
                'translations' => [
                    'ar' => [
                        'title' => 'عرض الإسلام على القبائل في المواسم',
                        'description' => 'لما يئس رسول الله ﷺ من استجابة قريش وثقيف لم تلن له قناة ولم يفتر له عزم، بل أخذ يعرض نفسه ودعوته على قبائل العرب في مواسم الحج والأسواق؛ في عكاظ ومجنّة وذي المجاز ومنى، يقف على منازل القبائل فيقول: «يا بني فلان، إني رسول الله إليكم، يأمركم أن تعبدوا الله ولا تشركوا به شيئًا... وأن تمنعوني حتى أبلّغ رسالة ربي»، وعمه أبو لهب يتبعه يقول: لا تطيعوه فإنه صابئ كذاب! فعرض نفسه على كندة وكلب وبني حنيفة وبني عامر بن صعصعة وغيرها، فمنهم من ردّ ردًّا قبيحًا، ومنهم من اشترط أن يكون له الأمر من بعده — كما قال بنو عامر — فقال ﷺ: «الأمر إلى الله يضعه حيث يشاء»، فأبوا. وفي هذه المواسم لقي ﷺ في العقبة عند منى نفرًا من الخزرج من أهل يثرب، فدعاهم إلى الله وتلا عليهم القرآن، وكانوا قد سمعوا من يهود يثرب أن نبيًّا يُبعث قد أطلّ زمانه، فقال بعضهم لبعض: تعلمون والله إنه للنبي الذي توعّدكم به يهود فلا يسبقنّكم إليه! فأسلم ستة منهم ورجعوا إلى المدينة فأخبروا قومهم، فما بقيت دار من دور الأنصار إلا وفيها ذكر رسول الله ﷺ. وهكذا فتح الله من حيث لم تحتسب قريش بابَ النصرة من يثرب.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'Presenting Islam to the Tribes at the Pilgrimage Seasons',
                        'description' => 'When Quraysh and Thaqif rejected him, the Prophet\'s ﷺ resolve never wavered. He began presenting himself and his message to the Arab tribes at the pilgrimage seasons and fairs — at Ukaz, Majannah, Dhul-Majaz, and Mina — standing at the encampments and declaring: "O sons of so-and-so, I am the Messenger of God to you. He commands you to worship Him alone and associate nothing with Him... and that you protect me until I convey the message of my Lord." His uncle Abu Lahab trailed him crying: "Do not obey him — he is a renegade liar!" He approached Kindah, Kalb, Banu Hanifah, Banu Amir ibn Sa\'sa\'ah and others; some rebuffed him crudely, while Banu Amir demanded rulership after him, to which he replied: "The matter belongs to God, who places it where He wills." During these seasons, at al-Aqabah near Mina, he met a group of the Khazraj from Yathrib. He called them to God and recited the Quran. Having heard Yathrib\'s Jews speak of an imminent prophet, they said to one another: "By God, this is the prophet the Jews threatened you with — let them not reach him before you!" Six embraced Islam and carried the news home, until no household of the Ansar remained without mention of the Messenger of God ﷺ. Thus, from where Quraysh least expected, God opened the door of support from Yathrib.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'bayat-al-aqaba-al-ula',
                'event_type' => 'treaty',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => 'ذو الحجة 2 ق.هـ',
                'hijri_year' => -2,
                'hijri_month' => 12,
                'gregorian_year' => 621,
                'location' => 'العقبة عند منى',
                'latitude' => 21.4133000,
                'longitude' => 39.8933000,
                'display_order' => 290,
                'translations' => [
                    'ar' => [
                        'title' => 'بيعة العقبة الأولى',
                        'description' => 'في موسم الحج من السنة الثانية عشرة للبعثة وافى رسولَ الله ﷺ عند العقبة اثنا عشر رجلًا من أهل يثرب، عشرة من الخزرج واثنان من الأوس، فيهم خمسة من الستة الذين أسلموا في الموسم السابق، منهم أسعد بن زرارة وعبادة بن الصامت ورافع بن مالك وذكوان بن عبد قيس. فبايعوه بيعة سميت «بيعة النساء» لأنها لم تتضمن قتالًا، على ما رواه عبادة بن الصامت: بايعنا رسول الله ﷺ على ألا نشرك بالله شيئًا، ولا نسرق، ولا نزني، ولا نقتل أولادنا، ولا نأتي ببهتان نفتريه بين أيدينا وأرجلنا، ولا نعصيه في معروف؛ فمن وفى منا فأجره على الله، ومن أصاب من ذلك شيئًا فعوقب به في الدنيا فهو كفارة له، ومن ستره الله فأمره إلى الله إن شاء عذبه وإن شاء غفر له. وكانت هذه البيعة حجر الأساس في بناء المجتمع المسلم في يثرب؛ إذ قامت على التزكية الأخلاقية والعقدية قبل أي التزام سياسي أو عسكري. ولما انصرف القوم بعث رسول الله ﷺ معهم مصعب بن عمير أول سفير في الإسلام، يقرئهم القرآن ويعلمهم الدين ويصلي بهم، فنزل على أسعد بن زرارة، وأسلم على يديه خلق كثير من الأنصار، منهم سيدا الأوس سعد بن معاذ وأسيد بن حضير، فلم يبق بيت من بيوت الأنصار إلا دخله الإسلام.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The First Pledge of al-Aqabah',
                        'description' => 'At the pilgrimage season of the twelfth year of prophethood, twelve men of Yathrib — ten of Khazraj and two of Aws, among them As\'ad ibn Zurarah, Ubadah ibn al-Samit, Rafi\' ibn Malik, and Dhakwan ibn Abd Qays — met the Prophet ﷺ at al-Aqabah and gave him the pledge later called the "Pledge of Women," as it involved no fighting. Ubadah ibn al-Samit related: "We pledged to the Messenger of God ﷺ that we would associate nothing with God, would not steal, commit adultery, kill our children, bring slander we fabricate, nor disobey him in what is right. Whoever fulfills it, his reward is with God; whoever commits any of these and is punished in this world, it is his expiation; and whoever God conceals, his matter rests with God — He may punish or forgive him." This pledge was the cornerstone of the Muslim community in Yathrib, founded on moral and doctrinal purification before any political or military commitment. The Prophet ﷺ sent with them Mus\'ab ibn Umayr — Islam\'s first ambassador — to teach them the Quran and lead their prayers. Hosted by As\'ad ibn Zurarah, Mus\'ab brought multitudes of the Ansar to Islam, including the Aws chieftains Sa\'d ibn Mu\'adh and Usayd ibn Hudayr, until Islam entered every household of the Ansar.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أسعد بن زرارة', 'description' => 'من أوائل أنصار يثرب إسلامًا ونقيب بني النجار، استضاف مصعب بن عمير في داره.'],
                            'en' => ['name' => 'As\'ad ibn Zurarah', 'description' => 'Among the first of Yathrib\'s Ansar to embrace Islam; he hosted Mus\'ab ibn Umayr in his home.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'مصعب بن عمير', 'description' => 'أول سفير في الإسلام، بعثه النبي ﷺ إلى يثرب معلمًا ومقرئًا فأسلم على يديه كبار الأنصار.'],
                            'en' => ['name' => 'Mus\'ab ibn Umayr', 'description' => 'Islam\'s first ambassador, sent to Yathrib as teacher and reciter; leading Ansar figures embraced Islam at his hands.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'bayat-al-aqaba-al-thaniya',
                'event_type' => 'treaty',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => 'ذو الحجة 1 ق.هـ',
                'hijri_year' => -1,
                'hijri_month' => 12,
                'gregorian_year' => 622,
                'location' => 'العقبة عند منى',
                'latitude' => 21.4133000,
                'longitude' => 39.8933000,
                'display_order' => 300,
                'translations' => [
                    'ar' => [
                        'title' => 'بيعة العقبة الثانية (بيعة الحرب)',
                        'description' => 'في موسم الحج من السنة الثالثة عشرة للبعثة قدم مكةَ بضعة وسبعون من أنصار يثرب — ثلاثة وسبعون رجلًا وامرأتان: نسيبة بنت كعب أم عمارة وأسماء بنت عمرو — في حجاج قومهم من المشركين، وتواعدوا مع رسول الله ﷺ أن يلقوه عند العقبة من أوسط أيام التشريق سرًّا في ثلث الليل، يتسللون مستخفين تسلل القطا. فجاءهم رسول الله ﷺ ومعه عمه العباس بن عبد المطلب — وهو يومئذ على دين قومه — فتكلم العباس مؤكدًا عليهم خطر ما يقدمون عليه، فقالوا: قد سمعنا ما قلت، فتكلم يا رسول الله فخذ لنفسك ولربك ما أحببت. فتلا ﷺ القرآن ودعا إلى الله ثم قال: «أبايعكم على أن تمنعوني مما تمنعون منه نساءكم وأبناءكم». فأخذ البراء بن معرور بيده وقال: نعم والذي بعثك بالحق نبيًّا لنمنعنّك مما نمنع منه أُزرنا، فبايعنا يا رسول الله، فنحن والله أبناء الحروب وأهل الحلقة. وقال أبو الهيثم بن التيهان: يا رسول الله، إن بيننا وبين الرجال — يعني اليهود — حبالًا وإنا قاطعوها، فهل عسيت إن نحن فعلنا ذلك ثم أظهرك الله أن ترجع إلى قومك وتدعنا؟ فتبسم ﷺ وقال: «بل الدمُ الدمُ والهدمُ الهدمُ، أنا منكم وأنتم مني، أحارب من حاربتم وأسالم من سالمتم». ثم اختار منهم اثني عشر نقيبًا. وكانت هذه البيعة إعلان ميلاد الدولة الإسلامية ومقدمة الهجرة المباشرة؛ إذ أذن ﷺ بعدها للمسلمين بالهجرة إلى يثرب.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Second Pledge of al-Aqabah (Pledge of War)',
                        'description' => 'At the pilgrimage season of the thirteenth year of prophethood, over seventy of Yathrib\'s believers — seventy-three men and two women, Nusaybah bint Ka\'b and Asma bint Amr — came to Mecca among their polytheist tribesmen and arranged to meet the Prophet ﷺ secretly at al-Aqabah in the middle of the night. He came with his uncle al-Abbas — then still on his people\'s religion — who stressed the gravity of their undertaking. They replied: "Speak, O Messenger of God, and take for yourself and your Lord what you wish." After reciting the Quran he said: "I take your pledge that you will protect me as you protect your women and children." Al-Bara ibn Ma\'rur seized his hand: "Yes, by Him who sent you with the truth, we shall protect you as we protect our own garments. Take our pledge — we are people of war, sons of arms inherited generation after generation." Abu al-Haytham ibn al-Tayyihan asked: "If we sever our ties with the Jews and God grants you victory, might you return to your people and abandon us?" The Prophet ﷺ smiled and said: "Rather, blood is blood and destruction is destruction. I am of you and you are of me. I war against whom you war, and make peace with whom you make peace." He then chose twelve deputies (naqibs) from among them. This pledge announced the birth of the Islamic polity and directly paved the way for the Hijra, after which he permitted the Muslims to emigrate to Yathrib.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'البراء بن معرور', 'description' => 'سيد بني سلمة وأول من بايع ليلة العقبة الثانية وأول من تكلم فيها.'],
                            'en' => ['name' => 'Al-Bara ibn Ma\'rur', 'description' => 'Chief of Banu Salamah, the first to speak and pledge on the night of the Second Aqabah.'],
                        ],
                    ],
                    [
                        'role' => 'other',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'العباس بن عبد المطلب', 'description' => 'عم النبي ﷺ، حضر البيعة موثقًا لابن أخيه قبل إسلامه.'],
                            'en' => ['name' => 'Al-Abbas ibn Abdul-Muttalib', 'description' => 'The Prophet\'s uncle, who attended the pledge to secure guarantees for his nephew before his own conversion.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'نسيبة بنت كعب (أم عمارة)', 'description' => 'إحدى المرأتين المبايعتين ليلة العقبة الثانية، والمجاهدة التي دافعت عن النبي ﷺ يوم أحد.'],
                            'en' => ['name' => 'Nusaybah bint Ka\'b (Umm Umarah)', 'description' => 'One of the two women at the Second Pledge; she later defended the Prophet ﷺ in person at Uhud.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'al-hijra-ila-al-madina',
                'event_type' => 'migration',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => 'ربيع الأول 1 هـ',
                'hijri_year' => 1,
                'hijri_month' => 3,
                'gregorian_date' => '622-09-24',
                'gregorian_year' => 622,
                'location' => 'من مكة المكرمة إلى المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 310,
                'translations' => [
                    'ar' => [
                        'title' => 'الهجرة النبوية إلى المدينة',
                        'description' => 'لما اشتد البلاء بمكة وتمت بيعة العقبة الثانية أذن رسول الله ﷺ لأصحابه بالهجرة إلى يثرب، فخرجوا أرسالًا يتسللون، حتى لم يبق معه إلا أبو بكر وعلي ومن حُبس أو فُتن. فلما رأت قريش ذلك اجتمع ملؤهم في دار الندوة، فأجمعوا على أن يأخذوا من كل قبيلة فتى شابًّا جلدًا فيضربوه ضربة رجل واحد فيتفرق دمه في القبائل. فأتى جبريل رسول الله ﷺ بالخبر وأمره ألا يبيت في مضجعه، فأمر عليًّا أن ينام على فراشه ويردّ الودائع إلى أهلها، وخرج ﷺ على القوم المتربصين فحثا التراب على رؤوسهم وهو يتلو: ﴿وَجَعَلْنَا مِن بَيْنِ أَيْدِيهِمْ سَدًّا﴾ فلم يروه. ثم انطلق إلى دار أبي بكر — وقد قال له: «الصحبة يا أبا بكر» فبكى أبو بكر فرحًا — فخرجا من خوخة في ظهر بيته وكَمَنا في غار ثور ثلاث ليال، وعامر بن فهيرة يريح عليهما الغنم، وعبد الله بن أبي بكر يأتيهما بأخبار قريش، وأسماء تأتيهما بالطعام. وقد بلغت قريش بطلبهما حتى وقفوا على باب الغار، فقال أبو بكر: لو نظر أحدهم تحت قدميه لأبصرنا! فقال ﷺ: «يا أبا بكر، ما ظنك باثنين الله ثالثهما؟ لا تحزن إن الله معنا». ثم سارا مع الدليل عبد الله بن أريقط على طريق الساحل، وتبعهما سراقة بن مالك طمعًا في جائزة قريش فساخت قوائم فرسه، فطلب الأمان ورجع يعمّي الأخبار، ووعده النبي ﷺ بسواري كسرى. ووصل ﷺ إلى قباء في ربيع الأول فأقام بها أيامًا وأسس مسجدها، ثم دخل المدينة فاستقبله الأنصار بالفرح والترحاب، وبركت ناقته بموضع مسجده، ونزل على أبي أيوب الأنصاري. وكانت الهجرة الفاصل بين عهدين، وبها بدأ التاريخ الإسلامي.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Hijra to Medina',
                        'description' => 'After the Second Pledge of al-Aqabah, the Prophet ﷺ permitted his companions to emigrate to Yathrib, and they slipped away in groups until only he, Abu Bakr, Ali, and the detained remained. Alarmed, the chiefs of Quraysh gathered at Dar al-Nadwah and resolved that a strong youth from every clan should strike him simultaneously, scattering the blood-guilt among all the tribes. Jibril brought him the news, so he had Ali sleep in his bed to return the deposits entrusted to him, then walked out past the lurking assassins, casting dust upon their heads while reciting "And We placed before them a barrier" — and they did not see him. He went to Abu Bakr, who wept for joy at being chosen as his companion. Leaving through a back window, they hid three nights in the cave of Thawr, with Amir ibn Fuhayrah grazing sheep over their tracks, Abdullah ibn Abi Bakr bringing intelligence, and Asma bringing food. When the search party stood at the very mouth of the cave, Abu Bakr whispered: "If one of them looked beneath his feet, he would see us!" The Prophet ﷺ replied: "O Abu Bakr, what do you think of two whose third is God? Grieve not; indeed God is with us." Guided by Abdullah ibn Urayqit along the coastal route, they were pursued by Suraqah ibn Malik seeking Quraysh\'s bounty — until his horse\'s legs sank into the ground; he begged for amnesty, turned back the pursuers, and was promised the bracelets of Khosrau. The Prophet ﷺ reached Quba in Rabi\' al-Awwal, founded its mosque, then entered Medina amid jubilant celebration. His camel knelt at the site of his future mosque, and he lodged with Abu Ayyub al-Ansari. The Hijra divided two eras — and with it the Islamic calendar begins.',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'مؤامرة دار الندوة', 'description' => 'أجمعت قريش على قتل النبي ﷺ بأن يضربه فتيان من كل القبائل ضربة رجل واحد فيتفرق دمه فيها.'],
                        'en' => ['title' => 'The plot at Dar al-Nadwah', 'description' => 'Quraysh resolved that youths from every clan would strike the Prophet ﷺ together, scattering the blood-guilt among all tribes.'],
                    ],
                    [
                        'ar' => ['title' => 'مبيت علي في فراش النبي ﷺ', 'description' => 'نام علي بن أبي طالب في فراشه ﷺ مخاطرًا بنفسه، وخرج النبي على المتربصين فلم يروه.'],
                        'en' => ['title' => 'Ali sleeps in the Prophet\'s bed', 'description' => 'Ali ibn Abi Talib risked his life sleeping in the Prophet\'s bed while he ﷺ walked unseen past the assassins.'],
                    ],
                    [
                        'ar' => ['title' => 'ثلاث ليال في غار ثور', 'description' => 'كَمَن ﷺ وأبو بكر في غار ثور، وقال له حين وقف الطلب على باب الغار: لا تحزن إن الله معنا.'],
                        'en' => ['title' => 'Three nights in the cave of Thawr', 'description' => 'Hidden in the cave with the search party at its mouth, he ﷺ reassured Abu Bakr: "Grieve not; indeed God is with us."'],
                    ],
                    [
                        'ar' => ['title' => 'قصة سراقة بن مالك', 'description' => 'تبعهما سراقة طمعًا في الجائزة فساخت فرسه، فطلب الأمان ووعده النبي ﷺ بسواري كسرى.'],
                        'en' => ['title' => 'The pursuit of Suraqah', 'description' => 'Suraqah chased them for the bounty until his horse sank; granted amnesty, he was promised the bracelets of Khosrau.'],
                    ],
                    [
                        'ar' => ['title' => 'النزول بقباء وتأسيس مسجدها', 'description' => 'أقام ﷺ بقباء أيامًا وأسس أول مسجد بُني في الإسلام على التقوى من أول يوم.'],
                        'en' => ['title' => 'Arrival at Quba and its mosque', 'description' => 'He stayed several days at Quba and founded the first mosque built in Islam, "founded on piety from the first day."'],
                    ],
                    [
                        'ar' => ['title' => 'دخول المدينة واستقبال الأنصار', 'description' => 'دخل ﷺ المدينة وسط فرحة الأنصار، وبركت ناقته بموضع المسجد النبوي ونزل على أبي أيوب الأنصاري.'],
                        'en' => ['title' => 'Triumphal entry into Medina', 'description' => 'He entered Medina amid the Ansar\'s jubilation; his camel knelt at the future mosque\'s site, and he lodged with Abu Ayyub.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'هاجر من مكة إلى المدينة بأمر ربه، فكانت هجرته فاتحة عهد الدولة الإسلامية.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Emigrated from Mecca to Medina by his Lord\'s command, inaugurating the era of the Islamic polity.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو بكر الصدّيق', 'description' => 'صاحب النبي ﷺ في الهجرة وثاني اثنين إذ هما في الغار.'],
                            'en' => ['name' => 'Abu Bakr al-Siddiq', 'description' => 'The Prophet\'s companion on the Hijra — "the second of the two when they were in the cave."'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'علي بن أبي طالب', 'description' => 'بات في فراش النبي ﷺ ليلة الهجرة فاديًا له بنفسه، وردّ الودائع إلى أهلها ثم لحق به.'],
                            'en' => ['name' => 'Ali ibn Abi Talib', 'description' => 'Slept in the Prophet\'s bed on the night of the Hijra, returned the entrusted deposits, then followed him to Medina.'],
                        ],
                    ],
                    [
                        'role' => 'other',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'سراقة بن مالك', 'description' => 'تبع النبي ﷺ طمعًا في جائزة قريش فساخت فرسه، فرجع يعمّي الأخبار وأسلم بعد ذلك.'],
                            'en' => ['name' => 'Suraqah ibn Malik', 'description' => 'Pursued the Prophet ﷺ for Quraysh\'s bounty until his horse sank; he turned back the pursuers and later embraced Islam.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'bina-masjid-quba',
                'event_type' => 'other',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => 'ربيع الأول 1 هـ',
                'hijri_year' => 1,
                'hijri_month' => 3,
                'gregorian_year' => 622,
                'location' => 'قباء جنوب المدينة المنورة',
                'latitude' => 24.4393000,
                'longitude' => 39.6173000,
                'display_order' => 320,
                'translations' => [
                    'ar' => [
                        'title' => 'بناء مسجد قباء أول مسجد في الإسلام',
                        'description' => 'لما وصل رسول الله ﷺ مهاجرًا إلى قباء في ظاهر المدينة نزل على كلثوم بن الهِدم في بني عمرو بن عوف، وأقام فيهم أيامًا — قيل أربع عشرة ليلة — أسس خلالها مسجد قباء، فكان أول مسجد بُني في الإسلام بعد البعثة، وأول عمل قام به ﷺ في دار هجرته قبل أن يدخل المدينة نفسها، وفي ذلك دلالة بالغة على مركزية المسجد في بناء المجتمع المسلم. وقد شارك ﷺ بنفسه الشريفة في بنائه، ينقل الحجارة مع أصحابه حتى قال بعض الصحابة يصفون ذلك: إنه كان ينقل الحجر وهو يتعرّض للغبار والتعب كأحدهم، مما ضرب أروع الأمثلة في التواضع والمشاركة في العمل. وفي هذا المسجد نزل قول الله تعالى: ﴿لَمَسْجِدٌ أُسِّسَ عَلَى التَّقْوَى مِنْ أَوَّلِ يَوْمٍ أَحَقُّ أَن تَقُومَ فِيهِ رِجَالٌ يُحِبُّونَ أَن يَتَطَهَّرُوا وَاللَّهُ يُحِبُّ الْمُطَّهِّرِينَ﴾. وكان ﷺ بعد استقراره في المدينة يأتي قباء كل سبت ماشيًا وراكبًا فيصلي فيه ركعتين، وقال: «من تطهّر في بيته ثم أتى مسجد قباء فصلى فيه صلاة كان له كأجر عمرة». فبقي مسجد قباء عبر التاريخ شاهدًا على فجر الحضارة الإسلامية، ورمزًا لأول لبنة وضعها النبي ﷺ في صرح دولته.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'Building the Quba Mosque, the First in Islam',
                        'description' => 'Arriving at Quba on the outskirts of Medina, the Prophet ﷺ lodged with Kulthum ibn al-Hidm among Banu Amr ibn Awf and stayed some days — fourteen nights by one account — during which he founded the Quba Mosque, the first mosque built in Islam and his very first undertaking in the land of his emigration, even before entering Medina itself: a profound sign of the mosque\'s centrality in building the Muslim community. He ﷺ took part in the construction with his own hands, carrying stones alongside his companions, covered in dust and fatigue like any one of them — a matchless example of humility and shared labor. Concerning this mosque God revealed: "A mosque founded on piety from the first day is more worthy that you stand therein; in it are men who love to purify themselves, and God loves those who purify themselves" (al-Tawbah 108). After settling in Medina, the Prophet ﷺ would visit Quba every Saturday, walking or riding, to pray two units there, and he said: "Whoever purifies himself in his house then comes to the Quba Mosque and prays in it has the reward of an Umrah." The mosque endures through history as a witness to the dawn of Islamic civilization — the first brick the Prophet ﷺ laid in the edifice of his state.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'bina-al-masjid-al-nabawi',
                'event_type' => 'other',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'high',
                'hijri_date' => 'ربيع الأول 1 هـ',
                'hijri_year' => 1,
                'hijri_month' => 3,
                'gregorian_year' => 622,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 330,
                'translations' => [
                    'ar' => [
                        'title' => 'بناء المسجد النبوي الشريف',
                        'description' => 'لما دخل رسول الله ﷺ المدينة بركت ناقته القصواء في مِربد لغلامين يتيمين من بني النجار هما سهل وسهيل في حجر أسعد بن زرارة، فقال ﷺ: «هذا إن شاء الله المنزل»، ثم ساوم الغلامين في المربد ليتخذه مسجدًا فقالا: بل نهبه لك يا رسول الله، فأبى ﷺ إلا أن يبتاعه منهما بثمنه فاشتراه بعشرة دنانير أداها عنه أبو بكر. وكان في المربد نخل وقبور للمشركين وخرِب، فأمر ﷺ بالقبور فنُبشت وبالنخل فقُطع وبالخرب فسوّيت، وبنوا المسجد من اللبن وجعلوا عضادتيه من الحجارة وسقفه من الجريد وعمده من جذوع النخل، وجعل قبلته إلى بيت المقدس قبل تحويل القبلة. وعمل فيه رسول الله ﷺ بيده ينقل اللبن والحجارة مع المسلمين وهو يقول: «اللهم لا عيش إلا عيش الآخرة، فاغفر للأنصار والمهاجرة»، فازداد المسلمون نشاطًا وجدًّا. وبنى إلى جانبه حُجرات أزواجه. ولم يكن المسجد النبوي مجرد مصلًّى، بل كان جامعة الإسلام الأولى ودار الحكومة ومقر الشورى ومدرسة العلم وملجأ الفقراء؛ ففيه كانت تعقد الألوية وتستقبل الوفود ويتدارس الصحابة القرآن، وفي صُفّته سكن فقراء المهاجرين «أهل الصُّفّة». وجعل ﷺ الصلاة فيه بألف صلاة فيما سواه إلا المسجد الحرام، فصار ثاني أعظم مساجد الأرض حرمةً وقدرًا.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'Building the Prophet\'s Mosque',
                        'description' => 'When the Prophet ﷺ entered Medina, his she-camel al-Qaswa knelt at a drying-yard belonging to two orphan boys of Banu al-Najjar, Sahl and Suhayl, wards of As\'ad ibn Zurarah. "This, God willing, is the dwelling," he ﷺ said. The boys offered the land as a gift, but he insisted on paying its price — ten dinars, paid by Abu Bakr. The yard contained palm trees, old graves, and ruins; the graves were dug up, the palms cut, and the ground leveled. The mosque was built of mud-brick with stone foundations, a roof of palm fronds, and pillars of palm trunks, its qiblah first facing Jerusalem. The Prophet ﷺ labored with his own hands, carrying bricks and stones while reciting: "O God, there is no life but the life of the Hereafter, so forgive the Ansar and the Muhajirah" — spurring the Muslims to even greater effort. Beside it he built the apartments of his wives. The mosque was far more than a prayer hall: it was Islam\'s first university, the seat of government and consultation, a school of knowledge, and a shelter for the poor — banners were tied there, delegations received, the Quran studied, and the destitute "People of the Bench" housed in its shaded portico. He ﷺ declared prayer within it worth a thousand prayers elsewhere, except in the Sacred Mosque of Mecca.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'al-muakhah',
                'event_type' => 'other',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'high',
                'hijri_date' => '1 هـ',
                'hijri_year' => 1,
                'gregorian_year' => 623,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 340,
                'translations' => [
                    'ar' => [
                        'title' => 'المؤاخاة بين المهاجرين والأنصار',
                        'description' => 'من أوائل ما عُني به رسول الله ﷺ بعد استقراره في المدينة معالجةُ أوضاع المهاجرين الذين تركوا ديارهم وأموالهم بمكة فرارًا بدينهم، فآخى بين المهاجرين والأنصار في دار أنس بن مالك، وكانوا تسعين رجلًا — نصفهم من المهاجرين ونصفهم من الأنصار — على المواساة والتناصر، حتى كانوا في أول الأمر يتوارثون بهذه المؤاخاة دون ذوي الأرحام، إلى أن نزل قوله تعالى: ﴿وَأُولُو الْأَرْحَامِ بَعْضُهُمْ أَوْلَى بِبَعْضٍ﴾ فرُدّ التوارث إلى القرابة وبقيت الأخوة والمناصرة. فآخى بين أبي بكر وخارجة بن زيد، وبين عمر وعتبان بن مالك، وبين عبد الرحمن بن عوف وسعد بن الربيع، وبين سلمان الفارسي وأبي الدرداء، وغيرهم. وقد ضرب الأنصار في هذه المؤاخاة أروع أمثلة الإيثار التي سجّلها القرآن: ﴿وَيُؤْثِرُونَ عَلَى أَنفُسِهِمْ وَلَوْ كَانَ بِهِمْ خَصَاصَةٌ﴾؛ فهذا سعد بن الربيع يعرض على أخيه عبد الرحمن بن عوف شطر ماله، فيدعو له عبد الرحمن ويقول كلمته المشهورة: بارك الله لك في أهلك ومالك، دلوني على السوق! فما لبث أن أثرى من تجارته. وعرض الأنصار على النبي ﷺ أن يقاسموا إخوانهم نخيلهم فأبى، فقالوا: تكفوننا المؤونة ونشرككم في الثمرة، قالوا: سمعنا وأطعنا. وبهذه المؤاخاة الفريدة في التاريخ صهر النبي ﷺ المجتمع الجديد في أمة واحدة تعلو روابطُ عقيدتها على روابط الدم والقبيلة.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Brotherhood between the Muhajirun and the Ansar',
                        'description' => 'Among the Prophet\'s ﷺ first concerns after settling in Medina was the plight of the emigrants who had abandoned homes and wealth in Mecca for their faith. In the house of Anas ibn Malik he established a formal brotherhood between the Muhajirun and the Ansar — ninety men, half from each — binding them to mutual support and consolation. So strong was this bond that at first they even inherited from one another in place of blood relatives, until the verse "But kindred by blood are nearer to one another" restored inheritance to kin while the brotherhood of faith remained. He paired Abu Bakr with Kharijah ibn Zayd, Umar with Itban ibn Malik, Abd al-Rahman ibn Awf with Sa\'d ibn al-Rabi\', Salman al-Farisi with Abu al-Darda, and others. The Ansar displayed selflessness immortalized in the Quran: "They give preference over themselves, even though they are in need" (al-Hashr 9). Sa\'d ibn al-Rabi\' offered his brother half of all his wealth; Abd al-Rahman blessed him and famously replied: "May God bless your family and wealth — show me the marketplace!" and soon prospered through trade. When the Ansar offered to divide their orchards, the Prophet ﷺ declined; instead they agreed: "Spare us the labor and share with us the fruit." Through this brotherhood, unique in history, he ﷺ fused the new society into one community whose bonds of faith rose above blood and tribe.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'wathiqat-al-madina',
                'event_type' => 'treaty',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'high',
                'hijri_date' => '1 هـ',
                'hijri_year' => 1,
                'gregorian_year' => 623,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 350,
                'translations' => [
                    'ar' => [
                        'title' => 'وثيقة المدينة (الصحيفة)',
                        'description' => 'بعد أن وحّد رسول الله ﷺ الصف الداخلي بالمؤاخاة، نظّم علاقات المجتمع المدني الجديد كله بوثيقة مكتوبة عرفت بـ«صحيفة المدينة» أو «وثيقة المدينة»، وهي تعد أول دستور مكتوب في التاريخ ينظم العلاقات بين مكونات مجتمع متعدد الأديان والأعراق. كتبها ﷺ بين المهاجرين والأنصار، ووادع فيها يهود المدينة — بني قينقاع وبني النضير وبني قريظة ومن لف لفهم — وعاهدهم وأقرهم على دينهم وأموالهم. ومن أبرز بنودها: أن المسلمين «أمة واحدة من دون الناس»، وأن المؤمنين لا يتركون مُفرَحًا — أي مثقلًا بالدَّين — بينهم، وأن ذمة الله واحدة يجير عليهم أدناهم، وأن من تبعهم من يهود فإن له النصر والأسوة غير مظلومين ولا متناصَر عليهم، وأن لليهود دينهم وللمسلمين دينهم، وأن على كل طائفة نفقتها، وأن بينهم النصر على من حارب أهل هذه الصحيفة، وأن يثرب حرام جوفها لأهل هذه الصحيفة، وأنهم ما اختلفوا في شيء فإن مردّه إلى الله وإلى محمد ﷺ. فأرست الوثيقة مبادئ المواطنة والتعايش وحرية التدين والتكافل والدفاع المشترك والتحاكم إلى مرجعية واحدة، وأقامت لأول مرة سلطة عليا في يثرب التي كانت قبلُ ممزقة بحروب الأوس والخزرج، فاكتملت بذلك أركان الدولة الوليدة: الأرض والشعب والقيادة والقانون.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Constitution of Medina',
                        'description' => 'Having unified the believers through brotherhood, the Prophet ﷺ organized the entire new civic order in a written document known as the Charter (Sahifah) of Medina — widely regarded as history\'s first written constitution governing a multi-religious, multi-ethnic society. Drawn up between the Muhajirun and the Ansar, it also covenanted with the Jews of Medina — Banu Qaynuqa, Banu al-Nadir, Banu Qurayzah and their allies — confirming them in their religion and property. Its landmark clauses declared: the Muslims are "one community to the exclusion of others"; believers shall not leave anyone burdened with debt without aid; the protection of God is one, extended even by the humblest of them; Jews who follow them have help and parity, neither wronged nor abandoned; "the Jews have their religion and the Muslims have theirs"; each party bears its own expenses; all must defend one another against whoever attacks the people of the charter; the valley of Yathrib is inviolable for them; and every dispute shall be referred to God and to Muhammad ﷺ. The document established principles of common citizenship, religious freedom, mutual solidarity, joint defense, and a single arbitral authority — creating, for the first time, a supreme authority in a city long torn by the wars of Aws and Khazraj, and completing the pillars of the nascent state: land, people, leadership, and law.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'mashruiyyat-al-adhan',
                'event_type' => 'other',
                'era' => 'medina',
                'phase' => 'hijra',
                'importance' => 'medium',
                'hijri_date' => '1 هـ',
                'hijri_year' => 1,
                'gregorian_year' => 623,
                'location' => 'المدينة المنورة',
                'latitude' => 24.4672000,
                'longitude' => 39.6111000,
                'display_order' => 360,
                'translations' => [
                    'ar' => [
                        'title' => 'مشروعية الأذان',
                        'description' => 'لما استقر المسلمون بالمدينة وبُني المسجد النبوي كانوا يجتمعون للصلاة فيتحيّنون أوقاتها بغير دعوة، فاهتم النبي ﷺ بأمر جمعهم لها، فتشاور مع أصحابه؛ فقال بعضهم: نتخذ ناقوسًا كناقوس النصارى، وقال بعضهم: بل بوقًا كقرن اليهود، وذُكرت النار فكرهها ﷺ لما فيها من مشابهة المجوس. فبينما هم على ذلك رأى عبد الله بن زيد بن عبد ربه الأنصاري في منامه رجلًا يحمل ناقوسًا فقال له: يا عبد الله أتبيع الناقوس؟ قال: وما تصنع به؟ قال: ندعو به إلى الصلاة. قال: أفلا أدلك على ما هو خير من ذلك؟ تقول: الله أكبر الله أكبر... حتى أتم ألفاظ الأذان، ثم علّمه ألفاظ الإقامة. فلما أصبح أتى رسول الله ﷺ فأخبره، فقال ﷺ: «إنها لرؤيا حق إن شاء الله، فقم مع بلال فألقِ عليه ما رأيت فليؤذن به فإنه أندى صوتًا منك». فلما أذّن بلال سمعه عمر بن الخطاب وهو في بيته فخرج يجر رداءه يقول: والذي بعثك بالحق يا رسول الله لقد رأيت مثل ما رأى! فقال ﷺ: «فلله الحمد». وهكذا شُرع الأذان شعارَ الإسلام الخالد الذي يصدح بالتوحيد من فوق المآذن خمس مرات في اليوم، كلماتٍ جامعةً لأصول العقيدة: تكبيرًا لله وشهادةً بالتوحيد والرسالة ودعوةً إلى الصلاح والفلاح، وصار بلال بن رباح أول مؤذن في الإسلام.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Institution of the Adhan',
                        'description' => 'Once the Muslims settled in Medina and the mosque was built, they would gather for prayer by estimating its times without any summons. The Prophet ﷺ consulted his companions on how to assemble them: some proposed a bell like the Christians, others a horn like the Jews; fire was mentioned, but he disliked its association with the Magians. Then Abdullah ibn Zayd al-Ansari saw in a dream a man carrying a bell and asked to buy it. "What will you do with it?" the man asked. "We will call to prayer with it." The man replied: "Shall I not guide you to something better? Say: God is Most Great, God is Most Great..." — teaching him the complete words of the adhan and then of the iqamah. In the morning he told the Prophet ﷺ, who said: "It is a true vision, God willing. Stand with Bilal and convey to him what you saw, for he has the more resonant voice." When Bilal\'s call rang out, Umar ibn al-Khattab came hurrying from his house, dragging his cloak and swearing he had seen the very same dream — and the Prophet ﷺ said: "To God belongs all praise." Thus was instituted the adhan, Islam\'s eternal emblem proclaiming God\'s oneness from the minarets five times daily — words gathering the essentials of faith — and Bilal ibn Rabah became the first muezzin in Islam.',
                        'content' => null,
                    ],
                ],
            ],
        ];
    }
}
