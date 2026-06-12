@extends('layouts.admin')

@section('content')
@php
    use App\Domain\Content\Enums\SiteSettingType;

    $locales = ['ar', 'en', 'fr', 'tr', 'ur'];
    $inputClasses = 'w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-base sm:text-sm focus:ring-2 focus:ring-accent/50';
@endphp
<div x-data="{ activeLanguage: 'ar', submitting: false }">
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_settings') }}</span>
    </nav>

    <div class="mb-8">
        <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_settings') }}</h1>
        <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_settings_hint') }}</p>
    </div>

    @include('admin.content._flash')

    @if (empty($groups))
        <div class="rounded-2xl border p-12 text-center" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <i data-lucide="settings" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
            <p class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_no_settings') }}</p>
        </div>
    @else
        {{-- Group tabs --}}
        <div class="flex flex-wrap gap-2 mb-6 overflow-x-auto">
            @foreach ($groups as $group)
                <a href="{{ route('admin.content.settings.index', ['group' => $group]) }}"
                   class="px-4 py-2 rounded-lg font-semibold transition-all whitespace-nowrap flex-shrink-0 {{ $group === $activeGroup ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
                   @if ($group !== $activeGroup) :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" @endif>
                    {{ \Illuminate\Support\Str::headline($group) }}
                </a>
            @endforeach
        </div>

        <form method="POST"
              action="{{ route('admin.content.settings.update', ['group' => $activeGroup]) }}"
              @submit="submitting = true"
              class="rounded-2xl overflow-hidden shadow-xl border"
              :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            @csrf
            @method('PUT')

            <div class="p-6 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                @include('admin.content._language_tabs')
            </div>

            <div class="p-6 space-y-8">
                @forelse ($settings as $setting)
                    <div>
                        <label class="block mb-2 font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                            {{ \Illuminate\Support\Str::headline($setting->key) }}
                            <span class="ms-2 text-xs font-normal" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $setting->key }}</span>
                        </label>

                        @if ($setting->is_translatable)
                            @php $values = $setting->getTranslations('value'); @endphp
                            @foreach ($locales as $locale)
                                <div x-show="activeLanguage === '{{ $locale }}'" x-cloak>
                                    @if ($setting->type === SiteSettingType::RICHTEXT)
                                        <x-admin.form.rich-text
                                            name="settings[{{ $setting->key }}][{{ $locale }}]"
                                            :value="old('settings.'.$setting->key.'.'.$locale, $values[$locale] ?? '')"
                                            :locale="$locale" />
                                    @elseif ($setting->type === SiteSettingType::TEXTAREA)
                                        <textarea name="settings[{{ $setting->key }}][{{ $locale }}]"
                                                  rows="3"
                                                  dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                                  class="{{ $inputClasses }}"
                                                  :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">{{ old('settings.'.$setting->key.'.'.$locale, $values[$locale] ?? '') }}</textarea>
                                    @else
                                        <input type="text"
                                               name="settings[{{ $setting->key }}][{{ $locale }}]"
                                               value="{{ old('settings.'.$setting->key.'.'.$locale, $values[$locale] ?? '') }}"
                                               dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                               class="{{ $inputClasses }}"
                                               :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                                    @endif
                                    @error("settings.{$setting->key}.{$locale}")
                                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        @elseif ($setting->type === SiteSettingType::BOOL)
                            <label class="inline-flex items-center gap-3 cursor-pointer">
                                <input type="checkbox"
                                       name="settings[{{ $setting->key }}]"
                                       value="1"
                                       @checked(old('settings.'.$setting->key, $setting->value) === '1')
                                       class="w-5 h-5 rounded text-accent focus:ring-accent/50">
                                <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_enabled') }}</span>
                            </label>
                        @else
                            <input type="text"
                                   name="settings[{{ $setting->key }}]"
                                   value="{{ old('settings.'.$setting->key, $setting->value) }}"
                                   dir="ltr"
                                   @if (in_array($setting->type, [SiteSettingType::URL, SiteSettingType::IMAGE], true)) placeholder="https://" @endif
                                   class="{{ $inputClasses }} font-mono"
                                   :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        @endif

                        @error("settings.{$setting->key}")
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @empty
                    <p class="text-center py-8" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_no_settings') }}</p>
                @endforelse
            </div>

            <div class="p-6 border-t flex flex-wrap justify-end gap-3" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <button type="submit"
                        :disabled="submitting"
                        class="px-8 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2 disabled:opacity-60 disabled:hover:scale-100">
                    <i data-lucide="save" class="w-5 h-5" x-show="!submitting"></i>
                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting" x-cloak></i>
                    <span>{{ __('admin.content_save_group') }}</span>
                </button>
            </div>
        </form>
    @endif
</div>
@endsection
