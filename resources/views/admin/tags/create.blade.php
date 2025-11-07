@extends('layouts.admin')

@section('content')
<div>
    <!-- Breadcrumbs -->
    <nav class="mb-6 flex items-center gap-2 text-sm"
         :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}"
           class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <a href="{{ route('admin.tags.index') }}"
           class="hover:text-accent transition-colors">
            Tags
        </a>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">Create</span>
    </nav>

    <!-- Page Header -->
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold gradient-text mb-2">Create New Tag</h1>
        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Add a new tag to organize your content
        </p>
    </div>

    <!-- Form -->
    @include('admin.tags._form')
</div>
@endsection
