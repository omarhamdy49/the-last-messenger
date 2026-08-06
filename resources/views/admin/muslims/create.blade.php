@extends('layouts.admin')

@section('page-header')
<h1 class="text-3xl font-bold gradient-text">{{ __('people.add') }}</h1>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.muslims.store') }}" data-testid="admin-muslim-form">
    @csrf
    @include('admin.muslims._form', ['muslim' => null, 'roles' => $roles])

    <button type="submit"
            class="px-6 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"
            data-testid="admin-muslim-submit">
        {{ __('people.save') }}
    </button>
</form>
@endsection
