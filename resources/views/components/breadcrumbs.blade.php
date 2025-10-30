{{-- Breadcrumbs Component
Props: $items (array): ['Home' => '/', 'Articles' => '/articles', 'Current'] --}}
@props(['items'])
<nav {{ $attributes->merge(['class' => 'flex items-center gap-2 text-sm']) }}
     :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
     aria-label="{{ __('site.breadcrumbs') }}">
    @foreach($items as $label => $url)
        @if($loop->last)
            <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $label }}</span>
        @else
            <a href="{{ $url }}" class="hover:text-[#64CCC5] transition">{{ $label }}</a>
            <i class="w-4 h-4" data-lucide="chevron-right"></i>
        @endif
    @endforeach
</nav>
