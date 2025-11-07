{{--
    Language Tabs Component
    Manages multi-language content editing with validation tracking

    Usage:
    <x-admin.language-tabs :languages="['ar', 'en', 'fr', 'tr', 'ur']" :errors="$errors">
        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
            <x-admin.language-tab-panel :language="$lang">
                <!-- Form fields here -->
            </x-admin.language-tab-panel>
        @endforeach
    </x-admin.language-tabs>
--}}

@props([
    'languages' => ['ar', 'en', 'fr', 'tr', 'ur'],
    'errors' => null,
    'defaultLanguage' => 'ar',
    'showCompletionStatus' => true,
    'showCopyButton' => true,
])

@php
    $languageData = [
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'direction' => 'rtl'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'direction' => 'ltr'],
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷', 'direction' => 'ltr'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'direction' => 'ltr'],
        'ur' => ['name' => 'اردو', 'flag' => '🇵🇰', 'direction' => 'rtl'],
    ];

    // Extract validation errors per language
    $languageErrors = [];
    if ($errors && $errors->any()) {
        foreach ($languages as $lang) {
            $languageErrors[$lang] = $errors->get("*_{$lang}");
        }
    }
@endphp

<div x-data="languageTabs({
        defaultLanguage: '{{ $defaultLanguage }}',
        languages: {{ Js::from($languages) }},
        languageData: {{ Js::from($languageData) }},
        validationErrors: {{ Js::from($languageErrors) }},
        showCompletionStatus: {{ $showCompletionStatus ? 'true' : 'false' }},
        showCopyButton: {{ $showCopyButton ? 'true' : 'false' }}
    })"
     x-init="init()"
     {{ $attributes->merge(['class' => 'language-tabs-container']) }}>

    {{-- Tab Navigation --}}
    <div class="sticky top-0 z-20 glass border-b"
         :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">

        {{-- Desktop Tabs --}}
        <div class="hidden md:flex items-center gap-2 px-4 py-3 overflow-x-auto custom-scrollbar">
            <template x-for="lang in languages" :key="lang">
                <button type="button"
                        @click="switchLanguage(lang)"
                        class="relative flex items-center gap-2 px-4 py-2.5 rounded-lg font-semibold text-sm transition-all duration-300 whitespace-nowrap"
                        :class="activeLanguage === lang ?
                            'bg-gradient-primary text-white shadow-lg scale-105' :
                            (darkMode ? 'bg-gray-800/50 text-gray-300 hover:bg-gray-800 hover:text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-900')">

                    {{-- Flag Icon --}}
                    <span class="text-lg" x-text="languageData[lang].flag"></span>

                    {{-- Language Name --}}
                    <span x-text="languageData[lang].name"></span>

                    {{-- Validation Badge --}}
                    <template x-if="hasErrors(lang)">
                        <span class="w-2 h-2 bg-red-500 rounded-full ring-2 ring-white animate-pulse"></span>
                    </template>

                    {{-- Completion Badge --}}
                    <template x-if="showCompletionStatus && !hasErrors(lang) && getCompletionPercentage(lang) === 100">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-400"></i>
                    </template>

                    {{-- Active Indicator --}}
                    <div x-show="activeLanguage === lang"
                         class="absolute bottom-0 left-0 right-0 h-1 bg-accent rounded-t-full"></div>
                </button>
            </template>
        </div>

        {{-- Mobile Dropdown --}}
        <div class="md:hidden px-4 py-3">
            <div x-data="{ langMenuOpen: false }" class="relative">
                <button type="button"
                        @click="langMenuOpen = !langMenuOpen"
                        class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-lg font-semibold transition-all"
                        :class="darkMode ? 'bg-gray-800/50 text-white' : 'bg-gray-100 text-gray-900'">

                    <div class="flex items-center gap-2">
                        <span class="text-lg" x-text="languageData[activeLanguage].flag"></span>
                        <span x-text="languageData[activeLanguage].name"></span>

                        <template x-if="hasErrors(activeLanguage)">
                            <span class="w-2 h-2 bg-red-500 rounded-full ring-2 ring-white animate-pulse"></span>
                        </template>
                    </div>

                    <i data-lucide="chevron-down" class="w-5 h-5 transition-transform" :class="langMenuOpen ? 'rotate-180' : ''"></i>
                </button>

                {{-- Dropdown Menu --}}
                <div x-show="langMenuOpen"
                     x-cloak
                     @click.away="langMenuOpen = false"
                     x-transition
                     class="absolute top-full mt-2 inset-x-0 glass rounded-lg shadow-xl overflow-hidden z-30"
                     :class="darkMode ? 'glass-dark' : 'glass-light'">

                    <div class="p-2 space-y-1">
                        <template x-for="lang in languages" :key="lang">
                            <button type="button"
                                    @click="switchLanguage(lang); langMenuOpen = false"
                                    class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-lg text-start transition-colors"
                                    :class="activeLanguage === lang ?
                                        'bg-gradient-primary text-white' :
                                        (darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100')">

                                <div class="flex items-center gap-2">
                                    <span class="text-lg" x-text="languageData[lang].flag"></span>
                                    <span class="font-semibold" x-text="languageData[lang].name"></span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <template x-if="hasErrors(lang)">
                                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
                                    </template>

                                    <template x-if="showCompletionStatus && !hasErrors(lang) && getCompletionPercentage(lang) === 100">
                                        <i data-lucide="check-circle" class="w-4 h-4 text-green-400"></i>
                                    </template>

                                    <template x-if="activeLanguage === lang">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </template>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Language Actions Bar --}}
        <div class="flex items-center justify-between px-4 py-2 border-t"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">

            {{-- Completion Status --}}
            <div x-show="showCompletionStatus" class="flex items-center gap-2">
                <div class="flex items-center gap-2 text-xs">
                    <div class="w-32 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-primary transition-all duration-500 rounded-full"
                             :style="`width: ${getCompletionPercentage(activeLanguage)}%`"></div>
                    </div>
                    <span class="font-semibold tabular-nums"
                          :class="darkMode ? 'text-gray-400' : 'text-gray-600'"
                          x-text="`${getCompletionPercentage(activeLanguage)}%`"></span>
                </div>
            </div>

            {{-- Copy from Language Button --}}
            <div x-show="showCopyButton" x-data="{ copyMenuOpen: false }" class="relative">
                <button type="button"
                        @click="copyMenuOpen = !copyMenuOpen"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                        :class="darkMode ? 'bg-gray-800/50 text-gray-300 hover:bg-gray-800' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span>Copy from...</span>
                </button>

                {{-- Copy Menu Dropdown --}}
                <div x-show="copyMenuOpen"
                     x-cloak
                     @click.away="copyMenuOpen = false"
                     x-transition
                     class="absolute top-full mt-2 end-0 w-48 glass rounded-lg shadow-xl overflow-hidden z-30"
                     :class="darkMode ? 'glass-dark' : 'glass-light'">

                    <div class="p-2 space-y-1">
                        <template x-for="lang in languages.filter(l => l !== activeLanguage)" :key="lang">
                            <button type="button"
                                    @click="copyFromLanguage(lang); copyMenuOpen = false"
                                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-start transition-colors"
                                    :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-100'">
                                <span class="text-lg" x-text="languageData[lang].flag"></span>
                                <span class="text-sm font-semibold" x-text="languageData[lang].name"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Content Panels --}}
    <div class="language-tab-panels">
        {{ $slot }}
    </div>
