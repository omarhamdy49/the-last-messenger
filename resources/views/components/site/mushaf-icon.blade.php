{{--
    Mushaf icon — an open Qur'an resting on a rehl (reading stand).

    Lucide has no Qur'an glyph and the codebase falls back to `book-open`, which
    the Seerah cards already use. Drawn to Lucide's conventions (24 viewBox,
    currentColor stroke, round caps/joins) so it sits consistently beside the
    lucide icons in the same row.
--}}
<svg {{ $attributes->merge(['class' => 'h-6 w-6']) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {{-- Spine --}}
    <path d="M12 7.6v8.6"/>
    {{-- Left leaf --}}
    <path d="M12 7.6C10.1 6.2 7.5 5.5 4.4 5.7a.85.85 0 0 0-.8.85v7.1c0 .48.39.86.87.84 2.6-.1 5 .53 7.53 1.71"/>
    {{-- Right leaf --}}
    <path d="M12 7.6c1.9-1.4 4.5-2.1 7.6-1.9.46.03.8.4.8.85v7.1a.85.85 0 0 1-.87.84c-2.6-.1-5 .53-7.53 1.71"/>
    {{-- Rehl: two legs and a foot --}}
    <path d="M8.4 17.1 5.8 20.9M15.6 17.1l2.6 3.8M6.6 20.9h10.8"/>
</svg>
