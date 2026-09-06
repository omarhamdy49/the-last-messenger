{{--
    Media Picker Modal Tabs Component

    Displays the tab navigation for switching between Browse and Upload tabs.
    Supports dark mode and RTL layouts.

    Usage:
    <x-admin.media.picker-tabs />

    This component is part of the media-picker-modal and relies on Alpine.js data context
    from the parent mediaPickerModal() component.
--}}

<div class="flex border-b"
     :class="$root.darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
    <button @click="activeTab = 'browse'"
            class="flex items-center gap-2 px-6 py-3 font-semibold transition-colors relative"
            :class="activeTab === 'browse' ?
                'text-accent border-b-2 border-accent' :
                ($root.darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-600 hover:text-gray-900')">
        <i data-lucide="image" class="w-5 h-5"></i>
        <span>{{ __('media.picker.browse') }}</span>
    </button>

    <button @click="activeTab = 'upload'"
            class="flex items-center gap-2 px-6 py-3 font-semibold transition-colors relative"
            :class="activeTab === 'upload' ?
                'text-accent border-b-2 border-accent' :
                ($root.darkMode ? 'text-gray-400 hover:text-gray-200' : 'text-gray-600 hover:text-gray-900')">
        <i data-lucide="upload" class="w-5 h-5"></i>
        <span>{{ __('media.picker.upload_new') }}</span>
    </button>
</div>
