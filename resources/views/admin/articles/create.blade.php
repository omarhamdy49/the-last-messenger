@extends('layouts.admin')

@section('title', __('articles.create'))

@section('page-header')
<nav class="flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-slate-500'" aria-label="breadcrumb">
    <a href="{{ route('admin.articles.index') }}" class="hover:text-accent transition-colors">{{ __('articles.title') }}</a>
    <i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180" aria-hidden="true"></i>
    <span aria-current="page">{{ __('articles.create') }}</span>
</nav>
@endsection

@section('content')

{{-- Non-AJAX submit failures (quick save shows its own summary inside the wizard). --}}
@if($errors->any())
    <div class="wz-alert mb-6" role="alert">
        <p class="wz-alert-title"><i data-lucide="alert-octagon" aria-hidden="true"></i>{{ __('common.please_fix_errors') }}</p>
        <ul class="list-disc ps-5 text-sm">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

@include('admin.articles._form-wizard', [
    'article' => null,
    'categories' => $categories,
    'writers' => $writers,
    'tags' => $tags,
    'locales' => $locales
])

<x-admin.media-picker-modal />

@endsection
