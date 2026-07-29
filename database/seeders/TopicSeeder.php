<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Topics\Enums\TopicItemType;
use App\Domain\Topics\Models\Topic;
use App\Domain\Topics\Repositories\TopicRepositoryInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TopicSeeder
 *
 * Creates ~12 real Islamic themes and AUTO-CURATES each topic's content by
 * matching the theme's Arabic root term(s) against the EXISTING content tables:
 *
 *   - Quran verses:  quran_verse_translations.text_notashkeel (diacritic-free
 *                    column, so a plain LIKE matches reliably)
 *   - Hadiths:       hadith_translations.search_text for the Arabic locale
 *                    (the diacritic-free Arabic matn)
 *   - Articles:      article_locale.title / content (ar) LIKE term
 *
 * It links 100% REAL rows via topic_items — no content is fabricated. The seeder
 * is idempotent: topics are updateOrCreate'd by slug and items are sync'd.
 */
class TopicSeeder extends Seeder
{
    private const VERSE_LIMIT = 25;

    private const HADITH_LIMIT = 25;

    private const ARTICLE_LIMIT = 12;

    /**
     * Theme definitions. `terms` are diacritic-free Arabic substrings used to
     * match the *_notashkeel content columns.
     *
     * @var array<int, array<string, mixed>>
     */
    private function themes(): array
    {
        return [
            ['slug' => 'patience', 'icon' => 'hourglass', 'color' => 'from-blue-500 to-cyan-500', 'featured' => true,
                'title' => ['ar' => 'الصبر', 'en' => 'Patience', 'fr' => 'La patience', 'tr' => 'Sabır', 'ur' => 'صبر'],
                'desc' => ['ar' => 'استعراض شامل للآيات والأحاديث المتعلقة بفضيلة الصبر في الإسلام.', 'en' => 'A comprehensive review of the verses and hadiths on the virtue of patience in Islam.'],
                'terms' => ['الصبر', 'صبر', 'الصابرين', 'اصبر']],

            ['slug' => 'prayer', 'icon' => 'moon-star', 'color' => 'from-indigo-500 to-purple-600', 'featured' => true,
                'title' => ['ar' => 'الصلاة', 'en' => 'Prayer', 'fr' => 'La prière', 'tr' => 'Namaz', 'ur' => 'نماز'],
                'desc' => ['ar' => 'الآيات والأحاديث الواردة في فضل الصلاة وأحكامها.', 'en' => 'Verses and hadiths on the merit and rulings of prayer.'],
                // 'صلوة' is the classical Quranic orthography in the notashkeel column.
                'terms' => ['الصلوة', 'صلوة', 'الصلاة', 'صلاة', 'يصلي', 'الصلوات']],

            ['slug' => 'charity', 'icon' => 'hand-coins', 'color' => 'from-emerald-500 to-teal-500', 'featured' => false,
                'title' => ['ar' => 'الزكاة والصدقة', 'en' => 'Charity', 'fr' => 'L\'aumône', 'tr' => 'Zekât', 'ur' => 'زکوٰۃ'],
                'desc' => ['ar' => 'ما ورد في الزكاة والصدقة والإنفاق في سبيل الله.', 'en' => 'What is reported on zakat, charity, and spending for the sake of God.'],
                // 'زكوة' classical orthography; 'صدقة' for charity.
                'terms' => ['الزكوة', 'زكوة', 'الزكاة', 'زكاة', 'الصدقة', 'صدقة', 'الصدقت']],

            ['slug' => 'truthfulness', 'icon' => 'badge-check', 'color' => 'from-sky-500 to-blue-600', 'featured' => false,
                'title' => ['ar' => 'الصدق', 'en' => 'Truthfulness', 'fr' => 'La véracité', 'tr' => 'Doğruluk', 'ur' => 'سچائی'],
                'desc' => ['ar' => 'الآيات والأحاديث في فضيلة الصدق وذم الكذب.', 'en' => 'Verses and hadiths on the virtue of truthfulness.'],
                'terms' => ['الصدق', 'صدق', 'الصادقين', 'صادق']],

            ['slug' => 'repentance', 'icon' => 'rotate-ccw', 'color' => 'from-green-500 to-emerald-600', 'featured' => false,
                'title' => ['ar' => 'التوبة', 'en' => 'Repentance', 'fr' => 'Le repentir', 'tr' => 'Tövbe', 'ur' => 'توبہ'],
                'desc' => ['ar' => 'ما ورد في التوبة والاستغفار والرجوع إلى الله.', 'en' => 'What is reported on repentance and seeking forgiveness.'],
                'terms' => ['التوبة', 'التوبه', 'توبة', 'تاب', 'الاستغفار', 'يستغفر']],

            ['slug' => 'kindness-to-parents', 'icon' => 'heart-handshake', 'color' => 'from-pink-500 to-rose-500', 'featured' => false,
                'title' => ['ar' => 'بر الوالدين', 'en' => 'Kindness to Parents', 'fr' => 'La bonté envers les parents', 'tr' => 'Anne babaya iyilik', 'ur' => 'والدین سے حسن سلوک'],
                'desc' => ['ar' => 'الآيات والأحاديث في الإحسان إلى الوالدين وبرهما.', 'en' => 'Verses and hadiths on kindness and dutifulness to parents.'],
                // Quranic notashkeel drops the dagger alef: 'ولدين' / 'ولديه'. Hadith uses 'الوالدين'.
                'terms' => ['ولدين', 'بالولدين', 'ولديه', 'الوالدين', 'بالوالدين', 'والديه']],

            ['slug' => 'knowledge', 'icon' => 'graduation-cap', 'color' => 'from-amber-500 to-orange-500', 'featured' => true,
                'title' => ['ar' => 'العلم', 'en' => 'Knowledge', 'fr' => 'Le savoir', 'tr' => 'İlim', 'ur' => 'علم'],
                'desc' => ['ar' => 'فضل طلب العلم والعلماء في القرآن والسنة.', 'en' => 'The merit of seeking knowledge in the Quran and Sunnah.'],
                'terms' => ['العلم', 'علم', 'العلماء', 'يعلمون', 'تعلم']],

            ['slug' => 'faith', 'icon' => 'sparkles', 'color' => 'from-violet-500 to-purple-600', 'featured' => true,
                'title' => ['ar' => 'الإيمان', 'en' => 'Faith', 'fr' => 'La foi', 'tr' => 'İman', 'ur' => 'ایمان'],
                'desc' => ['ar' => 'الآيات والأحاديث في حقيقة الإيمان وشعبه.', 'en' => 'Verses and hadiths on the reality and branches of faith.'],
                'terms' => ['الايمان', 'ايمان', 'المؤمنين', 'امنوا', 'يؤمنون']],

            ['slug' => 'piety', 'icon' => 'shield-check', 'color' => 'from-teal-500 to-cyan-600', 'featured' => false,
                'title' => ['ar' => 'التقوى', 'en' => 'Piety', 'fr' => 'La piété', 'tr' => 'Takvâ', 'ur' => 'تقویٰ'],
                'desc' => ['ar' => 'ما ورد في تقوى الله والخوف منه.', 'en' => 'What is reported on the fear and consciousness of God.'],
                'terms' => ['التقوى', 'تقوى', 'المتقين', 'اتقوا', 'يتقون']],

            ['slug' => 'mercy', 'icon' => 'heart', 'color' => 'from-rose-500 to-pink-600', 'featured' => false,
                'title' => ['ar' => 'الرحمة', 'en' => 'Mercy', 'fr' => 'La miséricorde', 'tr' => 'Merhamet', 'ur' => 'رحمت'],
                'desc' => ['ar' => 'رحمة الله بعباده والرحمة بين الناس.', 'en' => 'God\'s mercy toward His servants and mercy among people.'],
                'terms' => ['الرحمة', 'الرحمه', 'رحمة', 'الرحمن', 'الرحيم', 'يرحم']],

            ['slug' => 'striving', 'icon' => 'flag', 'color' => 'from-red-500 to-orange-600', 'featured' => false,
                'title' => ['ar' => 'الجهاد', 'en' => 'Striving', 'fr' => 'L\'effort', 'tr' => 'Cihad', 'ur' => 'جہاد'],
                'desc' => ['ar' => 'الآيات والأحاديث في الجهاد في سبيل الله.', 'en' => 'Verses and hadiths on striving in the path of God.'],
                'terms' => ['الجهاد', 'جهاد', 'جاهدوا', 'المجاهدين', 'سبيل الله']],

            ['slug' => 'pilgrimage', 'icon' => 'map-pin', 'color' => 'from-yellow-500 to-amber-600', 'featured' => false,
                'title' => ['ar' => 'الحج', 'en' => 'Pilgrimage', 'fr' => 'Le pèlerinage', 'tr' => 'Hac', 'ur' => 'حج'],
                'desc' => ['ar' => 'ما ورد في الحج والعمرة ومناسكهما.', 'en' => 'What is reported on Hajj, Umrah, and their rites.'],
                'terms' => ['الحج', 'حج', 'البيت', 'العمرة', 'الكعبة', 'مكة']],
        ];
    }

