{{--
    One wizard step. `data-wizard-step` is how quick-save errors are routed back to a step.

    @props key, index (1-based), total, icon, title, description
    @slots default, actions (optional, rendered in the header)
--}}
@props(['key', 'index', 'total', 'icon' => 'circle', 'title', 'description' => null])

<section x-show="step === '{{ $key }}'" x-cloak data-wizard-step="{{ $key }}" id="step-panel-{{ $key }}"
         role="region" aria-labelledby="step-title-{{ $key }}" {{ $attributes->merge(['class' => 'wz-card wz-panel']) }}>
    <div class="wz-panel-head">
        <div class="min-w-0">
            <p class="wz-panel-kicker">{{ __('wizard.step_of', ['current' => $index, 'total' => $total]) }}</p>
            <h2 id="step-title-{{ $key }}" class="wz-panel-title flex items-center gap-2">
                <i data-lucide="{{ $icon }}" class="w-5 h-5 text-[color:var(--wz-accent)]" aria-hidden="true"></i>{{ $title }}
            </h2>
            @if($description)<p class="wz-panel-desc">{{ $description }}</p>@endif
        </div>
        @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
    </div>
    <div class="wz-panel-body">
        {{ $slot }}
    </div>
</section>
