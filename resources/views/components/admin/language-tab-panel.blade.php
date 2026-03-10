{{--
    Language Tab Panel Component
    Individual panel for each language's form fields

    Usage:
    <x-admin.language-tab-panel :language="$lang" :label="$label">
        <x-admin.form.text-input name="title_{{ $lang }}" label="Title" />
    </x-admin.language-tab-panel>
--}}

@props([
    'language' => 'en',
    'label' => null,
    'showHeader' => false,
    'headerClass' => '',
])

@php
    $languageData = [
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'direction' => 'rtl'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'direction' => 'ltr'],
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷', 'direction' => 'ltr'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'direction' => 'ltr'],
        'ur' => ['name' => 'اردو', 'flag' => '🇵🇰', 'direction' => 'rtl'],
    ];

    $langData = $languageData[$language] ?? ['name' => $language, 'flag' => '🌐', 'direction' => 'ltr'];
    $direction = $langData['direction'];
    $displayLabel = $label ?? $langData['name'];
@endphp

<div :class="{
        'block': activeLanguage === '{{ $language }}',
        'hidden': activeLanguage !== '{{ $language }}'
     }"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-4"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     data-language="{{ $language }}"
     dir="{{ $direction }}"
     {{ $attributes->merge(['class' => 'language-tab-panel']) }}
     role="tabpanel"
     aria-labelledby="tab-{{ $language }}">

    {{-- Optional Panel Header --}}
    @if($showHeader)
        <div class="flex items-center justify-between mb-6 pb-4 border-b"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">

            <div class="flex items-center gap-3">
                <span class="text-2xl">{{ $langData['flag'] }}</span>
                <div>
                    <h3 class="text-lg font-bold"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $displayLabel }}
                    </h3>
                    <p class="text-xs"
                       :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        @if($direction === 'rtl')
                            Right-to-Left (RTL) Language
                        @else
                            Left-to-Right (LTR) Language
                        @endif
                    </p>
                </div>
            </div>

            {{-- Translation Status Badge --}}
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg"
                     :class="[
                        getCompletionPercentage('{{ $language }}') === 100 ?
                            'bg-green-500/10 text-green-500' :
                            (getCompletionPercentage('{{ $language }}') > 0 ?
                                'bg-yellow-500/10 text-yellow-500' :
                                'bg-gray-500/10 text-gray-500')
                     ]">
                    <template x-if="getCompletionPercentage('{{ $language }}') === 100">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                    </template>
                    <template x-if="getCompletionPercentage('{{ $language }}') > 0 && getCompletionPercentage('{{ $language }}') < 100">
                        <i data-lucide="clock" class="w-4 h-4"></i>
                    </template>
                    <template x-if="getCompletionPercentage('{{ $language }}') === 0">
                        <i data-lucide="circle" class="w-4 h-4"></i>
                    </template>
                    <span class="text-sm font-semibold"
                          x-text="`${getCompletionPercentage('{{ $language }}')}% Complete`"></span>
                </div>
            </div>
        </div>
    @endif

    {{-- Panel Content --}}
    <div class="space-y-6">
        {{ $slot }}
    </div>

    {{-- Helpful Hints for RTL Languages --}}
    @if($direction === 'rtl')
        <div x-data="{ showHint: true }" x-show="showHint" class="mt-6">
            <div class="flex items-start gap-3 p-4 rounded-lg"
                 :class="darkMode ? 'bg-blue-500/10 border border-blue-500/20' : 'bg-blue-50 border border-blue-200'">
                <i data-lucide="info" class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold"
                       :class="darkMode ? 'text-blue-400' : 'text-blue-700'">
                        RTL Language Mode
                    </p>
                    <p class="text-xs mt-1"
                       :class="darkMode ? 'text-blue-300/70' : 'text-blue-600'">
                        This language uses right-to-left text direction. Content will automatically align correctly.
                    </p>
                </div>
                <button type="button"
                        @click="showHint = false"
                        class="text-blue-500 hover:text-blue-600 transition-colors">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    @endif
</div>
