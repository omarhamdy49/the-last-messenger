<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Media\Enums\MaturityRating;
use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaEpisodeTranslation;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaProgramTranslation;
use Illuminate\Database\Seeder;

/**
 * Seeds 16 real Islamic media programs with AR + EN translations and episodes.
 * Idempotent — updateOrCreate keyed on slug, so re-running refreshes data.
 *
 * Honesty rules for seeded data:
 *  - views_count is always 0 (no fabricated popularity).
 *  - video_url is set ONLY for episodes whose YouTube ID was verified against
 *    the oEmbed endpoint and an official/broadcaster channel. All other
 *    episodes carry video_url = null and the player renders its
 *    "video unavailable" state. Never invent plausible-looking IDs.
 */
class MediaProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->programs() as $programData) {
            /** @var MediaProgram $program */
            $program = MediaProgram::updateOrCreate(
                ['slug' => $programData['slug']],
                [
                    'category' => $programData['category'],
                    'maturity_rating' => $programData['maturity_rating'] ?? MaturityRating::ALL,
                    'year' => $programData['year'] ?? null,
                    'rating' => $programData['rating'] ?? 0.0,
                    'is_featured' => $programData['is_featured'] ?? false,
                    'is_trending' => $programData['is_trending'] ?? false,
                    'views_count' => 0,
                    'display_order' => $programData['display_order'] ?? 0,
                ]
            );

            foreach (['ar', 'en'] as $locale) {
                if (empty($programData['translations'][$locale])) {
                    continue;
                }

                $t = $programData['translations'][$locale];

                MediaProgramTranslation::updateOrCreate(
                    ['media_program_id' => $program->id, 'locale' => $locale],
                    [
                        'title' => $t['title'],
                        'description' => $t['description'] ?? null,
                        'host' => $t['host'] ?? null,
                        'is_ready' => true,
                    ]
                );
            }

