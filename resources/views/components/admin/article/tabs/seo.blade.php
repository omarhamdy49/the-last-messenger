{{--
    SEO Tab Component - Article Form
    Features: SEO metadata per locale, search preview
--}}

@props([
    'locales' => array_keys(config('app.supported_locales', ['ar' => [], 'en' => []])),
    'article' => null
])

<div x-show="activeTab === 'seo'" x-transition class="space-y-5">

    {{-- Language Tabs for SEO --}}
    <div class="flex gap-2 overflow-x-auto tabs-scroll pb-1">
        @foreach($locales as $locale)
        <button type="button" @click="activeLang = '{{ $locale }}'"
                :class="activeLang === '{{ $locale }}'
                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm'
                    : (darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')"
                class="px-4 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap">
            {{ strtoupper($locale) }}
        </button>
        @endforeach
    </div>

    @foreach($locales as $locale)
    <div x-show="activeLang === '{{ $locale }}'" x-transition class="space-y-5">

        {{-- SEO Title --}}
        <div>
            <label class="block text-sm font-semibold mb-1.5"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
                عنوان تحسين محركات البحث
                <span class="text-gray-400 font-normal text-xs">(اختياري)</span>
            </label>
            <input type="text" name="seo_title[{{ $locale }}]"
                   x-model="formData.seo_title['{{ $locale }}']" maxlength="70"
                   class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                   :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
                   @if(in_array($locale, ['ar', 'ur'])) dir="rtl" @endif>
            <div class="flex justify-between mt-1">
                <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    يُفضل أن يكون بين 50-60 حرفاً
                </p>
                <span class="text-xs"
                      :class="(formData.seo_title['{{ $locale }}']?.length || 0) > 60 ? 'text-orange-500' : (darkMode ? 'text-gray-500' : 'text-gray-400')">
                    <span x-text="formData.seo_title['{{ $locale }}']?.length || 0"></span>/70
                </span>
            </div>
        </div>

        {{-- SEO Description --}}
        <div>
            <label class="block text-sm font-semibold mb-1.5"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
                وصف تحسين محركات البحث
                <span class="text-gray-400 font-normal text-xs">(اختياري)</span>
            </label>
            <textarea name="seo_description[{{ $locale }}]"
                      x-model="formData.seo_description['{{ $locale }}']"
                      rows="3" maxlength="160"
                      class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                      :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
                      @if(in_array($locale, ['ar', 'ur'])) dir="rtl" @endif></textarea>
            <div class="flex justify-between mt-1">
                <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                    يُفضل أن يكون بين 150-155 حرفاً
                </p>
                <span class="text-xs"
                      :class="(formData.seo_description['{{ $locale }}']?.length || 0) > 155 ? 'text-orange-500' : (darkMode ? 'text-gray-500' : 'text-gray-400')">
                    <span x-text="formData.seo_description['{{ $locale }}']?.length || 0"></span>/160
                </span>
            </div>
        </div>

        {{-- Search Preview --}}
        <div class="rounded-lg border p-4"
             :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-gray-50 border-gray-200'">
            <h4 class="text-xs font-semibold mb-3 uppercase tracking-wide"
                :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                معاينة نتائج البحث
            </h4>
            <div class="space-y-1">
                <div class="text-blue-600 text-lg font-medium">
                    <span x-text="formData.seo_title['{{ $locale }}'] || formData.title['{{ $locale }}'] || 'عنوان المقال'"></span>
                </div>
                <div class="text-sm text-green-700 flex items-center gap-1">
                    <span>{{ config('app.url') }}</span>
                    <span>/</span>
                    <span x-text="formData.slug['{{ $locale }}'] || 'article-slug'"></span>
                </div>
                <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <span x-text="formData.seo_description['{{ $locale }}'] || formData.excerpt?.['{{ $locale }}'] || '...'"></span>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
