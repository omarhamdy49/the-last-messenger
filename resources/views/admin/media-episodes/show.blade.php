@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.media-episodes.index', ['program_id' => $episode->program_id]) }}"
           class="p-2 rounded-xl transition-colors"
           :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
        </a>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="clapperboard" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.episode_details') }}
        </h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        @if($episode->video_url)
        {{-- Sync this episode's data from its YouTube video (synchronous) --}}
        <form method="POST" action="{{ route('admin.media-episodes.youtube.sync', $episode->id) }}"
              x-data="{ busy: false }"
              @submit="if (!confirm(@js(__('admin.yt_confirm_episode_sync')))) { $event.preventDefault(); return; } busy = true;">
            @csrf
            <button type="submit" :disabled="busy" title="{{ __('admin.yt_sync_hint') }}"
                    class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border disabled:opacity-60 disabled:cursor-wait"
                    :class="darkMode ? 'border-gray-700 text-gray-200 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                <i data-lucide="youtube" class="w-4 h-4 text-red-600" x-show="!busy"></i>
                <svg x-show="busy" x-cloak class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="busy ? @js(__('admin.yt_importing')) : @js(__('admin.sync_from_youtube'))"></span>
            </button>
        </form>
        @endif
        <a href="{{ route('admin.media-episodes.edit', $episode->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="pencil" class="w-4 h-4"></i>
            {{ __('admin.edit') }}
        </a>
    </div>
</div>
@endsection

@section('content')
@php
    $locales = enabled_content_languages();
    // Guarded accessors — raw getFirstMedia* throws on the custom media table.
    $thumbUrl = $episode->thumb_url;
    $bannerUrl = $episode->program?->banner_url;
    $posterThumbUrl = $episode->program?->poster_thumb_url;
@endphp

<!-- Hero: Netflix-style — full-height cover, all content overlaid on the image -->
<div class="relative rounded-2xl overflow-hidden mb-6 h-72 sm:h-96 animate-fadeInUp bg-gradient-to-r from-[#0056b3] to-[#218838]">
    @if($bannerUrl)
        <img src="{{ $bannerUrl }}" class="absolute inset-0 w-full h-full object-cover" alt="banner">
    @elseif($thumbUrl)
        <img src="{{ $thumbUrl }}" class="absolute inset-0 w-full h-full object-cover blur-sm scale-105" alt="backdrop">
    @endif
    {{-- Readability scrim: strong at the bottom where title/badges sit --}}
    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-black/10"></div>

    <!-- Program chip (top corner) -->
    @if($episode->program)
    <a href="{{ route('admin.media-programs.show', $episode->program_id) }}"
       class="absolute top-3 end-3 flex items-center gap-2 ps-1.5 pe-3 py-1.5 rounded-full text-xs font-semibold transition-all hover:scale-105 bg-black/50 text-white backdrop-blur-sm border border-white/20">
        @if($posterThumbUrl)
            <img src="{{ $posterThumbUrl }}" class="w-6 h-6 rounded-full object-cover" alt="poster">
        @else
            <span class="w-6 h-6 rounded-full flex items-center justify-center bg-gradient-to-r from-[#0056b3] to-[#218838]">
                <i data-lucide="tv" class="w-3.5 h-3.5 text-white"></i>
            </span>
        @endif
        {{ $episode->program->getTranslation('title', 'ar') ?? $episode->program->slug }}
    </a>
    @endif

    <!-- Overlaid content: thumbnail + title + meta chips -->
    <div class="absolute bottom-0 inset-x-0 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="relative flex-shrink-0 w-36 sm:w-48 aspect-video">
                <div class="w-full h-full rounded-2xl overflow-hidden shadow-2xl ring-2 ring-white/40 bg-black/30 backdrop-blur-sm">
                    @if($thumbUrl)
                        <img src="{{ $thumbUrl }}" class="w-full h-full object-cover" alt="{{ $episode->slug }}">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                            <i data-lucide="play" class="w-10 h-10 text-white/80"></i>
                        </div>
                    @endif
                </div>

                {{-- Inline thumbnail change (Facebook-style, like the program hero) --}}
                <form method="POST" action="{{ route('admin.media-episodes.thumbnail', $episode->id) }}"
                      enctype="multipart/form-data" class="absolute -top-2 -end-2">
                    @csrf
                    <label title="{{ __('admin.change_thumbnail') }}"
                           class="flex items-center justify-center w-8 h-8 rounded-full cursor-pointer shadow-lg transition-all hover:scale-110 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                        <i data-lucide="camera" class="w-4 h-4"></i>
                        <input type="file" name="thumbnail" accept="image/jpeg,image/jpg,image/png,image/webp"
                               class="hidden" onchange="this.form.submit()">
                    </label>
                </form>

                @if($thumbUrl)
                <form method="POST" action="{{ route('admin.media-episodes.thumbnail', $episode->id) }}"
                      onsubmit="return window.confirmSubmit(this, { message: '{{ __('admin.confirm_delete') }}' })"
                      class="absolute -top-2 -start-2">
                    @csrf
                    <input type="hidden" name="remove" value="1">
                    <button type="submit" title="{{ __('admin.remove_thumbnail') }}"
                            class="flex items-center justify-center w-8 h-8 rounded-full shadow-lg transition-all hover:scale-110 bg-red-600 text-white">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <h2 class="text-2xl sm:text-3xl font-bold truncate text-white drop-shadow-lg">
                    {{ $episode->getTranslation('title', 'ar') ?? $episode->slug }}
                </h2>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                    <span class="px-2 py-0.5 rounded-full font-mono text-xs font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow">
                        S{{ $episode->season_number }}E{{ str_pad($episode->episode_number, 2, '0', STR_PAD_LEFT) }}
                    </span>
                    @switch($episode->publishStatus())
                        @case('published')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-500/85 text-white backdrop-blur-sm">{{ __('admin.published') }}</span>
                            @break
                        @case('scheduled')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-500/90 text-white backdrop-blur-sm">
                                {{ __('admin.scheduled') }} • {{ $episode->publish_at?->format('Y-m-d H:i') }}
                            </span>
                            @break
                        @default
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-500/80 text-white backdrop-blur-sm">{{ __('admin.draft') }}</span>
                    @endswitch
                    @if($episode->duration_seconds)
                    <span class="px-2 py-0.5 rounded-full flex items-center gap-1 bg-white/15 text-white backdrop-blur-sm border border-white/10">
                        <i data-lucide="clock" class="w-3 h-3"></i>{{ $episode->duration_formatted }}
                    </span>
                    @endif
                    <span class="px-2 py-0.5 rounded-full flex items-center gap-1 bg-white/15 text-white backdrop-blur-sm border border-white/10">
                        <i data-lucide="eye" class="w-3 h-3"></i>{{ number_format($episode->views_count) }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full bg-white/15 text-white backdrop-blur-sm border border-white/10">
                        {{ $episode->created_at->format('Y-m-d') }}
                    </span>
                    @if($episode->is_featured)
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/90 text-white backdrop-blur-sm flex items-center gap-1">
                        <i data-lucide="star" class="w-3 h-3"></i>{{ __('admin.featured_only') }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Video Preview -->
        @if($episode->video_url)
        <div class="rounded-2xl border overflow-hidden animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="aspect-video">
                <iframe src="{{ $episode->video_url }}"
                        class="w-full h-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </div>
        </div>
        @endif

        <!-- Translations -->
        <div class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.translations') }}
            </h3>

            <div x-data="{ activeLocale: 'ar' }">
                <div class="flex gap-2 mb-4 overflow-x-auto whitespace-nowrap pb-1">
                    @foreach($locales as $code => $label)
                    @php $hasTranslation = $episode->translations->firstWhere('locale', $code)?->title; @endphp
                    <button type="button" @click="activeLocale = '{{ $code }}'"
                            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5"
                            :class="activeLocale === '{{ $code }}'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                : (darkMode ? 'text-gray-400 hover:bg-gray-800' : 'text-gray-600 hover:bg-gray-100')">
                        {{ $label }}
                        <span class="w-1.5 h-1.5 rounded-full {{ filled($hasTranslation) ? 'bg-green-400' : 'bg-gray-400/60' }}"></span>
                    </button>
                    @endforeach
                </div>

                @foreach($locales as $code => $label)
                @php $t = $episode->translations->firstWhere('locale', $code); @endphp
                <div x-show="activeLocale === '{{ $code }}'" dir="{{ in_array($code, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                    @if($t && filled($t->title))
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-medium mb-1 uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.title') }}</p>
                            <p class="font-semibold text-lg" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $t->title }}</p>
                        </div>
                        @if(filled($t->description))
                        <div>
                            <p class="text-xs font-medium mb-1 uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.description') }}</p>
                            {{-- WYSIWYG content authored by trusted admins --}}
                            <div class="prose prose-sm max-w-none text-sm leading-relaxed" :class="darkMode ? 'prose-invert text-gray-300' : 'text-gray-700'">
                                {!! $t->description !!}
                            </div>
                        </div>
                        @endif
                        @if(filled($t->transcript))
                        <div>
                            <p class="text-xs font-medium mb-1 uppercase tracking-wide" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.transcript') }}</p>
                            {{-- WYSIWYG content authored by trusted admins --}}
                            <div class="prose prose-sm max-w-none text-sm leading-relaxed max-h-72 overflow-y-auto rounded-xl p-4"
                                 :class="darkMode ? 'prose-invert bg-gray-800 text-gray-300' : 'bg-gray-50 text-gray-700'">
                                {!! $t->transcript !!}
                            </div>
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
                    <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.no_translation') }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Episode Info — all fields -->
        <div class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.episode_information') }}
            </h3>
            <dl class="space-y-3">
                @if($episode->program)
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="tv" class="w-3.5 h-3.5"></i>{{ __('admin.program') }}
                    </dt>
                    <dd class="text-sm font-medium text-end" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <a href="{{ route('admin.media-programs.show', $episode->program_id) }}"
                           class="text-[#0056b3] hover:underline" :class="darkMode ? '!text-[#64CCC5]' : ''">
                            {{ $episode->program->getTranslation('title', 'ar') ?? $episode->program->slug }}
                        </a>
                    </dd>
                </div>
                @endif
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i>{{ __('admin.season_number') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->season_number }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="hash" class="w-3.5 h-3.5"></i>{{ __('admin.episode_number') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->episode_number }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="link" class="w-3.5 h-3.5"></i>{{ __('admin.slug') }}
                    </dt>
                    <dd class="text-sm font-mono text-end break-all" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $episode->slug }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="video" class="w-3.5 h-3.5"></i>{{ __('admin.video_url') }}
                    </dt>
                    <dd class="text-sm text-end break-all max-w-[180px]" dir="ltr">
                        @if($episode->video_url)
                        <a href="{{ $episode->video_url }}" target="_blank" rel="noopener"
                           class="text-[#0056b3] hover:underline" :class="darkMode ? '!text-[#64CCC5]' : ''">
                            {{ \Illuminate\Support\Str::limit($episode->video_url, 36) }}
                        </a>
                        @else
                        <span :class="darkMode ? 'text-gray-500' : 'text-gray-400'">—</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>{{ __('admin.duration') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->duration_formatted ?: '—' }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="eye" class="w-3.5 h-3.5"></i>{{ __('admin.views') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($episode->views_count) }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>{{ __('admin.publish_status') }}
                    </dt>
                    <dd class="text-end">
                        @switch($episode->publishStatus())
                            @case('published')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('admin.published') }}</span>
                                @break
                            @case('scheduled')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    {{ __('admin.scheduled') }} • {{ $episode->publish_at?->format('Y-m-d H:i') }}
                                </span>
                                @break
                            @default
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">{{ __('admin.draft') }}</span>
                        @endswitch
                    </dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="star" class="w-3.5 h-3.5"></i>{{ __('admin.featured_only') }}
                    </dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $episode->is_featured ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $episode->is_featured ? __('admin.yes') : __('admin.no') }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="calendar-plus" class="w-3.5 h-3.5"></i>{{ __('admin.created_at') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->created_at->format('Y-m-d H:i') }}</dd>
                </div>
                <div class="flex justify-between items-start gap-3">
                    <dt class="text-sm flex items-center gap-1.5" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <i data-lucide="calendar-clock" class="w-3.5 h-3.5"></i>{{ __('admin.updated_at') }}
                    </dt>
                    <dd class="text-sm font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $episode->updated_at->format('Y-m-d H:i') }}</dd>
                </div>
            </dl>
        </div>

        <!-- Actions -->
        <div class="space-y-3 animate-fadeInUp">
            @if($episode->program)
            <a href="{{ route('admin.media-programs.show', $episode->program_id) }}"
               class="w-full py-2.5 rounded-xl font-semibold border transition-colors flex items-center justify-center gap-2"
               :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                <i data-lucide="tv" class="w-4 h-4"></i>
                {{ __('admin.view_program') }}
            </a>
            @endif
            <form method="POST" action="{{ route('admin.media-episodes.destroy', $episode->id) }}"
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
    </div>
</div>
@endsection
