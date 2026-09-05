@extends('layouts.admin')
@section('page-header')
<h1 class="text-3xl font-bold gradient-text">تعديل: {{ $collection->title_ar }}</h1>
@endsection
@section('content')
<form method="POST" action="{{ route('admin.hadith-collections.update', $collection) }}">
    @csrf
    @method('PUT')
    @include('admin.hadith.collections._form', ['collection' => $collection])
</form>
@endsection
