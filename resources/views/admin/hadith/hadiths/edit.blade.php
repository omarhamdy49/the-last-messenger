@extends("layouts.admin")
@section("page-header")<h1 class="text-3xl font-bold gradient-text">تعديل حديث</h1>@endsection
@section("content")
<form method="POST" action="{{ route('admin.hadiths.update', $hadith) }}">@csrf @method("PUT")
@include("admin.hadith.hadiths._form", ["hadith" => $hadith, "presetCollection" => null])
</form>
@endsection
