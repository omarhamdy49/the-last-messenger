@props(['paginator'])

@php $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true); @endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination"
         {{ $attributes->merge(['class' => 'sx-pager mt-10']) }}>

        {{-- Showing X–Y of Z --}}
        <p class="text-sm text-[color:var(--sx-muted)]">
            {{ __('site.showing') }}
            <span class="font-bold tabular-nums text-[color:var(--sx-ink)]">{{ $paginator->firstItem() }}</span>–<span class="font-bold tabular-nums text-[color:var(--sx-ink)]">{{ $paginator->lastItem() }}</span>
            {{ __('site.of') }}
            <span class="font-bold tabular-nums text-[color:var(--sx-ink)]">{{ $paginator->total() }}</span>
        </p>

        <div class="sx-pages">
            {{-- Prev --}}
            @if ($paginator->onFirstPage())
                <span class="sx-page opacity-40" aria-hidden="true">
                    <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="sx-page"
                   aria-label="{{ __('seerah.public.previous') }}" title="{{ __('seerah.public.previous') }}">
                    <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5"></i>
                </a>
            @endif

            {{-- Numbers with ellipsis windows --}}
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page" class="sx-page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="sx-page">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="sx-page"
                   aria-label="{{ __('seerah.public.next') }}" title="{{ __('seerah.public.next') }}">
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5"></i>
                </a>
            @else
                <span class="sx-page opacity-40" aria-hidden="true">
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
