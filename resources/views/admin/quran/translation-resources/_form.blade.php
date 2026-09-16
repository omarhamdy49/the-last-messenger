{{-- Shared create/edit fields. Expects optional $resource. --}}
@php
    $r = $resource ?? null;
    $val = fn (string $k, $d = null) => old($k, $d);
    $langs = enabled_content_languages() + (in_array('en', enabled_content_locales(), true) ? ['en-tl' => 'Transliteration'] : []);
@endphp

@if($errors->any())
<div class="mb-4 p-4 rounded-xl bg-red-100 text-red-800 border border-red-200">
    <ul class="list-disc ps-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.name_ar') }} *</label>
        <input type="text" name="name_ar" value="{{ $val('name_ar', $r?->getName('ar')) }}" required dir="rtl"
               class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.name_en') }}</label>
        <input type="text" name="name_en" value="{{ $val('name_en', $r ? ($r->name['en'] ?? '') : '') }}"
               class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.slug') }} *</label>
        <input type="text" name="slug" value="{{ $val('slug', $r?->slug) }}" required
               class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.language') }} *</label>
        <select name="language" class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
                :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
            @foreach($langs as $code => $label)
            <option value="{{ $code }}" @selected($val('language', $r?->language ?? 'ar') === $code)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.direction') }} *</label>
        <select name="direction" class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
                :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
            <option value="ltr" @selected($val('direction', $r?->direction ?? 'ltr') === 'ltr')>LTR</option>
            <option value="rtl" @selected($val('direction', $r?->direction ?? 'ltr') === 'rtl')>RTL</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.author') }}</label>
        <input type="text" name="author" value="{{ $val('author', $r?->author) }}"
               class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.source') }}</label>
        <input type="text" name="source" value="{{ $val('source', $r?->source) }}"
               class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.display_order') }}</label>
        <input type="number" name="display_order" min="0" value="{{ $val('display_order', $r?->display_order ?? 0) }}"
               class="w-full px-4 py-2.5 text-base sm:text-sm rounded-xl border-2 focus:outline-none focus:ring-2 focus:ring-[#64CCC5]"
               :class="darkMode ? 'bg-gray-900 border-gray-800 text-white' : 'bg-white border-gray-200 text-gray-900'">
    </div>
    <div class="flex items-end gap-6">
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_default" value="1" @checked($val('is_default', $r?->is_default ?? false)) class="w-5 h-5 rounded">
            <span class="text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.is_default') }}</span>
        </label>
        <label class="inline-flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" @checked($val('is_active', $r?->is_active ?? true)) class="w-5 h-5 rounded">
            <span class="text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.admin.is_active') }}</span>
        </label>
    </div>
</div>

<p class="mt-3 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('quran.admin.translation_import_hint') }}</p>

<div class="mt-6 flex items-center gap-3 flex-wrap">
    <button type="submit" class="px-6 py-3 rounded-xl font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg hover:shadow-xl transition-all">
        {{ __('quran.admin.save') }}
    </button>
    <a href="{{ route('admin.translation-resources.index') }}" class="px-6 py-3 rounded-xl font-semibold"
       :class="darkMode ? 'bg-gray-800 text-gray-200' : 'bg-gray-200 text-gray-800'">{{ __('quran.admin.cancel') }}</a>
</div>
