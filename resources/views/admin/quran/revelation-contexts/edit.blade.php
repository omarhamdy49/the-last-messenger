@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.revelation-contexts.index') }}" class="hover:text-accent transition-colors">{{ __('quran.asbab.title') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-0 ltr:rotate-180"></i>
            <span>{{ __('quran.asbab.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            <span class="truncate">{{ __('quran.asbab.edit') }} — {{ $context->getTranslation('title', 'ar') }}</span>
        </h1>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.revelation-contexts.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.revelation-contexts.update', $context->id) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.quran.revelation-contexts._form', ['context' => $context])
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
