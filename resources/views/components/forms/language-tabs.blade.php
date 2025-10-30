{{-- Language Tabs Component --}}
@php
    // Load active languages from database
    $languages = \App\Domain\Shared\Models\Language::active()->orderBy('sort_order')->get();
    $active = $active ?? 'ar';
@endphp

@props(['active' => 'ar'])
<div x-data="{ activeTab: '{{ $active }}' }" {{ $attributes }}>
    <div class="flex gap-2 border-b mb-4" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        @foreach($languages as $language)
        <button type="button" @click="activeTab = '{{ $language->code }}'" class="px-4 py-2.5 font-medium transition-all relative" :class="activeTab === '{{ $language->code }}' ? 'text-[#0056b3]' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
            <span class="flex items-center gap-2">
                {{ $language->flag }} {{ $language->native_name }}
            </span>
            <div x-show="activeTab === '{{ $language->code }}'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-[#0056b3] to-[#218838]"></div>
        </button>
        @endforeach
    </div>
    {{ $slot }}
</div>
