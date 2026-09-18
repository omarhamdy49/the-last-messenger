{{-- Attached media: video/audio inline, anything else as a download tile. @props id, title, items: list of {title,type,url}, index --}}
@props(['id' => 'media', 'title', 'items', 'index' => null])

@if (! empty($items))
    <x-public.editorial.section :id="$id" icon="play-circle" :title="$title" :index="$index">
        <div class="grid gap-4">
            @foreach ($items as $item)
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
    </x-public.editorial.section>
@endif
