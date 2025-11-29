# Blade Template Integration Guide

## Basic Admin Layout Template

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
      x-data
      :class="{ 'dark': $store.admin.darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name') . ' - Admin')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/admin.css', 'resources/js/admin/app.js'])

    <!-- Additional Head Content -->
    @stack('styles')
</head>
<body class="antialiased">
    <!-- Sidebar -->
    @include('admin.partials.sidebar')

    <!-- Header -->
    @include('admin.partials.header')

    <!-- Main Content -->
    <main class="main-content">
        <!-- Breadcrumbs -->
        @if(isset($breadcrumbs))
            <nav class="mb-8" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    @foreach($breadcrumbs as $index => $breadcrumb)
                        <li class="flex items-center">
                            @if(!$loop->last)
                                <a href="{{ $breadcrumb['url'] }}"
                                   class="text-gray-600 dark:text-gray-400 hover:text-primary-500 transition-colors">
                                    {{ $breadcrumb['label'] }}
                                </a>
                                <i data-lucide="chevron-right" class="w-4 h-4 mx-2 text-gray-400"></i>
                            @else
                                <span class="text-gray-900 dark:text-white font-semibold">
                                    {{ $breadcrumb['label'] }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <!-- Page Content -->
        @yield('content')
    </main>

    <!-- Toast Notifications -->
    <div class="fixed bottom-4 right-4 z-100 space-y-3"
         x-data
         x-show="$store.admin.toasts.length > 0">
        <template x-for="toast in $store.admin.toasts" :key="toast.id">
            <div class="glass-strong rounded-xl p-4 shadow-xl min-w-[300px] animate-slide-in-right"
                 :class="{
                     'border-l-4 border-green-500': toast.type === 'success',
                     'border-l-4 border-red-500': toast.type === 'error',
                     'border-l-4 border-yellow-500': toast.type === 'warning',
                     'border-l-4 border-blue-500': toast.type === 'info'
                 }"
                 x-show="true"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-8"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-8">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <i :data-lucide="toast.type === 'success' ? 'check-circle' :
                                           toast.type === 'error' ? 'alert-circle' :
                                           toast.type === 'warning' ? 'alert-triangle' :
                                           'info'"
                               class="w-5 h-5"
                               :class="{
                                   'text-green-500': toast.type === 'success',
                                   'text-red-500': toast.type === 'error',
                                   'text-yellow-500': toast.type === 'warning',
                                   'text-blue-500': toast.type === 'info'
                               }"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white"
                           x-text="toast.message"></p>
                    </div>
                    <button @click="$store.admin.dismissToast(toast.id)"
                            class="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- Confirmation Dialog -->
    <div x-show="$store.admin.confirmDialog.open"
         class="fixed inset-0 z-100 overflow-y-auto"
         x-cloak
         @keydown.escape.window="$store.admin.closeConfirmDialog()">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
             x-show="$store.admin.confirmDialog.open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="$store.admin.closeConfirmDialog()"></div>

        <!-- Dialog -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="relative glass-strong rounded-2xl p-6 max-w-md w-full shadow-2xl"
                 x-show="$store.admin.confirmDialog.open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-90"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-90"
                 @click.stop>
                <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white"
                    x-text="$store.admin.confirmDialog.title"></h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6"
                   x-text="$store.admin.confirmDialog.message"></p>

                <div class="flex space-x-3 justify-end">
                    <button @click="$store.admin.confirmDialog.onCancel()"
                            class="btn-secondary">
                        <span x-text="$store.admin.confirmDialog.cancelText"></span>
                    </button>
                    <button @click="$store.admin.confirmDialog.onConfirm()"
                            class="btn-primary"
                            :class="{
                                'bg-gradient-to-r from-red-600 to-red-700': $store.admin.confirmDialog.type === 'danger'
                            }">
                        <span x-text="$store.admin.confirmDialog.confirmText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    @stack('scripts')

    <!-- Flash Messages -->
    @if(session('success'))
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('admin').success(@json(session('success')));
            });
        </script>
    @endif

    @if(session('error'))
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('admin').error(@json(session('error')));
            });
        </script>
    @endif
