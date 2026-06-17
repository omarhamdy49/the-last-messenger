{{--
    Ported verbatim from the-last-messenger-ui/index-enhanced.html (header + search modal).
    Client-side translations dict → server-side __('site.*'); darkMode → $store.theme;
    language switch → POST /language/switch (server reloads locale + dir).
--}}
@php
    $locale = app()->getLocale();

    // Nav + search chips come from the Content domain via ContentServiceProvider's
    // view composer (DB-managed, forever-cached). Defaults keep isolated renders safe.
    $headerMenu = $headerMenu ?? [];
    $searchChips = $searchChips ?? [];
    $siteName = $siteName ?? __('site.site_name');

    $menuItemUrl = function (array $item): string {
        if (!empty($item['route_name']) && Route::has($item['route_name'])) {
            return route($item['route_name']);
        }

        return $item['url'] ?? '#';
    };
    $menuItemIsActive = fn (array $item): bool => !empty($item['route_name']) && request()->routeIs($item['route_name']);

    // Chips link to a search query, a named route, or an absolute/relative URL.
    $chipUrl = function (array $chip): string {
        if (!empty($chip['target_query'])) {
            $base = Route::has('public.search') ? route('public.search') : '#';

            return $base.'?q='.urlencode($chip['target_query']);
        }

        $target = $chip['target_url'] ?? null;

        if ($target !== null && Route::has($target)) {
            return route($target);
        }

        if ($target !== null && (str_starts_with($target, 'http') || str_starts_with($target, '/'))) {
            return $target;
        }

        return '#';
    };

    $languages = [
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷'],
        'ur' => ['name' => 'اردو', 'flag' => '🇵🇰'],
    ];
    $navUrl = fn (string $route) => Route::has($route) ? route($route) : '#';
@endphp

