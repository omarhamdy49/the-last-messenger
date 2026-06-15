@extends('layouts.admin')

@section('page-header')
<h1 class="text-3xl font-bold gradient-text">{{ __('quran.admin.edit_source') }}</h1>
@endsection

@section('content')
<div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <form method="POST" action="{{ route('admin.tafsir-resources.update', $resource->id) }}">
        @csrf
        @method('PUT')
        @include('admin.quran.tafsir-resources._form')
    </form>
</div>
@endsection
