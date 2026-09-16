{{-- Menu item form — used by create and edit. Expects: $locations, $activeLocation, $parents, optional $item --}}
@php
    $isEdit = isset($item) && $item->exists;
    $locales = enabled_content_locales();
    $inputClasses = 'w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-base sm:text-sm focus:ring-2 focus:ring-accent/50';
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.content.menus.update', $item->id) : route('admin.content.menus.store') }}"
      x-data="{ activeLanguage: 'ar', submitting: false }"
      @submit="submitting = true"
      class="max-w-4xl mx-auto space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 space-y-6">
            {{-- Labels per language --}}
            @include('admin.content._language_tabs', ['errorFields' => ['label']])

            @foreach ($locales as $locale)
                <div x-show="activeLanguage === '{{ $locale }}'" x-cloak>
                    <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        {{ __('admin.content_label') }} @if ($locale === 'ar')<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="text"
                           name="label[{{ $locale }}]"
                           value="{{ old('label.'.$locale, $isEdit ? ($item->getTranslations('label')[$locale] ?? '') : '') }}"
                           dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                           maxlength="150"
                           class="{{ $inputClasses }}"
                           :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                    @error("label.{$locale}")
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                {{-- Location --}}
                <div>
                    <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        {{ __('admin.content_menu_location') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="location"
                            class="{{ $inputClasses }}"
                            :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        @foreach ($locations as $location)
                            <option value="{{ $location->value }}" @selected(old('location', $activeLocation->value) === $location->value)>
                                {{ __('admin.content_menu_location_'.$location->value) }}
                            </option>
                        @endforeach
                    </select>
                    @error('location')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Parent --}}
                <div>
                    <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        {{ __('admin.content_parent') }}
                    </label>
                    <select name="parent_id"
                            class="{{ $inputClasses }}"
                            :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        <option value="">{{ __('admin.content_no_parent') }}</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((string) old('parent_id', $isEdit ? $item->parent_id : '') === (string) $parent->id)>
                                {{ $parent->getTranslation('label', 'ar') ?? '#'.$parent->id }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Route name --}}
                <div>
                    <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        {{ __('admin.content_route_name') }}
                    </label>
                    <input type="text"
                           name="route_name"
                           value="{{ old('route_name', $isEdit ? $item->route_name : '') }}"
                           dir="ltr"
                           placeholder="site.home"
                           class="{{ $inputClasses }} font-mono"
                           :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                    <p class="mt-2 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.content_route_or_url_hint') }}</p>
                    @error('route_name')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- URL --}}
                <div>
                    <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        {{ __('admin.content_url') }}
                    </label>
                    <input type="text"
                           name="url"
                           value="{{ old('url', $isEdit ? $item->url : '') }}"
                           dir="ltr"
                           placeholder="https://"
                           class="{{ $inputClasses }} font-mono"
                           :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                    @error('url')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Icon --}}
                <div>
                    <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        {{ __('admin.content_icon') }}
                    </label>
                    <x-admin.icon-picker name="icon" :value="old('icon', $isEdit ? ($item->icon ?? '') : '')" />
                    @error('icon')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>

                {{-- Active --}}
                <div class="flex items-end pb-3">
                    <label class="inline-flex items-center gap-3 cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               @checked(old('is_active', $isEdit ? $item->is_active : true))
                               class="w-5 h-5 rounded text-accent focus:ring-accent/50">
                        <span class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.active') }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('admin.content.menus.index', ['location' => $activeLocation->value]) }}"
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
            <span>{{ $isEdit ? __('admin.content_update_item') : __('admin.content_create_item') }}</span>
        </button>
    </div>
</form>
