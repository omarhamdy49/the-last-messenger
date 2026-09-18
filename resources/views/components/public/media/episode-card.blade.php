{{--
    Episode → editorial card. @props episode (MediaController::mapEpisodeCard, optionally
    merged with watch progress), showSeason, variant tile|row, eager.
    Duration, "watched" and the resume bar live in the card body so the numerals stay tabular.
--}}
@props(['episode', 'showSeason' => false, 'variant' => 'tile', 'eager' => false])

@php
    $episodeLabel = __('media.public.episode_label', ['number' => $episode['number']]);
    $percent = (int) ($episode['progress_percent'] ?? 0);
@endphp

<x-public.editorial.card
    :url="$episode['url']" :title="$episode['title'] ?: $episodeLabel"
    :image="$episode['thumbnail']" :icon="$episode['has_video'] ? 'monitor-play' : 'video-off'"
    :badge="$episodeLabel"
    :meta="$showSeason ? [['icon' => 'layers', 'text' => __('media.public.season_label', ['number' => $episode['season']])]] : []"
    :variant="$variant" :eager="$eager" {{ $attributes }}>
    <div class="flex flex-wrap items-center gap-2">
        @if ($episode['duration'])
            <span class="sx-chip"><i data-lucide="clock" aria-hidden="true"></i><span dir="ltr" class="tabular-nums">{{ $episode['duration'] }}</span></span>
        @endif
        @unless ($episode['has_video'])
            <span class="sx-chip is-danger"><i data-lucide="video-off" aria-hidden="true"></i>{{ __('media.public.video_unavailable_title') }}</span>
        @endunless
        @if (! empty($episode['completed']))
            <span class="sx-chip"><i data-lucide="check" aria-hidden="true"></i>{{ __('media.public.watched') }}</span>
        @endif
    </div>

    @if ($percent > 0)
        <div class="h-1 overflow-hidden rounded-full bg-[color:var(--sx-surface-2)]"
             role="img" aria-label="{{ $percent }}% {{ __('media.public.complete') }}">
            <span class="block h-full rounded-full" style="width: {{ $percent }}%; background: var(--sx-grad)"></span>
        </div>
    @endif
</x-public.editorial.card>
