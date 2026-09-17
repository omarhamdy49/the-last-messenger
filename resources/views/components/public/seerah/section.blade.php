{{-- Titled content section. @props id, icon, title, index (chapter number, optional) --}}
@props(['id' => null, 'icon' => 'circle', 'title', 'index' => null])

<section @if ($id) id="{{ $id }}" @endif {{ $attributes->class('sx-section sx-reveal') }}>
    <header class="sx-section-head">
        <span class="sx-section-icon"><i data-lucide="{{ $icon }}" aria-hidden="true"></i></span>
        <div class="min-w-0">
            @if ($index)<p class="sx-section-index">{{ str_pad((string) $index, 2, '0', STR_PAD_LEFT) }}</p>@endif
            <h2 class="sx-section-title">{{ $title }}</h2>
        </div>
    </header>
    {{ $slot }}
</section>
