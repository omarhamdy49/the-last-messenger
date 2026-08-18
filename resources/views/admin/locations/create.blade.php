@extends('layouts.admin')

@section('page-header')
<h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
    <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
        <i data-lucide="map-pin" class="w-7 h-7 text-white"></i>
    </div>
    {{ __('locations.create') }}
</h1>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.locations.store') }}">
    @csrf
    @include('admin.locations._form', ['location' => null])
</form>
@endsection
