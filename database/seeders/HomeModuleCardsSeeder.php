<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\HomeModuleCard;
use App\Domain\Content\Services\HomeContentService;
use Illuminate\Database\Seeder;

/**
 * HomeModuleCardsSeeder
 *
 * Seeds the six homepage module cards with the CURRENT hardcoded values
 * from public/home.blade.php $modules (icons, accent colours, targets) and
 * resources/lang/{ar,en,fr,tr,ur}/site.php (titles + descriptions), so the
 * view-wiring task swaps cleanly. Idempotent: cards are upserted by key.
 */
class HomeModuleCardsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->cards() as $index => $card) {
            /** @var HomeModuleCard $model */
            $model = HomeModuleCard::query()->updateOrCreate(
                ['key' => $card['key']],
                [
                    'icon' => $card['icon'],
                    'target_url' => $card['target_url'],
                    'accent_color' => $card['accent_color'],
                    'is_featured' => $card['is_featured'],
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );

            $model->syncTranslations([
                'title' => $card['title'],
                'description' => $card['description'],
            ]);
        }

        app(HomeContentService::class)->flush();

        $this->command?->info('Home module cards seeded: '.HomeModuleCard::query()->count().' cards.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cards(): array
    {
        return [
            [
                'key' => 'seerah',
                'icon' => 'scroll-text',
                'target_url' => 'public.seerah',
                'accent_color' => '#0056b3',
                'is_featured' => true, // "popular" badge in the prototype
                'title' => ['ar' => 'السيرة النبوية', 'en' => 'Biography', 'fr' => 'Biographie', 'tr' => 'Siyer', 'ur' => 'سیرت'],
                'description' => [
                    'ar' => 'رحلة في حياة النبي ﷺ عبر جدول زمني تفاعلي للأحداث والغزوات.',
                    'en' => 'A journey through the life of the Prophet ﷺ via an interactive timeline of events and battles.',
                    'fr' => 'Un voyage à travers la vie du Prophète ﷺ via une chronologie interactive des événements et batailles.',
                    'tr' => 'Hz. Muhammed\'in olaylar ve savaşların etkileşimli zaman çizelgesiyle hayatına yolculuk.',
                    'ur' => 'نبی ﷺ کی زندگی کا سفر واقعات اور غزوات کی انٹرایکٹو ٹائم لائن کے ذریعے۔',
                ],
            ],
            [
                'key' => 'hadith',
                'icon' => 'message-square-quote',
                'target_url' => 'public.hadith',
                'accent_color' => '#218838',
                'is_featured' => true, // "featured" badge in the prototype
                'title' => ['ar' => 'الحديث الشريف', 'en' => 'Hadith', 'fr' => 'Hadith', 'tr' => 'Hadis', 'ur' => 'حدیث'],
                'description' => [
                    'ar' => 'آلاف الأحاديث الشريفة من أمهات الكتب مع الأسانيد والشروح والتخريج.',
                    'en' => 'Thousands of authentic hadiths from the major collections with chains, gradings, and commentary.',
                    'fr' => 'Des milliers de hadiths authentiques des principales collections avec chaînes, classements et commentaires.',
                    'tr' => 'Başlıca koleksiyonlardan zincirler, derecelendirmeler ve yorumlarla binlerce sahih hadis.',
                    'ur' => 'اہم مجموعوں سے سندوں، درجہ بندی اور تشریح کے ساتھ ہزاروں صحیح احادیث۔',
                ],
            ],
            [
                'key' => 'media',
                'icon' => 'film',
                'target_url' => 'public.media',
                'accent_color' => '#FF7F50',
                'is_featured' => true, // "new" badge in the prototype
                'title' => ['ar' => 'البرامج الإعلامية', 'en' => 'Media', 'fr' => 'Médias', 'tr' => 'Medya', 'ur' => 'میڈیا'],
                'description' => [
                    'ar' => 'برامج ومسلسلات وثائقية إسلامية في السيرة والقرآن والحديث.',
                    'en' => 'Islamic documentary programs and series on the Seerah, Quran, and Hadith.',
                    'fr' => 'Programmes documentaires islamiques et séries sur la Seerah, le Coran et le Hadith.',
                    'tr' => 'Siyer, Kur\'an ve Hadis üzerine İslami belgesel programlar ve diziler.',
                    'ur' => 'سیرت، قرآن اور حدیث پر اسلامی دستاویزی پروگرام۔',
                ],
            ],
            [
                'key' => 'genealogy',
                'icon' => 'git-branch',
                'target_url' => 'public.genealogy',
                'accent_color' => '#6f42c1',
                'is_featured' => false,
                'title' => ['ar' => 'شجرة العائلة', 'en' => 'Family Tree', 'fr' => 'Arbre généalogique', 'tr' => 'Soy Ağacı', 'ur' => 'خاندانی شجرہ'],
                'description' => [
                    'ar' => 'شجرة النسب النبوي الشريف وعائلة النبي ﷺ من الأجداد إلى الأحفاد.',
                    'en' => 'The Prophetic family tree from ancestors to grandchildren, fully traversable.',
                    'fr' => 'L\'arbre généalogique prophétique des ancêtres aux petits-enfants, entièrement navigable.',
                    'tr' => 'Atalardan torunlara kadar tam gezilebilir Peygamber soy ağacı.',
                    'ur' => 'نبوی خاندانی شجرہ اجداد سے پوتوں تک مکمل قابلِ تلاش۔',
                ],
            ],
            [
                'key' => 'quran',
                'icon' => 'book-open',
                'target_url' => 'public.quran',
                'accent_color' => '#17a2b8',
                'is_featured' => false,
                'title' => ['ar' => 'القرآن الكريم', 'en' => 'Quran', 'fr' => 'Coran', 'tr' => 'Kuran', 'ur' => 'قرآن'],
                'description' => [
                    'ar' => 'المصحف الشريف كاملاً مع التلاوة والتفسير والترجمات بخمس لغات.',
                    'en' => 'The complete Mushaf with recitation, tafsir, and translations in five languages.',
                    'fr' => 'Le Mushaf complet avec récitation, tafsir et traductions en cinq langues.',
                    'tr' => 'Beş dilde tilavet, tefsir ve çevirilerle tam Mushaf.',
                    'ur' => 'پانچ زبانوں میں تلاوت، تفسیر اور ترجمہ کے ساتھ مکمل مصحف۔',
                ],
            ],
            [
                'key' => 'maps',
                'icon' => 'map',
                'target_url' => 'public.seerah',
                'accent_color' => '#fd7e14',
                'is_featured' => false,
                'title' => ['ar' => 'الخرائط التفاعلية', 'en' => 'Interactive Maps', 'fr' => 'Cartes Interactives', 'tr' => 'Etkileşimli Haritalar', 'ur' => 'انٹرایکٹو نقشے'],
                'description' => [
                    'ar' => 'تتبع الأحداث التاريخية الإسلامية على الخرائط الجغرافية التفاعلية.',
                    'en' => 'Track Islamic historical events on interactive geographical maps.',
                    'fr' => 'Suivez les événements historiques islamiques sur des cartes géographiques interactives.',
                    'tr' => 'İslami tarihsel olayları etkileşimli coğrafi haritalarda takip edin.',
                    'ur' => 'انٹرایکٹو جغرافیائی نقشوں پر اسلامی تاریخی واقعات کو ٹریک کریں۔',
                ],
            ],
        ];
    }
}
