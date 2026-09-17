@extends('layouts.admin')

@section('title', __('wizard.new_record').' · '.__('wizard.seerah.eyebrow'))

@section('page-header')
<nav class="flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-slate-500'" aria-label="breadcrumb">
    <a href="{{ route('admin.seerah-events.index') }}" class="hover:text-accent transition-colors">أحداث السيرة النبوية</a>
    <i data-lucide="chevron-left" class="w-4 h-4 rtl:rotate-0 ltr:rotate-180" aria-hidden="true"></i>
    <span aria-current="page">إضافة حدث جديد</span>
</nav>
@endsection

@section('content')
<form action="{{ route('admin.seerah-events.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.seerah-events._form', ['event' => null])
</form>
@endsection
