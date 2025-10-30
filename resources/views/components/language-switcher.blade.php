{{--
Language Switcher Component

Usage:
    <x-language-switcher />

Props:
    - darkMode (optional): true/false for dark mode styling
    - position (optional): 'header' or 'footer' for different positioning

Example with props:
    <x-language-switcher :darkMode="true" position="header" />
--}}

@props(['darkMode' => false, 'position' => 'header'])

<div x-data="languageSwitcher()" class="relative" @click.away="isOpen = false">
    <!-- Language Switcher Button -->
    <button @click="isOpen = !isOpen"
            {{ $attributes->merge(['class' => 'flex items-center gap-2 px-4 py-2 rounded-lg transition-all hover:scale-105']) }}
            :class="@js($darkMode) ? 'bg-white/10 hover:bg-white/20 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-900'">

        <!-- Current Language Flag -->
        <span class="text-xl" x-text="currentLanguage.flag"></span>

        <!-- Current Language Name -->
        <span class="font-semibold text-sm" x-text="currentLanguage.native_name"></span>

        <!-- Dropdown Icon -->
        <i data-lucide="chevron-down"
           class="w-4 h-4 transition-transform"
           :class="isOpen ? 'rotate-180' : ''"></i>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         :class="@js($position) === 'footer' ? 'bottom-full mb-2' : 'top-full mt-2'"
         class="absolute {{ app()->getLocale() === 'ar' || app()->getLocale() === 'ur' ? 'left-0' : 'right-0' }} z-50 min-w-[200px] rounded-xl shadow-2xl overflow-hidden"
         style="display: none;">

        <!-- Glass Effect Background -->
        <div :class="@js($darkMode) ? 'bg-gray-800/95 border-gray-700' : 'bg-white/95 border-gray-200'"
             class="backdrop-blur-xl border">

            <!-- Header -->
            <div :class="@js($darkMode) ? 'bg-gray-900/50 border-gray-700' : 'bg-gray-50 border-gray-200'"
                 class="px-4 py-3 border-b">
                <div class="flex items-center gap-2">
                    <i data-lucide="languages" class="w-4 h-4"></i>
                    <span class="font-bold text-sm">{{ __('اختر اللغة') }}</span>
                </div>
            </div>

            <!-- Language Options -->
            <div class="py-2">
                <template x-for="language in languages" :key="language.code">
                    <button @click="switchLanguage(language.code)"
                            :class="[
                                language.code === currentLanguage.code
                                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                                    : (@js($darkMode) ? 'hover:bg-gray-700 text-gray-300' : 'hover:bg-gray-100 text-gray-700'),
                                language.direction === 'rtl' ? 'text-right' : 'text-left'
                            ]"
                            class="w-full px-4 py-3 flex items-center gap-3 transition-all">

                        <!-- Flag -->
                        <span class="text-2xl" x-text="language.flag"></span>

                        <!-- Language Info -->
                        <div class="flex-1">
                            <div class="font-semibold text-sm" x-text="language.native_name"></div>
                            <div class="text-xs opacity-70" x-text="language.name"></div>
                        </div>

                        <!-- Active Indicator -->
                        <div x-show="language.code === currentLanguage.code">
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
    function languageSwitcher() {
        return {
            isOpen: false,
            currentLocale: @json(app()->getLocale()),
            languages: [],

            get currentLanguage() {
                return this.languages.find(lang => lang.code === this.currentLocale) || this.languages[0];
            },

            async init() {
                // Fetch active languages from cache
                try {
                    const response = await fetch('/api/languages/active');
                    if (response.ok) {
                        const data = await response.json();
                        this.languages = data.data || this.getDefaultLanguages();
                    } else {
                        this.languages = this.getDefaultLanguages();
                    }
                } catch (error) {
                    console.error('Failed to fetch languages:', error);
                    this.languages = this.getDefaultLanguages();
                }

                // Initialize Lucide icons
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                });
            },

            getDefaultLanguages() {
                return [
                    { code: 'ar', name: 'Arabic', native_name: 'العربية', direction: 'rtl', flag: '🇸🇦', active: true },
                    { code: 'en', name: 'English', native_name: 'English', direction: 'ltr', flag: '🇬🇧', active: true },
                    { code: 'fr', name: 'French', native_name: 'Français', direction: 'ltr', flag: '🇫🇷', active: true },
                    { code: 'tr', name: 'Turkish', native_name: 'Türkçe', direction: 'ltr', flag: '🇹🇷', active: true },
                    { code: 'ur', name: 'Urdu', native_name: 'اردو', direction: 'rtl', flag: '🇵🇰', active: true }
                ];
            },

            async switchLanguage(code) {
                if (code === this.currentLocale) {
                    this.isOpen = false;
                    return;
                }

                try {
                    // Call language switch endpoint
                    const response = await fetch('/language/switch', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        },
                        body: JSON.stringify({ language: code })
                    });

                    if (response.ok) {
                        // Reload page to apply new language
                        window.location.reload();
                    } else {
                        console.error('Failed to switch language');
                    }
                } catch (error) {
                    console.error('Error switching language:', error);
                }
            }
        }
    }
</script>

@once
    @push('scripts')
    <script>
        // Auto-reinitialize icons when Alpine updates
        document.addEventListener('alpine:initialized', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
    @endpush
@endonce
