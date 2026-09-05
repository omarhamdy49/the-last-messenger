@props(['action', 'label' => ''])
<form method="POST" action="{{ $action }}" class="inline"
      x-data
      @submit="if (! confirm(@js(__('hadith_admin.delete') . ': ' . $label . '؟'))) $event.preventDefault()">
    @csrf
    @method('DELETE')
    <button type="submit" class="p-2 rounded-lg text-red-500 hover:bg-red-500/10 transition-colors"
            title="{{ __('hadith_admin.delete') }}">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
    </button>
</form>
