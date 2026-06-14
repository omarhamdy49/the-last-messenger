@extends('layouts.admin')

@php
    $isYoutube = $channel->streaming_url && \Illuminate\Support\Str::contains($channel->streaming_url, ['youtube.com/embed', 'youtu']);
@endphp

@section('page-header')
<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.live-channels.index') }}"
           class="p-2 rounded-xl transition-colors"
           :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold gradient-text">{{ $channel->trans('title') ?: $channel->slug }}</h1>
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
        {{-- Preview --}}
        <section class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="relative aspect-video bg-black">
                @if($isYoutube)
                    <iframe src="{{ $channel->streaming_url }}" class="absolute inset-0 w-full h-full" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                @elseif($channel->thumbnail_url)
                    <img src="{{ $channel->thumbnail_url }}" alt="" class="absolute inset-0 w-full h-full object-cover">
                @else
                    <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                        <i data-lucide="radio" class="w-12 h-12 text-white/70"></i>
                    </div>
                @endif
                @if($channel->status->value === 'live')
                    <div class="absolute top-3 end-3 flex items-center gap-1.5 px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-full">
                        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>{{ __('media.public.live_now') }}
                    </div>
                @endif
            </div>
        </section>

        {{-- Translations --}}
        <section class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.translations') }}</h3>
            <div class="space-y-4">
                @foreach($channel->translations->sortBy('locale') as $t)
                <div class="pb-4 border-b last:border-0" :class="darkMode ? 'border-gray-800' : 'border-gray-100'" dir="{{ in_array($t->locale, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold mb-2 bg-[#0056b3]/10 text-[#0056b3]">{{ strtoupper($t->locale) }}</span>
                    <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $t->title }}</p>
                    @if($t->description)<p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $t->description }}</p>@endif
                </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Meta --}}
    <div class="space-y-6">
        <section class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-semibold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.details') }}</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.status') }}</dt><dd class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('media.public.live_status_'.$channel->status->value) }}</dd></div>
                <div class="flex justify-between"><dt :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.published') }}</dt><dd class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $channel->is_published ? __('admin.yes') : __('admin.no') }}</dd></div>
                <div class="flex justify-between"><dt :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.display_order') }}</dt><dd class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $channel->display_order }}</dd></div>
                <div class="flex justify-between"><dt :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('media.public.views') }}</dt><dd class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($channel->views_count) }}</dd></div>
                <div><dt class="mb-1" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.live_channel_streaming_url') }}</dt><dd class="font-mono text-xs break-all" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $channel->streaming_url ?: '—' }}</dd></div>
            </dl>
        </section>
    </div>
</div>
@endsection
