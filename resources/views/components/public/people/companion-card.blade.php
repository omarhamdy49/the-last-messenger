{{--
    Companion (Muslim) → editorial card. One mapping, so the directory grid and
    any future companion rail cannot describe the same person differently.

    @props sahabi (Muslim model, list columns only — no relations are touched
           here, the directory query does not load them), variant tile|row, eager
--}}
@props(['sahabi', 'variant' => 'tile', 'eager' => false])

@php
    $locale = app()->getLocale();
    $name = $sahabi->name($locale);
    $kunya = $sahabi->getTranslation('kunya', $locale) ?: null;

    // `thumb` is free text in the admin form: a full URL, a root-relative path,
    // or a path on the public disk. Anything else would 404 as an <img> src.
    $thumb = trim((string) $sahabi->thumb);
    $image = $thumb === ''
        ? null
        : (\Illuminate\Support\Str::startsWith($thumb, ['http://', 'https://', '/'])
            ? $thumb
            : \Illuminate\Support\Facades\Storage::disk('public')->url($thumb));

    // Signed, not absolute: a pre-Hijra year printed as a positive number would
    // be a claim the record does not make.
    $deathYear = $sahabi->death_year_hijri === null
        ? null
        : $sahabi->death_year_hijri.' '.__('sahaba.year_hijri');
@endphp

<x-public.editorial.card
    :url="route('public.sahaba.show', $sahabi->slug)"
    :title="$name"
    :image="$image"
    icon="user-round"
    :badge="$kunya"
    :meta="[
        ['icon' => 'calendar', 'text' => $deathYear],
        ['icon' => 'heart', 'text' => $sahabi->gender?->value === 'female' ? __('site.radia_allah_anha') : __('site.radi_allah_anhu')],
    ]"
    :variant="$variant" :eager="$eager" {{ $attributes }} />
