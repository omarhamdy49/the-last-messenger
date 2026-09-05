@extends('layouts.admin')
@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text">{{ __('hadith_admin.hadith') }}</h1>
        <p class="mt-2 text-sm font-mono" dir="ltr" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ $hadith->collection?->slug }}:{{ $hadith->reference_key }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($hadith->is_published && $hadith->collection?->is_published)
        <a href="{{ url($hadith->publicPath()) }}" target="_blank" rel="noopener"
           class="px-5 py-2.5 rounded-xl font-semibold border" :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
            <i data-lucide="external-link" class="w-4 h-4 inline"></i> الصفحة العامة
        </a>
        @endif
        <a href="{{ route('admin.hadiths.edit', $hadith) }}" class="px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">{{ __('hadith_admin.edit') }}</a>
    </div>
</div>
@endsection
@section('content')
@include('admin.hadith._flash')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith_admin.text_ar') }}</h2>
            <p class="leading-loose text-lg" dir="rtl" :class="darkMode ? 'text-gray-100' : 'text-gray-900'">{{ $hadith->text_ar }}</p>
            @if($hadith->narrated_by)
            <p class="mt-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith_admin.narrated_by') }}: {{ $hadith->narrated_by }}</p>
            @endif
        </div>
        @if($hadith->text_en)
        <div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith_admin.text_en') }}</h2>
            <p class="leading-relaxed" dir="ltr" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ $hadith->text_en }}</p>
        </div>
        @endif
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border p-6 space-y-3 text-sm" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            @foreach([
                __('hadith_admin.collection') => $hadith->collection?->title_ar,
                __('hadith_admin.book') => $hadith->book?->title_ar,
                __('hadith_admin.chapter') => $hadith->chapter?->title_ar,
                __('hadith_admin.number') => $hadith->number,
                __('hadith_admin.grade') => $hadith->grade?->label('ar') ?? ($hadith->raw_grade ?: __('hadith_admin.ungraded')),
            ] as $label => $value)
                <div class="flex justify-between gap-3 border-b pb-2 last:border-0" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                    <span :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $label }}</span>
                    <span class="font-semibold text-end">{{ $value ?: '—' }}</span>
                </div>
            @endforeach
            <div class="flex justify-between items-center gap-3 pt-1">
                <span :class="darkMode ? 'text-gray-500' : 'text-gray-500'">الحالة</span>
                <x-admin.hadith-status-toggle :action="route('admin.hadiths.toggle-status', $hadith)" :published="$hadith->is_published" />
            </div>
        </div>
    </div>
</div>
@endsection
