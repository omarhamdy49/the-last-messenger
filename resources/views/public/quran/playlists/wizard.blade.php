@extends('layouts.public')
@section('content')
{{-- stub: replaced in Task 6 --}}
<div class="container mx-auto px-4 py-10">
    <h1>{{ $playlist->name ?? __('quran.playlists.title') }}</h1>
    @isset($playlists)@foreach ($playlists as $card)<p>{{ $card['name'] }}</p>@endforeach @endisset
</div>
@endsection
