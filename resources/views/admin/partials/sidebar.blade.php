<aside
    class="relative h-full w-72 lg:w-auto transition-all duration-300 custom-scrollbar overflow-y-auto flex-shrink-0"
    :class="!sidebarOpen && window.innerWidth >= 1024 ? 'lg:w-20' : 'lg:w-72'"
>
    <div class="h-full relative overflow-y-auto custom-scrollbar transition-colors duration-300"
         :class="darkMode ? 'bg-gray-900/95 backdrop-blur-lg shadow-2xl' : 'glass-light-soft shadow-lg border-r border-blue-100/50'">
        <!-- Gradient Overlay (only in dark mode) -->
        <div class="absolute inset-0 opacity-10 gradient-primary" x-show="darkMode"></div>

        <!-- Sidebar Content -->
        <div class="relative z-10 flex flex-col h-full">
            <!-- Logo Section -->
            <div class="p-6 transition-colors duration-300"
                 :class="darkMode ? 'border-b border-white/10' : 'border-b border-blue-100/30'">
                <div class="flex items-center justify-between">
                    <a href="{{ route('admin.dashboard.index') }}" class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center shadow-lg animate-pulse-glow">
                            <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
                        </div>
                        <div x-show="sidebarOpen" x-transition class="overflow-hidden">
                            <h1 class="text-xl font-bold transition-colors" :class="darkMode ? 'text-white' : 'text-gray-900'">خاتَمُ المُرْسَلِين</h1>
                            <p class="text-xs transition-colors" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">لوحة التحكم</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Navigation Menu -->
            <nav class="flex-1 p-4 space-y-2">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard.index') }}"
                   class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.dashboard.*') ? 'active' : '' }}"
                   :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                    <span x-show="sidebarOpen" x-transition class="font-semibold">لوحة المعلومات</span>
                </a>

                <!-- Content Management Section -->
                <div class="pt-4">
                    <p x-show="sidebarOpen" x-transition class="px-4 text-xs font-bold uppercase tracking-wider mb-2 transition-colors"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        إدارة المحتوى
                    </p>

                    <!-- Articles -->
                    <a href="{{ route('admin.articles.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="file-text" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">المقالات</span>
                        @if(isset($stats['articles']) && $stats['articles'] > 0)
                            <span x-show="sidebarOpen" x-transition class="mr-auto bg-blue-500/20 text-blue-300 text-xs px-2 py-1 rounded-full">
                                {{ $stats['articles'] ?? 0 }}
                            </span>
                        @endif
                    </a>

                    <!-- Blog Categories -->
                    <a href="{{ route('admin.categories.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="tags" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">التصنيفات</span>
                    </a>

                    <!-- Tags -->
                    <a href="{{ route('admin.tags.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.tags.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="bookmark" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">الوسوم</span>
                    </a>

                    <!-- Series -->
                    <a href="{{ route('admin.series.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.series.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="list" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">السلاسل</span>
                    </a>

                    <!-- Hadith -->
                    <a href="{{ route('admin.hadith.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.hadith.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="scroll-text" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">الأحاديث</span>
                        @if(isset($stats['hadith']) && $stats['hadith'] > 0)
                            <span x-show="sidebarOpen" x-transition class="mr-auto bg-green-500/20 text-green-300 text-xs px-2 py-1 rounded-full">
                                {{ $stats['hadith'] ?? 0 }}
                            </span>
                        @endif
                    </a>

                    <!-- Simple Media Library -->
                    <a href="{{ route('admin.simple-media.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.simple-media.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="image" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">مكتبة الوسائط</span>
                    </a>

                    <!-- Seerah Events -->
                    <a href="{{ route('admin.seerah-events.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.seerah-events.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="calendar" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">أحداث السيرة</span>
                    </a>

                    <!-- Sahaba -->
                    <a href="{{ route('admin.sahaba.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.sahaba.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="users" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">الصحابة</span>
                    </a>

                    <!-- Quran -->
                    <a href="{{ route('admin.quran.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.quran.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="book" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">القرآن الكريم</span>
                    </a>

                    <!-- Genealogy -->
                    <a href="{{ route('admin.genealogy.index') }}"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.genealogy.*') ? 'active' : '' }}"
                       :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                        <i data-lucide="git-branch" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">الأنساب</span>
                    </a>
                </div>

                <!-- User Management Section -->
                @if(auth()->user()->hasAnyRole(['super_admin', 'admin']))
                    <div class="pt-4">
                        <p x-show="sidebarOpen" x-transition class="px-4 text-xs font-bold uppercase tracking-wider mb-2 transition-colors"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            إدارة المستخدمين
                        </p>

                        <!-- Users -->
                        <a href="{{ route('admin.users.index') }}"
                           class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                           :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                            <i data-lucide="user-cog" class="w-5 h-5 flex-shrink-0"></i>
                            <span x-show="sidebarOpen" x-transition class="font-semibold">المستخدمون</span>
                            @if(isset($stats['users']) && $stats['users'] > 0)
                                <span x-show="sidebarOpen" x-transition class="mr-auto bg-teal-500/20 text-teal-300 text-xs px-2 py-1 rounded-full">
                                    {{ $stats['users'] ?? 0 }}
                                </span>
                            @endif
                        </a>

                        <!-- Roles & Permissions -->
                        @if(auth()->user()->hasRole('super_admin'))
                            <a href="{{ route('admin.roles.index') }}"
                               class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                               :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                                <i data-lucide="shield" class="w-5 h-5 flex-shrink-0"></i>
                                <span x-show="sidebarOpen" x-transition class="font-semibold">الأدوار والصلاحيات</span>
                            </a>
                        @endif
                    </div>
                @endif

                <!-- Settings Section -->
                <div class="pt-4">
                    <p x-show="sidebarOpen" x-transition class="px-4 text-xs font-bold uppercase tracking-wider mb-2 transition-colors"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        الإعدادات
                    </p>

                    <!-- Settings (Future) -->
                    <a href="#"
                       class="sidebar-item flex items-center space-x-3 space-x-reverse px-4 py-3 rounded-xl transition-colors duration-200 opacity-50 cursor-not-allowed"
                       :class="darkMode ? 'text-white' : 'text-gray-700'">
                        <i data-lucide="settings" class="w-5 h-5 flex-shrink-0"></i>
                        <span x-show="sidebarOpen" x-transition class="font-semibold">الإعدادات</span>
                        <span x-show="sidebarOpen" x-transition class="mr-auto text-xs transition-colors"
                              :class="darkMode ? 'text-gray-400' : 'text-gray-500'">قريباً</span>
                    </a>
                </div>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-4 transition-colors duration-300"
                 :class="darkMode ? 'border-t border-white/10' : 'border-t border-gray-200'">
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    class="w-full flex items-center justify-center space-x-2 space-x-reverse px-4 py-3 rounded-xl transition-all duration-300"
                    :class="darkMode ? 'text-white hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'">
                    <i :data-lucide="sidebarOpen ? 'chevrons-right' : 'chevrons-left'" class="w-5 h-5"></i>
                    <span x-show="sidebarOpen" x-transition class="font-semibold">طي القائمة</span>
                </button>
            </div>
        </div>
    </div>
</aside>

<script>
    // Re-initialize Lucide icons after Alpine mutations
    document.addEventListener('alpine:initialized', () => {
        lucide.createIcons();
    });
</script>
