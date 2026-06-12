{{-- Home module card form — used by create and edit. Expects optional $card --}}
@php
    $isEdit = isset($card) && $card->exists;
    $locales = ['ar', 'en', 'fr', 'tr', 'ur'];
    $inputClasses = 'w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-base sm:text-sm focus:ring-2 focus:ring-accent/50';
    $titles = $isEdit ? $card->getTranslations('title') : [];
    $descriptions = $isEdit ? $card->getTranslations('description') : [];
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.content.home-cards.update', $card->id) : route('admin.content.home-cards.store') }}"
      x-data="{ activeLanguage: 'ar', submitting: false }"
      @submit="submitting = true"
      class="max-w-4xl mx-auto space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_key') }} <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="key"
                       value="{{ old('key', $isEdit ? $card->key : '') }}"
                       dir="ltr"
                       required
                       placeholder="seerah"
                       class="{{ $inputClasses }} font-mono"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('key')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_target') }}
                </label>
                <input type="text"
                       name="target_url"
                       value="{{ old('target_url', $isEdit ? $card->target_url : '') }}"
                       dir="ltr"
                       placeholder="site.seerah.index"
                       class="{{ $inputClasses }} font-mono"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                <p class="mt-2 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.content_route_or_url_hint') }}</p>
                @error('target_url')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_icon') }}
                </label>
                <x-admin.icon-picker name="icon" :value="old('icon', $isEdit ? ($card->icon ?? '') : '')" />
                @error('icon')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_accent_color') }}
                </label>
                <div class="flex items-center gap-3">
                    <input type="color"
                           name="accent_color"
                           value="{{ old('accent_color', $isEdit ? ($card->accent_color ?? '#64CCC5') : '#64CCC5') }}"
                           class="h-12 w-16 rounded-lg border-2 cursor-pointer"
                           :class="darkMode ? 'bg-gray-950 border-gray-700' : 'bg-white border-gray-300'">
                    <span class="text-xs font-mono" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        {{ old('accent_color', $isEdit ? ($card->accent_color ?? '#64CCC5') : '#64CCC5') }}
                    </span>
                </div>
                @error('accent_color')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_image_url') }}
                </label>
                <input type="text"
                       name="image"
                       value="{{ old('image', $isEdit ? $card->image : '') }}"
                       dir="ltr"
                       placeholder="https://"
                       class="{{ $inputClasses }} font-mono"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('image')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_sort_order') }}
                </label>
                <input type="number"
                       name="sort_order"
                       min="0"
                       value="{{ old('sort_order', $isEdit ? $card->sort_order : '') }}"
                       dir="ltr"
                       class="{{ $inputClasses }}"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('sort_order')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-8">
                <label class="inline-flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_featured"
                           value="1"
                           @checked(old('is_featured', $isEdit ? $card->is_featured : false))
                           class="w-5 h-5 rounded text-accent focus:ring-accent/50">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.content_featured') }}</span>
                </label>
                <label class="inline-flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           @checked(old('is_active', $isEdit ? $card->is_active : true))
                           class="w-5 h-5 rounded text-accent focus:ring-accent/50">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.active') }}</span>
                </label>
            </div>
        </div>
    </div>

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            @include('admin.content._language_tabs', ['errorFields' => ['title', 'description']])
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
                            {{ __('admin.description') }}
                        </label>
                        <textarea name="description[{{ $locale }}]"
                                  rows="3"
                                  class="{{ $inputClasses }}"
                                  :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">{{ old('description.'.$locale, $descriptions[$locale] ?? '') }}</textarea>
                        @error("description.{$locale}")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('admin.content.home-cards.index') }}"
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
            <span>{{ $isEdit ? __('admin.content_update_card') : __('admin.content_create_card') }}</span>
        </button>
    </div>
</form>