</div>

@once
    @push('scripts')
    <script>
        // Language tabs Alpine.js component
        function languageTabs(config) {
            return {
                activeLanguage: config.defaultLanguage,
                languages: config.languages,
                languageData: config.languageData,
                validationErrors: config.validationErrors || {},
                completionStatus: {},
                showCompletionStatus: config.showCompletionStatus,
                showCopyButton: config.showCopyButton,
                autoSaveTimeout: null,

                init() {
                    // Initialize completion status for all languages
                    this.languages.forEach(lang => {
                        this.completionStatus[lang] = 0;
                    });

                    // Check for errors and switch to first language with errors
                    const firstErrorLang = this.languages.find(lang => this.hasErrors(lang));
                    if (firstErrorLang) {
                        this.activeLanguage = firstErrorLang;
                    }

                    // Calculate initial completion
                    this.$nextTick(() => {
                        this.calculateCompletion();
                        lucide.createIcons();
                    });

                    // Listen for input changes to update completion
                    this.$el.addEventListener('input', () => {
                        this.debouncedCalculateCompletion();
                    });

                    // Listen for custom validation events
                    window.addEventListener('language-validation-error', (e) => {
                        if (e.detail.language) {
                            this.switchLanguage(e.detail.language);
                        }
                    });
                },

                switchLanguage(lang) {
                    if (this.activeLanguage === lang) return;

                    this.activeLanguage = lang;

                    // Update direction based on language
                    const direction = this.languageData[lang].direction;
                    const activePanel = this.$el.querySelector(`[data-language="${lang}"]`);
                    if (activePanel) {
                        activePanel.setAttribute('dir', direction);
                    }

                    // Reinitialize Lucide icons after panel switch
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });

                    // Dispatch custom event
                    this.$dispatch('language-changed', { language: lang });
                },

                hasErrors(lang) {
                    return this.validationErrors[lang] && this.validationErrors[lang].length > 0;
                },

                getCompletionPercentage(lang) {
                    return this.completionStatus[lang] || 0;
                },

                calculateCompletion() {
                    this.languages.forEach(lang => {
                        const panel = this.$el.querySelector(`[data-language="${lang}"]`);
                        if (!panel) return;

                        const requiredFields = panel.querySelectorAll('[required]');
                        if (requiredFields.length === 0) {
                            this.completionStatus[lang] = 100;
                            return;
                        }

                        let filledFields = 0;
                        requiredFields.forEach(field => {
                            if (field.value && field.value.trim() !== '') {
                                filledFields++;
                            }
                        });

                        this.completionStatus[lang] = Math.round((filledFields / requiredFields.length) * 100);
                    });
                },

                debouncedCalculateCompletion() {
                    clearTimeout(this.autoSaveTimeout);
                    this.autoSaveTimeout = setTimeout(() => {
                        this.calculateCompletion();
                    }, 300);
                },

                copyFromLanguage(sourceLang) {
                    const sourcePanel = this.$el.querySelector(`[data-language="${sourceLang}"]`);
                    const targetPanel = this.$el.querySelector(`[data-language="${this.activeLanguage}"]`);

                    if (!sourcePanel || !targetPanel) return;

                    // Copy input values
                    const sourceInputs = sourcePanel.querySelectorAll('input[type="text"], textarea');
                    sourceInputs.forEach(sourceInput => {
                        const name = sourceInput.name;
                        if (!name) return;

                        // Find corresponding input in target panel
                        const baseName = name.replace(`_${sourceLang}`, '');
                        const targetName = `${baseName}_${this.activeLanguage}`;
                        const targetInput = targetPanel.querySelector(`[name="${targetName}"]`);

                        if (targetInput && sourceInput.value) {
                            targetInput.value = sourceInput.value;

                            // Trigger input event for reactivity
                            targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                    });

                    // Recalculate completion
                    this.calculateCompletion();

                    // Show success notification
                    this.$dispatch('show-toast', {
                        type: 'success',
                        message: `Content copied from ${this.languageData[sourceLang].name}`
                    });
                },
            };
        }

        // Make available globally
        window.languageTabs = languageTabs;
    </script>
    @endpush
@endonce
