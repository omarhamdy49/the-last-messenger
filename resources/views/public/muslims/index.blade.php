@extends('layouts.app')

@section('title', __('people.title'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">{{ __('people.title') }}</h1>

    <form method="GET" class="mb-6 flex flex-wrap gap-3 items-end" data-testid="muslims-filters">
        <div>
            <label for="q" class="block text-sm mb-1">{{ __('people.search') }}</label>
            <input id="q" name="q" value="{{ request('q') }}"
                   class="border rounded-lg px-3 py-2" data-testid="muslims-search-input">
        </div>
        <div>
            <label for="role" class="block text-sm mb-1">{{ __('people.roles') }}</label>
            <select id="role" name="role" class="border rounded-lg px-3 py-2" data-testid="muslims-role-filter">
                <option value="">{{ __('people.all_roles') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>
                        {{ $role->name() }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-5 py-2 rounded-lg bg-[#0056b3] text-white" data-testid="muslims-filter-submit">
            {{ __('people.apply') }}
        </button>
    </form>

    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-testid="muslims-list">
        @forelse ($people as $person)
            <li class="border rounded-xl p-4" data-testid="muslim-card">
                <a href="{{ route('public.muslims.show', $person->slug) }}"
                   class="text-lg font-semibold" data-testid="muslim-card-link">
                    {{ $person->name() }}
                </a>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($person->roles as $role)
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100">{{ $role->name() }}</span>
                    @endforeach
                </div>
            </li>
        @empty
            <li data-testid="muslims-empty">{{ __('people.none_found') }}</li>
        @endforelse
    </ul>

    <div class="mt-8">{{ $people->links() }}</div>
</div>
@endsection
