@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            <a href="{{ route('admin.seerah-milestones.index') }}" class="hover:text-accent transition-colors">{{ __('admin.seerah_milestones') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-180"></i>
            <span>{{ __('admin.edit_milestone') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg"><i data-lucide="edit-2" class="w-7 h-7 text-white"></i></div>
            {{ $milestone->getTranslation('title', 'ar') }}
        </h1>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.seerah-milestones.update', $milestone->id) }}" method="POST" x-data="{ activeTab: 'ar', submitting: false }" @submit="submitting = true">
    @csrf
    @method('PUT')
    @include('admin.seerah-milestones._form', ['milestone' => $milestone])
</form>
@endsection

@push('scripts')<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>@endpush
