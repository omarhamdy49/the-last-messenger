@props(['variant' => 'default', 'icon' => null, 'pulse' => false])

@php
    // Grading + Makki/Madani + featured + category variants per spec §13.2.
    $variants = [
        'default'   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        'sahih'     => 'bg-secondary/15 text-secondary',
        'hasan'     => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'daif'      => 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        'makki'     => 'bg-primary/15 text-primary',
        'madani'    => 'bg-secondary/15 text-secondary',
        'featured'  => 'bg-gradient-to-l from-primary to-secondary text-white',
        'accent'    => 'bg-accent/20 text-accent-700 dark:text-accent',
        'trending'  => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
    ];
    $classes = $variants[$variant] ?? $variants['default'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold $classes" . ($pulse ? ' animate-pulse-glow' : '')]) }}>
    @if ($icon)
        <i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>
    @endif
    {{ $slot }}
</span>
