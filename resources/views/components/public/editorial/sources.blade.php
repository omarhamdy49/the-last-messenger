{{-- Numbered sources. @props id, title, items: list of {title, detail?, url?, tag?}, index --}}
@props(['id' => 'sources', 'title', 'items', 'index' => null])

@if (collect($items)->isNotEmpty())
    <x-public.editorial.section :id="$id" icon="library" :title="$title" :index="$index">
        <ol class="sx-sources">
            @foreach ($items as $item)
                <li>
                    <div class="min-w-0">
                        @if (! empty($item['url']))
                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer nofollow" class="font-bold text-[color:var(--sx-ink)] underline decoration-[color:var(--sx-line-strong)] underline-offset-4 hover:text-[color:var(--sx-accent)] break-words">{{ $item['title'] }}</a>
                        @else
                            <strong class="break-words">{{ $item['title'] }}</strong>
                        @endif
                        @if (! empty($item['tag']))<span class="sx-chip !inline-flex !min-h-0 !py-0 !px-2 ms-2 align-middle">{{ $item['tag'] }}</span>@endif
                        @if (! empty($item['detail']))<span class="break-words">{{ $item['detail'] }}</span>@endif
                    </div>
                </li>
            @endforeach
        </ol>
    </x-public.editorial.section>
@endif
