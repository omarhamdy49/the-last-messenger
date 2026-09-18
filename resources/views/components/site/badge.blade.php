@props(['variant' => 'default', 'icon' => null, 'pulse' => false])

@php
    // Grading + Makki/Madani + featured + category variants per spec §13.2, on
    // the design-system chip. The tint carries the meaning: the single accent
    // for classification, amber for "notable", the plain chip for neutral.
    $variants = [
        'default'   => '',
        'sahih'     => '!text-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]',
        'hasan'     => '!text-amber-700 !bg-amber-500/15 dark:!text-amber-300',
        'daif'      => '',
        'makki'     => '!text-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]',
        'madani'    => '!text-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]',
        'featured'  => 'is-active',
        'accent'    => '!text-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]',
        'trending'  => '!text-amber-700 !bg-amber-500/15 dark:!text-amber-300',
    ];
    $classes = 'sx-chip !min-h-0 !py-1 !text-xs !font-bold ' . ($variants[$variant] ?? $variants['default']);
@endphp

<span {{ $attributes->merge(['class' => $classes . ($pulse ? ' animate-pulse-glow' : '')]) }}>
    @if ($icon)
        <i data-lucide="{{ $icon }}" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
