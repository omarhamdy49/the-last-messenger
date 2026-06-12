<?php

declare(strict_types=1);

namespace Database\Seeders\Blog;

use App\Domain\Blog\Enums\CategoryType;
use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Series;
use App\Domain\Blog\Models\Tag;
use Illuminate\Database\Seeder;

/**
 * Blog Taxonomy Seeder
 *
 * Seeds the blog taxonomy: 8 categories, 30 tags, and 4 series with
 * Arabic + English translations. Idempotent — uses the slug as the
 * natural key and upserts translations, so re-running never duplicates.
 */
class BlogTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $this->seedTags();
        $this->seedSeries();

        $this->command?->info('Blog taxonomy seeded: 8 categories, 30 tags, 4 series.');
    }

    private function seedCategories(): void
    {
        foreach ($this->categories() as $index => $definition) {
            /** @var Category $category */
            $category = Category::query()->firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'type' => CategoryType::BLOG->value,
                    'status' => 'active',
                    'parent_id' => null,
                    'icon' => $definition['icon'],
                    'color' => $definition['color'],
                    'display_order' => $index + 1,
                ]
            );

            $category->syncTranslations([
                'name' => $definition['name'],
                'description' => $definition['description'],
            ]);
        }
    }

    private function seedTags(): void
    {
        foreach ($this->tags() as $definition) {
            /** @var Tag $tag */
            $tag = Tag::query()->firstOrCreate(
                ['slug' => $definition['slug']],
                ['type' => 'blog']
            );

            $tag->syncTranslations([
                'name' => $definition['name'],
            ]);
        }
    }

    private function seedSeries(): void
    {
        foreach ($this->series() as $definition) {
            /** @var Series $series */
            $series = Series::query()->firstOrCreate(
                ['slug' => $definition['slug']]
            );

            $series->syncTranslations([
                'title' => $definition['title'],
                'description' => $definition['description'],
            ]);
        }
    }

    /**
     * @return array<int, array{slug: string, icon: string, color: string, name: array<string, string>, description: array<string, string>}>
     */
    private function categories(): array
    {
        return [
            [
                'slug' => 'seerah',
                'icon' => 'book-open',
                'color' => '#0056b3',
                'name' => ['ar' => 'السيرة النبوية', 'en' => 'Seerah'],
                'description' => [
                    'ar' => 'دراسات وتأملات في سيرة النبي محمد ﷺ منذ مولده إلى وفاته، واستخلاص الدروس والعبر من مواقفه وغزواته ومعاهداته.',
                    'en' => 'Studies and reflections on the biography of the Prophet Muhammad ﷺ, drawing lessons from his life, campaigns, and treaties.',
                ],
            ],
            [
                'slug' => 'aqeedah',
                'icon' => 'heart',
                'color' => '#218838',
                'name' => ['ar' => 'العقيدة', 'en' => 'Aqeedah'],
                'description' => [
                    'ar' => 'مقالات في أصول الإيمان والتوحيد وأسماء الله الحسنى وأعمال القلوب التي يقوم عليها دين المسلم.',
                    'en' => 'Articles on the foundations of faith, monotheism, the beautiful names of Allah, and the actions of the heart.',
                ],
            ],
            [
                'slug' => 'fiqh',
                'icon' => 'scale',
                'color' => '#6f42c1',
                'name' => ['ar' => 'الفقه', 'en' => 'Fiqh'],
                'description' => [
                    'ar' => 'أحكام العبادات والمعاملات بأسلوب ميسر، مع بيان مقاصد الشريعة وحِكَمها في حياة المسلم اليومية.',
                    'en' => 'Accessible explanations of Islamic rulings on worship and transactions, with attention to the objectives of the Sharia.',
                ],
            ],
            [
                'slug' => 'hadith-sciences',
                'icon' => 'scroll-text',
                'color' => '#b45309',
                'name' => ['ar' => 'علوم الحديث', 'en' => 'Hadith Sciences'],
                'description' => [
                    'ar' => 'مدخل إلى علوم الحديث النبوي: الرواية والإسناد والجرح والتعديل وجهود المحدثين في حفظ السنة.',
                    'en' => 'An introduction to the sciences of hadith: narration, chains of transmission, and the preservation of the Sunnah.',
                ],
            ],
            [
                'slug' => 'quran-sciences',
                'icon' => 'book',
                'color' => '#0e7490',
                'name' => ['ar' => 'علوم القرآن', 'en' => 'Quran Sciences'],
                'description' => [
                    'ar' => 'تدبر آيات القرآن الكريم، وأسباب النزول، وموضوعات قرآنية تربط كتاب الله بواقع الإنسان.',
                    'en' => 'Reflections on the verses of the Quran, occasions of revelation, and thematic Quranic studies.',
                ],
            ],
            [
                'slug' => 'islamic-history',
                'icon' => 'landmark',
                'color' => '#92400e',
                'name' => ['ar' => 'التاريخ الإسلامي', 'en' => 'Islamic History'],
                'description' => [
                    'ar' => 'محطات مضيئة من تاريخ الأمة الإسلامية: الخلافة الراشدة، وأعلام الصحابة والتابعين، ودروس الحضارة.',
                    'en' => 'Luminous chapters from Islamic history: the rightly-guided caliphate, the companions, and civilizational lessons.',
                ],
            ],
            [
                'slug' => 'prophetic-character',
                'icon' => 'sparkles',
                'color' => '#64CCC5',
                'name' => ['ar' => 'الشمائل المحمدية', 'en' => 'Prophetic Character'],
                'description' => [
                    'ar' => 'صفات النبي ﷺ وأخلاقه في بيته ومع أصحابه ومع المخالفين، وكيف نتأسى بها في حياتنا.',
                    'en' => 'The character and noble traits of the Prophet ﷺ at home, with his companions, and with opponents.',
                ],
            ],
            [
                'slug' => 'contemporary-issues',
                'icon' => 'globe',
                'color' => '#475569',
                'name' => ['ar' => 'قضايا معاصرة', 'en' => 'Contemporary Issues'],
                'description' => [
                    'ar' => 'قراءة شرعية وتربوية في قضايا العصر: التقنية، والأسرة، والشباب، وتحديات الهوية في عالم متغير.',
                    'en' => 'Islamic perspectives on modern challenges: technology, family, youth, and identity in a changing world.',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{slug: string, name: array<string, string>}>
     */
    private function tags(): array
    {
        return [
            ['slug' => 'sabr', 'name' => ['ar' => 'الصبر', 'en' => 'Patience']],
            ['slug' => 'rahma', 'name' => ['ar' => 'الرحمة', 'en' => 'Mercy']],
            ['slug' => 'hijra', 'name' => ['ar' => 'الهجرة النبوية', 'en' => 'Hijra']],
            ['slug' => 'ramadan', 'name' => ['ar' => 'رمضان', 'en' => 'Ramadan']],
            ['slug' => 'fasting', 'name' => ['ar' => 'الصيام', 'en' => 'Fasting']],
            ['slug' => 'salah', 'name' => ['ar' => 'الصلاة', 'en' => 'Prayer']],
            ['slug' => 'qiyam-al-layl', 'name' => ['ar' => 'قيام الليل', 'en' => 'Night Prayer']],
            ['slug' => 'ikhlas', 'name' => ['ar' => 'الإخلاص', 'en' => 'Sincerity']],
            ['slug' => 'dua', 'name' => ['ar' => 'الدعاء', 'en' => 'Supplication']],
            ['slug' => 'quran', 'name' => ['ar' => 'القرآن الكريم', 'en' => 'Quran']],
            ['slug' => 'hadith', 'name' => ['ar' => 'الحديث الشريف', 'en' => 'Hadith']],
            ['slug' => 'sunnah', 'name' => ['ar' => 'السنة النبوية', 'en' => 'Sunnah']],
            ['slug' => 'family', 'name' => ['ar' => 'الأسرة', 'en' => 'Family']],
            ['slug' => 'parenting', 'name' => ['ar' => 'التربية', 'en' => 'Parenting']],
            ['slug' => 'youth', 'name' => ['ar' => 'الشباب', 'en' => 'Youth']],
            ['slug' => 'akhlaq', 'name' => ['ar' => 'الأخلاق', 'en' => 'Character']],
            ['slug' => 'tawba', 'name' => ['ar' => 'التوبة', 'en' => 'Repentance']],
            ['slug' => 'shukr', 'name' => ['ar' => 'الشكر', 'en' => 'Gratitude']],
            ['slug' => 'tawakkul', 'name' => ['ar' => 'التوكل', 'en' => 'Reliance on Allah']],
            ['slug' => 'brotherhood', 'name' => ['ar' => 'الأخوة', 'en' => 'Brotherhood']],
            ['slug' => 'justice', 'name' => ['ar' => 'العدل', 'en' => 'Justice']],
            ['slug' => 'ilm', 'name' => ['ar' => 'العلم', 'en' => 'Knowledge']],
            ['slug' => 'sadaqah', 'name' => ['ar' => 'الصدقة', 'en' => 'Charity']],
            ['slug' => 'hajj', 'name' => ['ar' => 'الحج', 'en' => 'Hajj']],
            ['slug' => 'iman', 'name' => ['ar' => 'الإيمان', 'en' => 'Faith']],
            ['slug' => 'ibtila', 'name' => ['ar' => 'الابتلاء', 'en' => 'Trials']],
            ['slug' => 'raja', 'name' => ['ar' => 'الرجاء', 'en' => 'Hope']],
            ['slug' => 'women-in-islam', 'name' => ['ar' => 'المرأة في الإسلام', 'en' => 'Women in Islam']],
            ['slug' => 'taqwa', 'name' => ['ar' => 'التقوى', 'en' => 'Taqwa']],
            ['slug' => 'modern-life', 'name' => ['ar' => 'الحياة المعاصرة', 'en' => 'Modern Life']],
        ];
    }

    /**
     * @return array<int, array{slug: string, title: array<string, string>, description: array<string, string>}>
     */
    private function series(): array
    {
        return [
            [
                'slug' => 'lessons-from-the-seerah',
                'title' => ['ar' => 'دروس من السيرة', 'en' => 'Lessons from the Seerah'],
                'description' => [
                    'ar' => 'سلسلة تستخرج الدروس العملية من محطات السيرة النبوية الكبرى: الهجرة، والحصار، والصلح، وحجة الوداع.',
                    'en' => 'A series extracting practical lessons from the milestones of the Prophetic biography: the Hijra, the boycott, the treaty, and the farewell pilgrimage.',
                ],
            ],
            [
                'slug' => 'names-of-allah',
                'title' => ['ar' => 'أسماء الله الحسنى', 'en' => 'Names of Allah'],
                'description' => [
                    'ar' => 'سلسلة تتأمل في أسماء الله الحسنى ومعانيها وأثر الإيمان بها في قلب المسلم وسلوكه.',
                    'en' => 'A series contemplating the beautiful names of Allah, their meanings, and their impact on the believer\'s heart and conduct.',
                ],
            ],
            [
                'slug' => 'prophetic-parenting',
                'title' => ['ar' => 'التربية النبوية', 'en' => 'Prophetic Parenting'],
                'description' => [
                    'ar' => 'سلسلة في فن تربية الأبناء على المنهج النبوي: الرحمة، والقدوة، والتوجيه الرفيق دون كسر أو قسوة.',
                    'en' => 'A series on raising children the Prophetic way: mercy, role-modelling, and gentle correction without harshness.',
                ],
            ],
            [
                'slug' => 'ramadan-series',
                'title' => ['ar' => 'سلسلة رمضان', 'en' => 'Ramadan Series'],
                'description' => [
                    'ar' => 'سلسلة رمضانية تتناول الصيام والقيام وليلة القدر وثمرات الشهر الكريم في تزكية النفس.',
                    'en' => 'A Ramadan series covering fasting, night prayer, Laylat al-Qadr, and the spiritual fruits of the blessed month.',
                ],
            ],
        ];
    }
}
