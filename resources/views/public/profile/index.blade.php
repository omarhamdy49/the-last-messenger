@extends('layouts.public')

@section('title', __('profile.my_profile') . ' — ' . __('site.site_name'))
@section('robots', 'noindex, nofollow')

@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);

    // Engagement stats with safe defaults.
    $score = $engagement['engagement_score'] ?? 0;
    $level = $engagement['engagement_level'] ?? null;
    $streak = $engagement['streak_days'] ?? 0;
    $totalInteractions = $engagement['total_interactions'] ?? 0;
    $breakdown = $engagement['breakdown'] ?? [];
    $views = $breakdown['views'] ?? 0;
    $likes = $breakdown['likes'] ?? 0;
    $shares = $breakdown['shares'] ?? 0;
    $bookmarksTotal = $bookmarkCounts['all'] ?? 0;

    $tabs = [
        ['id' => 'dashboard', 'label' => __('profile.tab_dashboard'), 'icon' => 'layout-dashboard'],
        ['id' => 'bookmarks', 'label' => __('profile.tab_bookmarks'), 'icon' => 'bookmark'],
        ['id' => 'progress',  'label' => __('profile.tab_progress'),  'icon' => 'trending-up'],
        ['id' => 'activity',  'label' => __('profile.tab_activity'),  'icon' => 'activity'],
        ['id' => 'settings',  'label' => __('profile.tab_settings'),  'icon' => 'settings'],
    ];

    $statCards = [
        ['icon' => 'flame', 'value' => $streak, 'label' => __('profile.current_streak'), 'suffix' => ' ' . __('profile.days'), 'tone' => 'text-orange-500'],
        ['icon' => 'eye', 'value' => $views, 'label' => __('profile.total_views'), 'suffix' => '', 'tone' => 'text-primary'],
        ['icon' => 'bookmark', 'value' => $bookmarksTotal, 'label' => __('profile.total_bookmarks'), 'suffix' => '', 'tone' => 'text-secondary'],
        ['icon' => 'zap', 'value' => $score, 'label' => __('profile.engagement_score'), 'suffix' => '', 'tone' => 'text-amber-500'],
    ];

    $activityVerb = [
        'view' => __('profile.activity_viewed'),
        'bookmark' => __('profile.activity_bookmarked'),
        'like' => __('profile.activity_liked'),
        'complete' => __('profile.activity_completed'),
    ];
