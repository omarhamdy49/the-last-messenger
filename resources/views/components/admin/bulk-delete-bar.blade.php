{{--
    Floating bulk-delete bar for admin index tables.

    Wrap the table in the Alpine scope this component expects:

        <div x-data="bulkSelect()">
            ... <input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)">
            ... <input type="checkbox" :value="id" x-model.number="selected">
            <x-admin.bulk-delete-bar :action="route('admin.media-programs.bulk-delete')" />
        </div>

    The bar posts the selected ids as `ids[]` and only appears once at least
    one row is selected, so it never covers content it isn't acting on.
--}}
@props([
    'action',
    'confirm' => null,
])

<div x-show="selected.length" x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-3"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-end="opacity-0 translate-y-3"
     class="fixed bottom-6 inset-x-0 z-40 flex justify-center pointer-events-none px-4">
    <div class="pointer-events-auto flex items-center gap-3 rounded-2xl border shadow-2xl px-4 py-3 backdrop-blur"
         :class="darkMode ? 'bg-gray-900/95 border-gray-700' : 'bg-white/95 border-gray-200'"
         role="region" aria-label="{{ __('admin.bulk_actions') }}">
        <span class="text-sm font-medium tabular-nums" :class="darkMode ? 'text-gray-200' : 'text-gray-800'"
              x-text="@js(__('admin.bulk_selected_count')).replace(':count', selected.length)"></span>

        <button type="button" @click="selected = []"
                class="px-3 py-2 rounded-xl text-sm font-semibold border transition-colors"
                :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
            {{ __('admin.cancel') }}
        </button>

        {{-- Goes through the shared confirm modal, never native confirm(). --}}
        <form method="POST" action="{{ $action }}"
              @submit.prevent="$dispatch('show-confirm', {
                  title: @js(__('admin.delete')),
                  message: @js($confirm ?? __('admin.bulk_delete_confirm')),
                  confirmText: @js(__('admin.delete')),
                  cancelText: @js(__('admin.cancel')),
                  type: 'danger',
                  onConfirm: () => $el.submit(),
              })">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <button type="submit"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors flex items-center gap-2">
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                {{ __('admin.delete') }}
            </button>
        </form>
    </div>
</div>

@once
@push('scripts')
<script>
// Shared selection state for admin index tables.
function bulkSelect(ids = []) {
    return {
        selected: [],
        allIds: ids,
        get allSelected() {
            return this.allIds.length > 0 && this.selected.length === this.allIds.length;
        },
        toggleAll(checked) {
            this.selected = checked ? [...this.allIds] : [];
        },
    };
}
</script>
@endpush
@endonce
