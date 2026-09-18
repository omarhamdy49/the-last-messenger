@extends('layouts.admin')

@push('styles')
@include('partials.seerah-content-styles')
@endpush

@php
    /**
     * Admin preview of a Seerah event: everything the editor entered, laid out close to
     * the public page, with per-language switching, readiness and record details.
     *
     * @var \App\Domain\Seerah\Models\SeerahEvent $event
     * @var array<int, \App\Domain\Shared\Contracts\MediaReference> $media
     * @var array<string, array<int, \App\Domain\Shared\Contracts\ContentReference>> $links
     */
    $links = $links ?? [];
    $languages = enabled_content_languages();
    $sanitizer = app(\App\Domain\Shared\Services\RichTextSanitizer::class);
    $dirFor = fn (string $lang): string => in_array($lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr';
    $translation = fn (string $lang) => $event->translations->firstWhere('locale', $lang);

    $titleLanguages = collect($languages)->filter(fn ($label, $lang) => filled($event->getTranslation('title', $lang, false)));
    $firstLang = $titleLanguages->keys()->first() ?? 'ar';

    $contentTranslations = collect($languages)
        ->map(fn ($label, $locale) => ['label' => $label, 'html' => $sanitizer->clean($event->getTranslation('content', $locale, false))])
        ->filter(fn ($row) => filled($row['html']));

    $cover = $event->pageImageUrl();
    $statistics = collect(is_array($event->statistics) ? $event->statistics : [])->filter(fn ($v) => is_scalar($v));
    $published = $event->status?->value === 'published';

    $linkGroups = [
        'verse' => ['book-open', __('wizard.show.verses')],
        'hadith' => ['quote', __('wizard.show.hadiths')],
        'muslim' => ['users', __('wizard.show.companions')],
        'article' => ['newspaper', __('wizard.show.articles')],
    ];
    $episodes = $links['episode'] ?? [];
    $taxonomy = [...($links['tag'] ?? []), ...($links['category'] ?? [])];
    $linkTotal = collect($linkGroups)->keys()->sum(fn ($type) => count($links[$type] ?? []));
@endphp

@section('page-header')
<nav class="flex flex-wrap items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-slate-500'" aria-label="breadcrumb">
    <a href="{{ route('admin.seerah-events.index') }}" class="hover:text-accent transition-colors">أحداث السيرة النبوية</a>
    <i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180" aria-hidden="true"></i>
    <span aria-current="page">{{ $event->getTranslation('title', 'ar') }}</span>
</nav>
@endsection

@section('content')
<div class="wz-root pv-root" x-data="{ lang: @js($firstLang) }">

    {{-- ═══════ HERO ═══════ --}}
    <header class="pv-hero">
        <div class="pv-hero-media">
            @if($cover)
                <img src="{{ $cover }}" alt="" fetchpriority="high">
            @else
                <div class="w-full h-full bg-gradient-to-br from-[#0b3b73] via-[#0f2a4a] to-[#16552a]"></div>
            @endif
        </div>
        <div class="pv-hero-body">
            <div class="flex flex-wrap items-center gap-2">
                <span class="pv-chip {{ $published ? 'is-published' : 'is-draft' }}">
                    <i data-lucide="{{ $published ? 'globe' : 'pencil-line' }}" aria-hidden="true"></i>{{ $event->status?->labelArabic() ?? '—' }}
                </span>
                @if($event->type)
                    <span class="pv-chip"><i data-lucide="{{ $event->type->icon ?? 'tag' }}" aria-hidden="true"></i>{{ $event->type->name('ar') }}</span>
                @endif
                @if($event->era)
                    <span class="pv-chip"><i data-lucide="hourglass" aria-hidden="true"></i>{{ $event->era->labelArabic() }}</span>
                @endif
                @if($event->milestone)
                    <span class="pv-chip"><i data-lucide="flag" aria-hidden="true"></i>{{ $event->milestone->getTranslation('title', 'ar') }}</span>
                @endif
            </div>

            @foreach($languages as $lang => $label)
                <div x-show="lang === '{{ $lang }}'" @if($lang !== $firstLang) x-cloak @endif dir="{{ $dirFor($lang) }}" lang="{{ $lang }}" class="grid gap-3">
                    <h1 class="pv-hero-title">{{ $event->getTranslation('title', $lang, false) ?: $event->getTranslation('title', 'ar') }}</h1>
                    @if(filled($event->getTranslation('description', $lang, false)))
                        <div class="pv-hero-lead seerah-content">{!! $sanitizer->clean($event->getTranslation('description', $lang, false)) !!}</div>
                    @endif
                </div>
            @endforeach

            <div class="flex flex-wrap items-center gap-2">
                @if($event->formattedHijriDate('ar'))
                    <span class="pv-chip"><i data-lucide="moon" aria-hidden="true"></i>{{ $event->formattedHijriDate('ar') }}</span>
                @endif
                @if($event->gregorian_date)
                    <span class="pv-chip" dir="ltr"><i data-lucide="calendar" aria-hidden="true"></i>{{ $event->gregorian_date->format('Y/m/d') }}</span>
                @endif
                @if($event->place)
                    <span class="pv-chip"><i data-lucide="map-pin" aria-hidden="true"></i>{{ $event->place->name('ar') }}</span>
                @endif
            </div>
        </div>
    </header>

    {{-- ═══════ TOOLBAR ═══════ --}}
    <div class="pv-toolbar">
        <div class="wz-langs" role="group" aria-label="{{ __('wizard.languages') }}">
            @foreach($languages as $lang => $label)
                <button type="button" class="wz-lang" :class="{ 'is-active': lang === '{{ $lang }}', 'is-filled': {{ $titleLanguages->has($lang) ? 'true' : 'false' }} }"
                        :aria-pressed="lang === '{{ $lang }}'" @click="lang = '{{ $lang }}'; $dispatch('pv-lang', '{{ $lang }}')">
                    <span class="wz-dot" aria-hidden="true"></span>{{ $label }}
                </button>
            @endforeach
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($published)
                <a href="{{ route('public.seerah.event', $event->slug) }}" target="_blank" rel="noopener" class="wz-btn is-ghost">
                    <i data-lucide="external-link" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('wizard.open_public') }}</span>
                </a>
            @endif
            <a href="{{ route('admin.seerah-events.create') }}" class="wz-btn is-ghost">
                <i data-lucide="plus" aria-hidden="true"></i><span class="hidden sm:inline">إضافة حدث</span>
            </a>
            <a href="{{ route('admin.seerah-events.edit', $event->id) }}" class="wz-btn is-primary">
                <i data-lucide="pencil" aria-hidden="true"></i>{{ __('wizard.open_editor') }}
            </a>
        </div>
    </div>

    {{-- ═══════ FIGURES ═══════ --}}
    @if($statistics->isNotEmpty())
        <section class="pv-stats pv-reveal mb-6" aria-label="{{ __('wizard.show.statistics') }}">
            @foreach($statistics as $label => $value)
                <div class="wz-card pv-stat">
                    <p class="pv-stat-value">{{ $value }}</p>
                    <p class="pv-stat-label">{{ $label }}</p>
                </div>
            @endforeach
        </section>
    @endif

    <div class="pv-layout">
        <div class="min-w-0 grid gap-6 pv-reveal">

            {{-- Dispute note --}}
            @foreach($languages as $lang => $label)
                @if(filled($event->getTranslation('dispute_note', $lang, false)))
                    <aside x-show="lang === '{{ $lang }}'" @if($lang !== $firstLang) x-cloak @endif
                           class="wz-alert !mb-0 !border-amber-400/40 !bg-amber-500/10 !text-amber-800 dark:!text-amber-200" dir="{{ $dirFor($lang) }}">
                        <p class="wz-alert-title"><i data-lucide="alert-triangle" aria-hidden="true"></i>{{ __('wizard.show.dispute') }}</p>
                        <p class="text-sm leading-relaxed">{{ $event->getTranslation('dispute_note', $lang, false) }}</p>
                    </aside>
                @endif
            @endforeach

            {{-- Content --}}
            @if($contentTranslations->isNotEmpty())
                <section aria-labelledby="event-content-heading" class="wz-card pv-section">
                    <h2 id="event-content-heading" class="pv-section-title">
                        <i data-lucide="book-open" aria-hidden="true"></i>المحتوى الكامل
                    </h2>
                    <div x-data="{ activeTab: '{{ $contentTranslations->keys()->first() }}' }"
                         @pv-lang.window="if (@js($contentTranslations->keys()).includes($event.detail)) activeTab = $event.detail"
                         class="grid gap-5">
                        @if($contentTranslations->count() > 1)
                            <div class="wz-langs w-fit">
                                @foreach($contentTranslations as $lang => $row)
                                    <button type="button" class="wz-lang is-filled" :class="{ 'is-active': activeTab === '{{ $lang }}' }"
                                            :aria-pressed="activeTab === '{{ $lang }}'" aria-controls="event-content-{{ $lang }}"
                                            @click="activeTab = '{{ $lang }}'">
                                        <span class="wz-dot" aria-hidden="true"></span>{{ $row['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @foreach($contentTranslations as $lang => $row)
                            <article id="event-content-{{ $lang }}" x-show="activeTab === '{{ $lang }}'" @if(! $loop->first) x-cloak @endif
                                     class="pv-prose seerah-content" lang="{{ $lang }}" dir="{{ $dirFor($lang) }}">
                                {!! $row['html'] !!}
                            </article>
                        @endforeach
                    </div>
                </section>
            @else
                <section class="wz-card pv-section">
                    <h2 class="pv-section-title"><i data-lucide="book-open" aria-hidden="true"></i>{{ __('wizard.show.content') }}</h2>
                    <div class="wz-empty">
                        <i data-lucide="file-plus-2" aria-hidden="true"></i>
                        <p>{{ __('wizard.show.no_content') }}</p>
                        <a href="{{ route('admin.seerah-events.edit', $event->id) }}#step-content" class="wz-btn is-soft">{{ __('wizard.open_editor') }}</a>
                    </div>
                </section>
            @endif

            {{-- Gallery --}}
            @if($event->gallery->isNotEmpty())
                <section class="wz-card pv-section" aria-labelledby="pv-gallery">
                    <h2 id="pv-gallery" class="pv-section-title">
                        <i data-lucide="images" aria-hidden="true"></i>{{ __('wizard.show.gallery') }}
                        <span class="pv-count">{{ $event->gallery->count() }}</span>
                    </h2>
                    <div class="pv-gallery">
                        @foreach($event->gallery as $item)
                            <figure>
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->image) }}" alt="{{ $item->captionFor('ar') }}" loading="lazy">
                                @if($item->captionFor('ar'))
                                    <figcaption>{{ $item->captionFor('ar') }}</figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Related content --}}
            <section class="wz-card pv-section" aria-labelledby="pv-links">
                <h2 id="pv-links" class="pv-section-title">
                    <i data-lucide="network" aria-hidden="true"></i>{{ __('wizard.show.links') }}
                    <span class="pv-count">{{ $linkTotal }}</span>
                </h2>
                @if($linkTotal === 0)
                    <div class="wz-empty">
                        <i data-lucide="link-2-off" aria-hidden="true"></i>
                        <p>{{ __('wizard.show.nothing_linked') }}</p>
                        <a href="{{ route('admin.seerah-events.edit', $event->id) }}#step-links" class="wz-btn is-soft">{{ __('wizard.open_editor') }}</a>
                    </div>
                @else
                    <div class="grid gap-5 lg:grid-cols-2">
                        @foreach($linkGroups as $type => [$icon, $groupLabel])
                            @continue(empty($links[$type]))
                            <div class="grid gap-2 content-start">
                                <p class="text-xs font-bold text-slate-500 flex items-center gap-1.5"><i data-lucide="{{ $icon }}" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $groupLabel }} · {{ count($links[$type]) }}</p>
                                <div class="pv-list">
                                    @foreach($links[$type] as $ref)
                                        <{{ $ref->url ? 'a' : 'div' }} @if($ref->url) href="{{ $ref->url }}" target="_blank" rel="noopener" @endif class="pv-item">
                                            <span class="pv-item-icon">
                                                @if($ref->thumbUrl)<img src="{{ $ref->thumbUrl }}" alt="" loading="lazy">@else<i data-lucide="{{ $icon }}" aria-hidden="true"></i>@endif
                                            </span>
                                            <span class="min-w-0">
                                                <span class="pv-item-title block">{{ $ref->title }}</span>
                                                @if($ref->subtitle)<span class="pv-item-sub block">{{ $ref->subtitle }}</span>@endif
                                            </span>
                                            <span class="pv-item-end">@if($ref->url)<i data-lucide="arrow-up-left" class="w-4 h-4 ltr:rotate-90" aria-hidden="true"></i>@endif</span>
                                        </{{ $ref->url ? 'a' : 'div' }}>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($taxonomy !== [])
                    <div class="mt-5 pt-5 border-t flex flex-wrap gap-2" :class="darkMode ? 'border-white/10' : 'border-slate-200'">
                        @foreach($taxonomy as $ref)
                            <span class="wz-pill"><i data-lucide="{{ $ref->type->value === 'tag' ? 'hash' : 'folder' }}" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $ref->title }}</span>
                        @endforeach
                    </div>
                @endif

                @foreach($languages as $lang => $label)
                    @php $external = $event->externalLinksFor($lang); @endphp
                    @if($external !== [])
                        <div x-show="lang === '{{ $lang }}'" @if($lang !== $firstLang) x-cloak @endif class="mt-5 pt-5 border-t grid gap-2" :class="darkMode ? 'border-white/10' : 'border-slate-200'">
                            <p class="text-xs font-bold text-slate-500">{{ __('wizard.show.external') }}</p>
                            <div class="pv-list">
                                @foreach($external as $row)
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener noreferrer" class="pv-item">
                                        <span class="pv-item-icon"><i data-lucide="globe" aria-hidden="true"></i></span>
                                        <span class="min-w-0">
                                            <span class="pv-item-title block">{{ $row['title'] ?: $row['url'] }}</span>
                                            <span class="pv-item-sub block" dir="ltr">{{ parse_url($row['url'], PHP_URL_HOST) }}</span>
                                        </span>
                                        <span class="pv-item-end"><i data-lucide="external-link" class="w-4 h-4" aria-hidden="true"></i></span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </section>

            {{-- Media & episodes --}}
            @if($media !== [] || $episodes !== [])
                <section class="wz-card pv-section" aria-labelledby="pv-media">
                    <h2 id="pv-media" class="pv-section-title">
                        <i data-lucide="clapperboard" aria-hidden="true"></i>{{ __('wizard.show.media') }}
                        <span class="pv-count">{{ count($media) + count($episodes) }}</span>
                    </h2>
                    <div class="grid gap-5 lg:grid-cols-2">
                        @if($episodes !== [])
                            <div class="grid gap-2 content-start">
                                <p class="text-xs font-bold text-slate-500">{{ __('wizard.show.episodes') }} · {{ count($episodes) }}</p>
                                <ol class="pv-list">
                                    @foreach($episodes as $ref)
                                        <li>
                                            <{{ $ref->url ? 'a' : 'div' }} @if($ref->url) href="{{ $ref->url }}" target="_blank" rel="noopener" @endif class="pv-item">
                                                <span class="pv-item-icon !w-16 !h-10">
                                                    @if($ref->thumbUrl)<img src="{{ $ref->thumbUrl }}" alt="" loading="lazy">@else<i data-lucide="play" aria-hidden="true"></i>@endif
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="pv-item-title block">{{ $ref->title }}</span>
                                                    @if($ref->subtitle)<span class="pv-item-sub block">{{ $ref->subtitle }}</span>@endif
                                                </span>
                                                <span class="pv-item-end tabular-nums">{{ $loop->iteration }}</span>
                                            </{{ $ref->url ? 'a' : 'div' }}>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                        @if($media !== [])
                            <div class="grid gap-2 content-start">
                                <p class="text-xs font-bold text-slate-500">{{ __('wizard.show.files') }} · {{ count($media) }}</p>
                                <div class="pv-list">
                                    @foreach($media as $item)
                                        <{{ $item->url ? 'a' : 'div' }} @if($item->url) href="{{ $item->url }}" target="_blank" rel="noopener" @endif class="pv-item">
                                            <span class="pv-item-icon">
                                                @if($item->thumbnailUrl)<img src="{{ $item->thumbnailUrl }}" alt="" loading="lazy">@else<i data-lucide="file" aria-hidden="true"></i>@endif
                                            </span>
                                            <span class="min-w-0">
                                                <span class="pv-item-title block">{{ $item->title }}</span>
                                                <span class="pv-item-sub block">{{ $item->mimeType ?? $item->type }}</span>
                                            </span>
                                            <span class="pv-item-end">{{ $item->type }}</span>
                                        </{{ $item->url ? 'a' : 'div' }}>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Details & sources --}}
            @foreach($languages as $lang => $label)
                @php
                    $details = $translation($lang)?->metadata ?? [];
                    $sources = $translation($lang)?->sources ?? [];
                @endphp
                @if($details !== [] || $sources !== [])
                    <section x-show="lang === '{{ $lang }}'" @if($lang !== $firstLang) x-cloak @endif class="wz-card pv-section grid gap-6" dir="{{ $dirFor($lang) }}">
                        @if($details !== [])
                            <div>
                                <h2 class="pv-section-title"><i data-lucide="list" aria-hidden="true"></i>{{ __('wizard.show.metadata') }}</h2>
                                <dl class="grid gap-3 sm:grid-cols-2">
                                    @foreach($details as $row)
                                        <div class="rounded-xl p-3 border" :class="darkMode ? 'border-white/10 bg-white/[0.02]' : 'border-slate-200 bg-slate-50/70'">
                                            <dt class="text-xs font-semibold text-slate-500">{{ $row['key'] ?? '' }}</dt>
                                            <dd class="mt-1 font-semibold" :class="darkMode ? 'text-white' : 'text-slate-900'">{{ $row['value'] ?? '' }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                        @if($sources !== [])
                            <div>
                                <h2 class="pv-section-title"><i data-lucide="library" aria-hidden="true"></i>{{ __('wizard.show.sources') }}</h2>
                                <ol class="grid gap-2">
                                    @foreach($sources as $source)
                                        <li class="flex gap-3 items-baseline text-sm">
                                            <span class="tabular-nums font-bold text-slate-400">{{ $loop->iteration }}.</span>
                                            <span :class="darkMode ? 'text-slate-300' : 'text-slate-700'">
                                                <strong :class="darkMode ? 'text-white' : 'text-slate-900'">{{ $source['title'] ?? '' }}</strong>
                                                @if(filled($source['author'] ?? null)) — {{ $source['author'] }}@endif
                                                @if(filled($source['reference'] ?? null))<span class="text-slate-500"> · {{ $source['reference'] }}</span>@endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </section>
                @endif
            @endforeach

            {{-- Search appearance --}}
            <section class="wz-card pv-section" aria-labelledby="pv-seo">
                <h2 id="pv-seo" class="pv-section-title"><i data-lucide="scan-search" aria-hidden="true"></i>{{ __('articles.seo.preview') }}</h2>
                @foreach($languages as $lang => $label)
                    @php $seo = $event->seoFor($lang); @endphp
                    <div x-show="lang === '{{ $lang }}'" @if($lang !== $firstLang) x-cloak @endif dir="{{ $dirFor($lang) }}" class="max-w-[65ch]">
                        <p class="text-xs text-slate-500 truncate" dir="ltr">{{ route('public.seerah.event', $event->slug) }}</p>
                        <p class="mt-1 text-lg font-semibold leading-snug text-[#1a0dab] dark:text-[#8ab4f8]">{{ $seo['title'] ?? ($event->getTranslation('title', $lang, false) ?: $event->getTranslation('title', 'ar')) }}</p>
                        <p class="mt-1 text-sm leading-relaxed" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">
                            {{ $seo['description'] ?? \Illuminate\Support\Str::limit(strip_tags((string) $event->getTranslation('description', $lang, false)), 160) }}
                        </p>
                    </div>
                @endforeach
            </section>
        </div>

        {{-- ═══════ ASIDE ═══════ --}}
        <aside class="pv-aside">
            <div class="wz-card wz-rail-card">
                <p class="wz-rail-title"><i data-lucide="database" aria-hidden="true"></i>{{ __('wizard.show.record') }}</p>
                <dl class="wz-kv !mt-0">
                    <div><dt>الرابط المختصر</dt><dd class="font-mono text-xs" dir="ltr">{{ $event->slug }}</dd></div>
                    <div><dt>نوع الحدث</dt><dd>{{ $event->type?->name('ar') ?? '—' }}</dd></div>
                    <div><dt>{{ __('admin.status') }}</dt><dd>{{ $event->status?->labelArabic() ?? '—' }}</dd></div>
                    <div><dt>الحقبة</dt><dd>{{ $event->milestone?->getTranslation('title', 'ar') ?? '—' }}</dd></div>
                    <div><dt>الحقبة التاريخية</dt><dd>{{ $event->era?->labelArabic() ?? '—' }}</dd></div>
                    <div><dt>ترتيب العرض</dt><dd class="tabular-nums">{{ $event->order }}</dd></div>
                    <div><dt>{{ __('wizard.show.created') }}</dt><dd class="tabular-nums">{{ $event->created_at?->format('Y/m/d') }}</dd></div>
                    <div><dt>{{ __('wizard.show.updated') }}</dt><dd>{{ $event->updated_at?->diffForHumans() }}</dd></div>
                </dl>
            </div>

            <div class="wz-card wz-rail-card">
                <p class="wz-rail-title"><i data-lucide="languages" aria-hidden="true"></i>{{ __('wizard.show.readiness') }}</p>
                <ul class="pv-locales">
                    @foreach($languages as $lang => $label)
                        @php
                            $row = $translation($lang);
                            $parts = collect(['title', 'description', 'content'])->filter(fn ($f) => filled($row?->{$f}))->count();
                        @endphp
                        <li class="pv-locale">
                            <button type="button" class="font-semibold hover:underline" @click="lang = '{{ $lang }}'; $dispatch('pv-lang', '{{ $lang }}')">{{ $label }}</button>
                            <span class="flex items-center gap-2">
                                <span class="text-xs tabular-nums text-slate-500">{{ $parts }}/3</span>
                                <span class="wz-pill !min-h-6 {{ $row?->is_ready ? 'is-success' : ($row ? 'is-warn' : '') }}">
                                    <span class="wz-dot" aria-hidden="true"></span>
                                    {{ $row?->is_ready ? __('wizard.show.ready') : ($row ? __('wizard.show.not_ready') : __('wizard.show.missing')) }}
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($event->place)
                <div class="wz-card wz-rail-card">
                    <p class="wz-rail-title"><i data-lucide="map-pinned" aria-hidden="true"></i>{{ __('wizard.show.place') }}</p>
                    <p class="font-bold" :class="darkMode ? 'text-white' : 'text-slate-900'">{{ $event->place->name('ar') }}</p>
                    @if($coords = $event->resolvedCoordinates())
                        <p class="wz-help tabular-nums" dir="ltr">{{ number_format($coords['latitude'], 4) }}, {{ number_format($coords['longitude'], 4) }}</p>
                        <a href="https://www.openstreetmap.org/?mlat={{ $coords['latitude'] }}&mlon={{ $coords['longitude'] }}#map=10/{{ $coords['latitude'] }}/{{ $coords['longitude'] }}"
                           target="_blank" rel="noopener noreferrer" class="wz-btn is-ghost w-full mt-3"><i data-lucide="map" aria-hidden="true"></i>OpenStreetMap</a>
                    @endif
                </div>
            @endif

            <div class="wz-card wz-rail-card !border-red-500/20">
                <p class="wz-rail-title !text-red-600"><i data-lucide="trash-2" class="!text-red-600" aria-hidden="true"></i>حذف الحدث</p>
                <p class="wz-help !mt-0 mb-3">سيتم حذف هذا الحدث نهائياً ولا يمكن استرجاعه.</p>
                <form action="{{ route('admin.seerah-events.destroy', $event->id) }}" method="POST"
                      @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="wz-btn w-full !text-red-600 !border-red-500/30 hover:!bg-red-500/10">
                        <i data-lucide="trash-2" aria-hidden="true"></i>حذف الحدث
                    </button>
                </form>
            </div>
        </aside>
    </div>
</div>
@endsection
