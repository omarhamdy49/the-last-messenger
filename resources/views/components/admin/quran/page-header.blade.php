{{-- Quran admin page header: breadcrumbs, gradient title, subtitle and action slot. --}}
@props(['title', 'icon' => 'book-open', 'subtitle' => null, 'crumbs' => [], 'image' => null])
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        @if ($crumbs !== [])
            <nav class="flex flex-wrap items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                @foreach ($crumbs as $label => $url)
                    <a href="{{ $url }}" class="hover:text-accent transition-colors">{{ $label }}</a>
                    <i data-lucide="chevron-left" class="w-4 h-4 rtl:block ltr:hidden"></i>
                    <i data-lucide="chevron-right" class="w-4 h-4 ltr:block rtl:hidden"></i>
                @endforeach
                <span>{{ $title }}</span>
            </nav>
        @endif
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            @if ($image)
                {{-- The initial sits under the photo, so a broken image still reads as a person. --}}
                <span class="relative grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-gradient-to-br from-[#0056b3] to-[#218838] text-xl font-bold text-white shadow-lg">
                    {{ mb_substr($title, 0, 1) }}
                    <img src="{{ $image }}" alt="" width="56" height="56" decoding="async" onerror="this.remove()" class="absolute inset-0 h-full w-full object-cover">
                </span>
            @else
                <span class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                    <i data-lucide="{{ $icon }}" class="w-7 h-7 text-white"></i>
                </span>
            @endif
            {{ $title }}
        </h1>
        @if ($subtitle)
            <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim((string) $slot) !== '')
        <div class="flex flex-wrap items-center gap-3">{{ $slot }}</div>
    @endif
</div>
