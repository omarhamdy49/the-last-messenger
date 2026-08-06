@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="users" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('people.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('people.admin_subtitle') }}
        </p>
    </div>

    <a href="{{ route('admin.muslims.create') }}"
       class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
              bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"
       data-testid="admin-muslim-create">
        <i data-lucide="plus" class="w-5 h-5"></i>
        <span>{{ __('people.add') }}</span>
    </a>
</div>
@endsection

@section('content')
@if (session('success'))
    <div class="mb-4 px-4 py-3 rounded-xl bg-green-100 text-green-800" data-testid="admin-flash">
        {{ session('success') }}
    </div>
@endif

<form method="GET" class="mb-6 flex flex-wrap gap-3 items-end">
    <input name="q" value="{{ request('q') }}" placeholder="{{ __('people.search') }}"
           class="border rounded-lg px-3 py-2" data-testid="admin-muslim-search">
    <select name="role" class="border rounded-lg px-3 py-2" data-testid="admin-muslim-role-filter">
        <option value="">{{ __('people.all_roles') }}</option>
        @foreach ($roles as $role)
            <option value="{{ $role->slug }}" @selected(request('role') === $role->slug)>{{ $role->name() }}</option>
        @endforeach
    </select>
    <button class="px-5 py-2 rounded-lg bg-[#0056b3] text-white">{{ __('people.apply') }}</button>
</form>

<table class="w-full text-start" data-testid="admin-muslims-table">
    <thead>
        <tr class="text-start">
            <th class="text-start p-3">{{ __('people.name') }}</th>
            <th class="text-start p-3">{{ __('people.roles') }}</th>
            <th class="text-start p-3">{{ __('people.status') }}</th>
            <th class="text-start p-3">{{ __('people.actions') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($people as $person)
            <tr data-testid="admin-muslim-row" data-slug="{{ $person->slug }}">
                <td class="p-3">{{ $person->name() }}</td>
                <td class="p-3">
                    @foreach ($person->roles as $role)
                        <span class="text-xs px-2 py-1 rounded-full bg-gray-100">{{ $role->name() }}</span>
                    @endforeach
                </td>
                <td class="p-3">
                    <span data-testid="admin-muslim-status">
                        {{ $person->is_published ? __('people.published') : __('people.unpublished') }}
                    </span>
                </td>
                <td class="p-3">
                    <a href="{{ route('admin.muslims.edit', $person) }}"
                       class="text-[#0056b3]" data-testid="admin-muslim-edit">{{ __('people.edit') }}</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="p-3" data-testid="admin-muslims-empty">{{ __('people.none_found') }}</td></tr>
        @endforelse
    </tbody>
</table>

<div class="mt-6">{{ $people->links() }}</div>
@endsection
