@extends('layouts.admin')

@section('title', __('admin.edit').': '.($program->getTranslation('title', 'ar') ?? $program->slug))

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.media-programs.index') }}" class="hover:text-accent transition-colors">{{ __('admin.media_programs') }}</a>
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
            <a href="{{ route('admin.media-programs.show', $program->id) }}" class="hover:text-accent transition-colors">
                {{ $program->getTranslation('title', 'ar') ?? $program->slug }}
            </a>
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="pencil" class="w-7 h-7 text-white"></i>
            </div>
            {{ $program->getTranslation('title', 'ar') ?? $program->slug }}
        </h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.media-programs.show', $program->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="eye" class="w-5 h-5"></i>
            <span>{{ __('admin.view') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
    @include('admin.media-programs._form', ['program' => $program, 'categories' => $categories])
@endsection
