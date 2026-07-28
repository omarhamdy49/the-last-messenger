<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Models\HomeSection;
use App\Domain\Content\Models\HomeSectionTranslation;
use App\Domain\Content\Services\HomeSectionService;
use Illuminate\Database\Seeder;

final class HomeSectionsSeeder extends Seeder
{
    private const LOCALES = ['ar', 'en', 'fr', 'tr', 'ur'];

    public function run(): void
    {
        foreach ($this->sections() as $displayOrder => $section) {
            $model = HomeSection::query()->firstOrCreate(
                ['key' => $section['key']],
                ['is_active' => true, 'display_order' => $displayOrder],
            );

            foreach (self::LOCALES as $locale) {
                HomeSectionTranslation::query()->firstOrCreate(
                    ['home_section_id' => $model->id, 'locale' => $locale],
                    [
                        'heading' => $section['heading'][$locale] ?? null,
                        'subheading' => $section['subheading'][$locale] ?? null,
                        'is_ready' => filled($section['heading'][$locale] ?? null)
                            || filled($section['subheading'][$locale] ?? null),
                    ],
                );
            }
        }

        app(HomeSectionService::class)->flush();
    }

    /** @return array<int, array{key: string, heading: array<string, string>, subheading: array<string, string>}> */
    private function sections(): array
    {
        return [
            1 => $this->hero(),
            2 => ['key' => 'hero_cards', 'heading' => [], 'subheading' => []],
            3 => $this->sahabaCarousel(),
            4 => $this->sealBand(),
            5 => $this->daily(),
            6 => $this->explore(),
            7 => $this->featuredContent(),
            8 => $this->cta(),
        ];
    }

    /** @return array{key: string, heading: array<string, string>, subheading: array<string, string>} */
    private function hero(): array
    {
        return [
            'key' => 'hero',
            'heading' => [
                'ar' => 'الموسوعة الشاملة لخاتَمُ المُرْسَلِين ﷺ',
                'en' => 'Comprehensive Encyclopedia of The Last Messenger ﷺ',
                'fr' => 'Encyclopédie Complète du Dernier Messager ﷺ',
                'tr' => 'Son Peygamber\'in Kapsamlı Ansiklopedisi ﷺ',
                'ur' => 'خاتم المرسلین ﷺ کا جامع انسائیکلوپیڈیا',
            ],
            'subheading' => [
                'ar' => 'مصدرك الموثوق لاستكشاف السيرة النبوية، الحديث الشريف، والمحتوى الإسلامي المرئي بأسلوب عصري وتفاعلي.',
                'en' => 'Your trusted source to explore the Prophetic biography, Hadith, and Islamic content in a modern and interactive way.',
                'fr' => 'Votre source fiable pour explorer la biographie prophétique, les Hadiths et le contenu islamique de manière moderne et interactive.',
                'tr' => 'Peygamber biyografisini, Hadisleri ve İslami içeriği modern ve etkileşimli bir şekilde keşfetmek için güvenilir kaynağınız.',
                'ur' => 'سیرتِ نبوی، حدیث اور اسلامی مواد کو جدید انداز میں دریافت کرنے کا آپ کا قابلِ اعتماد ذریعہ۔',
            ],
        ];
    }

    /** @return array{key: string, heading: array<string, string>, subheading: array<string, string>} */
    private function sahabaCarousel(): array
    {
        return [
            'key' => 'sahaba_carousel',
            'heading' => [
                'ar' => 'صحابة رسول الله ﷺ',
                'en' => 'Companions of the Messenger ﷺ',
                'fr' => 'Compagnons du Messager ﷺ',
                'tr' => 'Resûlullah\'ın Sahabeleri ﷺ',
                'ur' => 'رسول اللہ ﷺ کے صحابہ',
            ],
            'subheading' => [
                'ar' => 'تعرّف على سير وقصص أصحاب النبي الكريم رضوان الله عليهم',
                'en' => 'Learn about the lives and stories of the Prophet\'s noble Companions, may Allah be pleased with them',
                'fr' => 'Découvrez les vies et histoires des nobles Compagnons du Prophète',
                'tr' => 'Peygamberin asil Sahabelerinin hayatlarını ve hikayelerini öğrenin',
                'ur' => 'نبی کریم ﷺ کے اصحاب کی زندگیاں اور کہانیاں جانیں',
            ],
        ];
    }

