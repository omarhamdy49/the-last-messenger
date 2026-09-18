{{-- @props icon, title, hint, actionUrl, actionLabel --}}
@props(['icon' => 'search-x', 'title', 'hint' => null, 'actionUrl' => null, 'actionLabel' => null])

<div {{ $attributes->class('sx-surface sx-empty') }}>
    <span class="sx-empty-icon"><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span>
    <h2 class="text-xl font-extrabold text-[color:var(--sx-ink)]">{{ $title }}</h2>
    @if ($hint)<p class="max-w-md text-[color:var(--sx-muted)] leading-relaxed">{{ $hint }}</p>@endif
    @if ($actionUrl)
        <a href="{{ $actionUrl }}" class="sx-btn is-primary mt-2"><i data-lucide="rotate-ccw" aria-hidden="true"></i>{{ $actionLabel }}</a>
    @endif
</div>
