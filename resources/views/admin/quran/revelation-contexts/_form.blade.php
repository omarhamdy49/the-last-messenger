{{-- Shared form fields for create/edit. Expects: $surahs, optional $context (model). --}}
@php
    $locales = ['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'];
    $trans = fn (string $field, string $locale) => old("{$field}.{$locale}", isset($context) ? $context->getTranslation($field, $locale, false) : null);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main Form -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Verse Reference -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="book-open" class="w-5 h-5 text-accent"></i>
                {{ __('quran.asbab.verse_reference') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Surah -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('quran.asbab.surah') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="surah_id"
                            class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                            required>
                        <option value="">{{ __('quran.asbab.select_surah') }}</option>
                        @foreach($surahs ?? [] as $surah)
                            <option value="{{ $surah->id }}" {{ (string) old('surah_id', $context->surah_id ?? '') === (string) $surah->id ? 'selected' : '' }}>
                                {{ $surah->number }}. {{ $surah->name_ar }}
                            </option>
                        @endforeach
                    </select>
                    @error('surah_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Period -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('quran.asbab.period') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="period"
                            class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                            required>
                        <option value="makki" {{ old('period', $context->period ?? '') === 'makki' ? 'selected' : '' }}>{{ __('quran.asbab.period_makki') }}</option>
                        <option value="madani" {{ old('period', $context->period ?? '') === 'madani' ? 'selected' : '' }}>{{ __('quran.asbab.period_madani') }}</option>
                    </select>
                    @error('period')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Verse From -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('quran.asbab.verse_from') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="verse_from"
                           min="1"
                           value="{{ old('verse_from', $context->verse_from ?? '') }}"
                           class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                           required>
                    @error('verse_from')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Verse To -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('quran.asbab.verse_to') }}
                    </label>
                    <input type="number"
                           name="verse_to"
                           min="1"
                           value="{{ old('verse_to', $context->verse_to ?? '') }}"
                           class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('verse_to')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Translatable Content -->
        <div class="rounded-2xl border p-6"
             x-data="{ activeTab: 'ar' }"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                {{ __('quran.asbab.content') }}
            </h2>

            <!-- Language Tabs -->
            <div class="flex gap-2 mb-6 overflow-x-auto pb-1">
                @foreach($locales as $code => $label)
                    <button type="button"
                            @click="activeTab = '{{ $code }}'"
                            class="px-4 py-2 rounded-xl font-semibold text-sm whitespace-nowrap flex-shrink-0 transition-all"
                            :class="activeTab === '{{ $code }}'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow'
                                : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                        {{ $label }}
                        @if($code === 'ar')<span class="text-red-300">*</span>@endif
                    </button>
                @endforeach
            </div>

            @foreach($locales as $code => $label)
                <div x-show="activeTab === '{{ $code }}'" x-cloak class="space-y-5">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.asbab.title_field') }} ({{ $label }})
                            @if($code === 'ar')<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="text"
                               name="title[{{ $code }}]"
                               value="{{ $trans('title', $code) }}"
                               dir="{{ in_array($code, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               @if($code === 'ar') required @endif>
                        @error("title.{$code}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Narrative -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.asbab.narrative') }} ({{ $label }})
                            @if($code === 'ar')<span class="text-red-500">*</span>@endif
                        </label>
                        <textarea name="narrative[{{ $code }}]"
                                  rows="6"
                                  dir="{{ in_array($code, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                  class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-y"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  @if($code === 'ar') required @endif>{{ $trans('narrative', $code) }}</textarea>
                        @error("narrative.{$code}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Source Reference -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('quran.asbab.source_reference') }} ({{ $label }})
                        </label>
                        <input type="text"
                               name="source_reference[{{ $code }}]"
                               value="{{ $trans('source_reference', $code) }}"
                               dir="{{ in_array($code, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @error("source_reference.{$code}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Submit -->
        <div class="rounded-2xl border p-6"
             x-data="{ submitting: false }"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
             x-init="$el.closest('form').addEventListener('submit', () => submitting = true)">
            @error('error')
                <p class="mb-4 text-sm text-red-500">{{ $message }}</p>
            @enderror

            <button type="submit"
                    :disabled="submitting"
                    class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100">
                <i data-lucide="save" class="w-5 h-5" x-show="!submitting"></i>
                <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting" x-cloak></i>
                {{ __('admin.save') }}
            </button>

            <a href="{{ route('admin.revelation-contexts.index') }}"
               class="mt-4 w-full px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border flex items-center justify-center gap-2"
               :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                {{ __('admin.cancel') }}
            </a>
        </div>

        <!-- Metadata -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="settings-2" class="w-5 h-5 text-accent"></i>
                {{ __('quran.asbab.settings') }}
            </h3>

            <div class="space-y-4">
                <!-- Event Key -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('quran.asbab.event_key') }}
                    </label>
                    <input type="text"
                           name="event_key"
                           value="{{ old('event_key', $context->event_key ?? '') }}"
                           dir="ltr"
                           class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('event_key')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Display Order -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('quran.asbab.display_order') }}
                    </label>
                    <input type="number"
                           name="display_order"
                           min="0"
                           value="{{ old('display_order', $context->display_order ?? 0) }}"
                           class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @error('display_order')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Active -->
                <label class="flex items-center gap-3 cursor-pointer px-4 py-3 rounded-xl border transition-all"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700' : 'border-gray-300 bg-white hover:bg-gray-50'">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', $context->is_active ?? true) ? 'checked' : '' }}
                           class="w-5 h-5 rounded text-accent border-gray-300 focus:ring-accent">
                    <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('quran.asbab.active') }}</span>
                </label>
            </div>
        </div>
    </div>
</div>
