@props([
    'name' => '',
    'label' => '',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'multilang' => false,
    'helpText' => '',
    'rows' => 4,
    'maxlength' => null,
    'minlength' => null,
    'autoResize' => false,
])

@php
    $supportedLanguages = config('translatable.locales');
    if (is_callable($supportedLanguages)) {
        $supportedLanguages = $supportedLanguages();
    }

    $languageNames = [
        'ar' => 'العربية',
        'en' => 'English',
        'fr' => 'Français',
        'tr' => 'Türkçe',
        'ur' => 'اردو',
    ];

    // Get old values for each language if multilang
    $oldValues = [];
    if ($multilang) {
        foreach ($supportedLanguages as $lang) {
            $oldValues[$lang] = old("{$name}.{$lang}", is_array($value) ? ($value[$lang] ?? '') : $value);
        }
    } else {
        $oldValues['single'] = old($name, $value);
    }
@endphp

<div class="form-field"
     x-data="{
         currentLang: '{{ app()->getLocale() }}',
         hasError: false,
         isFocused: false,
         charCount: 0,
         maxLength: {{ $maxlength ?? 'null' }},
         autoResize: {{ $autoResize ? 'true' : 'false' }},

         init() {
             @if($multilang)
                 this.updateCharCount('{{ app()->getLocale() }}');
                 if (this.autoResize) {
                     this.resizeTextarea('{{ app()->getLocale() }}');
                 }
             @else
                 this.updateCharCount();
                 if (this.autoResize) {
                     this.resizeTextarea();
                 }
             @endif

             // Check for errors
             @if($errors->has($name) || ($multilang && collect($supportedLanguages)->first(fn($lang) => $errors->has("{$name}.{$lang}"))))
                 this.hasError = true;
             @endif
         },

         updateCharCount(lang = null) {
             if (this.maxLength) {
                 const textarea = lang
                     ? this.$el.querySelector(`[name='{{ $name }}[${lang}]']`)
                     : this.$el.querySelector(`[name='{{ $name }}']`);

                 if (textarea) {
                     this.charCount = textarea.value.length;
                 }
             }
         },

         resizeTextarea(lang = null) {
             const textarea = lang
                 ? this.$el.querySelector(`[name='{{ $name }}[${lang}]']`)
                 : this.$el.querySelector(`[name='{{ $name }}']`);

             if (textarea && this.autoResize) {
                 textarea.style.height = 'auto';
                 textarea.style.height = textarea.scrollHeight + 'px';
             }
         },

         handleInput(lang = null) {
             this.updateCharCount(lang);
             if (this.autoResize) {
                 this.resizeTextarea(lang);
             }
         }
     }">

    <!-- Label -->
    @if($label)
        <label class="block mb-2 font-semibold"
               :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    @if($multilang)
        <!-- Multi-language Tabs -->
        <div class="mb-3">
            <div class="flex gap-2 mb-4 overflow-x-auto pb-1 custom-scrollbar">
                @foreach($supportedLanguages as $lang)
                    <button type="button"
                            @click="currentLang = '{{ $lang }}'; $nextTick(() => { updateCharCount('{{ $lang }}'); if(autoResize) resizeTextarea('{{ $lang }}'); })"
                            class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 whitespace-nowrap flex-shrink-0"
                            :class="currentLang === '{{ $lang }}'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105'
                                : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                        {{ $languageNames[$lang] ?? $lang }}
                        @if($errors->has("{$name}.{$lang}"))
                            <span class="inline-block w-2 h-2 bg-red-500 rounded-full ml-2"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            <!-- Language-specific textareas -->
            @foreach($supportedLanguages as $lang)
                <div x-show="currentLang === '{{ $lang }}'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     class="relative">

                    <textarea name="{{ $name }}[{{ $lang }}]"
                              placeholder="{{ $placeholder }}"
                              rows="{{ $rows }}"
                              @if($required) required @endif
                              @if($disabled) disabled @endif
                              @if($readonly) readonly @endif
                              @if($maxlength) maxlength="{{ $maxlength }}" @endif
                              @if($minlength) minlength="{{ $minlength }}" @endif
                              @input="handleInput('{{ $lang }}')"
                              @focus="isFocused = true"
                              @blur="isFocused = false"
                              dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                              class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-base sm:text-sm resize-none custom-scrollbar"
                              :class="[
                                  darkMode ? 'bg-gray-900 border-gray-700 text-white placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400',
                                  isFocused ? 'border-[#64CCC5] shadow-glow ring-4 ring-[#64CCC5]/20' : '',
                                  hasError ? 'border-red-500' : ''
                              ]">{{ $oldValues[$lang] }}</textarea>

                    @if($errors->has("{$name}.{$lang}"))
                        <div class="mt-2 flex items-start gap-2 text-red-500 text-sm error-message animate-fadeInUp">
                            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <span>{{ $errors->first("{$name}.{$lang}") }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

    @else
        <!-- Single language textarea -->
        <div class="relative">
            <textarea name="{{ $name }}"
                      placeholder="{{ $placeholder }}"
                      rows="{{ $rows }}"
                      @if($required) required @endif
                      @if($disabled) disabled @endif
                      @if($readonly) readonly @endif
                      @if($maxlength) maxlength="{{ $maxlength }}" @endif
                      @if($minlength) minlength="{{ $minlength }}" @endif
                      @input="handleInput()"
                      @focus="isFocused = true"
                      @blur="isFocused = false"
                      class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo text-base sm:text-sm resize-none custom-scrollbar"
                      :class="[
                          darkMode ? 'bg-gray-900 border-gray-700 text-white placeholder-gray-400' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400',
                          isFocused ? 'border-[#64CCC5] shadow-glow ring-4 ring-[#64CCC5]/20' : '',
                          hasError ? 'border-red-500' : ''
                      ]">{{ $oldValues['single'] }}</textarea>

            @if($errors->has($name))
                <div class="mt-2 flex items-start gap-2 text-red-500 text-sm error-message animate-fadeInUp">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                    <span>{{ $errors->first($name) }}</span>
                </div>
            @endif
        </div>
    @endif

    <!-- Character count and help text -->
    <div class="mt-2 flex items-center justify-between text-sm">
        @if($helpText)
            <p :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                {{ $helpText }}
            </p>
        @else
            <span></span>
        @endif

        @if($maxlength)
            <span class="font-mono"
                  :class="[
                      charCount > maxLength * 0.9 ? 'text-red-500' : (darkMode ? 'text-gray-400' : 'text-gray-600')
                  ]">
                <span x-text="charCount"></span>/<span>{{ $maxlength }}</span>
            </span>
        @endif
    </div>
</div>
