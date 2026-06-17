{{-- Quran-on-rehal (open book on a stand) glyph. Inherits color via currentColor. --}}
<svg {{ $attributes->merge(['class' => 'w-6 h-6']) }} viewBox="0 0 512 512"
     xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    <g fill="currentColor">
        {{-- Left page --}}
        <path d="M256 302 L247 142 C201 121 142 112 96 114 C87 115 81 120 78 129 L45 248 C42 258 48 267 58 270 C123 287 199 296 256 302 Z"/>
        {{-- Right page --}}
        <path d="M256 302 L265 142 C311 121 370 112 416 114 C425 115 431 120 434 129 L467 248 C470 258 464 267 454 270 C389 287 313 296 256 302 Z"/>
        {{-- Center holder tabs --}}
        <path d="M250 312 L232 336 L252 330 Z"/>
        <path d="M262 312 L280 336 L260 330 Z"/>
    </g>
    {{-- X-shaped stand --}}
    <g stroke="currentColor" stroke-width="34" stroke-linecap="round" fill="none">
        <path d="M202 322 L360 410"/>
        <path d="M310 322 L152 410"/>
    </g>
</svg>
