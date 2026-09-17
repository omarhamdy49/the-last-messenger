@extends('layouts.admin')

@php
    $enabledLocales = $article->locales->whereIn('locale', enabled_content_locales());
    $defaultLocale = $enabledLocales->firstWhere('locale', 'ar')?->locale
        ?? $enabledLocales->first()?->locale
        ?? 'ar';
    $primaryLocale = $enabledLocales->firstWhere('locale', $defaultLocale) ?? $enabledLocales->first();
    $statusValue = $article->status instanceof \BackedEnum ? $article->status->value : $article->status;
    $isPublished = in_array((string) $statusValue, ['published', 'active', '1', 'true'], true);
@endphp

@push('styles')
<style>
    /* Editorial reading surface — the Tailwind CDN build ships no typography plugin,
       so the article body HTML is styled explicitly here for both themes. */
    .article-prose { font-size: 1.0625rem; line-height: 1.9; }
    .article-prose > * + * { margin-top: 1.2em; }
    .article-prose p { margin: 0 0 1.15em; }
    .article-prose h2 { font-size: 1.6rem; font-weight: 700; line-height: 1.3; margin: 1.7em 0 .55em; letter-spacing: -0.01em; }
    .article-prose h3 { font-size: 1.3rem; font-weight: 700; line-height: 1.35; margin: 1.5em 0 .5em; }
    .article-prose h4 { font-size: 1.1rem; font-weight: 600; margin: 1.3em 0 .45em; }
    .article-prose a { color: #0056b3; text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 3px; }
    .dark .article-prose a { color: #64CCC5; }
    .article-prose ul, .article-prose ol { padding-inline-start: 1.4rem; margin: 1.1em 0; }
    .article-prose ul { list-style: disc; }
    .article-prose ol { list-style: decimal; }
    .article-prose li { margin: .45em 0; }
    .article-prose li::marker { color: #64CCC5; }
    .article-prose blockquote {
        border-inline-start: 3px solid #64CCC5;
        padding-inline-start: 1.1rem;
        margin: 1.6em 0;
        font-size: 1.15rem;
        line-height: 1.7;
        font-style: italic;
        opacity: .92;
    }
    .article-prose img { border-radius: .9rem; margin: 1.6em 0; max-width: 100%; height: auto; }
    .article-prose hr { border: 0; border-top: 1px solid currentColor; opacity: .12; margin: 2em 0; }
    .article-prose code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .9em; padding: .12em .4em; border-radius: .35rem;
        background: rgba(100, 204, 197, 0.14);
    }
    .article-prose table { width: 100%; border-collapse: collapse; margin: 1.4em 0; font-size: .95rem; }
    .article-prose th, .article-prose td { padding: .6rem .8rem; border: 1px solid rgba(128,128,128,.25); text-align: start; }

    /* Editorial opening: drop-style emphasis on the lead's first letter. */
    .article-lead::first-letter { font-size: 1.06em; font-weight: 700; }

    /* Staggered entrance collapses to instant under reduced-motion. */
    @media (prefers-reduced-motion: reduce) {
        .reveal { animation: none !important; opacity: 1 !important; transform: none !important; }
    }
</style>
@endpush

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
    <div class="min-w-0">
        <nav class="flex items-center gap-2 text-sm mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
            <a href="{{ route('admin.articles.index') }}" class="hover:text-accent transition-colors font-medium">المقالات</a>
            <i data-lucide="chevron-left" class="w-3.5 h-3.5 opacity-60 rtl:hidden"></i>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 opacity-60 ltr:hidden"></i>
            <span class="truncate max-w-[16rem]">{{ $primaryLocale?->title ?? 'Article' }}</span>
        </nav>

        <h1 class="text-2xl sm:text-3xl font-bold gradient-text leading-tight tracking-tight">
            {{ $primaryLocale?->title ?? 'Article' }}
        </h1>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $isPublished ? 'bg-emerald-500/15 text-emerald-500' : 'bg-amber-500/15 text-amber-500' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $isPublished ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $isPublished ? 'منشور' : 'مسودة' }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="user" class="w-4 h-4 opacity-70"></i>
                {{ $article->writer?->name ?? '—' }}
            </span>
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="eye" class="w-4 h-4 opacity-70"></i>
                {{ number_format($article->views) }}
            </span>
            @if($article->read_time)
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="clock" class="w-4 h-4 opacity-70"></i>
                {{ $article->read_time }} دقيقة
            </span>
            @endif
        </div>
    </div>

    <div class="flex items-center gap-2.5 flex-wrap shrink-0">
        @if($primaryLocale)
        <a href="{{ route('admin.articles.preview', ['article' => $article->id, 'locale' => $primaryLocale->locale]) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:-translate-y-0.5 active:translate-y-0 flex items-center gap-2 border"
           :class="darkMode ? 'border-gray-700 bg-gray-800/60 text-gray-100 hover:bg-gray-700' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
            <i data-lucide="eye" class="w-5 h-5"></i>
            <span>معاينة</span>
        </a>
        @endif

        <a href="{{ route('admin.articles.edit', $article) }}"
           class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:-translate-y-0.5 active:translate-y-0 flex items-center gap-2 shadow-soft bg-gradient-primary text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>

        <a href="{{ route('admin.articles.index') }}"
           class="p-2.5 rounded-xl transition-all hover:-translate-y-0.5 active:translate-y-0 border"
           title="رجوع"
           :class="darkMode ? 'border-gray-700 bg-gray-800/60 text-gray-300 hover:bg-gray-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5 rtl:block ltr:hidden"></i>
            <i data-lucide="arrow-left" class="w-5 h-5 ltr:block rtl:hidden"></i>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 xl:gap-8" x-data="{ activeLocale: '{{ $defaultLocale }}' }">

    {{-- ───────────────────────── Reading column ───────────────────────── --}}
    <article class="lg:col-span-8 reveal animate-fadeInUp">
        <div class="rounded-3xl border overflow-hidden shadow-soft"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">

            {{-- Cover with the title set as an overlaid editorial masthead --}}
            @if($article->thumb)
            <div class="relative aspect-[16/9]">
                <img src="{{ asset('storage/' . $article->thumb) }}"
                     alt="{{ $primaryLocale?->title ?? 'Article thumbnail' }}"
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent"></div>

                <div class="absolute top-4 start-4 flex flex-wrap items-center gap-2">
                    @if($article->is_featured)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500/90 text-white backdrop-blur-sm">
                        <i data-lucide="star" class="w-3.5 h-3.5"></i> مميز
                    </span>
                    @endif
                    @if($article->is_pinned)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-sky-500/90 text-white backdrop-blur-sm">
                        <i data-lucide="pin" class="w-3.5 h-3.5"></i> مثبت
                    </span>
                    @endif
                </div>

                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                    @foreach($enabledLocales as $locale)
                    <h2 x-show="activeLocale === '{{ $locale->locale }}'" x-cloak
                        dir="{{ in_array($locale->locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                        class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white leading-tight tracking-tight">
                        {{ $locale->title }}
                    </h2>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="p-5 sm:p-7 lg:p-9">

                {{-- Language switcher as a segmented control --}}
                @if($enabledLocales->count() > 1)
                <div class="inline-flex p-1 rounded-xl mb-7 gap-1"
                     :class="darkMode ? 'bg-gray-800/70' : 'bg-gray-100'">
                    @foreach($enabledLocales as $locale)
                    <button type="button"
                            @click="activeLocale = '{{ $locale->locale }}'"
                            class="px-3.5 py-1.5 rounded-lg text-sm font-semibold transition-all"
                            :class="activeLocale === '{{ $locale->locale }}'
                                ? 'bg-gradient-primary text-white shadow-soft'
                                : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900')">
                        {{ strtoupper($locale->locale) }}
                    </button>
                    @endforeach
                </div>
                @endif

                @forelse($enabledLocales as $locale)
                <div x-show="activeLocale === '{{ $locale->locale }}'" x-cloak
                     dir="{{ in_array($locale->locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">

                    @php
                        // content['body'] is a builder structure: an array of {type, content}
                        // blocks. Flatten the HTML of each block; tolerate a legacy plain-string body.
                        $rawBody = $locale->content['body'] ?? null;
                        if (is_array($rawBody)) {
                            $bodyHtml = collect($rawBody)->map(function ($block) {
                                if (is_string($block)) {
                                    return $block;
                                }
                                $content = is_array($block) ? ($block['content'] ?? '') : '';
                                return is_string($content) ? $content : '';
                            })->implode("\n");
                        } else {
                            $bodyHtml = is_string($rawBody) ? $rawBody : '';
                        }
                        $hasBody = trim(strip_tags($bodyHtml)) !== '' || str_contains($bodyHtml, '<img');
                    @endphp

                    {{-- No cover image? render the title here as the masthead --}}
                    @unless($article->thumb)
                    <h2 class="text-3xl lg:text-4xl font-bold mb-5 leading-tight tracking-tight"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $locale->title }}
                    </h2>
                    @endunless

                    {{-- Lead / excerpt --}}
                    @if($locale->excerpt)
                    <p class="article-lead text-lg sm:text-xl leading-relaxed font-medium mb-7"
                       :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ $locale->excerpt }}
                    </p>
                    @endif

                    {{-- Optional short description, set off as a pull aside --}}
                    @if($locale->description)
                    <div class="border-s-2 border-accent ps-4 mb-8 text-base leading-relaxed"
                         :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        {{ $locale->description }}
                    </div>
                    @endif

                    {{-- Body --}}
                    @if($hasBody)
                    <div class="article-prose" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        {!! $bodyHtml !!}
                    </div>
                    @else
                    <div class="flex flex-col items-center justify-center text-center py-14 rounded-2xl border border-dashed"
                         :class="darkMode ? 'border-gray-700 text-gray-500' : 'border-gray-200 text-gray-400'">
                        <i data-lucide="file-text" class="w-9 h-9 mb-3 opacity-60"></i>
                        <p class="font-medium">لا يوجد محتوى لهذه اللغة بعد</p>
                        <a href="{{ route('admin.articles.edit', $article) }}" class="text-sm text-accent hover:underline mt-2">إضافة المحتوى</a>
                    </div>
                    @endif

                    {{-- Publication details — one bordered panel, hairline-separated rows --}}
                    <div class="mt-10 rounded-2xl border divide-y"
                         :class="darkMode ? 'border-gray-800 divide-gray-800' : 'border-gray-200 divide-gray-100'">
                        <div class="px-5 py-3.5 flex items-center justify-between gap-4">
                            <span class="text-sm shrink-0" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">الرابط (Slug)</span>
                            <span class="font-mono text-sm truncate" :class="darkMode ? 'text-gray-200' : 'text-gray-800'" dir="ltr">{{ $locale->slug }}</span>
                        </div>
                        <div class="px-5 py-3.5 flex items-center justify-between gap-4">
                            <span class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">حالة الترجمة</span>
                            <span class="inline-flex items-center gap-1.5 text-sm font-semibold {{ $locale->is_ready ? 'text-emerald-500' : 'text-amber-500' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $locale->is_ready ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                {{ $locale->is_ready ? 'جاهز' : 'مسودة' }}
                            </span>
                        </div>
                        <div class="px-5 py-3.5">
                            <p class="text-sm mb-1.5" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">معاينة محرّكات البحث (SEO)</p>
                            <p class="font-semibold leading-snug" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">
                                {{ $locale->seo_title ?? $locale->title }}
                            </p>
                            <p class="text-sm mt-1 leading-relaxed" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                {{ $locale->seo_description ?? \Illuminate\Support\Str::limit(trim(strip_tags($bodyHtml)), 160) }}
                            </p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center text-center py-16"
                     :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    <i data-lucide="languages" class="w-10 h-10 mb-3 opacity-60"></i>
                    <p class="font-medium">لا توجد ترجمات لهذا المقال</p>
                </div>
                @endforelse
            </div>
        </div>
    </article>

    {{-- ───────────────────────── Sidebar ───────────────────────── --}}
    <aside class="lg:col-span-4 space-y-5 reveal animate-fadeInUp" style="animation-delay: 90ms">

        {{-- Article info --}}
        <div class="rounded-2xl border shadow-soft overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="px-5 py-4 border-b flex items-center gap-2.5" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                <h3 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">معلومات المقال</h3>
            </div>
            <div class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                <div class="px-5 py-3.5 flex items-center justify-between gap-4">
                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">الكاتب</span>
                    <span class="font-semibold text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $article->writer?->name ?? '—' }}</span>
                </div>
                <div class="px-5 py-3.5 flex items-center justify-between gap-4">
                    <span class="text-sm shrink-0" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">التصنيف الرئيسي</span>
                    <span class="font-semibold text-sm text-end" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $article->category?->getNameInLanguage('ar') ?? '—' }}</span>
                </div>
                <div class="px-5 py-3.5 flex items-center justify-between gap-4">
                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">المشاهدات</span>
                    <span class="font-bold text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ number_format($article->views) }}</span>
                </div>
                <div class="px-5 py-3.5 flex items-center justify-between gap-4">
                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">التاريخ الهجري</span>
                    <span class="font-semibold text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $article->hijri_date ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Taxonomy --}}
        <div class="rounded-2xl border shadow-soft p-5"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="font-bold mb-4 flex items-center gap-2.5" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="folder-tree" class="w-5 h-5 text-accent"></i> التصنيفات والوسوم
            </h3>

            <p class="text-xs font-semibold uppercase tracking-wide mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">التصنيفات</p>
            <div class="flex flex-wrap gap-2 mb-5">
                @forelse($article->categories as $category)
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium"
                      :class="darkMode ? 'bg-gray-800 text-gray-200' : 'bg-gray-100 text-gray-700'">
                    {{ $category->getNameInLanguage('ar') ?? $category->slug }}
                </span>
                @empty
                <span class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">لا توجد تصنيفات مرتبطة</span>
                @endforelse
            </div>

            <p class="text-xs font-semibold uppercase tracking-wide mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">الوسوم</p>
            <div class="flex flex-wrap gap-2">
                @forelse($article->tags as $tag)
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium text-accent bg-accent/10">
                    <i data-lucide="hash" class="w-3 h-3"></i>{{ $tag->getTranslation('name', 'ar') }}
                </span>
                @empty
                <span class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">لا توجد وسوم مرتبطة</span>
                @endforelse
            </div>
        </div>

        {{-- Media --}}
        <div class="rounded-2xl border shadow-soft p-5"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="font-bold mb-4 flex items-center gap-2.5" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="image-plus" class="w-5 h-5 text-accent"></i> الوسائط المرفقة
            </h3>
            <div class="space-y-2.5">
                @forelse($article->mediaItems as $media)
                <div class="p-2.5 rounded-xl flex items-center gap-3 transition-colors"
                     :class="darkMode ? 'bg-gray-800/50 hover:bg-gray-800' : 'bg-gray-50 hover:bg-gray-100'">
                    <div class="w-12 h-12 rounded-lg overflow-hidden flex items-center justify-center bg-gradient-primary shrink-0">
                        @if($media->thumbnail_url || str_starts_with((string) $media->mime_type, 'image/'))
                        <img src="{{ $media->thumbnail_url ?: $media->url }}" alt="{{ $media->filename }}" class="w-full h-full object-cover">
                        @else
                        <i data-lucide="file" class="w-5 h-5 text-white"></i>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-sm truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $media->filename }}</p>
                        <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $media->mime_type }}</p>
                    </div>
                    <a href="{{ route('admin.media-library.show', $media) }}"
                       class="p-2 rounded-lg transition-colors shrink-0"
                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-200 text-gray-500 hover:text-gray-900'">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                    </a>
                </div>
                @empty
                <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">لا توجد وسائط مرتبطة بهذا المقال</p>
                @endforelse
            </div>
        </div>

    </aside>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
