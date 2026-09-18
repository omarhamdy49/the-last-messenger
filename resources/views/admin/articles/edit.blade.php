@extends('layouts.admin')

@section('title', __('articles.edit') . ' - ' . ($article->locales->first()->title ?? 'Untitled'))

@section('page-header')
<nav class="flex flex-wrap items-center gap-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-slate-500'" aria-label="breadcrumb">
    <a href="{{ route('admin.articles.index') }}" class="hover:text-accent transition-colors">{{ __('articles.title') }}</a>
    <i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180" aria-hidden="true"></i>
    <a href="{{ route('admin.articles.show', $article) }}" class="hover:text-accent transition-colors">{{ $article->locales->first()->title ?? 'Untitled' }}</a>
    <i data-lucide="chevron-left" class="w-4 h-4 ltr:rotate-180" aria-hidden="true"></i>
    <span aria-current="page">{{ __('admin.edit') }}</span>
</nav>
@endsection

@section('content')

@if(session('success'))
    <div class="wz-pill is-success !min-h-10 !px-4 mb-6" role="status">
        <i data-lucide="check-circle" class="w-4 h-4" aria-hidden="true"></i>{{ session('success') }}
    </div>
@endif

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
    'article' => $article,
    'formArticle' => $formArticle,
    'categories' => $categories,
    'writers' => $writers,
    'tags' => $tags,
    'locales' => $locales
])

<x-admin.media-picker-modal />

@endsection
