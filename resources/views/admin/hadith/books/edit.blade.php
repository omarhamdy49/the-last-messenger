@extends("layouts.admin")
@section("page-header")<h1 class="text-3xl font-bold gradient-text">تعديل: {{ $book->title_ar }}</h1>@endsection
@section("content")
<form method="POST" action="{{ route('admin.hadith-books.update', $book) }}">@csrf @method("PUT")
@include("admin.hadith.books._form", ["book" => $book, "presetCollection" => null])
</form>
@endsection
