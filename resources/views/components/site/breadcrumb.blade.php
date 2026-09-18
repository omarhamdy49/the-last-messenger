@props(['items' => []])

@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'flex items-center flex-wrap gap-1.5 text-sm text-[color:var(--sx-muted)]']) }}>
    <a href="{{ route('public.home') }}" aria-label="{{ __('site.home') }}" title="{{ __('site.home') }}"
       class="flex items-center transition-colors hover:text-[color:var(--sx-accent)]">
        <i data-lucide="home" class="w-4 h-4" aria-hidden="true"></i>
        <span class="sr-only">{{ __('site.home') }}</span>
    </a>
    @foreach ($items as $item)
        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4 shrink-0 opacity-60" aria-hidden="true"></i>
        @if (! empty($item['url']) && ! $loop->last)
            <a href="{{ $item['url'] }}" title="{{ $item['label'] }}"
               class="sx-clamp-1 max-w-[12rem] transition-colors hover:text-[color:var(--sx-accent)]">{{ $item['label'] }}</a>
        @else
            <span class="sx-clamp-1 max-w-[14rem] font-bold text-[color:var(--sx-ink)]" title="{{ $item['label'] }}" aria-current="page">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
