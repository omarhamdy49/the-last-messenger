@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
        <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
            <i data-lucide="map-pin" class="w-7 h-7 text-white"></i>
        </div>
        {{ __('locations.edit') }} — {{ $location->name() ?? $location->slug }}
    </h1>
    <a href="{{ route('admin.locations.show', $location->id) }}"
       class="px-5 py-2.5 rounded-xl font-semibold" :class="darkMode ? 'bg-gray-800 text-gray-200' : 'bg-gray-100 text-gray-700'">
        {{ __('locations.show') }}
    </a>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.locations.update', $location->id) }}">
    @csrf
    @method('PUT')
    @include('admin.locations._form')
</form>
@endsection
