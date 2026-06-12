{{-- Quick-filter chip form — used by create and edit. Expects optional $chip --}}
@php
    $isEdit = isset($chip) && $chip->exists;
    $locales = ['ar', 'en', 'fr', 'tr', 'ur'];
    $inputClasses = 'w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-base sm:text-sm focus:ring-2 focus:ring-accent/50';
    $labels = $isEdit ? $chip->getTranslations('label') : [];
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.content.quick-filters.update', $chip->id) : route('admin.content.quick-filters.store') }}"
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
                       value="{{ old('key', $isEdit ? $chip->key : '') }}"
                       dir="ltr"
                       required
                       placeholder="hadith-sahih"
                       class="{{ $inputClasses }} font-mono"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('key')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_sort_order') }}
                </label>
                <input type="number"
                       name="sort_order"
                       min="0"
                       value="{{ old('sort_order', $isEdit ? $chip->sort_order : '') }}"
                       dir="ltr"
                       class="{{ $inputClasses }}"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('sort_order')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_target_query') }}
                </label>
                <input type="text"
                       name="target_query"
                       value="{{ old('target_query', $isEdit ? $chip->target_query : '') }}"
                       class="{{ $inputClasses }}"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('target_query')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    {{ __('admin.content_target') }}
                </label>
                <input type="text"
                       name="target_url"
                       value="{{ old('target_url', $isEdit ? $chip->target_url : '') }}"
                       dir="ltr"
                       class="{{ $inputClasses }} font-mono"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                @error('target_url')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center">
                <label class="inline-flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           @checked(old('is_active', $isEdit ? $chip->is_active : true))
                           class="w-5 h-5 rounded text-accent focus:ring-accent/50">
                    <span class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.active') }}</span>
                </label>
            </div>
        </div>
    </div>

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            @include('admin.content._language_tabs', ['errorFields' => ['label']])
        </div>
        <div class="p-6">
            @foreach ($locales as $locale)
                <div x-show="activeLanguage === '{{ $locale }}'" x-cloak
                     dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                    <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        {{ __('admin.content_label') }} @if ($locale === 'ar')<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="text"
                           name="label[{{ $locale }}]"
                           value="{{ old('label.'.$locale, $labels[$locale] ?? '') }}"
                           maxlength="100"
                           class="{{ $inputClasses }}"
                           :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                    @error("label.{$locale}")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('admin.content.quick-filters.index') }}"
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
            <span>{{ $isEdit ? __('admin.content_update_chip') : __('admin.content_create_chip') }}</span>
        </button>
    </div>
</form>
