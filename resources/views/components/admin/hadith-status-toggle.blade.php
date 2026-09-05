@props(['action', 'published'])
{{-- A real PATCH form, not a JSON fetch: method spoofing from a JSON body is
     ignored by Symfony, so those requests stay POST and 405. --}}
<form method="POST" action="{{ $action }}" class="inline">
    @csrf
    @method('PATCH')
    <button type="submit"
            class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors inline-flex items-center gap-1.5"
            :class="darkMode
                ? '{{ $published ? 'bg-emerald-500/15 text-emerald-300' : 'bg-gray-700 text-gray-300' }}'
                : '{{ $published ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}'">
        <i data-lucide="{{ $published ? 'eye' : 'eye-off' }}" class="w-3.5 h-3.5"></i>
        {{ $published ? __('hadith_admin.published') : __('hadith_admin.draft') }}
    </button>
</form>
