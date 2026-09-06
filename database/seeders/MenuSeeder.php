<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Content\Enums\MenuLocation;
use App\Domain\Content\Models\MenuItem;
use App\Domain\Content\Services\MenuService;
use Illuminate\Database\Seeder;

/**
 * MenuSeeder
 *
 * Seeds the header nav and footer link groups with the CURRENT hardcoded
 * items from components/site/header.blade.php and footer.blade.php (route
 * names verbatim, labels from resources/lang/{ar,en,fr,tr,ur}/site.php),
 * so the view-wiring task swaps cleanly. Idempotent: items are matched by
 * (location, route_name) — social icons by (location, icon).
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->menus() as $location => $items) {
            $this->seedLocation($location, $items);
        }

        app(MenuService::class)->flush();

        $this->command?->info('Menu items seeded: '.MenuItem::query()->count().' items.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function seedLocation(string $location, array $items): void
    {
        foreach ($items as $index => $item) {
            $menuItem = $this->seedItem($location, $item, ($index + 1) * 10, null);

            foreach ($item['children'] ?? [] as $childIndex => $child) {
                $this->seedItem($location, $child, ($childIndex + 1) * 10, $menuItem->id);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function seedItem(string $location, array $item, int $sortOrder, ?int $parentId): MenuItem
    {
        $match = ['location' => $location];
        if (isset($item['route_name'])) {
            $match['route_name'] = $item['route_name'];
        } else {
            $match['icon'] = $item['icon'];
        }

        /** @var MenuItem $menuItem */
        $menuItem = MenuItem::query()->updateOrCreate($match, [
            'route_name' => $item['route_name'] ?? null,
            'url' => $item['url'] ?? null,
            'icon' => $item['icon'] ?? null,
            'is_active' => true,
            'sort_order' => $sortOrder,
            'parent_id' => $parentId,
        ]);

        $menuItem->syncTranslations(['label' => $item['labels']]);

        return $menuItem;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function menus(): array
    {
        $navSeerah = ['ar' => 'السيرة', 'en' => 'Biography', 'fr' => 'Biographie', 'tr' => 'Siyer', 'ur' => 'سیرت'];
        $navHadith = ['ar' => 'الحديث', 'en' => 'Hadith', 'fr' => 'Hadith', 'tr' => 'Hadis', 'ur' => 'حدیث'];
        $navMedia = ['ar' => 'المرئيات', 'en' => 'Videos', 'fr' => 'Vidéos', 'tr' => 'Videolar', 'ur' => 'ویڈیوز'];
        $navGenealogy = ['ar' => 'شجرة النسب', 'en' => 'Family Tree', 'fr' => 'Arbre Généalogique', 'tr' => 'Soy Ağacı', 'ur' => 'خاندانی درخت'];
        // site.nav_topics — the /topics section had no header entry, so it was
        // reachable only by typing the URL.
        $navTopics = ['ar' => 'المواضيع', 'en' => 'Topics', 'fr' => 'Thèmes', 'tr' => 'Konular', 'ur' => 'موضوعات'];

        return [
            // Header nav — order and routes verbatim from header.blade.php $nav.
            MenuLocation::HEADER->value => [
                ['route_name' => 'public.home', 'labels' => ['ar' => 'الرئيسية', 'en' => 'Home', 'fr' => 'Accueil', 'tr' => 'Ana Sayfa', 'ur' => 'ہوم']],
                ['route_name' => 'public.seerah', 'labels' => $navSeerah],
                // Library dropdown: articles + series grouped under one parent.
                ['icon' => 'library', 'labels' => ['ar' => 'المكتبة', 'en' => 'Library', 'fr' => 'Bibliothèque', 'tr' => 'Kütüphane', 'ur' => 'کتب خانہ'], 'children' => [
                    ['route_name' => 'public.articles', 'labels' => ['ar' => 'المقالات', 'en' => 'Articles', 'fr' => 'Articles', 'tr' => 'Makaleler', 'ur' => 'مضامین']],
                    ['route_name' => 'public.series', 'labels' => ['ar' => 'السلاسل', 'en' => 'Series', 'fr' => 'Séries', 'tr' => 'Seriler', 'ur' => 'سلسلے']],
                ]],
                ['route_name' => 'public.hadith', 'labels' => $navHadith],
                ['route_name' => 'public.media', 'labels' => $navMedia],
                ['route_name' => 'public.genealogy', 'labels' => $navGenealogy],
                ['route_name' => 'public.quran', 'labels' => ['ar' => 'القرآن', 'en' => 'Quran', 'fr' => 'Coran', 'tr' => 'Kuran', 'ur' => 'قرآن']],
                ['route_name' => 'public.topics', 'labels' => $navTopics],
            ],

            // Footer "Quick Links" column — verbatim from footer.blade.php.
            MenuLocation::FOOTER_QUICK->value => [
                ['route_name' => 'public.about', 'labels' => ['ar' => 'عن المشروع', 'en' => 'About the Project', 'fr' => 'À propos du projet', 'tr' => 'Proje Hakkında', 'ur' => 'منصوبے کے بارے میں']],
                ['route_name' => 'public.contact', 'labels' => ['ar' => 'تواصل معنا', 'en' => 'Contact Us', 'fr' => 'Contact', 'tr' => 'İletişim', 'ur' => 'رابطہ کریں']],
                ['route_name' => 'public.privacy', 'labels' => ['ar' => 'سياسة الخصوصية', 'en' => 'Privacy Policy', 'fr' => 'Politique de confidentialité', 'tr' => 'Gizlilik Politikası', 'ur' => 'رازداری کی پالیسی']],
                ['route_name' => 'public.terms', 'labels' => ['ar' => 'شروط الاستخدام', 'en' => 'Terms of Use', 'fr' => 'Conditions d\'utilisation', 'tr' => 'Kullanım Koşulları', 'ur' => 'استعمال کی شرائط']],
            ],

            // Footer "Modules" column — verbatim from footer.blade.php.
            MenuLocation::FOOTER_MODULES->value => [
                ['route_name' => 'public.seerah', 'labels' => $navSeerah],
                ['route_name' => 'public.hadith', 'labels' => $navHadith],
                ['route_name' => 'public.media', 'labels' => $navMedia],
                ['route_name' => 'public.genealogy', 'labels' => $navGenealogy],
            ],

            // Footer social icon row — hrefs are '#' placeholders in the blade;
            // the canonical editable URLs live in site settings (social_* keys).
            MenuLocation::FOOTER_SOCIAL->value => [
                ['icon' => 'facebook', 'url' => '#', 'labels' => $this->sameLabel('Facebook')],
                ['icon' => 'twitter', 'url' => '#', 'labels' => $this->sameLabel('Twitter')],
                ['icon' => 'youtube', 'url' => '#', 'labels' => $this->sameLabel('YouTube')],
                ['icon' => 'instagram', 'url' => '#', 'labels' => $this->sameLabel('Instagram')],
            ],
        ];
    }

    /**
     * Brand names are identical across locales.
     *
     * @return array<string, string>
     */
    private function sameLabel(string $label): array
    {
        return ['ar' => $label, 'en' => $label, 'fr' => $label, 'tr' => $label, 'ur' => $label];
    }
}
