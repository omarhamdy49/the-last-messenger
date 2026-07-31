{{--
    Scripture pane — the "paper" reading surface that sits inside a daily panel.

    Replaces the previous flat `bg-white` slab, which stayed pure white in dark
    mode and blew out against the dark shell. Both themes are defined here so
    callers never have to remember the dark variant.

    Props:
        $tone — literal Tailwind text-* class for the ornamental rule; pass the
                caller's AA-safe ink colour (the deep hue, not the light accent).
--}}
@props(['tone' => 'text-[#0f7c8c]'])

<div {{ $attributes->class([
    'relative overflow-hidden rounded-2xl p-6 sm:p-7',
    'bg-gradient-to-b from-white to-[#FBFAF6] dark:from-[#16213e] dark:to-[#131c33]',
    'ring-1 ring-inset ring-black/5 dark:ring-white/10',
    'shadow-[0_10px_30px_-12px_rgba(0,0,0,.35)]',
]) }}>
    {{-- Manuscript head rule: two hairlines meeting a diamond. --}}
    <svg class="mx-auto mb-5 h-3 w-28 {{ $tone }}" viewBox="0 0 112 12" fill="none" aria-hidden="true">
        <path d="M2 6h38M72 6h38" stroke="currentColor" stroke-width="1" opacity=".35"/>
        <path d="M56 1.2 60.8 6 56 10.8 51.2 6z" stroke="currentColor" stroke-width="1.2"/>
        <circle cx="45" cy="6" r="1.5" fill="currentColor" opacity=".6"/>
        <circle cx="67" cy="6" r="1.5" fill="currentColor" opacity=".6"/>
    </svg>

    {{ $slot }}
</div>
