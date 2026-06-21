@props(['items' => []])

@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'flex items-center flex-wrap gap-1.5 text-sm text-gray-500 dark:text-gray-400']) }}>
    <a href="{{ route('public.home') }}" aria-label="{{ __('site.home') }}" class="hover:text-primary transition-colors flex items-center">
        <i data-lucide="home" class="w-4 h-4" aria-hidden="true"></i>
        <span class="sr-only">{{ __('site.home') }}</span>
    </a>
    @foreach ($items as $item)
        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4 shrink-0 opacity-60"></i>
        @if (! empty($item['url']) && ! $loop->last)
            <a href="{{ $item['url'] }}" class="hover:text-primary transition-colors truncate max-w-[12rem]">{{ $item['label'] }}</a>
        @else
            <span class="text-gray-900 dark:text-gray-100 font-semibold truncate max-w-[14rem]" aria-current="page">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
