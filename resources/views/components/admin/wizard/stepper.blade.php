{{--
    Clickable stepper. Every step is reachable at any time; state comes from adminWizard():
    current / done (check) / error (count) / idle.

    @props steps: array<string, array{0: string icon, 1: string label, 2: string hint}>
--}}
@props(['steps' => []])

<nav class="wz-stepper" aria-label="{{ __('wizard.sections') }}">
    <ol class="wz-steps" role="list">
        @foreach($steps as $key => [$icon, $label, $hint])
            <li>
                <button type="button" class="wz-step" :class="'is-' + stepStatus('{{ $key }}')"
                        @click="goTo('{{ $key }}')"
                        :aria-current="step === '{{ $key }}' ? 'step' : null"
                        :title="stepErrorCount('{{ $key }}') ? @js(__('wizard.fix_errors')) : null">
                    <span class="wz-step-index" aria-hidden="true">
                        <span class="wz-step-num">{{ $loop->iteration }}</span>
                        <span class="wz-step-check"><i data-lucide="check" class="w-4 h-4"></i></span>
                        <span class="wz-step-err" x-text="stepErrorCount('{{ $key }}')"></span>
                    </span>
                    <span class="wz-step-text">
                        <span class="wz-step-label">{{ $label }}</span>
                        <span class="wz-step-hint">{{ $hint }}</span>
                    </span>
                </button>
            </li>
        @endforeach
    </ol>
</nav>
