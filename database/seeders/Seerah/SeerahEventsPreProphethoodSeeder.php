<?php

declare(strict_types=1);

namespace Database\Seeders\Seerah;

/**
 * Batch 1 of 5 — From the Year of the Elephant to the eve of revelation (570–610 CE).
 */
class SeerahEventsPreProphethoodSeeder extends SeerahEventSeederBase
{
    protected function events(): array
    {
        return [
            [
                'slug' => 'aam-al-fil',
                'event_type' => 'miracle',
                'era' => 'pre_birth',
                'phase' => 'early_life',
                'importance' => 'high',
                'hijri_date' => 'المحرم 53 ق.هـ',
                'hijri_year' => -53,
                'hijri_month' => 1,
                'gregorian_year' => 571,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 10,
                'translations' => [
                    'ar' => [
                        'title' => 'عام الفيل',
                        'description' => 'في العام الذي وُلد فيه النبي ﷺ وقعت حادثة عظيمة خلّدها القرآن الكريم في سورة الفيل. كان أبرهة الأشرم الحبشي واليًا على اليمن من قبل ملك الحبشة، فبنى في صنعاء كنيسة ضخمة سمّاها العرب «القُلَّيْس»، وأراد أن يصرف حجّ العرب إليها بدلًا من الكعبة المشرفة. فلما لم يتحقق له ذلك، غضب وأقسم ليهدمنّ الكعبة، فجهّز جيشًا عظيمًا يتقدمه فيل ضخم يقال له محمود، وسار به نحو مكة. وفي الطريق استولى على إبل لعبد المطلب بن هاشم جدّ النبي ﷺ، فجاءه عبد المطلب يطلب إبله، فعجب أبرهة وقال: أتكلمني في مائتي بعير وتترك بيتًا هو دينك ودين آبائك؟ فأجابه عبد المطلب بكلمته الخالدة: «أنا ربُّ الإبل، وإنَّ للبيت ربًّا سيحميه». ولما دنا الجيش من مكة بَرَك الفيل وأبى أن يتقدم نحو الكعبة، وأرسل الله عليهم طيرًا أبابيل ترميهم بحجارة من سجّيل، فجعلهم كعصف مأكول، وهلك أبرهة في طريق عودته. وقد عظُمت هذه الحادثة في نفوس العرب حتى أرّخوا بها، فقالوا: وُلد فلان عام الفيل، وكانت إرهاصًا بين يدي مولد خاتم المرسلين ﷺ.',
                    ],
                    'en' => [
                        'title' => 'The Year of the Elephant',
                        'description' => 'In the year of the Prophet\'s ﷺ birth, Abraha al-Ashram, the Abyssinian viceroy of Yemen, built a grand cathedral in Sana\'a and sought to divert the Arabs\' pilgrimage away from the Kaaba. When this failed, he marched on Mecca with a great army led by an enormous elephant. Abdul-Muttalib, the Prophet\'s grandfather, famously told him: "I am the lord of the camels, but the House has a Lord who will defend it." As the army approached, the elephant knelt and refused to advance, and God sent flocks of birds pelting the army with stones of baked clay, destroying it — an event immortalized in Surah al-Fil. The Arabs dated their calendar by this year, which heralded the birth of the final Messenger.',
                    ],
                ],
            ],
            [
                'slug' => 'mawlid-al-nabi',
                'event_type' => 'birth',
                'era' => 'childhood',
                'phase' => 'early_life',
                'importance' => 'high',
                'is_pivotal' => true,
                'is_featured' => true,
                'hijri_date' => '12 ربيع الأول 53 ق.هـ (عام الفيل)',
                'hijri_year' => -53,
                'hijri_month' => 3,
                'hijri_day' => 12,
                'gregorian_date' => '571-04-22',
                'gregorian_year' => 571,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 20,
                'translations' => [
                    'ar' => [
                        'title' => 'مولد النبي ﷺ',
                        'description' => 'وُلد رسول الله محمد بن عبد الله بن عبد المطلب ﷺ في مكة المكرمة صبيحة يوم الاثنين الثاني عشر من شهر ربيع الأول عام الفيل، على أشهر أقوال أهل السير، الموافق سنة 571 للميلاد تقريبًا. وُلد ﷺ يتيمَ الأب؛ إذ توفي أبوه عبد الله وهو ما يزال جنينًا في بطن أمه آمنة بنت وهب الزهرية، ودُفن في يثرب عند أخواله من بني النجار. ولما وضعته أمه أرسلت إلى جدّه عبد المطلب تبشّره بحفيده، فجاء مسرورًا وأخذه فدخل به الكعبة ودعا الله وشكره، وسمّاه «محمدًا»، وهو اسم لم يكن شائعًا في العرب، فلما سُئل عن ذلك قال: أردت أن يُحمد في السماء والأرض. وفي اليوم السابع ختنه وأولم له على عادة العرب. وأرضعته أولَ ما أرضعته أمُّه، ثم ثويبةُ مولاة أبي لهب. وقد نشأ ﷺ في أشرف بيوت العرب نسبًا؛ فهو من بني هاشم من قريش، وقد قال ﷺ: «إن الله اصطفى كنانة من ولد إسماعيل، واصطفى قريشًا من كنانة، واصطفى من قريش بني هاشم، واصطفاني من بني هاشم». فكان مولده ﷺ فجرَ التاريخ الجديد ومبدأ النور الذي عمّ العالمين.',
                    ],
                    'en' => [
                        'title' => 'Birth of the Prophet ﷺ',
                        'description' => 'The Messenger of God, Muhammad ibn Abdullah ibn Abdul-Muttalib ﷺ, was born in Mecca on Monday morning, the 12th of Rabi\' al-Awwal in the Year of the Elephant, corresponding to approximately 571 CE. He was born an orphan, his father Abdullah having died while his mother Aminah bint Wahb was still pregnant. His grandfather Abdul-Muttalib joyfully carried the newborn into the Kaaba, thanked God, and named him "Muhammad" — an uncommon name among the Arabs — saying he wished him to be praised in heaven and on earth. He was first nursed by his mother, then by Thuwaybah. Born into Banu Hashim, the noblest house of Quraysh, his birth marked the dawn of a new era for all humanity.',
                    ],
                ],
                'timeline' => [
                    [
                        'ar' => ['title' => 'وفاة والده عبد الله', 'description' => 'توفي عبد الله بن عبد المطلب في يثرب عائدًا من رحلة تجارية إلى الشام، والنبي ﷺ ما يزال جنينًا في بطن أمه، فوُلد ﷺ يتيمًا.'],
                        'en' => ['title' => 'Death of his father Abdullah', 'description' => 'Abdullah ibn Abdul-Muttalib died in Yathrib returning from a trade journey to Syria, while the Prophet ﷺ was still in his mother\'s womb.'],
                    ],
                    [
                        'ar' => ['title' => 'ليلة المولد المباركة', 'description' => 'وُلد ﷺ صبيحة يوم الاثنين الثاني عشر من ربيع الأول عام الفيل في شعب بني هاشم بمكة، وقالت أمه آمنة: لقد خرج مني نور أضاءت له قصور الشام.'],
                        'en' => ['title' => 'The blessed night of birth', 'description' => 'He ﷺ was born on Monday morning, 12 Rabi\' al-Awwal of the Year of the Elephant, in the quarter of Banu Hashim in Mecca.'],
                    ],
                    [
                        'ar' => ['title' => 'تسمية جده له «محمدًا»', 'description' => 'حمله جده عبد المطلب إلى الكعبة شاكرًا داعيًا، وسمّاه محمدًا ليُحمد في السماء والأرض، وختنه وأولم له في يومه السابع.'],
                        'en' => ['title' => 'His grandfather names him Muhammad', 'description' => 'Abdul-Muttalib carried him into the Kaaba in gratitude and named him Muhammad, "so that he may be praised in heaven and on earth."'],
                    ],
                    [
                        'ar' => ['title' => 'إرضاع ثويبة', 'description' => 'أرضعته أمه آمنة أيامًا، ثم أرضعته ثويبة مولاة أبي لهب قبل أن يُسترضع له في بادية بني سعد.'],
                        'en' => ['title' => 'Nursing by Thuwaybah', 'description' => 'After his mother, he was nursed by Thuwaybah, the freed maid of Abu Lahab, before being sent to the desert of Banu Sa\'d.'],
                    ],
                    [
                        'ar' => ['title' => 'إرساله إلى بادية بني سعد', 'description' => 'جريًا على عادة أشراف العرب، أُرسل ﷺ إلى بادية بني سعد ليسترضع هناك وينشأ فصيح اللسان قوي البدن، فكفلته حليمة السعدية.'],
                        'en' => ['title' => 'Sent to the desert of Banu Sa\'d', 'description' => 'Following noble Arab custom, the infant was entrusted to Halimah al-Sa\'diyyah to be nursed in the desert of Banu Sa\'d.'],
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'خاتم الأنبياء والمرسلين، وُلد بمكة عام الفيل يتيم الأب في بني هاشم أشرف بيوت قريش.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'The final Prophet and Messenger, born in Mecca in the Year of the Elephant into Banu Hashim.'],
                        ],
                    ],
                    [
                        'role' => 'other',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'آمنة بنت وهب', 'description' => 'أم النبي ﷺ، من بني زهرة من قريش، توفيت وهو في السادسة من عمره.'],
                            'en' => ['name' => 'Aminah bint Wahb', 'description' => 'The Prophet\'s mother, of Banu Zuhrah of Quraysh; she died when he was six years old.'],
                        ],
                    ],
                    [
                        'role' => 'other',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'عبد المطلب بن هاشم', 'description' => 'جد النبي ﷺ وسيد قريش، تولى كفالته بعد وفاة أمه وسمّاه محمدًا.'],
                            'en' => ['name' => 'Abdul-Muttalib ibn Hashim', 'description' => 'The Prophet\'s grandfather and chief of Quraysh, who named him Muhammad and later became his guardian.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'al-ridaa-fi-bani-saad',
                'event_type' => 'miracle',
                'era' => 'childhood',
                'phase' => 'early_life',
                'importance' => 'medium',
                'hijri_date' => '53–48 ق.هـ',
                'hijri_year' => -52,
                'gregorian_year' => 575,
                'location' => 'بادية بني سعد قرب الطائف',
                'latitude' => 21.1500000,
                'longitude' => 40.3500000,
                'display_order' => 30,
                'translations' => [
                    'ar' => [
                        'title' => 'الرضاعة في بني سعد وحادثة شق الصدر',
                        'description' => 'جريًا على عادة أشراف العرب في استرضاع أبنائهم في البوادي طلبًا لفصاحة اللسان وقوة الجسم، اُسترضع للنبي ﷺ في بادية بني سعد بن بكر، فكفلته حليمة بنت أبي ذؤيب السعدية. وقد روت حليمة أنها قدمت مكة في سنة جدباء مع نسوة من بني سعد يلتمسن الرضعاء، فلم تجد إلا ذلك اليتيم، فأخذته بعد أن أعرض عنه غيرها، فما إن ضمّته إلى رحلها حتى رأت البركة تغمر بيتها؛ درّت شارفها، وحفلت أتانها، وأخصبت أرضها، حتى عرف بنو سعد ذلك. وأقام ﷺ فيهم نحو أربع سنين أو خمس، وفيها وقعت حادثة شق الصدر الأولى التي رواها مسلم في صحيحه: إذ أتاه جبريل عليه السلام وهو يلعب مع الغلمان، فأخذه فصرعه، فشق عن قلبه فاستخرج منه علقة فقال: هذا حظ الشيطان منك، ثم غسله في طست من ذهب بماء زمزم ثم لأَمَه وأعاده مكانه. فخافت عليه حليمة وزوجها وردّاه إلى أمه آمنة، وهو ابن خمس سنين. وكان ﷺ يفخر بنشأته فيهم ويقول: «أنا أعربُكم؛ أنا قرشيٌّ واستُرضعت في بني سعد بن بكر».',
                    ],
                    'en' => [
                        'title' => 'Nursing in Banu Sa\'d and the Splitting of the Chest',
                        'description' => 'Following the custom of noble Arab families, the infant Prophet ﷺ was sent to the desert of Banu Sa\'d ibn Bakr to be nursed by Halimah al-Sa\'diyyah. Halimah recounted that from the moment she took the orphan child, blessing filled her household: her animals gave abundant milk and her land flourished. He remained with them about four to five years, during which the first Splitting of the Chest occurred, as narrated in Sahih Muslim: the angel Jibril came to him as he played with other boys, opened his chest, removed a clot saying "This is Satan\'s portion of you," washed his heart with Zamzam water in a golden basin, and restored it. Alarmed, Halimah returned him to his mother Aminah.',
                    ],
                ],
            ],
            [
                'slug' => 'wafat-aminah',
                'event_type' => 'death',
                'era' => 'childhood',
                'phase' => 'early_life',
                'importance' => 'medium',
                'hijri_date' => '47 ق.هـ',
                'hijri_year' => -47,
                'gregorian_year' => 577,
                'location' => 'الأبواء بين مكة والمدينة',
                'latitude' => 23.0833000,
                'longitude' => 38.9667000,
                'display_order' => 40,
                'translations' => [
                    'ar' => [
                        'title' => 'وفاة أمه آمنة بنت وهب',
                        'description' => 'لما بلغ النبي ﷺ ست سنين خرجت به أمه آمنة بنت وهب إلى يثرب تزور أخواله من بني عدي بن النجار، وتزور قبر زوجها عبد الله، ومعها حاضنته أم أيمن بركة الحبشية وجده عبد المطلب. فأقامت بيثرب شهرًا ثم قفلت راجعة إلى مكة، فلما كانت بالأبواء — قرية بين مكة والمدينة — مرضت وتوفيت ودُفنت هناك، والنبي ﷺ ابن ست سنين. فعادت به أم أيمن إلى مكة إلى جده عبد المطلب، وقد اجتمع عليه ﷺ يُتم الأبوين معًا، وهو بعدُ طفل صغير، ليتولى الله تربيته ورعايته كما قال سبحانه: «ألم يجدك يتيمًا فآوى». وقد مرّ النبي ﷺ بقبر أمه بالأبواء في عمرة الحديبية بعد البعثة فبكى وأبكى من حوله، وقال: «استأذنت ربي في أن أزور قبر أمي فأذن لي، فزوروا القبور فإنها تذكّر الموت». وكان لهذا اليتم المبكر أثر بالغ في صقل شخصيته ﷺ، إذ نشأ في كنف الله بعيدًا عن دلال الأبوة، معتمدًا على الله وحده، رحيمًا باليتامى والضعفاء.',
                    ],
                    'en' => [
                        'title' => 'Death of His Mother Aminah bint Wahb',
                        'description' => 'When the Prophet ﷺ was six years old, his mother Aminah took him to Yathrib to visit his maternal relatives of Banu al-Najjar and the grave of his father Abdullah, accompanied by his nurse Umm Ayman. On the return journey she fell ill and died at al-Abwa, a village between Mecca and Medina, where she was buried. Umm Ayman brought the now fully orphaned child back to his grandfather Abdul-Muttalib in Mecca. Years later, the Prophet ﷺ visited her grave at al-Abwa and wept. This early orphanhood shaped his character profoundly — raised in God\'s care, he grew compassionate toward orphans and the weak.',
                    ],
                ],
            ],
            [
                'slug' => 'kafalat-abi-talib',
                'event_type' => 'personal',
                'era' => 'childhood',
                'phase' => 'early_life',
                'importance' => 'medium',
                'hijri_date' => '45 ق.هـ',
                'hijri_year' => -45,
                'gregorian_year' => 579,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 50,
                'translations' => [
                    'ar' => [
                        'title' => 'وفاة عبد المطلب وكفالة أبي طالب',
                        'description' => 'بعد وفاة آمنة كفل النبيَّ ﷺ جدُّه عبد المطلب بن هاشم سيد قريش، فأحبه حبًا شديدًا وقدّمه على أبنائه؛ كان يُفرش له فراش في ظل الكعبة لا يجلس عليه أحد من بنيه إجلالًا له، فإذا جاء محمد ﷺ أجلسه عليه ومسح ظهره وقال: دعوا ابني فإن له لشأنًا. فلما بلغ النبي ﷺ ثماني سنين توفي عبد المطلب، وقد عهد بكفالته إلى عمه أبي طالب شقيق أبيه عبد الله. فقام أبو طالب بكفالته خير قيام، وضمّه إلى عياله وقدّمه عليهم، وظل يحوطه وينصره ويذبّ عنه أكثر من أربعين سنة، حتى بعد البعثة حين اشتد أذى قريش، فكان حصنًا يحميه بجاهه ومنعته، مع أنه بقي على دين قومه. وفي كفالة أبي طالب خرج النبي ﷺ معه إلى الشام، ورعى الغنم لأهل مكة على قراريط، يأكل من كسب يده، قال ﷺ: «ما بعث الله نبيًا إلا رعى الغنم». وهكذا تنقّل ﷺ في كنف الرحمة من جدّه إلى عمّه، يربيه الله على عينه إعدادًا لحمل الرسالة الخاتمة.',
                    ],
                    'en' => [
                        'title' => 'Death of Abdul-Muttalib and Guardianship of Abu Talib',
                        'description' => 'After Aminah\'s death, the Prophet ﷺ was cared for by his grandfather Abdul-Muttalib, chief of Quraysh, who loved him dearly and would seat him on his own couch in the shade of the Kaaba, saying: "Leave my son, for he is destined for greatness." When the Prophet ﷺ was eight, Abdul-Muttalib died, entrusting his care to his uncle Abu Talib, full brother of his father Abdullah. Abu Talib embraced him as his own, favoring him over his children, and protected and defended him for more than forty years — even after the prophethood, when Quraysh\'s hostility intensified. In his uncle\'s care, the young Muhammad ﷺ traveled to Syria and herded sheep for the Meccans, earning his own living.',
                    ],
                ],
            ],
            [
                'slug' => 'rihlat-al-sham-wa-bahira',
                'event_type' => 'personal',
                'era' => 'childhood',
                'phase' => 'early_life',
                'importance' => 'medium',
                'hijri_date' => '41 ق.هـ',
                'hijri_year' => -41,
                'gregorian_year' => 583,
                'location' => 'بُصرى الشام',
                'latitude' => 32.5182000,
                'longitude' => 36.4817000,
                'display_order' => 60,
                'translations' => [
                    'ar' => [
                        'title' => 'رحلة الشام الأولى ولقاء الراهب بَحيرى',
                        'description' => 'لما بلغ النبي ﷺ اثنتي عشرة سنة خرج به عمه أبو طالب في ركب تجاري إلى الشام، فلما نزل الركب بُصرى — مدينة في جنوب الشام — كان بها راهب نصراني يقال له بَحيرى في صومعته، وكان عالمًا بالكتب السالفة. فلما رأى الركبَ رأى غمامةً تظلل غلامًا منهم، فصنع لهم طعامًا ودعاهم جميعًا، وجعل يتأمل النبي ﷺ ويسأله عن أحواله ونومه وهيئته، فيوافق ذلك ما عنده من صفة النبي المنتظر، ثم نظر بين كتفيه فرأى خاتم النبوة على الصفة التي عنده في كتبه. فأقبل على أبي طالب فسأله عنه، فقال: هو ابني، فقال بحيرى: ما هو بابنك، وما ينبغي لهذا الغلام أن يكون أبوه حيًا، فقال: هو ابن أخي، مات أبوه وأمه حبلى به. فقال له بحيرى: صدقت؛ ارجع بابن أخيك إلى بلده واحذر عليه اليهود، فوالله لئن رأوه وعرفوا منه ما عرفتُ ليبغُنّه شرًا؛ فإنه كائن لابن أخيك شأن عظيم. فأسرع به أبو طالب راجعًا إلى مكة بعد أن قضى تجارته، وكانت هذه شهادة من علماء أهل الكتاب بنبوته ﷺ قبل بعثته.',
                    ],
                    'en' => [
                        'title' => 'First Journey to Syria and the Monk Bahira',
                        'description' => 'At twelve years old, the Prophet ﷺ accompanied his uncle Abu Talib with a trade caravan to Syria. At Busra, a Christian monk named Bahira, learned in the ancient scriptures, noticed a cloud shading one boy in the caravan. He invited the travelers to a meal, questioned the boy closely, and found his answers matching the description of the awaited Prophet in his books — then saw the Seal of Prophethood between his shoulders. Bahira urged Abu Talib: "Take your nephew back to his land and guard him against the Jews, for a great destiny awaits him." Abu Talib promptly returned with him to Mecca. It was an early testimony from the People of the Book to his coming prophethood.',
                    ],
                ],
            ],
            [
                'slug' => 'harb-al-fijar',
                'event_type' => 'battle',
                'era' => 'pre_revelation',
                'phase' => 'early_life',
                'importance' => 'low',
                'hijri_date' => '34 ق.هـ تقريبًا',
                'hijri_year' => -34,
                'gregorian_year' => 589,
                'location' => 'سوق عكاظ قرب الطائف',
                'latitude' => 21.7333000,
                'longitude' => 40.4000000,
                'display_order' => 70,
                'translations' => [
                    'ar' => [
                        'title' => 'حرب الفِجار',
                        'description' => 'وقعت حرب الفِجار بين قريش ومعها كنانة من جهة، وبين قبائل قيس عيلان (هوازن ومن معها) من جهة أخرى، وسُميت «الفِجار» لأن القتال وقع في الأشهر الحرم التي كانت العرب تعظّمها وتحرّم فيها القتال، ففجروا فيها جميعًا. وكان سببها أن البرّاض بن قيس الكناني قتل عروة الرحّال العامري وهو يجير قافلة للنعمان بن المنذر إلى سوق عكاظ، فاشتعلت الحرب بين الفريقين أعوامًا تتخللها أيام مشهودة، أعظمها يوم نخلة ويوم شمطة وغيرهما. وقد شهد النبي ﷺ بعض أيامها وهو غلام في نحو الرابعة عشرة أو الخامسة عشرة من عمره — وقيل أكثر — وكان دوره أن يجمع نبال العدو إذا رُميت ويناولها أعمامه، ولم يقاتل بنفسه. وانتهت الحرب بالصلح بين الفريقين على أن يُحصى قتلى الجانبين ويُدفع فضل الديات. وكانت هذه الحرب من أسباب تنادي قبائل قريش بعدها إلى حلف الفضول لنصرة المظلوم؛ إذ أدركوا ما تجرّه الحروب من ويلات. وفي شهوده ﷺ لها وهو غلام تعرّفٌ مبكر على أحوال العرب وفروسيتهم وعصبياتهم التي جاء الإسلام لاحقًا ليهذبها ويوجهها.',
                    ],
                    'en' => [
                        'title' => 'The Fijar War',
                        'description' => 'The Fijar ("Sacrilegious") War broke out between Quraysh allied with Kinanah on one side and the tribes of Qays Aylan on the other. It earned its name because fighting occurred during the sacred months, which the Arabs traditionally held inviolable. The young Muhammad ﷺ, then around fourteen or fifteen, witnessed some of its days, gathering enemy arrows for his uncles without himself fighting. The war ended in a truce with compensation paid for the difference in casualties. Its devastation prompted the clans of Quraysh to form the Pact of al-Fudul soon after, to defend the wronged — a pact the Prophet ﷺ would proudly attend.',
                    ],
                ],
            ],
            [
                'slug' => 'hilf-al-fudul',
                'event_type' => 'treaty',
                'era' => 'pre_revelation',
                'phase' => 'early_life',
                'importance' => 'medium',
                'hijri_date' => '33 ق.هـ تقريبًا',
                'hijri_year' => -33,
                'gregorian_year' => 590,
                'location' => 'مكة المكرمة — دار عبد الله بن جدعان',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 80,
                'translations' => [
                    'ar' => [
                        'title' => 'حلف الفُضول',
                        'description' => 'بعد حرب الفِجار تداعت قبائل من قريش — بنو هاشم وبنو المطلب وأسد بن عبد العزى وزهرة بن كلاب وتيم بن مرة — إلى حلفٍ عقدوه في دار عبد الله بن جُدعان التيمي، تعاهدوا فيه وتعاقدوا على ألا يجدوا بمكة مظلومًا من أهلها أو من غيرهم ممن دخلها إلا قاموا معه حتى تُردَّ عليه مظلمته. وكان سبب الحلف أن رجلًا من زَبيد قدم مكة ببضاعة فاشتراها منه العاص بن وائل السهمي ومطله حقَّه، فاستعدى عليه الزبيديُّ قريشًا فلم ينصره أحد، فصعد على جبل أبي قبيس ونادى بأبيات يستصرخ فيها أهل المروءة، فتحركت النخوة في صدور كرام قريش فعقدوا هذا الحلف، وقاموا فانتزعوا للزبيدي حقه من العاص بن وائل. وقد شهده النبي ﷺ قبل البعثة وهو شاب، وقال بعد أن أكرمه الله بالرسالة: «لقد شهدت في دار عبد الله بن جدعان حلفًا ما أحب أن لي به حُمْر النَّعَم، ولو أُدعى به في الإسلام لأجبت». وفي هذا دلالة عظيمة على أن الإسلام يُقرّ كلَّ تعاونٍ على نصرة المظلوم وإقامة العدل، ولو صدر عن غير المسلمين، وأن النبي ﷺ كان قبل بعثته مشاركًا في كل خير ومكرمة في قومه.',
                    ],
                    'en' => [
                        'title' => 'The Pact of al-Fudul',
                        'description' => 'After the Fijar War, clans of Quraysh — Banu Hashim, Banu al-Muttalib, Asad, Zuhrah and Taym — gathered at the house of Abdullah ibn Jud\'an and swore that no one in Mecca, resident or visitor, would be wronged without them standing by him until his right was restored. The pact was prompted when a merchant from Zabid was cheated by al-As ibn Wa\'il and found no one to help him until he cried out for justice from Mount Abu Qubays. The young Muhammad ﷺ attended the pact and later said: "I witnessed in the house of Ibn Jud\'an a pact more beloved to me than red camels; were I called to it in Islam, I would respond." It stands as a timeless endorsement of alliances for justice.',
                    ],
                ],
            ],
            [
                'slug' => 'zawaj-khadijah',
                'event_type' => 'marriage',
                'era' => 'pre_revelation',
                'phase' => 'early_life',
                'importance' => 'high',
                'is_featured' => true,
                'hijri_date' => '28 ق.هـ',
                'hijri_year' => -28,
                'gregorian_year' => 595,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 90,
                'translations' => [
                    'ar' => [
                        'title' => 'زواجه ﷺ من خديجة بنت خويلد',
                        'description' => 'كانت خديجة بنت خويلد الأسدية القرشية امرأة تاجرة ذات شرف ومال، تستأجر الرجال في مالها وتضاربهم إياه. فلما بلغها عن النبي ﷺ — وكان يومئذ في الخامسة والعشرين — صدقُ حديثه وعظم أمانته وكرم أخلاقه، حتى لُقّب في قومه بـ«الأمين»، عرضت عليه أن يخرج في تجارة لها إلى الشام مع غلامها ميسرة، فخرج ﷺ وربحت تجارتها ربحًا لم تعهد مثله، ورجع ميسرة يحدثها عما رأى من أمانته وكريم شمائله وما لاحظه من آيات. فرغبت فيه خديجة وأرسلت إليه صديقتها نفيسة بنت منية تعرض عليه الزواج منها، فرضي ﷺ وذكر ذلك لأعمامه، فخرج معه حمزة بن عبد المطلب حتى خطبها له من عمها عمرو بن أسد، وأصدقها عشرين بَكرة. وكانت خديجة يومئذ في الأربعين على المشهور، وقد تزوجت قبله مرتين. فكانت له ﷺ نِعم الزوجة؛ ولدت له أولاده كلهم إلا إبراهيم: القاسم وعبد الله وزينب ورقية وأم كلثوم وفاطمة، ولم يتزوج عليها حتى ماتت، وكانت أول من آمن به يوم بُعث، وواسته بنفسها ومالها، حتى قال ﷺ: «إني رُزقت حبَّها».',
                    ],
                    'en' => [
                        'title' => 'Marriage to Khadijah bint Khuwaylid',
                        'description' => 'Khadijah bint Khuwaylid was a noble and wealthy merchant woman of Quraysh. Hearing of the honesty of the twenty-five-year-old Muhammad ﷺ — already known as "al-Amin," the Trustworthy — she engaged him to lead her trade caravan to Syria with her servant Maysarah. The journey returned unprecedented profit, and Maysarah reported the young man\'s remarkable character. Khadijah proposed marriage through her friend Nafisah bint Munyah; the Prophet ﷺ agreed, and his uncle Hamzah sought her hand from her family. She was about forty. She bore all his children except Ibrahim, was the first to believe in his mission, and supported him with her soul and wealth. He never married another while she lived, and said of her: "I was blessed with her love."',
                    ],
                ],
                'participants' => [
                    [
                        'role' => 'prophet',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'النبي محمد ﷺ', 'description' => 'كان في الخامسة والعشرين من عمره، اشتهر بين قومه بالأمانة والصدق.'],
                            'en' => ['name' => 'Prophet Muhammad ﷺ', 'description' => 'Twenty-five years old at the time, renowned among his people as "the Trustworthy".'],
                        ],
                    ],
                    [
                        'role' => 'companion',
                        'type' => 'person',
                        'translations' => [
                            'ar' => ['name' => 'خديجة بنت خويلد', 'description' => 'أم المؤمنين، سيدة قريش وتاجرتها الشريفة، أول من آمن بالنبي ﷺ وأم أولاده.'],
                            'en' => ['name' => 'Khadijah bint Khuwaylid', 'description' => 'Mother of the Believers, noble merchant of Quraysh, first to believe in the Prophet ﷺ and mother of his children.'],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'binaa-al-kaaba-wa-al-tahkim',
                'event_type' => 'personal',
                'era' => 'pre_revelation',
                'phase' => 'early_life',
                'importance' => 'medium',
                'hijri_date' => '18 ق.هـ',
                'hijri_year' => -18,
                'gregorian_year' => 605,
                'location' => 'مكة المكرمة — الكعبة المشرفة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 100,
                'translations' => [
                    'ar' => [
                        'title' => 'إعادة بناء الكعبة وتحكيمه ﷺ في وضع الحجر الأسود',
                        'description' => 'لما بلغ النبي ﷺ خمسًا وثلاثين سنة، أصاب الكعبةَ سيلٌ عظيم صدّع جدرانها، وكانت رَضْمًا فوق القامة بقليل، فأجمعت قريش على هدمها وإعادة بنائها وتسقيفها، وتعاهدوا ألا يُدخلوا في بنائها إلا مالًا طيبًا ليس فيه مهر بغيّ ولا بيع ربا ولا مظلمة أحد، فقصرت بهم النفقة الطيبة عن إتمام البناء على قواعد إبراهيم، فأخرجوا منها الحِجْر. وتقاسمت القبائل أركان البناء، حتى إذا بلغوا موضع الحجر الأسود اختصموا فيمن ينال شرف وضعه، حتى تحالفوا وأوشكوا على الاقتتال، وأعدّ بنو عبد الدار جَفنة مملوءة دمًا تعاقدوا هم وبنو عدي على الموت وأدخلوا أيديهم فيها. ثم ارتضوا أن يحكّموا أول داخل عليهم من باب المسجد، فكان رسول الله ﷺ، فلما رأوه قالوا: هذا الأمين، رضينا، هذا محمد. فطلب رداءً فوضع الحجر فيه بيده، ثم قال: لتأخذ كل قبيلة بناحية من الثوب، فرفعوه جميعًا حتى إذا بلغوا موضعه أخذه بيده الشريفة فوضعه في مكانه. فحقن الله بحكمته ﷺ دماء قريش، وظهر للناس قبل البعثة رجاحة عقله وعظيم منزلته في قومه.',
                    ],
                    'en' => [
                        'title' => 'Rebuilding the Kaaba and the Black Stone Arbitration',
                        'description' => 'When the Prophet ﷺ was thirty-five, a great flood cracked the walls of the Kaaba, and Quraysh resolved to rebuild it using only honestly earned wealth. When their pure funds fell short, they left out the Hijr area. The clans divided the work, but when it came to replacing the Black Stone, fierce rivalry over the honor nearly led to bloodshed. They agreed to accept the judgment of the first man to enter the sanctuary — and it was Muhammad ﷺ. "It is al-Amin! We accept!" they cried. He placed the Stone on a cloak, had each clan lift a corner together, then set it in place with his own hands — a brilliant arbitration that averted war and revealed his wisdom before prophethood.',
                    ],
                ],
            ],
            [
                'slug' => 'al-khalwa-fi-ghar-hira',
                'event_type' => 'personal',
                'era' => 'pre_revelation',
                'phase' => 'early_life',
                'importance' => 'medium',
                'hijri_date' => '16–13 ق.هـ',
                'hijri_year' => -14,
                'gregorian_year' => 609,
                'location' => 'غار حراء — جبل النور بمكة',
                'latitude' => 21.4581000,
                'longitude' => 39.8581000,
                'display_order' => 110,
                'translations' => [
                    'ar' => [
                        'title' => 'التحنّث في غار حراء',
                        'description' => 'لما قارب النبي ﷺ الأربعين من عمره، حُبّب إليه الخلاء والاعتزال؛ إذ كان قد كره ما عليه قومه من عبادة الأوثان وسفك الدماء وأكل أموال الناس بالباطل، ولم يجد فيما حوله ما تطمئن إليه نفسه الكبيرة. فكان يأخذ زاده من سويق وماء ويخرج إلى غار حراء في جبل النور — على نحو ميلين من مكة — فيتحنّث فيه الليالي ذوات العدد: يتعبد ويتفكر في ملكوت السماوات والأرض، على بقايا ملة إبراهيم عليه السلام، ثم يرجع إلى خديجة فيتزود لمثلها. وكان أكثر ما يجاور في شهر رمضان، وكان من شأنه إذا انصرف من جواره أن يطوف بالكعبة قبل أن يدخل بيته. وقد هيأت هذه الخلوات الطويلة قلبه الشريف لتلقي الوحي؛ فصفت روحه، وتعمق تفكره، وانقطع عن شواغل الدنيا، حتى إذا كملت له أربعون سنة — وهي سن كمال العقل — جاءه الحق وهو في غار حراء. وفي هذا سنة ربانية في إعداد الأنبياء: فقد كانت الخلوة مدرسة التأمل التي تخرج فيها ﷺ قبل أن يحمل أعظم رسالة عرفتها البشرية.',
                    ],
                    'en' => [
                        'title' => 'Seclusion in the Cave of Hira',
                        'description' => 'As the Prophet ﷺ neared forty, solitude was made beloved to him. Repelled by his people\'s idolatry and injustice, he would take provisions and retreat to the cave of Hira on the Mountain of Light, about two miles from Mecca, devoting nights on end to worship and contemplation of the heavens and the earth upon the remnants of the way of Ibrahim. He favored the month of Ramadan for these retreats, returning to Khadijah only to resupply, and circling the Kaaba before entering his home. These long retreats purified his soul and prepared his heart for revelation — a divine pattern in the making of prophets — until, at the completion of his fortieth year, the Truth came to him in that very cave.',
                    ],
                ],
            ],
            [
                'slug' => 'al-ruya-al-sadiqa',
                'event_type' => 'revelation',
                'era' => 'pre_revelation',
                'phase' => 'early_life',
                'importance' => 'medium',
                'hijri_date' => '13 ق.هـ',
                'hijri_year' => -13,
                'gregorian_year' => 610,
                'location' => 'مكة المكرمة',
                'latitude' => 21.4225000,
                'longitude' => 39.8262000,
                'display_order' => 120,
                'translations' => [
                    'ar' => [
                        'title' => 'الرؤيا الصادقة — بواكير الوحي',
                        'description' => 'كان أول ما بُدئ به رسول الله ﷺ من الوحي الرؤيا الصادقة في النوم، كما روت أم المؤمنين عائشة رضي الله عنها في الصحيحين: «أولُ ما بُدئ به رسول الله ﷺ من الوحي الرؤيا الصالحة في النوم، فكان لا يرى رؤيا إلا جاءت مثل فَلَق الصبح». فمكث على ذلك ستة أشهر، يرى الرؤيا في منامه فتقع في يقظته كما رآها سواء، لا تخطئ ولا تتخلف، حتى ألِفت نفسُه الشريفة صدق ما يَرِد عليه من عالم الغيب. وذكر أهل السير أن من إرهاصات تلك الفترة أيضًا أنه ﷺ كان يسمع سلام الحجر والشجر عليه، قال ﷺ: «إني لأعرف حجرًا بمكة كان يسلّم عليّ قبل أن أُبعث، إني لأعرفه الآن». وقد كانت هذه الرؤى والإرهاصات تمهيدًا ربانيًا لطيفًا، يُؤنس به قلبه ويُدرَّب به على تلقي الوحي شيئًا فشيئًا؛ إذ لو فاجأه الملَك بالوحي اليقظ دون تهيئة لعَظُم الأمر عليه. ولهذا عدّ العلماء الرؤيا الصادقة جزءًا من النبوة، كما في الحديث: «الرؤيا الصالحة من الرجل الصالح جزء من ستة وأربعين جزءًا من النبوة» — وهي نسبة الأشهر الستة إلى مدة الوحي ثلاث وعشرين سنة.',
                    ],
                    'en' => [
                        'title' => 'The True Dreams — Dawn of Revelation',
                        'description' => 'The first form of revelation given to the Messenger of God ﷺ was the true dream. As Aisha narrated in the two Sahihs: "He never saw a dream but that it came true like the break of dawn." This continued for six months: whatever he saw in sleep unfolded in waking life exactly as seen. The biographers also record that stones and trees would greet him; he ﷺ said: "I know a stone in Mecca that used to greet me before my mission." These dreams were a gentle divine preparation, accustoming his heart gradually to receiving revelation. Hence the hadith counts the true dream as one of forty-six parts of prophethood — the ratio of those six months to the twenty-three years of revelation.',
                    ],
                ],
            ],
        ];
    }
}
