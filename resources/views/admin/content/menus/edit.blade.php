@extends('layouts.admin')

@section('content')
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <a href="{{ route('admin.content.menus.index') }}" class="hover:text-accent transition-colors">{{ __('admin.content_menus') }}</a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.edit') }}</span>
    </nav>

    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_menu_edit_item') }}</h1>
    </div>

    @include('admin.content._flash')
    @include('admin.content.menus._form')
</div>
@endsection