</body>
</html>
```

---

## Sidebar Partial

**File:** `resources/views/admin/partials/sidebar.blade.php`

```blade
<aside class="sidebar"
       :class="{
           'collapsed': $store.admin.sidebarCollapsed,
           'show': $store.admin.sidebarOpen
       }">
    <!-- Logo -->
    <div class="flex items-center justify-between p-6">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3">
            <img src="{{ asset('images/logo.png') }}"
                 alt="Logo"
                 class="w-10 h-10"
                 x-show="!$store.admin.sidebarCollapsed || window.innerWidth < 768">
            <span class="font-bold text-xl text-gradient"
                  x-show="!$store.admin.sidebarCollapsed">
                {{ config('app.name') }}
            </span>
        </a>

        <!-- Mobile Close Button -->
        <button @click="$store.admin.closeSidebar()"
                class="btn-icon md:hidden">
            <i data-lucide="x"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}"
           class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <i data-lucide="layout-dashboard" class="sidebar-item-icon"></i>
            <span class="sidebar-item-text">{{ __('Dashboard') }}</span>
        </a>

        <!-- Articles -->
        <a href="{{ route('admin.articles.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
            <i data-lucide="file-text" class="sidebar-item-icon"></i>
            <span class="sidebar-item-text">{{ __('Articles') }}</span>
        </a>

        <!-- Hadith -->
        <a href="{{ route('admin.hadith.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.hadith.*') ? 'active' : '' }}">
            <i data-lucide="book-open" class="sidebar-item-icon"></i>
            <span class="sidebar-item-text">{{ __('Hadith') }}</span>
        </a>

        <!-- Quran -->
        <a href="{{ route('admin.quran.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.quran.*') ? 'active' : '' }}">
            <i data-lucide="book" class="sidebar-item-icon"></i>
            <span class="sidebar-item-text">{{ __('Quran') }}</span>
        </a>

        <!-- Media -->
        <a href="{{ route('admin.media.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.media.*') ? 'active' : '' }}">
            <i data-lucide="video" class="sidebar-item-icon"></i>
            <span class="sidebar-item-text">{{ __('Media') }}</span>
        </a>

        <!-- Genealogy -->
        <a href="{{ route('admin.genealogy.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.genealogy.*') ? 'active' : '' }}">
            <i data-lucide="git-branch" class="sidebar-item-icon"></i>
            <span class="sidebar-item-text">{{ __('Genealogy') }}</span>
        </a>

        <!-- Users -->
        <a href="{{ route('admin.users.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <i data-lucide="users" class="sidebar-item-icon"></i>
            <span class="sidebar-item-text">{{ __('Users') }}</span>
        </a>

        <!-- Settings -->
        <a href="{{ route('admin.settings.index') }}"
           class="sidebar-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <i data-lucide="settings" class="sidebar-item-icon"></i>
            <span class="sidebar-item-text">{{ __('Settings') }}</span>
        </a>
    </nav>

    <!-- Collapse Toggle (Desktop) -->
    <div class="absolute bottom-4 left-4 right-4 hidden md:block">
        <button @click="$store.admin.toggleSidebar()"
                class="w-full btn-secondary justify-center">
            <i data-lucide="chevrons-left"
               x-show="!$store.admin.sidebarCollapsed"></i>
            <i data-lucide="chevrons-right"
               x-show="$store.admin.sidebarCollapsed"></i>
        </button>
    </div>
</aside>

<!-- Overlay for mobile -->
<div x-show="$store.admin.sidebarOpen"
     @click="$store.admin.closeSidebar()"
     class="fixed inset-0 bg-black/50 z-40 md:hidden"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"></div>
