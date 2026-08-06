@extends('layouts.admin')

@section('page-header')
<h1 class="text-3xl font-bold gradient-text">{{ __('people.edit') }} — {{ $muslim->name() }}</h1>
@endsection

@section('content')
@if (session('success'))
    <div class="mb-4 px-4 py-3 rounded-xl bg-green-100 text-green-800" data-testid="admin-flash">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.muslims.update', $muslim) }}" data-testid="admin-muslim-form">
    @csrf
    @method('PUT')
    @include('admin.muslims._form', ['muslim' => $muslim, 'roles' => $roles])

    <div class="flex gap-3">
        <button type="submit"
                class="px-6 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"
                data-testid="admin-muslim-submit">
            {{ __('people.save') }}
        </button>
        <a href="{{ route('public.muslims.tree', $muslim->slug) }}"
           class="px-6 py-2.5 rounded-xl border" data-testid="admin-muslim-view-tree">
            {{ __('people.tree') }}
        </a>
    </div>
</form>

@if ($relations !== [])
    <section class="mt-8">
        <h2 class="text-xl font-semibold mb-3">{{ __('people.relations') }}</h2>
        <ul class="grid gap-2" data-testid="admin-muslim-relations">
            @foreach ($relations as $relation)
                <li>{{ $relation['name'] }} <span class="text-sm text-gray-500">— {{ $relation['label'] }}</span></li>
            @endforeach
        </ul>
    </section>
@endif
@endsection
