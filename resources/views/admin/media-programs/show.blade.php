@extends('layouts.admin')

@section('title', $program->getTranslation('title', 'ar') ?? $program->slug)

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.media-programs.index') }}"
           class="p-2 rounded-xl transition-colors"
           :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold gradient-text">{{ $program->getTranslation('title', 'ar') ?? $program->slug }}</h1>
            <p class="mt-1 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $program->slug }}</p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        {{-- Opens the YouTube import modal (rendered inside @section('content')) --}}
        <button type="button" @click="window.dispatchEvent(new CustomEvent('open-yt-import'))"
                title="{{ __('admin.yt_import_hint') }}"
                class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border"
                :class="darkMode ? 'border-gray-700 text-gray-200 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
            <i data-lucide="youtube" class="w-4 h-4 text-red-600"></i>
            {{ __('admin.import_from_youtube') }}
        </button>
        <a href="{{ route('admin.media-programs.edit', $program->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="pencil" class="w-4 h-4"></i>
            {{ __('admin.edit') }}
        </a>
    </div>
</div>
@endsection

@section('content')
@php
    // poster_url/banner_url are the guarded accessors; raw getFirstMedia* throws on the custom media table.
    $posterUrl = $program->poster_url;
    $bannerUrl = $program->banner_url;
    $ytPrefill = $program->youtube_playlist_id
        ? 'https://www.youtube.com/playlist?list='.$program->youtube_playlist_id
        : '';
@endphp

