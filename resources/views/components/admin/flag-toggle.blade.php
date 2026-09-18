{{--
    One flag, shown as its current state and flipped by pressing it.

    A real PATCH form, not a JSON fetch: method spoofing from a JSON body is
    ignored by Symfony, so those requests stay POST and 405 (see
    `admin.hadith-status-toggle`, which this generalises).

    `value` is the state the press writes, not a flip of what the server reads,
    so a double submit is idempotent.

    @props
    - action:   string  route to PATCH
    - on:       bool    current state
    - onLabel / offLabel:  string
    - onIcon  / offIcon:   Lucide icon name
    - tone:     'emerald'|'amber'|'slate'  colour when on
    - size:     'sm'|'md'
    - disabled: bool    render as a static pill with no form
    - title:    ?string tooltip / aria description
--}}
@props([
    'action',
    'on' => false,
    'onLabel' => '',
    'offLabel' => '',
    'onIcon' => 'check-circle',
    'offIcon' => 'circle',
    'tone' => 'emerald',
    'size' => 'sm',
    'disabled' => false,
    'title' => null,
])

@php
    // Tailwind compiles from source, never from a runtime value, so every tone
    // has to exist here as a literal class string.
    $tones = [
        'emerald' => [
            'light' => 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100',
            'dark' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/25',
        ],
        'amber' => [
            'light' => 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100',
            'dark' => 'bg-amber-500/15 text-amber-300 border-amber-500/30 hover:bg-amber-500/25',
        ],
        'slate' => [
            'light' => 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200',
            'dark' => 'bg-slate-500/20 text-slate-300 border-slate-500/30 hover:bg-slate-500/30',
        ],
    ];

    $offClasses = [
        'light' => 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50',
        'dark' => 'bg-gray-800/60 text-gray-400 border-gray-700 hover:bg-gray-800',
    ];

    $palette = $on ? ($tones[$tone] ?? $tones['emerald']) : $offClasses;

    $sizing = $size === 'md'
        ? 'px-3.5 py-2 text-sm gap-2'
        : 'px-2.5 py-1 text-xs gap-1.5';

    $iconSize = $size === 'md' ? 'w-4 h-4' : 'w-3.5 h-3.5';
    $label = $on ? $onLabel : $offLabel;
@endphp

@if ($disabled)
    <span class="inline-flex items-center rounded-lg border font-semibold {{ $sizing }}"
          :class="darkMode ? '{{ $palette['dark'] }}' : '{{ $palette['light'] }}'"
          @if ($title) title="{{ $title }}" @endif>
        <i data-lucide="{{ $on ? $onIcon : $offIcon }}" class="{{ $iconSize }}"></i>
        <span>{{ $label }}</span>
    </span>
@else
    <form method="POST" action="{{ $action }}" class="inline-block">
        @csrf
        @method('PATCH')
        {{-- The target state, so pressing twice is not a flip-flop. --}}
        <input type="hidden" name="value" value="{{ $on ? '0' : '1' }}">
        <button type="submit"
                aria-pressed="{{ $on ? 'true' : 'false' }}"
                @if ($title) title="{{ $title }}" aria-label="{{ $title }}" @endif
                class="inline-flex items-center rounded-lg border font-semibold transition-colors
                       focus:outline-none focus-visible:ring-2 focus-visible:ring-accent {{ $sizing }}"
                :class="darkMode ? '{{ $palette['dark'] }}' : '{{ $palette['light'] }}'"
                {{ $attributes }}>
            <i data-lucide="{{ $on ? $onIcon : $offIcon }}" class="{{ $iconSize }}"></i>
            <span>{{ $label }}</span>
        </button>
    </form>
@endif