    public function run(): void
    {
        /** @var TopicRepositoryInterface $repository */
        $repository = app(TopicRepositoryInterface::class);

        $order = 0;
        foreach ($this->themes() as $theme) {
            $topic = Topic::updateOrCreate(
                ['slug' => $theme['slug']],
                [
                    'icon' => $theme['icon'],
                    'color' => $theme['color'],
                    'is_featured' => (bool) $theme['featured'],
                    'display_order' => $order++,
                ],
            );

            $topic->syncTranslations([
                'title' => $theme['title'],
                'description' => $theme['desc'],
            ]);

            $verseIds = $this->matchVerseIds($theme['terms']);
            $hadithIds = $this->matchHadithIds($theme['terms']);
            $articleIds = $this->matchArticleIds($theme['terms']);

            $repository->syncItems($topic->id, TopicItemType::VERSE->value, $verseIds);
            $repository->syncItems($topic->id, TopicItemType::HADITH->value, $hadithIds);
            $repository->syncItems($topic->id, TopicItemType::ARTICLE->value, $articleIds);

            $this->command?->info(sprintf(
                '  • %-22s verses=%d hadiths=%d articles=%d',
                $theme['title']['ar'],
                count($verseIds),
                count($hadithIds),
                count($articleIds),
            ));
        }

        $this->command?->info('✅ Topics seeded with real, linked content.');
    }

