{{-- Toast Notification Component --}}
{{-- Usage: Include this component in your layout, it will automatically handle Laravel session messages --}}

<div x-data="toastManager()" x-init="init()" @toast.window="addToast($event.detail)" class="fixed top-20 start-4 z-50 space-y-3 max-w-md">
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="transform -translate-x-full opacity-0"
            x-transition:enter-end="transform translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="transform translate-x-0 opacity-100"
            x-transition:leave-end="transform -translate-x-full opacity-0"
            class="glass-dark backdrop-blur-lg px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 border"
            :class="{
                'bg-green-500/90 border-green-400/50': toast.type === 'success',
                'bg-red-500/90 border-red-400/50': toast.type === 'error',
                'bg-yellow-500/90 border-yellow-400/50': toast.type === 'warning',
                'bg-blue-500/90 border-blue-400/50': toast.type === 'info'
            }"
            role="alert"
            :aria-label="toast.message"
        >
            <!-- Icon -->
            <div class="flex-shrink-0">
                <template x-if="toast.type === 'success'">
                    <i data-lucide="check-circle" class="w-6 h-6 text-white"></i>
                </template>
                <template x-if="toast.type === 'error'">
                    <i data-lucide="alert-circle" class="w-6 h-6 text-white"></i>
                </template>
                <template x-if="toast.type === 'warning'">
                    <i data-lucide="alert-triangle" class="w-6 h-6 text-white"></i>
                </template>
                <template x-if="toast.type === 'info'">
                    <i data-lucide="info" class="w-6 h-6 text-white"></i>
                </template>
            </div>

            <!-- Message -->
            <div class="flex-1 text-white">
                <p class="font-semibold text-sm leading-relaxed" x-text="toast.message"></p>
            </div>

            <!-- Close Button -->
            <button
                @click="removeToast(toast.id)"
                class="flex-shrink-0 text-white hover:text-gray-200 transition-colors focus:outline-none focus:ring-2 focus:ring-white/50 rounded-lg p-1"
                :aria-label="toast.type === 'success' ? 'إغلاق رسالة النجاح' : 'إغلاق الرسالة'"
            >
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
    </template>
</div>

<script>
    // Global toast function for easy access
    window.showToast = function(message, type = 'success') {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message, type }
        }));
    };

    function toastManager() {
        return {
            toasts: [],
            nextId: 1,

            init() {
                // Check for Laravel session messages
                @if(session('success'))
                    this.addToast({ message: "{{ session('success') }}", type: 'success' });
                @endif

                @if(session('error'))
                    this.addToast({ message: "{{ session('error') }}", type: 'error' });
                @endif

                @if(session('warning'))
                    this.addToast({ message: "{{ session('warning') }}", type: 'warning' });
                @endif

                @if(session('info'))
                    this.addToast({ message: "{{ session('info') }}", type: 'info' });
                @endif

                // Reinitialize Lucide icons after Alpine renders
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            addToast(detail) {
                const id = this.nextId++;
                const toast = {
                    id,
                    message: detail.message || '',
                    type: detail.type || 'success',
                    visible: true
                };

                this.toasts.push(toast);

                // Reinitialize Lucide icons
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });

                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    this.removeToast(id);
                }, 5000);
            },

            removeToast(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.toasts[index].visible = false;
                    // Remove from array after animation completes
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 200);
                }
            }
        };
    }
</script>

<style>
    /* RTL Support for toast animations */
    [dir="rtl"] .start-4 {
        right: 1rem;
        left: auto;
    }

    [dir="ltr"] .start-4 {
        left: 1rem;
        right: auto;
    }
</style>
