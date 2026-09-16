@props(['action', 'label' => ''])
<form method="POST" action="{{ $action }}" class="inline"
      onsubmit="return window.confirmSubmit(this, { title: {{ Js::from(__('admin.confirm_delete')) }}, message: {{ Js::from(__('hadith_admin.delete') . ': ' . $label . '؟') }}, confirmText: {{ Js::from(__('hadith_admin.delete')) }} })">
    @csrf
    @method('DELETE')
    <button type="submit" class="p-2 rounded-lg text-red-500 hover:bg-red-500/10 transition-colors"
            title="{{ __('hadith_admin.delete') }}">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
    </button>
</form>
