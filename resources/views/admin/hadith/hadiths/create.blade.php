@extends("layouts.admin")
@section("page-header")<h1 class="text-3xl font-bold gradient-text">إضافة حديث</h1>@endsection
@section("content")
<form method="POST" action="{{ route('admin.hadiths.store') }}">@csrf
@include("admin.hadith.hadiths._form", ["hadith" => null])
</form>
@endsection
