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
    // First published live channel (MediaServiceProvider composer). Null when
    // none exists → the live quick-link is hidden rather than pointing nowhere.
    $navLiveChannel = $navLiveChannel ?? null;

    $menuItemUrl = function (array $item): string {
        if (!empty($item['route_name']) && Route::has($item['route_name'])) {
            return route($item['route_name']);
        }

        return $item['url'] ?? '#';
    };
    // Section-aware: "public.media" also matches public.media.program etc.,
    // so the nav item stays highlighted on detail pages within its section.
    $menuItemIsActive = fn (array $item): bool => !empty($item['route_name'])
        && request()->routeIs($item['route_name'], $item['route_name'].'.*');
    // A parent (dropdown) item is active when any of its children matches the route.
    $menuBranchIsActive = function (array $item) use ($menuItemIsActive): bool {
        if ($menuItemIsActive($item)) {
            return true;
        }
        foreach ($item['children'] ?? [] as $child) {
            if (! empty($child['route_name']) && request()->routeIs($child['route_name'].'*')) {
                return true;
            }
        }

        return false;
    };

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

    $languages = collect(config('app.supported_locales'))->only(enabled_content_locales())->all();
    $navUrl = fn (string $route) => Route::has($route) ? route($route) : '#';
@endphp

<div x-data="{ mobileMenuOpen: false, searchOpen: false }" class="contents">
    <!-- Header with Glassmorphism -->
    <header class="sx sticky top-0 z-50 border-b border-[color:var(--sx-line)] !bg-[color:var(--sx-surface)]/85 backdrop-blur-xl backdrop-saturate-150">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="flex items-center justify-between h-20">

                <!-- Logo -->
                <div class="flex items-center space-s-4 2xl:space-s-10">
                    <x-site.logo />

                    <!-- Desktop Navigation -->
                    {{-- 8 items + actions don't fit between lg and xl — the hamburger covers that range --}}
                    <div class="relative hidden xl:flex items-center gap-1">
                        <span class="sx-nav-indicator" data-nav-indicator style="opacity: 0" aria-hidden="true"></span>
                        @foreach ($headerMenu as $item)
                            @if (! empty($item['children']))
                                {{-- Dropdown parent (e.g. Library = articles + series) --}}
                                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @click.away="open = false">
                                    <button @click="open = true"
                                            :aria-expanded="open"
                                            @class(['sx-nav-link cursor-pointer', 'is-active' => $menuBranchIsActive($item)])>
                                        {{ $item['label'] }}
                                        <i class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" data-lucide="chevron-down"></i>
                                    </button>
                                    <div x-show="open" x-cloak x-transition.origin.top
                                         class="absolute top-full start-0 pt-2 z-50 min-w-44">
                                        <div class="sx sx-surface !rounded-2xl overflow-hidden py-1.5">
                                            @foreach ($item['children'] as $child)
                                                <a href="{{ $menuItemUrl($child) }}"
                                                   @class(['block px-4 py-2.5 text-sm font-bold transition-colors hover:bg-[color:var(--sx-accent-soft)]', 'text-[color:var(--sx-accent)] bg-[color:var(--sx-accent-soft)]' => $menuItemIsActive($child), 'text-[color:var(--sx-ink-2)]' => ! $menuItemIsActive($child)])
                                                   @if ($menuItemIsActive($child)) aria-current="page" @endif>
                                                    {{ $child['label'] }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ $menuItemUrl($item) }}"
                                   @if ($menuItemIsActive($item)) aria-current="page" @endif
                                   @class(['sx-nav-link', 'is-active' => $menuItemIsActive($item)])>
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                <!-- Right side actions -->
                <div class="flex items-center space-s-1 sm:space-s-2 lg:space-s-3">

                    <!-- Search Icon -->
                    <button @click="searchOpen = true"
                            class="p-2.5 rounded-full text-[color:var(--sx-ink-2)] transition-colors hover:bg-[color:var(--sx-accent-soft)] hover:text-[color:var(--sx-accent)]"
                            aria-label="{{ __('site.search') }}">
                        <i class="w-5 h-5" data-lucide="search"></i>
                    </button>

                    <!-- Language Switcher (action bar from sm up; in the burger menu on phones) -->
                    <div class="relative hidden sm:block" x-data="{ langOpen: false }">
                        <button @click="langOpen = !langOpen"
                                class="flex items-center space-s-2 px-3 py-2 rounded-xl text-[color:var(--sx-ink-2)] transition-colors hover:bg-[color:var(--sx-accent-soft)] hover:text-[color:var(--sx-accent)]"
                                aria-label="Language">
                            <i class="w-5 h-5" data-lucide="globe"></i>
                            <span class="hidden sm:block text-sm font-semibold">{{ strtoupper($locale) }}</span>
                            <i class="w-4 h-4" data-lucide="chevron-down"></i>
                        </button>

                        <div x-show="langOpen"
                             @click.away="langOpen = false"
                             x-transition
                             x-cloak
                             class="sx sx-surface absolute end-0 mt-2 w-48 !rounded-2xl py-2 z-50">
                            @foreach ($languages as $code => $lang)
                                <form method="POST" action="{{ route('language.switch') }}">
                                    @csrf
                                    <input type="hidden" name="language" value="{{ $code }}">
                                    <button type="submit"
                                            class="w-full text-start px-4 py-2.5 hover:bg-[color:var(--sx-accent-soft)] flex items-center space-s-3 {{ $locale === $code ? 'bg-[color:var(--sx-accent-soft)] text-[color:var(--sx-accent)] font-bold' : '' }}">
                                        <span class="text-2xl">{{ $lang['flag'] }}</span>
                                        <span class="font-medium">{{ $lang['name'] }}</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>

                    <!-- Dark Mode Toggle (action bar from sm up; in the burger menu on phones) -->
                    <button @click="$store.theme.toggle()"
                            class="hidden sm:inline-flex p-2.5 rounded-full hover:bg-[color:var(--sx-accent-soft)]"
                            aria-label="{{ __('site.toggle_theme') }}">
                        <i x-show="!$store.theme.dark" class="w-5 h-5" data-lucide="moon"></i>
                        <i x-show="$store.theme.dark" class="w-5 h-5" data-lucide="sun" x-cloak></i>
                    </button>

                    <!-- Ask Quran AI (hidden on phones — surfaced in the mobile menu instead) -->
                    <a href="{{ route('public.quran.ask') }}"
                       class="hidden sm:inline-flex p-2.5 rounded-full hover:bg-[color:var(--sx-accent-soft)] text-[color:var(--sx-accent)]"
                       aria-label="{{ __('site.ask_ai') }}" title="{{ __('site.ask_ai') }}">
                        <i class="w-5 h-5" data-lucide="sparkles"></i>
                    </a>

                    <!-- Live channel — first published one (hidden on phones — surfaced in the mobile menu instead) -->
                    @if ($navLiveChannel)
                    <a href="{{ route('public.media.live', $navLiveChannel->slug) }}"
                       class="relative hidden sm:inline-flex p-2.5 rounded-full hover:bg-[color:var(--sx-accent-soft)]"
                       aria-label="{{ __('site.makkah_live') }}" title="{{ __('site.makkah_live') }}">
                        <x-icons.kaaba class="w-5 h-5" />
                        <span class="absolute top-1.5 end-1.5 flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75 motion-safe:animate-ping"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-[#1a1a2e]"></span>
                        </span>
                    </a>
                    @endif

                    <!-- User / Auth menu -->
                    @auth
                        <div class="relative" x-data="{ userOpen: false }">
                            <button @click="userOpen = !userOpen"
                                    class="p-1.5 rounded-full hover:bg-[color:var(--sx-accent-soft)]"
                                    aria-label="{{ __('site.profile') }}">
                                @if (auth()->user()->avatarUrl())
                                    <img src="{{ auth()->user()->avatarUrl() }}"
                                         alt="{{ auth()->user()->name }}"
                                         class="w-7 h-7 rounded-full object-cover ring-2 ring-[#64CCC5]/50">
                                @else
                                    <span class="w-7 h-7 rounded-full [background:var(--sx-grad)] text-white text-xs font-bold flex items-center justify-center">
                                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                                    </span>
                                @endif
                            </button>
                            <div x-show="userOpen"
                                 @click.away="userOpen = false"
                                 x-transition
                                 x-cloak
                                 class="sx sx-surface absolute end-0 mt-2 w-56 !rounded-2xl py-2 z-50">
                                <div class="px-4 py-2 border-b border-[color:var(--sx-line)]">
                                    <p class="text-sm font-bold truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-[color:var(--sx-muted)] truncate">{{ auth()->user()->email }}</p>
                                </div>
                                @if (Route::has('public.profile'))
                                    <a href="{{ route('public.profile') }}"
                                       class="block px-4 py-2.5 hover:bg-[color:var(--sx-accent-soft)] text-sm">
                                        {{ __('site.profile') }}
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('public.logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-start px-4 py-2.5 hover:bg-[color:var(--sx-accent-soft)] text-sm text-[color:var(--sx-danger)]">
                                        {{ __('site.logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        {{-- Single auth CTA: gradient Login (opens the login modal). Register removed.
                             Compact gradient icon button on small/medium screens (prevents the label
                             from wrapping or crowding the action bar); expands to a labelled pill at lg+. --}}
                        <a href="{{ route('public.login') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-full p-2.5 lg:rounded-lg lg:px-4 lg:py-2 text-sm font-semibold text-white [background:var(--sx-grad)] hover:opacity-90 transition-all"
                           aria-label="{{ __('site.login') }}" title="{{ __('site.login') }}">
                            <i class="w-5 h-5 lg:hidden" data-lucide="log-in"></i>
                            <span class="hidden lg:inline">{{ __('site.login') }}</span>
                        </a>
                    @endauth

                    <!-- Mobile menu button -->
                    <button @click.stop="mobileMenuOpen = !mobileMenuOpen"
                            class="xl:hidden p-2.5 rounded-lg hover:bg-[color:var(--sx-accent-soft)]"
                            aria-label="Menu">
                        <i class="w-6 h-6" data-lucide="menu"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu — absolutely positioned so it overlays the page as a dropdown
                 instead of growing the sticky header and pushing content down. Anchored to
                 the bottom of the nav bar (top-full), full container width (inset-x-0), with
                 its own glass surface + shadow. Capped to the viewport so a long menu scrolls
                 internally. @click.away closes it when tapping the page behind. -->
            <div x-show="mobileMenuOpen"
                 @click.away="mobileMenuOpen = false"
                 x-transition
                 x-cloak
                 class="sx xl:hidden absolute inset-x-0 top-full z-40 border-t border-[color:var(--sx-line)] !bg-[color:var(--sx-surface)]/95 backdrop-blur-xl shadow-[var(--sx-shadow)] rounded-b-3xl px-4 sm:px-6 lg:px-8 pt-3 pb-4 space-y-2 max-h-[calc(100dvh-5rem)] overflow-y-auto overscroll-contain">
                @foreach ($headerMenu as $item)
                    @if (! empty($item['children']))
                        {{-- Grouped section: parent as a heading, children indented --}}
                        <div>
                            <div class="px-4 pt-2 pb-1 text-xs font-bold text-[color:var(--sx-muted)]">
                                {{ $item['label'] }}
                            </div>
                            @foreach ($item['children'] as $child)
                                <a href="{{ $menuItemUrl($child) }}"
                                   @class(['block ps-8 pe-4 py-3 rounded-xl font-bold transition-colors hover:bg-[color:var(--sx-accent-soft)]', 'text-[color:var(--sx-accent)] bg-[color:var(--sx-accent-soft)]' => $menuItemIsActive($child)])>
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a href="{{ $menuItemUrl($item) }}"
                           @class(['block px-4 py-3 rounded-xl font-bold transition-colors hover:bg-[color:var(--sx-accent-soft)]', 'text-[color:var(--sx-accent)] bg-[color:var(--sx-accent-soft)]' => $menuItemIsActive($item)])>
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
                {{-- Secondary actions hidden from the action bar on phones --}}
                <a href="{{ route('public.quran.ask') }}"
                   class="sm:hidden flex items-center space-s-3 px-4 py-3 rounded-lg hover:bg-[color:var(--sx-accent-soft)] font-semibold text-[color:var(--sx-accent)]">
                    <i class="w-5 h-5" data-lucide="sparkles"></i><span>{{ __('site.ask_ai') }}</span>
                </a>
                @if ($navLiveChannel)
                <a href="{{ route('public.media.live', $navLiveChannel->slug) }}"
                   class="sm:hidden flex items-center space-s-3 px-4 py-3 rounded-lg hover:bg-[color:var(--sx-accent-soft)] font-semibold">
                    <x-icons.kaaba class="w-5 h-5" /><span>{{ __('site.makkah_live') }}</span>
                </a>
                @endif

                {{-- Appearance + language — surfaced here on phones (they sit in the action bar from sm up) --}}
                <div class="sm:hidden pt-2 mt-2 space-y-2 border-t" :class="$store.theme.dark ? 'border-white/15' : 'border-[#DEE2E6]'">
                    {{-- Dark mode toggle --}}
                    <button @click="$store.theme.toggle()"
                            class="w-full flex items-center space-s-3 px-4 py-3 rounded-lg hover:bg-[color:var(--sx-accent-soft)] font-semibold">
                        <i x-show="!$store.theme.dark" class="w-5 h-5" data-lucide="moon"></i>
                        <i x-show="$store.theme.dark" class="w-5 h-5" data-lucide="sun" x-cloak></i>
                        <span>{{ __('site.toggle_theme') }}</span>
                    </button>

                    {{-- Language options --}}
                    <div class="px-4 pt-1">
                        <p class="mb-1.5 flex items-center space-s-2 text-xs font-semibold text-[color:var(--sx-muted)]">
                            <i class="w-4 h-4" data-lucide="globe"></i><span>{{ __('site.language') }}</span>
                        </p>
                        <div class="grid grid-cols-2 gap-1.5">
                            @foreach ($languages as $code => $lang)
                                <form method="POST" action="{{ route('language.switch') }}">
                                    @csrf
                                    <input type="hidden" name="language" value="{{ $code }}">
                                    <button type="submit"
                                            class="w-full flex items-center space-s-2 text-start px-3 py-2.5 rounded-lg hover:bg-[color:var(--sx-accent-soft)] {{ $locale === $code ? 'bg-[color:var(--sx-accent-soft)] text-[color:var(--sx-accent)] font-bold' : '' }}">
                                        <span class="text-lg">{{ $lang['flag'] }}</span>
                                        <span class="text-sm font-medium">{{ $lang['name'] }}</span>
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
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
             class="sx sx-surface w-full max-w-3xl p-6 sx-rise">
            <form action="{{ $navUrl('public.search') }}" method="GET">
                <div class="flex items-center space-s-4 mb-6">
                    <i class="w-6 h-6 text-[color:var(--sx-muted)]" data-lucide="search"></i>
                    <input type="search"
                           name="q"
                           placeholder="{{ __('site.search_placeholder') }}"
                           class="flex-1 text-xl bg-transparent border-none outline-none"
                           x-ref="searchInput"
                           x-effect="if (searchOpen) $nextTick(() => $refs.searchInput.focus())" />
                    <button type="button" @click="searchOpen = false"
                            class="p-2 rounded-lg hover:bg-[color:var(--sx-accent-soft)]">
                        <i class="w-5 h-5" data-lucide="x"></i>
                    </button>
                </div>
            </form>

            @if (count($searchChips) > 0)
                <div class="space-y-3">
                    <p class="text-sm text-[color:var(--sx-muted)]">{{ __('site.recent_searches') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($searchChips as $chip)
                            <a href="{{ $chipUrl($chip) }}"
                               class="sx-chip">{{ $chip['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
