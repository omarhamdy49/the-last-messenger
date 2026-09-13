@extends('layouts.admin')
@use('Illuminate\Support\Js')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur']);
    $profile = $reciter->profile;
    $name = $reciter->getDisplayName($locale);
    $secondaryName = $locale === 'en' ? $reciter->getTranslation('name', 'ar') : $reciter->getTranslation('name', 'en');
    $image = $reciter->profile_image_url ?? $profile?->profile_image_url;
    $country = $reciter->country ?? $profile?->country;
    $styleLabel = fn ($recording) => $recording->style ? __('quran.recordings.'.$recording->style) : __('quran.recordings.unspecified');
    $surahSources = $reciter->sources->where('kind', 'surah')->values();
    $coverage = $stats['surahs'] ? (int) round($stats['with_audio'] / $stats['surahs'] * 100) : 0;
    $duration = $stats['duration_seconds'];
    $durationLabel = $duration ? intdiv($duration, 3600).':'.str_pad((string) intdiv($duration % 3600, 60), 2, '0', STR_PAD_LEFT) : '—';
    $languages = ['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'];
    $bios = collect($languages)->keys()->mapWithKeys(fn ($lang) => [$lang => $reciter->getTranslation('description', $lang, false)])->filter();
    $names = collect($languages)->keys()->mapWithKeys(fn ($lang) => [$lang => $reciter->getTranslation('name', $lang, false)])->filter();
    $chevron = $isRtl ? 'chevron-left' : 'chevron-right';
    $glassBtn = 'min-h-10 px-4 py-2 rounded-xl font-semibold inline-flex items-center gap-2 border border-white/30 bg-white/15 hover:bg-white/25 text-white backdrop-blur transition-all hover:scale-105 motion-reduce:transition-none motion-reduce:hover:scale-100 focus:outline-none focus:ring-2 focus:ring-white';
@endphp

