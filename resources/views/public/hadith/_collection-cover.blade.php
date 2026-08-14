{{--
    Book cover for a hadith collection. Shows the uploaded thumb when present,
    otherwise a designed placeholder cover in the brand palette (variant picked
    deterministically by id so each collection keeps its color).

    Expects: $collection, $locale. Parent supplies sizing/rounding via a wrapper
    with aspect-[3/4] (this fills it).
--}}
@php
    $coverVariants = [
        'from-[#0056b3] to-[#218838]',
        'from-[#0f3460] to-[#0056b3]',
        'from-[#218838] to-[#0f3460]',
    ];
    $coverGradient = $coverVariants[$collection->id % count($coverVariants)];
@endphp

@if ($collection->coverUrl())
    <img src="{{ $collection->coverUrl() }}"
         alt="{{ $collection->title($locale) }}"
         loading="lazy" decoding="async"
         class="absolute inset-0 w-full h-full object-cover">
@else
    <div class="absolute inset-0 bg-gradient-to-br {{ $coverGradient }} text-white">
        {{-- spine --}}
        <span aria-hidden="true" class="absolute inset-y-0 start-0 w-1.5 bg-white/20"></span>
        <span aria-hidden="true" class="absolute inset-y-0 start-1.5 w-px bg-black/20"></span>
        {{-- double frame, classic book-cover style --}}
        <span aria-hidden="true" class="absolute inset-2.5 rounded-lg border border-white/30"></span>
        <span aria-hidden="true" class="absolute inset-[13px] rounded-md border border-white/10"></span>
        {{-- subtle texture --}}
        <span aria-hidden="true" class="absolute inset-0 opacity-[0.08]"
              style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:16px 16px"></span>

        <span class="relative h-full flex flex-col items-center justify-center gap-3 px-5 text-center">
            <i data-lucide="book-open-text" class="w-6 h-6 text-white/70"></i>
            <span class="font-arabic font-extrabold text-lg sm:text-xl leading-snug drop-shadow-sm line-clamp-3">
                {{ $collection->title($locale) }}
            </span>
            <span class="w-8 h-px bg-white/40"></span>
            <span class="text-[10px] font-bold tracking-widest text-white/70">{{ __('hadith.hadith') }}</span>
        </span>
    </div>
@endif
