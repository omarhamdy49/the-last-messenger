@php
    // Derive the active page segment from the URL path
    $adminPrefix   = 'su/portal/v1/admin/';
    $currentPath   = request()->path();
    $activeSegment = 'dashboard';

    if (str_starts_with($currentPath, $adminPrefix)) {
        $afterAdmin    = ltrim(substr($currentPath, strlen($adminPrefix)), '/');
        $parts         = explode('/', $afterAdmin);
        $activeSegment = $parts[0] ?? 'dashboard';
    }

    // Map segments to which parent submenu should be open
    $submenuMap = [
        'articles'        => 'content',
        'categories'      => 'content',
        'tags'            => 'content',
        'series'          => 'content',
        'hadith'          => 'hadith',
        'hadith-collections' => 'hadith',
        'hadith-narrators' => 'hadith',
        'hadith-scholars' => 'hadith',
        'hadith-themes'   => 'hadith',
        'hadith-grading'  => 'hadith',
        'seerah-events'   => 'seerah',
        'seerah-event-types' => 'seerah',
        'seerah-milestones' => 'seerah',
        'quran'           => 'quran',
        'surahs'          => 'quran',
        'verses'          => 'quran',
        'tafseer'         => 'quran',
        'reciters'        => 'quran',
        'translation-resources' => 'quran',
        'tafsir-resources' => 'quran',
        'revelation-contexts' => 'quran',
        'ai-inquiries'    => 'quran',
        'media-programs'  => 'media',
        'media-episodes'  => 'media',
        'media-categories' => 'media',
        'media-library'   => 'media',
        'site-settings'   => 'site-content',
        'menus'           => 'site-content',
        'home-cards'      => 'site-content',
        'quick-filters'   => 'site-content',
        'static-pages'    => 'site-content',
        'faqs'            => 'site-content',
        'contact-messages' => 'site-content',
        'imports'         => 'site-content',
    ];
    $initialSubmenu = $submenuMap[$activeSegment] ?? '';

    // Unread contact messages badge for the Site Content group
    $contentUnreadCount = app(\App\Domain\Content\Services\ContactMessageService::class)->unreadCount();
@endphp

