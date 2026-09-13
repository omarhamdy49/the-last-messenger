{{-- Quick actions for one profile; relies on reciterProfilesPage() for `rows` and `toggle()`. --}}
@php
    $btn = 'p-2 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-accent';
    $btnTheme = "darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'";
@endphp
<div class="flex items-center gap-1">
    <a href="{{ route('admin.reciter-profiles.edit', $profile->id) }}" class="{{ $btn }}" :class="{{ $btnTheme }}"
       aria-label="{{ __('quran.profiles_admin.edit') }}: {{ $name }}" title="{{ __('quran.profiles_admin.edit') }}">
        <i data-lucide="edit-2" class="w-4 h-4" aria-hidden="true"></i>
    </a>
    <form method="POST" action="{{ route('admin.reciter-profiles.toggle', [$profile->id, 'active']) }}" class="inline"
          @submit.prevent="toggle({{ $profile->id }}, 'active', $el.action)">
        @csrf
        <button type="submit" class="{{ $btn }}" :class="rows[{{ $profile->id }}].active ? 'text-green-600 hover:bg-green-50' : ({{ $btnTheme }})"
                :aria-pressed="rows[{{ $profile->id }}].active.toString()" aria-label="{{ __('quran.profiles_admin.toggle_active') }}: {{ $name }}" title="{{ __('quran.profiles_admin.toggle_active') }}">
            <i data-lucide="power" class="w-4 h-4" aria-hidden="true"></i>
        </button>
    </form>
    <form method="POST" action="{{ route('admin.reciter-profiles.toggle', [$profile->id, 'featured']) }}" class="inline"
          @submit.prevent="toggle({{ $profile->id }}, 'featured', $el.action)">
        @csrf
        <button type="submit" class="{{ $btn }}" :class="rows[{{ $profile->id }}].featured ? 'text-amber-500 hover:bg-amber-50' : ({{ $btnTheme }})"
                :aria-pressed="rows[{{ $profile->id }}].featured.toString()" aria-label="{{ __('quran.profiles_admin.toggle_featured') }}: {{ $name }}" title="{{ __('quran.profiles_admin.toggle_featured') }}">
            <i data-lucide="star" class="w-4 h-4" aria-hidden="true"></i>
        </button>
    </form>
    <form method="POST" action="{{ route('admin.reciter-profiles.destroy', $profile->id) }}" class="inline"
          @submit.prevent="{{ $profile->recordings_count > 0
              ? 'window.showToast('.\Illuminate\Support\Js::from(__('quran.profiles_admin.delete_blocked', ['count' => $profile->recordings_count])).', \'error\')'
              : '$dispatch(\'show-confirm\', { title: '.\Illuminate\Support\Js::from(__('quran.profiles_admin.delete_title')).', message: '.\Illuminate\Support\Js::from(__('quran.profiles_admin.delete_message', ['name' => $name])).', type: \'danger\', onConfirm: () => $el.submit() })' }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="{{ $btn }} text-red-500 hover:text-red-700" :class="darkMode ? 'hover:bg-red-900/20' : 'hover:bg-red-50'"
                aria-label="{{ __('quran.profiles_admin.delete') }}: {{ $name }}" title="{{ __('quran.profiles_admin.delete') }}">
            <i data-lucide="trash-2" class="w-4 h-4" aria-hidden="true"></i>
        </button>
    </form>
</div>
