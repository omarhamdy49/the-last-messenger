@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.settings') }}" class="hover:text-accent transition-colors">{{ __('admin.settings') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4 rtl:block ltr:hidden"></i>
            <i data-lucide="chevron-right" class="w-4 h-4 ltr:block rtl:hidden"></i>
            <span>{{ __('admin.api_clients.title') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="key-round" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.api_clients.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.api_clients.description') }}</p>
        <p class="mt-1 text-xs font-mono" dir="ltr" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('admin.api_clients.usage_hint') }}</p>
    </div>
</div>
@endsection

@section('content')
@if (session('success'))
    <div class="mb-4 rounded-xl border border-green-300 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300" role="status">
        {{ session('success') }}
    </div>
@endif

<div class="mb-6 rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <h2 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
        <i data-lucide="plus-circle" class="w-5 h-5 text-accent"></i>
        {{ __('admin.api_clients.create') }}
    </h2>
    <form method="POST" action="{{ route('admin.settings.api-clients.store') }}">
        @csrf
        @include('admin.settings.api-clients._form')
        <div class="mt-4 flex justify-end">
            <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838]">
                {{ __('admin.api_clients.create') }}
            </button>
        </div>
    </form>
</div>

<div class="rounded-2xl border overflow-x-auto" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    @if ($clients->isEmpty())
        <p class="p-6 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.api_clients.empty') }}</p>
    @else
        <table class="w-full text-sm">
            <thead :class="darkMode ? 'bg-gray-800/60 text-gray-300' : 'bg-gray-50 text-gray-600'">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('admin.api_clients.name') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.api_clients.key') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.api_clients.scopes') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.api_clients.rate_limit') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.api_clients.last_used') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('admin.api_clients.status') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('admin.api_clients.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y" :class="darkMode ? 'divide-gray-800 text-gray-200' : 'divide-gray-100 text-gray-800'">
                @foreach ($clients as $client)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $client->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs" dir="ltr">{{ $client->key }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ implode(', ', $client->scopes ?? []) }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $client->rate_limit_per_minute }}</td>
                        <td class="px-4 py-3">{{ $client->last_used_at?->diffForHumans() ?? __('admin.api_clients.never_used') }}</td>
                        <td class="px-4 py-3">
                            @if ($client->isUsable())
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">{{ __('admin.api_clients.active') }}</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ __('admin.api_clients.inactive') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.settings.api-clients.edit', $client) }}" class="rounded-lg border px-3 py-1.5 text-xs"
                                   :class="darkMode ? 'border-gray-700' : 'border-gray-300'">{{ __('admin.api_clients.edit') }}</a>
                                <form method="POST" action="{{ route('admin.settings.api-clients.toggle', $client) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs" :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                                        {{ $client->is_active ? __('admin.api_clients.revoke') : __('admin.api_clients.enable') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.settings.api-clients.rotate', $client) }}"
                                      x-data @submit="if (!window.confirm(@js(__('admin.api_clients.rotate_confirm')))) $event.preventDefault()">
                                    @csrf
                                    <button type="submit" class="rounded-lg border px-3 py-1.5 text-xs" :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                                        {{ __('admin.api_clients.rotate') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.settings.api-clients.destroy', $client) }}"
                                      x-data @submit="if (!window.confirm(@js(__('admin.api_clients.delete_confirm')))) $event.preventDefault()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs text-red-600">
                                        {{ __('admin.api_clients.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4">{{ $clients->links() }}</div>
    @endif
</div>
@endsection
