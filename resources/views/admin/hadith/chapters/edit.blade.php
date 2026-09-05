@extends("layouts.admin")
@section("page-header")<h1 class="text-3xl font-bold gradient-text">تعديل: {{ $chapter->title_ar }}</h1>@endsection
@section("content")
<form method="POST" action="{{ route('admin.hadith-chapters.update', $chapter) }}">@csrf @method("PUT")
@include("admin.hadith.chapters._form", ["chapter" => $chapter, "presetBook" => null])
</form>
@endsection
