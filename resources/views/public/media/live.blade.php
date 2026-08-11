@extends('layouts.public')

@section('title', $channel['title'].' | '.config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($channel['description'] ?? '')), 160))

@section('content')
@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<div x-data="liveChannelPage()"
     class="min-h-screen font-['Cairo'] bg-gray-50 dark:bg-gray-950 transition-colors duration-300">

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">

        {{-- Breadcrumb --}}
        <nav class="flex mb-6 text-sm text-gray-600 dark:text-gray-400" aria-label="breadcrumb">
            <ol class="inline-flex items-center gap-2 flex-wrap">
                <li><a href="{{ route('public.media') }}" class="hover:text-[#0056b3] dark:hover:text-[#64CCC5] transition-colors">{{ __('media.public.page_title') }}</a></li>
                <li class="flex items-center gap-2">
                    <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-3.5 h-3.5"></i>
                    <a href="{{ route('public.media.live.index') }}" class="hover:text-[#0056b3] dark:hover:text-[#64CCC5] transition-colors">{{ __('media.public.live_channels') }}</a>
                </li>
                <li class="flex items-center gap-2">
                    <i data-lucide="{{ $isRtl ? 'chevron-left' : 'chevron-right' }}" class="w-3.5 h-3.5"></i>
                    <span class="font-medium text-gray-900 dark:text-white line-clamp-1">{{ $channel['title'] }}</span>
                </li>
            </ol>
        </nav>

        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
            {{-- ============================ MAIN ============================ --}}
            <div class="lg:col-span-2">
                {{-- Player --}}
                <div class="mb-6 rounded-2xl overflow-hidden shadow-2xl bg-gray-900">
                    <div class="relative aspect-video bg-black">
                        @if ($channel['embed_url'])
                            <iframe src="{{ $channel['embed_url'] }}"
                                    title="{{ $channel['title'] }}"
                                    class="absolute inset-0 w-full h-full"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen></iframe>
                        @else
                            {{-- No stream URL yet — graceful placeholder --}}
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gradient-to-br from-[#0056b3] via-[#1a6e57] to-[#218838] text-white">
                                <i data-lucide="radio" class="w-12 h-12 opacity-80"></i>
                                <p class="text-sm font-semibold">{{ __('media.public.live_status_offline') }}</p>
                            </div>
                        @endif

                        @if ($channel['is_live'])
                            <div class="absolute top-4 end-4 z-10">
                                <span class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-full shadow-lg">
                                    <span class="w-2.5 h-2.5 bg-white rounded-full animate-pulse"></span>
                                    <span class="font-bold text-sm">{{ __('media.public.live_now') }}</span>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Info --}}
                <div class="mb-6 p-6 rounded-2xl shadow-lg bg-white dark:bg-gray-900">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2 break-words">{{ $channel['title'] }}</h1>
                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                @if ($channel['is_live'])
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-red-600">
                                        <i data-lucide="radio" class="w-4 h-4"></i>{{ __('media.public.live_now') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 font-semibold">
                                        <i data-lucide="circle" class="w-4 h-4"></i>{{ __('media.public.live_status_'.$channel['status']) }}
                                    </span>
                                @endif
                                <span class="flex items-center gap-1">
                                    <i data-lucide="eye" class="w-4 h-4"></i>{{ number_format($channel['views_count']) }} {{ __('media.public.views') }}
                                </span>
                            </div>
                        </div>

                        <button type="button" @click="share()"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            <i data-lucide="share-2" class="w-4 h-4"></i>
                            <span x-text="shareCopied ? @js(__('media.public.link_copied')) : @js(__('media.public.share'))"></span>
                        </button>
                    </div>

                    @if ($channel['description'])
                        <p class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 leading-relaxed text-gray-700 dark:text-gray-300 break-words">{{ $channel['description'] }}</p>
                    @endif
                </div>
            </div>

            {{-- ========================== SIDEBAR ========================== --}}
            <aside class="lg:col-span-1 mt-8 lg:mt-0">
                <div class="lg:sticky lg:top-24">
                    <div class="rounded-2xl shadow-lg p-5 bg-white dark:bg-gray-900">
                        <h2 class="text-lg font-bold mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                            <i data-lucide="radio" class="w-5 h-5 text-[#0056b3] dark:text-[#64CCC5]"></i>
                            {{ __('media.public.related_channels') }}
                        </h2>

                        @if (empty($related))
                            <p class="text-sm text-gray-500 dark:text-gray-400 py-6 text-center">{{ __('media.public.no_live_channels') }}</p>
                        @else
                            <div class="space-y-3">
                                @foreach ($related as $item)
                                    <a href="{{ $item['url'] }}"
                                       class="group flex items-start gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <div class="relative flex-shrink-0 w-28 aspect-video rounded-lg overflow-hidden bg-gradient-to-br from-[#0056b3] via-[#1a6e57] to-[#218838]">
                                            @if ($item['thumbnail'])
                                                <img src="{{ $item['thumbnail'] }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 motion-reduce:transform-none transition-transform duration-500">
                                            @else
                                                <div class="absolute inset-0 flex items-center justify-center opacity-40"><i data-lucide="radio" class="w-6 h-6 text-white"></i></div>
                                            @endif
                                            @if ($item['is_live'])
                                                <span class="absolute top-1 start-1 inline-flex items-center gap-1 px-1.5 py-0.5 bg-red-600 text-white text-[10px] font-bold rounded-full">
                                                    <span class="w-1 h-1 bg-white rounded-full animate-pulse"></span>{{ __('media.public.live_now') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="font-bold text-sm text-gray-900 dark:text-white line-clamp-2 break-words group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors">{{ $item['title'] }}</h3>
                                            <p class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                <i data-lucide="eye" class="w-3 h-3"></i>{{ number_format($item['views_count']) }} {{ __('media.public.views') }}
                                            </p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function liveChannelPage() {
        return {
            shareCopied: false,
            async share() {
                const payload = { title: document.title, url: window.location.href };
                if (navigator.share) {
                    try { await navigator.share(payload); return; } catch (e) { /* dismissed */ }
                }
                try {
                    await navigator.clipboard.writeText(window.location.href);
                    this.shareCopied = true;
                    setTimeout(() => { this.shareCopied = false; }, 2000);
                } catch (e) { /* clipboard unavailable */ }
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') { lucide.createIcons(); }
    });
</script>
@endpush
@endsection
