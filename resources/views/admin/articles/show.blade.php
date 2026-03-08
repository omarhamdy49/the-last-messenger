@extends('layouts.admin')

@php
    $defaultLocale = $article->locales->firstWhere('locale', 'ar')?->locale
        ?? $article->locales->first()?->locale
        ?? 'ar';
    $primaryLocale = $article->locales->firstWhere('locale', $defaultLocale) ?? $article->locales->first();
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.articles.index') }}" class="hover:text-accent transition-colors">المقالات</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $primaryLocale?->title ?? 'Article' }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="file-text" class="w-7 h-7 text-white"></i>
            </div>
            {{ $primaryLocale?->title ?? 'Article' }}
        </h1>
    </div>

    <div class="flex items-center gap-3 flex-wrap">
        @if($primaryLocale)
        <a href="{{ route('admin.articles.preview', ['article' => $article->id, 'locale' => $primaryLocale->locale]) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="eye" class="w-5 h-5"></i>
            <span>معاينة</span>
        </a>
        @endif

        <a href="{{ route('admin.articles.edit', $article) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>

        <a href="{{ route('admin.articles.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{ activeLocale: '{{ $defaultLocale }}' }">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            @if($article->thumb)
            <div class="relative aspect-video">
                <img src="{{ asset('storage/' . $article->thumb) }}"
                     alt="{{ $primaryLocale?->title ?? 'Article thumbnail' }}"
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                <div class="absolute bottom-4 start-4 flex flex-wrap items-center gap-2">
                    @if($article->is_featured)
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500 text-white">
                        <i data-lucide="star" class="w-3.5 h-3.5"></i>
                        مميز
                    </span>
                    @endif
                    @if($article->is_pinned)
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-sky-500 text-white">
                        <i data-lucide="pin" class="w-3.5 h-3.5"></i>
                        مثبت
                    </span>
                    @endif
                    @if($article->read_time)
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-black/40 text-white backdrop-blur-sm">
                        <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                        {{ $article->read_time }} دقيقة
                    </span>
                    @endif
                </div>
            </div>
            @endif

            <div class="p-6">
                <div class="flex flex-wrap gap-2 border-b pb-4 mb-6" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach($article->locales as $locale)
                    <button type="button"
                            @click="activeLocale = '{{ $locale->locale }}'"
                            class="px-4 py-2 rounded-lg font-medium transition-all"
                            :class="activeLocale === '{{ $locale->locale }}'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                : (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                        {{ strtoupper($locale->locale) }}
                    </button>
                    @endforeach
                </div>

                @foreach($article->locales as $locale)
                <div x-show="activeLocale === '{{ $locale->locale }}'" x-cloak class="space-y-6" dir="{{ in_array($locale->locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                    <div>
                        <h2 class="text-3xl font-bold mb-3" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $locale->title }}
                        </h2>
                        @if($locale->excerpt)
                        <p class="text-lg leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $locale->excerpt }}
                        </p>
                        @endif
                    </div>

                    @if($locale->description)
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <h3 class="text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الوصف</h3>
                        <p class="leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $locale->description }}
                        </p>
                    </div>
                    @endif

                    <div class="prose max-w-none" :class="darkMode ? 'prose-invert' : ''">
                        {!! $locale->content['body'] ?? '' !!}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                            <p class="text-xs font-semibold mb-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">Slug</p>
                            <p class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $locale->slug }}</p>
                        </div>
                        <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                            <p class="text-xs font-semibold mb-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">الحالة</p>
                            <p class="text-sm font-semibold {{ $locale->is_ready ? 'text-green-500' : 'text-amber-500' }}">
                                {{ $locale->is_ready ? 'جاهز' : 'مسودة' }}
                            </p>
                        </div>
                        <div class="p-4 rounded-xl md:col-span-2" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                            <p class="text-xs font-semibold mb-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">SEO</p>
                            <p class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $locale->seo_title ?? $locale->title }}
                            </p>
                            <p class="text-sm mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $locale->seo_description ?? \Illuminate\Support\Str::limit(strip_tags($locale->content['body'] ?? ''), 160) }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات المقال
            </h3>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الكاتب</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $article->writer?->name ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">التصنيف الرئيسي</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $article->category?->getNameInLanguage('ar') ?? '-' }}
                    </span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">المشاهدات</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($article->views) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">التاريخ الهجري</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $article->hijri_date ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="folder-tree" class="w-5 h-5 text-accent"></i>
                العلاقات
            </h3>

            <div class="space-y-4">
                <div>
                    <p class="text-xs font-semibold mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">التصنيفات</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse($article->categories as $category)
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            {{ $category->getNameInLanguage('ar') ?? $category->slug }}
                        </span>
                        @empty
                        <span class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">لا توجد تصنيفات مرتبطة</span>
                        @endforelse
                    </div>
                </div>

                <div>
                    <p class="text-xs font-semibold mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">الوسوم</p>
                    <div class="flex flex-wrap gap-2">
                        @forelse($article->tags as $tag)
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium"
                              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                            {{ $tag->getTranslation('name', 'ar') }}
                        </span>
                        @empty
                        <span class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">لا توجد وسوم مرتبطة</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="image-plus" class="w-5 h-5 text-accent"></i>
                الوسائط المرفقة
            </h3>

            <div class="space-y-3">
                @forelse($article->mediaItems as $media)
                <div class="p-3 rounded-xl flex items-center gap-3" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <div class="w-14 h-14 rounded-lg overflow-hidden flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                        @if($media->thumbnail_url || str_starts_with((string) $media->mime_type, 'image/'))
                        <img src="{{ $media->thumbnail_url ?: $media->url }}" alt="{{ $media->filename }}" class="w-full h-full object-cover">
                        @else
                        <i data-lucide="file" class="w-6 h-6 text-white"></i>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $media->filename }}</p>
                        <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $media->mime_type }}</p>
                    </div>
                    <a href="{{ route('admin.media-library.show', $media) }}"
                       class="p-2 rounded-lg transition-colors"
                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-600 hover:text-gray-900'">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                    </a>
                </div>
                @empty
                <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">لا توجد وسائط مرتبطة بهذا المقال</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="calendar-range" class="w-5 h-5 text-accent"></i>
                أحداث السيرة المرتبطة
            </h3>

            <div class="space-y-3">
                @forelse($article->seerahEvents as $event)
                <a href="{{ route('admin.seerah-events.show', $event->id) }}"
                   class="block p-4 rounded-xl transition-colors"
                   :class="darkMode ? 'bg-gray-800/50 hover:bg-gray-800 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $event->getTranslation('title', 'ar') ?: $event->slug }}</p>
                            <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $event->slug }}</p>
                        </div>
                        <i data-lucide="arrow-up-left" class="w-4 h-4 text-accent"></i>
                    </div>
                </a>
                @empty
                <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">لا توجد أحداث سيرة مرتبطة بهذا المقال</p>
                @endforelse
            </div>
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
