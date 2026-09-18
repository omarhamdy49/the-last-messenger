{{-- Error summary for a failed quick save: each message jumps to its step and field. --}}
<div x-show="errorList.length" x-cloak class="wz-alert" role="alert" aria-live="assertive">
    <p class="wz-alert-title">
        <i data-lucide="alert-octagon" aria-hidden="true"></i>
        <span x-text="@js(__('wizard.errors_title', ['count' => '__N__'])).replace('__N__', errorList.length)"></span>
    </p>
    <ul>
        <template x-for="item in errorList.slice(0, 8)" :key="item.key">
            <li><button type="button" @click="revealError(item)" x-text="item.message"></button></li>
        </template>
    </ul>
</div>
