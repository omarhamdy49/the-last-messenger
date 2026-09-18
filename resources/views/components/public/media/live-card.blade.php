{{--
    Live channel → editorial card. @props card (MediaController::mapLiveCard), variant tile|row, eager.
    The status label rides on the artwork as the card badge; a live channel also
    carries a red status chip in the body, since "live" is the deciding signal.
--}}
@props(['card', 'variant' => 'tile', 'eager' => false])

<x-public.editorial.card
    :url="$card['url']" :title="$card['title']" :image="$card['thumbnail']" icon="radio"
    :badge="$card['is_live'] ? __('media.public.live_now') : __('media.public.live_status_'.$card['status'])"
    :meta="[
        ['icon' => 'eye', 'text' => $card['views_count'] > 0 ? number_format($card['views_count']).' '.__('media.public.views') : null],
    ]"
    :variant="$variant" :eager="$eager" {{ $attributes }}>
    @if ($card['is_live'])
        <div>
            <span class="sx-chip is-danger"><i data-lucide="radio" aria-hidden="true"></i>{{ __('media.public.live_now') }}</span>
        </div>
    @endif
</x-public.editorial.card>