<div x-data="{ mobileMenuOpen: false, searchOpen: false }">
    <!-- Header with Glassmorphism -->
    <header class="glass shadow-lg sticky top-0 z-50 border-b"
            :class="$store.theme.dark ? 'border-white/20' : 'border-gray-200'">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">

                <!-- Logo -->
                <div class="flex items-center space-s-4 2xl:space-s-10">
                    <a href="{{ route('public.home') }}" class="flex items-center space-s-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-xl flex items-center justify-center">
                            <i class="w-8 h-8 text-white" data-lucide="book-open"></i>
                        </div>
                        <span class="text-2xl font-bold gradient-text hidden sm:block">{{ $siteName }}</span>
                    </a>

                    <!-- Desktop Navigation -->
                    {{-- 8 items + actions don't fit between lg and xl — the hamburger covers that range --}}
                    <div class="hidden xl:flex items-baseline space-s-1 2xl:space-s-6">
                        @foreach ($headerMenu as $item)
                            <a href="{{ $menuItemUrl($item) }}"
                               class="px-2.5 2xl:px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50 transition-all {{ $menuItemIsActive($item) ? 'bg-[#E9ECEF]/50 dark:bg-[#0f3460]/50' : '' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Right side actions -->
                <div class="flex items-center space-s-3">

                    <!-- Search Icon -->
                    <button @click="searchOpen = true"
                            class="p-2.5 rounded-full hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50"
                            aria-label="{{ __('site.search') }}">
                        <i class="w-5 h-5" data-lucide="search"></i>
                    </button>

                    <!-- Language Switcher -->
                    <div class="relative" x-data="{ langOpen: false }">
                        <button @click="langOpen = !langOpen"
                                class="flex items-center space-s-2 px-3 py-2 rounded-lg hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50"
                                aria-label="Language">
                            <i class="w-5 h-5" data-lucide="globe"></i>
                            <span class="hidden sm:block text-sm font-semibold">{{ strtoupper($locale) }}</span>
                            <i class="w-4 h-4" data-lucide="chevron-down"></i>
                        </button>

                        <div x-show="langOpen"
                             @click.away="langOpen = false"
                             x-transition
                             x-cloak
                             class="absolute end-0 mt-2 w-48 bg-white dark:bg-[#16213e] rounded-lg shadow-2xl border border-[#DEE2E6] dark:border-[#0f3460] py-2 z-50">
                            @foreach ($languages as $code => $lang)
                                <form method="POST" action="{{ route('language.switch') }}">
                                    @csrf
                                    <input type="hidden" name="language" value="{{ $code }}">
                                    <button type="submit"
                                            class="w-full text-start px-4 py-2.5 hover:bg-[#E9ECEF] dark:hover:bg-[#0f3460] flex items-center space-s-3 {{ $locale === $code ? 'bg-[#E9ECEF] dark:bg-[#0f3460]' : '' }}">
                                        <span class="text-2xl">{{ $lang['flag'] }}</span>
                                        <span class="font-medium">{{ $lang['name'] }}</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button @click="$store.theme.toggle()"
                            class="p-2.5 rounded-full hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50"
                            aria-label="Toggle dark mode">
                        <i x-show="!$store.theme.dark" class="w-5 h-5" data-lucide="moon"></i>
                        <i x-show="$store.theme.dark" class="w-5 h-5" data-lucide="sun" x-cloak></i>
                    </button>

                    <!-- Notifications -->
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button @click="notifOpen = !notifOpen"
                                class="p-2.5 rounded-full hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50 relative"
                                aria-label="{{ __('site.notifications') }}">
                            <i class="w-5 h-5" data-lucide="bell"></i>
                            <span class="absolute top-1 end-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>

                        <div x-show="notifOpen"
                             @click.away="notifOpen = false"
                             x-transition
                             x-cloak
                             class="absolute end-0 mt-2 w-80 bg-white dark:bg-[#16213e] rounded-lg shadow-2xl border border-[#DEE2E6] dark:border-[#0f3460] z-50">
                            <div class="p-4 border-b border-[#DEE2E6] dark:border-[#0f3460]">
                                <h3 class="font-bold">{{ __('site.notifications') }}</h3>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <div class="p-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                                    {{ __('site.no_notifications') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User / Auth menu -->
                    @auth
                        <div class="relative" x-data="{ userOpen: false }">
                            <button @click="userOpen = !userOpen"
                                    class="p-1.5 rounded-full hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50"
                                    aria-label="{{ __('site.profile') }}">
                                @if (auth()->user()->avatarUrl())
                                    <img src="{{ auth()->user()->avatarUrl() }}"
                                         alt="{{ auth()->user()->name }}"
                                         class="w-7 h-7 rounded-full object-cover ring-2 ring-[#64CCC5]/50">
                                @else
                                    <span class="w-7 h-7 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] text-white text-xs font-bold flex items-center justify-center">
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    </span>
                                @endif
                            </button>
                            <div x-show="userOpen"
                                 @click.away="userOpen = false"
                                 x-transition
                                 x-cloak
                                 class="absolute end-0 mt-2 w-56 bg-white dark:bg-[#16213e] rounded-lg shadow-2xl border border-[#DEE2E6] dark:border-[#0f3460] py-2 z-50">
                                <div class="px-4 py-2 border-b border-[#DEE2E6] dark:border-[#0f3460]">
                                    <p class="text-sm font-bold truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                @if (Route::has('public.profile'))
                                    <a href="{{ route('public.profile') }}"
                                       class="block px-4 py-2.5 hover:bg-[#E9ECEF] dark:hover:bg-[#0f3460] text-sm">
                                        {{ __('site.profile') }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('public.logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-start px-4 py-2.5 hover:bg-[#E9ECEF] dark:hover:bg-[#0f3460] text-sm text-red-600">
                                        {{ __('site.logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('public.login') }}"
                           @click.prevent="$dispatch('open-login')"
                           class="hidden sm:inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50">
                            {{ __('site.login') }}
                        </a>
                        <a href="{{ route('public.register') }}"
                           class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-br from-[#0056b3] to-[#218838] hover:opacity-90"
                           aria-label="{{ __('site.register') }}">
                            <i class="w-5 h-5 sm:hidden" data-lucide="user"></i>
                            <span class="hidden sm:inline">{{ __('site.register') }}</span>
                        </a>
                    @endauth

                    <!-- Mobile menu button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="xl:hidden p-2.5 rounded-lg hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50"
                            aria-label="Menu">
                        <i class="w-6 h-6" data-lucide="menu"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen"
                 x-transition
                 x-cloak
                 class="xl:hidden pb-4 space-y-2">
                @foreach ($headerMenu as $item)
                    <a href="{{ $menuItemUrl($item) }}"
                       class="block px-4 py-3 rounded-lg hover:bg-[#E9ECEF]/50 dark:hover:bg-[#0f3460]/50 font-semibold {{ $menuItemIsActive($item) ? 'bg-[#E9ECEF]/50 dark:bg-[#0f3460]/50' : '' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    </header>

    <!-- Search Modal -->
    <div x-show="searchOpen"
         x-transition.opacity
         x-cloak
         @click="searchOpen = false"
         @keydown.escape.window="searchOpen = false"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-start justify-center pt-20 px-4">
        <div @click.stop
             class="w-full max-w-3xl bg-white dark:bg-[#16213e] rounded-2xl shadow-2xl p-6 animate-fade-in-up">
            <form action="{{ $navUrl('public.search') }}" method="GET">
                <div class="flex items-center space-s-4 mb-6">
                    <i class="w-6 h-6 text-gray-400" data-lucide="search"></i>
                    <input type="search"
                           name="q"
                           placeholder="{{ __('site.search_placeholder') }}"
                           class="flex-1 text-xl bg-transparent border-none outline-none"
                           x-ref="searchInput"
                           x-effect="if (searchOpen) $nextTick(() => $refs.searchInput.focus())" />
                    <button type="button" @click="searchOpen = false"
                            class="p-2 rounded-lg hover:bg-[#F8F9FA] dark:hover:bg-[#0f3460]">
                        <i class="w-5 h-5" data-lucide="x"></i>
                    </button>
                </div>
            </form>

            @if (count($searchChips) > 0)
                <div class="space-y-3">
                    <p class="text-sm text-gray-500">{{ __('site.recent_searches') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($searchChips as $chip)
                            <a href="{{ $chipUrl($chip) }}"
                               class="px-4 py-2 bg-[#F8F9FA] dark:bg-[#0f3460] rounded-full text-sm cursor-pointer hover:bg-[#E9ECEF]">{{ $chip['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