@endphp

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="profilePage()"
     x-init="init()">

    {{-- Profile header --}}
    <header class="card-premium rounded-2xl p-6 sm:p-8 mb-6 flex flex-col sm:flex-row sm:items-center gap-5">
        {{-- Avatar (uploaded image, fallback to initial) with camera-overlay upload --}}
        <div class="relative group shrink-0 self-start sm:self-auto">
            @if ($user->avatarUrl())
                <div class="w-20 h-20 rounded-2xl overflow-hidden ring-4 ring-white dark:ring-gray-700">
                    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                </div>
            @else
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center text-white text-3xl font-bold">
                    {{ mb_substr($user->name, 0, 1) }}
                </div>
            @endif
            <form method="POST" action="{{ route('public.profile.avatar') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="avatar" id="avatar-input" class="sr-only" x-ref="avatarInput"
                       accept="image/jpeg,image/png,image/webp"
                       @change="uploadingAvatar = true; $el.form.submit()">
                <button type="button"
                        @click="$refs.avatarInput.click()"
                        :disabled="uploadingAvatar"
                        class="absolute bottom-0 end-0 w-8 h-8 rounded-full bg-gradient-to-l from-primary to-secondary text-white flex items-center justify-center shadow-md opacity-90 sm:opacity-0 sm:group-hover:opacity-100 focus:opacity-100 transition-opacity disabled:opacity-60"
                        aria-label="{{ __('profile.change_avatar') }}"
                        title="{{ __('profile.change_avatar') }}">
                    <i data-lucide="camera" class="w-4 h-4" x-show="!uploadingAvatar"></i>
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="uploadingAvatar" x-cloak></i>
                </button>
            </form>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('profile.welcome_back') }}</p>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white truncate">{{ $user->name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                {{ __('profile.member_since') }}
                {{ $user->created_at?->translatedFormat('F Y') }}
            </p>
        </div>
        @if ($level)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 self-start sm:self-center">
                <i data-lucide="award" class="w-4 h-4"></i>
                {{ ucfirst((string) $level) }}
            </span>
        @endif
    </header>

    {{-- Avatar upload validation errors --}}
    @error('avatar')
        <div class="mb-6 rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-300 flex items-start gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
            <span class="break-words">{{ $message }}</span>
        </div>
    @enderror

    {{-- Unverified email banner (informational — browsing is never gated) --}}
    @if (! $user->hasVerifiedEmail())
        <div class="mb-6 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 px-4 py-3 flex flex-wrap items-center gap-3">
            <i data-lucide="mail-warning" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0"></i>
            <p class="text-sm text-amber-800 dark:text-amber-200 flex-1 min-w-[12rem] break-words">
                {{ __('profile.email_unverified_banner') }}
            </p>
            <form method="POST" action="{{ route('public.verification.send') }}"
                  x-data="{ sending: false }" @submit="sending = true">
                @csrf
                <button type="submit" :disabled="sending"
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold text-amber-800 dark:text-amber-200 border border-amber-400/60 hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors disabled:opacity-60">
                    <i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin" x-show="sending" x-cloak></i>
                    <i data-lucide="send" class="w-3.5 h-3.5" x-show="!sending"></i>
                    {{ __('profile.resend_verification') }}
                </button>
            </form>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex items-center gap-1 overflow-x-auto scrollbar-hide -mb-px">
            @foreach ($tabs as $tab)
                <button type="button"
                        @click="switchTab('{{ $tab['id'] }}')"
                        :class="activeTab === '{{ $tab['id'] }}'
                            ? 'text-primary border-primary'
                            : 'text-gray-500 dark:text-gray-400 border-transparent hover:text-gray-800 dark:hover:text-gray-200'"
                        class="whitespace-nowrap shrink-0 flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors">
                    <i data-lucide="{{ $tab['icon'] }}" class="w-4 h-4"></i>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ============================ DASHBOARD ============================ --}}
    <section x-show="activeTab === 'dashboard'" x-cloak class="space-y-6">
        {{-- Stat cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($statCards as $card)
                <div class="card-premium rounded-2xl p-5 flex flex-col gap-2">
                    <span class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-[#0f3460] flex items-center justify-center {{ $card['tone'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="w-5 h-5"></i>
                    </span>
                    <span class="text-3xl font-bold gradient-text">{{ number_format((int) $card['value']) }}{{ $card['suffix'] }}</span>
                    <span class="text-sm text-gray-600 dark:text-gray-300">{{ $card['label'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Recent bookmarks preview --}}
        <div class="card-premium rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('profile.bookmarks_title') }}</h2>
                <a href="{{ $favoritesUrl }}" class="text-sm font-semibold text-primary hover:underline flex items-center gap-1">
                    {{ __('profile.view_all_bookmarks') }}
                    <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4"></i>
                </a>
            </div>

            @if ($recentBookmarks->isEmpty())
                <x-site.empty-state
                    icon="bookmark"
                    :title="__('profile.no_bookmarks_title')"
                    :message="__('profile.no_bookmarks_message')" />
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($recentBookmarks as $item)
                        <x-site.bookmark-card :item="$item" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ============================ BOOKMARKS ============================ --}}
    <section x-show="activeTab === 'bookmarks'" x-cloak>
        <div class="card-premium rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('profile.bookmarks_title') }}</h2>
                <a href="{{ $favoritesUrl }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold text-primary border border-primary/30 hover:bg-primary/5 transition-colors">
                    {{ __('profile.view_all_bookmarks') }}
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                </a>
            </div>

            @if ($recentBookmarks->isEmpty())
                <x-site.empty-state
                    icon="bookmark"
                    :title="__('profile.no_bookmarks_title')"
                    :message="__('profile.no_bookmarks_message')">
                    <a href="{{ route('public.home') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white bg-gradient-to-l from-primary to-secondary hover:opacity-90">
                        <i data-lucide="compass" class="w-4 h-4"></i>
                        {{ __('profile.browse_content') }}
                    </a>
                </x-site.empty-state>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($recentBookmarks as $item)
                        <x-site.bookmark-card :item="$item" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ============================ PROGRESS ============================ --}}
    <section x-show="activeTab === 'progress'" x-cloak>
        <div class="card-premium rounded-2xl p-6 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl bg-gray-50 dark:bg-[#0f3460] p-5 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('profile.current_streak') }}</p>
                    <p class="text-3xl font-bold text-orange-500">{{ number_format((int) $streak) }} {{ __('profile.days') }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 dark:bg-[#0f3460] p-5 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('profile.total_interactions') }}</p>
                    <p class="text-3xl font-bold text-primary">{{ number_format((int) $totalInteractions) }}</p>
                </div>
                <div class="rounded-xl bg-gray-50 dark:bg-[#0f3460] p-5 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">{{ __('profile.engagement_score') }}</p>
                    <p class="text-3xl font-bold text-amber-500">{{ number_format((int) $score) }}</p>
                </div>
            </div>

            @if ($totalInteractions === 0)
                <x-site.empty-state
                    icon="trending-up"
                    :title="__('profile.no_progress_title')"
                    :message="__('profile.no_progress_message')" />
            @endif
        </div>
    </section>

    {{-- ============================ ACTIVITY ============================ --}}
    <section x-show="activeTab === 'activity'" x-cloak>
        <div class="card-premium rounded-2xl p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">{{ __('profile.recent_activity') }}</h2>

            @if ($activity->isEmpty())
                <x-site.empty-state
                    icon="activity"
                    :title="__('profile.no_activity_title')"
                    :message="__('profile.no_activity_message')" />
            @else
                <ul class="space-y-3">
                    @foreach ($activity as $entry)
                        @php
                            $type = $entry->interaction_type?->value ?? 'view';
                            $verb = $activityVerb[$type] ?? __('profile.activity_viewed');
                            $contentName = class_basename((string) $entry->interactable_type);
                        @endphp
                        <li class="flex items-start gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-[#0f3460]/50 transition-colors">
                            <span class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <i data-lucide="{{ $type === 'bookmark' ? 'bookmark' : ($type === 'like' ? 'heart' : ($type === 'complete' ? 'check-circle' : 'eye')) }}" class="w-4 h-4"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm text-gray-800 dark:text-gray-200 truncate">
                                    <span class="font-semibold">{{ $verb }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ $contentName }}</span>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ __('profile.activity_on') }} {{ $entry->created_at?->translatedFormat('d M Y') }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    {{-- ============================ SETTINGS ============================ --}}
    <section x-show="activeTab === 'settings'" x-cloak class="space-y-6">
        {{-- Account settings --}}
        <div class="card-premium rounded-2xl p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('profile.account_settings') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('profile.account_settings_hint') }}</p>

            <form method="POST" action="{{ route('public.profile.update') }}"
                  @submit="savingProfile = true" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-semibold mb-1.5">{{ __('profile.name') }}</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460] text-base sm:text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                    @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold mb-1.5">{{ __('profile.email') }}</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required dir="ltr"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460] text-base sm:text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                    @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="language" class="block text-sm font-semibold mb-1.5">{{ __('profile.language') }}</label>
                    <select id="language" name="language"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460] text-base sm:text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                        @foreach (['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $code => $name)
                            <option value="{{ $code }}" @selected(old('language', $user->language) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('language') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" :disabled="savingProfile"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-semibold text-white bg-gradient-to-l from-primary to-secondary hover:opacity-90 transition-opacity disabled:opacity-60">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="savingProfile" x-cloak></i>
                    <span x-text="savingProfile ? '{{ __('profile.saving') }}' : '{{ __('profile.save_changes') }}'"></span>
                </button>
            </form>
        </div>

        {{-- Change password --}}
        <div class="card-premium rounded-2xl p-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('profile.password_settings') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">{{ __('profile.password_settings_hint') }}</p>

            <form method="POST" action="{{ route('public.profile.password') }}"
                  @submit="savingPassword = true" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-semibold mb-1.5">{{ __('profile.current_password') }}</label>
                    <input type="password" id="current_password" name="current_password" required dir="ltr"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460] text-base sm:text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                    @error('current_password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold mb-1.5">{{ __('profile.new_password') }}</label>
                    <input type="password" id="password" name="password" required dir="ltr"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460] text-base sm:text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                    @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold mb-1.5">{{ __('profile.confirm_password') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required dir="ltr"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460] text-base sm:text-sm focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none">
                </div>

                <button type="submit" :disabled="savingPassword"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-semibold text-white bg-gradient-to-l from-primary to-secondary hover:opacity-90 transition-opacity disabled:opacity-60">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="savingPassword" x-cloak></i>
                    <span x-text="savingPassword ? '{{ __('profile.saving') }}' : '{{ __('profile.update_password') }}'"></span>
                </button>
            </form>
        </div>

        @if ($exportAvailable)
            <div class="card-premium rounded-2xl p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('profile.data_settings') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('profile.download_my_data_hint') }}</p>
                <a href="{{ $exportUrl ?? '#' }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-primary border border-primary/30 hover:bg-primary/5">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    {{ __('profile.download_my_data') }}
                </a>
            </div>
        @endif

        {{-- Danger zone: permanent account deletion --}}
        <div class="card-premium rounded-2xl p-6 border border-red-200 dark:border-red-900/60">
            <h2 class="text-lg font-bold text-red-600 dark:text-red-400">{{ __('profile.delete_account') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 break-words">{{ __('profile.delete_account_hint') }}</p>

            @error('password', 'deleteAccount')
                <p class="text-sm text-red-600 dark:text-red-400 mb-3 break-words">{{ $message }}</p>
            @enderror

            <button type="button" @click="deleteModalOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-red-600 dark:text-red-400 border border-red-300 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                {{ __('profile.delete_account') }}
            </button>
        </div>
    </section>

    {{-- Delete account confirmation modal (type-to-confirm + password) --}}
    <div x-show="deleteModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="deletingAccount || (deleteModalOpen = false)">
        <div class="absolute inset-0 bg-black/60" @click="deletingAccount || (deleteModalOpen = false)"></div>
        <div class="relative w-full max-w-md max-h-[80vh] overflow-y-auto rounded-2xl bg-white dark:bg-[#16213e] p-6 shadow-2xl"
             role="dialog" aria-modal="true" aria-labelledby="delete-account-title">
            <h3 id="delete-account-title" class="text-lg font-bold text-red-600 dark:text-red-400 mb-2">
                {{ __('profile.delete_account_confirm_title') }}
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 break-words">
                {{ __('profile.delete_account_confirm_message') }}
            </p>

            <form method="POST" action="{{ route('public.profile.destroy') }}"
                  @submit="deletingAccount = true" class="space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label for="delete-confirm-word" class="block text-sm font-semibold mb-1.5">
                        {{ __('profile.delete_type_to_confirm', ['word' => __('profile.delete_confirm_word')]) }}
                    </label>
                    <input type="text" id="delete-confirm-word" x-model="deleteConfirmText"
                           autocomplete="off"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460] text-base sm:text-sm focus:border-red-400 focus:ring-2 focus:ring-red-400/20 outline-none">
                </div>

                <div>
                    <label for="delete-password" class="block text-sm font-semibold mb-1.5">{{ __('profile.delete_password_label') }}</label>
                    <input type="password" id="delete-password" name="password" required dir="ltr"
                           class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0f3460] text-base sm:text-sm focus:border-red-400 focus:ring-2 focus:ring-red-400/20 outline-none">
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                    <button type="button" @click="deleteModalOpen = false" :disabled="deletingAccount"
                            class="px-5 py-2.5 rounded-full text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#0f3460] transition-colors disabled:opacity-60">
                        {{ __('profile.cancel') }}
                    </button>
                    <button type="submit"
                            :disabled="deletingAccount || deleteConfirmText !== '{{ __('profile.delete_confirm_word') }}'"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="deletingAccount" x-cloak></i>
                        {{ __('profile.delete_account_cta') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function profilePage() {
        return {
            activeTab: 'dashboard',
            savingProfile: false,
            savingPassword: false,
            uploadingAvatar: false,
            deleteModalOpen: false,
            deletingAccount: false,
            deleteConfirmText: '',
            validTabs: ['dashboard', 'bookmarks', 'progress', 'activity', 'settings'],
            init() {
                // Honour a #fragment (e.g. redirect after settings save) or a saved tab.
                const fragment = (window.location.hash || '').replace('#', '');
                if (this.validTabs.includes(fragment)) {
                    this.activeTab = fragment;
                }
                @if ($errors->deleteAccount->isNotEmpty())
                    // A failed deletion attempt returns the user to the danger zone.
                    this.activeTab = 'settings';
                @endif
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },
            switchTab(tab) {
                this.activeTab = tab;
                if (history.replaceState) {
                    history.replaceState(null, '', '#' + tab);
                }
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            },
        };
    }
</script>
@endpush
@endsection
