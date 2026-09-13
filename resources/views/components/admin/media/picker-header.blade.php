{{--
    Media Picker Modal Header Component

    Displays the header section of the media picker modal with title, selection help text,
    and close button. Supports dark mode and RTL layouts.

    Usage:
    <x-admin.media.picker-header />

    This component is part of the media-picker-modal and relies on Alpine.js data context
    from the parent mediaPickerModal() component.
--}}

<div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b"
     :class="$root.darkMode ? 'border-gray-800' : 'border-gray-200'">
    <div>
        <h2 class="text-2xl font-bold gradient-text">
            {{ __('media.picker.title') }}
        </h2>
        <p class="text-sm mt-1"
           :class="$root.darkMode ? 'text-gray-400' : 'text-gray-600'">
            <span x-text="selectionHelp()"></span>
            <span x-show="filterType !== 'all'" class="ms-1">
                (<span x-text="typeLabel(filterType)"></span>)
            </span>
        </p>
    </div>

    <button @click="close()"
            class="p-2 rounded-lg transition-all hover:scale-110"
            :class="$root.darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
        <i data-lucide="x" class="w-6 h-6"></i>
    </button>
</div>
