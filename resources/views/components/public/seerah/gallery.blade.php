{{-- Bento gallery. @props images: list of {url, caption}, title (alt fallback), index --}}
@props(['images', 'title' => '', 'index' => null])

@if (! empty($images))
    <x-public.seerah.section id="gallery" icon="images" :title="__('seerah.public.event_gallery')" :index="$index">
        <div class="sx-bento">
            @foreach ($images as $image)
                <figure>
                    <img src="{{ $image['url'] }}" alt="{{ $image['caption'] ?: $title }}" width="800" height="600" loading="lazy" decoding="async">
                    @if ($image['caption'])
                        <figcaption>{{ $image['caption'] }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </x-public.seerah.section>
@endif
