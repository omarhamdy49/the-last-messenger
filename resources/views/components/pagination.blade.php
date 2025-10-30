{{-- Pagination Component --}}
@props(['paginator'])
@if($paginator->hasPages())
<nav {{ $attributes->merge(['class' => 'flex items-center justify-between']) }}>
    <div class="flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        <span>{{ __('site.showing') }}</span>
        <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $paginator->firstItem() }}</span>
        <span>{{ __('site.to') }}</span>
        <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $paginator->lastItem() }}</span>
        <span>{{ __('site.of') }}</span>
        <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $paginator->total() }}</span>
    </div>
    <div class="flex items-center gap-2">
        @if($paginator->onFirstPage())
            <span class="px-4 py-2 rounded-lg opacity-50 cursor-not-allowed" :class="darkMode ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400'">
                <i class="w-4 h-4" data-lucide="chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-4 py-2 rounded-lg hover:scale-105 transition" :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                <i class="w-4 h-4" data-lucide="chevron-left"></i>
            </a>
        @endif
        @foreach(range(1, $paginator->lastPage()) as $page)
            @if($page == $paginator->currentPage())
                <span class="px-4 py-2 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold">{{ $page }}</span>
            @elseif($page <= 3 || $page > $paginator->lastPage() - 3 || abs($page - $paginator->currentPage()) <= 2)
                <a href="{{ $paginator->url($page) }}" class="px-4 py-2 rounded-lg hover:scale-105 transition" :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">{{ $page }}</a>
            @elseif($page == 4 || $page == $paginator->lastPage() - 3)
                <span class="px-2" :class="darkMode ? 'text-gray-600' : 'text-gray-400'">...</span>
            @endif
        @endforeach
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-4 py-2 rounded-lg hover:scale-105 transition" :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                <i class="w-4 h-4" data-lucide="chevron-right"></i>
            </a>
        @else
            <span class="px-4 py-2 rounded-lg opacity-50 cursor-not-allowed" :class="darkMode ? 'bg-gray-800 text-gray-600' : 'bg-gray-200 text-gray-400'">
                <i class="w-4 h-4" data-lucide="chevron-right"></i>
            </span>
        @endif
    </div>
</nav>
@endif
