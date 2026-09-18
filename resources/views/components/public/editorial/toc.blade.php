{{-- On-this-page list with scroll spy (editorialReader.active). @props items: list of {id,label,level?}, title --}}
@props(['items', 'title' => null])

@if (collect($items)->isNotEmpty())
    <nav {{ $attributes->class('sx-surface p-4') }} aria-label="{{ $title ?? __('seerah.public.on_this_page') }}">
        <p class="px-2 mb-2 text-xs font-extrabold text-[color:var(--sx-muted)]">{{ $title ?? __('seerah.public.on_this_page') }}</p>
        <div class="sx-toc sx-scroll max-h-[min(26rem,calc(100dvh-16rem))] overflow-y-auto overscroll-contain">
            @foreach ($items as $item)
                <a href="#{{ $item['id'] }}" @class(['ps-6 !text-[0.82rem]' => ($item['level'] ?? 2) > 2]) title="{{ $item['label'] }}" :aria-current="(active === '{{ $item['id'] }}').toString()"><span class="sx-clamp-1">{{ $item['label'] }}</span></a>
            @endforeach
        </div>
    </nav>
@endif
