{{--
    Public Header Component

    Props:
    - $activeMenu (string): Currently active menu item
    - $showSearch (bool): Show search button
    - $authenticated (bool): User authentication status

    Usage:
    <x-public.header activeMenu="seerah" :showSearch="true" :authenticated="auth()->check()" />
--}}

@props([
    'activeMenu' => '',
    'showSearch' => true,
    'authenticated' => false
])

<header {{ $attributes->merge(['class' => 'fixed top-0 left-0 right-0 z-50 transition-all duration-300']) }}
        x-data="{
            scrolled: false,
            mobileMenuOpen: false,
            langOpen: false,
            searchOpen: false
        }"
        @scroll.window="scrolled = window.pageYOffset > 50"
        :class="scrolled ? 'glass shadow-lg' : 'bg-transparent'">

    <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo & Main Navigation --}}
            <div class="flex items-center gap-8">
                {{-- Logo --}}
                <a href="{{ route('home') }}"
                   class="text-2xl font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent hover:scale-110 transition-transform duration-300">
                    {{ __('site.name') }}
                </a>

                {{-- Desktop Navigation --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('seerah.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeMenu === 'seerah' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
                       :class="darkMode ? 'text-gray-300 hover:text-white hover:bg-white/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'">
                        {{ __('site.seerah') }}
                    </a>

                    <a href="{{ route('hadith.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeMenu === 'hadith' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
                       :class="darkMode ? 'text-gray-300 hover:text-white hover:bg-white/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'">
                        {{ __('site.hadith') }}
                    </a>

                    <a href="{{ route('media.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeMenu === 'media' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
                       :class="darkMode ? 'text-gray-300 hover:text-white hover:bg-white/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'">
                        {{ __('site.media') }}
                    </a>

                    <a href="{{ route('quran.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeMenu === 'quran' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
                       :class="darkMode ? 'text-gray-300 hover:text-white hover:bg-white/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'">
                        {{ __('site.quran') }}
                    </a>

                    <a href="{{ route('genealogy.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeMenu === 'genealogy' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
                       :class="darkMode ? 'text-gray-300 hover:text-white hover:bg-white/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'">
                        {{ __('site.genealogy') }}
                    </a>

                    <a href="{{ route('sahaba.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 {{ $activeMenu === 'sahaba' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
                       :class="darkMode ? 'text-gray-300 hover:text-white hover:bg-white/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-200'">
                        {{ __('site.sahaba') }}
                    </a>
                </div>
            </div>

            {{-- Right Actions --}}
            <div class="hidden md:flex items-center gap-3">

                {{-- Search Button --}}
                @if($showSearch)
                <button @click="searchOpen = true"
                        class="p-2 rounded-full hover:bg-white/10 text-white hover:scale-110 transition-all duration-200"
                        aria-label="{{ __('site.search') }}">
                    <i class="w-5 h-5" data-lucide="search"></i>
                </button>
                @endif

                {{-- Notifications --}}
                @auth
                <button class="p-2 rounded-full hover:bg-white/10 relative hover:scale-110 transition-all duration-200"
                        :class="darkMode ? 'text-white' : 'text-gray-700'"
                        aria-label="{{ __('site.notifications') }}">
                    <i class="w-5 h-5" data-lucide="bell"></i>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                    <span class="absolute top-1 end-1 w-2 h-2 bg-[#64CCC5] rounded-full animate-pulse"></span>
                    @endif
                </button>
                @endauth

                {{-- Dark Mode Toggle --}}
                <button @click="darkMode = !darkMode; lucide.createIcons();"
                        class="p-2 rounded-full hover:bg-white/10 hover:scale-110 transition-all duration-200"
                        :class="darkMode ? 'text-white' : 'text-gray-700'"
                        :aria-label="darkMode ? '{{ __('site.light_mode') }}' : '{{ __('site.dark_mode') }}'">
                    <i x-show="!darkMode" class="w-5 h-5" data-lucide="moon"></i>
                    <i x-show="darkMode" class="w-5 h-5" data-lucide="sun"></i>
                </button>

                {{-- Language Dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="px-3 py-2 rounded-full hover:bg-white/10 text-white text-sm font-medium hover:scale-105 transition-all duration-200">
                        <i class="w-4 h-4 inline-block me-1" data-lucide="languages"></i>
                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                    </button>

                    <div x-show="open"
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute end-0 mt-2 w-48 glass rounded-xl shadow-2xl py-2 z-50">

                        @foreach(['ar' => 'العربية', 'en' => 'English', 'tr' => 'Türkçe', 'fr' => 'Français', 'ur' => 'اردو'] as $locale => $label)
                        <a href="{{ route('locale.switch', $locale) }}"
                           class="block w-full text-start px-4 py-2 text-sm text-white hover:bg-white/10 transition {{ app()->getLocale() === $locale ? 'bg-white/20' : '' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- User Profile / Login --}}
                @auth
                <a href="{{ route('profile.show') }}"
                   class="p-2 rounded-full hover:bg-white/10 hover:scale-110 transition-all duration-200">
                    <i class="w-6 h-6 text-white" data-lucide="circle-user"></i>
                </a>
                @else
                <a href="{{ route('login') }}"
                   class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-full font-semibold hover:scale-105 transition-all duration-200 shadow-lg">
                    {{ __('auth.login') }}
                </a>
                @endauth
            </div>

            {{-- Mobile Menu Button --}}
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-2 rounded-lg hover:bg-white/10 text-white"
                    aria-label="{{ __('site.menu') }}">
                <i class="w-6 h-6" data-lucide="menu"></i>
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="md:hidden py-4 border-t border-white/10">

            <div class="space-y-2">
                <a href="{{ route('seerah.index') }}"
                   class="block px-4 py-2 rounded-lg text-white hover:bg-white/10">
                    {{ __('site.seerah') }}
                </a>
                <a href="{{ route('hadith.index') }}"
                   class="block px-4 py-2 rounded-lg text-white hover:bg-white/10">
                    {{ __('site.hadith') }}
                </a>
                <a href="{{ route('media.index') }}"
                   class="block px-4 py-2 rounded-lg text-white hover:bg-white/10">
                    {{ __('site.media') }}
                </a>
                <a href="{{ route('quran.index') }}"
                   class="block px-4 py-2 rounded-lg text-white hover:bg-white/10">
                    {{ __('site.quran') }}
                </a>
                <a href="{{ route('genealogy.index') }}"
                   class="block px-4 py-2 rounded-lg text-white hover:bg-white/10">
                    {{ __('site.genealogy') }}
                </a>
                <a href="{{ route('sahaba.index') }}"
                   class="block px-4 py-2 rounded-lg text-white hover:bg-white/10">
                    {{ __('site.sahaba') }}
                </a>
            </div>
        </div>
    </nav>

    {{-- Search Modal --}}
    @if($showSearch)
    <x-modals.search-modal />
    @endif
</header>
