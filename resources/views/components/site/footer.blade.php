{{--
    Ported from the-last-messenger-ui/index-enhanced.html footer.
    Link groups, brand name/description, social URLs, and the copyright line
    come from the Content domain (MenuService + SiteSettingsService) via
    ContentServiceProvider's view composer. Empty groups render nothing.
--}}
@php
    $footerQuickMenu = $footerQuickMenu ?? [];
    $footerModulesMenu = $footerModulesMenu ?? [];
    $footerSocialLinks = $footerSocialLinks ?? [];
    $siteName = $siteName ?? __('site.site_name');
    $siteDescription = $siteDescription ?? __('site.footer_tagline');
    $footerCopyright = $footerCopyright ?? __('site.all_rights_reserved');

    $menuItemUrl = function (array $item): string {
        if (!empty($item['route_name']) && Route::has($item['route_name'])) {
            return route($item['route_name']);
        }

        return $item['url'] ?? '#';
    };
@endphp

<footer class="bg-[#212529] dark:bg-[#0a0a0a] text-[#E9ECEF]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
            <div>
                <div class="flex items-center space-s-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-xl flex items-center justify-center">
                        <i class="w-8 h-8 text-white" data-lucide="book-open"></i>
                    </div>
                    <span class="text-2xl font-bold text-white">{{ $siteName }}</span>
                </div>
                <p class="text-sm text-[#DEE2E6]">{{ $siteDescription }}</p>
            </div>

            <div>
                @if (count($footerQuickMenu) > 0)
                    <h4 class="font-bold text-white mb-4">{{ __('site.quick_links') }}</h4>
                    <ul class="space-y-3 text-sm">
                        @foreach ($footerQuickMenu as $item)
                            <li><a href="{{ $menuItemUrl($item) }}" class="text-[#DEE2E6] hover:text-white transition">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                @if (count($footerModulesMenu) > 0)
                    <h4 class="font-bold text-white mb-4">{{ __('site.modules') }}</h4>
                    <ul class="space-y-3 text-sm">
                        @foreach ($footerModulesMenu as $item)
                            <li><a href="{{ $menuItemUrl($item) }}" class="text-[#DEE2E6] hover:text-white transition">{{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                @if (count($footerSocialLinks) > 0)
                    <h4 class="font-bold text-white mb-4">{{ __('site.follow_us') }}</h4>
                    <div class="flex space-s-4 mb-6">
                        @foreach ($footerSocialLinks as $social)
                            <a href="{{ $social['url'] }}"
                               aria-label="{{ $social['label'] ?? $social['icon'] }}"
                               class="w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition">
                                <i class="w-5 h-5" data-lucide="{{ $social['icon'] }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif

                <h4 class="font-bold text-white mb-3 text-sm">{{ __('site.newsletter') }}</h4>
                <form class="flex" @submit.prevent="$store.toast.push('{{ __('site.newsletter_thanks') }}')">
                    <input type="email" required placeholder="{{ __('site.newsletter_placeholder') }}"
                           class="flex-1 px-4 py-2 rounded-s-lg bg-white/10 border border-white/20 text-white placeholder-white/50 outline-none" />
                    <button type="submit" aria-label="Subscribe"
                            class="bg-gradient-to-r from-[#0056b3] to-[#218838] px-6 py-2 rounded-e-lg font-semibold hover:shadow-lg transition">
                        <i class="w-5 h-5" data-lucide="send"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="pt-8 border-t border-[#DEE2E6]/20 text-center text-sm text-[#DEE2E6]">
            <p>© {{ date('Y') }} {{ $siteName }}. {{ $footerCopyright }}.</p>
        </div>
    </div>
</footer>
