<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Content\Enums\MenuLocation;
use App\Domain\Content\Repositories\ContactMessageRepository;
use App\Domain\Content\Repositories\ContactMessageRepositoryInterface;
use App\Domain\Content\Repositories\FaqRepository;
use App\Domain\Content\Repositories\FaqRepositoryInterface;
use App\Domain\Content\Repositories\HomeContentRepository;
use App\Domain\Content\Repositories\HomeContentRepositoryInterface;
use App\Domain\Content\Repositories\HomeSectionRepository;
use App\Domain\Content\Repositories\HomeSectionRepositoryInterface;
use App\Domain\Content\Repositories\MenuItemRepository;
use App\Domain\Content\Repositories\MenuItemRepositoryInterface;
use App\Domain\Content\Repositories\SiteSettingRepository;
use App\Domain\Content\Repositories\SiteSettingRepositoryInterface;
use App\Domain\Content\Repositories\StaticPageRepository;
use App\Domain\Content\Repositories\StaticPageRepositoryInterface;
use App\Domain\Content\Services\FaqService;
use App\Domain\Content\Services\HomeContentService;
use App\Domain\Content\Services\HomeSectionService;
use App\Domain\Content\Services\MenuService;
use App\Domain\Content\Services\SiteSettingsService;
use App\Domain\Shared\Contracts\FaqPublisherInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;

class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            SiteSettingRepositoryInterface::class,
            SiteSettingRepository::class
        );

        $this->app->bind(
            MenuItemRepositoryInterface::class,
            MenuItemRepository::class
        );

        $this->app->bind(
            HomeContentRepositoryInterface::class,
            HomeContentRepository::class
        );

        $this->app->bind(
            HomeSectionRepositoryInterface::class,
            HomeSectionRepository::class
        );

        $this->app->bind(
            StaticPageRepositoryInterface::class,
            StaticPageRepository::class
        );

        $this->app->bind(
            FaqRepositoryInterface::class,
            FaqRepository::class
        );

        $this->app->bind(
            ContactMessageRepositoryInterface::class,
            ContactMessageRepository::class
        );

        // Cross-domain FAQ publishing (Shared contract) — lets the Quran "Ask AI"
        // moderation publish an inquiry as a FAQ without importing the Content domain.
        $this->app->bind(
            FaqPublisherInterface::class,
            FaqService::class
        );
    }

    /**
     * Inject DB-managed site chrome (menus, settings, home sections, search chips) into the
     * public header/footer/layout views. All reads hit the Content domain's
     * forever-cached maps, so this adds no per-request query cost.
     * Empty datasets yield empty arrays/nulls — the views render nothing.
     */
    public function boot(): void
    {
        ViewFacade::composer('public.home', function (View $view): void {
            $view->with('homeSections', $this->app->make(HomeSectionService::class)->active());
        });

        ViewFacade::composer('components.site.header', function (View $view): void {
            $view->with([
                'headerMenu' => $this->app->make(MenuService::class)->tree(MenuLocation::HEADER),
                'searchChips' => $this->app->make(HomeContentService::class)->activeChips(),
                'siteName' => $this->app->make(SiteSettingsService::class)->get('site_name'),
            ]);
        });

        ViewFacade::composer('components.site.footer', function (View $view): void {
            $menus = $this->app->make(MenuService::class);
            $settings = $this->app->make(SiteSettingsService::class);

            $view->with([
                'footerQuickMenu' => $menus->tree(MenuLocation::FOOTER_QUICK),
                'footerModulesMenu' => $menus->tree(MenuLocation::FOOTER_MODULES),
                'footerSocialLinks' => $this->socialLinks($menus, $settings),
                'siteName' => $settings->get('site_name'),
                'siteDescription' => $settings->get('site_description'),
                'footerCopyright' => $settings->get('footer_copyright'),
            ]);
        });

        ViewFacade::composer('layouts.public', function (View $view): void {
            $settings = $this->app->make(SiteSettingsService::class);

            $view->with([
                'seoSiteName' => $settings->get('site_name'),
                'seoDefaultTitle' => $settings->get('seo_default_title'),
                'seoDefaultDescription' => $settings->get('seo_default_description'),
            ]);
        });
    }

    /**
     * Footer social icons: the icon row comes from the footer_social menu;
     * each href resolves from the matching social_<icon> site setting (the
     * canonical editable URL), falling back to the menu item's own URL.
     *
     * @return array<int, array{icon: string|null, label: string|null, url: string}>
     */
    private function socialLinks(MenuService $menus, SiteSettingsService $settings): array
    {
        $links = [];

        foreach ($menus->tree(MenuLocation::FOOTER_SOCIAL) as $item) {
            $settingUrl = $item['icon'] !== null ? $settings->get('social_'.$item['icon']) : null;

            $links[] = [
                'icon' => $item['icon'],
                'label' => $item['label'],
                'url' => (is_string($settingUrl) && $settingUrl !== '' ? $settingUrl : null)
                    ?? $item['url']
                    ?? '#',
            ];
        }

        return $links;
    }
}
