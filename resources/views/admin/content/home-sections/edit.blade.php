@extends('layouts.admin')

@section('content')
@php
    $locales = ['ar', 'en', 'fr', 'tr', 'ur'];
    $inputClasses = 'w-full px-4 py-3 rounded-lg border-2 transition-all font-cairo';
@endphp

<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.content.home-sections.index') }}" class="hover:text-accent transition-colors">
            {{ __('admin.content_home_sections') }}
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span>{{ $section->key }}</span>
    </nav>

    @include('admin.content._flash')

    <form method="POST"
          action="{{ route('admin.content.home-sections.update', $section->id) }}"
          x-data="{ activeLanguage: 'ar', submitting: false }"
          @submit="submitting = true"
          class="max-w-4xl mx-auto space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl overflow-hidden shadow-xl border"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="p-6 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <p class="mb-4 font-mono text-sm" dir="ltr">{{ $section->key }}</p>
                @include('admin.content._language_tabs', ['errorFields' => ['heading', 'subheading']])
            </div>

            <div class="p-6">
                @foreach ($locales as $locale)
                    <div x-show="activeLanguage === '{{ $locale }}'"
                         x-cloak
                         class="space-y-6"
                         dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                        <div>
                            <label class="block mb-2 font-semibold">{{ __('admin.title') }}</label>
                            <input type="text"
                                   name="heading[{{ $locale }}]"
                                   value="{{ old('heading.'.$locale, $section->headings[$locale] ?? '') }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                            @error("heading.{$locale}")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block mb-2 font-semibold">{{ __('admin.subtitle') }}</label>
                            <textarea name="subheading[{{ $locale }}]"
                                      rows="4"
                                      class="{{ $inputClasses }}"
                                      :class="darkMode ? 'bg-gray-950 border-gray-700 text-white focus:border-accent' : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">{{ old('subheading.'.$locale, $section->subheadings[$locale] ?? '') }}</textarea>
                            @error("subheading.{$locale}")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('admin.content.home-sections.index') }}"
               class="px-6 py-3 rounded-lg font-semibold border-2"
               :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
                {{ __('admin.cancel') }}
            </a>
            <button type="submit"
                    :disabled="submitting"
                    class="px-8 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg disabled:opacity-60 flex items-center gap-2">
                <i data-lucide="save" class="w-5 h-5" x-show="!submitting"></i>
                <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting" x-cloak></i>
                <span>{{ __('admin.save_changes') }}</span>
            </button>
        </div>
    </form>
</div>
@endsection
