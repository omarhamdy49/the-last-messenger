@extends('layouts.admin')

{{-- Shown once, as the direct response to create/rotate: the plain secret is never
     stored, flashed or retrievable afterwards. --}}

@section('page-header')
<h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
    <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
        <i data-lucide="key-round" class="w-7 h-7 text-white"></i>
    </div>
    {{ ($rotated ?? false) ? __('admin.api_clients.rotated_title') : __('admin.api_clients.created_title') }}
</h1>
@endsection

@section('content')
<div class="rounded-2xl border p-6 space-y-4" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'"
     x-data="{ copied: null, copy(value, which) { navigator.clipboard?.writeText(value); this.copied = which; } }">
    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-200" role="alert">
        {{ __('admin.api_clients.secret_once') }}
    </div>

    <p class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $client->name }}</p>

    <div>
        <span class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.api_clients.key') }}</span>
        <div class="flex gap-2" dir="ltr">
            <input type="text" readonly value="{{ $client->key }}" class="w-full rounded-xl border px-4 py-2.5 font-mono text-sm"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-300 text-gray-900'">
            <button type="button" @click="copy(@js($client->key), 'key')" class="rounded-xl border px-3" :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                <i data-lucide="copy" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <div>
        <span class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.api_clients.secret') }}</span>
        <div class="flex gap-2" dir="ltr">
            <input type="text" readonly value="{{ $secret }}" class="w-full rounded-xl border px-4 py-2.5 font-mono text-sm"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-gray-50 border-gray-300 text-gray-900'">
            <button type="button" @click="copy(@js($secret), 'secret')" class="rounded-xl border px-3" :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                <i data-lucide="copy" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    <a href="{{ route('admin.settings.api-clients.index') }}" class="inline-flex px-5 py-2.5 rounded-xl font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838]">
        {{ __('admin.api_clients.back') }}
    </a>
</div>
@endsection
