{{--
    Recursive genealogy tree node.

    Props:
        $node  — plain array: id, slug, name, title, type, typeLabel, isRoot, isProphet, gender, children[]
        $depth — current depth (0 = root)
        $index — position among siblings (drives the entrance stagger)

    The whole tree is server-rendered once (three levels, a few dozen nodes) so
    it stays crawlable; Alpine only owns *visibility* — `isOpen(id)` — which is
    what keeps the page from dumping every generation on screen at once.
--}}
@php
    $children   = $node['children'] ?? [];
    $childCount = count($children);
    $isRoot     = ! empty($node['isRoot']) || $depth === 0;
    // The Prophet ﷺ badge is keyed off an EXPLICIT flag, never off being the
    // tree root. Any person can now be the root — labelling whoever sits there
    // as the Prophet is a false claim, not a styling choice.
    $isProphet  = ! empty($node['isProphet']);
    $nodeId     = (int) $node['id'];

    $name = trim((string) ($node['name'] ?? '')) ?: __('genealogy.public.unnamed');

    // Calligraphic monogram — same treatment as the Sahaba medallion card, so
    // the two surfaces read as one system. Leading punctuation is skipped so
    // transliterations like "'Umar" don't render an apostrophe.
    $initial = mb_strtoupper(mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', $name) ?: $name, 0, 1)) ?: '۞';

    // Depth carries meaning in an ancestry chart, so the palette does too. The
    // previous per-index rainbow made generations look unrelated.
    $gradient = match (true) {
        $isRoot      => 'from-[#0056b3] to-[#218838]',
        $depth === 1 => 'from-[#0056b3] to-[#3b82f6]',
        default      => 'from-[#218838] to-[#64CCC5]',
    };

    $subtitle = $isProphet
        ? __('genealogy.public.prophet_relation')
        : (($node['title'] ?? '') ?: ($node['typeLabel'] ?? ''));

    $sizes = match (true) {
        $isRoot      => ['card' => 'w-56 sm:w-60 px-5 pt-6 pb-5', 'avatar' => 'w-20 h-20 sm:w-24 sm:h-24', 'glyph' => 'text-4xl sm:text-5xl', 'name' => 'text-lg sm:text-xl'],
        $depth === 1 => ['card' => 'w-44 px-4 pt-5 pb-4',         'avatar' => 'w-16 h-16',                 'glyph' => 'text-2xl',          'name' => 'text-sm sm:text-base'],
        default      => ['card' => 'w-36 px-3 pt-4 pb-3.5',       'avatar' => 'w-12 h-12',                 'glyph' => 'text-xl',           'name' => 'text-xs sm:text-sm'],
    };
@endphp

<div class="gtree-node">
    {{-- Person card --}}
    <button type="button"
            @click="openPerson({{ $nodeId }})"
            style="--enter-delay: {{ min($depth * 90 + ($index ?? 0) * 45, 700) }}ms"
            title="{{ $name }}"
            class="gtree-card card-enter group relative flex flex-col items-center rounded-2xl
                   bg-white dark:bg-[#16213e] {{ $sizes['card'] }}
                   {{ $isRoot
                        ? 'gtree-card--root border border-transparent ring-2 ring-[#0056b3]/35 dark:ring-[#64CCC5]/35'
                        : 'border border-black/5 dark:border-white/10' }}
                   shadow-[0_10px_30px_-14px_rgba(0,0,0,.28)]
                   hover:shadow-[0_26px_50px_-18px_rgba(0,86,179,.45)]
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]
                   focus-visible:ring-offset-2 focus-visible:ring-offset-[#F8F9FA]
                   dark:focus-visible:ring-offset-[#1a1a2e]">

        {{-- Role badge: only where it means something (the Prophet ﷺ), rather
             than a generic person glyph repeated on every single card. --}}
        @if ($isProphet)
            <span class="absolute -top-2.5 start-1/2 -translate-x-1/2 rtl:translate-x-1/2 z-[2]
                         inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                         text-[10px] font-bold text-white shadow-md
                         bg-gradient-to-l from-amber-500 to-yellow-500">
                <i data-lucide="crown" class="w-3 h-3"></i>
                {{ __('genealogy.public.legend_root') }}
            </span>
        @endif

        {{-- Medallion --}}
        <div class="relative flex items-center justify-center transition-transform duration-500 group-hover:scale-105">
            @unless ($depth >= 2)
                {{-- Geometric medallion, echoing the Sahaba card. Dropped on the
                     smallest tier where it would collapse into noise. --}}
                <svg class="sahaba-medallion absolute {{ $isRoot ? 'w-[7.5rem] h-[7.5rem] sm:w-36 sm:h-36' : 'w-24 h-24' }} text-[#0056b3]/40 dark:text-[#64CCC5]/40"
                     viewBox="0 0 100 100" fill="none" aria-hidden="true">
                    <circle cx="50" cy="50" r="47" stroke="currentColor" stroke-width="0.6" stroke-dasharray="1 3" opacity="0.6"/>
                    <g class="ring-outer" opacity="0.55">
                        <rect x="22" y="22" width="56" height="56" rx="4" stroke="currentColor" stroke-width="0.9"/>
                        <rect x="22" y="22" width="56" height="56" rx="4" stroke="currentColor" stroke-width="0.9" transform="rotate(45 50 50)"/>
                    </g>
                </svg>
            @endunless

            <span class="{{ $sizes['avatar'] }} {{ $isRoot ? 'genealogy-glow' : '' }}
                         rounded-full flex items-center justify-center shadow-lg select-none
                         bg-gradient-to-br {{ $gradient }}
                         ring-2 ring-white/70 dark:ring-white/15">
                <span class="{{ $sizes['glyph'] }} font-bold leading-none text-white"
                      style="text-shadow:0 2px 8px rgba(0,0,0,.28);" aria-hidden="true">{{ $initial }}</span>
            </span>
        </div>

        <h3 class="{{ $sizes['name'] }} mt-3 font-extrabold leading-snug text-center line-clamp-2
                   text-gray-900 dark:text-white">{{ $name }}</h3>

        @if ($subtitle)
            {{-- Two lines on the root: its subtitle is a full honorific
                 ("Seal of the Prophets and Messengers"), which one line eats. --}}
            <p class="mt-1 text-[11px] font-semibold text-center {{ $isRoot ? 'line-clamp-2' : 'line-clamp-1' }}
                      text-[#0f766e] dark:text-[#64CCC5]">{{ $subtitle }}</p>
        @endif
    </button>

    {{-- Descendants toggle. A sibling of the card, not a child of it — nesting
         a button inside a button is invalid and swallows the inner click. --}}
    @if ($childCount)
        <button type="button"
                @click="toggleNode({{ $nodeId }})"
                :aria-expanded="isOpen({{ $nodeId }}) ? 'true' : 'false'"
                :aria-label="isOpen({{ $nodeId }})
                    ? '{{ __('genealogy.public.collapse_branch') }}'
                    : '{{ __('genealogy.public.expand_branch') }}'"
                class="gtree-toggle mt-2.5 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full
                       text-[11px] font-bold transition-all active:scale-95
                       bg-white dark:bg-[#16213e] text-gray-600 dark:text-gray-200
                       border border-gray-200 dark:border-white/15
                       hover:border-[#0056b3]/50 hover:text-[#0056b3] dark:hover:text-[#64CCC5]
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0056b3]">
            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-300"
               :class="isOpen({{ $nodeId }}) ? 'rotate-180' : ''"></i>
            <span class="tabular-nums">{{ trans_choice('genealogy.public.descendants_count', $childCount, ['count' => $childCount]) }}</span>
        </button>

        <div class="gtree-children" x-show="isOpen({{ $nodeId }})" x-collapse.duration.400ms>
            @foreach ($children as $i => $child)
                <div class="gtree-cell">
                    @include('public.genealogy._node', ['node' => $child, 'depth' => $depth + 1, 'index' => $i])
                </div>
            @endforeach
        </div>
    @endif
</div>