{{-- ============================ YouTube import modal ============================ --}}
<div x-data="youtubeImport({
        analyzeRoute: '{{ route('admin.media-programs.youtube.analyze', $program->id) }}',
        syncRoute: '{{ route('admin.media-programs.youtube.sync', $program->id) }}',
        csrf: '{{ csrf_token() }}',
        prefill: @js($ytPrefill)
     })"
     x-show="open" x-cloak
     class="fixed inset-0 z-[60] flex items-center justify-center p-4"
     @keydown.escape.window="if (step !== 'syncing') close()">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="if (step !== 'syncing') close()"></div>

    <div class="relative w-full max-w-xl max-h-[88vh] flex flex-col rounded-2xl border shadow-2xl overflow-hidden"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        {{-- Header --}}
        <div class="px-6 py-4 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h3 class="text-lg font-semibold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="youtube" class="w-5 h-5 text-red-600"></i>
                {{ __('admin.yt_import_title') }}
            </h3>
            <button type="button" @click="close()" :disabled="step === 'syncing'"
                    class="p-2 rounded-lg disabled:opacity-40"
                    :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
            {{-- STEP: input --}}
            <div x-show="step === 'input'">
                <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.yt_import_hint') }}</p>
                <label class="block text-sm font-medium mb-1.5" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.yt_playlist_url_label') }}</label>
                <input type="url" x-model="url" dir="ltr" placeholder="{{ __('admin.yt_playlist_url_placeholder') }}"
                       @keydown.enter.prevent="analyze()"
                       class="w-full text-base sm:text-sm rounded-lg border px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                <p x-show="error" x-text="error" class="mt-2 text-sm text-red-500"></p>
            </div>

            {{-- STEP: analyzing --}}
            <div x-show="step === 'analyzing'" class="py-10 flex flex-col items-center justify-center gap-3 text-center">
                <svg class="animate-spin w-8 h-8 text-[#0056b3]" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.yt_analyzing') }}</p>
            </div>

            {{-- STEP: preview --}}
            <div x-show="step === 'preview'" x-cloak>
                <div class="mb-3">
                    <p class="font-semibold break-words" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="analysis?.title"></p>
                    <p class="text-sm mt-0.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <span x-text="analysis?.channel"></span>
                        <span> • </span>
                        <span x-text="@js(__('admin.yt_videos_found')).replace(':count', analysis?.count ?? 0)"></span>
                    </p>
                </div>
                <ul class="rounded-xl border divide-y max-h-64 overflow-y-auto custom-scrollbar"
                    :class="darkMode ? 'border-gray-800 divide-gray-800' : 'border-gray-200 divide-gray-100'">
                    <template x-for="item in (analysis?.entries || [])" :key="item.video_id">
                        <li class="flex items-center gap-2 px-3 py-2">
                            <span class="text-xs font-mono px-1.5 py-0.5 rounded flex-shrink-0"
                                  :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'" x-text="item.position"></span>
                            <span class="text-sm truncate" :class="darkMode ? 'text-gray-200' : 'text-gray-800'" x-text="item.title"></span>
                        </li>
                    </template>
                </ul>
            </div>

            {{-- STEP: syncing --}}
            <div x-show="step === 'syncing'" x-cloak class="py-4">
                <div class="flex items-center justify-between text-sm mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    <span class="font-medium">{{ __('admin.yt_importing') }}</span>
                    <span x-text="progress.processed + ' / ' + progress.total"></span>
                </div>
                <div class="w-full h-3 rounded-full overflow-hidden" :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'">
                    <div class="h-full bg-gradient-to-r from-[#0056b3] to-[#218838] transition-all duration-500"
                         :style="'width:' + progress.percent + '%'"></div>
                </div>
                <p class="mt-2 text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="progress.message"></p>
                <div class="mt-4 flex items-center gap-4 text-sm">
                    <span class="flex items-center gap-1.5 text-green-500"><i data-lucide="plus-circle" class="w-4 h-4"></i>{{ __('admin.yt_episodes_created') }}: <span x-text="progress.created"></span></span>
                    <span class="flex items-center gap-1.5 text-blue-500"><i data-lucide="refresh-cw" class="w-4 h-4"></i>{{ __('admin.yt_episodes_updated') }}: <span x-text="progress.updated"></span></span>
                </div>
            </div>

            {{-- STEP: done --}}
            <div x-show="step === 'done'" x-cloak class="py-8 flex flex-col items-center justify-center gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                    <i data-lucide="check" class="w-7 h-7 text-green-600"></i>
                </div>
                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.yt_import_done') }}</p>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('admin.yt_episodes_created') }}: <span x-text="progress.created"></span> •
                    {{ __('admin.yt_episodes_updated') }}: <span x-text="progress.updated"></span>
                </p>
            </div>

            {{-- STEP: error --}}
            <div x-show="step === 'error'" x-cloak class="py-8 flex flex-col items-center justify-center gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-7 h-7 text-red-600"></i>
                </div>
                <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.yt_import_failed') }}</p>
                <p class="text-sm break-words max-w-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'" x-text="error"></p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t flex items-center justify-end gap-3" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            {{-- input --}}
            <template x-if="step === 'input'">
                <div class="flex gap-3">
                    <button type="button" @click="close()" class="px-4 py-2 rounded-xl text-sm font-semibold border"
                            :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('admin.cancel') }}</button>
                    <button type="button" @click="analyze()" :disabled="!url"
                            class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <i data-lucide="search" class="w-4 h-4"></i>{{ __('admin.yt_analyze') }}
                    </button>
                </div>
            </template>
            {{-- preview --}}
            <template x-if="step === 'preview'">
                <div class="flex gap-3">
                    <button type="button" @click="step = 'input'" class="px-4 py-2 rounded-xl text-sm font-semibold border"
                            :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('admin.back') }}</button>
                    <button type="button" @click="confirm()"
                            class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all flex items-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span x-text="@js(__('admin.yt_confirm_import')).replace(':count', analysis?.count ?? 0)"></span>
                    </button>
                </div>
            </template>
            {{-- done / error --}}
            <template x-if="step === 'done' || step === 'error'">
                <div class="flex gap-3">
                    <button type="button" x-show="step === 'error'" @click="step = 'input'"
                            class="px-4 py-2 rounded-xl text-sm font-semibold border"
                            :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('admin.yt_retry') }}</button>
                    <button type="button" @click="close()"
                            class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all">{{ __('admin.yt_close') }}</button>
                </div>
            </template>
        </div>
    </div>
</div>

