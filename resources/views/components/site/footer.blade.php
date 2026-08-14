{{--
    Site footer. Link groups, brand description and social URLs come from the
    Content domain (MenuService + SiteSettingsService) via ContentServiceProvider's
    view composer. Empty groups render nothing. Uses the shared <x-site.logo>
    lockup (same seal + wordmark as the header). Copyright line is fixed by
    product decision — not settings-driven.
--}}
@php
    $footerQuickMenu = $footerQuickMenu ?? [];
    $footerModulesMenu = $footerModulesMenu ?? [];
    $footerSocialLinks = $footerSocialLinks ?? [];
    $siteDescription = $siteDescription ?? __('site.footer_tagline');

    $menuItemUrl = function (array $item): string {
        if (!empty($item['route_name']) && Route::has($item['route_name'])) {
            return route($item['route_name']);
        }

        return $item['url'] ?? '#';
    };
@endphp

<footer class="relative bg-[#16181d] dark:bg-[#0a0a0a] text-[#DEE2E6] overflow-hidden">
    {{-- Brand gradient hairline --}}
    <div class="h-px bg-gradient-to-r from-transparent via-[#0056b3] to-transparent"></div>
    <div class="h-px bg-gradient-to-r from-[#0056b3]/0 via-[#218838]/60 to-[#64CCC5]/0 blur-[1px]"></div>

    {{-- Soft brand glow behind the brand column --}}
    <div aria-hidden="true"
         class="pointer-events-none absolute -top-32 start-0 w-[28rem] h-[28rem] rounded-full opacity-[0.07]"
         style="background: radial-gradient(circle, #0056b3 0%, #218838 45%, transparent 70%);"></div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-20 relative">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 mb-14">

            {{-- Brand --}}
            <div class="lg:col-span-2">
                <x-site.logo :eager="false" class="mb-6" />
                <p class="text-sm leading-7 text-[#ADB5BD] max-w-sm">{{ $siteDescription }}</p>

                @if (count($footerSocialLinks) > 0)
                    <div class="flex items-center gap-3 mt-8">
                        @foreach ($footerSocialLinks as $social)
                            <a href="{{ $social['url'] }}"
                               aria-label="{{ $social['label'] ?? $social['icon'] }}"
                               class="group w-10 h-10 rounded-xl border border-white/10 bg-white/5 flex items-center justify-center
                                      transition-all duration-300 hover:border-transparent hover:bg-gradient-to-br hover:from-[#0056b3] hover:to-[#218838] hover:shadow-lg hover:shadow-[#0056b3]/25 hover:-translate-y-0.5">
                                <i class="w-4.5 h-4.5 text-[#ADB5BD] group-hover:text-white transition-colors" data-lucide="{{ $social['icon'] }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Quick links --}}
            @if (count($footerQuickMenu) > 0)
                <div>
                    <h4 class="text-white font-bold mb-5 flex items-center gap-2">
                        <span class="w-6 h-px bg-gradient-to-r from-[#0056b3] to-[#218838]"></span>
                        {{ __('site.quick_links') }}
                    </h4>
                    <ul class="space-y-3.5 text-sm">
                        @foreach ($footerQuickMenu as $item)
                            <li>
                                <a href="{{ $menuItemUrl($item) }}"
                                   class="inline-block text-[#ADB5BD] hover:text-white hover:translate-x-1 rtl:hover:-translate-x-1 transition-all duration-200">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Modules --}}
            @if (count($footerModulesMenu) > 0)
                <div>
                    <h4 class="text-white font-bold mb-5 flex items-center gap-2">
                        <span class="w-6 h-px bg-gradient-to-r from-[#0056b3] to-[#218838]"></span>
                        {{ __('site.modules') }}
                    </h4>
                    <ul class="space-y-3.5 text-sm">
                        @foreach ($footerModulesMenu as $item)
                            <li>
                                <a href="{{ $menuItemUrl($item) }}"
                                   class="inline-block text-[#ADB5BD] hover:text-white hover:translate-x-1 rtl:hover:-translate-x-1 transition-all duration-200">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Follow us --}}
            @if (count($footerSocialLinks) > 0)
                <div>
                    <h4 class="text-white font-bold mb-5 flex items-center gap-2">
                        <span class="w-6 h-px bg-gradient-to-r from-[#0056b3] to-[#218838]"></span>
                        {{ __('site.follow_us') }}
                    </h4>
                    <ul class="space-y-3.5 text-sm">
                        @foreach ($footerSocialLinks as $social)
                            <li>
                                <a href="{{ $social['url'] }}"
                                   class="inline-flex items-center gap-2.5 text-[#ADB5BD] hover:text-white hover:translate-x-1 rtl:hover:-translate-x-1 transition-all duration-200">
                                    <i class="w-4 h-4" data-lucide="{{ $social['icon'] }}"></i>
                                    {{ $social['label'] ?? $social['icon'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        {{-- Copyright --}}
        <div class="pt-8 border-t border-white/10 text-center">
            <p class="text-sm text-[#ADB5BD]">© {{ date('Y') }} خاتم المرسلين. جميع الحقوق محفوظة لأمة محمد ﷺ</p>
        </div>
    </div>
</footer>
