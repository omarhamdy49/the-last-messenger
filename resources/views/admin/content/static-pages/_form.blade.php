{{-- Static page form — used by create and edit. Expects optional $page --}}
@php
    $isEdit = isset($page) && $page->exists;
    $locales = ['ar', 'en', 'fr', 'tr', 'ur'];
    $inputClasses = 'w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-base sm:text-sm focus:ring-2 focus:ring-accent/50';
    $titles = $isEdit ? $page->getTranslations('title') : [];
    $bodies = $isEdit ? $page->getTranslations('body') : [];
    $seoTitles = $isEdit ? $page->getTranslations('seo_title') : [];
    $seoDescriptions = $isEdit ? $page->getTranslations('seo_description') : [];
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.content.static-pages.update', $page->id) : route('admin.content.static-pages.store') }}"
      x-data="{ activeLanguage: 'ar', submitting: false }"
      @submit="submitting = true"
      class="max-w-5xl mx-auto space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- Page attributes --}}
    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.slug') }} <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="slug"
                       value="{{ old('slug', $isEdit ? $page->slug : '') }}"
                       dir="ltr"
                       required
                       placeholder="about-us"
                       class="{{ $inputClasses }} font-mono"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('slug')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_published_at') }}
                </label>
                <input type="datetime-local"
                       name="published_at"
                       value="{{ old('published_at', $isEdit ? $page->published_at?->format('Y-m-d\TH:i') : '') }}"
                       dir="ltr"
                       class="{{ $inputClasses }}"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('published_at')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end pb-3">
                <label class="inline-flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           @checked(old('is_active', $isEdit ? $page->is_active : true))
                           class="w-5 h-5 rounded text-accent focus:ring-accent/50">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.active') }}</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Translatable content --}}
    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h2 class="text-xl font-bold flex items-center gap-2 mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                <span>{{ __('admin.content_page_content') }}</span>
            </h2>
            @include('admin.content._language_tabs', ['errorFields' => ['title', 'body', 'seo_title', 'seo_description']])
        </div>

        <div class="p-6">
            @foreach ($locales as $locale)
                <div x-show="activeLanguage === '{{ $locale }}'" x-cloak class="space-y-6"
                     dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                    <div>
                        <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                            {{ __('admin.title') }} @if ($locale === 'ar')<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="text"
                               name="title[{{ $locale }}]"
                               value="{{ old('title.'.$locale, $titles[$locale] ?? '') }}"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        @error("title.{$locale}")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                            {{ __('admin.content_body') }}
                        </label>
                        <x-admin.form.wysiwyg
                            name="body[{{ $locale }}]"
                            :value="old('body.'.$locale, $bodies[$locale] ?? '')"
                            :locale="$locale"
                            toolbar="compact"
                            height="320px" />
                        @error("body.{$locale}")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                {{ __('admin.content_seo_title') }}
                            </label>
                            <input type="text"
                                   name="seo_title[{{ $locale }}]"
                                   value="{{ old('seo_title.'.$locale, $seoTitles[$locale] ?? '') }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                            @error("seo_title.{$locale}")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                {{ __('admin.content_seo_description') }}
                            </label>
                            <textarea name="seo_description[{{ $locale }}]"
                                      rows="2"
                                      class="{{ $inputClasses }}"
                                      :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">{{ old('seo_description.'.$locale, $seoDescriptions[$locale] ?? '') }}</textarea>
                            @error("seo_description.{$locale}")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('admin.content.static-pages.index') }}"
           class="px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
        <button type="submit"
                :disabled="submitting"
                class="px-8 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2 disabled:opacity-60 disabled:hover:scale-100">
            <i data-lucide="save" class="w-5 h-5" x-show="!submitting"></i>
            <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting" x-cloak></i>
            <span>{{ $isEdit ? __('admin.content_update_page') : __('admin.content_create_page') }}</span>
        </button>
    </div>
</form>
