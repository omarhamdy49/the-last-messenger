@php
    $langs = enabled_content_languages();
    $colorOptions = ['green','gray','red','blue','purple','yellow','pink','cyan','teal','orange','slate','indigo','amber','emerald','rose'];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Name (multilingual) -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                {{ __('admin.name') }}
            </h2>

            <div class="flex flex-wrap gap-2 border-b pb-4 mb-6 overflow-x-auto" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                @foreach($langs as $lang => $label)
                <button type="button" @click="activeTab = '{{ $lang }}'"
                        class="px-4 py-2 rounded-lg font-medium transition-all whitespace-nowrap flex-shrink-0"
                        :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                    {{ $label }}
                </button>
                @endforeach
            </div>

            @foreach(array_keys($langs) as $lang)
            <div x-show="activeTab === '{{ $lang }}'">
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.name') }} ({{ strtoupper($lang) }})
                    @if($lang === 'ar') <span class="text-red-500">*</span> @endif
                </label>
                <input type="text" name="locale[{{ $lang }}]"
                       value="{{ old("locale.$lang", $type?->getTranslation('name', $lang, false)) }}"
                       dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                       class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                       placeholder="{{ __('admin.name') }}"
                       {{ $lang === 'ar' ? 'required' : '' }}>
                @error("locale.$lang") <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
            @endforeach
        </div>

        <!-- Appearance -->
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="palette" class="w-5 h-5 text-accent"></i>
                {{ __('admin.appearance') }}
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.icon') }}</label>
                    <x-admin.icon-picker name="metadata[icon]" :value="old('metadata.icon', $type?->icon ?? 'tag')" />
                    @error('metadata.icon') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.color') }}</label>
                    <select name="metadata[color]"
                            class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">—</option>
                        @foreach($colorOptions as $c)
                            <option value="{{ $c }}" {{ old('metadata.color', $type?->color) === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                    @error('metadata.color') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.gradient') }}</label>
                    <x-admin.gradient-picker name="metadata[gradient]" :value="old('metadata.gradient', $type?->gradient)" />
                    @error('metadata.gradient') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="settings-2" class="w-5 h-5 text-accent"></i>
                {{ __('admin.settings') }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('admin.slug') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="slug" value="{{ old('slug', $type?->slug) }}"
                           class="w-full px-4 py-3 text-base sm:text-sm rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                           placeholder="battle" pattern="[a-z0-9\-]+" required>
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.slug_format') }}</p>
                    @error('slug') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl transition-all" :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $type?->is_active ?? true) ? 'checked' : '' }}
                           class="w-5 h-5 rounded text-green-500 border-gray-300 focus:ring-green-500">
                    <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.is_active') }}</span>
                </label>
            </div>
        </div>

        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <button type="submit" :disabled="submitting"
                    class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed">
                <i data-lucide="save" class="w-5 h-5" x-show="!submitting"></i>
                <svg x-show="submitting" x-cloak class="animate-spin w-5 h-5" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                {{ __('admin.save') }}
            </button>
            <a href="{{ route('admin.seerah-event-types.index') }}"
               class="mt-4 w-full px-4 py-2.5 rounded-xl font-semibold text-center transition-all border flex items-center justify-center gap-2"
               :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                {{ __('admin.cancel') }}
            </a>
        </div>
    </div>
</div>
