{{--
    Expandable body text — clamps long passages and reveals a show more/less
    toggle only when the text actually overflows.

    A plain clamp truncates silently; an always-visible toggle lies on short
    passages. This measures the clamped box after layout AND after webfonts
    settle (Amiri changes the line count), so the toggle appears exactly when
    something is hidden.

    Props:
        $lang      — BCP-47 language of the passage
        $dir       — 'rtl' or 'ltr' for this passage. Never inherited: an English
                     line inside the RTL page would flip its punctuation.
        $clamp     — literal Tailwind line-clamp-* utility used while collapsed
        $textClass — typography classes for the passage
        $toneClass — classes for the toggle button
--}}
@props([
    'lang' => null,
    'dir' => 'ltr',
    'clamp' => 'line-clamp-6',
    'textClass' => '',
    'toneClass' => 'text-[#0f7c8c] dark:text-[#64CCC5]',
])

<div x-data="{
        expanded: false,
        overflowing: false,
        measure() {
            // Only the clamped box is measurable — once expanded, scrollHeight
            // equals clientHeight and the toggle would hide itself.
            if (this.expanded) return;
            const el = this.$refs.body;
            if (el) this.overflowing = el.scrollHeight > el.clientHeight + 2;
        },
     }"
     x-init="$nextTick(() => measure()); document.fonts?.ready.then(() => measure())"
     @resize.window.debounce.150ms="measure()">

    <p x-ref="body"
       :class="expanded ? '!line-clamp-none' : ''"
       class="{{ $clamp }} {{ $textClass }}"
       @if($lang) lang="{{ $lang }}" @endif dir="{{ $dir }}">{{ $slot }}</p>

    <button type="button"
            x-cloak
            x-show="expanded || overflowing"
            @click="expanded = ! expanded"
            :aria-expanded="expanded"
            class="mx-auto mt-3 flex h-9 items-center gap-1 rounded-full px-3 text-xs font-bold
                   transition hover:bg-black/5 focus:outline-none focus-visible:ring-2
                   focus-visible:ring-current dark:hover:bg-white/10 {{ $toneClass }}">
        <span x-text="expanded ? @js(__('common.show_less')) : @js(__('common.show_more'))"></span>
        {{-- The rotation binds to this span, not the <i>: lucide swaps the <i>
             for an <svg> at boot and any binding on it would be lost. --}}
        <span class="inline-flex transition-transform" :class="expanded ? 'rotate-180' : ''">
            <i class="h-3.5 w-3.5" data-lucide="chevron-down"></i>
        </span>
    </button>
</div>
