@extends('layouts.admin')

@section('title', __('articles.create'))

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.articles.index') }}" class="hover:text-accent transition-colors">{{ __('articles.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('articles.create') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="plus" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('articles.create') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.articles.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')

{{-- Validation Errors --}}
@if($errors->any())
    <div class="mb-6 p-4 rounded-lg border-l-4 border-red-500 animate-fadeInUp"
         :class="darkMode ? 'bg-red-900/20 border-red-500' : 'bg-red-50 border-red-500'">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <h3 class="font-bold text-red-500 mb-2">
                    {{ __('common.please_fix_errors') }}
                </h3>
                <ul class="list-disc list-inside space-y-1 text-sm"
                    :class="darkMode ? 'text-red-400' : 'text-red-600'">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

{{-- Success Alert --}}
@if(session('success'))
    <div class="mb-6 p-4 rounded-lg border-l-4 border-green-500 animate-fadeInUp"
         :class="darkMode ? 'bg-green-900/20' : 'bg-green-50'">
        <div class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold"
                   :class="darkMode ? 'text-green-400' : 'text-green-600'">
                    {{ session('success') }}
                </p>
            </div>
        </div>
    </div>
@endif

{{-- Article Wizard Form --}}
@include('admin.articles._form-wizard', [
    'article' => null,
    'categories' => $categories,
    'writers' => $writers,
    'tags' => $tags,
    'locales' => $locales
])

{{-- Media Picker Modal --}}
<x-admin.media-picker-modal />

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endpush
