@extends('layouts.admin')

@php
    $locales = enabled_content_languages();
    $byLocale = $context->translationsByLocale();
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.revelation-contexts.index') }}" class="hover:text-accent transition-colors">{{ __('quran.asbab.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-0 ltr:rotate-180"></i>
            <span class="truncate">{{ $context->getTranslation('title', 'ar') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="scroll-text" class="w-7 h-7 text-white"></i>
            </div>
            <span class="truncate">{{ $context->getTranslation('title', 'ar') }}</span>
        </h1>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.revelation-contexts.edit', $context->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <form action="{{ route('admin.revelation-contexts.destroy', $context->id) }}"
              method="POST"
              class="inline"
              @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 border-red-300 text-red-600 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                <span>{{ __('admin.delete') }}</span>
            </button>
        </form>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Translations -->
    <div class="lg:col-span-2 space-y-6">
        @foreach($locales as $code => $label)
            @php $translation = $byLocale->get($code); @endphp
            @if($translation && ($translation->title || $translation->narrative))
                <div class="rounded-2xl border p-6"
                     :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
                     dir="{{ in_array($code, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                        <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $label }}</h2>
                        @if($translation->is_ready)
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ __('quran.asbab.translation_ready') }}</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('admin.draft') }}</span>
                        @endif
                    </div>
                    <h3 class="text-xl font-bold mb-3" :class="darkMode ? 'text-gray-100' : 'text-gray-900'" style="word-break: break-word;">
                        {{ $translation->title }}
                    </h3>
                    <p class="leading-relaxed whitespace-pre-line" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" style="word-break: break-word;">
                        {{ $translation->narrative }}
                    </p>
                    @if($translation->source_reference)
                        <div class="pt-4 mt-4 border-t" :class="darkMode ? 'border-gray-700 text-gray-400' : 'border-gray-200 text-gray-600'">
                            <span class="font-semibold">{{ __('quran.asbab.source_reference') }}:</span>
                            {{ $translation->source_reference }}
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>

    <!-- Metadata -->
    <div class="space-y-6">
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('quran.asbab.verse_reference') }}</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-2">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.asbab.surah') }}</dt>
                    <dd class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-900'">{{ $context->surah?->name_ar }} ({{ $context->surah?->revelation_order }})</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.asbab.verses_label') }}</dt>
                    <dd class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-900'">{{ $context->verse_range }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.asbab.period') }}</dt>
                    <dd>
                        @if($context->period === 'makki')
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ __('quran.asbab.period_makki') }}</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ __('quran.asbab.period_madani') }}</span>
                        @endif
                    </dd>
                </div>
                @if($context->event_key)
                <div class="flex justify-between gap-2">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.asbab.event_key') }}</dt>
                    <dd class="font-mono text-xs" dir="ltr" :class="darkMode ? 'text-gray-200' : 'text-gray-900'">{{ $context->event_key }}</dd>
                </div>
                @endif
                <div class="flex justify-between gap-2">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('quran.asbab.display_order') }}</dt>
                    <dd class="font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-900'">{{ $context->display_order }}</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.status') }}</dt>
                    <dd>
                        @if($context->is_active)
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ __('quran.asbab.active') }}</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ __('quran.asbab.inactive') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
