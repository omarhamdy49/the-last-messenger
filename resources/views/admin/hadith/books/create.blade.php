@extends("layouts.admin")
@section("page-header")<h1 class="text-3xl font-bold gradient-text">إضافة باب</h1>@endsection
@section("content")
<form method="POST" action="{{ route('admin.hadith-books.store') }}">@csrf
@include("admin.hadith.books._form", ["book" => null])
</form>
@endsection
