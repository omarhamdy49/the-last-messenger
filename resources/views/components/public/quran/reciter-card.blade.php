{{--
    Reciter → editorial card. @props reciter (ReciterDirectoryService::listing row), variant tile|row, eager

    The portrait is the card media, so it morphs into the profile hero on open.
    The recitation coverage rides the plate as the big figure and the country
    (flag + name) is the plate badge.
--}}
@props(['reciter', 'variant' => 'tile', 'eager' => false])

@php
    $country = trim(($reciter['countryFlag'] ?? '').' '.($reciter['countryName'] ?? ''));
@endphp

<x-public.editorial.card
    :url="url('/quran/reciters/'.$reciter['slug'])"
    :title="$reciter['nameAr'] ?: $reciter['nameEn']"
    :excerpt="$reciter['description'] ?: null"
    :image="$reciter['profileImage'] ?: null"
    icon="mic"
    :badge="$country ?: null"
    :featured="(bool) ($reciter['isFeatured'] ?? false)"
    :big="$reciter['recitationsCount'] ?: null" :big-suffix="__('quran.public.recording_unit')"
    :meta="[
        ['icon' => 'languages', 'text' => $reciter['nameEn'] ?: null],
    ]"
    :variant="$variant" :eager="$eager" {{ $attributes }} />