```

---

## Header Partial

**File:** `resources/views/admin/partials/header.blade.php`

```blade
<header class="header">
    <div class="flex items-center justify-between h-full px-6">
        <!-- Left Side -->
        <div class="flex items-center space-x-4">
            <!-- Mobile Menu Toggle -->
            <button @click="$store.admin.toggleSidebar()"
                    class="btn-icon md:hidden">
                <i data-lucide="menu"></i>
            </button>

            <!-- Search -->
            <button @click="$store.admin.openSearch()"
                    class="btn-icon hidden md:flex">
                <i data-lucide="search"></i>
            </button>

            <!-- Breadcrumbs would go here on larger screens -->
        </div>

        <!-- Right Side -->
        <div class="flex items-center space-x-4">
            <!-- Language Switcher -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="btn-icon">
                    <i data-lucide="globe"></i>
                </button>

                <div x-show="open"
                     @click.away="open = false"
                     class="dropdown-menu absolute right-0 mt-2"
                     :class="{ 'show': open }">
                    <a href="{{ route('admin.language.switch', 'en') }}"
                       class="dropdown-item">
                        English
                    </a>
                    <a href="{{ route('admin.language.switch', 'ar') }}"
                       class="dropdown-item">
                        العربية
                    </a>
                    <a href="{{ route('admin.language.switch', 'tr') }}"
                       class="dropdown-item">
                        Türkçe
                    </a>
                    <a href="{{ route('admin.language.switch', 'ur') }}"
                       class="dropdown-item">
                        اردو
                    </a>
                    <a href="{{ route('admin.language.switch', 'fr') }}"
                       class="dropdown-item">
                        Français
                    </a>
                </div>
            </div>

            <!-- Dark Mode Toggle -->
            <button @click="$store.admin.toggleDarkMode()"
                    class="btn-icon">
                <i x-show="!$store.admin.darkMode" data-lucide="moon"></i>
                <i x-show="$store.admin.darkMode" data-lucide="sun"></i>
            </button>

            <!-- Notifications -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="btn-icon relative">
                    <i data-lucide="bell"></i>
                    <span x-show="$store.admin.unreadNotificationsCount > 0"
                          class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                          x-text="$store.admin.unreadNotificationsCount"></span>
                </button>

                <div x-show="open"
                     @click.away="open = false"
                     class="dropdown-menu absolute right-0 mt-2 w-80"
                     :class="{ 'show': open }">
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ __('Notifications') }}
                            </h3>
                            <button @click="$store.admin.markAllAsRead()"
                                    class="text-xs text-primary-500 hover:text-primary-600">
                                {{ __('Mark all as read') }}
                            </button>
                        </div>
                    </div>

                    <div class="max-h-96 overflow-y-auto custom-scrollbar">
                        <template x-for="notification in $store.admin.notifications" :key="notification.id">
                            <div @click="$store.admin.markAsRead(notification.id)"
                                 class="p-4 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                 :class="{ 'bg-primary-50 dark:bg-primary-900/10': !notification.read }">
                                <div class="flex items-start space-x-3">
                                    <i :data-lucide="notification.icon" class="w-5 h-5 text-primary-500"></i>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-sm text-gray-900 dark:text-white"
                                            x-text="notification.title"></h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400"
                                           x-text="notification.message"></p>
                                        <span class="text-xs text-gray-500"
                                              x-text="new Date(notification.timestamp).toLocaleString()"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('admin.notifications.index') }}"
                           class="block text-center text-sm text-primary-500 hover:text-primary-600">
                            {{ __('View all notifications') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="flex items-center space-x-3">
                    <img src="{{ auth()->user()->avatar ?? asset('images/default-avatar.png') }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-10 h-10 rounded-full border-2 border-primary-500">
                    <span class="hidden md:block text-sm font-semibold text-gray-900 dark:text-white">
                        {{ auth()->user()->name }}
                    </span>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600 dark:text-gray-400"></i>
                </button>

                <div x-show="open"
                     @click.away="open = false"
                     class="dropdown-menu absolute right-0 mt-2"
                     :class="{ 'show': open }">
                    <a href="{{ route('admin.profile') }}" class="dropdown-item">
                        <i data-lucide="user"></i>
                        <span>{{ __('Profile') }}</span>
                    </a>
                    <a href="{{ route('admin.settings') }}" class="dropdown-item">
                        <i data-lucide="settings"></i>
                        <span>{{ __('Settings') }}</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item w-full text-left">
                            <i data-lucide="log-out"></i>
                            <span>{{ __('Logout') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
```

---

## Example Dashboard Page

**File:** `resources/views/admin/dashboard.blade.php`

```blade
@extends('layouts.admin')

@section('title', __('Dashboard'))

@section('content')
<div class="animate-fade-in-up">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-gradient text-4xl font-black mb-2">
            {{ __('Dashboard') }}
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            {{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}
        </p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Articles -->
        <div class="glass-card animate-fade-in-up stagger-1">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-[#0056b3] to-[#218838] rounded-xl flex items-center justify-center">
                    <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
                </div>
                <span class="badge badge-success">
                    +12%
                </span>
            </div>
            <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1">
                1,234
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Total Articles') }}
            </p>
        </div>

        <!-- Total Users -->
        <div class="glass-card animate-fade-in-up stagger-2">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-r from-[#64CCC5] to-[#0056b3] rounded-xl flex items-center justify-center">
                    <i data-lucide="users" class="w-6 h-6 text-white"></i>
                </div>
                <span class="badge badge-success">
                    +8%
                </span>
            </div>
            <h3 class="text-3xl font-black text-gray-900 dark:text-white mb-1">
                5,678
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Total Users') }}
            </p>
        </div>

        <!-- Add more stat cards... -->
    </div>

    <!-- Charts and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Articles -->
        <div class="glass-card">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                {{ __('Recent Articles') }}
            </h3>

            <div class="space-y-4">
                @foreach($recentArticles as $article)
                <div class="flex items-center space-x-4 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer">
                    <img src="{{ $article->featured_image }}"
                         alt="{{ $article->title }}"
                         class="w-16 h-16 rounded-lg object-cover">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900 dark:text-white truncate">
                            {{ $article->title }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $article->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <span class="badge badge-primary">
                        {{ $article->views }} views
                    </span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Activity Log -->
        <div class="glass-card">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                {{ __('Recent Activity') }}
            </h3>

            <div class="space-y-4">
                @foreach($activities as $activity)
                <div class="flex items-start space-x-4">
                    <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $activity->icon }}" class="w-5 h-5 text-primary-500"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ $activity->description }}
                        </p>
                        <span class="text-xs text-gray-500">
                            {{ $activity->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize charts or other interactive features
    document.addEventListener('DOMContentLoaded', () => {
        // Chart.js initialization would go here
    });
</script>
@endpush
```

---

## Example Form Page

**File:** `resources/views/admin/articles/create.blade.php`

```blade
@extends('layouts.admin')

@section('title', __('Create Article'))

@section('content')
<div class="max-w-4xl mx-auto animate-fade-in-up">
    <div class="glass-card">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-gradient text-3xl font-black mb-2">
                {{ __('Create New Article') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('Fill in the details below to create a new article') }}
            </p>
        </div>

        <!-- Form -->
        <form action="{{ route('admin.articles.store') }}"
              method="POST"
              enctype="multipart/form-data"
              x-data="{ title: '', slug: '' }">
            @csrf

            <!-- Title -->
            <div class="mb-6">
                <label class="block mb-2 font-semibold text-gray-900 dark:text-white">
                    {{ __('Title') }} <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="title"
                       x-model="title"
                       @input="slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-')"
                       class="input-custom @error('title') border-red-500 @enderror"
                       placeholder="{{ __('Enter article title') }}"
                       value="{{ old('title') }}"
                       required>
                @error('title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug -->
            <div class="mb-6">
                <label class="block mb-2 font-semibold text-gray-900 dark:text-white">
                    {{ __('Slug') }}
                </label>
                <input type="text"
                       name="slug"
                       x-model="slug"
                       class="input-custom @error('slug') border-red-500 @enderror"
                       placeholder="{{ __('article-slug') }}"
                       value="{{ old('slug') }}">
                @error('slug')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Content -->
            <div class="mb-6">
                <label class="block mb-2 font-semibold text-gray-900 dark:text-white">
                    {{ __('Content') }} <span class="text-red-500">*</span>
                </label>
                <textarea name="content"
                          rows="10"
                          class="input-custom @error('content') border-red-500 @enderror"
                          placeholder="{{ __('Write your article content here...') }}"
                          required>{{ old('content') }}</textarea>
                @error('content')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Featured Image -->
            <div class="mb-6">
                <label class="block mb-2 font-semibold text-gray-900 dark:text-white">
                    {{ __('Featured Image') }}
                </label>
                <input type="file"
                       name="featured_image"
                       accept="image/*"
                       class="input-custom @error('featured_image') border-red-500 @enderror">
                @error('featured_image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status -->
            <div class="mb-6">
                <label class="block mb-2 font-semibold text-gray-900 dark:text-white">
                    {{ __('Status') }}
                </label>
                <select name="status" class="select-custom">
                    <option value="draft">{{ __('Draft') }}</option>
                    <option value="published">{{ __('Published') }}</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('admin.articles.index') }}" class="btn-secondary">
                    {{ __('Cancel') }}
                </a>
                <button type="submit" class="btn-primary">
                    <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                    {{ __('Create Article') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
```

This guide provides complete, production-ready Blade templates using the admin design system.
