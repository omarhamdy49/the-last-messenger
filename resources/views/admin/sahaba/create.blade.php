@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="user-plus" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('sahaba.create_title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('sahaba.admin_all_subtitle') }}
        </p>
    </div>

    <a href="{{ route('admin.sahaba.index') }}"
       class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
       :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
        <i data-lucide="arrow-{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'right' : 'left' }}" class="w-5 h-5"></i>
        <span>{{ __('sahaba.back_to_list') }}</span>
    </a>
</div>
@endsection

@section('content')
<form action="{{ route('admin.sahaba.store') }}" method="POST" x-data="{ submitting: false }"
      @submit="submitting = true">
    @csrf

    @include('admin.sahaba._form', ['muslim' => null, 'roles' => $roles])

    <div class="flex flex-wrap items-center gap-3 mt-6">
        <button type="submit" :disabled="submitting"
                class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 shadow-lg flex items-center gap-2
                       disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100
                       bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i x-show="submitting" data-lucide="loader-2" class="w-5 h-5 animate-spin" style="display:none"></i>
            <i x-show="! submitting" data-lucide="save" class="w-5 h-5"></i>
            <span>{{ __('sahaba.save') }}</span>
        </button>

        <a href="{{ route('admin.sahaba.index') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105"
           :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>

{{-- Outside the form: a picker inside it would submit its own search input. --}}
<x-admin.muslim-picker-modal :roles="$roles" />
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
