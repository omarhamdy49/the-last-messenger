{{--
    Media program → editorial card. @props card (MediaController::mapProgramCard),
    rank (Top-10 numeral, printed as the card's big figure), variant tile|row, eager.
--}}
@props(['card', 'rank' => null, 'variant' => 'tile', 'eager' => false])

@php
    $glyphs = ['seerah' => 'map-pin', 'quran' => 'book-open', 'hadith' => 'scroll-text', 'kids' => 'baby', 'documentary' => 'film'];
@endphp

<x-public.editorial.card
    :url="$card['url']" :title="$card['title']"
    :excerpt="filled($card['description'] ?? null) ? \Illuminate\Support\Str::limit(strip_tags((string) $card['description']), 120) : null"
    :image="$card['poster'] ?? $card['banner']"
    :icon="$glyphs[$card['category']] ?? 'monitor-play'"
    :badge="$card['category_label']"
    :big="$rank === null ? null : str_pad((string) $rank, 2, '0', STR_PAD_LEFT)"
    :meta="[
        ['icon' => 'trending-up', 'text' => $card['is_trending'] ? __('media.public.trending_now') : null],
        ['icon' => 'calendar', 'text' => $card['year'] ?: null],
    ]"
    :variant="$variant" :eager="$eager" {{ $attributes }}>
    <div class="sx-card-meta">
        <span><i data-lucide="list-video" aria-hidden="true"></i><span class="tabular-nums">{{ __('media.public.episodes_count', ['count' => $card['episodes_count']]) }}</span></span>
    </div>
</x-public.editorial.card>
