@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.articles.index') }}" class="hover:text-accent transition-colors">المقالات</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <a href="{{ route('admin.articles.show', $article->id) }}" class="hover:text-accent transition-colors">{{ $articleLocale->title }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>معاينة</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="eye" class="w-7 h-7 text-white"></i>
            </div>
            معاينة المقال
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <!-- Language Switcher -->
        <div class="flex items-center gap-1 p-1 rounded-xl border"
             :class="darkMode ? 'border-gray-700 bg-gray-800' : 'border-gray-300 bg-white'">
            @foreach($article->locales as $locale)
            <a href="{{ route('admin.articles.preview', ['article' => $article->id, 'locale' => $locale->locale]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all
                      {{ $locale->locale === $articleLocale->locale ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : '' }}"
               :class="{{ $locale->locale !== $articleLocale->locale ? "darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'" : "''" }}">
                {{ strtoupper($locale->locale) }}
            </a>
            @endforeach
        </div>

        <a href="{{ route('admin.articles.edit', $article->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.articles.show', $article->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Preview Banner -->
    <div class="rounded-2xl border p-4 flex items-center gap-3"
         :class="darkMode ? 'bg-amber-900/20 border-amber-800/50' : 'bg-amber-50 border-amber-200'">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-500 flex-shrink-0"></i>
        <p class="text-sm" :class="darkMode ? 'text-amber-400' : 'text-amber-700'">
            هذه معاينة للمقال كما سيظهر للقراء. المقال {{ $articleLocale->is_ready ? 'جاهز للنشر' : 'غير جاهز للنشر بعد' }}.
        </p>
    </div>

    <!-- Article Preview Card -->
    <article class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
             dir="{{ in_array($articleLocale->locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">

        <!-- Featured Image -->
        @if($article->thumb)
        <div class="relative aspect-video">
            <img src="{{ asset('storage/' . $article->thumb) }}"
                 alt="{{ $articleLocale->title }}"
                 class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

            <!-- Category Badge -->
            @if($article->category)
            <div class="absolute top-4 start-4">
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">
                    {{ $article->category->getTranslation('name', $articleLocale->locale) }}
                </span>
            </div>
            @endif

            <!-- Meta Badges -->
            <div class="absolute bottom-4 start-4 flex items-center gap-3">
                @if($article->is_featured)
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500 text-white">
                    <i data-lucide="star" class="w-3.5 h-3.5"></i>
                    مميز
                </span>
                @endif
                @if($article->read_time)
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-black/40 text-white backdrop-blur-sm">
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    {{ $article->read_time }} دقيقة للقراءة
                </span>
                @endif
            </div>
        </div>
        @endif

        <!-- Content -->
        <div class="p-8">
            <!-- Title -->
            <h1 class="text-3xl font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ $articleLocale->title }}
            </h1>

            <!-- Meta Info -->
            <div class="flex flex-wrap items-center gap-4 mb-6 pb-6 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                @if($article->writer)
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <span class="text-white text-xs font-bold">{{ mb_substr($article->writer->name, 0, 1) }}</span>
                    </div>
                    <span class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ $article->writer->name }}
                    </span>
                </div>
                @endif
                <span class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    <i data-lucide="calendar" class="w-4 h-4 inline-block me-1"></i>
                    {{ $article->created_at?->format('Y/m/d') }}
                </span>
                <span class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    <i data-lucide="eye" class="w-4 h-4 inline-block me-1"></i>
                    {{ number_format($article->views ?? 0) }} مشاهدة
                </span>
            </div>

            <!-- Excerpt -->
            @if($articleLocale->excerpt)
            <div class="p-4 mb-6 rounded-xl border-s-4 border-accent"
                 :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ $articleLocale->excerpt }}
                </p>
            </div>
            @endif

            <!-- Body Content -->
            <div class="prose max-w-none" :class="darkMode ? 'prose-invert' : ''">
                {!! $articleLocale->content['body'] ?? '' !!}
            </div>

            <!-- Tags -->
            @if($article->tags && $article->tags->count() > 0)
            <div class="mt-8 pt-6 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div class="flex flex-wrap items-center gap-2">
                    <i data-lucide="tag" class="w-4 h-4" :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                    @foreach($article->tags as $tag)
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        {{ $tag->getTranslation('name', $articleLocale->locale) }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </article>

    <!-- SEO Preview -->
    <div class="rounded-2xl border p-6"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="search" class="w-5 h-5 text-accent"></i>
            معاينة محركات البحث (SEO)
        </h2>

        <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
            <p class="text-lg font-medium text-blue-600 mb-1">
                {{ $articleLocale->seo_title ?? $articleLocale->title }}
            </p>
            <p class="text-sm text-green-700 mb-2">
                {{ url('/') }}/articles/{{ $articleLocale->slug }}
            </p>
            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ $articleLocale->seo_description ?? Str::limit(strip_tags($articleLocale->content['body'] ?? ''), 160) }}
            </p>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
