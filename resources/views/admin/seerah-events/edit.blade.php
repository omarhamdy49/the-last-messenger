@extends('layouts.admin')

@section('title', __('wizard.edit_record').' · '.$event->getTranslation('title', 'ar'))

@section('page-header')
<nav class="flex flex-wrap items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-slate-500'" aria-label="breadcrumb">
    <a href="{{ route('admin.seerah-events.index') }}" class="hover:text-accent transition-colors">أحداث السيرة النبوية</a>
    <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-0 ltr:rotate-180" aria-hidden="true"></i>
    <a href="{{ route('admin.seerah-events.show', $event->id) }}" class="hover:text-accent transition-colors">{{ $event->getTranslation('title', 'ar') }}</a>
    <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-0 ltr:rotate-180" aria-hidden="true"></i>
    <span aria-current="page">{{ __('admin.edit') }}</span>
</nav>
@endsection

@section('content')
<form action="{{ route('admin.seerah-events.update', $event->id) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')
    @include('admin.seerah-events._form', ['event' => $event])
</form>
@endsection
