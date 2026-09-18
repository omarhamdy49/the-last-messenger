@extends('layouts.public')

@section('title', ($playlist ? __('quran.playlists.edit_title') : __('quran.playlists.create_title')).' | '.__('quran.playlists.title'))
@section('meta_description', __('quran.playlists.subtitle'))

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

<div class="sx min-h-[100dvh]"
     x-data="playlistWizard(@js($props))"
     x-init="init()"
     :style="$store.audioPlayer.track ? 'padding-bottom: 9rem' : 'padding-bottom: 5rem'">
<div class="sx-wrap">

    {{-- Breadcrumb --}}
    <nav class="flex flex-wrap items-center gap-2 pt-6 text-sm text-[color:var(--sx-muted)]" aria-label="breadcrumb">
        <a href="{{ route('public.quran') }}" class="transition-colors hover:text-[color:var(--sx-accent)]">{{ __('quran.public.page_title') }}</a>
        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="h-4 w-4" aria-hidden="true"></i>
        <a href="{{ route('public.quran.playlists.index') }}" class="transition-colors hover:text-[color:var(--sx-accent)]">{{ __('quran.playlists.title') }}</a>
    </nav>

    <x-public.editorial.page-hero
        :title="$playlist ? __('quran.playlists.edit_title') : __('quran.playlists.create_title')"
        :lead="__('quran.playlists.cap_hint', ['count' => $count, 'max' => $cap])" />

    {{-- Step indicator: a step you have not reached yet is disabled, not hidden. --}}
    <ol class="sx-tabs mb-8" aria-label="steps">
        @foreach (['step_reciters', 'step_scope', 'step_preview'] as $i => $key)
            <li>
                <button type="button"
                        @click="goTo({{ $i + 1 }})"
                        :disabled="{{ $i + 1 }} > maxStep"
                        :aria-current="step === {{ $i + 1 }} ? 'step' : null"
                        class="sx-tab disabled:cursor-not-allowed disabled:opacity-45"
                        :class="step === {{ $i + 1 }} ? 'text-[color:var(--sx-accent)] border-[color:var(--sx-accent)]' : ''">
                    <span class="tabular-nums">{{ $i + 1 }}.</span> {{ __('quran.playlists.'.$key) }}
                </button>
            </li>
        @endforeach
    </ol>

    {{-- ============================ STEP 1: RECITERS ============================ --}}
    <section x-show="step === 1" x-cloak class="space-y-8">
        <div class="sx-surface grid gap-4 p-4 sm:p-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="pl-name" class="sx-label">{{ __('quran.playlists.name') }} <span class="sx-req" aria-hidden="true">*</span></label>
                    <input id="pl-name" type="text" x-model="name" maxlength="80" required autocomplete="off"
                           @input="dirty = true"
                           :aria-invalid="!!errors.name"
                           class="sx-input"
                           :class="errors.name ? '!border-[color:var(--sx-danger)]' : ''">
                    <p class="mt-1.5 text-xs text-[color:var(--sx-muted)]" x-show="!errors.name">{{ __('quran.playlists.name_help') }}</p>
                    <p class="sx-error" x-show="errors.name" x-text="errors.name?.[0]" role="alert"></p>
                </div>
                <div>
                    <label for="pl-desc" class="sx-label">{{ __('quran.playlists.description') }}</label>
                    <input id="pl-desc" type="text" x-model="description" maxlength="300" autocomplete="off"
                           @input="dirty = true"
                           class="sx-input"
                           :class="errors.description ? '!border-[color:var(--sx-danger)]' : ''">
                    <p class="sx-error" x-show="errors.description" x-text="errors.description?.[0]" role="alert"></p>
                </div>
            </div>

            <div class="relative">
                <label for="pl-reciter-search" class="sx-label">{{ __('quran.playlists.search_reciters') }}</label>
                <i data-lucide="search" class="pointer-events-none absolute start-4 top-[2.4rem] h-5 w-5 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                <input id="pl-reciter-search" type="search" x-model="reciterSearch" placeholder="{{ __('quran.playlists.search_reciters') }}"
                       autocomplete="off" enterkeyhint="search" class="sx-input ps-12">
            </div>
        </div>

        <template x-for="group in groups" :key="group.key">
            <div x-show="group.items.length">
                <h2 class="mb-3 flex items-center gap-2 text-sm font-bold text-[color:var(--sx-ink-2)]">
                    <i :data-lucide="group.icon" class="h-4 w-4 text-[color:var(--sx-accent)]" aria-hidden="true"></i>
                    <span x-text="group.label"></span>
                </h2>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    <template x-for="p in group.items" :key="p.id">
                        <button type="button" role="checkbox"
                                :aria-checked="selected.has(p.id)"
                                @click="toggleProfile(p.id)"
                                :title="p.name"
                                class="sx-panel relative p-3 text-start transition-[border-color,box-shadow,transform] duration-200 active:scale-[0.98] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--sx-teal)]"
                                :class="selected.has(p.id) ? 'border-[color:var(--sx-accent)] shadow-[0_0_0_3px_var(--sx-accent-soft)]' : 'hover:border-[color:var(--sx-line-strong)]'">
                            <span class="absolute top-2.5 end-2.5 flex h-6 w-6 items-center justify-center rounded-full transition-colors"
                                  :class="selected.has(p.id) ? 'text-white' : 'bg-[color:var(--sx-surface)] text-transparent'"
                                  :style="selected.has(p.id) ? 'background: var(--sx-grad)' : ''">
                                <i data-lucide="check" class="h-3.5 w-3.5" aria-hidden="true"></i>
                            </span>
                            <span class="sx-avatar mx-auto mb-2 h-16 w-16">
                                <template x-if="p.image"><img :src="p.image" :alt="p.name" loading="lazy" width="64" height="64" onerror="this.remove()"></template>
                                <template x-if="!p.image"><span class="sx-clamp-3 px-1.5 text-center text-[11px] font-bold leading-tight" x-text="p.name"></span></template>
                            </span>
                            <span class="sx-clamp-2 block text-center text-sm font-bold leading-snug text-[color:var(--sx-ink)]" x-text="p.name"></span>
                            <span class="mt-1 flex items-center justify-center gap-1 text-[11px] text-[color:var(--sx-muted)]">
                                <span x-text="p.flag"></span>
                                <span class="truncate" x-text="p.styles[0] || ''"></span>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <x-public.editorial.empty-state icon="mic-off" x-show="!groups.some(g => g.items.length)" x-cloak
            :title="__('quran.playlists.empty_search')" :hint="__('site.try_adjusting_filters')" />
    </section>

    {{-- ============================ STEP 2: SCOPE ============================ --}}
    <section x-show="step === 2" x-cloak class="space-y-8">
        <div class="grid gap-4 sm:grid-cols-2" role="radiogroup" aria-label="{{ __('quran.playlists.step_scope') }}">
            <template x-for="opt in [{v:'full', icon:'book-open', title:@js(__('quran.playlists.scope_full')), help:@js(__('quran.playlists.scope_full_help'))},{v:'custom', icon:'list-checks', title:@js(__('quran.playlists.scope_custom')), help:@js(__('quran.playlists.scope_custom_help'))}]" :key="opt.v">
                <button type="button" role="radio" :aria-checked="scope === opt.v" @click="setScope(opt.v)"
                        class="sx-panel p-5 text-start transition-[border-color,box-shadow,transform] duration-200 active:scale-[0.99] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[color:var(--sx-teal)] sm:p-6"
                        :class="scope === opt.v ? 'border-[color:var(--sx-accent)] shadow-[0_0_0_3px_var(--sx-accent-soft)]' : 'hover:border-[color:var(--sx-line-strong)]'">
                    <span class="flex items-start gap-4">
                        <span class="sx-tile-icon h-12 w-12 shrink-0 transition-colors"
                              :class="scope === opt.v ? '!text-white' : ''"
                              :style="scope === opt.v ? 'background: var(--sx-grad)' : ''">
                            <i :data-lucide="opt.icon" class="h-6 w-6" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-lg font-bold text-[color:var(--sx-ink)]" x-text="opt.title"></span>
                            <span class="mt-1 block text-sm leading-relaxed text-[color:var(--sx-muted)]" x-text="opt.help"></span>
                        </span>
                    </span>
                </button>
            </template>
        </div>

        <div x-show="scope === 'custom'" x-collapse x-cloak class="space-y-4">
            <div class="sx-surface grid gap-4 p-4 sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="relative min-w-0 flex-1">
                        <label for="pl-surah-search" class="sx-label">{{ __('quran.playlists.search_surahs') }}</label>
                        <i data-lucide="search" class="pointer-events-none absolute start-4 top-[2.4rem] h-5 w-5 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                        <input id="pl-surah-search" type="search" x-model="surahSearch" placeholder="{{ __('quran.playlists.search_surahs') }}"
                               autocomplete="off" enterkeyhint="search" class="sx-input ps-12">
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="q in [{k:'amma', l:@js(__('quran.playlists.quick_juz_amma'))},{k:'mufassal', l:@js(__('quran.playlists.quick_mufassal'))},{k:'all', l:@js(__('quran.playlists.quick_all'))},{k:'clear', l:@js(__('quran.playlists.quick_clear'))}]" :key="q.k">
                            <button type="button" @click="quick(q.k)" class="sx-btn" x-text="q.l"></button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
                <template x-for="s in surahs.filter(s => !surahSearch.trim() || s.name.includes(surahSearch.trim()) || String(s.id) === surahSearch.trim())" :key="s.id">
                    <button type="button" :aria-pressed="surahIds.has(s.id)" @click="toggleSurah(s.id)" :title="s.name"
                            class="sx-chip min-h-[44px] w-full active:scale-[0.98]"
                            :class="surahIds.has(s.id) ? 'is-active' : ''">
                        <span class="text-xs tabular-nums opacity-70" x-text="fmt(s.id)"></span>
                        <span class="truncate" x-text="s.name"></span>
                    </button>
                </template>
            </div>
        </div>
    </section>

    {{-- ============================ STEP 3: PREVIEW ============================ --}}
    <section x-show="step === 3" x-cloak class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold text-[color:var(--sx-ink)] tabular-nums" x-text="coverageText()"></p>
                <p class="text-xs text-[color:var(--sx-warn)] tabular-nums" x-show="uncovered.length" x-text="`${fmt(uncovered.length)} ×`"></p>
            </div>
            <button type="button" @click="preview(true)" :disabled="loading" class="sx-btn disabled:opacity-50">
                <i data-lucide="shuffle" :class="loading ? 'animate-spin' : ''" aria-hidden="true"></i>
                {{ __('quran.playlists.reshuffle') }}
            </button>
        </div>

        {{-- Skeleton: shaped like the rows it stands in for. --}}
        <div x-show="loading" class="sx-surface overflow-hidden" aria-hidden="true">
            <template x-for="i in 8" :key="i">
                <div class="grid grid-cols-[auto_1fr_auto] items-center gap-4 border-t border-[color:var(--sx-line)] px-4 py-3.5 first:border-t-0">
                    <span class="sx-skeleton h-9 w-9 !rounded-xl"></span>
                    <span class="sx-skeleton h-4 w-40"></span>
                    <span class="sx-skeleton h-10 w-48"></span>
                </div>
            </template>
        </div>

        <ul x-show="!loading" class="sx-surface overflow-hidden" role="list">
            <template x-for="(s, i) in rows" :key="s.id">
                <li class="grid grid-cols-[auto_1fr_auto] items-center gap-x-3 gap-y-2 border-t border-[color:var(--sx-line)] px-3 py-3 first:border-t-0 sm:grid-cols-[auto_1fr_minmax(220px,300px)_auto] sm:px-4"
                    :class="!items[s.id] ? 'bg-[color:var(--sx-warn-soft)]' : ''">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold tabular-nums"
                          :class="items[s.id] ? 'bg-[color:var(--sx-surface-2)] text-[color:var(--sx-ink-2)]' : 'bg-[color:var(--sx-warn-soft)] text-[color:var(--sx-warn)]'"
                          x-text="fmt(s.id)"></span>
                    <div class="min-w-0">
                        <p class="sx-clamp-1 font-bold text-[color:var(--sx-ink)]" :title="s.name" x-text="s.name"></p>
                        <p class="text-xs tabular-nums text-[color:var(--sx-muted)]" x-text="versesText(s.verses)"></p>
                    </div>

                    {{-- Reciter (and recording) in one native select: one optgroup per person --}}
                    <div class="col-span-3 flex items-center gap-2 sm:col-span-1">
                        <span class="sx-avatar is-square hidden h-9 w-9 sm:grid">
                            <template x-if="profileFor(items[s.id])?.image"><img :src="profileFor(items[s.id]).image" alt="" loading="lazy" width="36" height="36" onerror="this.remove()"></template>
                            <template x-if="items[s.id] && !profileFor(items[s.id])?.image"><span class="sx-clamp-1 px-0.5 text-[8px] font-bold" :title="profileFor(items[s.id])?.name" x-text="(profileFor(items[s.id])?.name || '').split(' ')[0]"></span></template>
                        </span>
                        <label :for="`pl-rec-${s.id}`" class="sr-only">{{ __('quran.playlists.reciter_select') }}</label>
                        <select :id="`pl-rec-${s.id}`"
                                :value="items[s.id] ?? ''"
                                @change="setRecording(s.id, $event.target.value ? +$event.target.value : null)"
                                :disabled="!optionsFor(s.id).length"
                                class="sx-input !min-h-[2.75rem] text-base disabled:opacity-60 sm:text-sm"
                                :class="errors[`items.${s.id}`] ? '!border-[color:var(--sx-danger)]' : ''">
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
                                class="sx-btn w-11 px-0 disabled:opacity-30"
                                :aria-label="`{{ __('quran.playlists.preview_play') }} ${s.name}`"
                                :title="`{{ __('quran.playlists.preview_play') }} ${s.name}`">
                            <i data-lucide="play" class="{{ $isRtl ? '-scale-x-100' : '' }}" x-show="!isPlaying(s.id)" aria-hidden="true"></i>
                            <i data-lucide="pause" x-show="isPlaying(s.id)" x-cloak aria-hidden="true"></i>
                        </button>
                        <button type="button" @click="removeSurah(s.id)"
                                class="sx-btn w-11 px-0 hover:!border-[color:var(--sx-danger)] hover:!text-[color:var(--sx-danger)]"
                                aria-label="{{ __('quran.playlists.remove_surah') }}" title="{{ __('quran.playlists.remove_surah') }}">
                            <i data-lucide="x" aria-hidden="true"></i>
                        </button>
                    </div>

                    <p class="col-span-3 flex items-start gap-1.5 text-xs font-semibold text-[color:var(--sx-danger)] sm:col-span-4" x-show="!items[s.id]" role="alert">
                        <i data-lucide="alert-triangle" class="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true"></i>{{ __('quran.playlists.uncovered') }}
                    </p>
                    <p class="col-span-3 text-xs font-semibold text-[color:var(--sx-danger)] sm:col-span-4" x-show="errors[`items.${s.id}`]" x-text="errors[`items.${s.id}`]?.[0]" role="alert"></p>
                </li>
            </template>
        </ul>

        <p class="text-xs font-semibold text-[color:var(--sx-danger)]" x-show="errors.items" x-text="errors.items?.[0]" role="alert"></p>
    </section>

    {{-- ============================ STICKY ACTION BAR ============================ --}}
    <div class="fixed inset-x-0 z-30 border-t border-[color:var(--sx-line)] bg-[color:var(--sx-surface)]"
         :style="$store.audioPlayer.track ? 'bottom: 4.5rem' : 'bottom: 0'">
        <div class="sx-wrap flex h-16 items-center justify-between gap-3 sm:h-[4.5rem]">
            <div class="min-w-0 truncate text-sm tabular-nums text-[color:var(--sx-ink-2)]">
                <span x-show="step === 1" x-text="selectedText()"></span>
                <span x-show="step === 2" x-text="surahsText()"></span>
                <label x-show="step === 3" class="inline-flex cursor-pointer select-none items-center gap-3">
                    <button type="button" role="switch" :aria-checked="isPublic" @click="isPublic = !isPublic; dirty = true"
                            class="relative h-7 w-12 shrink-0 rounded-full transition-colors"
                            :class="isPublic ? '' : 'bg-[color:var(--sx-line-strong)]'"
                            :style="isPublic ? 'background: var(--sx-grad)' : ''">
                        <span class="absolute top-1 h-5 w-5 rounded-full bg-[color:var(--sx-surface)] shadow transition-transform"
                              :class="isPublic ? '{{ $isRtl ? '-translate-x-6 end-1' : 'translate-x-6 start-1' }}' : '{{ $isRtl ? 'end-1' : 'start-1' }}'"></span>
                    </button>
                    <span class="flex flex-col leading-tight">
                        <span class="font-semibold text-[color:var(--sx-ink)]">{{ __('quran.playlists.public_toggle') }}</span>
                        <span class="hidden text-xs text-[color:var(--sx-muted)] sm:block">{{ __('quran.playlists.public_toggle_help') }}</span>
                    </span>
                </label>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" @click="back()" x-show="step > 1" class="sx-btn">
                    {{ __('quran.playlists.back') }}
                </button>
                <button type="button" @click="next()" x-show="step < 3" :disabled="!canNext"
                        class="sx-btn is-primary disabled:cursor-not-allowed disabled:opacity-40">
                    {{ __('quran.playlists.next') }}
                    <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" aria-hidden="true"></i>
                </button>
                <button type="button" @click="save()" x-show="step === 3" :disabled="saving || loading || !canSave"
                        class="sx-btn is-primary disabled:cursor-not-allowed disabled:opacity-40">
                    <i data-lucide="loader-2" class="animate-spin" x-show="saving" x-cloak aria-hidden="true"></i>
                    <i data-lucide="check" x-show="!saving" aria-hidden="true"></i>
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
