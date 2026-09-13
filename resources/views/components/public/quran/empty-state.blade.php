{{--
    Empty State Component

    Displays a message when no verses are available.

    Props:
    - $icon: Lucide icon name (default: 'book-x')
    - $message: Message to display (default: from translation key)

    Expected Alpine.js context:
    - verses: array of verses
    - $store.theme.dark: boolean for dark mode
--}}

@props([
    'icon' => 'book-x',
    'message' => null
])

<div x-show="verses.length === 0" class="text-center py-16">
    <i data-lucide="{{ $icon }}" class="w-16 h-16 mx-auto mb-4 text-gray-400"></i>
    <p class="text-xl" :class="$store.theme.dark ? 'text-gray-400' : 'text-gray-600'">
        {{ $message ?? __('quran.empty.no_verses') }}
    </p>
</div>
