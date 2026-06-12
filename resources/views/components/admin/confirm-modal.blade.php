{{--
    Global Confirm Modal
    Replaces native confirm() dialogs.

    Usage from Alpine:
        $dispatch('show-confirm', {
            title: 'Delete Item?',
            message: 'This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            type: 'danger',          // 'danger' | 'warning' | 'info'
            onConfirm: () => { ... } // callback on confirm
        })

    Usage from JS:
        window.showConfirm({
            title: 'Delete?',
            message: 'Are you sure?',
            type: 'danger',
            onConfirm: () => formEl.submit()
        })

    For forms with onsubmit:
        <form @submit.prevent="$dispatch('show-confirm', {
            title: '...', message: '...', type: 'danger',
            onConfirm: () => $el.submit()
        })">
--}}
<div x-data="confirmModal()" x-on:show-confirm.window="open($event.detail)" x-cloak>
    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[9998]"
         @click="cancel">
    </div>

    <!-- Modal -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
         @keydown.escape.window="cancel">
        <div class="w-full max-w-md rounded-2xl shadow-2xl border p-6"
             :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'"
             @click.stop>

            <!-- Icon -->
            <div class="mx-auto w-14 h-14 rounded-full flex items-center justify-center mb-4"
                 :class="{
                     'bg-red-100 dark:bg-red-900/30': modalType === 'danger',
                     'bg-yellow-100 dark:bg-yellow-900/30': modalType === 'warning',
                     'bg-blue-100 dark:bg-blue-900/30': modalType === 'info'
                 }">
                <template x-if="modalType === 'danger'">
                    <svg class="w-7 h-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </template>
                <template x-if="modalType === 'warning'">
                    <svg class="w-7 h-7 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </template>
                <template x-if="modalType === 'info'">
                    <svg class="w-7 h-7 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
            </div>

            <!-- Title -->
            <h3 class="text-lg font-bold text-center mb-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'"
                x-text="title"></h3>

            <!-- Message -->
            <p class="text-sm text-center mb-6"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
               x-text="message"></p>

            <!-- Actions -->
            <div class="flex gap-3">
                <button @click="cancel" type="button"
                        class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                        :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'"
                        x-text="cancelText"></button>
                <button @click="confirm" type="button"
                        class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 text-white"
                        :class="{
                            'bg-red-600 hover:bg-red-700': modalType === 'danger',
                            'bg-yellow-500 hover:bg-yellow-600': modalType === 'warning',
                            'bg-blue-600 hover:bg-blue-700': modalType === 'info'
                        }"
                        x-text="confirmText"></button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmModal() {
    return {
        isOpen: false,
        title: '',
        message: '',
        confirmText: '{{ __("admin.confirm") ?? "تأكيد" }}',
        cancelText: '{{ __("admin.cancel") ?? "إلغاء" }}',
        modalType: 'danger',
        onConfirmCallback: null,

        open(detail) {
            this.title = detail.title || '{{ __("admin.confirm_delete") ?? "هل أنت متأكد؟" }}';
            this.message = detail.message || '';
            this.confirmText = detail.confirmText || '{{ __("admin.confirm") ?? "تأكيد" }}';
            this.cancelText = detail.cancelText || '{{ __("admin.cancel") ?? "إلغاء" }}';
            this.modalType = detail.type || 'danger';
            this.onConfirmCallback = detail.onConfirm || null;
            this.isOpen = true;
        },

        confirm() {
            this.isOpen = false;
            if (this.onConfirmCallback) {
                this.onConfirmCallback();
            }
        },

        cancel() {
            this.isOpen = false;
            this.onConfirmCallback = null;
        }
    };
}

// Global helper
window.showConfirm = function(options) {
    window.dispatchEvent(new CustomEvent('show-confirm', { detail: options }));
};

// Form-submit guard replacing native confirm():
//   <form onsubmit="return window.confirmSubmit(this, { message: '...' })">
// form.submit() bypasses onsubmit, so confirming can't re-trigger the dialog.
window.confirmSubmit = function(form, options = {}) {
    window.showConfirm({
        title: options.title,
        message: options.message,
        confirmText: options.confirmText,
        cancelText: options.cancelText,
        type: options.type || 'danger',
        onConfirm: () => form.submit(),
    });
    return false;
};
</script>
