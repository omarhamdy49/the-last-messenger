{{-- Bento gallery. @props id, title (section heading), alt (fallback alt), images: list of {url, caption}, index --}}
@props(['id' => 'gallery', 'title', 'alt' => '', 'images', 'index' => null])

@if (! empty($images))
    <x-public.editorial.section :id="$id" icon="images" :title="$title" :index="$index">
        <div class="sx-bento">
            @foreach ($images as $image)
                <figure>
                    <img src="{{ $image['url'] }}" alt="{{ $image['caption'] ?: $alt }}" width="800" height="600" loading="lazy" decoding="async">
                    @if ($image['caption'])<figcaption>{{ $image['caption'] }}</figcaption>@endif
                </figure>
            @endforeach
        </div>
    </x-public.editorial.section>
@endif
