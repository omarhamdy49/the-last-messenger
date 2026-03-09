{{--
    Global Toast Notification Container
    Usage from Alpine.js:
        $dispatch('show-toast', { message: 'Success!', type: 'success' })
        $dispatch('show-toast', { message: 'Error!', type: 'error' })
        $dispatch('show-toast', { message: 'Warning!', type: 'warning' })
    Usage from JS:
        window.showToast('Success!', 'success')
        window.showToast('Error!', 'error')
--}}
<div x-data="toastManager()" x-on:show-toast.window="addToast($event.detail)" class="fixed top-4 end-4 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm w-full">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-8"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-8"
             class="pointer-events-auto rounded-xl shadow-xl border backdrop-blur-sm flex items-start gap-3 p-4"
             :class="{
                'bg-green-50 border-green-200 dark:bg-green-900/30 dark:border-green-800': toast.type === 'success',
                'bg-red-50 border-red-200 dark:bg-red-900/30 dark:border-red-800': toast.type === 'error',
                'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/30 dark:border-yellow-800': toast.type === 'warning',
                'bg-blue-50 border-blue-200 dark:bg-blue-900/30 dark:border-blue-800': toast.type === 'info'
             }">
            <!-- Icon -->
            <div class="flex-shrink-0 w-6 h-6 mt-0.5">
                <template x-if="toast.type === 'success'">
                    <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
                <template x-if="toast.type === 'warning'">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
            </div>

            <!-- Message -->
            <p class="flex-1 text-sm font-medium"
               :class="{
                   'text-green-800 dark:text-green-200': toast.type === 'success',
                   'text-red-800 dark:text-red-200': toast.type === 'error',
                   'text-yellow-800 dark:text-yellow-200': toast.type === 'warning',
                   'text-blue-800 dark:text-blue-200': toast.type === 'info'
               }"
               x-text="toast.message"></p>

            <!-- Close -->
            <button @click="removeToast(toast.id)" class="flex-shrink-0 p-0.5 rounded-lg transition-colors"
                    :class="{
                        'text-green-400 hover:text-green-600 dark:text-green-500 dark:hover:text-green-300': toast.type === 'success',
                        'text-red-400 hover:text-red-600 dark:text-red-500 dark:hover:text-red-300': toast.type === 'error',
                        'text-yellow-400 hover:text-yellow-600 dark:text-yellow-500 dark:hover:text-yellow-300': toast.type === 'warning',
                        'text-blue-400 hover:text-blue-600 dark:text-blue-500 dark:hover:text-blue-300': toast.type === 'info'
                    }">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

<script>
function toastManager() {
    return {
        toasts: [],
        addToast(detail) {
            const toast = {
                id: Date.now() + Math.random(),
                message: detail.message || '',
                type: detail.type || 'info',
                visible: true,
            };
            this.toasts.push(toast);
            setTimeout(() => this.removeToast(toast.id), detail.duration || 4000);
        },
        removeToast(id) {
            const t = this.toasts.find(t => t.id === id);
            if (t) t.visible = false;
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 300);
        }
    };
}

// Global helper
window.showToast = function(message, type = 'info', duration = 4000) {
    window.dispatchEvent(new CustomEvent('show-toast', { detail: { message, type, duration } }));
};
</script>
