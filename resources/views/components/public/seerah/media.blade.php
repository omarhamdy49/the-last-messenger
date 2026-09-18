{{-- Attached media files: video/audio inline, anything else as a download tile. @props media, index --}}
@props(['media', 'index' => null])

@if (! empty($media))
    <x-public.seerah.section id="media" icon="play-circle" :title="__('seerah.public.section_media')" :index="$index">
        <div class="grid gap-4">
            @foreach ($media as $item)
                @if ($item['type'] === 'video')
                    <video controls preload="metadata" class="w-full rounded-2xl bg-black" src="{{ $item['url'] }}" aria-label="{{ $item['title'] }}"></video>
                @elseif ($item['type'] === 'audio')
                    <div class="sx-tile !grid-cols-1">
                        <p class="sx-tile-title" title="{{ $item['title'] }}">{{ $item['title'] }}</p>
                        <audio controls preload="metadata" class="w-full" src="{{ $item['url'] }}"></audio>
                    </div>
                @else
                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="sx-tile">
                        <span class="sx-tile-icon"><i data-lucide="file" aria-hidden="true"></i></span>
                        <span class="sx-tile-title" title="{{ $item['title'] }}">{{ $item['title'] }}</span>
                        <i data-lucide="download" class="w-4 h-4 text-[color:var(--sx-muted)]" aria-hidden="true"></i>
                    </a>
                @endif
            @endforeach
        </div>
    </x-public.seerah.section>
@endif