<!-- Hero: Facebook-style editable cover + square poster -->
<div class="rounded-2xl border overflow-hidden mb-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
    <!-- Cover / banner -->
    <div class="relative h-48 sm:h-64 bg-gradient-to-r from-[#0056b3] to-[#218838]">
        @if($bannerUrl)
            <img src="{{ $bannerUrl }}" class="absolute inset-0 w-full h-full object-cover" alt="banner">
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>

        <form method="POST" action="{{ route('admin.media-programs.images', $program->id) }}"
              enctype="multipart/form-data" class="absolute top-3 end-3">
            @csrf
            <label class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold cursor-pointer transition-all hover:scale-105 bg-black/50 text-white backdrop-blur-sm border border-white/20">
                <i data-lucide="camera" class="w-4 h-4"></i>
                {{ __('admin.change_banner') }}
                <input type="file" name="banner" accept="image/jpeg,image/jpg,image/png,image/webp"
                       class="hidden" onchange="this.form.submit()">
            </label>
        </form>
    </div>

    <!-- Header row: square poster + title + badges -->
    <div class="px-4 sm:px-6 pb-5">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4 -mt-12 sm:-mt-14">
            <div class="relative flex-shrink-0 w-24 h-24 sm:w-32 sm:h-32">
                <div class="w-full h-full rounded-2xl overflow-hidden shadow-2xl ring-4"
                     :class="darkMode ? 'ring-gray-900 bg-gray-800' : 'ring-white bg-gray-100'">
                    @if($posterUrl)
                        <img src="{{ $posterUrl }}" class="w-full h-full object-cover" alt="poster">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                            <i data-lucide="tv" class="w-10 h-10 text-white/80"></i>
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.media-programs.images', $program->id) }}"
                      enctype="multipart/form-data" class="absolute -bottom-2 -end-2">
                    @csrf
                    <label title="{{ __('admin.change_poster') }}"
                           class="flex items-center justify-center w-8 h-8 rounded-full cursor-pointer shadow-lg transition-all hover:scale-110 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        <input type="file" name="poster" accept="image/jpeg,image/jpg,image/png,image/webp"
                               class="hidden" onchange="this.form.submit()">
                    </label>
                </form>
            </div>

            <div class="min-w-0 flex-1 sm:pb-1">
                <h2 class="text-xl sm:text-2xl font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $program->getTranslation('title', 'ar') ?? $program->slug }}
                </h2>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                    @switch($program->publishStatus())
                        @case('published')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('admin.published') }}</span>
                            @break
                        @case('scheduled')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                {{ __('admin.scheduled') }} • {{ $program->publish_at?->format('Y-m-d H:i') }}
                            </span>
                            @break
                        @default
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">{{ __('admin.draft') }}</span>
                    @endswitch
                    <span class="px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">{{ $program->category?->labelAr() }}</span>
                    @if($program->year)
                        <span class="px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">{{ $program->year }}</span>
                    @endif
                    <span class="px-2 py-0.5 rounded-full flex items-center gap-1" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="star" class="w-3 h-3 text-yellow-500"></i>{{ $program->rating }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        {{ $program->total_seasons }} {{ __('admin.season') }} • {{ $program->total_episodes }} {{ __('admin.episode') }}
                    </span>
                    @if($program->is_featured)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('admin.featured_only') }}</span>
                    @endif
                    @if($program->is_trending)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ __('admin.is_trending') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6"
     x-data="episodesManager()">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Episodes Manager -->
        <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="px-6 py-4 border-b flex flex-wrap items-center justify-between gap-2" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div>
                    <h3 class="text-lg font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.media_episodes') }} ({{ $program->episodes->count() }})
                    </h3>
                    @if($program->episodes->count() > 1)
                    <p class="text-xs mt-0.5" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        {{ __('admin.drag_to_reorder') }}
                    </p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="openLibrary()"
                            class="px-4 py-1.5 rounded-lg text-sm font-semibold border transition-colors flex items-center gap-1"
                            :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="library" class="w-3.5 h-3.5"></i>
                        {{ __('admin.episodes_library') }}
                    </button>
                    <a href="{{ route('admin.media-episodes.create', ['program_id' => $program->id]) }}"
                       class="px-4 py-1.5 rounded-lg text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all flex items-center gap-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        {{ __('admin.add_episode') }}
                    </a>
                </div>
            </div>

            @if($program->episodes->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <i data-lucide="film" class="w-10 h-10 mb-3" :class="darkMode ? 'text-gray-600' : 'text-gray-300'"></i>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.no_episodes_found') }}</p>
            </div>
            @else
            <ul id="episodes-sortable" class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                @foreach($program->episodes as $episode)
                <li class="flex items-center justify-between px-4 sm:px-6 py-3 transition-colors"
                    data-episode-id="{{ $episode->id }}" data-season="{{ $episode->season_number }}"
                    :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="episode-drag-handle cursor-grab active:cursor-grabbing p-1 rounded touch-none"
                              :class="darkMode ? 'text-gray-600 hover:text-gray-300' : 'text-gray-300 hover:text-gray-600'">
                            <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                        </span>
                        <span class="episode-number-badge text-xs font-mono px-2 py-0.5 rounded flex-shrink-0"
                              :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                            S{{ $episode->season_number }}E{{ str_pad($episode->episode_number, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="truncate text-sm" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                            {{ $episode->getTranslation('title', 'ar') ?? $episode->slug }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <span class="text-xs me-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            {{ gmdate('G:i', $episode->duration_seconds ?? 0) }}
                        </span>
                        <a href="{{ route('admin.media-episodes.edit', $episode->id) }}"
                           class="p-1.5 rounded-lg" title="{{ __('admin.edit') }}"
                           :class="darkMode ? 'hover:bg-gray-700 text-blue-400' : 'hover:bg-blue-50 text-blue-600'">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.media-episodes.destroy', $episode->id) }}"
                              onsubmit="return window.confirmSubmit(this, { message: '{{ __('admin.confirm_delete') }}' })" class="inline">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="from_program" value="1">
                            <button type="submit" class="p-1.5 rounded-lg" title="{{ __('admin.delete') }}"
                                    :class="darkMode ? 'hover:bg-gray-700 text-red-400' : 'hover:bg-red-50 text-red-600'">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>

        <!-- Translations -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.translations') }}</h3>

            @php $locales = ['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو']; @endphp
            <div x-data="{ activeLocale: 'ar' }">
                <div class="flex gap-2 mb-4 overflow-x-auto whitespace-nowrap pb-1">
                    @foreach($locales as $code => $label)
                    <button type="button" @click="activeLocale = '{{ $code }}'"
                            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                            :class="activeLocale === '{{ $code }}'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                : (darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100')">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                @foreach($locales as $code => $label)
                @php $t = $program->translations->where('locale', $code)->first(); @endphp
                <div x-show="activeLocale === '{{ $code }}'" dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                    @if($t && ($t->title || $t->description || $t->host))
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-medium mb-1 uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.title') }}</p>
                            <p class="font-semibold text-lg break-words" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $t->title }}</p>
                        </div>
                        @if($t->description)
                        <div>
                            <p class="text-xs font-medium mb-1 uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.description') }}</p>
                            {{-- WYSIWYG content authored by trusted admins (same convention as articles) --}}
                            <div class="text-sm leading-relaxed prose prose-sm max-w-none break-words"
                                 :class="darkMode ? 'text-gray-300 prose-invert' : 'text-gray-700'">
                                {!! $t->description !!}
                            </div>
                        </div>
                        @endif
                        @if($t->host)
                        <div>
                            <p class="text-xs font-medium mb-1 uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.program_host') }}</p>
                            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t->host }}</p>
                        </div>
                        @endif
                        @if($t->seo_title || $t->seo_description || $t->seo_keywords)
                        <div class="pt-3 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                            <p class="text-xs font-medium mb-2 uppercase tracking-wide flex items-center gap-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                <i data-lucide="search-check" class="w-3.5 h-3.5"></i>
                                {{ __('admin.seo_settings') }}
                            </p>
                            <dl class="space-y-1.5 text-sm">
                                @if($t->seo_title)
                                <div class="flex gap-2">
                                    <dt class="flex-shrink-0" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.meta_title') }}:</dt>
                                    <dd class="break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t->seo_title }}</dd>
                                </div>
                                @endif
                                @if($t->seo_description)
                                <div class="flex gap-2">
                                    <dt class="flex-shrink-0" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.meta_description') }}:</dt>
                                    <dd class="break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t->seo_description }}</dd>
                                </div>
                                @endif
                                @if($t->seo_keywords)
                                <div class="flex gap-2">
                                    <dt class="flex-shrink-0" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.seo_keywords') }}:</dt>
                                    <dd class="break-words" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t->seo_keywords }}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <i data-lucide="languages" class="w-8 h-8 mb-2" :class="darkMode ? 'text-gray-600' : 'text-gray-300'"></i>
                        <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.no_translation') }}</p>
                        <a href="{{ route('admin.media-programs.edit', $program->id) }}"
                           class="mt-2 text-sm text-[#0056b3] hover:underline">{{ __('admin.edit') }}</a>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Program Info -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.program_information') }}</h3>
            <dl class="space-y-3">
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.slug') }}</dt>
                    <dd class="text-sm font-medium truncate" dir="ltr" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->slug }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.type') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->category?->labelAr() }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.maturity_rating') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.maturity_'.($program->maturity_rating?->value ?? 'all')) }}
                    </dd>
                </div>
                @if($program->year)
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.program_year') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->year }}</dd>
                </div>
                @endif
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.total_seasons') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->total_seasons }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.total_episodes') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->total_episodes }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.program_rating') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->rating }} / 10</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.display_order') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->display_order }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.publish_status') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.'.$program->publishStatus()) }}
                    </dd>
                </div>
                @if($program->publish_at)
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.publish_at') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->publish_at->format('Y-m-d H:i') }}</dd>
                </div>
                @endif
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.views') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($program->views_count) }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.created_at') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->created_at->format('Y-m-d') }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.updated_at') }}</dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $program->updated_at->format('Y-m-d') }}</dd>
                </div>
            </dl>
        </div>

        <!-- Delete -->
        <form method="POST" action="{{ route('admin.media-programs.destroy', $program->id) }}"
              onsubmit="return window.confirmSubmit(this, { message: '{{ __('admin.confirm_delete') }}' })">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="w-full py-2.5 rounded-xl font-semibold text-red-600 border border-red-300 hover:bg-red-50 transition-colors flex items-center justify-center gap-2">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                {{ __('admin.delete') }}
            </button>
        </form>
    </div>

    <!-- Episodes Library Modal -->
    <div x-show="libraryOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="libraryOpen = false">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="libraryOpen = false"></div>
        <div class="relative w-full max-w-2xl max-h-[85vh] flex flex-col rounded-2xl border shadow-2xl overflow-hidden"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <!-- Modal header -->
            <div class="px-6 py-4 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <h3 class="text-lg font-semibold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="library" class="w-5 h-5 text-[#64CCC5]"></i>
                    {{ __('admin.episodes_library') }}
                </h3>
                <button type="button" @click="libraryOpen = false" class="p-2 rounded-lg"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Search -->
            <div class="px-6 py-3 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    <input type="text" x-model="libraryQuery" @input.debounce.400ms="searchLibrary()"
                           placeholder="{{ __('admin.search') }}"
                           class="w-full text-base sm:text-sm rounded-lg border ps-9 pe-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                </div>
            </div>

            <!-- Results -->
            <div class="flex-1 overflow-y-auto custom-scrollbar">
                <template x-if="libraryLoading">
                    <div class="p-6 space-y-3">
                        <template x-for="i in 4" :key="i">
                            <div class="h-12 rounded-xl animate-pulse" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'"></div>
                        </template>
                    </div>
                </template>
                <template x-if="!libraryLoading && libraryItems.length === 0">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <i data-lucide="film" class="w-8 h-8 mb-2" :class="darkMode ? 'text-gray-600' : 'text-gray-300'"></i>
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.no_results') }}</p>
                    </div>
                </template>
                <template x-if="!libraryLoading && libraryItems.length > 0">
                    <ul class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                        <template x-for="item in libraryItems" :key="item.id">
                            <li class="flex items-center gap-3 px-6 py-3 cursor-pointer transition-colors"
                                :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'"
                                @click="toggleSelect(item.id)">
                                <input type="checkbox" :checked="selectedEpisodes.includes(item.id)"
                                       class="w-4 h-4 rounded text-[#0056b3] pointer-events-none">
                                <span class="text-xs font-mono px-2 py-0.5 rounded flex-shrink-0"
                                      :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'"
                                      x-text="'S' + item.season_number + 'E' + item.episode_number"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm truncate" :class="darkMode ? 'text-gray-200' : 'text-gray-800'" x-text="episodeTitle(item)"></p>
                                    <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="episodeProgram(item)"></p>
                                </div>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>

            <!-- Footer: pagination + assign -->
            <div class="px-6 py-4 border-t flex flex-wrap items-center justify-between gap-3" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div class="flex items-center gap-2">
                    <button type="button" @click="libraryPage > 1 && (libraryPage--, loadLibrary())"
                            :disabled="libraryPage <= 1"
                            class="p-2 rounded-lg border disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
                        <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-4 h-4"></i>
                    </button>
                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'"
                          x-text="libraryPage + ' / ' + libraryLastPage"></span>
                    <button type="button" @click="libraryPage < libraryLastPage && (libraryPage++, loadLibrary())"
                            :disabled="libraryPage >= libraryLastPage"
                            class="p-2 rounded-lg border disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
                        <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
                    </button>
                </div>
                <button type="button" @click="assignSelected()"
                        :disabled="selectedEpisodes.length === 0 || assigning"
                        class="px-5 py-2 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>{{ __('admin.assign_selected') }} (<span x-text="selectedEpisodes.length"></span>)</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function episodesManager() {
    return {
        libraryOpen: false,
        libraryQuery: '',
        libraryPage: 1,
        libraryLastPage: 1,
        libraryItems: [],
        libraryLoading: false,
        selectedEpisodes: [],
        assigning: false,

        openLibrary() {
            this.libraryOpen = true;
            this.selectedEpisodes = [];
            this.libraryPage = 1;
            this.loadLibrary();
        },

        searchLibrary() {
            this.libraryPage = 1;
            this.loadLibrary();
        },

        loadLibrary() {
            this.libraryLoading = true;
            const params = new URLSearchParams({
                exclude_program_id: '{{ $program->id }}',
                per_page: '8',
                page: this.libraryPage,
                q: this.libraryQuery,
            });
            fetch('{{ route('admin.media-episodes.index') }}?' + params, {
                headers: { 'Accept': 'application/json' },
            })
                .then(r => r.json())
                .then(d => {
                    this.libraryItems = d.data || [];
                    this.libraryLastPage = d.meta?.last_page || 1;
                })
                .finally(() => { this.libraryLoading = false; });
        },

        episodeTitle(item) {
            const t = (item.translations || []).find(t => t.locale === 'ar') || (item.translations || [])[0];
            return t?.title || item.slug;
        },

        episodeProgram(item) {
            const t = (item.program?.translations || []).find(t => t.locale === 'ar') || (item.program?.translations || [])[0];
            return t?.title || item.program?.slug || '';
        },

        toggleSelect(id) {
            this.selectedEpisodes = this.selectedEpisodes.includes(id)
                ? this.selectedEpisodes.filter(i => i !== id)
                : [...this.selectedEpisodes, id];
        },

        assignSelected() {
            if (this.selectedEpisodes.length === 0 || this.assigning) return;
            this.assigning = true;
            fetch('{{ route('admin.media-programs.episodes.assign', $program->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ episode_ids: this.selectedEpisodes }),
            })
                .then(r => r.json())
                .then(d => { if (d.success) window.location.reload(); })
                .finally(() => { this.assigning = false; });
        },
    };
}

