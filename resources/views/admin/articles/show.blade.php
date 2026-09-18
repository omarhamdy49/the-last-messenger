@extends('layouts.admin')

@push('styles')
@include('partials.seerah-content-styles')
@endpush

@php
    /**
     * Admin preview of an article (also serves admin.articles.preview with ?locale=).
     * The body goes through ArticleLocale::getBody(), the same renderer the public reader uses,
     * so module cards (verse / hadith / sahabi / seerah) look like they will for visitors.
     *
     * @var \App\Models\Article $article
     * @var string|null $initialLocale
     */
    $languages = enabled_content_languages();
    $locales = $article->locales->whereIn('locale', array_keys($languages))->keyBy('locale');
    $firstLang = ($initialLocale ?? null) && $locales->has($initialLocale)
        ? $initialLocale
        : ($locales->has('ar') ? 'ar' : ($locales->keys()->first() ?? 'ar'));
    $dirFor = fn (string $lang): string => in_array($lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr';
    $sanitizer = app(\App\Domain\Shared\Services\RichTextSanitizer::class);

    $status = $article->status instanceof \BackedEnum ? $article->status->value : (string) $article->status;
    $published = $status === 'published';
    $cover = ($article->featured_image ?: $article->thumb) ? asset('storage/'.($article->featured_image ?: $article->thumb)) : null;
    $statistics = collect($article->statistics ?? [])->filter(fn ($row) => filled($row['name'] ?? null) || filled($row['value'] ?? null));
    $references = collect($article->references ?? [])->filter(fn ($row) => filled($row['name'] ?? null) || filled($row['url'] ?? null));
    $publicSlug = $locales->get('ar')?->slug ?? $locales->first()?->slug;
@endphp

@section('page-header')
<nav class="flex flex-wrap items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-slate-500'" aria-label="breadcrumb">
    <a href="{{ route('admin.articles.index') }}" class="hover:text-accent transition-colors">{{ __('articles.title') }}</a>
    <i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180" aria-hidden="true"></i>
    <span aria-current="page" class="truncate max-w-[24rem]">{{ $locales->get($firstLang)?->title ?? '—' }}</span>
</nav>
@endsection

@section('content')
<div class="wz-root pv-root" x-data="{ lang: @js($firstLang) }">

    {{-- ═══════ HERO ═══════ --}}
    <header class="pv-hero">
        <div class="pv-hero-media">
            @if($cover)
                <img src="{{ $cover }}" alt="" fetchpriority="high" onerror="this.remove()">
            @endif
            <div class="absolute inset-0 -z-10 bg-gradient-to-br from-[#0b3b73] via-[#0f2a4a] to-[#16552a]"></div>
        </div>
        <div class="pv-hero-body">
            <div class="flex flex-wrap items-center gap-2">
                <span class="pv-chip {{ $published ? 'is-published' : 'is-draft' }}">
                    <i data-lucide="{{ $published ? 'globe' : 'pencil-line' }}" aria-hidden="true"></i>{{ __("articles.status.{$status}") }}
                </span>
                @if($article->category)
                    <span class="pv-chip"><i data-lucide="folder" aria-hidden="true"></i>{{ $article->category->getNameInLanguage('ar') ?? $article->category->slug }}</span>
                @endif
                @if($article->is_featured)
                    <span class="pv-chip"><i data-lucide="star" aria-hidden="true"></i>{{ __('articles.fields.is_featured') }}</span>
                @endif
                @if($article->is_pinned)
                    <span class="pv-chip"><i data-lucide="pin" aria-hidden="true"></i>{{ __('articles.fields.is_pinned') }}</span>
                @endif
            </div>

            @foreach($languages as $lang => $label)
                @php $row = $locales->get($lang); @endphp
                <div x-show="lang === '{{ $lang }}'" @if($lang !== $firstLang) x-cloak @endif dir="{{ $dirFor($lang) }}" lang="{{ $lang }}" class="grid gap-3">
                    <h1 class="pv-hero-title">{{ $row?->title ?: ($locales->get('ar')?->title ?? '—') }}</h1>
                    @if(filled($row?->excerpt))
                        <p class="pv-hero-lead">{{ $row->excerpt }}</p>
                    @endif
                </div>
            @endforeach

            <div class="flex flex-wrap items-center gap-2">
                @if($article->writer)
                    <span class="pv-chip"><i data-lucide="pen-line" aria-hidden="true"></i>{{ $article->writer->name }}</span>
                @endif
                @if($article->hijri_date)
                    <span class="pv-chip"><i data-lucide="moon" aria-hidden="true"></i>{{ $article->hijri_date }}</span>
                @endif
                @if($article->read_time)
                    <span class="pv-chip"><i data-lucide="clock" aria-hidden="true"></i>{{ $article->read_time }} {{ __('wizard.show.read_time') }}</span>
                @endif
                <span class="pv-chip"><i data-lucide="eye" aria-hidden="true"></i><span class="tabular-nums">{{ number_format($article->views ?? 0) }}</span> {{ __('wizard.show.views') }}</span>
            </div>
        </div>
    </header>

    {{-- ═══════ TOOLBAR ═══════ --}}
    <div class="pv-toolbar">
        <div class="wz-langs" role="group" aria-label="{{ __('wizard.languages') }}">
            @foreach($languages as $lang => $label)
                <button type="button" class="wz-lang" :class="{ 'is-active': lang === '{{ $lang }}', 'is-filled': {{ $locales->get($lang)?->is_ready ? 'true' : 'false' }} }"
                        :aria-pressed="lang === '{{ $lang }}'" @click="lang = '{{ $lang }}'">
                    <span class="wz-dot" aria-hidden="true"></span>{{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($published && $publicSlug)
                <a href="{{ route('public.articles.show', $publicSlug) }}" target="_blank" rel="noopener" class="wz-btn is-ghost">
                    <i data-lucide="external-link" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('wizard.open_public') }}</span>
                </a>
            @endif
            <a href="{{ route('admin.articles.create') }}" class="wz-btn is-ghost">
                <i data-lucide="plus" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('articles.create') }}</span>
            </a>
            <a href="{{ route('admin.articles.edit', $article) }}" class="wz-btn is-primary">
                <i data-lucide="pencil" aria-hidden="true"></i>{{ __('wizard.open_editor') }}
            </a>
        </div>
    </div>

    @if($statistics->isNotEmpty())
        <section class="pv-stats pv-reveal mb-6" aria-label="{{ __('articles.fields.statistics') }}">
            @foreach($statistics as $row)
                <div class="wz-card pv-stat">
                    <p class="pv-stat-value">{{ $row['value'] ?? '' }}</p>
                    <p class="pv-stat-label">{{ $row['name'] ?? '' }}</p>
                </div>
            @endforeach
        </section>
    @endif

    <div class="pv-layout">
        <div class="min-w-0 grid gap-6 pv-reveal">

            {{-- ═══════ READING ═══════ --}}
            @foreach($languages as $lang => $label)
                @php
                    $row = $locales->get($lang);
                    $body = (string) ($row?->getBody() ?? '');
                    $hasBody = trim(strip_tags($body)) !== '' || str_contains($body, '<img') || str_contains($body, 'module-embed');
                    $toc = collect($row?->content['toc'] ?? [])->filter(fn ($item) => filled($item['title'] ?? $item['text'] ?? null));
                    $summary = $sanitizer->clean($row?->content['summary'] ?? null);
                    $description = $sanitizer->clean($row?->description);
                @endphp
                <div x-show="lang === '{{ $lang }}'" @if($lang !== $firstLang) x-cloak @endif class="grid gap-6" dir="{{ $dirFor($lang) }}" lang="{{ $lang }}">
                    @if($toc->isNotEmpty())
                        <nav class="wz-card pv-section" aria-label="{{ __('wizard.show.toc') }}">
                            <p class="pv-section-title"><i data-lucide="list-tree" aria-hidden="true"></i>{{ __('wizard.show.toc') }}<span class="pv-count">{{ $toc->count() }}</span></p>
                            <ol class="grid gap-1.5 text-sm">
                                @foreach($toc as $item)
                                    <li class="flex gap-2 {{ (int) ($item['level'] ?? 2) > 2 ? 'ps-5' : '' }}">
                                        <span class="tabular-nums text-slate-400">{{ $loop->iteration }}.</span>
                                        <span :class="darkMode ? 'text-slate-300' : 'text-slate-700'">{{ $item['title'] ?? $item['text'] }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif

                    <article class="wz-card pv-section">
                        @if(filled($description))
                            <div class="mb-6 ps-4 border-s-2 border-[#64CCC5] text-base leading-relaxed seerah-content" :class="darkMode ? 'text-slate-300' : 'text-slate-600'">{!! $description !!}</div>
                        @endif

                        @if($hasBody)
                            <div class="pv-prose seerah-content">{!! $body !!}</div>
                        @else
                            <div class="wz-empty">
                                <i data-lucide="file-plus-2" aria-hidden="true"></i>
                                <p>{{ __('wizard.show.no_content') }}</p>
                                <a href="{{ route('admin.articles.edit', $article) }}#step-content" class="wz-btn is-soft">{{ __('wizard.open_editor') }}</a>
                            </div>
                        @endif

                        @if(filled($summary))
                            <aside class="mt-8 rounded-2xl p-5 border" :class="darkMode ? 'border-white/10 bg-white/[0.03]' : 'border-slate-200 bg-slate-50'">
                                <p class="pv-section-title !mb-2"><i data-lucide="sparkles" aria-hidden="true"></i>{{ __('wizard.show.summary') }}</p>
                                <div class="seerah-content text-sm leading-relaxed" :class="darkMode ? 'text-slate-300' : 'text-slate-700'">{!! $summary !!}</div>
                            </aside>
                        @endif
                    </article>

                    <section class="wz-card pv-section" aria-label="{{ __('articles.seo.preview') }}">
                        <p class="pv-section-title"><i data-lucide="scan-search" aria-hidden="true"></i>{{ __('articles.seo.preview') }}</p>
                        <div class="max-w-[65ch]">
                            <p class="text-xs text-slate-500 truncate" dir="ltr">{{ $row?->slug ? route('public.articles.show', $row->slug) : url('/articles') }}</p>
                            <p class="mt-1 text-lg font-semibold leading-snug text-[#1a0dab] dark:text-[#8ab4f8]">{{ $row?->seo_title ?: ($row?->title ?? '—') }}</p>
                            <p class="mt-1 text-sm leading-relaxed" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">{{ $row?->seo_description ?: \Illuminate\Support\Str::limit(trim(strip_tags($body)), 160) }}</p>
                        </div>
                    </section>
                </div>
            @endforeach

            {{-- ═══════ GALLERY ═══════ --}}
            @if($article->gallery->isNotEmpty())
                <section class="wz-card pv-section" aria-labelledby="pv-gallery">
                    <h2 id="pv-gallery" class="pv-section-title"><i data-lucide="images" aria-hidden="true"></i>{{ __('wizard.show.gallery') }}<span class="pv-count">{{ $article->gallery->count() }}</span></h2>
                    <div class="pv-gallery">
                        @foreach($article->gallery as $item)
                            @continue(! $item->image)
                            <figure>
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->image) }}" alt="{{ $item->getCaption('ar') }}" loading="lazy">
                                @if($item->getCaption('ar'))<figcaption>{{ $item->getCaption('ar') }}</figcaption>@endif
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ═══════ MEDIA ═══════ --}}
            @if($article->episodes->isNotEmpty() || $article->mediaItems->isNotEmpty())
                <section class="wz-card pv-section" aria-labelledby="pv-media">
                    <h2 id="pv-media" class="pv-section-title"><i data-lucide="clapperboard" aria-hidden="true"></i>{{ __('wizard.show.media') }}<span class="pv-count">{{ $article->episodes->count() + $article->mediaItems->count() }}</span></h2>
                    <div class="grid gap-5 lg:grid-cols-2">
                        @if($article->episodes->isNotEmpty())
                            <div class="grid gap-2 content-start">
                                <p class="text-xs font-bold text-slate-500">{{ __('wizard.show.episodes') }} · {{ $article->episodes->count() }}</p>
                                <ol class="pv-list">
                                    @foreach($article->episodes as $episode)
                                        @php $episodeUrl = $episode->program && $episode->is_published ? route('public.media.episode', [$episode->program->slug, $episode->slug]) : null; @endphp
                                        <li>
                                            <{{ $episodeUrl ? 'a' : 'div' }} @if($episodeUrl) href="{{ $episodeUrl }}" target="_blank" rel="noopener" @endif class="pv-item">
                                                <span class="pv-item-icon"><i data-lucide="play" aria-hidden="true"></i></span>
                                                <span class="min-w-0">
                                                    <span class="pv-item-title block">{{ $episode->getTranslation('title', 'ar') ?: $episode->slug }}</span>
                                                    <span class="pv-item-sub block">{{ $episode->program?->slug }}@if($episode->episode_number) · E{{ $episode->episode_number }}@endif</span>
                                                </span>
                                                <span class="pv-item-end tabular-nums">{{ $loop->iteration }}</span>
                                            </{{ $episodeUrl ? 'a' : 'div' }}>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                        @if($article->mediaItems->isNotEmpty())
                            <div class="grid gap-2 content-start">
                                <p class="text-xs font-bold text-slate-500">{{ __('wizard.show.files') }} · {{ $article->mediaItems->count() }}</p>
                                <div class="pv-list">
                                    @foreach($article->mediaItems as $media)
                                        <a href="{{ route('admin.media-library.show', $media) }}" class="pv-item">
                                            <span class="pv-item-icon">
                                                @if($media->thumbnail_url)<img src="{{ $media->thumbnail_url }}" alt="" loading="lazy">@else<i data-lucide="file" aria-hidden="true"></i>@endif
                                            </span>
                                            <span class="min-w-0">
                                                <span class="pv-item-title block">{{ $media->original_filename ?? $media->filename }}</span>
                                                <span class="pv-item-sub block">{{ $media->mime_type }}</span>
                                            </span>
                                            <span class="pv-item-end">{{ $media->type }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- ═══════ REFERENCES ═══════ --}}
            @if($references->isNotEmpty())
                <section class="wz-card pv-section" aria-labelledby="pv-refs">
                    <h2 id="pv-refs" class="pv-section-title"><i data-lucide="library" aria-hidden="true"></i>{{ __('wizard.show.references') }}<span class="pv-count">{{ $references->count() }}</span></h2>
                    <ol class="grid gap-2">
                        @foreach($references as $ref)
                            <li class="flex gap-3 items-baseline text-sm">
                                <span class="tabular-nums font-bold text-slate-400">{{ $loop->iteration }}.</span>
                                <span class="min-w-0" :class="darkMode ? 'text-slate-300' : 'text-slate-700'">
                                    @if(filled($ref['url'] ?? null) && preg_match('#^https?://#i', $ref['url']))
                                        <a href="{{ $ref['url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold underline underline-offset-4 decoration-slate-300">{{ $ref['name'] ?: $ref['url'] }}</a>
                                    @else
                                        <strong :class="darkMode ? 'text-white' : 'text-slate-900'">{{ $ref['name'] ?? $ref['url'] ?? '' }}</strong>
                                    @endif
                                    @if(filled($ref['type'] ?? null))<span class="text-slate-500"> · {{ $ref['type'] }}</span>@endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>

        {{-- ═══════ ASIDE ═══════ --}}
        <aside class="pv-aside">
            <div class="wz-card wz-rail-card">
                <p class="wz-rail-title"><i data-lucide="database" aria-hidden="true"></i>{{ __('wizard.show.record') }}</p>
                <dl class="wz-kv !mt-0">
                    <div><dt>{{ __('wizard.show.writer') }}</dt><dd>{{ $article->writer?->name ?? '—' }}</dd></div>
                    <div><dt>{{ __('articles.fields.status') }}</dt><dd>{{ __("articles.status.{$status}") }}</dd></div>
                    @if($article->scheduled_at)
                        <div><dt>{{ __('wizard.show.scheduled') }}</dt><dd class="tabular-nums">{{ $article->scheduled_at->format('Y/m/d H:i') }}</dd></div>
                    @endif
                    <div><dt>{{ __('articles.fields.hijri_date') }}</dt><dd>{{ $article->hijri_date ?? '—' }}</dd></div>
                    <div><dt>{{ __('articles.fields.views') }}</dt><dd class="tabular-nums">{{ number_format($article->views ?? 0) }}</dd></div>
                    <div><dt>{{ __('wizard.show.created') }}</dt><dd class="tabular-nums">{{ $article->created_at?->format('Y/m/d') }}</dd></div>
                    <div><dt>{{ __('wizard.show.updated') }}</dt><dd>{{ $article->updated_at?->diffForHumans() }}</dd></div>
                </dl>
            </div>

            <div class="wz-card wz-rail-card">
                <p class="wz-rail-title"><i data-lucide="languages" aria-hidden="true"></i>{{ __('wizard.show.readiness') }}</p>
                <ul class="pv-locales">
                    @foreach($languages as $lang => $label)
                        @php $row = $locales->get($lang); @endphp
                        <li class="pv-locale">
                            <button type="button" class="font-semibold hover:underline" @click="lang = '{{ $lang }}'">{{ $label }}</button>
                            <span class="wz-pill !min-h-6 {{ $row?->is_ready ? 'is-success' : ($row ? 'is-warn' : '') }}">
                                <span class="wz-dot" aria-hidden="true"></span>
                                {{ $row?->is_ready ? __('wizard.show.ready') : ($row ? __('wizard.show.not_ready') : __('wizard.show.missing')) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($article->categories->isNotEmpty() || $article->tags->isNotEmpty())
                <div class="wz-card wz-rail-card grid gap-3">
                    @if($article->categories->isNotEmpty())
                        <div>
                            <p class="wz-rail-title !mb-2"><i data-lucide="folder" aria-hidden="true"></i>{{ __('wizard.show.categories') }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($article->categories as $category)
                                    <span class="wz-pill is-accent">{{ $category->getNameInLanguage('ar') ?? $category->slug }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($article->tags->isNotEmpty())
                        <div>
                            <p class="wz-rail-title !mb-2"><i data-lucide="hash" aria-hidden="true"></i>{{ __('wizard.show.tags') }}</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($article->tags as $tag)
                                    <span class="wz-pill">#{{ $tag->getTranslation('name', 'ar') ?? $tag->slug }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="wz-card wz-rail-card !border-red-500/20">
                <p class="wz-rail-title !text-red-600"><i data-lucide="trash-2" class="!text-red-600" aria-hidden="true"></i>{{ __('common.delete') }}</p>
                <p class="wz-help !mt-0 mb-3">{{ __('admin.delete_warning') }}</p>
                <form action="{{ route('admin.articles.destroy', $article) }}" method="POST"
                      @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="wz-btn w-full !text-red-600 !border-red-500/30 hover:!bg-red-500/10">
                        <i data-lucide="trash-2" aria-hidden="true"></i>{{ __('common.delete') }}
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>
@endsection
