{{--
    Sticky command bar: step navigation, live save status, quick save (Ctrl/Cmd+S) and the
    regular submit (save and close). Must sit inside the <form>.

    @props previewUrl (nullable), total (step count)
--}}
@props(['previewUrl' => null, 'total' => 0])

<div class="wz-commandbar" role="region" aria-label="{{ __('wizard.sections') }}">
    <div class="wz-cb-group">
        <button type="button" class="wz-btn is-ghost" @click="prevStep()" :disabled="isFirstStep()" aria-label="{{ __('wizard.prev') }}">
            <i data-lucide="arrow-right" class="rtl:inline ltr:hidden" aria-hidden="true"></i>
            <i data-lucide="arrow-left" class="ltr:inline rtl:hidden" aria-hidden="true"></i>
            <span class="wz-cb-label">{{ __('wizard.prev') }}</span>
        </button>
        <span class="wz-cb-count" x-text="`${stepIndex() + 1} / {{ $total }}`"></span>
        <button type="button" class="wz-btn" @click="nextStep()" :disabled="isLastStep()" aria-label="{{ __('wizard.next') }}">
            <span class="wz-cb-label">{{ __('wizard.next') }}</span>
            <i data-lucide="arrow-left" class="rtl:inline ltr:hidden" aria-hidden="true"></i>
            <i data-lucide="arrow-right" class="ltr:inline rtl:hidden" aria-hidden="true"></i>
        </button>
    </div>

    <div class="wz-status" :class="'is-' + statusTone()" aria-live="polite">
        <span class="wz-status-dot" aria-hidden="true"></span>
        <span x-text="statusLabel()">{{ __('wizard.no_changes') }}</span>
    </div>

    <div class="wz-cb-group justify-end">
        @if($previewUrl)
            <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="wz-btn is-ghost">
                <i data-lucide="eye" aria-hidden="true"></i><span class="hidden sm:inline">{{ __('wizard.preview') }}</span>
            </a>
        @endif
        <button type="button" class="wz-btn is-soft" @click="quickSave()" :disabled="!canQuickSave()" :aria-busy="saving">
            <i data-lucide="zap" aria-hidden="true"></i>
            <span>{{ __('wizard.quick_save') }}</span>
            <kbd class="wz-kbd">⌘S</kbd>
        </button>
        <button type="submit" class="wz-btn is-primary" :disabled="saving">
            <i data-lucide="check" aria-hidden="true"></i>{{ __('wizard.save_exit') }}
        </button>
    </div>
</div>
