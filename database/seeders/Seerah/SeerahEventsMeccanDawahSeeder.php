<?php

declare(strict_types=1);

namespace Database\Seeders\Seerah;

/**
 * Batch 2 of 5 — The Meccan da'wah: from the first revelation to the
 * annulment of the boycott (610–619 CE).
 */
class SeerahEventsMeccanDawahSeeder extends SeerahEventSeederBase
{
    protected function events(): array
    {
        return [
            [
                'slug' => 'nuzul-al-wahy',
                'event_type' => 'revelation',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => 'رمضان 13 ق.هـ',
                'hijri_year' => -13,
                'hijri_month' => 9,
                'gregorian_year' => 610,
                'location' => 'غار حراء بمكة المكرمة',
                'latitude' => 21.4577000,
                'longitude' => 39.8593000,
                'display_order' => 130,
                'translations' => [
                    'ar' => [
                        'title' => 'نزول الوحي وبدء الرسالة',
                        'description' => 'لما قارب رسول الله ﷺ الأربعين من عمره حُبِّب إليه الخلاء، فكان يخلو بغار حراء في جبل النور يتحنّث فيه الليالي ذوات العدد، يتأمل في ملكوت السماوات والأرض، متجافيًا عما كان عليه قومه من عبادة الأوثان. وفي ليلة مباركة من رمضان في السنة الأربعين من مولده، الموافقة لسنة 610 للميلاد، جاءه جبريل عليه السلام وهو في الغار فقال له: اقرأ. قال ﷺ: «ما أنا بقارئ»، فأخذه فغطّه حتى بلغ منه الجهد ثم أرسله فقال: اقرأ، قال: «ما أنا بقارئ»، حتى كانت الثالثة فقال: ﴿اقْرَأْ بِاسْمِ رَبِّكَ الَّذِي خَلَقَ * خَلَقَ الْإِنسَانَ مِنْ عَلَقٍ * اقْرَأْ وَرَبُّكَ الْأَكْرَمُ * الَّذِي عَلَّمَ بِالْقَلَمِ * عَلَّمَ الْإِنسَانَ مَا لَمْ يَعْلَمْ﴾. فرجع بها رسول الله ﷺ يرجف فؤاده، فدخل على خديجة بنت خويلد رضي الله عنها فقال: «زمّلوني زمّلوني»، فزمّلوه حتى ذهب عنه الروع، فأخبرها الخبر وقال: «لقد خشيت على نفسي»، فقالت له كلمتها الخالدة: كلا والله ما يخزيك الله أبدًا؛ إنك لتصل الرحم، وتحمل الكَلّ، وتَكسب المعدوم، وتَقري الضيف، وتعين على نوائب الحق. ثم انطلقت به إلى ابن عمها ورقة بن نوفل، وكان شيخًا قد تنصّر وقرأ الكتب، فقال: هذا الناموس الذي نزّل الله على موسى، يا ليتني فيها جذعًا إذ يخرجك قومك. وبهذه الليلة بدأ عهد النبوة وانبلج فجر الرسالة الخاتمة.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The First Revelation',
                        'description' => 'As he approached forty, the Prophet ﷺ grew to love seclusion, retreating to the cave of Hira on the Mountain of Light to contemplate for nights on end. On a blessed night of Ramadan in 610 CE, the angel Jibril came to him and commanded: "Read!" He replied, "I am not one who reads." The angel pressed him tightly three times, then recited the first verses of Surah al-Alaq: "Read in the name of your Lord who created..." The Prophet ﷺ returned home trembling and said to Khadijah, "Cover me, cover me!" When he told her what happened, she reassured him with her immortal words: "Never! By God, God will never disgrace you. You keep ties of kinship, bear people\'s burdens, give to the destitute, host the guest, and assist in every just cause." She took him to her cousin Waraqah ibn Nawfal, a learned Christian, who declared: "This is the same Angel whom God sent to Moses. Would that I were young when your people drive you out!" That night the age of prophethood began and the final message dawned upon humanity.',
                        'content' => null,
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'التحنث في غار حراء', 'description' => 'كان ﷺ يخلو بغار حراء الليالي ذوات العدد يتعبد ويتأمل، ويتزود لذلك ثم يرجع إلى خديجة فيتزود لمثلها.'],
                        'en' => ['title' => 'Retreats in the cave of Hira', 'description' => 'He ﷺ would seclude himself in the cave of Hira for nights of worship and contemplation, returning to Khadijah only to resupply.'],
                    ],
                    [
                        'ar' => ['title' => 'نزول جبريل بـ«اقرأ»', 'description' => 'جاءه جبريل عليه السلام فغطّه ثلاثًا وهو يقول: اقرأ، ثم تلا عليه أول ما نزل من القرآن: ﴿اقْرَأْ بِاسْمِ رَبِّكَ الَّذِي خَلَقَ﴾.'],
                        'en' => ['title' => 'Jibril descends with "Read!"', 'description' => 'The angel Jibril embraced him tightly three times, then recited the first revealed verses: "Read in the name of your Lord who created."'],
                    ],
                    [
                        'ar' => ['title' => 'العودة إلى خديجة: «زمّلوني»', 'description' => 'رجع ﷺ يرجف فؤاده فقال: زمّلوني زمّلوني، فثبّتته خديجة بقولها: كلا والله ما يخزيك الله أبدًا.'],
                        'en' => ['title' => 'Return to Khadijah: "Cover me!"', 'description' => 'He returned with a trembling heart saying, "Cover me, cover me!" Khadijah comforted him: "By God, God will never disgrace you."'],
                    ],
                    [
                        'ar' => ['title' => 'لقاء ورقة بن نوفل', 'description' => 'انطلقت به خديجة إلى ورقة بن نوفل فقال: هذا الناموس الذي نزّل الله على موسى، وبشّره بأنه نبي هذه الأمة.'],
                        'en' => ['title' => 'Meeting Waraqah ibn Nawfal', 'description' => 'Khadijah took him to Waraqah, who recognized the Angel of revelation sent to Moses and foretold his prophethood and his people\'s enmity.'],
                    ],
                    [
                        'ar' => ['title' => 'بدء عهد النبوة', 'description' => 'بنزول صدر سورة العلق بدأت الرسالة الخاتمة، وتهيأ رسول الله ﷺ لحمل أعظم أمانة عرفها التاريخ.'],
                        'en' => ['title' => 'The age of prophethood begins', 'description' => 'With the opening verses of Surah al-Alaq, the final divine message began, and the Prophet ﷺ prepared to carry history\'s greatest trust.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'تلقّى أول الوحي في غار حراء وعمره أربعون سنة، فبدأت بذلك رسالته الخاتمة.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Received the first revelation in the cave of Hira at the age of forty, beginning the final message.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'خديجة بنت خويلد', 'description' => 'زوج النبي ﷺ وأول من آمن به، ثبّتته ليلة الوحي وواسته بمالها ونفسها.'],
                            'en' => ['name' => 'Khadijah bint Khuwaylid', 'description' => 'The Prophet\'s wife and the first believer, who comforted and supported him on the night of revelation.'],
                        ],
                    ],
                    [
                        'role' => 'witness',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'ورقة بن نوفل', 'description' => 'ابن عم خديجة، شيخ تنصّر وقرأ الكتب، عرف صدق النبوة وبشّر النبي ﷺ بها.'],
                            'en' => ['name' => 'Waraqah ibn Nawfal', 'description' => 'Khadijah\'s cousin, a learned Christian elder who recognized the revelation as the same sent to Moses.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'fatrat-al-wahy',
                'event_type' => 'revelation',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'medium',
                'hijri_date' => '13 ق.هـ',
                'hijri_year' => -13,
                'gregorian_year' => 610,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 140,
                'translations' => [
                    'ar' => [
                        'title' => 'فترة الوحي ثم نزول «يا أيها المدثر»',
                        'description' => 'بعد نزول صدر سورة العلق في غار حراء فتر الوحي مدةً، اختلف أهل السير في تقديرها بين أيام وأشهر، حتى حزن رسول الله ﷺ حزنًا شديدًا واشتاق إلى ما ذاقه من حلاوة الوحي ولقاء جبريل عليه السلام. وكانت لهذه الفترة حكمة بالغة؛ إذ ذهب عنه الروع الذي أصابه أول مرة، وتهيأت نفسه الشريفة لاستقبال الوحي وتحمّل أعبائه، وازداد شوقًا إلى الرسالة فلم تعد مفاجأة تثقل عليه. ثم بينما هو يمشي إذ سمع صوتًا من السماء، فرفع بصره فإذا المَلَك الذي جاءه بحراء جالس على كرسي بين السماء والأرض، فرُعب منه ورجع إلى أهله يقول: «زمّلوني زمّلوني»، فأنزل الله تعالى: ﴿يَا أَيُّهَا الْمُدَّثِّرُ * قُمْ فَأَنذِرْ * وَرَبَّكَ فَكَبِّرْ * وَثِيَابَكَ فَطَهِّرْ * وَالرُّجْزَ فَاهْجُرْ﴾. وبهذه الآيات الكريمات انتقل ﷺ من طور النبوة إلى طور الرسالة والإنذار؛ فقد كان نزول «اقرأ» إيذانًا بنبوته، وكان نزول «المدثر» تكليفًا له بالقيام بأعباء الدعوة وإنذار الناس. ثم تتابع الوحي بعد ذلك وحمي وتواتر، فشمّر رسول الله ﷺ عن ساعد الجد، وقام في قومه داعيًا إلى الله على بصيرة، مبتدئًا بأهل بيته وخاصته، صابرًا على ما سيلقاه في سبيل ذلك ثلاثة وعشرين عامًا حتى أكمل الله به الدين.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Pause of Revelation and "O You Wrapped Up"',
                        'description' => 'After the first verses of Surah al-Alaq, revelation paused for a period — estimated between days and months — until the Prophet ﷺ grieved deeply and longed for the sweetness of revelation. This pause carried profound wisdom: his initial awe subsided, his noble soul was prepared to bear the weight of revelation, and his yearning for the message grew. Then, while walking, he heard a voice from the sky and saw the angel who had come to him at Hira seated on a throne between heaven and earth. Shaken, he returned home saying, "Cover me, cover me!" — whereupon God revealed: "O you wrapped up in garments! Arise and warn, and magnify your Lord" (Surah al-Muddaththir). With these verses he moved from prophethood to messengership: "Read" had announced his prophethood, while "Arise and warn" charged him with the burdens of calling humanity. Revelation then continued in succession, and he ﷺ devoted himself fully to the mission for twenty-three years until the religion was completed.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'al-dawah-al-sirriyya',
                'event_type' => 'other',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => '13–10 ق.هـ',
                'hijri_year' => -13,
                'gregorian_year' => 610,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 150,
                'translations' => [
                    'ar' => [
                        'title' => 'الدعوة السرية وأوائل المسلمين',
                        'description' => 'لما أُمر رسول الله ﷺ بالإنذار بقوله تعالى ﴿قُمْ فَأَنذِرْ﴾ بدأ الدعوة إلى الله سرًّا، حذرًا من صدمة قريش التي كانت تتعصب لأوثانها ودين آبائها، فاقتصر في دعوته ثلاث سنين على من يثق بهم من أهل بيته وأصدقائه وخاصته. فكان أول من آمن به من النساء زوجه خديجة بنت خويلد، ومن الرجال الأحرار صديقه أبو بكر الصديق، ومن الصبيان ابن عمه علي بن أبي طالب وكان في كفالته، ومن الموالي زيد بن حارثة مولاه وحِبّه. ثم جعل أبو بكر رضي الله عنه يدعو من يثق به من قومه، فأسلم على يديه عثمان بن عفان والزبير بن العوام وعبد الرحمن بن عوف وسعد بن أبي وقاص وطلحة بن عبيد الله، وهؤلاء الثمانية كانوا طلائع الإسلام وسابقيه الأولين. ثم تتابع الناس في الدخول في دين الله أفرادًا، فأسلم بلال بن رباح وأبو عبيدة بن الجراح وأبو ذر الغفاري وعمار بن ياسر وأمه سمية وأبوه ياسر وصهيب الرومي وخباب بن الأرت وغيرهم من المستضعفين والأشراف. وكان المسلمون يستخفون بصلاتهم في شعاب مكة، حتى بلغ عددهم في هذه المرحلة نحو أربعين رجلًا وامرأة، إيمانهم راسخ كالجبال وإن كانوا قلة مستضعفين.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Secret Call and the First Muslims',
                        'description' => 'When commanded to "arise and warn," the Prophet ﷺ began calling to God in secret, wary of provoking Quraysh\'s fierce attachment to its idols. For three years he confined his call to trusted family and friends. The first believers were his wife Khadijah among women, his friend Abu Bakr among free men, his young cousin Ali ibn Abi Talib among boys, and his freedman Zayd ibn Harithah. Abu Bakr in turn brought to Islam Uthman ibn Affan, al-Zubayr ibn al-Awwam, Abd al-Rahman ibn Awf, Sa\'d ibn Abi Waqqas, and Talhah ibn Ubaydullah — the vanguard of the faith. Others soon followed individually: Bilal ibn Rabah, Abu Ubaydah ibn al-Jarrah, Abu Dharr al-Ghifari, Ammar ibn Yasir with his parents Yasir and Sumayyah, Suhayb al-Rumi, Khabbab ibn al-Aratt, and more — both the oppressed and the noble. The Muslims prayed discreetly in the valleys of Mecca until they numbered around forty men and women, few and vulnerable yet firm as mountains in faith.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'dar-al-arqam',
                'event_type' => 'other',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'medium',
                'hijri_date' => '10 ق.هـ تقريبًا',
                'hijri_year' => -10,
                'gregorian_year' => 613,
                'location' => 'دار الأرقم عند جبل الصفا بمكة',
                'latitude' => 21.4238000,
                'longitude' => 39.8267000,
                'display_order' => 160,
                'translations' => [
                    'ar' => [
                        'title' => 'دار الأرقم: أول مدرسة في الإسلام',
                        'description' => 'لما أخذ عدد المسلمين في التزايد واحتاجوا إلى مكان آمن يجتمعون فيه بعيدًا عن أعين قريش، اتخذ رسول الله ﷺ دار الأرقم بن أبي الأرقم المخزومي عند جبل الصفا مقرًّا سريًّا للدعوة، فكانت أول مدرسة في تاريخ الإسلام وأول مركز تربوي تخرّج فيه الرعيل الأول من الصحابة. وقد كان اختيار هذه الدار غاية في الحكمة؛ فالأرقم كان شابًّا حديث السن لا يُتوقع أن يكون بيته مركزًا للدعوة، وهو من بني مخزوم أشد البطون عداوة لبني هاشم، فلم يخطر ببال قريش أن تبحث عن محمد ﷺ وأصحابه في دار مخزومية، فضلًا عن قربها من الصفا في قلب مكة بما يسهل الوصول إليها دون ريبة. كان رسول الله ﷺ يجتمع فيها بأصحابه يقرئهم القرآن ويزكيهم ويعلمهم الكتاب والحكمة، ويستقبل فيها من أراد الدخول في الإسلام، فأسلم فيها خلق كثير، وكان آخر من أسلم فيها على المشهور عمر بن الخطاب رضي الله عنه، فلما أسلم خرج المسلمون منها يجهرون بدين الله. وقد ظلت دار الأرقم رمزًا خالدًا لمرحلة التربية والإعداد السري التي بُني عليها صرح الدعوة الإسلامية.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The House of al-Arqam: Islam\'s First School',
                        'description' => 'As the number of Muslims grew, they needed a safe meeting place away from the eyes of Quraysh. The Prophet ﷺ chose the house of al-Arqam ibn Abi al-Arqam al-Makhzumi by Mount Safa as the secret headquarters of the call — the first school in the history of Islam, where the first generation of Companions was nurtured. The choice was supremely wise: al-Arqam was a young man whose house would never be suspected, and he belonged to Banu Makhzum, the clan most hostile to Banu Hashim — Quraysh would never imagine searching for Muhammad ﷺ in a Makhzumi home. There the Prophet ﷺ taught his companions the Quran, purified their souls, and received those who wished to embrace Islam. Many converted within its walls; the last of them, according to the famous account, was Umar ibn al-Khattab, after whose conversion the Muslims marched out proclaiming their faith openly. The House of al-Arqam remains an enduring symbol of the stage of quiet education upon which the edifice of the Islamic call was built.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'al-dawah-al-jahriyya',
                'event_type' => 'other',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => '10 ق.هـ تقريبًا',
                'hijri_year' => -10,
                'gregorian_year' => 613,
                'location' => 'جبل الصفا بمكة المكرمة',
                'latitude' => 21.4238000,
                'longitude' => 39.8267000,
                'display_order' => 170,
                'translations' => [
                    'ar' => [
                        'title' => 'الجهر بالدعوة وصعود الصفا',
                        'description' => 'بعد ثلاث سنين من الدعوة السرية نزل قول الله تعالى: ﴿وَأَنذِرْ عَشِيرَتَكَ الْأَقْرَبِينَ﴾ وقوله: ﴿فَاصْدَعْ بِمَا تُؤْمَرُ وَأَعْرِضْ عَنِ الْمُشْرِكِينَ﴾، فانتقلت الدعوة إلى طور الجهر والإعلان. فصعد رسول الله ﷺ على الصفا فجعل ينادي: «يا بني فهر، يا بني عدي» لبطون قريش حتى اجتمعوا، فقال: «أرأيتكم لو أخبرتكم أن خيلًا بالوادي تريد أن تغير عليكم، أكنتم مصدقيّ؟» قالوا: نعم، ما جرّبنا عليك إلا صدقًا. قال: «فإني نذير لكم بين يدي عذاب شديد». فقال أبو لهب: تبًّا لك سائر اليوم، ألهذا جمعتنا؟! فأنزل الله: ﴿تَبَّتْ يَدَا أَبِي لَهَبٍ وَتَبَّ﴾. ثم جمع ﷺ بني هاشم وبني المطلب فدعاهم إلى الله وقال: «والله الذي لا إله إلا هو إني رسول الله إليكم خاصة وإلى الناس عامة». ومنذ ذلك اليوم انتقل الصراع بين التوحيد والوثنية إلى العلن؛ فقد جهر ﷺ بعيب آلهة قريش وتسفيه أحلامها، فأجمعت على عداوته، وقام عمه أبو طالب دونه يحميه ويمنعه، فاستمر ﷺ يدعو إلى الله جهارًا في المجالس والمواسم لا تأخذه في الله لومة لائم، صادعًا بالحق الذي أُمر به.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Public Call from Mount Safa',
                        'description' => 'After three years of secret preaching, God revealed: "And warn your nearest kindred" and "Proclaim openly what you are commanded." The Prophet ﷺ climbed Mount Safa and called out to the clans of Quraysh until they gathered, then asked: "If I told you that horsemen in the valley were about to attack you, would you believe me?" They replied: "Yes — we have never known you to lie." He said: "Then I am a warner to you before a severe punishment." Abu Lahab retorted: "May you perish! Is this why you gathered us?" — whereupon God revealed Surah al-Masad: "Perish the hands of Abu Lahab." The Prophet ﷺ then gathered Banu Hashim and Banu al-Muttalib, declaring: "By God besides whom there is no deity, I am the Messenger of God to you in particular and to all humanity." From that day the struggle between monotheism and idolatry became public. Quraysh united in enmity while his uncle Abu Talib stood to protect him, and the Prophet ﷺ continued calling openly in assemblies and pilgrimage seasons, fearing no blame in the way of God.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'idtihad-al-mustadafin',
                'event_type' => 'personal',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => '9 ق.هـ تقريبًا',
                'hijri_year' => -9,
                'gregorian_year' => 614,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 180,
                'translations' => [
                    'ar' => [
                        'title' => 'اضطهاد قريش للمستضعفين من المسلمين',
                        'description' => 'لما جهر رسول الله ﷺ بالدعوة ورأت قريش أن الإسلام يتسرب إلى بيوتها، أخذت تنكّل بالمستضعفين من المسلمين ممن لا عشيرة لهم تحميهم، فسامتهم سوء العذاب لعلهم يرجعون عن دينهم. فهذا بلال بن رباح الحبشي يخرجه أمية بن خلف في رمضاء الظهيرة فيلقيه على ظهره ويأمر بالصخرة العظيمة فتوضع على صدره ويقول: لا تزال هكذا حتى تموت أو تكفر بمحمد، وبلال يقول: أحدٌ أحد، حتى اشتراه أبو بكر الصديق فأعتقه. وهذا عمار بن ياسر وأبوه ياسر وأمه سمية يعذَّبون في رمضاء مكة، فيمر بهم رسول الله ﷺ فيقول: «صبرًا آل ياسر فإن موعدكم الجنة»، وقد طعن أبو جهل سميةَ بحربته فكانت أول شهيدة في الإسلام، ومات ياسر تحت العذاب. وعُذّب خباب بن الأرت بالنار حتى أطفأ ودكُ ظهره جمرها، وعُذّب صهيب وأبو فُكيهة وعامر بن فهيرة وغيرهم. وما زاد ذلك المؤمنين إلا إيمانًا وتسليمًا، حتى نزل فيهم قول الله تعالى: ﴿أَحَسِبَ النَّاسُ أَن يُتْرَكُوا أَن يَقُولُوا آمَنَّا وَهُمْ لَا يُفْتَنُونَ﴾، وبقي صبرهم الأسطوري مدرسة خالدة في الثبات على الحق مهما عظمت التضحيات.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'Persecution of the Oppressed Believers',
                        'description' => 'When the call became public and Quraysh saw Islam seeping into its households, it turned savagely on the vulnerable Muslims who had no clan protection. Bilal ibn Rabah was dragged out by Umayyah ibn Khalaf onto the scorching midday sand, a great boulder placed on his chest, and told: "You will stay like this until you die or renounce Muhammad" — yet Bilal only repeated: "One! One!" until Abu Bakr purchased and freed him. Ammar ibn Yasir, his father Yasir, and his mother Sumayyah were tortured in the burning heat; the Prophet ﷺ would pass by saying: "Patience, family of Yasir — your appointed place is Paradise." Abu Jahl stabbed Sumayyah with his spear, making her the first martyr of Islam, and Yasir died under torture. Khabbab ibn al-Aratt was branded with burning coals, and Suhayb, Abu Fukayhah, Amir ibn Fuhayrah and others suffered similar torment. Yet persecution only deepened their faith, and God revealed concerning them: "Do people think they will be left to say \'We believe\' without being tested?" Their legendary endurance remains an eternal school of steadfastness.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'بلال بن رباح', 'description' => 'مؤذن الرسول ﷺ، عُذّب في رمضاء مكة وهو يقول: أحدٌ أحد، حتى أعتقه أبو بكر.'],
                            'en' => ['name' => 'Bilal ibn Rabah', 'description' => 'The Prophet\'s muezzin, tortured on Mecca\'s burning sands while repeating "One! One!" until freed by Abu Bakr.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'سمية بنت الخياط', 'description' => 'أم عمار بن ياسر وأول شهيدة في الإسلام، قتلها أبو جهل بحربته لثباتها على دينها.'],
                            'en' => ['name' => 'Sumayyah bint Khayyat', 'description' => 'Mother of Ammar ibn Yasir and the first martyr of Islam, killed by Abu Jahl\'s spear for her steadfast faith.'],
                        ],
                    ],
                    [
                        'role' => 'enemy',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'أبو جهل عمرو بن هشام', 'description' => 'فرعون هذه الأمة وأشد صناديد قريش عداوة للإسلام وتعذيبًا للمستضعفين.'],
                            'en' => ['name' => 'Abu Jahl (Amr ibn Hisham)', 'description' => 'The fiercest of Quraysh\'s chieftains in enmity to Islam and in torturing the oppressed believers.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'hijrat-al-habasha-al-ula',
                'event_type' => 'migration',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => 'رجب 8 ق.هـ',
                'hijri_year' => -8,
                'hijri_month' => 7,
                'gregorian_year' => 615,
                'location' => 'الحبشة (مملكة أكسوم)',
                'latitude' => 14.1211000,
                'longitude' => 38.7234000,
                'display_order' => 190,
                'translations' => [
                    'ar' => [
                        'title' => 'الهجرة الأولى إلى الحبشة',
                        'description' => 'لما اشتد بلاء قريش على المسلمين ورأى رسول الله ﷺ ما يصيب أصحابه من العذاب وهو لا يستطيع أن يدفعه عنهم، قال لهم: «لو خرجتم إلى أرض الحبشة فإن بها ملكًا لا يُظلم عنده أحد، وهي أرض صدق، حتى يجعل الله لكم فرجًا مما أنتم فيه». فخرج في رجب من السنة الخامسة للبعثة أحد عشر رجلًا وأربع نسوة، يتسللون من مكة سرًّا إلى ساحل الشُّعيبة، فيهم عثمان بن عفان وزوجه رقية بنت رسول الله ﷺ — وقد قال فيهما ﷺ: «إنهما أول بيت هاجر في سبيل الله بعد إبراهيم ولوط» — وفيهم عبد الرحمن بن عوف والزبير بن العوام ومصعب بن عمير وأبو سلمة وزوجه أم سلمة وعثمان بن مظعون وأبو حذيفة بن عتبة وزوجه سهلة. فركبوا البحر إلى الحبشة فآواهم النجاشي أصحمة بن أبجر وأحسن جوارهم، فأقاموا عنده آمنين على دينهم لا يُؤذَون ولا يُفتنون. وكانت هذه الهجرة أول هجرة في الإسلام، وأول درس في فقه حماية الدين بالانتقال عن أرض الفتنة، وفيها ظهر بُعد نظر النبي ﷺ في اختيار دار اللجوء عند ملك عادل من أهل الكتاب.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The First Migration to Abyssinia',
                        'description' => 'As Quraysh\'s persecution intensified and the Prophet ﷺ could not shield his companions from torture, he told them: "If you went to the land of Abyssinia, there is a king there under whom no one is wronged — a land of truthfulness — until God grants you relief." In Rajab of the fifth year of prophethood, eleven men and four women slipped secretly out of Mecca to the port of Shu\'aybah. Among them were Uthman ibn Affan and his wife Ruqayyah, the Prophet\'s daughter — of whom he said they were the first household to emigrate in God\'s cause since Ibrahim and Lut — along with Abd al-Rahman ibn Awf, al-Zubayr ibn al-Awwam, Mus\'ab ibn Umayr, Abu Salamah and Umm Salamah, and Uthman ibn Maz\'un. They sailed to Abyssinia, where the Negus Ashamah received them generously and they lived secure in their faith. This first migration in Islam taught the principle of protecting one\'s religion by leaving the land of persecution, and showed the Prophet\'s foresight in choosing refuge with a just king of the People of the Book.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عثمان بن عفان', 'description' => 'ذو النورين، هاجر بزوجه رقية بنت النبي ﷺ فكانا أول بيت مهاجر في سبيل الله.'],
                            'en' => ['name' => 'Uthman ibn Affan', 'description' => 'Emigrated with his wife Ruqayyah, the Prophet\'s daughter — the first household to migrate in God\'s cause.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'رقية بنت رسول الله ﷺ', 'description' => 'ابنة النبي ﷺ وزوج عثمان بن عفان، هاجرت معه إلى الحبشة فرارًا بدينها.'],
                            'en' => ['name' => 'Ruqayyah bint Muhammad ﷺ', 'description' => 'The Prophet\'s daughter and wife of Uthman, who emigrated with him to Abyssinia for the sake of her faith.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'hijrat-al-habasha-al-thaniya',
                'event_type' => 'migration',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => '7 ق.هـ تقريبًا',
                'hijri_year' => -7,
                'gregorian_year' => 616,
                'location' => 'الحبشة (مملكة أكسوم)',
                'latitude' => 14.1211000,
                'longitude' => 38.7234000,
                'display_order' => 200,
                'translations' => [
                    'ar' => [
                        'title' => 'الهجرة الثانية إلى الحبشة وموقف النجاشي',
                        'description' => 'لما بلغ مهاجري الحبشة الأولين أن قريشًا كفّت عن المسلمين رجع بعضهم إلى مكة، فوجدوا البلاء أشد مما كان، فأذن رسول الله ﷺ في هجرة ثانية أعظم من الأولى، فهاجر ثلاثة وثمانون رجلًا وثماني عشرة امرأة بقيادة جعفر بن أبي طالب ابن عم النبي ﷺ. فلما رأت قريش ذلك بعثت عمرو بن العاص وعبد الله بن أبي ربيعة بالهدايا إلى النجاشي وبطارقته ليردّ المهاجرين، فجمع النجاشي المسلمين وسألهم عن دينهم، فقام جعفر فقال كلمته الجامعة: «أيها الملك، كنا قومًا أهل جاهلية نعبد الأصنام ونأكل الميتة ونأتي الفواحش ونقطع الأرحام ويأكل القوي منا الضعيف، حتى بعث الله إلينا رسولًا منا نعرف نسبه وصدقه وأمانته وعفافه، فدعانا إلى الله لنوحده ونعبده...». ثم قرأ عليه صدرًا من سورة مريم، فبكى النجاشي حتى أخضلت لحيته وقال: إن هذا والذي جاء به عيسى ليخرج من مشكاة واحدة، ثم قال للمسلمين: اذهبوا فأنتم سُيوم بأرضي — أي آمنون — وردّ هدايا قريش. ولما حاول عمرو في الغد أن يوقع بهم في عيسى عليه السلام قال جعفر: نقول فيه ما جاءنا به نبينا: هو عبد الله ورسوله وروحه وكلمته ألقاها إلى مريم العذراء البتول، فأقرّه النجاشي وأجارهم.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Second Migration to Abyssinia and the Negus',
                        'description' => 'When word reached the first emigrants that Quraysh had relented, some returned to Mecca only to find the persecution fiercer than before. The Prophet ﷺ then permitted a second, larger migration: eighty-three men and eighteen women, led by his cousin Ja\'far ibn Abi Talib. Alarmed, Quraysh sent Amr ibn al-As and Abdullah ibn Abi Rabi\'ah with gifts to the Negus and his bishops, demanding the refugees\' return. The Negus summoned the Muslims, and Ja\'far delivered his immortal speech: "O King, we were a people of ignorance, worshipping idols, eating carrion, committing indecencies, severing kinship ties, the strong devouring the weak — until God sent us a Messenger from among us whose lineage, truthfulness, and integrity we know..." He then recited the opening of Surah Maryam, and the Negus wept until his beard was soaked, declaring: "This and what Jesus brought come from the same lamp." He told the Muslims: "Go — you are safe in my land," and returned Quraysh\'s gifts. When Amr tried the next day to turn him against them concerning Jesus, Ja\'far answered: "He is the servant of God, His Messenger, His spirit, and His word bestowed upon the Virgin Mary" — and the Negus upheld their protection.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'جعفر بن أبي طالب', 'description' => 'ابن عم النبي ﷺ وقائد مهاجري الحبشة، خطيب المسلمين بين يدي النجاشي.'],
                            'en' => ['name' => 'Ja\'far ibn Abi Talib', 'description' => 'The Prophet\'s cousin, leader of the Abyssinian emigrants and their eloquent spokesman before the Negus.'],
                        ],
                    ],
                    [
                        'role' => 'ally',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النجاشي أصحمة بن أبجر', 'description' => 'ملك الحبشة العادل الذي آوى المسلمين وأجارهم، وأسلم بعد ذلك وصلى عليه النبي ﷺ صلاة الغائب.'],
                            'en' => ['name' => 'The Negus (Ashamah ibn Abjar)', 'description' => 'The just king of Abyssinia who sheltered the Muslims; he later embraced Islam, and the Prophet ﷺ prayed the absentee funeral prayer for him.'],
                        ],
                    ],
                    [
                        'role' => 'enemy',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عمرو بن العاص', 'description' => 'رسول قريش إلى النجاشي لردّ المهاجرين قبل إسلامه، وقد أسلم لاحقًا وصار من قادة الفتوح.'],
                            'en' => ['name' => 'Amr ibn al-As', 'description' => 'Quraysh\'s envoy to the Negus seeking the refugees\' extradition; he later embraced Islam and became a great commander.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'islam-hamza',
                'event_type' => 'personal',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => '6 ق.هـ تقريبًا',
                'hijri_year' => -6,
                'gregorian_year' => 616,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 210,
                'translations' => [
                    'ar' => [
                        'title' => 'إسلام حمزة بن عبد المطلب',
                        'description' => 'في السنة السادسة من البعثة أكرم الله الإسلام بإسلام حمزة بن عبد المطلب عم النبي ﷺ وأخيه من الرضاعة، وكان أعز فتى في قريش وأشدها شكيمة، صاحب صيد ورمي. وكان سبب إسلامه على المشهور أن أبا جهل مرّ برسول الله ﷺ عند الصفا فآذاه وشتمه ونال منه، ورسول الله ﷺ ساكت لا يكلمه، ومولاة لعبد الله بن جدعان في مسكن لها تسمع ذلك. ثم أقبل حمزة من قنصه متوشحًا قوسه، فأخبرته المولاة بما لقي ابن أخيه من أبي جهل، فاحتمل حمزةَ الغضبُ ومضى يسعى لم يقف على أحد، حتى دخل المسجد فقام على رأس أبي جهل وهو جالس في القوم، فرفع القوس فضربه بها ضربة شجّه شجة منكرة وقال: أتشتمه وأنا على دينه أقول ما يقول؟ فرُدّ ذلك عليّ إن استطعت! فقامت رجال من بني مخزوم لينصروه فقال أبو جهل: دعوا أبا عمارة فإني والله قد سببت ابن أخيه سبًّا قبيحًا. ثم ثبت الله الإيمانَ في قلب حمزة فحسن إسلامه ولزم أمر رسول الله ﷺ، وعرفت قريش أن محمدًا قد عزّ وامتنع، فكفّت عن بعض ما كانت تناله به. وصار حمزة بعدُ أسدَ الله وأسدَ رسوله وسيد الشهداء يوم أحد.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Conversion of Hamza ibn Abdul-Muttalib',
                        'description' => 'In the sixth year of prophethood, God honored Islam with the conversion of Hamza ibn Abdul-Muttalib, the Prophet\'s uncle and foster-brother, the mightiest and most spirited young man of Quraysh, a renowned hunter. The famous account of his conversion: Abu Jahl passed the Prophet ﷺ at Safa, insulting and abusing him while the Prophet remained silent. A freedwoman of Abdullah ibn Jud\'an witnessed it all. When Hamza returned from his hunt, bow slung over his shoulder, she told him what his nephew had suffered. Seized with fury, Hamza strode straight to the mosque, stood over Abu Jahl as he sat among his people, and struck him with the bow, gashing his head: "Do you insult him when I follow his religion and say what he says? Strike me back if you can!" When men of Banu Makhzum rose to help, Abu Jahl said: "Leave Abu Umarah — by God, I did grievously insult his nephew." God then made faith firm in Hamza\'s heart. Quraysh recognized that Muhammad ﷺ had gained a mighty protector and curbed some of its abuse. Hamza became the Lion of God and, at Uhud, the Master of Martyrs.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'حمزة بن عبد المطلب', 'description' => 'عم النبي ﷺ وأسد الله، أسلم غضبًا لرسول الله ثم ثبت الإيمان في قلبه، واستشهد يوم أحد.'],
                            'en' => ['name' => 'Hamza ibn Abdul-Muttalib', 'description' => 'The Prophet\'s uncle, the Lion of God, whose conversion strengthened Islam; he was martyred at Uhud.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'islam-umar',
                'event_type' => 'personal',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => 'ذو الحجة 6 ق.هـ تقريبًا',
                'hijri_year' => -6,
                'hijri_month' => 12,
                'gregorian_year' => 616,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 220,
                'translations' => [
                    'ar' => [
                        'title' => 'إسلام عمر بن الخطاب',
                        'description' => 'كان رسول الله ﷺ قد دعا ربه: «اللهم أعزّ الإسلام بأحبّ هذين الرجلين إليك: بأبي جهل أو بعمر بن الخطاب»، فكان أحبهما إلى الله عمر. خرج عمر يومًا متوشحًا سيفه يريد قتل رسول الله ﷺ، فلقيه نعيم بن عبد الله فقال: أين تعمد يا عمر؟ قال: أريد محمدًا. قال: أفلا ترجع إلى أهل بيتك فتقيمهم؟ إن أختك فاطمة وزوجها سعيد بن زيد قد أسلما. فرجع عمر مغضبًا إلى بيت أخته، وعندهما خباب بن الأرت يقرئهما سورة طه، فلما سمعوا حسّه توارى خباب وأخفت فاطمة الصحيفة. فقال: ما هذه الهَيْنمة؟ ثم بطش بزوج أخته فقامت فاطمة دونه فضربها فأدمى وجهها، فقالت وهي غاضبة: نعم أسلمنا وآمنا بالله ورسوله فاصنع ما بدا لك! فلما رأى الدم ندم واستحيا وقال: أعطوني هذه الصحيفة، فقالت: إنك رجس ولا يمسه إلا المطهرون، فقم فاغتسل. فاغتسل وقرأ: ﴿طه * مَا أَنزَلْنَا عَلَيْكَ الْقُرْآنَ لِتَشْقَى﴾ فقال: ما أحسن هذا الكلام وأكرمه! دلوني على محمد. فأتى دار الأرقم فأسلم، فكبّر المسلمون تكبيرة سُمعت بأعلى مكة، ثم قال: ألسنا على الحق؟ ففيمَ الاختفاء؟ فخرج المسلمون في صفين يجهرون بدينهم، فسماه رسول الله ﷺ «الفاروق»؛ إذ فرّق الله به بين الحق والباطل، وعزّ الإسلام بإسلامه عزًّا ظاهرًا.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Conversion of Umar ibn al-Khattab',
                        'description' => 'The Prophet ﷺ had prayed: "O God, strengthen Islam with the dearer to You of these two men: Abu Jahl or Umar ibn al-Khattab" — and the dearer to God was Umar. One day Umar set out, sword in hand, to kill the Prophet ﷺ. Nu\'aym ibn Abdullah intercepted him: "Why not first set your own house in order? Your sister Fatimah and her husband Sa\'id ibn Zayd have embraced Islam." Enraged, Umar stormed to his sister\'s house, where Khabbab ibn al-Aratt was teaching them Surah Ta-Ha. Umar struck his brother-in-law, and when Fatimah shielded him he wounded her face. Bleeding, she declared defiantly: "Yes, we have submitted and believed in God and His Messenger — do what you will!" Shamed at the sight of her blood, Umar asked for the scripture. "You are impure," she said, "none may touch it but the purified — go and wash." He washed, read "Ta-Ha. We have not sent down the Quran to distress you," and exclaimed: "How beautiful and noble are these words! Lead me to Muhammad." At the House of al-Arqam he embraced Islam, and the Muslims\' takbir rang over Mecca. At his insistence they marched out in two columns proclaiming their faith openly, and the Prophet ﷺ named him al-Faruq — he by whom God separated truth from falsehood.',
                        'content' => null,
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عمر بن الخطاب', 'description' => 'الفاروق، أسلم في السنة السادسة من البعثة فاعتز به الإسلام، وهو ثاني الخلفاء الراشدين.'],
                            'en' => ['name' => 'Umar ibn al-Khattab', 'description' => 'Al-Faruq, whose conversion in the sixth year of prophethood empowered Islam; later the second rightly-guided caliph.'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'فاطمة بنت الخطاب', 'description' => 'أخت عمر، ثبتت على إسلامها في وجهه حتى كان موقفها سببًا في هدايته.'],
                            'en' => ['name' => 'Fatimah bint al-Khattab', 'description' => 'Umar\'s sister, whose courageous steadfastness in her faith became the cause of his guidance.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'hisar-shib-abi-talib',
                'event_type' => 'other',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'high',
                'hijri_date' => 'المحرم 7 ق.هـ — 4 ق.هـ',
                'hijri_year' => -6,
                'hijri_month' => 1,
                'gregorian_year' => 617,
                'location' => 'شعب أبي طالب بمكة المكرمة',
                'latitude' => 21.4234000,
                'longitude' => 39.8290000,
                'statistics' => [
                    'duration_days' => 1095,
                ],
                'display_order' => 230,
                'translations' => [
                    'ar' => [
                        'title' => 'الحصار في شعب أبي طالب والمقاطعة العامة',
                        'description' => 'لما رأت قريش أن الإسلام يزداد قوةً بإسلام حمزة وعمر، وأن النجاشي قد منع مهاجري الحبشة، وأن بني هاشم وبني المطلب قاموا دون رسول الله ﷺ يمنعونه، أجمعت أمرها على مقاطعتهم مقاطعة شاملة؛ فكتبوا صحيفة ظالمة تعاقدوا فيها على ألا يناكحوا بني هاشم وبني المطلب ولا يبايعوهم ولا يخالطوهم ولا يكلموهم حتى يسلموا إليهم رسول الله ﷺ للقتل، وعلّقوها في جوف الكعبة توكيدًا لها. فانحاز بنو هاشم وبنو المطلب — مؤمنهم وكافرهم حميةً إلا أبا لهب — إلى شعب أبي طالب في المحرم من السنة السابعة للبعثة، فحوصروا فيه نحو ثلاث سنين، قُطعت عنهم الأسواق والميرة حتى أكلوا ورق الشجر والجلود، وكان يُسمع أصوات صبيانهم يتضاغون من الجوع من وراء الشعب، ولا يصل إليهم شيء إلا سرًّا ممن كان يرقّ لهم كحكيم بن حزام وهشام بن عمرو. وقام أبو طالب طوال الحصار يحرس ابن أخيه حتى كان يُنيمه في مضجعه ثم يبدّله بأحد بنيه خوف الاغتيال. ثم أطلع الله نبيه على أن الأرَضة أكلت صحيفتهم فلم تدع فيها إلا ذكر الله، فمشى نفر من عقلاء قريش في نقضها، فلما أُنزلت وجدوها كما أخبر ﷺ، فانفكّ الحصار وخرج بنو هاشم وقد ازدادوا تماسكًا وثبت المؤمنون على دينهم.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Boycott and Siege in the Ravine of Abu Talib',
                        'description' => 'Seeing Islam strengthened by the conversions of Hamza and Umar, and the Negus sheltering the emigrants, Quraysh resolved on a total boycott. They drew up an iniquitous pact: no marriage, no trade, no dealings, and no speech with Banu Hashim and Banu al-Muttalib until they surrendered the Prophet ﷺ for execution — and hung the document inside the Kaaba. In Muharram of the seventh year of prophethood, the two clans — believers and non-believers alike in tribal solidarity, all but Abu Lahab — withdrew into the ravine of Abu Talib, where they were besieged for about three years. Cut off from markets and provisions, they ate leaves and hides, and the cries of their starving children could be heard beyond the ravine; only secret sympathizers like Hakim ibn Hizam and Hisham ibn Amr smuggled in food. Throughout, Abu Talib guarded his nephew, even moving him from his bed at night and placing one of his own sons there for fear of assassination. Then God informed His Prophet that termites had devoured the pact, leaving only the mention of God. Sensible men of Quraysh moved to annul it, found the document exactly as he ﷺ had foretold, and the siege was lifted — the believers emerging firmer than ever.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'inshiqaq-al-qamar',
                'event_type' => 'miracle',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'medium',
                'hijri_date' => '5 ق.هـ تقريبًا',
                'hijri_year' => -5,
                'gregorian_year' => 618,
                'location' => 'منى قرب مكة المكرمة',
                'latitude' => 21.4133000,
                'longitude' => 39.8933000,
                'display_order' => 235,
                'translations' => [
                    'ar' => [
                        'title' => 'معجزة انشقاق القمر',
                        'description' => 'من أعظم المعجزات الحسية التي أيّد الله بها نبيه ﷺ في مكة معجزة انشقاق القمر، وقد ثبتت في الصحيحين من حديث جماعة من الصحابة منهم أنس بن مالك وعبد الله بن مسعود وعبد الله بن عباس وجبير بن مطعم رضي الله عنهم. فقد سأل أهل مكة رسول الله ﷺ أن يريهم آية تدل على صدقه، فأراهم انشقاق القمر فلقتين، حتى رأوا حراء بينهما، فقال رسول الله ﷺ: «اشهدوا». قال ابن مسعود: انشق القمر على عهد رسول الله ﷺ شقتين، فقال لنا رسول الله ﷺ: اشهدوا. ولكن القوم غُلبوا على أمرهم فقالوا: سحرنا محمد! فقال بعضهم: إن كان سحركم فإنه لا يستطيع أن يسحر الناس كلهم، فاسألوا السُّفّار — أي المسافرين القادمين من الآفاق — فسألوهم فأخبروهم أنهم رأوا مثل ذلك، فأبوا إلا العناد والإصرار على الكفر. وأنزل الله في ذلك قرآنًا يُتلى إلى يوم القيامة: ﴿اقْتَرَبَتِ السَّاعَةُ وَانشَقَّ الْقَمَرُ * وَإِن يَرَوْا آيَةً يُعْرِضُوا وَيَقُولُوا سِحْرٌ مُّسْتَمِرٌّ﴾. وكانت هذه المعجزة الباهرة شاهدًا على قدرة الله المطلقة وصدق نبيه ﷺ، ودليلًا على أن الآيات لا تنفع قومًا اختاروا الجحود مع قيام البرهان.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Miracle of the Splitting of the Moon',
                        'description' => 'Among the greatest physical miracles granted to the Prophet ﷺ in Mecca was the splitting of the moon, authentically reported in both Sahih collections from companions including Anas ibn Malik, Abdullah ibn Mas\'ud, Ibn Abbas, and Jubayr ibn Mut\'im. The people of Mecca demanded a sign proving his truthfulness, so God showed them the moon split into two halves — Mount Hira visible between them — and the Prophet ﷺ said: "Bear witness." Ibn Mas\'ud related: "The moon split in two during the lifetime of the Messenger of God ﷺ, and he said to us: Bear witness." Yet the Meccans, overcome by obstinacy, cried: "Muhammad has bewitched us!" Some among them reasoned: "If he bewitched you, he cannot have bewitched all people — ask the travelers arriving from distant horizons." They asked, and the travelers confirmed they had seen the same; still Quraysh persisted in denial. God revealed concerning it: "The Hour has drawn near and the moon has split. And if they see a sign, they turn away and say: passing magic" (Surah al-Qamar). The miracle stands as testimony to God\'s absolute power and to the truth that signs avail not a people determined to disbelieve.',
                        'content' => null,
                    ],
                ],
            ],
            [
                'slug' => 'naqd-al-sahifa',
                'event_type' => 'other',
                'era' => 'mecca',
                'phase' => 'mecca',
                'importance' => 'medium',
                'hijri_date' => '4 ق.هـ تقريبًا',
                'hijri_year' => -4,
                'gregorian_year' => 619,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 240,
                'translations' => [
                    'ar' => [
                        'title' => 'نقض الصحيفة وانتهاء المقاطعة',
                        'description' => 'بعد نحو ثلاث سنين من الحصار الجائر في شعب أبي طالب، قيّض الله لنقض الصحيفة الظالمة رجالًا من قريش أنكرتها قلوبهم وضاقوا ذرعًا بما يصيب بني هاشم من الجوع والبلاء. فقام بأمرها هشام بن عمرو بن ربيعة — وكان يصل بني هاشم سرًّا بالطعام — فمشى إلى زهير بن أبي أمية المخزومي وأمه عاتكة بنت عبد المطلب فقال: أرضيتَ أن تأكل الطعام وتلبس الثياب وأخوالك حيث قد علمت؟ ثم اجتمع معهما المطعم بن عدي وأبو البختري بن هشام وزمعة بن الأسود، وتعاقدوا ليلًا على القيام في نقض الصحيفة. فلما أصبحوا غدوا إلى أنديتهم، فقام زهير فطاف بالبيت سبعًا ثم قال: يا أهل مكة، أنأكل الطعام ونلبس الثياب وبنو هاشم هلكى لا يُباع لهم ولا يُبتاع منهم؟ والله لا أقعد حتى تُشق هذه الصحيفة القاطعة الظالمة! وكان رسول الله ﷺ قد أخبر عمه أبا طالب أن الله سلّط على صحيفتهم الأرَضة فأكلت ما فيها من جور وقطيعة ولم تدع إلا ذكر الله، فقال أبو طالب لقريش: إن كان ابن أخي صادقًا فانتهوا عن قطيعتنا، فلما أنزلوها وجدوها كما قال ﷺ، فسقطت حجتهم وشُقّت الصحيفة، وخرج بنو هاشم وبنو المطلب من الشعب، وكانت هذه الآية برهانًا جديدًا على صدق النبوة أمام قريش كلها.',
                        'content' => null,
                    ],
                    'en' => [
                        'title' => 'The Annulment of the Pact and End of the Boycott',
                        'description' => 'After nearly three years of unjust siege in the ravine of Abu Talib, God moved certain men of Quraysh — whose hearts had long rejected the cruel pact — to annul it. Hisham ibn Amr, who had been secretly supplying Banu Hashim with food, approached Zuhayr ibn Abi Umayyah, whose mother was Atikah bint Abdul-Muttalib: "Are you content to eat and dress while your maternal kin perish?" He then gathered al-Mut\'im ibn Adi, Abu al-Bakhtari, and Zam\'ah ibn al-Aswad, and they pledged by night to overturn the document. The next morning Zuhayr circumambulated the Kaaba seven times and proclaimed: "People of Mecca! Shall we eat and dress while Banu Hashim perish, unable to buy or sell? By God, I will not rest until this unjust, severing pact is torn up!" Meanwhile the Prophet ﷺ had informed his uncle Abu Talib that God had set termites upon the document, devouring every clause of injustice and leaving only the mention of God. Abu Talib challenged Quraysh: if his nephew spoke truly, they must end the boycott. When they brought the pact down, they found it exactly as foretold. Their argument collapsed, the document was destroyed, and Banu Hashim emerged from the ravine — the event standing as fresh proof of prophethood before all Quraysh.',
                        'content' => null,
                    ],
                ],
            ],
        ];
    }
}
