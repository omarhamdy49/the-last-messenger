@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            <a href="{{ route('admin.seerah-milestones.index') }}" class="hover:text-accent transition-colors">{{ __('admin.seerah_milestones') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-180"></i>
            <span>{{ $milestone->getTranslation('title', 'ar') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-br {{ $milestone->gradient ?: 'from-[#0056b3] to-[#218838]' }} shadow-lg"><i data-lucide="{{ $milestone->icon ?: 'milestone' }}" class="w-7 h-7 text-white"></i></div>
            {{ $milestone->getTranslation('title', 'ar') }}
        </h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.seerah-milestones.edit', $milestone->id) }}" class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"><i data-lucide="edit-2" class="w-5 h-5"></i> {{ __('admin.edit') }}</a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.translations') }}</h2>
            <div class="space-y-4">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                <div class="p-4 rounded-xl border" :class="darkMode ? 'border-gray-800' : 'border-gray-200'" dir="{{ in_array($lang, ['ar','ur']) ? 'rtl' : 'ltr' }}">
                    <p class="text-xs mb-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $label }}</p>
                    <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $milestone->getTranslation('title', $lang, false) ?: '—' }}</p>
                    @if($milestone->getTranslation('subtitle', $lang, false))<p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $milestone->getTranslation('subtitle', $lang, false) }}</p>@endif
                    @if($milestone->getTranslation('description', $lang, false))<p class="text-sm mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $milestone->getTranslation('description', $lang, false) }}</p>@endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border p-6 space-y-4" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.details') }}</h3>
            @foreach([
                ['admin.slug', $milestone->slug],
                ['admin.order', $milestone->order_index],
                ['admin.date_range', $milestone->hijriRangeLabel() ?: '—'],
                ['admin.gregorian', $milestone->gregorianRangeLabel() ?: '—'],
                ['admin.events', $eventsCount],
            ] as [$labelKey, $val])
            <div class="flex items-center justify-between text-sm gap-3">
                <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __($labelKey) }}</span>
                <span class="text-end" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $val }}</span>
            </div>
            @endforeach
            <div class="flex items-center justify-between text-sm">
                <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.status') }}</span>
                @if($milestone->is_active)
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ __('admin.active') }}</span>
                @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">{{ __('admin.inactive') }}</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>@endpush
