@extends('layouts.public')

@section('title', __('topics.index_title'))
@section('meta_description', __('topics.index_description'))

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ __('topics.index_title') }}",
    "description": "{{ __('topics.index_description') }}",
    "url": "{{ route('public.topics') }}",
    "inLanguage": "{{ app()->getLocale() }}"
}
</script>
@endpush

@section('content')

@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

{{-- Breadcrumb --}}
<section class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-[#16213e]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <x-site.breadcrumb :items="[['label' => __('topics.breadcrumb')]]" />
    </div>
</section>

{{-- Hero --}}
<section class="relative py-14 sm:py-20 overflow-hidden bg-gradient-to-br from-blue-50 via-green-50 to-cyan-50 dark:from-[#1a1a2e] dark:via-[#16213e] dark:to-[#0f3460]">
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute top-16 -end-20 w-64 h-64 bg-[#0056b3] opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -start-20 w-64 h-64 bg-[#218838] opacity-10 rounded-full blur-3xl"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-3xl mx-auto text-center">
            <div class="inline-flex items-center gap-3 glass px-5 py-2.5 rounded-full mb-6">
                <span class="w-9 h-9 bg-gradient-to-br from-[#0056b3] to-[#218838] rounded-full flex items-center justify-center">
                    <i data-lucide="bookmark" class="w-5 h-5 text-white"></i>
                </span>
                <span class="text-sm font-bold gradient-text">{{ __('topics.index_title') }}</span>
            </div>

            <h1 class="text-4xl sm:text-5xl md:text-6xl font-black mb-5 text-gray-900 dark:text-white">
                {{ __('topics.index_title') }}
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-300 leading-relaxed">
                {{ __('topics.index_subtitle') }}
            </p>
            @if ($totalTopics > 0)
                <p class="mt-4 text-sm font-semibold text-[#0056b3] dark:text-[#64CCC5]">
                    {{ $totalTopics }} {{ __('topics.topics_count') }}
                </p>
            @endif
        </div>
    </div>
</section>

{{-- Topics grid --}}
<main class="py-12 bg-[#F8F9FA] dark:bg-[#1a1a2e]">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        @if (count($topics) === 0)
            <x-site.empty-state
                icon="bookmark"
                :title="__('topics.empty_topics_title')"
                :message="__('topics.empty_topics_message')" />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($topics as $topic)
                    <a href="{{ $topic['url'] }}"
                       class="card-premium group flex flex-col p-6 rounded-2xl bg-white dark:bg-[#16213e] border border-gray-100 dark:border-gray-800 transition-all hover:-translate-y-1 hover:shadow-xl">
                        <div class="flex items-start justify-between mb-4">
                            <span class="w-14 h-14 rounded-2xl flex items-center justify-center text-white shrink-0 bg-gradient-to-br {{ $topic['color'] ?: 'from-[#0056b3] to-[#218838]' }}">
                                <i data-lucide="{{ $topic['icon'] ?: 'bookmark' }}" class="w-7 h-7"></i>
                            </span>
                            @if ($topic['isFeatured'])
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400">
                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
                                </span>
                            @endif
                        </div>

                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-1">
                            {{ $topic['title'] }}
                        </h2>
                        @if (! empty($topic['description']))
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-5 line-clamp-2 flex-1" style="word-break: break-word;">
                                {{ $topic['description'] }}
                            </p>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        {{-- Real per-topic counts --}}
                        <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-gray-100 dark:border-gray-800 text-xs font-semibold">
                            <span class="inline-flex items-center gap-1.5 text-[#0056b3] dark:text-[#64CCC5]">
                                <i data-lucide="book-open" class="w-4 h-4"></i>
                                {{ $topic['versesCount'] }} {{ __('topics.stat_verses') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[#218838]">
                                <i data-lucide="scroll-text" class="w-4 h-4"></i>
                                {{ $topic['hadithsCount'] }} {{ __('topics.stat_hadiths') }}
                            </span>
                            @if ($topic['articlesCount'] > 0)
                                <span class="inline-flex items-center gap-1.5 text-purple-500">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                    {{ $topic['articlesCount'] }} {{ __('topics.stat_articles') }}
                                </span>
                            @endif
                        </div>

                        <span class="mt-5 inline-flex items-center gap-2 text-sm font-bold text-[#0056b3] group-hover:text-[#218838] transition-colors">
                            {{ __('topics.explore') }}
                            <i data-lucide="arrow-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4 transition-transform group-hover:{{ $isRtl ? '-translate-x-1' : 'translate-x-1' }}"></i>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</main>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endpush
