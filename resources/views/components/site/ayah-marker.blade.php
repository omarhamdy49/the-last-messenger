{{--
    End-of-ayah medallion carrying the verse number, in the mushaf convention.
    Rides with the Arabic ayah wherever that lands — main line or sub line.

    Props:
        $number — verse number
        $tone   — literal Tailwind text-* classes for the mark
--}}
@props([
    'number',
    'tone' => 'text-[#0f7c8c] dark:text-[#64CCC5]',
])

<span class="relative mx-1 inline-flex h-8 w-8 shrink-0 items-center justify-center align-middle">
    <svg class="absolute inset-0 h-8 w-8 {{ $tone }}" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect x="7" y="7" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1" opacity=".7"/>
        <rect x="7" y="7" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1" opacity=".7"
              transform="rotate(45 16 16)"/>
    </svg>
    <span class="relative font-sans text-[11px] font-bold {{ $tone }}">{{ $number }}</span>
</span>
