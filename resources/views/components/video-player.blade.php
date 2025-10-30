{{-- Video Player Component --}}
@props(['videoUrl', 'thumbnail' => null, 'title' => null])
<div x-data="videoPlayer()" {{ $attributes->merge(['class' => 'relative rounded-2xl overflow-hidden shadow-2xl']) }}>
    <div x-ref="playerContainer" class="relative aspect-video bg-black">
        @if(str_contains($videoUrl, 'youtube.com') || str_contains($videoUrl, 'youtu.be'))
        <iframe x-ref="player" width="100%" height="100%" :src="'https://www.youtube.com/embed/{{ last(explode('/', parse_url($videoUrl, PHP_URL_PATH))) }}?enablejsapi=1'" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        @else
        <video x-ref="player" class="w-full h-full" controls :poster="'{{ $thumbnail }}'"><source src="{{ $videoUrl }}" type="video/mp4"></video>
        @endif
    </div>
    @if($title)
    <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/80 to-transparent">
        <h3 class="text-xl font-bold text-white">{{ $title }}</h3>
    </div>
    @endif
</div>
