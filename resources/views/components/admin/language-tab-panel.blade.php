{{--
    Language Tab Panel Component
    Individual panel for each language's form fields

    Usage:
    <x-admin.language-tab-panel :language="$lang">
        <x-admin.form.text-input name="title_{{ $lang }}" label="Title" />
    </x-admin.language-tab-panel>
--}}

@props([
    'language' => 'en',
    'label' => null,
])

@php
    $languageData = [
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦', 'direction' => 'rtl'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'direction' => 'ltr'],
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷', 'direction' => 'ltr'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'direction' => 'ltr'],
        'ur' => ['name' => 'اردو', 'flag' => '🇵🇰', 'direction' => 'rtl'],
    ];

    $langData   = $languageData[$language] ?? ['name' => $language, 'flag' => '🌐', 'direction' => 'ltr'];
    $direction  = $langData['direction'];
    $isRtl      = $direction === 'rtl';
@endphp

<div x-show="activeLanguage === '{{ $language }}'"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     dir="{{ $direction }}"
     {{ $attributes->merge(['class' => 'space-y-5']) }}
     role="tabpanel"
     aria-labelledby="tab-{{ $language }}">

    {{ $slot }}

    @if($isRtl)
        <div x-data="{ showHint: true }" x-show="showHint" class="mt-2">
            <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-50 border border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/20">
                <i data-lucide="info" class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5"></i>
                <p class="text-xs text-blue-700 dark:text-blue-300 flex-1">
                    {{ $langData['name'] }} — RTL language. Text direction applied automatically.
                </p>
                <button type="button" @click="showHint = false" class="text-blue-400 hover:text-blue-600">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>
    @endif
</div>
