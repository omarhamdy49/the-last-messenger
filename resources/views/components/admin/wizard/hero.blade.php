{{--
    Wizard hero: live title, meta pills, completion ring and the clickable stepper.
    Lives inside the component that spreads adminWizard(), so it reads step state directly.

    @props eyebrow, icon, titleExpr (Alpine expression for the live title), steps (for the stepper)
--}}
@props(['eyebrow', 'icon' => 'sparkles', 'titleExpr' => "''", 'steps' => []])

<header class="wz-card wz-hero">
    <div class="wz-hero-top">
        <div class="min-w-0">
            <span class="wz-eyebrow"><i data-lucide="{{ $icon }}" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $eyebrow }}</span>
            <h1 class="wz-title" :class="{ 'is-placeholder': !({{ $titleExpr }}) }"
                x-text="({{ $titleExpr }}) || @js(__('wizard.untitled'))">{{ __('wizard.untitled') }}</h1>
            <div class="wz-meta">
                {{ $meta ?? '' }}
            </div>
        </div>

        <div class="wz-progress-ring" role="img" :aria-label="`{{ __('wizard.progress') }} ${Math.round(progress() * 100)}%`">
            <svg viewBox="0 0 36 36" aria-hidden="true">
                <circle class="track" cx="18" cy="18" r="15.5" fill="none" stroke-width="3"></circle>
                <circle class="value" cx="18" cy="18" r="15.5" fill="none" stroke-width="3"
                        stroke-dasharray="97.4" :stroke-dashoffset="97.4 - 97.4 * progress()"></circle>
            </svg>
            <div class="wz-progress-label">
                <strong x-text="`${Math.round(progress() * 100)}%`">0%</strong>
                {{ __('wizard.progress') }}
            </div>
        </div>
    </div>

    <x-admin.wizard.stepper :steps="$steps" />
</header>