    /**
     * Real verse ids whose Arabic text (diacritic-free) contains any theme term.
     *
     * @param  array<int, string>  $terms
     * @return array<int, int>
     */
    private function matchVerseIds(array $terms): array
    {
        $query = DB::table('quran_verse_translations')
            ->where('lang', 'ar')
            ->where(function ($q) use ($terms): void {
                foreach ($terms as $term) {
                    $q->orWhere('text_notashkeel', 'LIKE', "%{$term}%")
                        ->orWhere('text', 'LIKE', "%{$term}%");
                }
            })
            ->orderBy('verse_id')
            ->limit(self::VERSE_LIMIT);

        return $query->pluck('verse_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Real hadith ids whose diacritic-free Arabic matn contains any theme term.
     * Prefers authentic (sahih) hadiths first.
     *
     * @param  array<int, string>  $terms
     * @return array<int, int>
     */
    private function matchHadithIds(array $terms): array
    {
        $ids = DB::table('hadith_translations as ht')
            ->join('hadith_hadiths as h', 'h.id', '=', 'ht.hadith_id')
            ->where('ht.locale', 'ar')
            ->where(function ($q) use ($terms): void {
                foreach ($terms as $term) {
                    $q->orWhere('ht.search_text', 'LIKE', "%{$term}%");
                }
            })
            ->orderByRaw("CASE
                WHEN EXISTS (
                    SELECT 1 FROM hadith_gradings hg
                    WHERE hg.hadith_id = h.id AND hg.grade_key = 'sahih'
                ) THEN 0
                WHEN EXISTS (
                    SELECT 1 FROM hadith_gradings hg
                    WHERE hg.hadith_id = h.id AND hg.grade_key = 'hasan'
                ) THEN 1
                ELSE 2
            END")
            ->orderBy('h.id')
            ->limit(self::HADITH_LIMIT)
            ->pluck('ht.hadith_id');

        // Distinct + preserve order.
        return $ids->unique()->values()->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Real article ids whose Arabic locale title/content contains any theme term.
     * Only published articles are linked.
     *
     * @param  array<int, string>  $terms
     * @return array<int, int>
     */
    private function matchArticleIds(array $terms): array
    {
        return DB::table('article_locale as al')
            ->join('articles as a', 'a.id', '=', 'al.article_id')
            ->where('al.locale', 'ar')
            ->where('a.status', 'published')
            ->whereNull('a.deleted_at')
            ->where(function ($q) use ($terms): void {
                foreach ($terms as $term) {
                    $q->orWhere('al.title', 'LIKE', "%{$term}%")
                        ->orWhere('al.content', 'LIKE', "%{$term}%")
                        ->orWhere('al.excerpt', 'LIKE', "%{$term}%");
                }
            })
            ->orderBy('a.id')
            ->limit(self::ARTICLE_LIMIT)
            ->pluck('al.article_id')
            ->unique()
            ->values()
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
