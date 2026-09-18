@extends('layouts.admin')

@php
    $isLive = $channel->status->value === 'live';
    $statusBadge = [
        'live' => 'bg-red-500/15 text-red-500',
        'scheduled' => 'bg-amber-500/15 text-amber-500',
        'offline' => 'bg-gray-500/15 text-gray-400',
    ][$channel->status->value] ?? 'bg-gray-500/15 text-gray-400';
@endphp

@section('page-header')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.live-channels.index') }}"
           class="p-2 rounded-xl transition-colors"
           :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold gradient-text">{{ $channel->trans('title') ?: $channel->slug }}</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $statusBadge }}">
                    @if($isLive)<span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>@endif
                    {{ __('media.public.live_status_'.$channel->status->value) }}
                </span>
                <span class="text-xs font-mono" dir="ltr" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $channel->slug }}</span>
            </div>
        </div>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('public.media.live', $channel->slug) }}" target="_blank"
           class="px-4 py-2 rounded-xl font-medium transition-colors flex items-center gap-2"
           :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
            <i data-lucide="external-link" class="w-4 h-4"></i>{{ __('admin.view_public') }}
        </a>
        <a href="{{ route('admin.live-channels.edit', $channel->id) }}"
           class="px-5 py-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold shadow-lg flex items-center gap-2">
            <i data-lucide="pencil" class="w-4 h-4"></i>{{ __('admin.edit') }}
        </a>
    </div>
</div>
@endsection

