{{--
    Admin Sidebar Component

    Props:
    - $activeMenu (string): Currently active menu item

    Usage:
    <x-admin.sidebar activeMenu="dashboard" />
--}}

@props([
    'activeMenu' => 'dashboard'
])

<aside {{ $attributes->merge(['class' => 'w-64 bg-gray-900 min-h-screen flex flex-col']) }}
       x-data="{
           collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
           mobileSidebarOpen: false
       }"
       :class="collapsed ? 'w-20' : 'w-64'">

    {{-- Logo --}}
    <div class="h-16 flex items-center justify-center border-b border-gray-800 px-4">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="w-6 h-6 text-white" data-lucide="zap"></i>
            </div>
            <span x-show="!collapsed"
                  class="text-xl font-bold bg-gradient-to-r from-[#0056b3] to-[#218838] bg-clip-text text-transparent">
                {{ __('admin.title') }}
            </span>
        </a>
    </div>

    {{-- Collapse Toggle --}}
    <button @click="collapsed = !collapsed; localStorage.setItem('sidebarCollapsed', collapsed)"
            class="hidden lg:flex items-center justify-center h-10 border-b border-gray-800 text-gray-400 hover:text-white hover:bg-gray-800 transition">
        <i x-show="!collapsed" class="w-4 h-4" data-lucide="chevrons-left"></i>
        <i x-show="collapsed" class="w-4 h-4" data-lucide="chevrons-right"></i>
    </button>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto custom-scrollbar py-6">
        <div class="space-y-1 px-3">

            {{-- Dashboard --}}
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'dashboard' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.dashboard') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="layout-dashboard"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.dashboard') }}</span>
            </a>

            {{-- Divider --}}
            <div class="h-px bg-gray-800 my-4" x-show="!collapsed"></div>
            <div class="text-xs font-semibold text-gray-500 px-3 mb-2" x-show="!collapsed">
                {{ __('admin.content_management') }}
            </div>

            {{-- Articles --}}
            <a href="{{ route('admin.articles.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'articles' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.articles') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="file-text"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.articles') }}</span>
                <span x-show="!collapsed"
                      class="ms-auto px-2 py-0.5 text-xs font-bold rounded-full bg-[#64CCC5] text-black">
                    {{ \App\Domain\Blog\Models\Article::count() }}
                </span>
            </a>

            {{-- Categories --}}
            <a href="{{ route('admin.categories.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'categories' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.categories') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="folder"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.categories') }}</span>
            </a>

            {{-- Hadith --}}
            <a href="{{ route('admin.hadith.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'hadith' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.hadith') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="scroll-text"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.hadith') }}</span>
            </a>

            {{-- Media Programs --}}
            <a href="{{ route('admin.media.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'media' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.media') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="play-circle"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.media') }}</span>
            </a>

            {{-- Timeline Events --}}
            <a href="{{ route('admin.seerah.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'seerah' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.timeline') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="git-branch"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.timeline') }}</span>
            </a>

            {{-- Sahaba --}}
            <a href="{{ route('admin.sahaba.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'sahaba' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.sahaba') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="users"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.sahaba') }}</span>
            </a>

            {{-- Quran --}}
            <a href="{{ route('admin.quran.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'quran' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.quran') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="book"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.quran') }}</span>
            </a>

            {{-- Genealogy --}}
            <a href="{{ route('admin.genealogy.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'genealogy' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.genealogy') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="network"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.genealogy') }}</span>
            </a>

            {{-- Divider --}}
            <div class="h-px bg-gray-800 my-4" x-show="!collapsed"></div>
            <div class="text-xs font-semibold text-gray-500 px-3 mb-2" x-show="!collapsed">
                {{ __('admin.system') }}
            </div>

            {{-- Users --}}
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'users' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.users') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="user-cog"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.users') }}</span>
            </a>

            {{-- Settings --}}
            <a href="{{ route('admin.settings.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'settings' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.settings') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="settings"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.settings') }}</span>
            </a>

            {{-- Logs --}}
            <a href="{{ route('admin.logs.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all {{ $activeMenu === 'logs' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}"
               :title="collapsed ? '{{ __('admin.logs') }}' : ''">
                <i class="w-5 h-5 flex-shrink-0" data-lucide="activity"></i>
                <span x-show="!collapsed" class="font-medium">{{ __('admin.logs') }}</span>
            </a>
        </div>
    </nav>

    {{-- User Info & Logout --}}
    <div class="border-t border-gray-800 p-4">
        <div class="flex items-center gap-3" :class="collapsed ? 'justify-center' : ''">
            <img src="{{ auth()->user()->avatar ?? asset('images/default-avatar.png') }}"
                 alt="{{ auth()->user()->name }}"
                 class="w-10 h-10 rounded-full flex-shrink-0">

            <div x-show="!collapsed" class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-white truncate">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-xs text-gray-500 truncate">
                    {{ auth()->user()->email }}
                </p>
            </div>

            <form action="{{ route('admin.logout') }}" method="POST" class="flex-shrink-0">
                @csrf
                <button type="submit"
                        class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-gray-800 transition"
                        :title="collapsed ? '{{ __('auth.logout') }}' : ''"
                        aria-label="{{ __('auth.logout') }}">
                    <i class="w-5 h-5" data-lucide="log-out"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
