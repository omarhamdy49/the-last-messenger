{{-- Windowed pagination for a LengthAwarePaginator. @props paginator --}}
@props(['paginator'])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $start = max(1, min($current - 2, $last - 4));
    $end = min($last, $start + 4);
    $isRtl = in_array(app()->getLocale(), ['ar', 'ur'], true);
@endphp

@if ($last > 1)
    <nav class="sx-pager" aria-label="pagination">
        <p class="text-sm text-[color:var(--sx-muted)]">
            {!! __('seerah.public.page_of', ['current' => '<strong class="text-[color:var(--sx-ink)] tabular-nums">'.$current.'</strong>', 'total' => '<span class="tabular-nums">'.$last.'</span>']) !!}
        </p>
        <div class="sx-pages">
            @if ($paginator->previousPageUrl())
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="sx-page" aria-label="{{ __('seerah.public.previous') }}">
                    <i data-lucide="chevron-{{ $isRtl ? 'right' : 'left' }}" class="w-5 h-5" aria-hidden="true"></i>
                </a>
            @endif
            @if ($start > 1)
                <a href="{{ $paginator->url(1) }}" class="sx-page">1</a>
                @if ($start > 2)<span class="px-1 text-[color:var(--sx-muted)]" aria-hidden="true">…</span>@endif
            @endif
            @for ($page = $start; $page <= $end; $page++)
                <a href="{{ $paginator->url($page) }}" class="sx-page" @if ($page === $current) aria-current="page" @endif>{{ $page }}</a>
            @endfor
            @if ($end < $last)
                @if ($end < $last - 1)<span class="px-1 text-[color:var(--sx-muted)]" aria-hidden="true">…</span>@endif
                <a href="{{ $paginator->url($last) }}" class="sx-page">{{ $last }}</a>
            @endif
            @if ($paginator->nextPageUrl())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="sx-page" aria-label="{{ __('seerah.public.next') }}">
                    <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="w-5 h-5" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </nav>
@endif
