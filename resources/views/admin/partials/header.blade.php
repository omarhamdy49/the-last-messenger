<header class="relative z-10 transition-colors duration-300"
        :class="darkMode ? 'bg-gray-900/95 backdrop-blur-lg shadow-lg' : 'glass-light-soft shadow-md'"
        x-data="{ userMenuOpen: false, notificationsOpen: false }">
    <div class="px-4 lg:px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Mobile Menu Toggle + Page Title -->
            <div class="flex items-center gap-3 flex-1">
                <!-- Mobile Menu Button -->
                <button @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 rounded-xl transition-all duration-300"
                        :class="darkMode ? 'hover:bg-white/10 text-white' : 'hover:bg-gray-100 text-gray-700'">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>

                <!-- Page Title -->
                <div>
                    <h2 class="text-xl lg:text-2xl font-bold transition-colors"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        @yield('page-title', 'لوحة المعلومات')
                    </h2>
                    @if(isset($pageSubtitle))
                        <p class="text-sm mt-1 transition-colors hidden sm:block"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $pageSubtitle }}</p>
                    @endif
                </div>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center space-x-2 lg:space-x-4 space-x-reverse">
                <!-- Search Bar -->
                <div class="relative hidden md:block">
                    <input
                        type="text"
                        placeholder="البحث..."
                        class="w-64 px-4 py-2 pr-10 rounded-xl transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                        :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500 border-0'"
                        x-data
                        @keydown.enter="console.log('Search:', $event.target.value)"
                    >
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 transition-colors"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                </div>

                <!-- Dark Mode Toggle -->
                <button
                    @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode); $nextTick(() => lucide.createIcons())"
                    class="p-2 rounded-xl transition-all duration-300"
                    :class="darkMode ? 'hover:bg-white/10 text-white' : 'hover:bg-gray-100 text-gray-700'"
                    title="تبديل الوضع الداكن"
                >
                    <i x-show="!darkMode" data-lucide="moon" class="w-5 h-5"></i>
                    <i x-show="darkMode" data-lucide="sun" class="w-5 h-5"></i>
                </button>

                <!-- Notifications Dropdown -->
                <div class="relative" @click.away="notificationsOpen = false">
                    <button
                        @click="notificationsOpen = !notificationsOpen"
                        class="relative p-2 rounded-xl transition-all duration-300"
                        :class="darkMode ? 'hover:bg-white/10 text-white' : 'hover:bg-gray-100 text-gray-700'"
                        title="الإشعارات"
                    >
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <!-- Notification Badge -->
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    </button>

                    <!-- Notifications Dropdown Menu -->
                    <div
                        x-show="notificationsOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 mt-2 w-80 rounded-2xl shadow-2xl overflow-hidden transition-colors"
                        :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'glass-light'"
                        style="display: none;"
                    >
                        <!-- Header -->
                        <div class="px-4 py-3 transition-colors"
                             :class="darkMode ? 'border-b border-gray-700' : 'border-b border-gray-200'">
                            <h3 class="text-lg font-bold transition-colors"
                                :class="darkMode ? 'text-white' : 'text-gray-900'">الإشعارات</h3>
                        </div>

                        <!-- Notifications List -->
                        <div class="max-h-96 overflow-y-auto custom-scrollbar">
                            <!-- Sample Notification -->
                            <div class="px-4 py-3 cursor-pointer transition-colors"
                                 :class="darkMode ? 'hover:bg-gray-700/50 border-b border-gray-700' : 'hover:bg-blue-50/50 border-b border-gray-100'">
                                <div class="flex items-start space-x-3 space-x-reverse">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full gradient-primary flex items-center justify-center">
                                            <i data-lucide="user-plus" class="w-5 h-5 text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold transition-colors"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">مستخدم جديد</p>
                                        <p class="text-xs mt-1 transition-colors"
                                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تم تسجيل مستخدم جديد في النظام</p>
                                        <p class="text-xs mt-1 transition-colors"
                                           :class="darkMode ? 'text-gray-500' : 'text-gray-500'">منذ 5 دقائق</p>
                                    </div>
                                </div>
                            </div>

                            <div class="px-4 py-3 cursor-pointer transition-colors"
                                 :class="darkMode ? 'hover:bg-gray-700/50 border-b border-gray-700' : 'hover:bg-green-50/50 border-b border-gray-100'">
                                <div class="flex items-start space-x-3 space-x-reverse">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                                            <i data-lucide="file-check" class="w-5 h-5 text-green-400"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold transition-colors"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">مقال جديد</p>
                                        <p class="text-xs mt-1 transition-colors"
                                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تم نشر مقال جديد بنجاح</p>
                                        <p class="text-xs mt-1 transition-colors"
                                           :class="darkMode ? 'text-gray-500' : 'text-gray-500'">منذ ساعة</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div class="px-4 py-8 text-center hidden">
                                <i data-lucide="bell-off" class="w-12 h-12 mx-auto mb-2 transition-colors"
                                   :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                                <p class="text-sm transition-colors"
                                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">لا توجد إشعارات جديدة</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-4 py-3 transition-colors"
                             :class="darkMode ? 'border-t border-gray-700' : 'border-t border-gray-200'">
                            <a href="#" class="block text-center text-sm text-brand-accent hover:text-blue-400 transition-colors font-semibold">
                                عرض جميع الإشعارات
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Profile Dropdown -->
                <div class="relative" @click.away="userMenuOpen = false">
                    <button
                        @click="userMenuOpen = !userMenuOpen"
                        class="flex items-center space-x-3 space-x-reverse px-3 py-2 rounded-xl transition-all duration-300"
                        :class="darkMode ? 'hover:bg-white/10' : 'hover:bg-gray-100'"
                    >
                        <!-- Avatar -->
                        <div class="w-10 h-10 rounded-full gradient-primary flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-sm">
                                {{ substr(auth()->user()->name, 0, 2) }}
                            </span>
                        </div>

                        <!-- User Info -->
                        <div class="hidden lg:block text-right">
                            <p class="text-sm font-semibold transition-colors"
                               :class="darkMode ? 'text-white' : 'text-gray-900'">{{ auth()->user()->name }}</p>
                            <p class="text-xs transition-colors"
                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                @if(auth()->user()->hasRole('super_admin'))
                                    مدير عام
                                @elseif(auth()->user()->hasRole('admin'))
                                    مدير
                                @elseif(auth()->user()->hasRole('editor'))
                                    محرر
                                @else
                                    مستخدم
                                @endif
                            </p>
                        </div>

                        <i data-lucide="chevron-down" class="w-4 h-4 transition-colors"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        x-show="userMenuOpen"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 mt-2 w-56 rounded-2xl shadow-2xl overflow-hidden transition-colors"
                        :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'glass-light'"
                        style="display: none;"
                    >
                        <!-- User Info -->
                        <div class="px-4 py-3 transition-colors"
                             :class="darkMode ? 'border-b border-gray-700' : 'border-b border-gray-200'">
                            <p class="text-sm font-bold transition-colors"
                               :class="darkMode ? 'text-white' : 'text-gray-900'">{{ auth()->user()->name }}</p>
                            <p class="text-xs mt-1 transition-colors"
                               :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ auth()->user()->email }}</p>
                        </div>

                        <!-- Menu Items -->
                        <div class="py-2">
                            <a href="#" class="flex items-center space-x-3 space-x-reverse px-4 py-2 transition-colors"
                               :class="darkMode ? 'hover:bg-gray-700/50' : 'hover:bg-blue-50/50'">
                                <i data-lucide="user" class="w-4 h-4 transition-colors"
                                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                                <span class="text-sm transition-colors"
                                      :class="darkMode ? 'text-white' : 'text-gray-700'">الملف الشخصي</span>
                            </a>

                            <a href="#" class="flex items-center space-x-3 space-x-reverse px-4 py-2 transition-colors"
                               :class="darkMode ? 'hover:bg-gray-700/50' : 'hover:bg-blue-50/50'">
                                <i data-lucide="settings" class="w-4 h-4 transition-colors"
                                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                                <span class="text-sm transition-colors"
                                      :class="darkMode ? 'text-white' : 'text-gray-700'">الإعدادات</span>
                            </a>

                            <a href="#" class="flex items-center space-x-3 space-x-reverse px-4 py-2 transition-colors"
                               :class="darkMode ? 'hover:bg-gray-700/50' : 'hover:bg-blue-50/50'">
                                <i data-lucide="help-circle" class="w-4 h-4 transition-colors"
                                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                                <span class="text-sm transition-colors"
                                      :class="darkMode ? 'text-white' : 'text-gray-700'">المساعدة</span>
                            </a>
                        </div>

                        <!-- Logout -->
                        <div class="transition-colors"
                             :class="darkMode ? 'border-t border-gray-700' : 'border-t border-gray-200'">
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center space-x-3 space-x-reverse w-full px-4 py-3 transition-colors text-right"
                                        :class="darkMode ? 'hover:bg-red-500/10' : 'hover:bg-red-50'">
                                    <i data-lucide="log-out" class="w-4 h-4 text-red-400"></i>
                                    <span class="text-sm text-red-400 font-semibold">تسجيل الخروج</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Bar (Optional) -->
    @if(isset($quickStats) && $quickStats)
        <div class="px-6 pb-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($quickStats as $stat)
                    <div class="rounded-xl p-3 transition-colors"
                         :class="darkMode ? 'bg-gray-800/50 border border-gray-700' : 'glass-light-soft border-0'">
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="w-10 h-10 rounded-lg {{ $stat['color'] ?? 'gradient-primary' }} flex items-center justify-center">
                                <i data-lucide="{{ $stat['icon'] }}" class="w-5 h-5 text-white"></i>
                            </div>
                            <div>
                                <p class="text-xs transition-colors"
                                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $stat['label'] }}</p>
                                <p class="text-lg font-bold transition-colors"
                                   :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $stat['value'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</header>