function youtubeImport(config) {
    return {
        open: false,
        step: 'input',
        url: config.prefill || '',
        analysis: null,
        error: '',
        runId: null,
        statusUrl: null,
        poll: null,
        progress: { percent: 0, processed: 0, total: 0, created: 0, updated: 0, message: '' },

        init() {
            window.addEventListener('open-yt-import', () => this.openModal());
        },

        openModal() {
            this.open = true;
            // Don't reset an in-flight import if the user reopens the modal.
            if (this.step === 'syncing') return;
            this.step = 'input';
            this.error = '';
        },

        close() {
            if (this.step === 'syncing') return; // can't close mid-import
            this.open = false;
            if (this.poll) { clearInterval(this.poll); this.poll = null; }
            if (this.step === 'done') { window.location.reload(); }
        },

        postHeaders() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': config.csrf,
            };
        },

        async analyze() {
            if (!this.url) return;
            this.step = 'analyzing';
            this.error = '';
            try {
                const r = await fetch(config.analyzeRoute, {
                    method: 'POST', headers: this.postHeaders(),
                    body: JSON.stringify({ playlist_url: this.url }),
                });
                const d = await r.json();
                if (!r.ok || !d.success) {
                    this.error = d.message || 'Error';
                    this.step = 'input';
                    return;
                }
                this.analysis = d.data;
                this.step = 'preview';
                this.$nextTick(() => window.lucide && window.lucide.createIcons());
            } catch (e) {
                this.error = String(e);
                this.step = 'input';
            }
        },

        async confirm() {
            this.step = 'syncing';
            this.error = '';
            this.progress = { percent: 0, processed: 0, total: this.analysis?.count || 0, created: 0, updated: 0, message: '' };
            try {
                const r = await fetch(config.syncRoute, {
                    method: 'POST', headers: this.postHeaders(),
                    body: JSON.stringify({ playlist_url: this.url }),
                });
                const d = await r.json();
                if (!r.ok || !d.success) {
                    this.error = d.message || 'Error';
                    this.step = 'error';
                    return;
                }
                this.runId = d.run_id;
                this.statusUrl = d.status_url;
                this.poll = setInterval(() => this.tick(), 2000);
                this.tick();
            } catch (e) {
                this.error = String(e);
                this.step = 'error';
            }
        },

        async tick() {
            try {
                const r = await fetch(this.statusUrl, { headers: { 'Accept': 'application/json' } });
                const d = await r.json();
                if (!d.success) return;
                const x = d.data;
                this.progress = {
                    percent: x.percent, processed: x.processed, total: x.total,
                    created: x.created, updated: x.updated, message: x.message || '',
                };
                if (x.is_terminal) {
                    clearInterval(this.poll); this.poll = null;
                    if (x.status === 'completed') {
                        this.step = 'done';
                    } else {
                        this.error = x.error || 'Error';
                        this.step = 'error';
                    }
                    this.$nextTick(() => window.lucide && window.lucide.createIcons());
                }
            } catch (e) { /* keep polling */ }
        },
    };
}

document.addEventListener('DOMContentLoaded', function () {
    const list = document.getElementById('episodes-sortable');
    if (!list || typeof Sortable === 'undefined') return;

    new Sortable(list, {
        handle: '.episode-drag-handle',
        animation: 150,
        onEnd: function () {
            const rows = Array.from(list.querySelectorAll('[data-episode-id]'));
            const ids = rows.map(el => parseInt(el.dataset.episodeId, 10));

            fetch('{{ route('admin.media-programs.episodes.reorder', $program->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ episode_ids: ids }),
            })
                .then(r => r.json())
                .then(d => {
                    if (!d.success) return;
                    // Mirror the server-side renumbering (episode_number = position).
                    rows.forEach((el, index) => {
                        const badge = el.querySelector('.episode-number-badge');
                        if (badge) {
                            badge.textContent = 'S' + el.dataset.season + 'E' + String(index + 1).padStart(2, '0');
                        }
                    });
                });
        },
    });
});
</script>
@endpush
