<!-- Admin Header Component -->
<header class="sticky top-0 z-30 glass border-b"
        :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">

    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 py-4">

        <!-- Left Section: Hamburger + Breadcrumbs -->
        <div class="flex items-center gap-4 flex-1">

            <!-- Hamburger Menu Button -->
            <button @click="toggleSidebar"
                    class="p-2 rounded-xl transition-all hover:scale-110"
                    :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>

            <!-- Breadcrumbs -->
            <nav class="hidden md:flex items-center gap-2 text-sm">
                @if(isset($breadcrumbs) && is_array($breadcrumbs))
                    @foreach($breadcrumbs as $index => $crumb)
                        @if($index > 0)
                            <i data-lucide="chevron-{{ app()->getLocale() === 'ar' || app()->getLocale() === 'ur' ? 'left' : 'right' }}"
                               class="w-4 h-4"
                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        @endif

                        @if($loop->last)
                            <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $crumb['label'] }}
                            </span>
                        @else
                            <a href="{{ $crumb['url'] }}"
                               class="hover:text-accent transition-colors"
                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $crumb['label'] }}
                            </a>
                        @endif
                    @endforeach
                @else
                    <a href="{{ route('admin.dashboard') }}"
                       class="hover:text-accent transition-colors"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        {{ __('admin.dashboard') }}
                    </a>
                    <i data-lucide="chevron-{{ app()->getLocale() === 'ar' || app()->getLocale() === 'ur' ? 'left' : 'right' }}"
                       class="w-4 h-4"
                       :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                    <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $pageTitle ?? __('admin.dashboard') }}
                    </span>
                @endif
            </nav>

        </div>

        <!-- Right Section: Search, Actions, User -->
        <div class="flex items-center gap-3">

            <!-- Search Bar -->
            <div x-data="{ searchOpen: false, searchQuery: '' }"
                 class="relative hidden lg:block">

                <!-- Search Input -->
                <div class="relative">
                    <input type="text"
                           x-model="searchQuery"
                           @focus="searchOpen = true"
                           @blur="setTimeout(() => searchOpen = false, 200)"
                           placeholder="{{ __('admin.search') }}"
                           class="w-64 px-4 py-2 ps-10 rounded-xl border transition-all focus:ring-2 focus:ring-accent focus:border-accent"
                           :class="darkMode ?
                               'bg-gray-800/50 border-gray-700 text-white placeholder-gray-500' :
                               'bg-white border-gray-200 text-gray-900 placeholder-gray-400'">

                    <i data-lucide="search"
                       class="absolute start-3 top-1/2 -translate-y-1/2 w-5 h-5"
                       :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>

                    <!-- Clear Button -->
                    <button x-show="searchQuery.length > 0"
                            @click="searchQuery = ''"
                            class="absolute end-3 top-1/2 -translate-y-1/2 p-1 rounded-full hover:bg-gray-700/50 transition-colors">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <!-- Search Dropdown (Quick Results) -->
                <div x-show="searchOpen && searchQuery.length > 0"
                     x-cloak
                     x-transition
                     class="absolute top-full mt-2 w-full glass rounded-xl shadow-xl overflow-hidden"
                     :class="darkMode ? 'glass-dark' : 'glass-light'">

                    <div class="p-4 text-center">
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('admin.no_results') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Mobile Search Button -->
            <div x-data="{ mobileSearchOpen: false, mobileQuery: '' }" class="lg:hidden">
                <button @click="mobileSearchOpen = true"
                        class="p-2 rounded-xl transition-all hover:scale-110"
                        :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                    <i data-lucide="search" class="w-5 h-5"></i>
                </button>

                <!-- Mobile Search Modal -->
                <div x-show="mobileSearchOpen"
                     x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-start justify-center pt-16 px-4"
                     @click.self="mobileSearchOpen = false; mobileQuery = ''">

                    <div class="w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden"
                         :class="darkMode ? 'bg-gray-900 border border-gray-700' : 'bg-white border border-gray-200'">

                        <!-- Search Input Row -->
                        <div class="flex items-center gap-3 px-4 py-3 border-b"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                            <i data-lucide="search" class="w-5 h-5 flex-shrink-0"
                               :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                            <input type="text"
                                   x-model="mobileQuery"
                                   x-ref="mobileSearchInput"
                                   x-init="$watch('mobileSearchOpen', v => v && $nextTick(() => $refs.mobileSearchInput?.focus()))"
                                   placeholder="{{ __('admin.search') }}"
                                   @keydown.escape="mobileSearchOpen = false; mobileQuery = ''"
                                   class="flex-1 bg-transparent outline-none text-base"
                                   :class="darkMode ? 'text-white placeholder-gray-500' : 'text-gray-900 placeholder-gray-400'">
                            <button @click="mobileSearchOpen = false; mobileQuery = ''"
                                    class="p-1 rounded-lg transition-colors"
                                    :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-500'">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <!-- Results Area -->
                        <div class="p-4 text-center py-8">
                            <i data-lucide="search" class="w-10 h-10 mx-auto mb-3"
                               :class="darkMode ? 'text-gray-600' : 'text-gray-300'"></i>
                            <p class="text-sm font-medium"
                               :class="darkMode ? 'text-gray-300' : 'text-gray-500'">
                                <span x-show="mobileQuery.length === 0">{{ __('admin.search_placeholder') }}</span>
                                <span x-show="mobileQuery.length > 0">{{ __('admin.no_results') }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Language Switcher -->
            <div x-data="{ langOpen: false }" class="relative">
                <button @click="langOpen = !langOpen"
                        class="flex items-center gap-2 px-3 py-2 rounded-xl transition-all hover:scale-105"
                        :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                    <i data-lucide="languages" class="w-5 h-5"></i>
                    <span class="hidden sm:inline text-sm font-semibold uppercase">{{ app()->getLocale() }}</span>
                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                </button>

                <!-- Language Dropdown -->
                <div x-show="langOpen"
                     x-cloak
                     @click.away="langOpen = false"
                     x-transition
                     class="absolute top-full mt-2 end-0 w-48 glass rounded-xl shadow-xl overflow-hidden"
                     :class="darkMode ? 'glass-dark' : 'glass-light'">

                    <div class="p-2 space-y-1">
                        @foreach(['ar' => 'العربية', 'en' => 'English', 'tr' => 'Türkçe', 'fr' => 'Français', 'ur' => 'اردو'] as $code => $name)
                            <button @click="changeLang('{{ $code }}'); langOpen = false"
                                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-start transition-colors"
                                    :class="[
                                        currentLang === '{{ $code }}' ?
                                            'bg-gradient-primary text-white' :
                                            (darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100')
                                    ]">
                                <span class="text-sm font-semibold">{{ $name }}</span>
                                @if(app()->getLocale() === $code)
                                    <i data-lucide="check" class="w-4 h-4 ms-auto"></i>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Dark/Light Mode Toggle -->
            <button @click="toggleDarkMode"
                    class="p-2 rounded-xl transition-all hover:scale-110"
                    :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                <i x-show="!darkMode" data-lucide="moon" class="w-5 h-5"></i>
                <i x-show="darkMode" data-lucide="sun" class="w-5 h-5"></i>
            </button>

            <!-- Notifications -->
            <div x-data="{ notifOpen: false }" class="relative">
                <button @click="notifOpen = !notifOpen"
                        class="relative p-2 rounded-xl transition-all hover:scale-110"
                        :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                    <i data-lucide="bell" class="w-5 h-5"></i>
                </button>

                <!-- Notifications Dropdown -->
                <div x-show="notifOpen"
                     x-cloak
                     @click.away="notifOpen = false"
                     x-transition
                     class="absolute top-full mt-2 end-0 w-80 glass rounded-xl shadow-xl overflow-hidden"
                     :class="darkMode ? 'glass-dark' : 'glass-light'">

                    <!-- Notification Header -->
                    <div class="px-4 py-3 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        <h3 class="font-bold">{{ __('admin.notifications') }}</h3>
                    </div>

                    <!-- Notification List -->
                    <div class="p-4 text-center">
                        <i data-lucide="bell-off" class="w-12 h-12 mx-auto mb-2" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('admin.no_notifications') }}
                        </p>
                    </div>

                    <!-- View All -->
                    <div class="p-2 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        <a href="#"
                           class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-semibold text-accent hover:bg-accent/10 rounded-lg transition-colors">
                            {{ __('admin.view_all_notifications') }}
                            <i data-lucide="arrow-{{ app()->getLocale() === 'ar' || app()->getLocale() === 'ur' ? 'left' : 'right' }}" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="w-px h-8" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>

            <!-- User Profile Dropdown -->
            @auth
                <div x-data="{ userOpen: false }" class="relative">
                    <button @click="userOpen = !userOpen"
                            class="flex items-center gap-3 px-2 py-2 rounded-xl transition-all hover:scale-105"
                            :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">

                        <!-- Avatar -->
                        <div class="w-10 h-10 rounded-full bg-gradient-primary flex items-center justify-center shadow-glow">
                            <span class="text-white font-bold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                        </div>

                        <!-- User Info (Hidden on Mobile) -->
                        <div class="hidden xl:block text-start">
                            <p class="text-sm font-semibold leading-tight" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                {{ auth()->user()->role ?? __('admin.user') }}
                            </p>
                        </div>

                        <i data-lucide="chevron-down" class="w-4 h-4 hidden xl:block"></i>
                    </button>

                    <!-- User Dropdown -->
                    <div x-show="userOpen"
                         x-cloak
                         @click.away="userOpen = false"
                         x-transition
                         class="absolute top-full mt-2 end-0 w-56 glass rounded-xl shadow-xl overflow-hidden"
                         :class="darkMode ? 'glass-dark' : 'glass-light'">

                        <!-- User Info Section -->
                        <div class="px-4 py-3 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                            <p class="text-sm font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                {{ auth()->user()->email }}
                            </p>
                        </div>

                        <!-- Menu Items -->
                        <div class="p-2 space-y-1">
                            <a href="{{ route('admin.profile') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors"
                               :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                                <i data-lucide="user" class="w-4 h-4"></i>
                                <span class="text-sm font-semibold">{{ __('admin.profile') }}</span>
                            </a>

                            <a href="{{ route('admin.settings') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors"
                               :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                                <i data-lucide="settings" class="w-4 h-4"></i>
                                <span class="text-sm font-semibold">{{ __('admin.settings') }}</span>
                            </a>

                            <a href="{{ route('admin.help') }}"
                               class="flex items-center gap-3 px-3 py-2 rounded-lg transition-colors"
                               :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                                <i data-lucide="help-circle" class="w-4 h-4"></i>
                                <span class="text-sm font-semibold">{{ __('admin.help') }}</span>
                            </a>
                        </div>

                        <!-- Logout -->
                        <div class="p-2 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg transition-colors text-red-500 hover:bg-red-500/10">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                    <span class="text-sm font-semibold">{{ __('admin.logout') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth

        </div>

    </div>

</header>
