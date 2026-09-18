{{--
    Surah → editorial card. @props surah (QuranController::cachedSurahList row), variant tile|row

    Surahs carry no artwork, so the card's gradient plate is the cover and the
    mushaf number rides it as the big figure (the old medallion). The reading
    progress bar lives in the card's slot because it is per-visitor state
    (localStorage), read through the page's Alpine root.
--}}
@props(['surah', 'variant' => 'tile'])

<x-public.editorial.card
    :url="url('/quran/'.$surah['number'])"
    :title="$surah['nameAr']"
    :excerpt="$surah['nameEn'] ?: null"
    icon="book-open"
    :badge="$surah['type'] === 'makki' ? __('quran.public.makki') : __('quran.public.madani')"
    :big="$surah['number']"
    :meta="[
        ['icon' => 'book-open', 'text' => $surah['verses'].' '.__('quran.public.verse_unit')],
    ]"
    :variant="$variant" {{ $attributes }}>

    <span x-show="getProgress({{ (int) $surah['number'] }}) > 0" x-cloak
          class="block h-1.5 overflow-hidden rounded-full bg-[color:var(--sx-surface-2)]"
          role="progressbar" aria-valuemin="0" aria-valuemax="100"
          :aria-valuenow="getProgress({{ (int) $surah['number'] }})"
          aria-label="{{ __('quran.public.overall_progress') }}">
        <span class="block h-full" :style="`width: ${getProgress({{ (int) $surah['number'] }})}%; background: var(--sx-grad)`"></span>
    </span>
</x-public.editorial.card>
