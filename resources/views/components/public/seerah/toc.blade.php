{{-- On-this-page list with scroll spy (seerahReader.active). @props items: list of {id,label} --}}
@props(['items'])

@if (collect($items)->isNotEmpty())
    <nav {{ $attributes->class('sx-surface p-4') }} aria-label="{{ __('seerah.public.on_this_page') }}">
        <p class="px-2 mb-2 text-xs font-extrabold text-[color:var(--sx-muted)]">{{ __('seerah.public.on_this_page') }}</p>
        <div class="sx-toc">
            @foreach ($items as $item)
                <a href="#{{ $item['id'] }}" :aria-current="(active === '{{ $item['id'] }}').toString()">{{ $item['label'] }}</a>
            @endforeach
        </div>
    </nav>
@endif
