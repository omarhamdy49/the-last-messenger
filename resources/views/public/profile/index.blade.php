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
        ['id' => 'playlists', 'label' => __('quran.playlists.my'),      'icon' => 'list-music'],
        ['id' => 'progress',  'label' => __('profile.tab_progress'),  'icon' => 'trending-up'],
        ['id' => 'activity',  'label' => __('profile.tab_activity'),  'icon' => 'activity'],
        ['id' => 'settings',  'label' => __('profile.tab_settings'),  'icon' => 'settings'],
    ];

    $statCards = [
        ['icon' => 'flame', 'value' => $streak, 'label' => __('profile.current_streak'), 'suffix' => ' ' . __('profile.days')],
        ['icon' => 'eye', 'value' => $views, 'label' => __('profile.total_views'), 'suffix' => ''],
        ['icon' => 'bookmark', 'value' => $bookmarksTotal, 'label' => __('profile.total_bookmarks'), 'suffix' => ''],
        ['icon' => 'zap', 'value' => $score, 'label' => __('profile.engagement_score'), 'suffix' => ''],
    ];

    $activityVerb = [
        'view' => __('profile.activity_viewed'),
        'bookmark' => __('profile.activity_bookmarked'),
        'like' => __('profile.activity_liked'),
        'complete' => __('profile.activity_completed'),
    ];
@endphp

