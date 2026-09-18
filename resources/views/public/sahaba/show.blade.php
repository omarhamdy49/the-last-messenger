@extends('layouts.public')

@section('title', ($profile['name'] ?? __('sahaba.page_title')) . ' | ' . __('site.site_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($profile['biography'] ?? __('sahaba.page_description')), 155))
@section('og_type', 'profile')

@push('structured_data')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Person",
    "name": "{{ $profile['name'] }}",
    "description": "{{ \Illuminate\Support\Str::limit(strip_tags($profile['biography'] ?? ''), 200) }}",
    "url": "{{ route('public.sahaba.show', $sahabi->slug) }}"
}
</script>
@endpush

@section('content')

@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $honorific = $profile['isFemale'] ? __('site.radia_allah_anha') : __('site.radi_allah_anhu');

    // The portrait, when the record holds one. `thumb` is free text in the
    // admin form: a URL, a root-relative path, or a path on the public disk.
    $thumb = trim((string) $sahabi->thumb);
    $portrait = $thumb === ''
        ? null
        : (\Illuminate\Support\Str::startsWith($thumb, ['http://', 'https://', '/'])
            ? $thumb
            : \Illuminate\Support\Facades\Storage::disk('public')->url($thumb));

    // Facts ride the hero's bottom edge. Every one names what it is, and a fact
    // with no recorded value is not rendered at all.
    $facts = collect([
        ['icon' => 'user-round', 'label' => __('sahaba.kunya'), 'value' => $profile['kunya']],
        ['icon' => 'users', 'label' => __('sahaba.tribe'), 'value' => $profile['tribe']
            ? ($profile['clan'] ? $profile['tribe'].' — '.$profile['clan'] : $profile['tribe'])
            : null],
        ['icon' => 'map-pin', 'label' => __('sahaba.birth_place'), 'value' => $profile['birthPlace']],
        ['icon' => 'calendar-check', 'label' => __('sahaba.conversion'), 'value' => $profile['conversionDateHijri']
            ? $profile['conversionDateHijri'].' '.__('sahaba.year_hijri')
            : ($profile['conversionOrder'] ? $profile['conversionOrder'].' '.__('sahaba.order_unit') : null)],
        ['icon' => 'swords', 'label' => __('sahaba.battles'), 'value' => ! empty($profile['battles'])
            ? implode('، ', array_slice($profile['battles'], 0, 4))
            : null],
        ['icon' => 'heart', 'label' => __('sahaba.death_place'), 'value' => trim(
            ($profile['deathPlace'] ?? '').' '.($profile['deathDateHijri'] ? $profile['deathDateHijri'].' '.__('sahaba.year_hijri') : '')
        ) ?: null],
    ])->filter(fn (array $fact): bool => filled($fact['value']))->values();

    // Figures, only where there is a real number behind them.
    $statistics = collect([
        __('sahaba.years_unit') => $profile['ageAtDeath'],
        __('sahaba.battles') => $profile['battlesCount'],
        __('sahaba.hadith_narrated') => $profile['hadithCount'],
    ])->filter()->all();

    $toc = collect([
        ['id' => 'story', 'label' => __('sahaba.section_story'), 'show' => filled($profile['biography'])],
        ['id' => 'virtues', 'label' => __('sahaba.section_virtues'), 'show' => filled($profile['virtues'])],
        ['id' => 'martyrdom', 'label' => __('sahaba.section_martyrdom'), 'show' => filled($profile['martyrdom'])],
        ['id' => 'timeline', 'label' => __('sahaba.section_timeline'), 'show' => ! empty($profile['timeline'])],
    ])->filter(fn (array $item): bool => $item['show'])->values();

    $firstSection = $toc->first()['id'] ?? 'story';

    $relatedTiles = collect($related)->map(fn ($rel): array => [
        'title' => $rel['name'],
        'url' => route('public.sahaba.show', $rel['slug']),
        'icon' => 'user-round',
        'sub' => $rel['laqab'] ?: null,
    ])->all();

    $articleUrl = ! empty($profile['article']) && Route::has('public.articles.show')
        ? route('public.articles.show', $profile['article']['slug'])
        : null;
@endphp

{{-- The reader's own scroll state: progress, the section the toc highlights
     (`active`, the name the shared toc component binds to) and the local
     bookmark list. Unchanged behaviour — only the skin is the design system. --}}
<div class="sx pb-28"
     x-data="{
        progress: 0,
        active: @js($firstSection),
        sections: @js($toc->pluck('id')->all()),
        bookmarked: false,

        init() {
            try { this.bookmarked = (JSON.parse(localStorage.getItem('bookmarkedSahaba') || '[]')).includes(@js($sahabi->slug)); } catch (e) {}
            this.onScroll();
            window.addEventListener('scroll', () => this.onScroll(), { passive: true });
        },

        onScroll() {
            const scrollable = document.documentElement.scrollHeight - window.innerHeight;
            this.progress = scrollable > 0 ? Math.min(window.scrollY / scrollable, 1) : 0;
            for (let i = this.sections.length - 1; i >= 0; i--) {
                const el = document.getElementById(this.sections[i]);
                if (el && el.getBoundingClientRect().top <= 140) { this.active = this.sections[i]; return; }
            }
        },

        toggleBookmark() {
            let list = [];
            try { list = JSON.parse(localStorage.getItem('bookmarkedSahaba') || '[]'); } catch (e) {}
            const idx = list.indexOf(@js($sahabi->slug));
            if (idx > -1) { list.splice(idx, 1); this.bookmarked = false; }
            else { list.push(@js($sahabi->slug)); this.bookmarked = true; }
            try { localStorage.setItem('bookmarkedSahaba', JSON.stringify(list)); } catch (e) {}
        },

        async share() {
            const data = { title: @js($profile['name']), url: window.location.href };
            if (navigator.share) { try { await navigator.share(data); } catch (e) {} return; }
            try {
                await navigator.clipboard.writeText(window.location.href);
                this.$store.toast?.push(@js(__('sahaba.link_copied')));
            } catch (e) {}
        },
     }">

    <div class="sx-progress" aria-hidden="true"><span :style="`transform: scaleX(${progress})`" style="transform: scaleX(0)"></span></div>

    <x-public.editorial.hero :image="$portrait" variant="reading" :title="$profile['name']"
        :lead="filled($profile['biography']) ? excerpt_sentence($profile['biography'], 180) : null">
        <x-slot:top>
            <nav aria-label="breadcrumb" class="mb-4 flex flex-wrap items-center gap-2 text-sm text-white/80">
                <a href="{{ url('/') }}" class="hover:underline underline-offset-4">{{ __('site.home') }}</a>
                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-3.5 h-3.5" aria-hidden="true"></i>
                <a href="{{ route('public.sahaba') }}" class="hover:underline underline-offset-4">{{ __('sahaba.page_title') }}</a>
            </nav>
        </x-slot:top>
        <x-slot:chips>
            @if ($profile['laqab'] || $profile['statusLabel'])
                <span class="sx-glass-chip sx-clamp-1 max-w-[16rem]" title="{{ $profile['laqab'] ?: $profile['statusLabel'] }}">
                    <i data-lucide="award" aria-hidden="true"></i>{{ $profile['laqab'] ?: $profile['statusLabel'] }}
                </span>
            @endif
            <span class="sx-glass-chip">{{ $honorific }}</span>
        </x-slot:chips>
        <x-slot:meta>
            @if ($profile['birthDateHijri'])
                <span class="sx-glass-chip"><i data-lucide="calendar-plus" aria-hidden="true"></i><span class="tabular-nums">{{ $profile['birthDateHijri'] }} {{ __('sahaba.year_hijri') }}</span></span>
            @endif
            @if ($profile['deathDateHijri'])
                <span class="sx-glass-chip"><i data-lucide="calendar-minus" aria-hidden="true"></i><span class="tabular-nums">{{ $profile['deathDateHijri'] }} {{ __('sahaba.year_hijri') }}</span></span>
            @endif
            @if ($profile['birthPlace'])
                <span class="sx-glass-chip"><i data-lucide="map-pin" aria-hidden="true"></i>{{ $profile['birthPlace'] }}</span>
            @endif
        </x-slot:meta>
        <x-slot:actions>
            <a href="#{{ $firstSection }}" class="sx-btn is-light"><i data-lucide="book-open" aria-hidden="true"></i>{{ __('site.read_biography') }}</a>
            <button type="button" @click="toggleBookmark()" class="sx-btn is-glass" :aria-pressed="bookmarked.toString()"
                    :title="bookmarked ? @js(__('sahaba.bookmarked')) : @js(__('sahaba.bookmark'))">
                <i data-lucide="bookmark" aria-hidden="true"></i>
                <span x-text="bookmarked ? @js(__('sahaba.bookmarked')) : @js(__('sahaba.bookmark'))">{{ __('sahaba.bookmark') }}</span>
            </button>
            <button type="button" @click="share()" class="sx-btn is-glass" title="{{ __('sahaba.share') }}">
                <i data-lucide="share-2" aria-hidden="true"></i>{{ __('sahaba.share') }}
            </button>
        </x-slot:actions>
    </x-public.editorial.hero>

    <x-public.editorial.fact-ribbon :facts="$facts" />

    <div class="sx-wrap mt-10 sm:mt-14 grid gap-10 sm:gap-14">
        <x-public.editorial.stats :statistics="$statistics" :title="__('sahaba.profile_card')" />

        <div class="sx-reading">
            <article class="sx-surface sx-article min-w-0 grid gap-10">
                @if (filled($profile['biography']))
                    <x-public.editorial.section id="story" icon="book-open" :title="__('sahaba.section_story')"
                        x-data="{ expanded: false }">
                        @if ($profile['fullName'] && $profile['fullName'] !== $profile['name'])
                            <p class="sx-note mb-6"><i data-lucide="quote" class="w-5 h-5" aria-hidden="true"></i>
                                <span class="font-bold text-[color:var(--sx-ink)]">{{ $profile['fullName'] }}</span>
                            </p>
                        @endif

                        <div class="sx-prose whitespace-pre-line" :class="!expanded && 'line-clamp-[12]'">{{ $profile['biography'] }}</div>

                        @if (mb_strlen(strip_tags($profile['biography'])) > 600)
                            <button type="button" @click="expanded = !expanded" class="sx-btn mt-5" :aria-expanded="expanded.toString()">
                                <span x-text="expanded ? @js(__('sahaba.show_less')) : @js(__('sahaba.show_more'))">{{ __('sahaba.show_more') }}</span>
                                <i data-lucide="chevron-down" class="transition-transform" :class="expanded && 'rotate-180'" aria-hidden="true"></i>
                            </button>
                        @endif

                        @if (! empty($profile['roles']))
                            <div class="mt-6 flex flex-wrap gap-2">
                                @foreach ($profile['roles'] as $role)
                                    <span class="sx-chip sx-clamp-1" title="{{ $role }}">{{ $role }}</span>
                                @endforeach
                            </div>
                        @endif
                    </x-public.editorial.section>
                @endif

                @if (filled($profile['virtues']))
                    <x-public.editorial.section id="virtues" icon="sword" :title="__('sahaba.section_virtues')">
                        <div class="sx-prose whitespace-pre-line">{{ $profile['virtues'] }}</div>

                        @if (filled($profile['famousNarrations']))
                            <div class="sx-panel mt-6 p-5">
                                <h3 class="mb-2 flex items-center gap-2 font-extrabold text-[color:var(--sx-ink)]">
                                    <i data-lucide="quote" class="w-5 h-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>
                                    {{ __('sahaba.section_narrations') }}
                                </h3>
                                <div class="sx-prose whitespace-pre-line !text-base">{{ $profile['famousNarrations'] }}</div>
                            </div>
                        @endif
                    </x-public.editorial.section>
                @endif

                @if (filled($profile['martyrdom']))
                    <x-public.editorial.section id="martyrdom" icon="heart" :title="__('sahaba.section_martyrdom')">
                        <div class="sx-dispute">
                            <span class="sx-dispute-icon" aria-hidden="true"><i data-lucide="heart"></i></span>
                            <p class="whitespace-pre-line">{{ $profile['martyrdom'] }}</p>
                        </div>
                    </x-public.editorial.section>
                @endif

                @if (! empty($profile['timeline']))
                    <x-public.editorial.section id="timeline" icon="milestone" :title="__('sahaba.section_timeline')">
                        {{-- Numbered stations down one rail: the life in the order
                             it happened, with the recorded date beside each step. --}}
                        <ol class="sx-path">
                            @foreach ($profile['timeline'] as $item)
                                <li>
                                    <span class="sx-path-step" aria-hidden="true">{{ $loop->iteration }}</span>
                                    <div class="sx-panel p-4">
                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                            <h3 class="font-extrabold text-[color:var(--sx-ink)] sx-clamp-2" title="{{ $item['title'] }}">{{ $item['title'] }}</h3>
                                            @if ($item['hijriDate'] || $item['hijriYear'])
                                                <span class="sx-chip tabular-nums">{{ $item['hijriDate'] ?: $item['hijriYear'].' '.__('sahaba.year_hijri') }}</span>
                                            @endif
                                        </div>
                                        @if ($item['description'])
                                            <p class="mt-2 text-sm leading-relaxed text-[color:var(--sx-muted)]">{{ $item['description'] }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </x-public.editorial.section>
                @endif

                @if ($toc->isEmpty())
                    <x-public.editorial.empty-state icon="file-question" :title="__('sahaba.no_biography')" class="!shadow-none" />
                @endif
            </article>

            <aside class="sx-aside">
                {{-- The person, as a plate: portrait or monogram, name, kunya. --}}
                <div class="sx-surface grid justify-items-center gap-2 p-6 text-center">
                    <span class="sx-avatar is-lg">
                        @if ($portrait)
                            <img src="{{ $portrait }}" alt="" width="176" height="176" loading="lazy" decoding="async" onerror="this.remove()">
                        @else
                            <i data-lucide="user-round" aria-hidden="true"></i>
                        @endif
                    </span>
                    <h2 class="text-lg font-extrabold text-[color:var(--sx-ink)] sx-clamp-2" title="{{ $profile['name'] }}">{{ $profile['name'] }}</h2>
                    @if ($profile['kunya'])
                        <p class="sx-chip sx-clamp-1" title="{{ $profile['kunya'] }}">{{ $profile['kunya'] }}</p>
                    @endif
                    <p class="text-sm text-[color:var(--sx-muted)]">{{ $honorific }}</p>
                </div>

                <x-public.editorial.toc :items="$toc" />

                @if ($articleUrl)
                    <x-public.editorial.related :title="__('sahaba.related_article')" icon="book-open"
                        :items="[['title' => $profile['article']['title'], 'url' => $articleUrl, 'icon' => 'file-text']]" />
                @endif

                <x-public.editorial.related :title="__('sahaba.related_companions')" icon="users" :items="$relatedTiles" />
            </aside>
        </div>
    </div>
</div>

@endsection
