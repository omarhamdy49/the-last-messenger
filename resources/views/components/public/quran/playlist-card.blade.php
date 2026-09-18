{{--
    Playlist → editorial card. @props card (PlaylistCard::from), variant tile|row, eager

    Playlists carry no cover image, so the card's gradient plate + glyph is the
    artwork and the surah count rides it as the big figure.
--}}
@props(['card', 'variant' => 'tile', 'eager' => false])

@php
    $num = fn ($n) => app()->getLocale() === 'ar' ? arabic_digits($n) : number_format((int) $n);
@endphp

<x-public.editorial.card
    :url="$card['url']" :title="$card['name']" :excerpt="$card['description'] ?: null"
    :icon="$card['isPublic'] ? 'list-music' : 'lock'"
    :badge="$card['isPublic'] ? __('quran.playlists.scope_'.$card['scope']) : __('quran.playlists.private')"
    :featured="$card['isFeatured']"
    :big="$num($card['items'])" :big-suffix="__('quran.playlists.surah_unit')"
    :meta="[
        ['icon' => 'mic', 'text' => __('quran.playlists.reciters_count', ['count' => $num(count($card['reciters']))])],
        ['icon' => 'headphones', 'text' => __('quran.playlists.plays').' '.$num($card['plays'])],
        ['icon' => 'user-round', 'text' => $card['owner']['name'] ?: null],
    ]"
    :variant="$variant" :eager="$eager" {{ $attributes }} />
