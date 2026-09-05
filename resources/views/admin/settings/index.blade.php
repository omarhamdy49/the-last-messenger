@extends('layouts.admin')

{{--
    Settings hub. The Commands tab is rendered inline; Languages and Media
    presets are full pages of their own, so their tabs navigate rather than
    duplicating those screens here.
--}}

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">{{ __('admin.dashboard') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4 rtl:block ltr:hidden"></i>
            <i data-lucide="chevron-right" class="w-4 h-4 ltr:block rtl:hidden"></i>
            <span>{{ __('admin.settings') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="settings" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.settings') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('admin.settings_desc') }}
        </p>
    </div>
</div>
@endsection

@section('content')

{{-- Tabs --}}
<div class="mb-6 border-b overflow-x-auto custom-scrollbar" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
    <nav class="flex gap-1 min-w-max" aria-label="{{ __('admin.settings') }}">
        <span class="px-4 py-3 text-sm font-semibold border-b-2 border-[#0056b3] text-[#0056b3] flex items-center gap-2"
              aria-current="page">
            <i data-lucide="terminal" class="w-4 h-4"></i>
            {{ __('admin.cmd_tab') }}
        </span>

        <a href="{{ route('admin.settings.languages.index') }}"
           class="px-4 py-3 text-sm font-semibold border-b-2 border-transparent transition-colors flex items-center gap-2"
           :class="darkMode ? 'text-gray-400 hover:text-white hover:border-gray-700' : 'text-gray-600 hover:text-gray-900 hover:border-gray-300'">
            <i data-lucide="languages" class="w-4 h-4"></i>
            {{ __('admin.languages') }}
        </a>

        <a href="{{ route('admin.media-library.settings') }}"
           class="px-4 py-3 text-sm font-semibold border-b-2 border-transparent transition-colors flex items-center gap-2"
           :class="darkMode ? 'text-gray-400 hover:text-white hover:border-gray-700' : 'text-gray-600 hover:text-gray-900 hover:border-gray-300'">
            <i data-lucide="image" class="w-4 h-4"></i>
            {{ __('admin.media_settings') }}
        </a>
    </nav>
</div>

@include('admin.settings._commands')

@if($maySeeBackups ?? false)
    @include('admin.settings._backups')
@endif

@endsection
