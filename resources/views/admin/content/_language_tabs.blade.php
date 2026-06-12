{{-- Language tab bar — requires `activeLanguage` in the surrounding x-data scope.
     Optional: $errorFields (array of translatable field names) to flag tabs with errors. --}}
@php
    $contentLanguages = [
        'ar' => ['name' => 'العربية', 'flag' => '🇸🇦'],
        'en' => ['name' => 'English', 'flag' => '🇬🇧'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷'],
        'ur' => ['name' => 'اردو', 'flag' => '🇵🇰'],
    ];
    $errorFields = $errorFields ?? [];
@endphp

<div class="flex flex-wrap gap-2 mb-6 overflow-x-auto">
    @foreach ($contentLanguages as $langCode => $langMeta)
        @php
            $hasError = collect($errorFields)
                ->contains(fn (string $field): bool => $errors->has("{$field}.{$langCode}"));
        @endphp
        <button type="button"
                @click="activeLanguage = '{{ $langCode }}'"
                class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2 whitespace-nowrap flex-shrink-0"
                :class="activeLanguage === '{{ $langCode }}'
                    ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                    : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
            <span>{{ $langMeta['flag'] }}</span>
            <span>{{ $langMeta['name'] }}</span>
            @if ($hasError)
                <span class="inline-block w-2 h-2 bg-red-500 rounded-full"></span>
            @endif
        </button>
    @endforeach
</div>