@section('content')
@if(session('success'))
<div class="mb-6 p-4 rounded-xl border-s-4 border-green-500" :class="darkMode ? 'bg-green-900/20 text-green-400' : 'bg-green-50 text-green-700'">
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        {{-- Player preview. Native elements only: the public wrapped player is
             built on the sx design tokens, which the admin bundle does not ship.
             HLS/DASH need a JS library to play outside Safari, so those send the
             editor to the channel page instead of faking a broken box. --}}
        @php $previewType = $channel->stream_source ? $channel->resolved_stream_type : null; @endphp
        <section class="rounded-2xl border overflow-hidden shadow-lg animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="relative aspect-video bg-black">
                @if($previewType === \App\Domain\Media\Enums\StreamType::Audio)
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6">
                        <i data-lucide="radio" class="w-10 h-10 text-white/70"></i>
                        <audio controls preload="none" src="{{ $channel->streaming_url }}" class="w-full max-w-md"></audio>
                    </div>
                @elseif($previewType === \App\Domain\Media\Enums\StreamType::File)
                    <video controls preload="none" src="{{ $channel->streaming_url }}"
                           @if($channel->thumbnail_url) poster="{{ $channel->thumbnail_url }}" @endif
                           class="absolute inset-0 w-full h-full object-contain"></video>
                @elseif($previewType && ! in_array($previewType, [\App\Domain\Media\Enums\StreamType::YouTube, \App\Domain\Media\Enums\StreamType::Iframe], true))
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center">
                        @if($channel->thumbnail_url)
                            <img src="{{ $channel->thumbnail_url }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-40">
                        @endif
                        <i data-lucide="monitor-play" class="relative w-10 h-10 text-white/70"></i>
                        <p class="relative text-sm text-white/80 max-w-sm">{{ __('admin.live_channel_preview_unavailable') }}</p>
                        <a href="{{ route('public.media.live', $channel->slug) }}" target="_blank" rel="noopener"
                           class="relative px-4 py-1.5 rounded-full text-xs font-bold bg-white/15 text-white hover:bg-white/25">
                            {{ __('admin.view') }}
                        </a>
                    </div>
                @elseif($channel->embed_url)
                    <iframe src="{{ $channel->embed_url }}" class="absolute inset-0 w-full h-full" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                @elseif($channel->thumbnail_url)
                    <img src="{{ $channel->thumbnail_url }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                @else
                    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                        <i data-lucide="radio" class="w-12 h-12 text-white/70"></i>
                    </div>
                @endif
                @if($isLive)
                    <div class="absolute top-3 end-3 flex items-center gap-1.5 px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-full shadow-lg pointer-events-none">
                        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>{{ __('media.public.live_now') }}
                    </div>
                @endif
            </div>
        </section>

        {{-- Stat cards --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 animate-fadeInUp">
            @foreach ([
                ['icon' => 'eye', 'gradient' => 'from-[#0056b3] to-[#64CCC5]', 'label' => __('admin.views'), 'value' => number_format($channel->views_count)],
                ['icon' => 'radio', 'gradient' => 'from-red-500 to-rose-600', 'label' => __('admin.status'), 'value' => __('media.public.live_status_'.$channel->status->value)],
                ['icon' => $channel->is_published ? 'check-circle' : 'circle-off', 'gradient' => 'from-[#218838] to-emerald-500', 'label' => __('admin.published'), 'value' => $channel->is_published ? __('admin.yes') : __('admin.no')],
                ['icon' => 'arrow-up-down', 'gradient' => 'from-amber-500 to-orange-500', 'label' => __('admin.display_order'), 'value' => $channel->display_order],
            ] as $stat)
                <div class="rounded-2xl border p-4 flex items-center gap-3" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br {{ $stat['gradient'] }} flex items-center justify-center shadow-lg shrink-0">
                        <i data-lucide="{{ $stat['icon'] }}" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $stat['label'] }}</p>
                        <p class="text-lg font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $stat['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Translations --}}
        <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.translations') }}
            </h3>
            @if($channel->translations->whereIn('locale', enabled_content_locales())->isEmpty())
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">—</p>
            @else
                <div class="space-y-4">
                    @foreach($channel->translations->whereIn('locale', enabled_content_locales())->sortBy('locale') as $t)
                    <div class="pb-4 border-b last:border-0 last:pb-0" :class="darkMode ? 'border-gray-800' : 'border-gray-100'" dir="{{ in_array($t->locale, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-bold mb-2 bg-[#0056b3]/10 text-[#0056b3] dark:bg-[#64CCC5]/10 dark:text-[#64CCC5]">{{ strtoupper($t->locale) }}</span>
                        <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $t->title }}</p>
                        @if($t->description)<p class="text-sm mt-1 leading-relaxed" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $t->description }}</p>@endif
                    </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Streaming --}}
        <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="radio" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.live_channel_streaming') }}
            </h3>
            @if($channel->streaming_url)
                <div x-data="{ copied: false }" class="space-y-2">
                    <p class="font-mono text-xs break-all rounded-xl p-3" dir="ltr"
                       :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-50 text-gray-700'">{{ $channel->streaming_url }}</p>
                    <button type="button"
                            @click="navigator.clipboard.writeText(@js($channel->streaming_url)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                            class="w-full px-3 py-2 text-sm rounded-xl border font-medium transition-colors flex items-center justify-center gap-2"
                            :class="darkMode ? 'border-gray-700 hover:bg-gray-800 text-gray-300' : 'border-gray-300 hover:bg-gray-50 text-gray-700'">
                        <i data-lucide="copy" class="w-4 h-4"></i>
                        <span x-text="copied ? @js(__('admin.copied')) : @js(__('admin.copy'))"></span>
                    </button>
                </div>
            @else
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">—</p>
            @endif
        </section>

        {{-- Details --}}
        <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.details') }}
            </h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.slug') }}</dt>
                    <dd class="font-mono text-xs" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $channel->slug }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.created_at') }}</dt>
                    <dd class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $channel->created_at->translatedFormat('d M Y — H:i') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.updated_at') }}</dt>
                    <dd class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $channel->updated_at->translatedFormat('d M Y — H:i') }}</dd>
                </div>
            </dl>
        </section>

        {{-- Thumbnail --}}
        <section class="rounded-2xl border p-6 animate-fadeInUp" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="image" class="w-5 h-5 text-[#64CCC5]"></i>
                {{ __('admin.thumbnail') }}
            </h3>
            @if($channel->thumbnail_url)
                <img src="{{ $channel->thumbnail_url }}" alt="" class="w-full aspect-video object-cover rounded-xl">
            @else
                <div class="w-full aspect-video rounded-xl flex items-center justify-center" :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                    <i data-lucide="image-off" class="w-8 h-8 text-gray-400"></i>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
