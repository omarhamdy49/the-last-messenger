{{-- Multi-Language Input Component --}}
@php
    // Load active languages from database
    $languages = \App\Domain\Shared\Models\Language::active()->orderBy('sort_order')->get();
    $rtlLanguages = ['ar', 'ur']; // RTL language codes
@endphp

@props(['name', 'label', 'type' => 'text', 'value' => [], 'required' => false, 'rows' => 4])
<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
        {{ $label }} @if($required)<span class="text-red-500">*</span>@endif
    </label>
    <x-forms.language-tabs>
        @foreach($languages as $language)
        <div x-show="activeTab === '{{ $language->code }}'" x-transition>
            @if($type === 'textarea')
            <textarea name="{{ $name }}[{{ $language->code }}]" rows="{{ $rows }}" {{ $required && $language->code === 'ar' ? 'required' : '' }} class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200'" dir="{{ in_array($language->code, $rtlLanguages) ? 'rtl' : 'ltr' }}">{{ is_array($value) ? ($value[$language->code] ?? '') : '' }}</textarea>
            @elseif($type === 'rich')
            <div class="rich-editor" data-lang="{{ $language->code }}" data-name="{{ $name }}[{{ $language->code }}]">{!! is_array($value) ? ($value[$language->code] ?? '') : '' !!}</div>
            @else
            <input type="text" name="{{ $name }}[{{ $language->code }}]" value="{{ is_array($value) ? ($value[$language->code] ?? '') : '' }}" {{ $required && $language->code === 'ar' ? 'required' : '' }} class="w-full px-4 py-3 rounded-xl border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-200'" dir="{{ in_array($language->code, $rtlLanguages) ? 'rtl' : 'ltr' }}">
            @endif
        </div>
        @endforeach
    </x-forms.language-tabs>
</div>
