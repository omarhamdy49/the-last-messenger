@props(['paginator'])

@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination"
         {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row items-center justify-between gap-4 mt-10']) }}>

        {{-- Showing X–Y of Z --}}
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('site.showing') }}
            <span class="font-semibold">{{ $paginator->firstItem() }}</span>–<span class="font-semibold">{{ $paginator->lastItem() }}</span>
            {{ __('site.of') }}
            <span class="font-semibold">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1.5">
            {{-- Prev --}}
            @if ($paginator->onFirstPage())
                <span class="px-3 py-2 rounded-lg text-gray-300 dark:text-gray-600 cursor-not-allowed">
                    <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-4 h-4"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="px-3 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors">
                    <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-4 h-4"></i>
                </a>
            @endif

            {{-- Numbers with ellipsis windows --}}
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page" class="px-4 py-2 rounded-lg font-bold text-white bg-gradient-to-l from-primary to-secondary shadow-md">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-4 py-2 rounded-lg font-semibold text-gray-700 dark:text-gray-200 hover:bg-primary/10 hover:text-primary transition-colors">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="px-3 py-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-primary/10 hover:text-primary transition-colors">
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4"></i>
                </a>
            @else
                <span class="px-3 py-2 rounded-lg text-gray-300 dark:text-gray-600 cursor-not-allowed">
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-4 h-4"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
