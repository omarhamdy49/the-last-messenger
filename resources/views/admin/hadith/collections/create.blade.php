@extends('layouts.admin')
@section('page-header')
<h1 class="text-3xl font-bold gradient-text">إضافة كتاب حديث</h1>
@endsection
@section('content')
<form method="POST" action="{{ route('admin.hadith-collections.store') }}">
    @csrf
    @include('admin.hadith.collections._form', ['collection' => null])
</form>
@endsection
