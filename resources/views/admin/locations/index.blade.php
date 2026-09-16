@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="map-pin" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('locations.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            {{ __('locations.index_description') }}
        </p>
    </div>

    <a href="{{ route('admin.locations.create') }}"
       class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
              bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
        <i data-lucide="plus" class="w-5 h-5"></i>
        <span>{{ __('locations.create') }}</span>
    </a>
</div>
@endsection

@section('content')
<div>
    @if(session('error'))
        <div class="mb-6 p-4 rounded-xl border bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 rounded-xl border bg-green-50 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Filters. Every name here is allowlisted server-side. --}}
    <div class="mb-6 p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.locations.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.filters.search') }}</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="{{ __('locations.filters.search') }}"
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3] focus:border-transparent"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.fields.type') }}</label>
                <select name="type"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('locations.filters.all_types') }}</option>
                    @foreach($types as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>
                            {{ __('locations.types.'.$type->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>{{ __('locations.filters.search') }}</span>
                </button>
            </div>

            <label class="flex items-center gap-2 text-sm md:col-span-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                <input type="checkbox" name="has_coordinates" value="1" @checked(request('has_coordinates'))
                       class="rounded border-gray-300 text-[#0056b3] focus:ring-[#0056b3]">
                {{ __('locations.filters.with_coordinates') }}
            </label>
        </form>
    </div>

    {{-- Tree. `depth` is derived on save, so indentation is a read, not a walk. --}}
    <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="overflow-x-auto">
            <table class="w-full text-start">
                <thead :class="darkMode ? 'bg-gray-800/60' : 'bg-gray-50'">
                    <tr class="text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        <th class="px-6 py-4 text-start">{{ __('locations.fields.name') }}</th>
                        <th class="px-6 py-4 text-start">{{ __('locations.fields.type') }}</th>
                        <th class="px-6 py-4 text-start">{{ __('locations.fields.parent') }}</th>
                        <th class="px-6 py-4 text-start">{{ __('locations.fields.coordinates') }}</th>
                        <th class="px-6 py-4 text-start">{{ __('locations.fields.is_active') }}</th>
                        <th class="px-6 py-4 text-end">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $location)
                        <tr class="border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2" style="padding-inline-start: {{ $location->depth * 1.25 }}rem">
                                    @if($location->depth > 0)
                                        <i data-lucide="corner-down-right" class="w-4 h-4 opacity-40 flex-shrink-0 rtl:-scale-x-100"></i>
                                    @endif
                                    <a href="{{ route('admin.locations.edit', $location->id) }}"
                                       class="font-semibold hover:underline" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $location->name() ?? $location->slug }}
                                    </a>
                                    <span class="text-xs opacity-50">{{ $location->slug }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                {{ __('locations.types.'.$location->type->value) }}
                            </td>
                            <td class="px-6 py-4 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                {{ $location->parent?->name() ?? $location->parent?->slug ?? __('locations.fields.no_parent') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($location->hasCoordinates())
                                    <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                        <i data-lucide="map-pin" class="w-3 h-3"></i>{{ $location->latitude }}, {{ $location->longitude }}
                                    </span>
                                @else
                                    <span class="text-xs opacity-50">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.locations.toggle-active', $location->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs px-2.5 py-1 rounded-lg {{ $location->is_active
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'
                                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $location->is_active ? __('locations.fields.is_active') : __('locations.actions.activate') }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.locations.edit', $location->id) }}"
                                       class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="{{ __('admin.edit') }}">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.locations.destroy', $location->id) }}"
                                          onsubmit="return window.confirmSubmit(this, { message: {{ Js::from(__('admin.confirm_delete')) }} })">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30" title="{{ __('admin.delete') }}">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center opacity-60">{{ __('locations.messages.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $locations->withQueryString()->links() }}</div>
</div>
@endsection
