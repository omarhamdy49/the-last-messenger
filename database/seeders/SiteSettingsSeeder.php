<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Enums\SiteSettingType;
use App\Domain\Content\Models\SiteSetting;
use App\Domain\Content\Services\SiteSettingsService;
use Illuminate\Database\Seeder;

/**
 * SiteSettingsSeeder
 *
 * Seeds the global site chrome with the CURRENT hardcoded values from
 * resources/lang/{ar,en,fr,tr,ur}/site.php and the footer blade, so the
 * later view-wiring task swaps __() calls for setting reads without any
 * visible change. Idempotent: settings are upserted by key.
 */
class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->settings() as $sortOrder => $setting) {
            $this->upsert($setting, $sortOrder);
        }

        // One explicit flush so the cached settings map rebuilds after seeding.
        app(SiteSettingsService::class)->flush();

        $this->command?->info('Site settings seeded: '.SiteSetting::query()->count().' keys.');
    }

    /**
     * @param  array<string, mixed>  $setting
     */
    private function upsert(array $setting, int $sortOrder): void
    {
        /** @var SiteSetting $model */
        $model = SiteSetting::query()->updateOrCreate(
            ['key' => $setting['key']],
            [
                'group' => $setting['group'],
                'type' => $setting['type'],
                'value' => $setting['value'] ?? null,
                'is_translatable' => isset($setting['translations']),
                'sort_order' => $sortOrder * 10,
            ]
        );

        if (isset($setting['translations'])) {
            $model->syncTranslations(['value' => $setting['translations']]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function settings(): array
    {
        return [
            [
                'key' => 'site_name',
                'group' => 'general',
                'type' => SiteSettingType::TEXT->value,
                'translations' => [
                    'ar' => 'خاتَمُ المُرْسَلِين',
                    'en' => 'The Last Messenger',
                    'fr' => 'Le Dernier Messager',
                    'tr' => 'Son Peygamber',
                    'ur' => 'خاتم المرسلین',
                ],
            ],
            [
                'key' => 'site_description',
                'group' => 'general',
                'type' => SiteSettingType::TEXTAREA->value,
                'translations' => [
                    'ar' => 'مشروع معرفي يهدف لتقديم محتوى إسلامي موثوق بأسلوب عصري وتفاعلي.',
                    'en' => 'A knowledge project aimed at providing trusted Islamic content in a modern and interactive style.',
                    'fr' => 'Un projet de connaissance visant à fournir un contenu islamique fiable dans un style moderne et interactif.',
                    'tr' => 'Modern ve etkileşimli bir tarzda güvenilir İslami içerik sunmayı amaçlayan bir bilgi projesi.',
                    'ur' => 'ایک علمی منصوبہ جس کا مقصد جدید اور انٹرایکٹو انداز میں مستند اسلامی مواد فراہم کرنا ہے۔',
                ],
            ],
            [
                'key' => 'seo_default_title',
                'group' => 'seo',
                'type' => SiteSettingType::TEXT->value,
                'translations' => [
                    'ar' => 'الموسوعة الشاملة لخاتَمُ المُرْسَلِين ﷺ',
                    'en' => 'Comprehensive Encyclopedia of The Last Messenger ﷺ',
                    'fr' => 'Encyclopédie Complète du Dernier Messager ﷺ',
                    'tr' => 'Son Peygamber\'in Kapsamlı Ansiklopedisi ﷺ',
                    'ur' => 'خاتم المرسلین ﷺ کا جامع انسائیکلوپیڈیا',
                ],
            ],
            [
                'key' => 'seo_default_description',
                'group' => 'seo',
                'type' => SiteSettingType::TEXTAREA->value,
                'translations' => [
                    'ar' => 'مصدرك الموثوق لاستكشاف السيرة النبوية، الحديث الشريف، والمحتوى الإسلامي المرئي بأسلوب عصري وتفاعلي.',
                    'en' => 'Your trusted source to explore the Prophetic biography, Hadith, and Islamic content in a modern and interactive way.',
                    'fr' => 'Votre source fiable pour explorer la biographie prophétique, les Hadiths et le contenu islamique de manière moderne et interactive.',
                    'tr' => 'Peygamber biyografisini, Hadisleri ve İslami içeriği modern ve etkileşimli bir şekilde keşfetmek için güvenilir kaynağınız.',
                    'ur' => 'سیرتِ نبوی، حدیث اور اسلامی مواد کو جدید انداز میں دریافت کرنے کا آپ کا قابلِ اعتماد ذریعہ۔',
                ],
            ],
            // Footer social hrefs are currently '#' placeholders in
            // components/site/footer.blade.php — kept as-is for admins to fill in.
            [
                'key' => 'social_facebook',
                'group' => 'social',
                'type' => SiteSettingType::URL->value,
                'value' => '#',
            ],
            [
                'key' => 'social_twitter',
                'group' => 'social',
                'type' => SiteSettingType::URL->value,
                'value' => '#',
            ],
            [
                'key' => 'social_youtube',
                'group' => 'social',
                'type' => SiteSettingType::URL->value,
                'value' => '#',
            ],
            [
                'key' => 'social_instagram',
                'group' => 'social',
                'type' => SiteSettingType::URL->value,
                'value' => '#',
            ],
            [
                'key' => 'contact_email',
                'group' => 'contact',
                'type' => SiteSettingType::TEXT->value,
                'value' => 'contact@thelastmessenger.com',
            ],
            [
                'key' => 'footer_copyright',
                'group' => 'footer',
                'type' => SiteSettingType::TEXT->value,
                'translations' => [
                    'ar' => 'جميع الحقوق محفوظة',
                    'en' => 'All Rights Reserved',
                    'fr' => 'Tous droits réservés',
                    'tr' => 'Tüm Hakları Saklıdır',
                    'ur' => 'تمام حقوق محفوظ ہیں',
                ],
            ],
            // How many children/spouses the public family-tree page shows
            // per level before curation (admins pick which ones make the
            // cut via each person's/relationship's display order + hide
            // flag — see Genealogy\PersonRepository).
            [
                'key' => 'genealogy_tree_max_children_per_level',
                'group' => 'genealogy_tree',
                'type' => SiteSettingType::TEXT->value,
                'value' => '10',
            ],
            [
                'key' => 'genealogy_tree_max_spouses_shown',
                'group' => 'genealogy_tree',
                'type' => SiteSettingType::TEXT->value,
                'value' => '12',
            ],
        ];
    }
}
