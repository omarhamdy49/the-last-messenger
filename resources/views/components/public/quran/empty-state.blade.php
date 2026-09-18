{{--
    Empty State Component

    Displays a message when no verses are available.

    Props:
    - $icon: Lucide icon name (default: 'book-x')
    - $message: Message to display (default: from translation key)

    Expected Alpine.js context:
    - verses: array of verses
--}}

@props([
    'icon' => 'book-x',
    'message' => null
])

<x-public.editorial.empty-state
    x-show="verses.length === 0"
    :icon="$icon"
    :title="$message ?? __('quran.empty.no_verses')"
    class="mb-12"
/>