<!-- Admin Sidebar Component -->
<aside x-show="sidebarOpen"
       x-transition:enter="transform transition ease-out duration-300"
       x-transition:enter-start="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'translate-x-full' : '-translate-x-full' }}"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transform transition ease-in duration-200"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'translate-x-full' : '-translate-x-full' }}"
       :class="[
           darkMode ? 'glass-dark' : 'glass-light',
           'fixed lg:static inset-y-0 {{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'right-0' : 'left-0' }} z-40 w-72 flex flex-col glass'
       ]"
       class="border-e max-w-[85vw]"
       @click.away="if (window.innerWidth < 1024) sidebarOpen = false">

    <!-- Logo Section -->
    <div class="flex items-center justify-between px-6 py-6 border-b"
         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
            <div class="w-12 h-12 shrink-0 group-hover:scale-110 transition-transform">
                <x-site.brand-icon size="w-12 h-12" :eager="true" />
            </div>
            <div class="flex flex-col">
                <span class="text-lg font-bold gradient-text">{{ config('app.name') }}</span>
                <span class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('admin.admin_panel') }}</span>
            </div>
        </a>

        <button @click="sidebarOpen = false"
                class="lg:hidden p-2 rounded-lg hover:bg-gray-800/50 transition-colors">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 overflow-y-auto custom-scrollbar px-4 py-6"
         x-data="{
             activeMenu: '{{ $activeSegment }}',
             openSubmenu: '{{ $initialSubmenu }}' || null,

             isActive(key) {
                 return this.activeMenu === key;
             },

             toggleSubmenu(menu) {
                 this.openSubmenu = this.openSubmenu === menu ? null : menu;
             }
         }">

        <div class="space-y-2">

            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
               @click="activeMenu = 'dashboard'; openSubmenu = null"
               :class="isActive('dashboard') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.dashboard') }}</span>
            </a>

            <!-- Content Management (Articles, Categories, Tags, Series) -->
            <div>
                <button @click="toggleSubmenu('content')"
                        :class="openSubmenu === 'content' ||
                                ['articles','categories','tags','series'].includes(activeMenu) ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.nav.content') }}</span>
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform duration-200"
                       :class="openSubmenu === 'content' ? 'rotate-180' : ''"></i>
                </button>

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

                    <a href="{{ route('admin.series.index') }}"
                       @click="activeMenu = 'series'"
                       :class="isActive('series') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                        <span>{{ __('admin.series') }}</span>
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
            <div>
                <button @click="toggleSubmenu('media')"
                        :class="openSubmenu === 'media' ||
                                ['media-programs','media-episodes','media-categories','media-library','live-channels'].includes(activeMenu) ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="video" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.nav.media') }}</span>
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform duration-200"
                       :class="openSubmenu === 'media' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSubmenu === 'media'"
                     x-collapse
                     class="mt-2 ms-6 space-y-1">
                    <a href="{{ route('admin.media-library.index') }}"
                       @click="activeMenu = 'media-library'"
                       :class="isActive('media-library') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="images" class="w-4 h-4"></i>
                        <span>{{ __('media.title') }}</span>
                    </a>
                    <a href="{{ route('admin.media-categories.index') }}"
                       @click="activeMenu = 'media-categories'"
                       :class="isActive('media-categories') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="layout-grid" class="w-4 h-4"></i>
                        <span>{{ __('admin.media_categories') }}</span>
                    </a>
                    <a href="{{ route('admin.media-programs.index') }}"
                       @click="activeMenu = 'media-programs'"
                       :class="isActive('media-programs') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="tv" class="w-4 h-4"></i>
                        <span>{{ __('admin.media_programs') }}</span>
                    </a>
                    <a href="{{ route('admin.media-episodes.index') }}"
                       @click="activeMenu = 'media-episodes'"
                       :class="isActive('media-episodes') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="film" class="w-4 h-4"></i>
                        <span>{{ __('admin.episodes') }}</span>
                    </a>
                    <a href="{{ route('admin.live-channels.index') }}"
                       @click="activeMenu = 'live-channels'"
                       :class="isActive('live-channels') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="radio" class="w-4 h-4"></i>
                        <span>{{ __('admin.live_channels') }}</span>
                    </a>
                </div>
            </div>

            <!-- Hadith Management -->
            <div>
                <button @click="toggleSubmenu('hadith')"
                        :class="openSubmenu === 'hadith' || activeMenu === 'hadith' ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.nav.hadith') }}</span>
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform duration-200"
                       :class="openSubmenu === 'hadith' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSubmenu === 'hadith'"
                     x-collapse
                     class="mt-2 ms-6 space-y-1">
                    <a href="{{ route('admin.hadith.index') }}"
                       @click="activeMenu = 'hadith'"
                       :class="isActive('hadith') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="book" class="w-4 h-4"></i>
                        <span>{{ __('admin.hadiths') }}</span>
                    </a>
                    <a href="{{ route('admin.hadith.collections.index') }}"
                       @click="activeMenu = 'hadith-collections'"
                       :class="isActive('hadith-collections') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="library" class="w-4 h-4"></i>
                        <span>{{ __('admin.hadith_collections') }}</span>
                    </a>
                    <a href="{{ route('admin.hadith.narrators.index') }}"
                       @click="activeMenu = 'hadith-narrators'"
                       :class="isActive('hadith-narrators') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>{{ __('admin.hadith_narrators') }}</span>
                    </a>
                    <a href="{{ route('admin.hadith.scholars.index') }}"
                       @click="activeMenu = 'hadith-scholars'"
                       :class="isActive('hadith-scholars') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        <span>{{ __('admin.hadith_scholars') }}</span>
                    </a>
                    <a href="{{ route('admin.hadith.themes.index') }}"
                       @click="activeMenu = 'hadith-themes'"
                       :class="isActive('hadith-themes') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="tags" class="w-4 h-4"></i>
                        <span>{{ __('admin.hadith_themes') }}</span>
                    </a>
                    <a href="{{ route('admin.hadith.grading-levels.index') }}"
                       @click="activeMenu = 'hadith-grading'"
                       :class="isActive('hadith-grading') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>{{ __('admin.hadith_grading') }}</span>
                    </a>
                </div>
            </div>

            <!-- Seerah (Timeline & Events) -->
            <div>
                <button @click="toggleSubmenu('seerah')"
                        :class="openSubmenu === 'seerah' || activeMenu === 'seerah' ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="scroll" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.seerah') }}</span>
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform duration-200"
                       :class="openSubmenu === 'seerah' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSubmenu === 'seerah'"
                     x-collapse
                     class="mt-2 ms-6 space-y-1">
                    <a href="{{ route('admin.seerah-events.index') }}"
                       @click="activeMenu = 'seerah-events'"
                       :class="isActive('seerah-events') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                        <span>{{ __('admin.seerah_events') }}</span>
                    </a>
                    <a href="{{ route('admin.seerah-event-types.index') }}"
                       @click="activeMenu = 'seerah-event-types'"
                       :class="isActive('seerah-event-types') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="tags" class="w-4 h-4"></i>
                        <span>{{ __('admin.seerah_event_types') }}</span>
                    </a>
                    <a href="{{ route('admin.seerah-milestones.index') }}"
                       @click="activeMenu = 'seerah-milestones'"
                       :class="isActive('seerah-milestones') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="milestone" class="w-4 h-4"></i>
                        <span>{{ __('admin.seerah_milestones') }}</span>
                    </a>
                </div>
            </div>

            <!-- Sahaba (Companions) -->
            <a href="{{ route('admin.sahaba.index') }}"
               @click="activeMenu = 'sahaba'; openSubmenu = null"
               :class="isActive('sahaba') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="heart-handshake" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.sahaba') }}</span>
            </a>

            <!-- Quran Management -->
            <div>
                <button @click="toggleSubmenu('quran')"
                        :class="openSubmenu === 'quran' ||
                                ['quran','surahs','verses','tafseer','reciters','translation-resources','tafsir-resources','revelation-contexts','ai-inquiries'].includes(activeMenu) ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="book-marked" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.quran') }}</span>
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform duration-200"
                       :class="openSubmenu === 'quran' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSubmenu === 'quran'"
                     x-collapse
                     class="mt-2 ms-6 space-y-1">
                    <a href="{{ route('admin.surahs.index') }}"
                       @click="activeMenu = 'surahs'"
                       :class="isActive('surahs') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        <span>{{ __('quran.surahs.title') }}</span>
                    </a>
                    <a href="{{ route('admin.verses.index') }}"
                       @click="activeMenu = 'verses'"
                       :class="isActive('verses') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="text" class="w-4 h-4"></i>
                        <span>{{ __('quran.verses.title') }}</span>
                    </a>
                    <a href="{{ route('admin.tafseer.index') }}"
                       @click="activeMenu = 'tafseer'"
                       :class="isActive('tafseer') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="message-square-text" class="w-4 h-4"></i>
                        <span>{{ __('quran.tafseer.title') }}</span>
                    </a>
                    <a href="{{ route('admin.reciters.index') }}"
                       @click="activeMenu = 'reciters'"
                       :class="isActive('reciters') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="mic" class="w-4 h-4"></i>
                        <span>{{ __('quran.reciters.title') }}</span>
                    </a>
                    <a href="{{ route('admin.translation-resources.index') }}"
                       @click="activeMenu = 'translation-resources'"
                       :class="isActive('translation-resources') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="languages" class="w-4 h-4"></i>
                        <span>{{ __('quran.admin.translation_sources') }}</span>
                    </a>
                    <a href="{{ route('admin.tafsir-resources.index') }}"
                       @click="activeMenu = 'tafsir-resources'"
                       :class="isActive('tafsir-resources') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="book-marked" class="w-4 h-4"></i>
                        <span>{{ __('quran.admin.tafsir_sources') }}</span>
                    </a>
                    <a href="{{ route('admin.quran.ai-inquiries.index') }}"
                       @click="activeMenu = 'ai-inquiries'"
                       :class="isActive('ai-inquiries') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>{{ __('admin.quran_ai_inquiries') }}</span>
                    </a>
                </div>
            </div>

            <!-- Genealogy -->
            <a href="{{ route('admin.genealogy.index') }}"
               @click="activeMenu = 'genealogy'; openSubmenu = null"
               :class="isActive('genealogy') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="git-branch" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.genealogy') }}</span>
            </a>

            <!-- Site Content (Settings, Menus, Home Sections, Pages, FAQs, Inbox, Imports) -->
            <div>
                <button @click="toggleSubmenu('site-content')"
                        :class="openSubmenu === 'site-content' ||
                                ['site-settings','menus','home-cards','quick-filters','static-pages','faqs','contact-messages','imports'].includes(activeMenu) ?
                            'bg-gradient-primary text-white shadow-lg' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl transition-all group">
                    <div class="flex items-center gap-3">
                        <i data-lucide="panels-top-left" class="w-5 h-5"></i>
                        <span class="font-semibold">{{ __('admin.nav_site_content') }}</span>
                        @if ($contentUnreadCount > 0)
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white">{{ $contentUnreadCount }}</span>
                        @endif
                    </div>
                    <i data-lucide="chevron-down"
                       class="w-4 h-4 transition-transform duration-200"
                       :class="openSubmenu === 'site-content' ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openSubmenu === 'site-content'"
                     x-collapse
                     class="mt-2 ms-6 space-y-1">

                    <a href="{{ route('admin.content.settings.index') }}"
                       @click="activeMenu = 'site-settings'"
                       :class="isActive('site-settings') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="settings-2" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_settings') }}</span>
                    </a>

                    <a href="{{ route('admin.content.menus.index') }}"
                       @click="activeMenu = 'menus'"
                       :class="isActive('menus') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="menu" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_menus') }}</span>
                    </a>

                    <a href="{{ route('admin.content.home-cards.index') }}"
                       @click="activeMenu = 'home-cards'"
                       :class="isActive('home-cards') || isActive('quick-filters') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="layout-grid" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_home_sections') }}</span>
                    </a>

                    <a href="{{ route('admin.content.static-pages.index') }}"
                       @click="activeMenu = 'static-pages'"
                       :class="isActive('static-pages') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_static_pages') }}</span>
                    </a>

                    <a href="{{ route('admin.content.faqs.index') }}"
                       @click="activeMenu = 'faqs'"
                       :class="isActive('faqs') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="circle-help" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_faqs') }}</span>
                    </a>

                    <a href="{{ route('admin.content.contact-messages.index') }}"
                       @click="activeMenu = 'contact-messages'"
                       :class="isActive('contact-messages') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="inbox" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_contact_messages') }}</span>
                        @if ($contentUnreadCount > 0)
                            <span class="ms-auto px-2 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white">{{ $contentUnreadCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('admin.content.imports.index') }}"
                       @click="activeMenu = 'imports'"
                       :class="isActive('imports') ?
                           'text-accent font-semibold' :
                           (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')"
                       class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_imports') }}</span>
                    </a>
                </div>
            </div>

            <!-- Divider -->
            <div class="my-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'"></div>

            <!-- User Management -->
            <a href="{{ route('admin.users.index') }}"
               @click="activeMenu = 'users'; openSubmenu = null"
               :class="isActive('users') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.users') }}</span>
            </a>

            <!-- Roles & Permissions -->
            <a href="{{ route('admin.roles.index') }}"
               @click="activeMenu = 'roles'; openSubmenu = null"
               :class="isActive('roles') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="shield" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.roles') }}</span>
            </a>

            <!-- Analytics -->
            <a href="{{ route('admin.analytics.index') }}"
               @click="activeMenu = 'analytics'; openSubmenu = null"
               :class="isActive('analytics') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="bar-chart" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.analytics') }}</span>
            </a>

            <!-- Activity Logs -->
            <a href="{{ route('admin.activity-logs.index') }}"
               @click="activeMenu = 'activity-logs'; openSubmenu = null"
               :class="isActive('activity-logs') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="activity" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.activity_logs') }}</span>
            </a>

            <!-- Scheduler -->
            <a href="{{ route('admin.scheduler.index') }}"
               @click="activeMenu = 'scheduler'; openSubmenu = null"
               :class="isActive('scheduler') ?
                   'bg-gradient-primary text-white shadow-lg' :
                   (darkMode ? 'text-gray-300 hover:bg-gray-800/50' : 'text-gray-700 hover:bg-gray-100')"
               class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all group">
                <i data-lucide="calendar-clock" class="w-5 h-5"></i>
                <span class="font-semibold">{{ __('admin.scheduler') }}</span>
            </a>

            <!-- Settings -->
            <a href="{{ route('admin.settings') }}"
               @click="activeMenu = 'settings'; openSubmenu = null"
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
