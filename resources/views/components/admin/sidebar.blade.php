<!-- Admin Sidebar Component -->
<aside x-show="sidebarOpen"
       x-transition:enter="transform transition ease-out duration-300"
       x-transition:enter-start="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? '-translate-x-full' : 'translate-x-full' }}"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transform transition ease-in duration-200"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? '-translate-x-full' : 'translate-x-full' }}"
       :class="[
           darkMode ? 'glass-dark' : 'glass-light',
           'fixed lg:static inset-y-0 {{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'right-0' : 'left-0' }} z-40 w-72 flex flex-col glass'
       ]"
       class="border-e"
       @click.away="if (window.innerWidth < 1024) sidebarOpen = false">

    <!-- Logo Section -->
    <div class="flex items-center justify-between px-6 py-6 border-b"
         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
            <!-- Logo Icon with Gradient Background -->
            <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center shadow-glow group-hover:scale-110 transition-transform">
                <i data-lucide="moon-star" class="w-7 h-7 text-white"></i>
            </div>

            <!-- Logo Text -->
            <div class="flex flex-col">
                <span class="text-lg font-bold gradient-text">{{ config('app.name') }}</span>
                <span class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('admin.admin_panel') }}</span>
            </div>
        </a>

        <!-- Close Button (Mobile Only) -->
        <button @click="sidebarOpen = false"
                class="lg:hidden p-2 rounded-lg hover:bg-gray-800/50 transition-colors">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto custom-scrollbar px-4 py-6"
         x-data="{
             activeMenu: '{{ request()->segment(2) ?? 'dashboard' }}',
             openSubmenu: null,

             isActive(route) {
                 return this.activeMenu === route;
             },

             toggleSubmenu(menu) {
                 this.openSubmenu = this.openSubmenu === menu ? null : menu;
             }
         }">

        <!-- Navigation Items -->
        <div class="space-y-2">

            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
               @click="activeMenu = 'dashboard'"
               :class="isActive('dashboard') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.dashboard') }}</span>
            </a>

            <!-- Content Management -->
            <div x-data="{ open: false }">
                <button @click="toggleSubmenu('content')"
                        :class="openSubmenu === 'content' || activeMenu.includes('articles') || activeMenu.includes('categories') ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.nav.content') }}</span>
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform"
                       :class="openSubmenu === 'content' ? 'rotate-180' : ''"></i>
                </button>

                <!-- Submenu -->
                <div x-show="openSubmenu === 'content'"
                     x-collapse
                     class="mt-2 ms-6 space-y-1">
                    <a href="{{ route('admin.articles.index') }}"
                       @click="activeMenu = 'articles'"
                       :class="isActive('articles') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="scroll-text" class="w-4 h-4"></i>
                        <span>{{ __('admin.articles') }}</span>
                    </a>
                    <a href="{{ route('admin.categories.index') }}"
                       @click="activeMenu = 'categories'"
                       :class="isActive('categories') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="folder" class="w-4 h-4"></i>
                        <span>{{ __('admin.categories') }}</span>
                    </a>
                    <a href="{{ route('admin.tags.index') }}"
                       @click="activeMenu = 'tags'"
                       :class="isActive('tags') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="tag" class="w-4 h-4"></i>
                        <span>{{ __('admin.tags') }}</span>
                    </a>
                </div>
            </div>

            <!-- Media & Programs -->
            <div x-data="{ open: false }">
                <button @click="toggleSubmenu('media')"
                        :class="openSubmenu === 'media' || activeMenu.includes('programs') || activeMenu.includes('episodes') ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="video" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.nav.media') }}</span>
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform"
                       :class="openSubmenu === 'media' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSubmenu === 'media'"
                     x-collapse
                     class="mt-2 ms-6 space-y-1">
                    <a href="{{ route('admin.programs.index') }}"
                       @click="activeMenu = 'programs'"
                       :class="isActive('programs') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="tv" class="w-4 h-4"></i>
                        <span>{{ __('admin.media_programs') }}</span>
                    </a>
                    <a href="{{ route('admin.episodes.index') }}"
                       @click="activeMenu = 'episodes'"
                       :class="isActive('episodes') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="film" class="w-4 h-4"></i>
                        <span>{{ __('admin.episodes') }}</span>
                    </a>
                </div>
            </div>

            <!-- Hadith Management -->
            <div x-data="{ open: false }">
                <button @click="toggleSubmenu('hadith')"
                        :class="openSubmenu === 'hadith' || activeMenu.includes('hadith') ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.hadith') }}</span>
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform"
                       :class="openSubmenu === 'hadith' ? 'rotate-180' : ''"></i>
                </button>

                <!-- Hadith Submenu -->
                <div x-show="openSubmenu === 'hadith'"
                     x-collapse
                     class="mt-2 ms-6 space-y-1">
                    <a href="{{ route('admin.hadith-books.index') }}"
                       @click="activeMenu = 'hadith-books'"
                       :class="isActive('hadith-books') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="book" class="w-4 h-4"></i>
                        <span>كتب الحديث</span>
                    </a>
                    <a href="{{ route('admin.hadith-chapters.index') }}"
                       @click="activeMenu = 'hadith-chapters'"
                       :class="isActive('hadith-chapters') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="bookmark" class="w-4 h-4"></i>
                        <span>الأبواب</span>
                    </a>
                    <a href="{{ route('admin.hadith.index') }}"
                       @click="activeMenu = 'hadith'"
                       :class="isActive('hadith') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="scroll-text" class="w-4 h-4"></i>
                        <span>الأحاديث</span>
                    </a>
                    <a href="{{ route('admin.hadith-narrators.index') }}"
                       @click="activeMenu = 'hadith-narrators'"
                       :class="isActive('hadith-narrators') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>الرواة</span>
                    </a>
                </div>
            </div>

            <!-- Quran Management -->
            <a href="{{ route('admin.quran.index') }}"
               @click="activeMenu = 'quran'"
               :class="isActive('quran') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="book-marked" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.quran') }}</span>
            </a>

            <!-- Genealogy -->
            <a href="{{ route('admin.genealogy.index') }}"
               @click="activeMenu = 'genealogy'"
               :class="isActive('genealogy') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="git-branch" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.genealogy') }}</span>
            </a>

            <!-- Divider -->
            <div class="my-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'"></div>

            <!-- User Management -->
            <a href="{{ route('admin.users.index') }}"
               @click="activeMenu = 'users'"
               :class="isActive('users') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.users') }}</span>
            </a>

            <!-- Roles & Permissions -->
            <a href="{{ route('admin.roles.index') }}"
               @click="activeMenu = 'roles'"
               :class="isActive('roles') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="shield" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.roles') }}</span>
            </a>

            <!-- Analytics -->
            <a href="{{ route('admin.analytics.index') }}"
               @click="activeMenu = 'analytics'"
               :class="isActive('analytics') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="bar-chart" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.analytics') }}</span>
            </a>

            <!-- Settings -->
            <a href="{{ route('admin.settings') }}"
               @click="activeMenu = 'settings'"
               :class="isActive('settings') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.settings') }}</span>
            </a>

        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="px-4 py-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        @auth
            <!-- User Profile Section -->
            <div class="flex items-center gap-3 p-3 rounded-xl glass"
                 :class="darkMode ? 'bg-gray-800/30 hover:bg-gray-800/50' : 'bg-gray-100/50 hover:bg-gray-100'">
                <div class="w-10 h-10 rounded-full bg-gradient-primary flex items-center justify-center shadow-glow">
                    <span class="text-white font-bold text-sm">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        {{ auth()->user()->email }}
                    </p>
                </div>
            </div>
        @endauth
    </div>

</aside>