    /** @return array{key: string, heading: array<string, string>, subheading: array<string, string>} */
    private function sealBand(): array
    {
        return [
            'key' => 'seal_band',
            'heading' => [
                'ar' => 'محمد رسول الله ﷺ',
                'en' => 'Muhammad, Messenger of Allah ﷺ',
                'fr' => 'Muhammad, Messager d\'Allah ﷺ',
                'tr' => 'Muhammed, Allah\'ın Elçisi ﷺ',
                'ur' => 'محمد رسول اللہ ﷺ',
            ],
            'subheading' => array_fill_keys(self::LOCALES, 'Muhammad, Messenger of Allah'),
        ];
    }

    /** @return array{key: string, heading: array<string, string>, subheading: array<string, string>} */
    private function daily(): array
    {
        return [
            'key' => 'daily',
            'heading' => [
                'ar' => 'آية اليوم',
                'en' => 'Verse of the Day',
                'fr' => 'Verset du Jour',
                'tr' => 'Günün Ayeti',
                'ur' => 'آج کی آیت',
            ],
            'subheading' => [
                'ar' => 'الحديث الشريف',
                'en' => 'Hadith',
                'fr' => 'Hadith',
                'tr' => 'Hadis',
                'ur' => 'حدیث',
            ],
        ];
    }

    /** @return array{key: string, heading: array<string, string>, subheading: array<string, string>} */
    private function explore(): array
    {
        return [
            'key' => 'explore',
            'heading' => [
                'ar' => 'استكشف الموسوعة',
                'en' => 'Explore the Encyclopedia',
                'fr' => 'Explorer l\'Encyclopédie',
                'tr' => 'Ansiklopediyi Keşfet',
                'ur' => 'انسائیکلوپیڈیا دریافت کریں',
            ],
            'subheading' => [
                'ar' => 'اختر من بين مجموعة متنوعة من المصادر الإسلامية الموثوقة',
                'en' => 'Choose from a variety of trusted Islamic sources',
                'fr' => 'Choisissez parmi diverses sources islamiques fiables',
                'tr' => 'Güvenilir İslami kaynaklardan seçim yapın',
                'ur' => 'قابلِ اعتماد اسلامی ذرائع سے انتخاب کریں',
            ],
        ];
    }

    /** @return array{key: string, heading: array<string, string>, subheading: array<string, string>} */
    private function featuredContent(): array
    {
        return [
            'key' => 'featured_content',
            'heading' => [
                'ar' => 'محتوى مميز',
                'en' => 'Featured Content',
                'fr' => 'Contenu en Vedette',
                'tr' => 'Öne Çıkan İçerik',
                'ur' => 'نمایاں مواد',
            ],
            'subheading' => [
                'ar' => 'مقالات وبرامج مختارة بعناية',
                'en' => 'Carefully selected articles and programs',
                'fr' => 'Articles et programmes soigneusement sélectionnés',
                'tr' => 'Özenle seçilmiş makaleler ve programlar',
                'ur' => 'احتیاط سے منتخب مضامین اور پروگرام',
            ],
        ];
    }

    /** @return array{key: string, heading: array<string, string>, subheading: array<string, string>} */
    private function cta(): array
    {
        return [
            'key' => 'cta',
            'heading' => [
                'ar' => 'انضم إلى مجتمعنا',
                'en' => 'Join Our Community',
                'fr' => 'Rejoignez Notre Communauté',
                'tr' => 'Topluluğumuza Katıl',
                'ur' => 'ہماری برادری سے جڑیں',
            ],
            'subheading' => [
                'ar' => 'احصل على تحديثات حول المحتوى الجديد، وشارك في النقاشات، واحفظ المحتوى المفضل لديك',
                'en' => 'Get updates on new content, participate in discussions, and save your favorite content',
                'fr' => 'Recevez des mises à jour sur le nouveau contenu, participez aux discussions et sauvegardez vos favoris',
                'tr' => 'Yeni içerik güncellemeleri alın, tartışmalara katılın ve favori içeriğinizi kaydedin',
                'ur' => 'نئے مواد کی اپ ڈیٹ حاصل کریں، مباحثوں میں حصہ لیں اور پسندیدہ مواد محفوظ کریں',
            ],
        ];
    }
}