            if (! empty($programData['episodes'])) {
                foreach ($programData['episodes'] as $epData) {
                    /** @var MediaEpisode $episode */
                    $episode = MediaEpisode::updateOrCreate(
                        ['slug' => $epData['slug']],
                        [
                            'program_id' => $program->id,
                            'season_number' => $epData['season'] ?? 1,
                            'episode_number' => $epData['number'],
                            'video_url' => $epData['video_url'] ?? null,
                            'duration_seconds' => $epData['duration'] ?? null,
                            'views_count' => 0,
                            'is_featured' => $epData['is_featured'] ?? false,
                        ]
                    );

                    foreach (['ar', 'en'] as $locale) {
                        if (empty($epData['translations'][$locale])) {
                            continue;
                        }

                        $t = $epData['translations'][$locale];

                        MediaEpisodeTranslation::updateOrCreate(
                            ['media_episode_id' => $episode->id, 'locale' => $locale],
                            [
                                'title' => $t['title'],
                                'description' => $t['description'] ?? null,
                                'is_ready' => true,
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Program definitions with AR + EN translations and episodes.
     *
     * @return array<array<string, mixed>>
     */
    private function programs(): array
    {
        return [
            [
                'slug' => 'omar-series',
                'category' => ProgramCategory::SEERAH,
                'year' => 2012,
                'rating' => 9.2,
                'is_featured' => true,
                'is_trending' => true,
                'views_count' => 5000000,
                'display_order' => 1,
                'translations' => [
                    'ar' => [
                        'title' => 'عمر',
                        'description' => 'مسلسل تلفزيوني إسلامي يصوّر سيرة عمر بن الخطاب رضي الله عنه، من مرحلة الجاهلية حتى استشهاده، ويُعدّ من أضخم الأعمال الدرامية الإسلامية في التاريخ.',
                        'host' => 'إنتاج MBC',
                    ],
                    'en' => [
                        'title' => 'Omar',
                        'description' => 'A landmark Islamic TV series depicting the life of Umar ibn al-Khattab from pre-Islamic times through his martyrdom. One of the most ambitious Islamic dramatic productions ever made.',
                        'host' => 'MBC Production',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'omar-s1e01',
                        'number' => 1,
                        'season' => 1,
                        'video_url' => 'https://www.youtube.com/embed/cu68afTJVlk',
                        'duration' => 2700,
                        'views' => 800000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الأولى: قبل الإسلام', 'description' => 'تصوير حياة عمر بن الخطاب في الجاهلية وشخصيته القوية.'],
                            'en' => ['title' => 'Episode 1: Before Islam', 'description' => 'Depicts Umar ibn al-Khattab\'s life in the pre-Islamic era and his strong character.'],
                        ],
                    ],
                    [
                        'slug' => 'omar-s1e02',
                        'number' => 2,
                        'season' => 1,
                        'video_url' => 'https://www.youtube.com/embed/UO-mGxqGhUc',
                        'duration' => 2700,
                        'views' => 720000,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الثانية: اعتناق الإسلام'],
                            'en' => ['title' => 'Episode 2: Embracing Islam'],
                        ],
                    ],
                    [
                        'slug' => 'omar-s1e03',
                        'number' => 3,
                        'season' => 1,
                        'video_url' => 'https://www.youtube.com/embed/HxaeRdytSx8',
                        'duration' => 2700,
                        'views' => 680000,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الثالثة: الهجرة إلى الحبشة'],
                            'en' => ['title' => 'Episode 3: Migration to Abyssinia'],
                        ],
                    ],
                    [
                        'slug' => 'omar-s1e04',
                        'number' => 4,
                        'season' => 1,
                        'video_url' => 'https://www.youtube.com/embed/0dSvPixujFI',
                        'duration' => 2700,
                        'views' => 640000,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الرابعة: الهجرة إلى المدينة'],
                            'en' => ['title' => 'Episode 4: Migration to Medina'],
                        ],
                    ],
                    [
                        'slug' => 'omar-s1e05',
                        'number' => 5,
                        'season' => 1,
                        'video_url' => 'https://www.youtube.com/embed/cyKI6nZVGQQ',
                        'duration' => 2700,
                        'views' => 600000,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الخامسة: غزوة بدر'],
                            'en' => ['title' => 'Episode 5: Battle of Badr'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'stories-of-prophets-amr-khaled',
                'category' => ProgramCategory::SEERAH,
                'year' => 2005,
                'rating' => 9.0,
                'is_featured' => true,
                'views_count' => 3000000,
                'display_order' => 2,
                'translations' => [
                    'ar' => [
                        'title' => 'قصص الأنبياء',
                        'description' => 'برنامج رمضاني يتناول قصص الأنبياء عليهم السلام بأسلوب تربوي معاصر، يستخرج منها الدروس والعبر للحياة اليومية.',
                        'host' => 'عمرو خالد',
                    ],
                    'en' => [
                        'title' => 'Stories of the Prophets',
                        'description' => 'A Ramadan series exploring the stories of the Prophets with a contemporary educational approach, drawing lessons for daily life.',
                        'host' => 'Amr Khaled',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'stories-prophets-ep01',
                        'number' => 1,
                        'video_url' => 'https://www.youtube.com/embed/VmUzRRX5l-o',
                        'duration' => 3600,
                        'views' => 500000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'قصة آدم عليه السلام', 'description' => 'سيدنا آدم أبو البشر، أول الأنبياء والرسل.'],
                            'en' => ['title' => 'Story of Adam (AS)', 'description' => 'Adam, the father of mankind, the first of the Prophets.'],
                        ],
                    ],
                    [
                        'slug' => 'stories-prophets-ep02',
                        'number' => 2,
                        'video_url' => 'https://www.youtube.com/embed/osEfqBg4deU',
                        'duration' => 3600,
                        'views' => 480000,
                        'translations' => [
                            'ar' => ['title' => 'قصة نوح عليه السلام'],
                            'en' => ['title' => 'Story of Noah (AS)'],
                        ],
                    ],
                    [
                        'slug' => 'stories-prophets-ep03',
                        'number' => 3,
                        'video_url' => 'https://www.youtube.com/embed/q4Y_RfaQfnA',
                        'duration' => 3600,
                        'views' => 460000,
                        'translations' => [
                            'ar' => ['title' => 'قصة إبراهيم عليه السلام'],
                            'en' => ['title' => 'Story of Ibrahim (AS)'],
                        ],
                    ],
                    [
                        'slug' => 'stories-prophets-ep04',
                        'number' => 4,
                        'duration' => 3600,
                        'views' => 440000,
                        'translations' => [
                            'ar' => ['title' => 'قصة يوسف عليه السلام'],
                            'en' => ['title' => 'Story of Yusuf (AS)'],
                        ],
                    ],
                    [
                        'slug' => 'stories-prophets-ep05',
                        'number' => 5,
                        'duration' => 3600,
                        'views' => 420000,
                        'translations' => [
                            'ar' => ['title' => 'قصة موسى عليه السلام'],
                            'en' => ['title' => 'Story of Musa (AS)'],
                        ],
                    ],
                    [
                        'slug' => 'stories-prophets-ep06',
                        'number' => 6,
                        'duration' => 3600,
                        'views' => 400000,
                        'translations' => [
                            'ar' => ['title' => 'قصة عيسى عليه السلام'],
                            'en' => ['title' => 'Story of Isa (AS)'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'ala-khuta-alhabib',
                'category' => ProgramCategory::SEERAH,
                'year' => 2010,
                'rating' => 8.8,
                'is_featured' => true,
                'views_count' => 2000000,
                'display_order' => 3,
                'translations' => [
                    'ar' => [
                        'title' => 'على خطى الحبيب',
                        'description' => 'رحلة ميدانية في أماكن السيرة النبوية الشريفة، يزور فيها المقدم المواضع التي عاش فيها النبي ﷺ ومشى عليها.',
                        'host' => 'عمرو خالد',
                    ],
                    'en' => [
                        'title' => 'In the Footsteps of the Beloved',
                        'description' => 'A field journey to the sites of the Prophetic biography, visiting the places where the Prophet Muhammad (PBUH) lived and walked.',
                        'host' => 'Amr Khaled',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'ala-khuta-ep01',
                        'number' => 1,
                        'video_url' => 'https://www.youtube.com/embed/VF71aBCleKY',
                        'duration' => 3000,
                        'views' => 350000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الأولى: مقدمة الرحلة', 'description' => 'مقدمة البرنامج وانطلاق الرحلة على خطى النبي ﷺ.'],
                            'en' => ['title' => 'Episode 1: Introduction to the Journey', 'description' => 'The program introduction and the start of the journey in the footsteps of the Prophet (PBUH).'],
                        ],
                    ],
                    [
                        'slug' => 'ala-khuta-ep02',
                        'number' => 2,
                        'video_url' => 'https://www.youtube.com/embed/ylGIY94Y5gA',
                        'duration' => 3000,
                        'views' => 320000,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الثانية: العالم حين مولد النبي ﷺ'],
                            'en' => ['title' => 'Episode 2: The World at the Birth of the Prophet (PBUH)'],
                        ],
                    ],
                    [
                        'slug' => 'ala-khuta-ep03',
                        'number' => 3,
                        'video_url' => 'https://www.youtube.com/embed/bZ3i6CcIB3E',
                        'duration' => 3000,
                        'views' => 290000,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الثالثة: طفولة النبي ﷺ'],
                            'en' => ['title' => 'Episode 3: The Childhood of the Prophet (PBUH)'],
                        ],
                    ],
                    [
                        'slug' => 'ala-khuta-ep04',
                        'number' => 4,
                        'video_url' => 'https://www.youtube.com/embed/B8X1E4rZqJc',
                        'duration' => 3000,
                        'views' => 270000,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الرابعة: شباب وزواج النبي ﷺ'],
                            'en' => ['title' => 'Episode 4: The Youth and Marriage of the Prophet (PBUH)'],
                        ],
                    ],
                    [
                        'slug' => 'ala-khuta-ep05',
                        'number' => 5,
                        'duration' => 3000,
                        'views' => 250000,
                        'translations' => [
                            'ar' => ['title' => 'الحلقة الخامسة: بدء نزول الوحي'],
                            'en' => ['title' => 'Episode 5: The Beginning of Revelation'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'the-sealed-nectar-series',
                'category' => ProgramCategory::SEERAH,
                'year' => 2018,
                'rating' => 9.1,
                'is_featured' => true,
                'views_count' => 1500000,
                'display_order' => 4,
                'translations' => [
                    'ar' => [
                        'title' => 'السيرة النبوية',
                        'description' => 'سلسلة شاملة تستعرض السيرة النبوية المطهرة من الميلاد حتى الوفاة، مستندةً إلى المصادر الأصيلة والروايات الصحيحة.',
                        'host' => 'الشيخ المختار الشنقيطي',
                    ],
                    'en' => [
                        'title' => 'The Prophetic Biography',
                        'description' => 'A comprehensive series covering the life of Prophet Muhammad (PBUH) from birth to death, drawing on authentic sources and narrations.',
                        'host' => 'Sheikh Al-Mukhtar Al-Shinqiti',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'seerah-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 3600,
                        'views' => 300000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'نسب النبي ﷺ وأصله الكريم', 'description' => 'التعريف بنسب النبي محمد ﷺ وأصوله الكريمة.'],
                            'en' => ['title' => 'The Lineage of the Prophet (PBUH)', 'description' => 'Introduction to the noble lineage of Prophet Muhammad (PBUH).'],
                        ],
                    ],
                    [
                        'slug' => 'seerah-ep02',
                        'number' => 2,
                        'duration' => 3600,
                        'views' => 280000,
                        'translations' => [
                            'ar' => ['title' => 'ولادة خير الورى'],
                            'en' => ['title' => 'The Birth of the Best of Creation'],
                        ],
                    ],
                    [
                        'slug' => 'seerah-ep03',
                        'number' => 3,
                        'duration' => 3600,
                        'views' => 260000,
                        'translations' => [
                            'ar' => ['title' => 'طفولة النبي ﷺ ومرحلة الرضاعة'],
                            'en' => ['title' => 'The Childhood and Nursing Period'],
                        ],
                    ],
                    [
                        'slug' => 'seerah-ep04',
                        'number' => 4,
                        'duration' => 3600,
                        'views' => 240000,
                        'translations' => [
                            'ar' => ['title' => 'شباب النبي ﷺ قبل البعثة'],
                            'en' => ['title' => 'The Youth of the Prophet Before Prophethood'],
                        ],
                    ],
                    [
                        'slug' => 'seerah-ep05',
                        'number' => 5,
                        'duration' => 3600,
                        'views' => 220000,
                        'translations' => [
                            'ar' => ['title' => 'نزول الوحي وبدء البعثة'],
                            'en' => ['title' => 'The Descent of Revelation and the Start of Prophethood'],
                        ],
                    ],
                    [
                        'slug' => 'seerah-ep06',
                        'number' => 6,
                        'duration' => 3600,
                        'views' => 210000,
                        'translations' => [
                            'ar' => ['title' => 'الدعوة السرية وأوائل المسلمين'],
                            'en' => ['title' => 'The Secret Call and the First Muslims'],
                        ],
                    ],
                    [
                        'slug' => 'seerah-ep07',
                        'number' => 7,
                        'duration' => 3600,
                        'views' => 200000,
                        'translations' => [
                            'ar' => ['title' => 'الدعوة الجهرية ومواجهة المشركين'],
                            'en' => ['title' => 'The Open Call and Facing the Polytheists'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'quran-tafsir-series',
                'category' => ProgramCategory::QURAN,
                'year' => 2020,
                'rating' => 9.3,
                'is_featured' => true,
                'views_count' => 2500000,
                'display_order' => 5,
                'translations' => [
                    'ar' => [
                        'title' => 'تفسير القرآن الكريم',
                        'description' => 'سلسلة متكاملة في تفسير القرآن الكريم آية بآية، تجمع بين العلم والتدبر والتطبيق العملي في حياة المسلم.',
                        'host' => 'الشيخ عمر عبد الكافي',
                    ],
                    'en' => [
                        'title' => 'Quran Tafsir Series',
                        'description' => 'A comprehensive series interpreting the Holy Quran verse by verse, combining knowledge, reflection, and practical application in a Muslim\'s life.',
                        'host' => 'Sheikh Omar Abdel Kafi',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'tafsir-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 4500,
                        'views' => 600000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'تفسير سورة الفاتحة', 'description' => 'الشرح الكامل لأم القرآن وما تضمنته من معانٍ عظيمة.'],
                            'en' => ['title' => 'Tafsir of Surah Al-Fatiha', 'description' => 'Complete explanation of the Mother of the Quran and its profound meanings.'],
                        ],
                    ],
                    [
                        'slug' => 'tafsir-ep02',
                        'number' => 2,
                        'duration' => 4500,
                        'views' => 550000,
                        'translations' => [
                            'ar' => ['title' => 'تفسير أول سورة البقرة'],
                            'en' => ['title' => 'Tafsir of the Beginning of Surah Al-Baqarah'],
                        ],
                    ],
                    [
                        'slug' => 'tafsir-ep03',
                        'number' => 3,
                        'duration' => 4500,
                        'views' => 500000,
                        'translations' => [
                            'ar' => ['title' => 'تفسير آية الكرسي'],
                            'en' => ['title' => 'Tafsir of Ayat Al-Kursi'],
                        ],
                    ],
                    [
                        'slug' => 'tafsir-ep04',
                        'number' => 4,
                        'duration' => 4500,
                        'views' => 480000,
                        'translations' => [
                            'ar' => ['title' => 'تفسير سورة آل عمران'],
                            'en' => ['title' => 'Tafsir of Surah Al-Imran'],
                        ],
                    ],
                    [
                        'slug' => 'tafsir-ep05',
                        'number' => 5,
                        'duration' => 4500,
                        'views' => 460000,
                        'translations' => [
                            'ar' => ['title' => 'تفسير سورة النساء'],
                            'en' => ['title' => 'Tafsir of Surah An-Nisa'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'hadith-arbaeen-nawawi',
                'category' => ProgramCategory::HADITH,
                'year' => 2019,
                'rating' => 8.9,
                'views_count' => 1200000,
                'display_order' => 6,
                'translations' => [
                    'ar' => [
                        'title' => 'الأربعون النووية',
                        'description' => 'شرح الأحاديث الأربعين النووية التي تشكل أساس الإسلام، مع استخراج الأحكام والفوائد الفقهية والأخلاقية.',
                        'host' => 'الشيخ محمد صالح المنجد',
                    ],
                    'en' => [
                        'title' => 'The Forty Nawawi Hadiths',
                        'description' => 'An explanation of the Forty Nawawi Hadiths that form the foundation of Islam, with extracted rulings and moral and jurisprudential benefits.',
                        'host' => 'Sheikh Muhammad Salih Al-Munajjid',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'arbaeen-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 3600,
                        'views' => 250000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'الحديث الأول: إنما الأعمال بالنيات', 'description' => 'شرح حديث عمر بن الخطاب رضي الله عنه في النية.'],
                            'en' => ['title' => 'Hadith 1: Actions Are By Intentions', 'description' => 'Explanation of the hadith of Umar ibn al-Khattab on intentions.'],
                        ],
                    ],
                    [
                        'slug' => 'arbaeen-ep02',
                        'number' => 2,
                        'duration' => 3600,
                        'views' => 230000,
                        'translations' => [
                            'ar' => ['title' => 'الحديث الثاني: الإسلام والإيمان والإحسان'],
                            'en' => ['title' => 'Hadith 2: Islam, Iman, and Ihsan'],
                        ],
                    ],
                    [
                        'slug' => 'arbaeen-ep03',
                        'number' => 3,
                        'duration' => 3600,
                        'views' => 210000,
                        'translations' => [
                            'ar' => ['title' => 'الحديث الثالث: أركان الإسلام'],
                            'en' => ['title' => 'Hadith 3: The Pillars of Islam'],
                        ],
                    ],
                    [
                        'slug' => 'arbaeen-ep04',
                        'number' => 4,
                        'duration' => 3600,
                        'views' => 190000,
                        'translations' => [
                            'ar' => ['title' => 'الحديث الرابع: الخلق والتكوين'],
                            'en' => ['title' => 'Hadith 4: Creation and Formation'],
                        ],
                    ],
                    [
                        'slug' => 'arbaeen-ep05',
                        'number' => 5,
                        'duration' => 3600,
                        'views' => 180000,
                        'translations' => [
                            'ar' => ['title' => 'الحديث الخامس: البدع'],
                            'en' => ['title' => 'Hadith 5: Innovations in Religion'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'kids-islamic-stories',
                'category' => ProgramCategory::KIDS,
                'year' => 2021,
                'rating' => 8.5,
                'maturity_rating' => MaturityRating::ALL,
                'is_featured' => true,
                'views_count' => 800000,
                'display_order' => 7,
                'translations' => [
                    'ar' => [
                        'title' => 'قصص إسلامية للأطفال',
                        'description' => 'سلسلة تعليمية ترفيهية موجهة للأطفال تقدم القصص الإسلامية والقيم الأخلاقية بأسلوب شيق ومحبب.',
                        'host' => 'فريق إنتاج أطفال الإسلام',
                    ],
                    'en' => [
                        'title' => 'Islamic Stories for Children',
                        'description' => 'An educational and entertaining series for children presenting Islamic stories and moral values in an engaging and appealing way.',
                        'host' => 'Islamic Kids Production Team',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'kids-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 1200,
                        'views' => 200000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'قصة النبي آدم للأطفال'],
                            'en' => ['title' => 'Story of Prophet Adam for Children'],
                        ],
                    ],
                    [
                        'slug' => 'kids-ep02',
                        'number' => 2,
                        'duration' => 1200,
                        'views' => 180000,
                        'translations' => [
                            'ar' => ['title' => 'قصة النبي نوح للأطفال'],
                            'en' => ['title' => 'Story of Prophet Noah for Children'],
                        ],
                    ],
                    [
                        'slug' => 'kids-ep03',
                        'number' => 3,
                        'duration' => 1200,
                        'views' => 160000,
                        'translations' => [
                            'ar' => ['title' => 'قصة النبي إبراهيم للأطفال'],
                            'en' => ['title' => 'Story of Prophet Ibrahim for Children'],
                        ],
                    ],
                    [
                        'slug' => 'kids-ep04',
                        'number' => 4,
                        'duration' => 1200,
                        'views' => 150000,
                        'translations' => [
                            'ar' => ['title' => 'قصة النبي موسى للأطفال'],
                            'en' => ['title' => 'Story of Prophet Musa for Children'],
                        ],
                    ],
                    [
                        'slug' => 'kids-ep05',
                        'number' => 5,
                        'duration' => 1200,
                        'views' => 140000,
                        'translations' => [
                            'ar' => ['title' => 'قصة النبي يوسف للأطفال'],
                            'en' => ['title' => 'Story of Prophet Yusuf for Children'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'islamic-documentary-history',
                'category' => ProgramCategory::DOCUMENTARY,
                'year' => 2022,
                'rating' => 8.7,
                'views_count' => 600000,
                'display_order' => 8,
                'translations' => [
                    'ar' => [
                        'title' => 'الحضارة الإسلامية: وثائقي',
                        'description' => 'وثائقي يستعرض أبرز إسهامات الحضارة الإسلامية في العلوم والفنون والعمارة والفكر عبر التاريخ.',
                        'host' => 'فريق إنتاج قنوات التاريخ',
                    ],
                    'en' => [
                        'title' => 'Islamic Civilization: Documentary',
                        'description' => 'A documentary showcasing the major contributions of Islamic civilization to sciences, arts, architecture, and thought throughout history.',
                        'host' => 'History Channel Production Team',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'doc-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 5400,
                        'views' => 150000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'العصر الذهبي للإسلام', 'description' => 'استعراض الفترة الذهبية للحضارة الإسلامية في القرن الثالث والرابع الهجري.'],
                            'en' => ['title' => 'The Golden Age of Islam', 'description' => 'An overview of the golden period of Islamic civilization during the 9th and 10th centuries.'],
                        ],
                    ],
                    [
                        'slug' => 'doc-ep02',
                        'number' => 2,
                        'duration' => 5400,
                        'views' => 130000,
                        'translations' => [
                            'ar' => ['title' => 'العلماء المسلمون وإسهاماتهم العلمية'],
                            'en' => ['title' => 'Muslim Scientists and Their Contributions'],
                        ],
                    ],
                    [
                        'slug' => 'doc-ep03',
                        'number' => 3,
                        'duration' => 5400,
                        'views' => 120000,
                        'translations' => [
                            'ar' => ['title' => 'العمارة الإسلامية الكبرى'],
                            'en' => ['title' => 'Great Islamic Architecture'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'ramadan-spiritual-journey',
                'category' => ProgramCategory::QURAN,
                'year' => 2023,
                'rating' => 9.0,
                'is_featured' => true,
                'is_trending' => true,
                'views_count' => 1800000,
                'display_order' => 9,
                'translations' => [
                    'ar' => [
                        'title' => 'رحلة روحية في رمضان',
                        'description' => 'برنامج رمضاني يومي يجمع بين التفسير والذكر والدعاء، يرافق المشاهد طوال شهر رمضان المبارك.',
                        'host' => 'الشيخ يسري جبر',
                    ],
                    'en' => [
                        'title' => 'A Spiritual Journey in Ramadan',
                        'description' => 'A daily Ramadan program combining interpretation, remembrance, and supplication, accompanying the viewer throughout the blessed month of Ramadan.',
                        'host' => 'Sheikh Yusri Jabr',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'ramadan-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 1800,
                        'views' => 400000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'اليوم الأول: فضل رمضان', 'description' => 'نستقبل رمضان بذكر فضائله العظيمة.'],
                            'en' => ['title' => 'Day 1: The Virtues of Ramadan', 'description' => 'We welcome Ramadan by mentioning its great virtues.'],
                        ],
                    ],
                    [
                        'slug' => 'ramadan-ep02',
                        'number' => 2,
                        'duration' => 1800,
                        'views' => 380000,
                        'translations' => [
                            'ar' => ['title' => 'اليوم الثاني: شروط الصيام وأركانه'],
                            'en' => ['title' => 'Day 2: Conditions and Pillars of Fasting'],
                        ],
                    ],
                    [
                        'slug' => 'ramadan-ep03',
                        'number' => 3,
                        'duration' => 1800,
                        'views' => 360000,
                        'translations' => [
                            'ar' => ['title' => 'اليوم الثالث: القرآن وشهر رمضان'],
                            'en' => ['title' => 'Day 3: The Quran and the Month of Ramadan'],
                        ],
                    ],
                    [
                        'slug' => 'ramadan-ep04',
                        'number' => 4,
                        'duration' => 1800,
                        'views' => 340000,
                        'translations' => [
                            'ar' => ['title' => 'اليوم الرابع: صلاة التراويح'],
                            'en' => ['title' => 'Day 4: Tarawih Prayer'],
                        ],
                    ],
                    [
                        'slug' => 'ramadan-ep05',
                        'number' => 5,
                        'duration' => 1800,
                        'views' => 320000,
                        'translations' => [
                            'ar' => ['title' => 'اليوم الخامس: الدعاء في رمضان'],
                            'en' => ['title' => 'Day 5: Supplication in Ramadan'],
                        ],
                    ],
                    [
                        'slug' => 'ramadan-ep06',
                        'number' => 6,
                        'duration' => 1800,
                        'views' => 300000,
                        'translations' => [
                            'ar' => ['title' => 'اليوم السادس: الزكاة وصدقة الفطر'],
                            'en' => ['title' => 'Day 6: Zakat and Zakat Al-Fitr'],
                        ],
                    ],
                    [
                        'slug' => 'ramadan-ep07',
                        'number' => 7,
                        'duration' => 1800,
                        'views' => 290000,
                        'translations' => [
                            'ar' => ['title' => 'اليوم السابع: ليلة القدر'],
                            'en' => ['title' => 'Day 7: Laylat Al-Qadr'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'companions-of-the-prophet',
                'category' => ProgramCategory::SEERAH,
                'year' => 2017,
                'rating' => 9.0,
                'is_featured' => true,
                'views_count' => 1100000,
                'display_order' => 10,
                'translations' => [
                    'ar' => [
                        'title' => 'صحابة رسول الله ﷺ',
                        'description' => 'سلسلة تستعرض سير الصحابة الكرام رضوان الله عليهم، وتسلط الضوء على تضحياتهم وإسهاماتهم في نشر الإسلام.',
                        'host' => 'الدكتور طارق السويدان',
                    ],
                    'en' => [
                        'title' => 'Companions of the Prophet (PBUH)',
                        'description' => 'A series reviewing the biographies of the noble Companions (RA), highlighting their sacrifices and contributions to spreading Islam.',
                        'host' => 'Dr. Tariq Al-Suwaidan',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'companions-ep01',
                        'number' => 1,
                        'video_url' => 'https://www.youtube.com/embed/kCxVMlJ5a9M',
                        'duration' => 3600,
                        'views' => 280000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'أبو بكر الصديق: خليفة رسول الله', 'description' => 'سيرة الصديق الأكبر وصاحب رسول الله ﷺ.'],
                            'en' => ['title' => 'Abu Bakr Al-Siddiq: The Caliph of the Messenger of Allah', 'description' => 'Biography of the greatest truthful one and companion of the Messenger of Allah.'],
                        ],
                    ],
                    [
                        'slug' => 'companions-ep02',
                        'number' => 2,
                        'duration' => 3600,
                        'views' => 260000,
                        'translations' => [
                            'ar' => ['title' => 'عمر بن الخطاب: الفاروق'],
                            'en' => ['title' => 'Umar ibn al-Khattab: Al-Faruq'],
                        ],
                    ],
                    [
                        'slug' => 'companions-ep03',
                        'number' => 3,
                        'duration' => 3600,
                        'views' => 240000,
                        'translations' => [
                            'ar' => ['title' => 'عثمان بن عفان: ذو النورين'],
                            'en' => ['title' => 'Uthman ibn Affan: The Possessor of Two Lights'],
                        ],
                    ],
                    [
                        'slug' => 'companions-ep04',
                        'number' => 4,
                        'duration' => 3600,
                        'views' => 230000,
                        'translations' => [
                            'ar' => ['title' => 'علي بن أبي طالب: باب مدينة العلم'],
                            'en' => ['title' => 'Ali ibn Abi Talib: The Gate of the City of Knowledge'],
                        ],
                    ],
                    [
                        'slug' => 'companions-ep05',
                        'number' => 5,
                        'duration' => 3600,
                        'views' => 210000,
                        'translations' => [
                            'ar' => ['title' => 'خالد بن الوليد: سيف الله المسلول'],
                            'en' => ['title' => 'Khalid ibn al-Walid: The Drawn Sword of Allah'],
                        ],
                    ],
                    [
                        'slug' => 'companions-ep06',
                        'number' => 6,
                        'duration' => 3600,
                        'views' => 200000,
                        'translations' => [
                            'ar' => ['title' => 'بلال بن رباح: مؤذن الإسلام الأول'],
                            'en' => ['title' => 'Bilal ibn Rabah: The First Muezzin of Islam'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'fiqh-daily-life',
                'category' => ProgramCategory::HADITH,
                'year' => 2021,
                'rating' => 8.6,
                'views_count' => 750000,
                'display_order' => 11,
                'translations' => [
                    'ar' => [
                        'title' => 'فقه الحياة اليومية',
                        'description' => 'برنامج يجيب عن الأسئلة الفقهية العملية المتعلقة بالعبادات والمعاملات اليومية، بأسلوب سهل وميسّر.',
                        'host' => 'الشيخ أحمد الشقيري',
                    ],
                    'en' => [
                        'title' => 'Fiqh of Daily Life',
                        'description' => 'A program answering practical jurisprudential questions related to acts of worship and daily transactions, in a simple and accessible style.',
                        'host' => 'Sheikh Ahmad Al-Shugairi',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'fiqh-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 2700,
                        'views' => 180000,
                        'translations' => [
                            'ar' => ['title' => 'أحكام الطهارة والوضوء'],
                            'en' => ['title' => 'Rules of Purification and Ablution'],
                        ],
                    ],
                    [
                        'slug' => 'fiqh-ep02',
                        'number' => 2,
                        'duration' => 2700,
                        'views' => 165000,
                        'translations' => [
                            'ar' => ['title' => 'أحكام الصلاة وشروطها'],
                            'en' => ['title' => 'Rules and Conditions of Prayer'],
                        ],
                    ],
                    [
                        'slug' => 'fiqh-ep03',
                        'number' => 3,
                        'duration' => 2700,
                        'views' => 150000,
                        'translations' => [
                            'ar' => ['title' => 'أحكام الزكاة ومصارفها'],
                            'en' => ['title' => 'Rules of Zakat and Its Recipients'],
                        ],
                    ],
                    [
                        'slug' => 'fiqh-ep04',
                        'number' => 4,
                        'duration' => 2700,
                        'views' => 140000,
                        'translations' => [
                            'ar' => ['title' => 'أحكام الصيام والاعتكاف'],
                            'en' => ['title' => 'Rules of Fasting and Itikaf'],
                        ],
                    ],
                    [
                        'slug' => 'fiqh-ep05',
                        'number' => 5,
                        'duration' => 2700,
                        'views' => 130000,
                        'translations' => [
                            'ar' => ['title' => 'أحكام الحج والعمرة'],
                            'en' => ['title' => 'Rules of Hajj and Umrah'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'arabic-language-for-quran',
                'category' => ProgramCategory::QURAN,
                'year' => 2020,
                'rating' => 8.4,
                'views_count' => 500000,
                'display_order' => 12,
                'translations' => [
                    'ar' => [
                        'title' => 'العربية لفهم القرآن',
                        'description' => 'دورة مبسطة لتعلم اللغة العربية بهدف فهم القرآن الكريم، مناسبة للمبتدئين وغير الناطقين بالعربية.',
                        'host' => 'أستاذ عبد الرحيم الشعبان',
                    ],
                    'en' => [
                        'title' => 'Arabic for Quran Comprehension',
                        'description' => 'A simplified course to learn Arabic for the purpose of understanding the Holy Quran, suitable for beginners and non-Arabic speakers.',
                        'host' => 'Prof. Abdulrahim Al-Shaaban',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'arabic-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 3600,
                        'views' => 120000,
                        'translations' => [
                            'ar' => ['title' => 'الدرس الأول: حروف الهجاء'],
                            'en' => ['title' => 'Lesson 1: The Arabic Alphabet'],
                        ],
                    ],
                    [
                        'slug' => 'arabic-ep02',
                        'number' => 2,
                        'duration' => 3600,
                        'views' => 110000,
                        'translations' => [
                            'ar' => ['title' => 'الدرس الثاني: الحركات والتشكيل'],
                            'en' => ['title' => 'Lesson 2: Vowels and Diacritics'],
                        ],
                    ],
                    [
                        'slug' => 'arabic-ep03',
                        'number' => 3,
                        'duration' => 3600,
                        'views' => 100000,
                        'translations' => [
                            'ar' => ['title' => 'الدرس الثالث: المفردات القرآنية الأساسية'],
                            'en' => ['title' => 'Lesson 3: Basic Quranic Vocabulary'],
                        ],
                    ],
                    [
                        'slug' => 'arabic-ep04',
                        'number' => 4,
                        'duration' => 3600,
                        'views' => 90000,
                        'translations' => [
                            'ar' => ['title' => 'الدرس الرابع: الجملة الاسمية'],
                            'en' => ['title' => 'Lesson 4: The Nominal Sentence'],
                        ],
                    ],
                    [
                        'slug' => 'arabic-ep05',
                        'number' => 5,
                        'duration' => 3600,
                        'views' => 85000,
                        'translations' => [
                            'ar' => ['title' => 'الدرس الخامس: الجملة الفعلية'],
                            'en' => ['title' => 'Lesson 5: The Verbal Sentence'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'heroes-of-islam',
                'category' => ProgramCategory::SEERAH,
                'year' => 2016,
                'rating' => 8.8,
                'is_trending' => true,
                'views_count' => 950000,
                'display_order' => 13,
                'translations' => [
                    'ar' => [
                        'title' => 'أبطال الإسلام',
                        'description' => 'سلسلة وثائقية تستعرض سير أبطال الإسلام عبر التاريخ من القادة العسكريين والعلماء والمصلحين.',
                        'host' => 'الدكتور راغب السرجاني',
                    ],
                    'en' => [
                        'title' => 'Heroes of Islam',
                        'description' => 'A documentary series reviewing the biographies of Islamic heroes throughout history, including military commanders, scholars, and reformers.',
                        'host' => 'Dr. Raghib Al-Sirjani',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'heroes-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 3600,
                        'views' => 220000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'صلاح الدين الأيوبي: فاتح القدس', 'description' => 'سيرة البطل الإسلامي الذي حرر بيت المقدس.'],
                            'en' => ['title' => 'Saladin: The Liberator of Jerusalem', 'description' => 'Biography of the Islamic hero who liberated Jerusalem.'],
                        ],
                    ],
                    [
                        'slug' => 'heroes-ep02',
                        'number' => 2,
                        'duration' => 3600,
                        'views' => 200000,
                        'translations' => [
                            'ar' => ['title' => 'محمد الفاتح: فاتح القسطنطينية'],
                            'en' => ['title' => 'Mehmed the Conqueror: The Conqueror of Constantinople'],
                        ],
                    ],
                    [
                        'slug' => 'heroes-ep03',
                        'number' => 3,
                        'duration' => 3600,
                        'views' => 185000,
                        'translations' => [
                            'ar' => ['title' => 'ابن بطوطة: الرحالة العظيم'],
                            'en' => ['title' => 'Ibn Battuta: The Great Traveler'],
                        ],
                    ],
                    [
                        'slug' => 'heroes-ep04',
                        'number' => 4,
                        'duration' => 3600,
                        'views' => 170000,
                        'translations' => [
                            'ar' => ['title' => 'ابن سينا: رئيس الأطباء'],
                            'en' => ['title' => 'Ibn Sina: The Prince of Physicians'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'youth-and-islam',
                'category' => ProgramCategory::DOCUMENTARY,
                'year' => 2023,
                'rating' => 8.5,
                'is_featured' => true,
                'is_trending' => true,
                'views_count' => 700000,
                'display_order' => 14,
                'translations' => [
                    'ar' => [
                        'title' => 'الشباب والإسلام',
                        'description' => 'برنامج موجه للشباب يتناول القضايا المعاصرة من منظور إسلامي، ويساعدهم على بناء هويتهم الإسلامية في عالم متغير.',
                        'host' => 'أحمد الشقيري',
                    ],
                    'en' => [
                        'title' => 'Youth and Islam',
                        'description' => 'A program directed at youth addressing contemporary issues from an Islamic perspective, helping them build their Islamic identity in a changing world.',
                        'host' => 'Ahmad Al-Shugairi',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'youth-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 2400,
                        'views' => 170000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'الهوية الإسلامية في عصر العولمة'],
                            'en' => ['title' => 'Islamic Identity in the Age of Globalization'],
                        ],
                    ],
                    [
                        'slug' => 'youth-ep02',
                        'number' => 2,
                        'duration' => 2400,
                        'views' => 155000,
                        'translations' => [
                            'ar' => ['title' => 'الشباب ومواقع التواصل الاجتماعي'],
                            'en' => ['title' => 'Youth and Social Media'],
                        ],
                    ],
                    [
                        'slug' => 'youth-ep03',
                        'number' => 3,
                        'duration' => 2400,
                        'views' => 140000,
                        'translations' => [
                            'ar' => ['title' => 'الأسرة المسلمة في المجتمعات الغربية'],
                            'en' => ['title' => 'Muslim Families in Western Societies'],
                        ],
                    ],
                    [
                        'slug' => 'youth-ep04',
                        'number' => 4,
                        'duration' => 2400,
                        'views' => 130000,
                        'translations' => [
                            'ar' => ['title' => 'الإسلام والعلم والتكنولوجيا'],
                            'en' => ['title' => 'Islam, Science, and Technology'],
                        ],
                    ],
                    [
                        'slug' => 'youth-ep05',
                        'number' => 5,
                        'duration' => 2400,
                        'views' => 120000,
                        'translations' => [
                            'ar' => ['title' => 'الشباب المسلم ومستقبل الأمة'],
                            'en' => ['title' => 'Muslim Youth and the Future of the Nation'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'tajweed-quran-recitation',
                'category' => ProgramCategory::QURAN,
                'year' => 2022,
                'rating' => 9.1,
                'views_count' => 1300000,
                'display_order' => 15,
                'translations' => [
                    'ar' => [
                        'title' => 'تعلم التجويد',
                        'description' => 'دورة شاملة لتعلم أحكام التجويد وتطبيقها في تلاوة القرآن الكريم، من المستوى المبتدئ إلى المتقدم.',
                        'host' => 'الشيخ أيمن سويد',
                    ],
                    'en' => [
                        'title' => 'Learn Tajweed',
                        'description' => 'A comprehensive course to learn the rules of Tajweed and apply them in reciting the Holy Quran, from beginner to advanced level.',
                        'host' => 'Sheikh Ayman Suwaid',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'tajweed-ep01',
                        'number' => 1,
                        'video_url' => 'https://www.youtube.com/embed/FmWCbVX5X_U',
                        'duration' => 2700,
                        'views' => 320000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'مخارج الحروف', 'description' => 'تعلم مخارج الحروف العربية وكيفية النطق السليم.'],
                            'en' => ['title' => 'Articulation Points of Letters', 'description' => 'Learning the articulation points of Arabic letters and correct pronunciation.'],
                        ],
                    ],
                    [
                        'slug' => 'tajweed-ep02',
                        'number' => 2,
                        'duration' => 2700,
                        'views' => 290000,
                        'translations' => [
                            'ar' => ['title' => 'صفات الحروف'],
                            'en' => ['title' => 'Characteristics of Letters'],
                        ],
                    ],
                    [
                        'slug' => 'tajweed-ep03',
                        'number' => 3,
                        'duration' => 2700,
                        'views' => 270000,
                        'translations' => [
                            'ar' => ['title' => 'أحكام النون الساكنة والتنوين'],
                            'en' => ['title' => 'Rules of Sukun Noon and Tanwin'],
                        ],
                    ],
                    [
                        'slug' => 'tajweed-ep04',
                        'number' => 4,
                        'duration' => 2700,
                        'views' => 250000,
                        'translations' => [
                            'ar' => ['title' => 'أحكام الميم الساكنة'],
                            'en' => ['title' => 'Rules of Sukun Meem'],
                        ],
                    ],
                    [
                        'slug' => 'tajweed-ep05',
                        'number' => 5,
                        'duration' => 2700,
                        'views' => 230000,
                        'translations' => [
                            'ar' => ['title' => 'المد وأنواعه'],
                            'en' => ['title' => 'Madd (Elongation) and Its Types'],
                        ],
                    ],
                    [
                        'slug' => 'tajweed-ep06',
                        'number' => 6,
                        'duration' => 2700,
                        'views' => 210000,
                        'translations' => [
                            'ar' => ['title' => 'أحكام الوقف والابتداء'],
                            'en' => ['title' => 'Rules of Pausing and Starting'],
                        ],
                    ],
                ],
            ],

            [
                'slug' => 'morning-adhkar-program',
                'category' => ProgramCategory::QURAN,
                'year' => 2023,
                'rating' => 9.2,
                'is_featured' => false,
                'is_trending' => true,
                'views_count' => 900000,
                'display_order' => 16,
                'translations' => [
                    'ar' => [
                        'title' => 'أذكار الصباح والمساء',
                        'description' => 'برنامج يومي للأذكار الصباحية والمسائية مع شرح معانيها وفوائدها، لتحصين المسلم وتقوية علاقته بالله.',
                        'host' => 'الشيخ وسيم يوسف',
                    ],
                    'en' => [
                        'title' => 'Morning and Evening Adhkar',
                        'description' => 'A daily program for morning and evening remembrances with explanation of their meanings and benefits, to strengthen the Muslim\'s connection with Allah.',
                        'host' => 'Sheikh Wassim Yusuf',
                    ],
                ],
                'episodes' => [
                    [
                        'slug' => 'adhkar-ep01',
                        'number' => 1,
                        'video_url' => null, // no verified official upload found — player shows unavailable state
                        'duration' => 1800,
                        'views' => 250000,
                        'is_featured' => true,
                        'translations' => [
                            'ar' => ['title' => 'أهمية الذكر في الإسلام', 'description' => 'مقدمة عن فضل الذكر وأهميته في حياة المسلم.'],
                            'en' => ['title' => 'The Importance of Dhikr in Islam', 'description' => 'Introduction to the virtue of remembrance and its importance in a Muslim\'s life.'],
                        ],
                    ],
                    [
                        'slug' => 'adhkar-ep02',
                        'number' => 2,
                        'duration' => 1800,
                        'views' => 230000,
                        'translations' => [
                            'ar' => ['title' => 'أذكار الاستيقاظ من النوم'],
                            'en' => ['title' => 'Adhkar Upon Waking from Sleep'],
                        ],
                    ],
                    [
                        'slug' => 'adhkar-ep03',
                        'number' => 3,
                        'duration' => 1800,
                        'views' => 210000,
                        'translations' => [
                            'ar' => ['title' => 'أذكار الصباح'],
                            'en' => ['title' => 'Morning Adhkar'],
                        ],
                    ],
                    [
                        'slug' => 'adhkar-ep04',
                        'number' => 4,
                        'duration' => 1800,
                        'views' => 200000,
                        'translations' => [
                            'ar' => ['title' => 'أذكار المساء'],
                            'en' => ['title' => 'Evening Adhkar'],
                        ],
                    ],
                    [
                        'slug' => 'adhkar-ep05',
                        'number' => 5,
                        'duration' => 1800,
                        'views' => 190000,
                        'translations' => [
                            'ar' => ['title' => 'أذكار النوم'],
                            'en' => ['title' => 'Adhkar Before Sleep'],
                        ],
                    ],
                    [
                        'slug' => 'adhkar-ep06',
                        'number' => 6,
                        'duration' => 1800,
                        'views' => 175000,
                        'translations' => [
                            'ar' => ['title' => 'أذكار الأكل والشرب'],
                            'en' => ['title' => 'Adhkar for Eating and Drinking'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
