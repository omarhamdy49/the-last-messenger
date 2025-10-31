{{-- Confirmation Dialog Component --}}
{{-- Usage: Call window.showConfirm(message, onConfirm, onCancel) --}}

<div x-data="confirmDialog()" x-init="init()" @confirm.window="show($event.detail)" class="fixed inset-0 z-40 pointer-events-none" :class="visible ? 'pointer-events-auto' : ''" style="opacity: 0; transition: opacity 0.3s ease-in-out;" :style="visible ? 'opacity: 1;' : 'opacity: 0;'">
    <!-- Backdrop -->
    <div
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="cancel()"
        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
        :class="!visible ? 'pointer-events-none' : ''"
    ></div>

    <!-- Dialog Container -->
    <div
        x-show="visible"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="transform -translate-y-full opacity-0"
        x-transition:enter-end="transform translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="transform translate-y-0 opacity-100"
        x-transition:leave-end="transform -translate-y-full opacity-0"
        class="absolute inset-0 flex items-center justify-center pointer-events-none"
    >
        <!-- Dialog Panel -->
        <div class="glass-dark backdrop-blur-xl max-w-md w-full mx-4 rounded-2xl shadow-2xl border border-white/20 p-6 pointer-events-auto">
            <!-- Icon -->
            <div class="flex justify-center mb-4">
                <div class="w-16 h-16 rounded-full bg-red-500/20 border border-red-500/40 flex items-center justify-center">
                    <i data-lucide="alert-circle" class="w-8 h-8 text-red-500"></i>
                </div>
            </div>

            <!-- Title -->
            <h3 class="text-xl font-bold text-white text-center mb-2" x-text="title"></h3>

            <!-- Message -->
            <p class="text-gray-300 text-center text-sm mb-6" x-text="message"></p>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <!-- Cancel Button -->
                <button
                    @click="cancel()"
                    class="flex-1 px-4 py-3 rounded-lg font-semibold text-gray-300 bg-gray-700/50 hover:bg-gray-700 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500"
                >
                    إلغاء
                </button>

                <!-- Confirm Button -->
                <button
                    @click="confirm()"
                    :disabled="isLoading"
                    class="flex-1 px-4 py-3 rounded-lg font-semibold text-white bg-red-600 hover:bg-red-700 disabled:bg-red-600/50 disabled:cursor-not-allowed transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 flex items-center justify-center gap-2"
                >
                    <template x-if="!isLoading">
                        <span>تأكيد الحذف</span>
                    </template>
                    <template x-if="isLoading">
                        <div class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        <span>جاري الحذف...</span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDialog() {
        return {
            visible: false,
            title: 'تأكيد',
            message: '',
            isLoading: false,
            onConfirm: null,
            onCancel: null,

            init() {
                // Ensure visible is false on init
                this.visible = false;
                // Make function globally available
                window.showConfirm = this.showDialog.bind(this);
            },

            showDialog(config) {
                this.title = config.title || 'تأكيد';
                this.message = config.message || 'هل أنت متأكد؟';
                this.onConfirm = config.onConfirm || null;
                this.onCancel = config.onCancel || null;
                this.isLoading = false;
                this.visible = true;

                // Reinitialize icons
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            async confirm() {
                if (this.isLoading || !this.onConfirm) return;

                this.isLoading = true;

                try {
                    const result = await Promise.resolve(this.onConfirm());
                    if (result !== false) {
                        this.visible = false;
                    }
                } catch (error) {
                    console.error('Error in confirm callback:', error);
                } finally {
                    this.isLoading = false;
                }
            },

            cancel() {
                if (this.isLoading) return;
                this.visible = false;
                if (this.onCancel) {
                    this.onCancel();
                }
            }
        };
    }
</script>

<style>
    /* RTL Support for backdrop */
    [dir="rtl"] .confirm-dialog-container {
        right: 0;
        left: auto;
    }

    [dir="ltr"] .confirm-dialog-container {
        left: 0;
        right: auto;
    }
</style>
