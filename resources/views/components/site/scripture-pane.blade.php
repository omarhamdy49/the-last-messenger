{{--
    Scripture pane — the "paper" reading surface that sits inside a daily panel.

    A flat `.sx-panel`: panels inside a surface are flat, so a card never nests
    inside a card. Both themes come from the design tokens, so callers never
    have to remember a dark variant.

    Props:
        $tone — text-* class for the ornamental rule; defaults to the single
                accent. Pass a token-based class, never a literal hue.
--}}
@props(['tone' => 'text-[color:var(--sx-accent)]'])

<div {{ $attributes->class('sx-panel relative overflow-hidden p-5 sm:p-7') }}>
    {{-- Manuscript head rule: two hairlines meeting a diamond. --}}
    <svg class="mx-auto mb-5 h-3 w-28 {{ $tone }}" viewBox="0 0 112 12" fill="none" aria-hidden="true">
        <path d="M2 6h38M72 6h38" stroke="currentColor" stroke-width="1" opacity=".35"/>
        <path d="M56 1.2 60.8 6 56 10.8 51.2 6z" stroke="currentColor" stroke-width="1.2"/>
        <circle cx="45" cy="6" r="1.5" fill="currentColor" opacity=".6"/>
        <circle cx="67" cy="6" r="1.5" fill="currentColor" opacity=".6"/>
    </svg>

    {{ $slot }}
</div>
