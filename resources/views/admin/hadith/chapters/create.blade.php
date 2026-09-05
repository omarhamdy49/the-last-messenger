@extends("layouts.admin")
@section("page-header")<h1 class="text-3xl font-bold gradient-text">إضافة فصل</h1>@endsection
@section("content")
<form method="POST" action="{{ route('admin.hadith-chapters.store') }}">@csrf
@include("admin.hadith.chapters._form", ["chapter" => null])
</form>
@endsection