@section('content')
<div class="space-y-6">

    {{-- ============ Hero ============ --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-white shadow-xl"
             x-data="{
                featured: {{ Js::from((bool) $reciter->is_featured) }},
                busy: false,
                async toggleFeatured() {
                    if (this.busy) return;
                    this.busy = true;
                    try {
                        const res = await fetch({{ Js::from(route('admin.reciters.toggle-featured', $reciter->id)) }}, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        });
                        const data = await res.json();
                        if (!res.ok || !data.success) throw new Error(data.message);
                        this.featured = data.data.is_featured;
                        window.showToast && window.showToast(data.message, 'success');
                    } catch (e) {
                        window.showToast && window.showToast(e.message || {{ Js::from(__('quran.recitations.error_generic')) }}, 'error');
                    } finally {
                        this.busy = false;
                    }
                },
             }">
        {{-- Decorative overlay --}}
        <div class="pointer-events-none absolute inset-0 opacity-20" aria-hidden="true"
             style="background-image: radial-gradient(rgba(255,255,255,.55) 1px, transparent 1px); background-size: 18px 18px;"></div>
        <div class="pointer-events-none absolute -top-24 -end-24 w-72 h-72 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-32 -start-16 w-80 h-80 rounded-full bg-[#64CCC5]/20 blur-3xl" aria-hidden="true"></div>

        <div class="relative p-6 sm:p-8">
            {{-- Breadcrumb + back --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                <nav aria-label="breadcrumb" class="flex flex-wrap items-center gap-1.5 text-sm text-white/80">
                    <a href="{{ route('admin.reciter-profiles.index') }}" class="hover:text-white underline-offset-4 hover:underline">{{ __('quran.recordings.profiles') }}</a>
                    @if($profile)
                        <i data-lucide="{{ $chevron }}" class="w-4 h-4" aria-hidden="true"></i>
                        <a href="{{ route('admin.reciter-profiles.edit', $profile->id) }}" class="hover:text-white underline-offset-4 hover:underline">{{ $profile->displayName($locale) }}</a>
                    @endif
                    <i data-lucide="{{ $chevron }}" class="w-4 h-4" aria-hidden="true"></i>
                    <span class="text-white font-medium" aria-current="page">{{ $reciter->recordingLabel($locale) }}</span>
                </nav>
                <a href="{{ $profile ? route('admin.reciter-profiles.edit', $profile->id) : route('admin.reciter-profiles.index') }}" class="{{ $glassBtn }}">
                    <i data-lucide="{{ $isRtl ? 'arrow-right' : 'arrow-left' }}" class="w-4 h-4" aria-hidden="true"></i>
                    <span>{{ __('quran.reciter_admin.back') }}</span>
                </a>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-end gap-6">
                {{-- Avatar --}}
                <div class="w-28 h-28 sm:w-36 sm:h-36 shrink-0 rounded-3xl overflow-hidden ring-4 ring-white/30 shadow-2xl bg-white/10">
                    @if($image)
                        <img src="{{ $image }}" alt="{{ $name }}" class="w-full h-full object-cover" loading="lazy">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-white/20 to-white/5" role="img" aria-label="{{ __('quran.reciter_admin.no_image') }}">
                            <i data-lucide="mic" class="w-14 h-14 text-white/80" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>

                {{-- Identity --}}
                <div class="min-w-0 flex-1">
                    <h1 class="text-3xl sm:text-4xl font-black leading-tight break-words">{{ $name }}</h1>
                    @if($secondaryName && $secondaryName !== $name)
                        <p class="mt-1 text-lg text-white/80" dir="{{ $locale === 'en' ? 'rtl' : 'ltr' }}">{{ $secondaryName }}</p>
                    @endif
                    @if($profile)
                        <a href="{{ route('admin.reciter-profiles.edit', $profile->id) }}"
                           class="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-white/90 hover:text-white underline underline-offset-4 decoration-white/40 hover:decoration-white">
                            <i data-lucide="user-round" class="w-4 h-4" aria-hidden="true"></i>
                            {{ __('quran.reciter_admin.profile_link', ['name' => $profile->displayName($locale)]) }}
                        </a>
                    @endif

                    <ul class="mt-4 flex flex-wrap gap-2 text-sm" role="list">
                        <li class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/20 backdrop-blur font-semibold">
                            <i data-lucide="audio-lines" class="w-4 h-4" aria-hidden="true"></i>{{ $reciter->recordingLabel($locale) }}
                        </li>
                        @if($country)
                            <li class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/15 backdrop-blur">
                                <i data-lucide="map-pin" class="w-4 h-4" aria-hidden="true"></i>{{ $country }}
                            </li>
                        @endif
                        <li class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full backdrop-blur {{ $reciter->is_active ? 'bg-emerald-400/25' : 'bg-black/20' }}">
                            <i data-lucide="{{ $reciter->is_active ? 'circle-check' : 'circle-off' }}" class="w-4 h-4" aria-hidden="true"></i>
                            {{ $reciter->is_active ? __('quran.reciter_admin.active') : __('quran.reciter_admin.inactive') }}
                        </li>
                        <li x-show="featured" x-cloak class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-400 text-amber-950 font-semibold">
                            <i data-lucide="star" class="w-4 h-4" aria-hidden="true"></i>{{ __('quran.reciter_admin.featured') }}
                        </li>
                    </ul>
                </div>

                {{-- Actions --}}
                <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                    <a href="{{ route('admin.reciters.edit', $reciter->id) }}"
                       class="min-h-10 px-5 py-2 rounded-xl font-semibold inline-flex items-center gap-2 bg-white text-[#0056b3] shadow-lg transition-all hover:scale-105 motion-reduce:transition-none motion-reduce:hover:scale-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-[#0056b3]">
                        <i data-lucide="edit-2" class="w-4 h-4" aria-hidden="true"></i>
                        <span>{{ __('quran.reciter_admin.edit') }}</span>
                    </a>
                    <a href="{{ route('admin.reciters.sources.index', $reciter->id) }}" class="{{ $glassBtn }}">
                        <i data-lucide="link" class="w-4 h-4" aria-hidden="true"></i>
                        <span>{{ __('quran.reciter_admin.sources') }}</span>
                    </a>
                    <button type="button" @click="toggleFeatured()" :disabled="busy" :aria-pressed="featured.toString()" class="{{ $glassBtn }} disabled:opacity-60">
                        <i data-lucide="star" class="w-4 h-4" :class="featured ? 'fill-amber-300 text-amber-300' : ''" aria-hidden="true"></i>
                        <span x-text="featured ? {{ Js::from(__('quran.reciter_admin.unfeature')) }} : {{ Js::from(__('quran.reciter_admin.feature')) }}">{{ $reciter->is_featured ? __('quran.reciter_admin.unfeature') : __('quran.reciter_admin.feature') }}</span>
                    </button>
                    <form action="{{ route('admin.reciters.destroy', $reciter->id) }}" method="POST"
                          @submit.prevent="$dispatch('show-confirm', { title: {{ Js::from(__('admin.confirm_delete')) }}, message: {{ Js::from(__('admin.delete_warning')) }}, type: 'danger', onConfirm: () => $el.submit() })">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="{{ str_replace('hover:bg-white/25', 'hover:bg-red-500/80', $glassBtn) }}">
                            <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
                            <span>{{ __('quran.reciter_admin.delete') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Stats ============ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-admin.stats-card icon="audio-lines" :title="__('quran.reciter_admin.stat_surahs')"
                            :value="$stats['with_audio'].' / '.$stats['surahs']" :change="$coverage.'%'" :change-type="$coverage >= 50 ? 'up' : 'down'" />
        <x-admin.stats-card icon="link" :title="__('quran.reciter_admin.stat_sources')" :value="$reciter->sources->count()"
                            :change="__('quran.reciter_admin.stat_sources_active', ['count' => $reciter->sources->where('is_active', true)->count()])" delay="delay-100" />
        <x-admin.stats-card icon="clock" :title="__('quran.reciter_admin.stat_duration')" :value="$durationLabel" delay="delay-200" />
        <x-admin.stats-card icon="layers" :title="__('quran.reciter_admin.stat_recordings')" :value="$recordings->count()" gradient delay="delay-300" />
    </div>

    {{-- ============ Recording types ============ --}}
    @if($recordings->count() > 1)
        <section class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
                 aria-labelledby="recording-types-title">
            <div class="mb-5">
                <h2 id="recording-types-title" class="text-xl font-bold gradient-text flex items-center gap-2">
                    <i data-lucide="layers" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ __('quran.reciter_admin.recording_types') }}
                </h2>
                <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.reciter_admin.recording_types_hint') }}</p>
            </div>
            <ul class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4" role="list">
                @foreach($recordings as $recording)
                    @php $isCurrent = $recording->id === $reciter->id; @endphp
                    <li>
                        <a href="{{ route('admin.reciters.show', $recording->id) }}" @if($isCurrent) aria-current="page" @endif
                           class="group relative block h-full rounded-2xl p-[2px] transition-all motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-accent {{ $isCurrent ? 'bg-gradient-to-br from-[#0056b3] to-[#218838] shadow-lg' : 'hover:-translate-y-0.5 motion-reduce:hover:translate-y-0' }}"
                           @unless($isCurrent) :class="darkMode ? 'bg-gray-800 hover:bg-gray-700' : 'bg-gray-200 hover:bg-gray-300'" @endunless>
                            <div class="h-full rounded-[14px] p-4 flex items-start gap-3" :class="darkMode ? 'bg-gray-900' : 'bg-white'">
                                <span class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center {{ $isCurrent ? 'bg-gradient-to-br from-[#0056b3] to-[#218838] text-white' : '' }}"
                                      @unless($isCurrent) :class="darkMode ? 'bg-gray-800 text-accent' : 'bg-gray-100 text-accent'" @endunless>
                                    <i data-lucide="{{ $isCurrent ? 'circle-play' : 'mic' }}" class="w-5 h-5" aria-hidden="true"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $styleLabel($recording) }}</p>
                                    <p class="text-sm truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" dir="ltr">{{ $recording->riwayah ?: '—' }}</p>
                                    <div class="mt-3 flex flex-wrap gap-1.5 text-xs font-semibold">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                                            <i data-lucide="book-open" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ __('quran.reciter_admin.surahs_count', ['count' => $recording->surahs_with_audio ?? 0]) }}
                                        </span>
                                        @if($isCurrent)
                                            <span class="px-2 py-0.5 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">{{ __('quran.reciter_admin.current') }}</span>
                                        @endif
                                        @if($profile?->default_recording_id === $recording->id)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-amber-900/30 text-amber-300' : 'bg-amber-100 text-amber-800'">
                                                <i data-lucide="bookmark-check" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ __('quran.reciter_admin.default') }}
                                            </span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full" :class="{{ $recording->is_active ? "darkMode ? 'bg-green-900/30 text-green-400' : 'bg-green-100 text-green-800'" : "darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'" }}">
                                            <i data-lucide="{{ $recording->is_active ? 'check' : 'minus' }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
                                            {{ $recording->is_active ? __('quran.reciter_admin.active') : __('quran.reciter_admin.inactive') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @else
        <div class="rounded-2xl border px-5 py-4 flex flex-wrap items-center gap-3" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <i data-lucide="mic" class="w-5 h-5 text-accent" aria-hidden="true"></i>
            <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $styleLabel($reciter) }}</span>
            <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" dir="ltr">{{ $reciter->riwayah ?: '—' }}</span>
            @if($profile?->default_recording_id === $reciter->id)
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="darkMode ? 'bg-amber-900/30 text-amber-300' : 'bg-amber-100 text-amber-800'">{{ __('quran.reciter_admin.default') }}</span>
            @endif
        </div>
    @endif

    {{-- ============ Surah recitations (lazy, paginated) ============ --}}
    <section class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
             aria-labelledby="recitations-title"
             x-data="recitationManager({{ Js::from([
                'storeUrl' => route('admin.reciters.recitations.store', $reciter->id),
                'listUrl' => route('admin.reciters.recitations.index', $reciter->id),
                'q' => (string) request('q', ''),
                'status' => in_array(request('status'), ['with_audio', 'missing'], true) ? request('status') : 'all',
                'source' => $selectedSource ? (string) $selectedSource : '',
                'perPage' => 20,
             ]) }})">

        <div class="p-6 border-b space-y-5" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <div>
                <h2 id="recitations-title" class="text-xl font-bold gradient-text flex items-center gap-2">
                    <i data-lucide="audio-lines" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ __('quran.reciter_admin.list_title') }}
                </h2>
                <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.reciter_admin.list_subtitle') }}</p>
            </div>

            {{-- Toolbar --}}
            <div class="flex flex-col lg:flex-row lg:items-end gap-3">
                <div class="flex-1 min-w-0">
                    <label for="recitation-search" class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.reciter_admin.search_label') }}</label>
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 absolute top-1/2 -translate-y-1/2 start-3 text-gray-400 pointer-events-none" aria-hidden="true"></i>
                        <input id="recitation-search" type="search" x-model="q" @input.debounce.300ms="reload()"
                               placeholder="{{ __('quran.reciter_admin.search_placeholder') }}" autocomplete="off"
                               class="w-full ps-9 pe-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent outline-none transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>
                </div>

                <fieldset>
                    <legend class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.reciter_admin.status_label') }}</legend>
                    <div class="inline-flex p-1 rounded-xl gap-1" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        @foreach(['all' => ['status_all', 'list'], 'with_audio' => ['status_with_audio', 'check'], 'missing' => ['status_missing', 'minus']] as $value => [$labelKey, $icon])
                            <button type="button" @click="setStatus('{{ $value }}')" :aria-pressed="(status === '{{ $value }}').toString()"
                                    class="min-h-10 px-3 rounded-lg text-sm font-semibold inline-flex items-center gap-1.5 transition-all motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-accent"
                                    :class="status === '{{ $value }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : (darkMode ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                                <i data-lucide="{{ $icon }}" class="w-4 h-4" aria-hidden="true"></i>
                                <span>{{ __('quran.reciter_admin.'.$labelKey) }}</span>
                                <span class="tabular-nums text-xs opacity-80" x-show="counted" x-text="count('{{ $value }}')"></span>
                            </button>
                        @endforeach
                    </div>
                </fieldset>

                @if($surahSources->isNotEmpty())
                    <div class="lg:w-64">
                        <label for="recitation-source" class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.reciter_admin.source_label') }}</label>
                        <select id="recitation-source" x-model="source" @change="reload()"
                                class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent outline-none transition-all"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">{{ __('quran.recordings.all_sources') }}</option>
                            @foreach($surahSources as $source)
                                <option value="{{ $source->id }}">{{ $source->provider }} · {{ $source->base_url }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" aria-live="polite" x-show="counted" x-cloak
               x-text="{{ Js::from(__('quran.reciter_admin.showing')) }}.replace(':shown', shown).replace(':total', total)"></p>
        </div>

        {{-- List --}}
        <ul x-ref="list" role="list" class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'" :aria-busy="loading.toString()"></ul>

        {{-- Skeleton (first page) --}}
        <ul x-show="loading && shown === 0" role="presentation" class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
            @for($i = 0; $i < 6; $i++)
                <li class="flex items-center gap-4 px-6 py-4 animate-pulse motion-reduce:animate-none">
                    <span class="w-11 h-11 rounded-xl" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></span>
                    <span class="flex-1 space-y-2">
                        <span class="block h-3.5 w-40 max-w-full rounded" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></span>
                        <span class="block h-3 w-24 rounded" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'"></span>
                    </span>
                    <span class="hidden sm:block h-7 w-20 rounded-full" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'"></span>
                </li>
            @endfor
        </ul>

        {{-- Empty --}}
        <div x-show="counted && !loading && !error && total === 0" x-cloak class="p-12 text-center">
            <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-3 text-gray-400" aria-hidden="true"></i>
            <p class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">{{ __('quran.reciter_admin.empty') }}</p>
            <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.reciter_admin.empty_hint') }}</p>
        </div>

        {{-- Error --}}
        <div x-show="error" x-cloak class="p-8 text-center" role="alert">
            <i data-lucide="triangle-alert" class="w-10 h-10 mx-auto mb-3 text-red-500" aria-hidden="true"></i>
            <p class="font-semibold mb-4" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">{{ __('quran.reciter_admin.error') }}</p>
            <button type="button" @click="load()"
                    class="min-h-10 px-5 rounded-xl font-semibold inline-flex items-center gap-2 border-2 transition-all focus:outline-none focus:ring-2 focus:ring-accent"
                    :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
                <i data-lucide="rotate-cw" class="w-4 h-4" aria-hidden="true"></i>{{ __('quran.reciter_admin.retry') }}
            </button>
        </div>

        {{-- Infinite-scroll sentinel + fallback button --}}
        <div x-ref="sentinel" class="p-4 flex justify-center" x-show="nextPage && !error && shown > 0" x-cloak>
            <button type="button" @click="load()" :disabled="loading"
                    class="min-h-10 px-5 rounded-xl font-semibold inline-flex items-center gap-2 border-2 transition-all disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-accent"
                    :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin motion-reduce:animate-none" x-show="loading" aria-hidden="true"></i>
                <span x-text="loading ? {{ Js::from(__('quran.reciter_admin.loading')) }} : {{ Js::from(__('quran.reciter_admin.load_more')) }}"></span>
            </button>
        </div>

        {{-- Add/Edit Modal --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="recitation-modal-title">
            <div class="absolute inset-0 bg-black/50" @click="close()"></div>

            <div class="relative w-full max-w-lg rounded-2xl border shadow-2xl max-h-[80vh] overflow-y-auto"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
                 @keydown.escape.window="close()">

                <div class="p-5 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <div>
                        <h3 id="recitation-modal-title" class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'"
                            x-text="mode === 'edit' ? {{ Js::from(__('quran.recitations.modal_edit_title')) }} : {{ Js::from(__('quran.recitations.modal_add_title')) }}"></h3>
                        <p class="text-sm mt-0.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'" x-text="form.surah_label"></p>
                    </div>
                    <button type="button" @click="close()" class="w-10 h-10 rounded-lg flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-accent"
                            :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-500'" aria-label="{{ __('quran.recitations.cancel') }}">
                        <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                </div>

                <form :action="formAction" method="POST" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="_method" x-bind:value="mode === 'edit' ? 'PUT' : 'POST'">
                    <input type="hidden" name="surah_id" x-model="form.surah_id">
                    <input type="hidden" name="source_id" x-model="form.source_id">

                    <div class="p-5 space-y-4">
                        <div>
                            <label for="recitation-audio-url" class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('quran.recitations.audio_url') }} <span class="text-red-500">*</span>
                            </label>
                            <input id="recitation-audio-url" type="url" name="audio_url" x-model="form.audio_url" required dir="ltr"
                                   placeholder="{{ __('quran.recitations.audio_url_placeholder') }}"
                                   class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent focus:border-accent outline-none transition-all"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="recitation-format" class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('quran.recitations.format') }} <span class="text-red-500">*</span>
                                </label>
                                <select id="recitation-format" name="format" x-model="form.format" required
                                        class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent outline-none"
                                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                    <option value="">{{ __('quran.recitations.select_format') }}</option>
                                    <option value="mp3">MP3</option>
                                    <option value="aac">AAC</option>
                                    <option value="opus">OPUS</option>
                                </select>
                            </div>

                            <div>
                                <label for="recitation-quality" class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('quran.recitations.quality') }}
                                </label>
                                <select id="recitation-quality" name="quality" x-model="form.quality"
                                        class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent outline-none"
                                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                                    <option value="">{{ __('quran.recitations.no_quality') }}</option>
                                    <option value="high">{{ __('quran.recitations.high') }}</option>
                                    <option value="medium">{{ __('quran.recitations.medium') }}</option>
                                    <option value="low">{{ __('quran.recitations.low') }}</option>
                                </select>
                            </div>

                            <div>
                                <label for="recitation-duration" class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('quran.recitations.duration_seconds') }}
                                </label>
                                <input id="recitation-duration" type="number" name="duration_seconds" x-model="form.duration_seconds" min="0" dir="ltr"
                                       class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent outline-none"
                                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            </div>

                            <div>
                                <label for="recitation-size" class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('quran.recitations.file_size_kb') }}
                                </label>
                                <input id="recitation-size" type="number" name="file_size_kb" x-model="form.file_size_kb" min="0" dir="ltr"
                                       class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm focus:ring-2 focus:ring-accent outline-none"
                                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            </div>
                        </div>
                    </div>

                    <div class="p-5 border-t flex flex-wrap items-center justify-end gap-3" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        <button type="button" @click="close()" :disabled="submitting"
                                class="px-5 py-2.5 rounded-xl font-semibold border-2 transition-all disabled:opacity-50"
                                :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
                            {{ __('quran.recitations.cancel') }}
                        </button>
                        <button type="submit" :disabled="submitting"
                                class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 motion-reduce:hover:scale-100 flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg disabled:opacity-60 disabled:hover:scale-100">
                            <i data-lucide="loader-2" class="w-5 h-5 animate-spin motion-reduce:animate-none" x-show="submitting" aria-hidden="true"></i>
                            <span x-text="submitting ? {{ Js::from(__('quran.recitations.saving')) }} : {{ Js::from(__('quran.recitations.save')) }}"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    {{-- ============ About (biography · names · details) ============ --}}
    <section class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
             x-data="{ tab: 'bio', lang: {{ Js::from($bios->keys()->first() ?? 'ar') }} }" aria-labelledby="about-title">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
            <h2 id="about-title" class="text-xl font-bold gradient-text flex items-center gap-2">
                <i data-lucide="file-text" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ __('quran.reciter_admin.about') }}
            </h2>
            <div class="inline-flex p-1 rounded-xl gap-1" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'" role="tablist">
                @foreach(['bio' => 'biography', 'names' => 'names', 'details' => 'details'] as $tab => $labelKey)
                    <button type="button" role="tab" id="about-tab-{{ $tab }}" aria-controls="about-panel-{{ $tab }}" @click="tab = '{{ $tab }}'"
                            :aria-selected="(tab === '{{ $tab }}').toString()"
                            class="min-h-10 px-4 rounded-lg text-sm font-semibold transition-all motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-accent"
                            :class="tab === '{{ $tab }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow' : (darkMode ? 'text-gray-300 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        {{ __('quran.reciter_admin.'.$labelKey) }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Biography --}}
        <div x-show="tab === 'bio'" id="about-panel-bio" role="tabpanel" aria-labelledby="about-tab-bio">
            @if($bios->isEmpty())
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.reciter_admin.no_biography') }}</p>
            @else
                @if($bios->count() > 1)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($bios->keys() as $lang)
                            <button type="button" @click="lang = '{{ $lang }}'" :aria-pressed="(lang === '{{ $lang }}').toString()"
                                    class="min-h-10 px-3 rounded-lg text-sm font-medium border transition-all focus:outline-none focus:ring-2 focus:ring-accent"
                                    :class="lang === '{{ $lang }}' ? 'border-accent text-accent' : (darkMode ? 'border-gray-700 text-gray-400' : 'border-gray-200 text-gray-600')">
                                {{ $languages[$lang] }}
                            </button>
                        @endforeach
                    </div>
                @endif
                @foreach($bios as $lang => $bio)
                    <p x-show="lang === '{{ $lang }}'" class="p-4 rounded-xl leading-relaxed whitespace-pre-line text-start" lang="{{ $lang }}"
                       dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                       :class="darkMode ? 'bg-gray-800/50 text-gray-300' : 'bg-gray-50 text-gray-700'">{{ $bio }}</p>
                @endforeach
            @endif
        </div>

        {{-- Names --}}
        <dl x-show="tab === 'names'" x-cloak id="about-panel-names" role="tabpanel" aria-labelledby="about-tab-names" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($names as $lang => $translatedName)
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <dt class="text-xs font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $languages[$lang] }}</dt>
                    <dd class="text-lg font-semibold mt-1" lang="{{ $lang }}" dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $translatedName }}</dd>
                </div>
            @endforeach
        </dl>

        {{-- Details --}}
        <dl x-show="tab === 'details'" x-cloak id="about-panel-details" role="tabpanel" aria-labelledby="about-tab-details" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach([
                'slug' => $reciter->slug,
                'display_order' => $reciter->display_order,
                'created_at' => $reciter->created_at?->format('Y/m/d'),
            ] as $labelKey => $detail)
                <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <dt class="text-xs font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.reciter_admin.'.$labelKey) }}</dt>
                    <dd class="font-semibold mt-1 break-all {{ $labelKey === 'slug' ? 'font-mono text-sm' : '' }}" dir="ltr" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $detail ?? '—' }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function recitationManager(config) {
        const emptyForm = { id: null, surah_id: '', source_id: '', surah_label: '', audio_url: '', format: '', quality: '', duration_seconds: '', file_size_kb: '' };

        return {
            // Add/edit modal
            open: false,
            mode: 'add',
            submitting: false,
            formAction: config.storeUrl,
            form: { ...emptyForm },

            // Lazy list
            q: config.q,
            status: config.status,
            source: config.source,
            nextPage: 1,
            total: 0,
            withAudio: 0,
            missing: 0,
            shown: 0,
            counted: false,
            loading: false,
            error: false,
            requestId: 0,

            init() {
                this.load();
                new IntersectionObserver((entries) => {
                    if (entries.some((e) => e.isIntersecting)) this.loadMoreIfNeeded();
                }, { rootMargin: '400px' }).observe(this.$refs.sentinel);
            },

            count(status) {
                return { all: this.withAudio + this.missing, with_audio: this.withAudio, missing: this.missing }[status];
            },

            setStatus(status) {
                if (this.status === status) return;
                this.status = status;
                this.reload();
            },

            reload() {
                this.requestId++; // drop any in-flight page for the old filters
                this.$refs.list.innerHTML = '';
                this.shown = 0;
                this.nextPage = 1;
                this.loading = false;
                this.syncUrl();
                this.load();
            },

            loadMoreIfNeeded() {
                if (!this.nextPage || this.loading || this.error) return;
                const rect = this.$refs.sentinel.getBoundingClientRect();
                if (rect.top < window.innerHeight + 400) this.load();
            },

            async load() {
                if (this.loading || !this.nextPage) return;
                const id = ++this.requestId;
                this.loading = true;
                this.error = false;

                const params = new URLSearchParams({ page: this.nextPage, per_page: config.perPage, status: this.status });
                if (this.q.trim()) params.set('q', this.q.trim());
                if (this.source) params.set('source', this.source);

                try {
                    const res = await fetch(`${config.listUrl}?${params}`, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) throw new Error(res.status);
                    const data = await res.json();
                    if (id !== this.requestId) return;

                    this.$refs.list.insertAdjacentHTML('beforeend', data.html);
                    this.shown = this.$refs.list.children.length;
                    this.total = data.total;
                    this.withAudio = data.with_audio;
                    this.missing = data.missing;
                    this.nextPage = data.next_page;
                    this.counted = true;
                    this.$nextTick(() => {
                        window.lucide && lucide.createIcons();
                        this.loadMoreIfNeeded();
                    });
                } catch (e) {
                    if (id === this.requestId) this.error = true;
                } finally {
                    if (id === this.requestId) this.loading = false;
                }
            },

            syncUrl() {
                const url = new URL(window.location.href);
                const set = (key, value) => value ? url.searchParams.set(key, value) : url.searchParams.delete(key);
                set('q', this.q.trim());
                set('status', this.status === 'all' ? '' : this.status);
                set('source', this.source);
                history.replaceState(null, '', url);
            },

            openAdd(data) {
                this.mode = 'add';
                this.formAction = config.storeUrl;
                this.form = { ...emptyForm, surah_id: data.surah_id, source_id: data.source_id ?? '', surah_label: data.surah_label };
                this.show();
            },

            openEdit(data) {
                this.mode = 'edit';
                this.formAction = data.update_url;
                this.form = {
                    id: data.id,
                    surah_id: data.surah_id,
                    source_id: data.source_id ?? '',
                    surah_label: data.surah_label,
                    audio_url: data.audio_url ?? '',
                    format: data.format ?? '',
                    quality: data.quality ?? '',
                    duration_seconds: data.duration_seconds ?? '',
                    file_size_kb: data.file_size_kb ?? '',
                };
                this.show();
            },

            show() {
                this.submitting = false;
                this.open = true;
                this.$nextTick(() => window.lucide && lucide.createIcons());
            },

            close() {
                if (this.submitting) return;
                this.open = false;
            },
        };
    }
</script>
@endpush
