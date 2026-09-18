{{--
    Empty state on the public design system.

    Deliberately paints no surface of its own: most callers drop it *inside* a
    `.sx-surface` panel, and a card inside a card is the one thing the system
    forbids. A caller that needs the paper adds `class="sx-surface"`.
--}}
@props(['icon' => 'inbox', 'title' => null, 'message' => null, 'showReset' => false])

<div {{ $attributes->merge(['class' => 'sx-empty']) }}>
    <span class="sx-empty-icon"><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span>
    <h3 class="text-lg font-extrabold text-[color:var(--sx-ink)]">{{ $title ?? __('site.no_results') }}</h3>
    {{-- An explicit `message=""` drops the line; omitting it keeps the default. --}}
    @if ($message !== '')
        <p class="max-w-sm text-sm leading-relaxed text-[color:var(--sx-muted)]">{{ $message ?? __('site.try_adjusting_filters') }}</p>
    @endif
    @if ($showReset)
        <button type="button" {{ $attributes->only('@click') }} class="sx-btn">
            <i data-lucide="rotate-ccw" aria-hidden="true"></i>
            {{ __('site.reset_filters') }}
        </button>
    @endif
    {{ $slot }}
</div>
