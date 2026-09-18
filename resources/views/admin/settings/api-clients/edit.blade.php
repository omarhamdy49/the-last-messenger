@extends('layouts.admin')

@section('page-header')
<div>
    <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        <a href="{{ route('admin.settings.api-clients.index') }}" class="hover:text-accent transition-colors">{{ __('admin.api_clients.title') }}</a>
        <i data-lucide="chevron-left" class="w-4 h-4 rtl:block ltr:hidden"></i>
        <i data-lucide="chevron-right" class="w-4 h-4 ltr:block rtl:hidden"></i>
        <span>{{ $client->name }}</span>
    </div>
    <h1 class="text-3xl font-bold gradient-text">{{ __('admin.api_clients.edit') }}</h1>
</div>
@endsection

@section('content')
<div class="rounded-2xl border p-6" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <p class="mb-4 text-sm font-mono" dir="ltr" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.api_clients.key') }}: {{ $client->key }}</p>
    <form method="POST" action="{{ route('admin.settings.api-clients.update', $client) }}">
        @csrf
        @method('PUT')
        @include('admin.settings.api-clients._form')
        <div class="mt-4 flex justify-end gap-2">
            <a href="{{ route('admin.settings.api-clients.index') }}" class="px-5 py-2.5 rounded-xl border" :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
                {{ __('admin.api_clients.back') }}
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838]">
                {{ __('admin.api_clients.save') }}
            </button>
        </div>
    </form>
</div>
@endsection