@section('content')
<div class="sx min-h-[100dvh] pb-20" x-data="profilePage()" x-init="init()">
    <div class="sx-wrap pt-8">

        {{-- Profile header --}}
        <header class="sx-surface mb-6 flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:p-8">
            {{-- Avatar (uploaded image, fallback to initial) with camera-overlay upload --}}
            <div class="group relative shrink-0 self-start sm:self-auto">
                <span class="sx-avatar is-square !h-20 !w-20 text-3xl font-bold">
                    @if ($user->avatarUrl())
                        <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}">
                    @else
                        {{ mb_substr($user->name, 0, 1) }}
                    @endif
                </span>
                <form method="POST" action="{{ route('public.profile.avatar') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="avatar" id="avatar-input" class="sr-only" x-ref="avatarInput"
                           accept="image/jpeg,image/png,image/webp"
                           @change="uploadingAvatar = true; $el.form.submit()">
                    <button type="button"
                            @click="$refs.avatarInput.click()"
                            :disabled="uploadingAvatar"
                            style="background: var(--sx-grad)"
                            class="absolute bottom-0 end-0 flex h-8 w-8 items-center justify-center rounded-full text-white opacity-90 shadow-md transition-opacity focus:opacity-100 disabled:opacity-60 sm:opacity-0 sm:group-hover:opacity-100"
                            aria-label="{{ __('profile.change_avatar') }}"
                            title="{{ __('profile.change_avatar') }}">
                        <i data-lucide="camera" class="w-4 h-4" x-show="!uploadingAvatar"></i>
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="uploadingAvatar" x-cloak></i>
                    </button>
                </form>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm text-[color:var(--sx-muted)]">{{ __('profile.welcome_back') }}</p>
                <h1 class="sx-clamp-1 text-2xl font-extrabold text-[color:var(--sx-ink)] sm:text-3xl" title="{{ $user->name }}">{{ $user->name }}</h1>
                <p class="sx-clamp-1 text-sm text-[color:var(--sx-muted)]" title="{{ $user->email }}">{{ $user->email }}</p>
                <p class="mt-1 text-xs text-[color:var(--sx-muted)]">
                    {{ __('profile.member_since') }}
                    {{ $user->created_at?->translatedFormat('F Y') }}
                </p>
            </div>
            @if ($level)
                <span class="sx-chip self-start !text-amber-700 !bg-amber-500/15 dark:!text-amber-300 sm:self-center">
                    <i data-lucide="award" aria-hidden="true"></i>
                    {{ ucfirst((string) $level) }}
                </span>
            @endif
        </header>

        {{-- Avatar upload validation errors --}}
        @error('avatar')
            <div class="sx-note mb-6 !bg-[color:var(--sx-danger-soft)] !text-[color:var(--sx-danger)]" role="alert">
                <i data-lucide="alert-circle" class="mt-1 h-4 w-4 shrink-0 !text-[color:var(--sx-danger)]" aria-hidden="true"></i>
                <span class="break-words text-sm">{{ $message }}</span>
            </div>
        @enderror

        {{-- Unverified email banner (informational — browsing is never gated) --}}
        @if (! $user->hasVerifiedEmail())
            <div class="sx-note mb-6 !bg-amber-500/10 !border-amber-500/30">
                <i data-lucide="mail-warning" class="mt-0.5 h-5 w-5 shrink-0 !text-amber-600 dark:!text-amber-400" aria-hidden="true"></i>
                <div class="flex flex-wrap items-center gap-3">
                    <p class="min-w-[12rem] flex-1 break-words text-sm">
                        {{ __('profile.email_unverified_banner') }}
                    </p>
                    <form method="POST" action="{{ route('public.verification.send') }}"
                          x-data="{ sending: false }" @submit="sending = true">
                        @csrf
                        <button type="submit" :disabled="sending" class="sx-btn !text-xs disabled:opacity-60">
                            <i data-lucide="loader-2" class="animate-spin" x-show="sending" x-cloak></i>
                            <i data-lucide="send" x-show="!sending"></i>
                            {{ __('profile.resend_verification') }}
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Tabs --}}
        <div class="sx-tabs mb-6" role="tablist">
            @foreach ($tabs as $tab)
                <button type="button" role="tab" class="sx-tab shrink-0"
                        id="tab-{{ $tab['id'] }}" aria-controls="panel-{{ $tab['id'] }}"
                        @click="switchTab('{{ $tab['id'] }}')"
                        :aria-selected="(activeTab === '{{ $tab['id'] }}').toString()">
                    <i data-lucide="{{ $tab['icon'] }}" aria-hidden="true"></i>
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        {{-- ============================ DASHBOARD ============================ --}}
        <section id="panel-dashboard" role="tabpanel" aria-labelledby="tab-dashboard" tabindex="0" x-show="activeTab === 'dashboard'" x-cloak class="space-y-6">
            {{-- Stat cards --}}
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($statCards as $card)
                    <div class="sx-surface sx-stat flex flex-col gap-2">
                        <span class="sx-tile-icon"><i data-lucide="{{ $card['icon'] }}" aria-hidden="true"></i></span>
                        <span class="sx-stat-value !text-3xl">{{ number_format((int) $card['value']) }}{{ $card['suffix'] }}</span>
                        <span class="sx-stat-label !mt-0">{{ $card['label'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Recent bookmarks preview --}}
            <div class="sx-surface p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-extrabold text-[color:var(--sx-ink)]">{{ __('profile.bookmarks_title') }}</h2>
                    <a href="{{ $favoritesUrl }}" class="inline-flex items-center gap-1 text-sm font-bold text-[color:var(--sx-accent)] hover:underline underline-offset-4">
                        {{ __('profile.view_all_bookmarks') }}
                        <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4" aria-hidden="true"></i>
                    </a>
                </div>

                @if ($recentBookmarks->isEmpty())
                    <x-site.empty-state
                        icon="bookmark"
                        :title="__('profile.no_bookmarks_title')"
                        :message="__('profile.no_bookmarks_message')" />
                @else
                    <div class="sx-grid is-tight">
                        @foreach ($recentBookmarks as $item)
                            <x-site.bookmark-card :item="$item" />
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ============================ BOOKMARKS ============================ --}}
        <section id="panel-bookmarks" role="tabpanel" aria-labelledby="tab-bookmarks" tabindex="0" x-show="activeTab === 'bookmarks'" x-cloak>
            <div class="sx-surface p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-extrabold text-[color:var(--sx-ink)]">{{ __('profile.bookmarks_title') }}</h2>
                    <a href="{{ $favoritesUrl }}" class="sx-btn">
                        {{ __('profile.view_all_bookmarks') }}
                        <i data-lucide="external-link" aria-hidden="true"></i>
                    </a>
                </div>

                @if ($recentBookmarks->isEmpty())
                    <x-site.empty-state
                        icon="bookmark"
                        :title="__('profile.no_bookmarks_title')"
                        :message="__('profile.no_bookmarks_message')">
                        <a href="{{ route('public.home') }}" class="sx-btn is-primary mt-2">
                            <i data-lucide="compass" aria-hidden="true"></i>
                            {{ __('profile.browse_content') }}
                        </a>
                    </x-site.empty-state>
                @else
                    <div class="sx-grid is-tight">
                        @foreach ($recentBookmarks as $item)
                            <x-site.bookmark-card :item="$item" />
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ============================ PROGRESS ============================ --}}
        <section id="panel-progress" role="tabpanel" aria-labelledby="tab-progress" tabindex="0" x-show="activeTab === 'progress'" x-cloak>
            <div class="sx-surface space-y-6 p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="sx-panel p-5 text-center">
                        <dt class="mb-1 text-sm text-[color:var(--sx-muted)]">{{ __('profile.current_streak') }}</dt>
                        <dd class="text-3xl font-extrabold tabular-nums text-[color:var(--sx-ink)]">{{ number_format((int) $streak) }} {{ __('profile.days') }}</dd>
                    </div>
                    <div class="sx-panel p-5 text-center">
                        <dt class="mb-1 text-sm text-[color:var(--sx-muted)]">{{ __('profile.total_interactions') }}</dt>
                        <dd class="text-3xl font-extrabold tabular-nums text-[color:var(--sx-ink)]">{{ number_format((int) $totalInteractions) }}</dd>
                    </div>
                    <div class="sx-panel p-5 text-center">
                        <dt class="mb-1 text-sm text-[color:var(--sx-muted)]">{{ __('profile.engagement_score') }}</dt>
                        <dd class="text-3xl font-extrabold tabular-nums text-[color:var(--sx-ink)]">{{ number_format((int) $score) }}</dd>
                    </div>
                </dl>

                @if ($totalInteractions === 0)
                    <x-site.empty-state
                        icon="trending-up"
                        :title="__('profile.no_progress_title')"
                        :message="__('profile.no_progress_message')" />
                @endif
            </div>
        </section>

        {{-- ============================ ACTIVITY ============================ --}}
        <section id="panel-activity" role="tabpanel" aria-labelledby="tab-activity" tabindex="0" x-show="activeTab === 'activity'" x-cloak>
            <div class="sx-surface p-6">
                <h2 class="mb-4 text-lg font-extrabold text-[color:var(--sx-ink)]">{{ __('profile.recent_activity') }}</h2>

                @if ($activity->isEmpty())
                    <x-site.empty-state
                        icon="activity"
                        :title="__('profile.no_activity_title')"
                        :message="__('profile.no_activity_message')" />
                @else
                    <ul class="sx-tile-list">
                        @foreach ($activity as $entry)
                            @php
                                $type = $entry->interaction_type?->value ?? 'view';
                                $verb = $activityVerb[$type] ?? __('profile.activity_viewed');
                                $contentName = class_basename((string) $entry->interactable_type);
                            @endphp
                            <li class="sx-tile">
                                <span class="sx-tile-icon">
                                    <i data-lucide="{{ $type === 'bookmark' ? 'bookmark' : ($type === 'like' ? 'heart' : ($type === 'complete' ? 'check-circle' : 'eye')) }}" aria-hidden="true"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="sx-tile-title !font-normal">
                                        <span class="font-bold">{{ $verb }}</span>
                                        <span class="text-[color:var(--sx-muted)]">{{ $contentName }}</span>
                                    </p>
                                    <p class="sx-tile-sub">
                                        {{ __('profile.activity_on') }} {{ $entry->created_at?->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- ============================ PLAYLISTS ============================ --}}
        <section id="panel-playlists" role="tabpanel" aria-labelledby="tab-playlists" tabindex="0" x-show="activeTab === 'playlists'" x-cloak>
            <div class="sx-surface p-6">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-extrabold text-[color:var(--sx-ink)]">{{ __('quran.playlists.my') }}</h2>
                    <a href="{{ route('public.quran.playlists.create') }}" class="sx-btn is-primary">
                        <i data-lucide="list-plus" aria-hidden="true"></i>{{ __('quran.playlists.new') }}
                    </a>
                </div>
                @if (count($playlists))
                    <div class="sx-grid is-tight">
                        @foreach ($playlists as $i => $card)
                            <x-site.playlist-card :card="$card" :index="$i" />
                        @endforeach
                        <a href="{{ route('public.quran.playlists.create') }}"
                           class="flex min-h-[160px] flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-[color:var(--sx-line-strong)] p-6 text-[color:var(--sx-muted)] transition-colors hover:border-[color:var(--sx-accent)] hover:text-[color:var(--sx-accent)]">
                            <i data-lucide="plus" class="w-6 h-6" aria-hidden="true"></i>
                            <span class="text-sm font-bold">{{ __('quran.playlists.new') }}</span>
                        </a>
                    </div>
                @else
                    <x-site.empty-state icon="list-music" :title="__('quran.playlists.empty_mine')" :message="__('quran.playlists.empty_mine_hint')" class="!py-10">
                        <a href="{{ route('public.quran.playlists.create') }}" class="sx-btn mt-2">
                            <i data-lucide="list-plus" aria-hidden="true"></i>{{ __('quran.playlists.create') }}
                        </a>
                    </x-site.empty-state>
                @endif
            </div>
        </section>

        {{-- ============================ SETTINGS ============================ --}}
        <section id="panel-settings" role="tabpanel" aria-labelledby="tab-settings" tabindex="0" x-show="activeTab === 'settings'" x-cloak class="space-y-6">
            {{-- Account settings --}}
            <div class="sx-surface p-6">
                <h2 class="text-lg font-extrabold text-[color:var(--sx-ink)]">{{ __('profile.account_settings') }}</h2>
                <p class="mb-5 text-sm text-[color:var(--sx-muted)]">{{ __('profile.account_settings_hint') }}</p>

                <form method="POST" action="{{ route('public.profile.update') }}"
                      @submit="savingProfile = true" class="max-w-xl space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="sx-label">{{ __('profile.name') }} <span aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                               @error('name') aria-invalid="true" aria-describedby="profile-name-error" @enderror
                               class="sx-input text-base sm:text-sm @error('name') !border-[color:var(--sx-danger)] @enderror">
                        @error('name') <p id="profile-name-error" class="mt-1.5 text-xs font-semibold text-[color:var(--sx-danger)]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="sx-label">{{ __('profile.email') }} <span aria-hidden="true">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required dir="ltr"
                               @error('email') aria-invalid="true" aria-describedby="profile-email-error" @enderror
                               class="sx-input text-base sm:text-sm @error('email') !border-[color:var(--sx-danger)] @enderror">
                        @error('email') <p id="profile-email-error" class="mt-1.5 text-xs font-semibold text-[color:var(--sx-danger)]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="language" class="sx-label">{{ __('profile.language') }}</label>
                        <select id="language" name="language" class="sx-input text-base sm:text-sm">
                            @foreach (enabled_content_languages() as $code => $name)
                                <option value="{{ $code }}" @selected(old('language', in_array($user->language, enabled_content_locales(), true) ? $user->language : app()->getLocale()) === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('language') <p class="mt-1.5 text-xs font-semibold text-[color:var(--sx-danger)]">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" :disabled="savingProfile" class="sx-btn is-primary disabled:opacity-60">
                        <i data-lucide="loader-2" class="animate-spin" x-show="savingProfile" x-cloak></i>
                        <span x-text="savingProfile ? '{{ __('profile.saving') }}' : '{{ __('profile.save_changes') }}'"></span>
                    </button>
                </form>
            </div>

            {{-- Change password --}}
            <div class="sx-surface p-6">
                <h2 class="text-lg font-extrabold text-[color:var(--sx-ink)]">{{ __('profile.password_settings') }}</h2>
                <p class="mb-5 text-sm text-[color:var(--sx-muted)]">{{ __('profile.password_settings_hint') }}</p>

                <form method="POST" action="{{ route('public.profile.password') }}"
                      @submit="savingPassword = true" class="max-w-xl space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" class="sx-label">{{ __('profile.current_password') }} <span aria-hidden="true">*</span></label>
                        <input type="password" id="current_password" name="current_password" required dir="ltr"
                               @error('current_password') aria-invalid="true" aria-describedby="current-password-error" @enderror
                               class="sx-input text-base sm:text-sm @error('current_password') !border-[color:var(--sx-danger)] @enderror">
                        @error('current_password') <p id="current-password-error" class="mt-1.5 text-xs font-semibold text-[color:var(--sx-danger)]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password" class="sx-label">{{ __('profile.new_password') }} <span aria-hidden="true">*</span></label>
                        <input type="password" id="password" name="password" required dir="ltr"
                               @error('password') aria-invalid="true" aria-describedby="new-password-error" @enderror
                               class="sx-input text-base sm:text-sm @error('password') !border-[color:var(--sx-danger)] @enderror">
                        @error('password') <p id="new-password-error" class="mt-1.5 text-xs font-semibold text-[color:var(--sx-danger)]">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="sx-label">{{ __('profile.confirm_password') }} <span aria-hidden="true">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required dir="ltr"
                               class="sx-input text-base sm:text-sm">
                    </div>

                    <button type="submit" :disabled="savingPassword" class="sx-btn is-primary disabled:opacity-60">
                        <i data-lucide="loader-2" class="animate-spin" x-show="savingPassword" x-cloak></i>
                        <span x-text="savingPassword ? '{{ __('profile.saving') }}' : '{{ __('profile.update_password') }}'"></span>
                    </button>
                </form>
            </div>

            @if ($exportAvailable)
                <div class="sx-surface p-6">
                    <h2 class="text-lg font-extrabold text-[color:var(--sx-ink)]">{{ __('profile.data_settings') }}</h2>
                    <p class="mb-4 text-sm text-[color:var(--sx-muted)]">{{ __('profile.download_my_data_hint') }}</p>
                    <a href="{{ $exportUrl ?? '#' }}" class="sx-btn">
                        <i data-lucide="download" aria-hidden="true"></i>
                        {{ __('profile.download_my_data') }}
                    </a>
                </div>
            @endif

            {{-- Danger zone: permanent account deletion --}}
            <div class="sx-surface p-6" style="border-color: color-mix(in srgb, var(--sx-danger) 40%, transparent)">
                <h2 class="text-lg font-extrabold text-[color:var(--sx-danger)]">{{ __('profile.delete_account') }}</h2>
                <p class="mb-4 break-words text-sm text-[color:var(--sx-muted)]">{{ __('profile.delete_account_hint') }}</p>

                @error('password', 'deleteAccount')
                    <p class="mb-3 break-words text-sm font-semibold text-[color:var(--sx-danger)]">{{ $message }}</p>
                @enderror

                <button type="button" @click="deleteModalOpen = true; $nextTick(() => window.lucide && window.lucide.createIcons())"
                        class="sx-btn is-danger-outline">
                    <i data-lucide="trash-2" aria-hidden="true"></i>
                    {{ __('profile.delete_account') }}
                </button>
            </div>
        </section>

        {{-- Delete account confirmation modal (type-to-confirm + password) --}}
        <div x-show="deleteModalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="deletingAccount || (deleteModalOpen = false)">
            <div class="absolute inset-0 bg-black/60" @click="deletingAccount || (deleteModalOpen = false)"></div>
            <div class="sx-surface relative max-h-[80vh] w-full max-w-md overflow-y-auto p-6"
                 role="dialog" aria-modal="true" aria-labelledby="delete-account-title">
                <h3 id="delete-account-title" class="mb-2 text-lg font-extrabold text-[color:var(--sx-danger)]">
                    {{ __('profile.delete_account_confirm_title') }}
                </h3>
                <p class="mb-4 break-words text-sm text-[color:var(--sx-ink-2)]">
                    {{ __('profile.delete_account_confirm_message') }}
                </p>

                <form method="POST" action="{{ route('public.profile.destroy') }}"
                      @submit="deletingAccount = true" class="space-y-4">
                    @csrf
                    @method('DELETE')

                    <div>
                        <label for="delete-confirm-word" class="sx-label">
                            {{ __('profile.delete_type_to_confirm', ['word' => __('profile.delete_confirm_word')]) }}
                        </label>
                        <input type="text" id="delete-confirm-word" x-model="deleteConfirmText"
                               autocomplete="off" class="sx-input text-base sm:text-sm">
                    </div>

                    <div>
                        <label for="delete-password" class="sx-label">{{ __('profile.delete_password_label') }} <span aria-hidden="true">*</span></label>
                        <input type="password" id="delete-password" name="password" required dir="ltr"
                               class="sx-input text-base sm:text-sm">
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                        <button type="button" @click="deleteModalOpen = false" :disabled="deletingAccount"
                                class="sx-btn !border-transparent !bg-transparent disabled:opacity-60">
                            {{ __('profile.cancel') }}
                        </button>
                        <button type="submit"
                                :disabled="deletingAccount || deleteConfirmText !== '{{ __('profile.delete_confirm_word') }}'"
                                class="sx-btn is-danger-solid disabled:cursor-not-allowed disabled:opacity-50">
                            <i data-lucide="loader-2" class="animate-spin" x-show="deletingAccount" x-cloak></i>
                            {{ __('profile.delete_account_cta') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('head')
<style>
    /* Destructive buttons: the system has one accent and one danger token, but
       no destructive `.sx-btn` modifier — these two are the page's only
       deletion affordances, so they are defined here rather than globally. */
    .sx-btn.is-danger-outline { color: var(--sx-danger); border-color: color-mix(in srgb, var(--sx-danger) 45%, transparent); }
    .sx-btn.is-danger-outline:hover { color: var(--sx-danger); background: var(--sx-danger-soft); border-color: var(--sx-danger); }
    .sx-btn.is-danger-solid { color: #fff; background: var(--sx-danger); border-color: transparent; }
    .sx-btn.is-danger-solid:hover { color: #fff; filter: brightness(1.08); }
</style>
@endpush

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
            validTabs: ['dashboard', 'bookmarks', 'playlists', 'progress', 'activity', 'settings'],
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
