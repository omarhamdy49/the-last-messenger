@extends('layouts.public')

@section('title', ($playlist ? __('quran.playlists.edit_title') : __('quran.playlists.create_title')).' | '.__('quran.playlists.title'))
@section('meta_description', __('quran.playlists.subtitle'))

@push('head')
<style>
    .pl-row { animation: plRow .25s ease-out both; animation-delay: calc(var(--i, 0) * 20ms); }
    @keyframes plRow { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) { .pl-row { animation: none; } }
    .pl-skeleton { background: linear-gradient(90deg, rgba(0,0,0,.05) 25%, rgba(0,0,0,.09) 37%, rgba(0,0,0,.05) 63%); background-size: 400% 100%; animation: plShimmer 1.4s ease infinite; }
    .dark .pl-skeleton { background: linear-gradient(90deg, rgba(255,255,255,.06) 25%, rgba(255,255,255,.12) 37%, rgba(255,255,255,.06) 63%); background-size: 400% 100%; }
    @keyframes plShimmer { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
</style>
@endpush

@section('content')
@php
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
    $props = [
        'profiles' => $profiles,
        'favoriteIds' => $favoriteIds,
        'surahs' => $surahs,
        'initial' => $initial,
        // Numbered per user, so an untouched field still saves under a distinct name.
        'defaultName' => __('quran.playlists.name_default', ['n' => app()->getLocale() === 'ar' ? arabic_digits($count + 1) : $count + 1]),
        'previewUrl' => route('public.quran.playlists.preview'),
        'storeUrl' => route('public.quran.playlists.store'),
        'updateUrl' => $playlist ? route('public.quran.playlists.update', $playlist->slug) : null,
        'audioUrlTemplate' => route('public.quran.playlists.audio', ['surah' => '__S__', 'recording' => '__R__']),
        'locale' => app()->getLocale(),
        't' => [
            'error' => __('quran.playlists.error'),
            'unsaved' => __('quran.playlists.unsaved'),
        ],
    ];
@endphp

<div class="min-h-dvh font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300"
     x-data="playlistWizard(@js($props))"
     x-init="init()"
     :style="$store.audioPlayer.track ? 'padding-bottom: 9rem' : 'padding-bottom: 5rem'">
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">

    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-2 flex-wrap" aria-label="breadcrumb">
        <a href="{{ route('public.quran') }}" class="hover:text-primary transition-colors">{{ __('quran.public.page_title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <a href="{{ route('public.quran.playlists.index') }}" class="hover:text-primary transition-colors">{{ __('quran.playlists.title') }}</a>
        <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span class="text-zinc-900 dark:text-white font-semibold">{{ $playlist ? __('quran.playlists.edit_title') : __('quran.playlists.create_title') }}</span>
    </nav>

    {{-- Header + progress rail --}}
    <header class="grid lg:grid-cols-[1fr_minmax(280px,420px)] items-end gap-6 mb-8">
        <div>
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-zinc-900 dark:text-white text-start">
                {{ $playlist ? __('quran.playlists.edit_title') : __('quran.playlists.create_title') }}
            </h1>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('quran.playlists.cap_hint', ['count' => $count, 'max' => $cap]) }}</p>
        </div>
        <ol class="grid grid-cols-3 gap-2" aria-label="steps">
            @foreach (['step_reciters', 'step_scope', 'step_preview'] as $i => $key)
                <li>
                    <button type="button"
                            @click="goTo({{ $i + 1 }})"
                            :disabled="{{ $i + 1 }} > maxStep"
                            :aria-current="step === {{ $i + 1 }} ? 'step' : null"
                            class="w-full text-start disabled:cursor-not-allowed">
                        <span class="block h-1.5 rounded-full transition-colors duration-300"
                              :class="step >= {{ $i + 1 }} ? 'bg-gradient-to-r from-[#0056b3] to-[#218838]' : 'bg-zinc-200 dark:bg-zinc-800'"></span>
                        <span class="mt-2 block text-xs font-semibold transition-colors"
                              :class="step === {{ $i + 1 }} ? 'text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400'">
                            <span class="tabular-nums">{{ $i + 1 }}.</span> {{ __('quran.playlists.'.$key) }}
                        </span>
                    </button>
                </li>
            @endforeach
        </ol>
    </header>

    {{-- ============================ STEP 1: RECITERS ============================ --}}
    <section x-show="step === 1" x-cloak class="space-y-8">
        <div class="grid md:grid-cols-2 gap-5">
            <div>
                <label for="pl-name" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-1.5">{{ __('quran.playlists.name') }} <span class="text-rose-500" aria-hidden="true">*</span></label>
                <input id="pl-name" type="text" x-model="name" maxlength="80" required autocomplete="off"
                       @input="dirty = true"
                       :aria-invalid="!!errors.name"
                       class="w-full h-12 px-4 rounded-xl border bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-primary/20 transition"
                       :class="errors.name ? 'border-rose-400' : 'border-zinc-200 dark:border-zinc-700 focus:border-primary'">
                <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400" x-show="!errors.name">{{ __('quran.playlists.name_help') }}</p>
                <p class="mt-1.5 text-xs text-rose-600" x-show="errors.name" x-text="errors.name?.[0]" role="alert"></p>
            </div>
            <div>
                <label for="pl-desc" class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100 mb-1.5">{{ __('quran.playlists.description') }}</label>
                <input id="pl-desc" type="text" x-model="description" maxlength="300" autocomplete="off"
                       @input="dirty = true"
                       class="w-full h-12 px-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
                <p class="mt-1.5 text-xs text-rose-600" x-show="errors.description" x-text="errors.description?.[0]" role="alert"></p>
            </div>
        </div>

        <div class="relative">
            <label for="pl-reciter-search" class="sr-only">{{ __('quran.playlists.search_reciters') }}</label>
            <i data-lucide="search" class="w-4 h-4 absolute top-1/2 -translate-y-1/2 start-3.5 text-zinc-400 pointer-events-none"></i>
            <input id="pl-reciter-search" type="search" x-model="reciterSearch" placeholder="{{ __('quran.playlists.search_reciters') }}" autocomplete="off"
                   class="w-full h-12 ps-10 pe-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
        </div>

        <template x-for="group in groups" :key="group.key">
            <div x-show="group.items.length">
                <h2 class="flex items-center gap-2 text-sm font-bold text-zinc-700 dark:text-zinc-200 mb-3">
                    <i :data-lucide="group.icon" class="w-4 h-4 text-primary"></i>
                    <span x-text="group.label"></span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                    <template x-for="p in group.items" :key="p.id">
                        <button type="button" role="checkbox"
                                :aria-checked="selected.has(p.id)"
                                @click="toggleProfile(p.id)"
                                class="group relative text-start rounded-2xl border p-3 min-h-[44px] bg-white dark:bg-zinc-900 transition-[transform,border-color,box-shadow] duration-200 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60"
                                :class="selected.has(p.id) ? 'border-primary shadow-[0_0_0_3px_rgba(0,86,179,0.12)]' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-400'">
                            <span class="absolute top-2.5 end-2.5 w-6 h-6 rounded-full flex items-center justify-center transition-colors"
                                  :class="selected.has(p.id) ? 'bg-primary text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-transparent'">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </span>
                            <span class="block w-16 h-16 rounded-full overflow-hidden mx-auto mb-2 bg-zinc-100 dark:bg-zinc-800">
                                <template x-if="p.image"><img :src="p.image" :alt="p.name" class="w-full h-full object-cover" loading="lazy" width="64" height="64"></template>
                                <template x-if="!p.image"><span class="w-full h-full flex items-center justify-center text-center px-1.5 text-[11px] font-bold leading-tight text-white bg-gradient-to-br from-[#0056b3] to-[#218838]" :title="p.name"><span class="line-clamp-3 break-words" x-text="p.name"></span></span></template>
                            </span>
                            <span class="block text-center text-sm font-bold text-zinc-900 dark:text-white leading-snug line-clamp-2" x-text="p.name"></span>
                            <span class="mt-1 flex items-center justify-center gap-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                <span x-text="p.flag"></span>
                                <span x-text="p.styles[0] || ''" class="truncate"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <x-site.empty-state icon="mic-off" x-show="!groups.some(g => g.items.length)" x-cloak
            :title="__('quran.playlists.empty_search')" :message="__('site.try_adjusting_filters')" />
    </section>

    {{-- ============================ STEP 2: SCOPE ============================ --}}
    <section x-show="step === 2" x-cloak class="space-y-8">
        <div class="grid sm:grid-cols-2 gap-4" role="radiogroup">
            <template x-for="opt in [{v:'full', icon:'book-open', title:@js(__('quran.playlists.scope_full')), help:@js(__('quran.playlists.scope_full_help'))},{v:'custom', icon:'list-checks', title:@js(__('quran.playlists.scope_custom')), help:@js(__('quran.playlists.scope_custom_help'))}]" :key="opt.v">
                <button type="button" role="radio" :aria-checked="scope === opt.v" @click="setScope(opt.v)"
                        class="text-start rounded-2xl border p-5 sm:p-6 bg-white dark:bg-zinc-900 transition-[border-color,box-shadow,transform] duration-200 active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60"
                        :class="scope === opt.v ? 'border-primary shadow-[0_0_0_3px_rgba(0,86,179,0.12)]' : 'border-zinc-200 dark:border-zinc-800 hover:border-zinc-400'">
                    <span class="flex items-start gap-4">
                        <span class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 transition-colors"
                              :class="scope === opt.v ? 'bg-primary text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500'">
                            <i :data-lucide="opt.icon" class="w-6 h-6"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-lg font-bold text-zinc-900 dark:text-white" x-text="opt.title"></span>
                            <span class="block mt-1 text-sm text-zinc-500 dark:text-zinc-400" x-text="opt.help"></span>
                        </span>
                    </span>
                </button>
            </template>
        </div>

        <div x-show="scope === 'custom'" x-collapse x-cloak class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="relative flex-1">
                    <label for="pl-surah-search" class="sr-only">{{ __('quran.playlists.search_surahs') }}</label>
                    <i data-lucide="search" class="w-4 h-4 absolute top-1/2 -translate-y-1/2 start-3.5 text-zinc-400 pointer-events-none"></i>
                    <input id="pl-surah-search" type="search" x-model="surahSearch" placeholder="{{ __('quran.playlists.search_surahs') }}" autocomplete="off"
                           class="w-full h-11 ps-10 pe-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white placeholder:text-zinc-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="q in [{k:'amma', l:@js(__('quran.playlists.quick_juz_amma'))},{k:'mufassal', l:@js(__('quran.playlists.quick_mufassal'))},{k:'all', l:@js(__('quran.playlists.quick_all'))},{k:'clear', l:@js(__('quran.playlists.quick_clear'))}]" :key="q.k">
                        <button type="button" @click="quick(q.k)"
                                class="h-11 px-4 rounded-full text-sm font-semibold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 hover:border-zinc-400 active:scale-[0.98] transition"
                                x-text="q.l"></button>
                    </template>
                </div>
            </div>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                <template x-for="s in surahs.filter(s => !surahSearch.trim() || s.name.includes(surahSearch.trim()) || String(s.id) === surahSearch.trim())" :key="s.id">
                    <button type="button" :aria-pressed="surahIds.has(s.id)" @click="toggleSurah(s.id)"
                            class="min-h-[44px] rounded-xl border px-2 py-2 text-sm font-semibold flex items-center gap-2 transition-colors active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/60"
                            :class="surahIds.has(s.id) ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 border-zinc-200 dark:border-zinc-800 hover:border-zinc-400'">
                        <span class="tabular-nums text-xs opacity-70" x-text="fmt(s.id)"></span>
                        <span class="truncate" x-text="s.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </section>

    {{-- ============================ STEP 3: PREVIEW ============================ --}}
    <section x-show="step === 3" x-cloak class="space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100" x-text="coverageText()"></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400" x-show="uncovered.length" x-text="`${fmt(uncovered.length)} ×`"></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="goTo(1)"
                        class="inline-flex items-center justify-center gap-2 h-11 px-4 rounded-full text-sm font-semibold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 hover:border-zinc-400 active:scale-[0.98] transition">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    {{ __('quran.playlists.edit_reciters') }}
                </button>
                <button type="button" @click="goTo(2)"
                        class="inline-flex items-center justify-center gap-2 h-11 px-4 rounded-full text-sm font-semibold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 hover:border-zinc-400 active:scale-[0.98] transition">
                    <i data-lucide="list-checks" class="w-4 h-4"></i>
                    {{ __('quran.playlists.edit_scope') }}
                </button>
                <button type="button" @click="preview(true)" :disabled="loading"
                        class="inline-flex items-center justify-center gap-2 h-11 px-4 rounded-full text-sm font-semibold border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-zinc-800 dark:text-zinc-100 hover:border-zinc-400 active:scale-[0.98] disabled:opacity-50 transition">
                    <i data-lucide="shuffle" class="w-4 h-4" :class="loading ? 'animate-spin' : ''"></i>
                    {{ __('quran.playlists.reshuffle') }}
                </button>
            </div>
        </div>

        {{-- Skeleton --}}
        <div x-show="loading" class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 divide-y divide-zinc-100 dark:divide-zinc-800" aria-hidden="true">
            <template x-for="i in 8" :key="i">
                <div class="grid grid-cols-[auto_1fr_auto] gap-4 items-center px-4 py-3.5">
                    <span class="w-9 h-9 rounded-xl pl-skeleton"></span>
                    <span class="h-4 w-40 rounded pl-skeleton"></span>
                    <span class="h-10 w-48 rounded-lg pl-skeleton"></span>
                </div>
            </template>
        </div>

        <ul x-show="!loading" class="rounded-2xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 divide-y divide-zinc-100 dark:divide-zinc-800" role="list">
            <template x-for="(s, i) in rows" :key="s.id">
                <li class="pl-row grid grid-cols-[auto_1fr_auto] sm:grid-cols-[auto_1fr_minmax(220px,300px)_auto] gap-x-3 gap-y-2 items-center px-3 sm:px-4 py-3"
                    :style="`--i:${i}`"
                    :class="!items[s.id] ? 'bg-amber-50 dark:bg-amber-950/30' : ''">
                    <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold tabular-nums shrink-0"
                          :class="items[s.id] ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200'"
                          x-text="fmt(s.id)"></span>
                    <div class="min-w-0">
                        <p class="font-bold text-zinc-900 dark:text-white truncate" x-text="s.name"></p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 tabular-nums" x-text="versesText(s.verses)"></p>
                    </div>

                    {{-- Reciter (and recording) in one native select: one optgroup per person --}}
                    <div class="col-span-3 sm:col-span-1 flex items-center gap-2">
                        <span class="w-9 h-9 rounded-full overflow-hidden bg-zinc-100 dark:bg-zinc-800 shrink-0 hidden sm:block">
                            <template x-if="profileFor(items[s.id])?.image"><img :src="profileFor(items[s.id]).image" alt="" class="w-full h-full object-cover" loading="lazy" width="36" height="36"></template>
                            <template x-if="items[s.id] && !profileFor(items[s.id])?.image"><span class="w-full h-full flex items-center justify-center text-[8px] font-bold text-white bg-gradient-to-br from-[#0056b3] to-[#218838]" :title="profileFor(items[s.id])?.name"><span class="line-clamp-1 px-0.5" x-text="(profileFor(items[s.id])?.name || '').split(' ')[0]"></span></span></template>
                        </span>
                        <label :for="`pl-rec-${s.id}`" class="sr-only">{{ __('quran.playlists.reciter_select') }}</label>
                        <select :id="`pl-rec-${s.id}`"
                                :value="items[s.id] ?? ''"
                                @change="setRecording(s.id, $event.target.value ? +$event.target.value : null)"
                                :disabled="!optionsFor(s.id).length"
                                class="w-full h-11 rounded-xl border bg-white dark:bg-zinc-900 text-sm text-zinc-900 dark:text-white px-3 focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:opacity-60 transition"
                                :class="errors[`items.${s.id}`] ? 'border-rose-400' : 'border-zinc-200 dark:border-zinc-700'">
                            <option value="" x-show="!items[s.id]">{{ __('quran.playlists.needs_reciter') }}</option>
                            <template x-for="opt in optionsFor(s.id)" :key="opt.profile.id">
                                <optgroup :label="opt.profile.name">
                                    <template x-for="rec in opt.recordings" :key="rec.id">
                                        <option :value="rec.id" :selected="items[s.id] === rec.id" x-text="opt.recordings.length > 1 ? `${opt.profile.name} · ${rec.label}` : opt.profile.name"></option>
                                    </template>
                                </optgroup>
                            </template>
                        </select>
                    </div>

                    <div class="flex items-center gap-1 justify-self-end">
                        <button type="button" @click="previewPlay(s.id)" :disabled="!items[s.id]"
                                class="w-11 h-11 rounded-full flex items-center justify-center text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 disabled:opacity-30 transition active:scale-[0.95]"
                                :aria-label="`{{ __('quran.playlists.preview_play') }} ${s.name}`">
                            <i data-lucide="play" class="w-4 h-4 {{ $isRtl ? '-scale-x-100' : '' }}" x-show="!isPlaying(s.id)"></i>
                            <i data-lucide="pause" class="w-4 h-4" x-show="isPlaying(s.id)" x-cloak></i>
                        </button>
                        <button type="button" @click="removeSurah(s.id)"
                                class="w-11 h-11 rounded-full flex items-center justify-center text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition active:scale-[0.95]"
                                aria-label="{{ __('quran.playlists.remove_surah') }}">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <p class="col-span-3 sm:col-span-4 text-xs text-amber-800 dark:text-amber-200 flex items-start gap-1.5" x-show="!items[s.id]" role="alert">
                        <i data-lucide="alert-triangle" class="w-3.5 h-3.5 mt-0.5 shrink-0"></i>{{ __('quran.playlists.uncovered') }}
                    </p>
                    <p class="col-span-3 sm:col-span-4 text-xs text-rose-600" x-show="errors[`items.${s.id}`]" x-text="errors[`items.${s.id}`]?.[0]" role="alert"></p>
                </li>
            </template>
        </ul>

        <p class="text-xs text-rose-600" x-show="errors.items" x-text="errors.items?.[0]" role="alert"></p>
    </section>

    {{-- ============================ STICKY ACTION BAR ============================ --}}
    <div class="fixed inset-x-0 z-30 border-t border-zinc-200/80 dark:border-zinc-800 bg-white/90 dark:bg-zinc-900/90 backdrop-blur supports-[backdrop-filter]:bg-white/75"
         :style="$store.audioPlayer.track ? 'bottom: 4.5rem' : 'bottom: 0'">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-[4.5rem] flex items-center justify-between gap-3">
            <div class="min-w-0 text-sm text-zinc-600 dark:text-zinc-300 tabular-nums truncate">
                <span x-show="step === 1" x-text="selectedText()"></span>
                <span x-show="step === 2" x-text="surahsText()"></span>
                <label x-show="step === 3" class="inline-flex items-center gap-3 cursor-pointer select-none">
                    <button type="button" role="switch" :aria-checked="isPublic" @click="isPublic = !isPublic; dirty = true"
                            class="relative w-12 h-7 rounded-full transition-colors shrink-0"
                            :class="isPublic ? 'bg-secondary' : 'bg-zinc-300 dark:bg-zinc-700'">
                        <span class="absolute top-1 start-1 w-5 h-5 rounded-full bg-white shadow ring-1 ring-black/10 transition-transform"
                              :class="isPublic ? 'ltr:translate-x-5 rtl:-translate-x-5' : ''"></span>
                    </button>
                    <span class="flex flex-col leading-tight">
                        <span class="font-semibold text-zinc-800 dark:text-zinc-100">{{ __('quran.playlists.public_toggle') }}</span>
                        <span class="hidden sm:block text-xs text-zinc-500 dark:text-zinc-400">{{ __('quran.playlists.public_toggle_help') }}</span>
                    </span>
                </label>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="back()" x-show="step > 1"
                        class="h-11 px-4 rounded-xl text-sm font-semibold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition active:scale-[0.98]">
                    {{ __('quran.playlists.back') }}
                </button>
                <button type="button" @click="next()" x-show="step < 3" :disabled="!canNext"
                        class="inline-flex items-center gap-2 h-11 px-6 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-sm font-bold shadow-sm transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed">
                    {{ __('quran.playlists.next') }}
                    <i data-lucide="{{ $isRtl ? 'arrow-left' : 'arrow-right' }}" class="w-4 h-4"></i>
                </button>
                <button type="button" @click="save()" x-show="step === 3" :disabled="saving || loading || !canSave"
                        class="inline-flex items-center gap-2 h-11 px-6 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-sm font-bold shadow-sm transition active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed">
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="saving" x-cloak></i>
                    <i data-lucide="check" class="w-4 h-4" x-show="!saving"></i>
                    <span x-text="saving ? @js(__('quran.playlists.saving')) : @js(__('quran.playlists.save'))"></span>
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function playlistWizard(props) {
    const range = (a, b) => Array.from({ length: b - a + 1 }, (_, i) => a + i);
    const headers = () => ({
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    });
    const t = {
        favorites: @js(__('quran.playlists.favorites_section')),
        all: @js(__('quran.playlists.all_reciters')),
        selected: @js(__('quran.playlists.selected_count', ['count' => ':count'])),
        surahs: @js(__('quran.playlists.surahs_selected', ['count' => ':count'])),
        coverage: @js(__('quran.playlists.coverage', ['covered' => ':covered', 'total' => ':total'])),
        verses: @js(__('quran.playlists.verses', ['count' => ':count'])),
    };

    return {
        step: 1,
        maxStep: props.initial ? 3 : 1,
        profiles: props.profiles,
        surahs: props.surahs,
        favoriteIds: props.favoriteIds,
        editing: !!props.initial,
        name: props.initial?.name ?? props.defaultName,
        description: props.initial?.description ?? '',
        isPublic: props.initial?.isPublic ?? false,
        selected: new Set(props.initial?.profileIds ?? props.favoriteIds),
        scope: props.initial?.scope ?? 'full',
        surahIds: new Set(props.initial?.surahIds ?? []),
        items: props.initial?.items ?? {},   // surah -> recording | null
        pinned: {},                          // surah -> recording chosen by hand (survives reshuffle)
        alternatives: {},
        uncovered: [],
        loading: false,
        saving: false,
        errors: {},
        dirty: false,
        reciterSearch: '',
        surahSearch: '',

        init() {
            window.addEventListener('beforeunload', (e) => {
                if (this.dirty && !this.saving) { e.preventDefault(); e.returnValue = ''; }
            });
            this.$watch('step', () => {
                // A new step starts at the top, whatever the previous one scrolled to.
                window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                this.$nextTick(() => window.lucide?.createIcons());
            });
            this.$watch('loading', () => this.$nextTick(() => window.lucide?.createIcons()));
            if (this.editing) { this.step = 3; this.preview(false); }
        },

        // ---------- helpers
        fmt(n) { return props.locale === 'ar' ? String(n).replace(/[0-9]/g, d => '٠١٢٣٤٥٦٧٨٩'[d]) : String(n); },
        profileById(id) { return this.profiles.find(p => p.id === id); },
        profileFor(recording) { return recording ? this.profiles.find(p => p.recordings.some(r => r.id === recording)) : null; },
        matches(p) { const q = this.reciterSearch.trim(); return !q || p.name.includes(q); },
        get groups() {
            return [
                { key: 'fav', icon: 'heart', label: t.favorites, items: this.profiles.filter(p => this.favoriteIds.includes(p.id) && this.matches(p)) },
                { key: 'all', icon: 'mic', label: t.all, items: this.profiles.filter(p => !this.favoriteIds.includes(p.id) && this.matches(p)) },
            ];
        },
        get scopeIds() { return this.scope === 'full' ? range(1, 114) : [...this.surahIds].sort((a, b) => a - b); },
        get rows() { return this.surahs.filter(s => s.id in this.items); },
        get covered() { return Object.values(this.items).filter(Boolean).length; },
        get canNext() {
            if (this.step === 1) return this.selected.size > 0 && this.name.trim().length >= 2;
            if (this.step === 2) return this.scopeIds.length > 0;
            return true;
        },
        get canSave() { return this.rows.length > 0 && this.name.trim().length >= 2; },
        selectedText() { return t.selected.replace(':count', this.fmt(this.selected.size)); },
        surahsText() { return t.surahs.replace(':count', this.fmt(this.scopeIds.length)); },
        coverageText() { return t.coverage.replace(':covered', this.fmt(this.covered)).replace(':total', this.fmt(this.rows.length)); },
        versesText(n) { return t.verses.replace(':count', this.fmt(n)); },

        // ---------- step 1
        toggleProfile(id) {
            this.selected.has(id) ? this.selected.delete(id) : this.selected.add(id);
            this.selected = new Set(this.selected);
            this.dirty = true;
        },

        // ---------- step 2
        setScope(v) { this.scope = v; this.dirty = true; },
        toggleSurah(id) {
            this.surahIds.has(id) ? this.surahIds.delete(id) : this.surahIds.add(id);
            this.surahIds = new Set(this.surahIds);
            this.dirty = true;
        },
        quick(kind) {
            const ids = kind === 'amma' ? range(78, 114) : kind === 'mufassal' ? range(50, 114) : kind === 'all' ? range(1, 114) : [];
            this.surahIds = new Set(ids);
            this.dirty = true;
        },

        // ---------- navigation
        goTo(n) { if (n <= this.maxStep) { this.step = n; } },
        async next() {
            if (!this.canNext || this.step >= 3) return;
            this.step++;
            this.maxStep = Math.max(this.maxStep, this.step);
            if (this.step === 3) await this.preview(false);
        },
        back() { if (this.step > 1) this.step--; },

        // ---------- step 3
        async preview(reshuffle) {
            this.loading = true; this.errors = {};
            // Reshuffle keeps only hand-picked rows; a normal preview keeps everything already assigned.
            const keep = reshuffle ? this.pinned : { ...this.items, ...this.pinned };
            try {
                const res = await fetch(props.previewUrl, {
                    method: 'POST', headers: headers(),
                    body: JSON.stringify({ profile_ids: [...this.selected], scope: this.scope, surah_ids: this.scopeIds, keep }),
                });
                if (!res.ok) throw new Error('preview_failed');
                const data = await res.json();
                const next = {};
                this.scopeIds.forEach(s => { next[s] = data.items[s] ?? null; });
                this.items = next;
                this.alternatives = data.alternatives || {};
                this.uncovered = data.uncovered || [];
            } catch (e) {
                this.$store.toast?.push(props.t.error, 'error');
            } finally {
                this.loading = false;
            }
        },
        optionsFor(surah) {
            const alt = this.alternatives[surah] || {};
            return Object.entries(alt)
                .map(([pid, recs]) => {
                    const profile = this.profileById(+pid);
                    if (!profile) return null;
                    return { profile, recordings: recs.map(id => profile.recordings.find(r => r.id === id) || { id, label: '' }) };
                })
                .filter(Boolean);
        },
        setRecording(surah, recording) {
            this.items[surah] = recording;
            if (recording) this.pinned[surah] = recording; else delete this.pinned[surah];
            this.dirty = true;
        },
        removeSurah(surah) {
            delete this.items[surah];
            delete this.pinned[surah];
            this.items = { ...this.items };
            // Dropping a surah from "the whole Quran" turns the scope custom.
            if (this.scope === 'full') this.scope = 'custom';
            this.surahIds = new Set(Object.keys(this.items).map(Number));
            this.dirty = true;
        },
        audioUrl(surah) {
            const rec = this.items[surah];
            return rec ? props.audioUrlTemplate.replace('__S__', surah).replace('__R__', rec) : null;
        },
        isPlaying(surah) {
            const url = this.audioUrl(surah);
            return !!url && this.$store.audioPlayer.track?.url === url && this.$store.audioPlayer.playing;
        },
        previewPlay(surah) {
            const url = this.audioUrl(surah);
            if (!url) return;
            const s = this.surahs.find(x => x.id === surah);
            this.$store.audioPlayer.play({ url, title: s?.name ?? '', subtitle: this.profileFor(this.items[surah])?.name ?? '' });
        },

        // ---------- save
        async save() {
            if (this.saving || !this.canSave) return;
            this.saving = true; this.errors = {};
            const body = { name: this.name.trim(), description: this.description || null, scope: this.scope, items: this.items, is_public: this.isPublic };
            try {
                const res = await fetch(this.editing ? props.updateUrl : props.storeUrl, {
                    method: this.editing ? 'PUT' : 'POST', headers: headers(), body: JSON.stringify(body),
                });
                const data = await res.json().catch(() => ({}));
                if (res.status === 422) {
                    this.errors = data.errors || {};
                    if (this.errors.name || this.errors.description) this.step = 1;
                    this.$store.toast?.push(Object.values(this.errors)[0]?.[0] || data.message || props.t.error, 'error');
                    return;
                }
                if (!res.ok) throw new Error('save_failed');
                this.dirty = false;
                if (data.message) this.$store.toast?.push(data.message, 'success');
                window.location.href = data.redirect;
            } catch (e) {
                this.$store.toast?.push(props.t.error, 'error');
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endpush
