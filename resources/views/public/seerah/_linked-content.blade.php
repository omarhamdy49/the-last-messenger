{{--
    Cross-module links of a Seerah event: the video queue, related verses / hadiths /
    companions / articles, external links, and tags & categories.
    Rendered by both event layouts; every block is absent when it has no data.

    Expects: $event; optional $episodesIndex / $linkedIndex (story chapter numbers).
--}}
@php
    $linked = $event['linked'] ?? [];
    $episodes = $event['episodes'] ?? [];
    $tags = $event['tags'] ?? [];
    $externalLinks = collect($event['externalLinks'] ?? [])->filter(fn ($l) => ! empty($l['url']));
    $groups = [
        'verse' => ['book-open', __('seerah.public.linked_verses')],
        'hadith' => ['quote', __('seerah.public.linked_hadiths')],
        'muslim' => ['users', __('seerah.public.linked_companions')],
        'article' => ['newspaper', __('seerah.public.linked_articles')],
    ];
@endphp

@if ($episodes !== [])
    <x-public.editorial.section id="episodes" icon="list-video" :title="__('seerah.public.section_episodes')" :index="$episodesIndex ?? null">
        <ol class="grid gap-3 sm:grid-cols-2">
            @foreach ($episodes as $index => $episode)
                <li>
                    <a href="{{ $episode['url'] ?? '#' }}" class="sx-tile !grid-cols-[auto_minmax(0,1fr)] h-full">
                        <span class="relative w-28 aspect-video rounded-xl overflow-hidden shrink-0 grid place-items-center text-white" style="background: var(--sx-grad)">
                            @if (! empty($episode['thumb']))
                                <img src="{{ $episode['thumb'] }}" alt="" width="160" height="90" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                            @endif
                            <span class="relative w-8 h-8 rounded-full grid place-items-center bg-black/45 backdrop-blur-sm"><i data-lucide="play" class="w-4 h-4" aria-hidden="true"></i></span>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-xs font-bold tabular-nums text-[color:var(--sx-muted)]">{{ $index + 1 }}</span>
                            <span class="sx-tile-title" title="{{ $episode['title'] }}">{{ $episode['title'] }}</span>
                            @if (! empty($episode['subtitle']))
                                <span class="sx-tile-sub block" title="{{ $episode['subtitle'] }}">{{ $episode['subtitle'] }}</span>
                            @endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ol>
    </x-public.editorial.section>
@endif

@if ($linked !== [])
    <x-public.editorial.section id="linked" icon="network" :title="__('seerah.public.section_linked')" :index="$linkedIndex ?? null">
        <div class="grid gap-8">
            @foreach ($groups as $type => [$icon, $groupLabel])
                @continue(empty($linked[$type]))
                <div class="grid gap-3">
                    <h3 class="flex items-center gap-2 font-extrabold text-[color:var(--sx-ink)]">
                        <i data-lucide="{{ $icon }}" class="w-5 h-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ $groupLabel }}
                        <span class="sx-chip !min-h-0 !py-0 !px-2 tabular-nums">{{ count($linked[$type]) }}</span>
                    </h3>
                    <ul class="grid gap-3 {{ in_array($type, ['verse', 'hadith'], true) ? '' : 'sm:grid-cols-2' }}">
                        @foreach ($linked[$type] as $item)
                            <li>
                                @php $tag = ! empty($item['url']) ? 'a' : 'div'; @endphp
                                @if (in_array($type, ['verse', 'hadith'], true))
                                    <{{ $tag }} @if ($tag === 'a') href="{{ $item['url'] }}" @endif class="sx-tile !grid-cols-1 !gap-2 !p-5">
                                        <span class="sx-clamp-3 break-words font-amiri text-xl leading-loose text-[color:var(--sx-ink)]" dir="rtl" lang="ar" title="{{ $item['title'] }}">{{ $item['title'] }}</span>
                                        @if (! empty($item['subtitle']))
                                            <span class="sx-tile-sub flex items-center gap-1.5"><i data-lucide="{{ $icon }}" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $item['subtitle'] }}</span>
                                        @endif
                                    </{{ $tag }}>
                                @else
                                    <{{ $tag }} @if ($tag === 'a') href="{{ $item['url'] }}" @endif class="sx-tile">
                                        <span class="sx-tile-icon">
                                            @if (! empty($item['thumb']))<img src="{{ $item['thumb'] }}" alt="" loading="lazy">@else<i data-lucide="{{ $icon }}" aria-hidden="true"></i>@endif
                                        </span>
                                        <span class="min-w-0">
                                            <span class="sx-tile-title" title="{{ $item['title'] }}">{{ $item['title'] }}</span>
                                            @if (! empty($item['subtitle']))<span class="sx-tile-sub block" title="{{ $item['subtitle'] }}">{{ $item['subtitle'] }}</span>@endif
                                        </span>
                                        @if ($tag === 'a')<i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180 text-[color:var(--sx-muted)]" aria-hidden="true"></i>@else<span></span>@endif
                                    </{{ $tag }}>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </x-public.editorial.section>
@endif

@if ($externalLinks->isNotEmpty() || $tags !== [])
    <div class="grid gap-6 sx-reveal">
        @if ($externalLinks->isNotEmpty())
            <div class="grid gap-3">
                <h2 class="flex items-center gap-2 text-lg font-extrabold text-[color:var(--sx-ink)]">
                    <i data-lucide="external-link" class="w-5 h-5 text-[color:var(--sx-accent)]" aria-hidden="true"></i>{{ __('seerah.public.section_external_links') }}
                </h2>
                <ul class="grid gap-2 sm:grid-cols-2">
                    @foreach ($externalLinks as $link)
                        <li>
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer nofollow" class="sx-tile">
                                <span class="sx-tile-icon"><i data-lucide="globe" aria-hidden="true"></i></span>
                                <span class="min-w-0">
                                    <span class="sx-tile-title" title="{{ $link['title'] ?: $link['url'] }}">{{ $link['title'] ?: $link['url'] }}</span>
                                    <span class="sx-tile-sub block" dir="ltr">{{ parse_url($link['url'], PHP_URL_HOST) }}</span>
                                </span>
                                <i data-lucide="arrow-up-right" class="w-4 h-4 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($tags !== [])
            <div>
                <h2 class="sr-only">{{ __('seerah.public.tags_label') }}</h2>
                <ul class="flex flex-wrap gap-2" aria-label="{{ __('seerah.public.tags_label') }}">
                    @foreach ($tags as $tagItem)
                        <li>
                            @if (! empty($tagItem['url']))
                                <a href="{{ $tagItem['url'] }}" class="sx-chip sx-clamp-1" title="{{ $tagItem['title'] }}"><i data-lucide="hash" class="inline align-[-2px]" aria-hidden="true"></i> {{ $tagItem['title'] }}</a>
                            @else
                                <span class="sx-chip sx-clamp-1" title="{{ $tagItem['title'] }}"><i data-lucide="hash" class="inline align-[-2px]" aria-hidden="true"></i> {{ $tagItem['title'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
