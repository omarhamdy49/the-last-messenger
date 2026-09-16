@extends('layouts.admin')

@section('content')
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_menus') }}</span>
    </nav>

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_menus') }}</h1>
            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_menus_hint') }}</p>
        </div>
        <a href="{{ route('admin.content.menus.create', ['location' => $activeLocation->value]) }}"
           class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.content_menu_add_item') }}</span>
        </a>
    </div>

    @include('admin.content._flash')

    {{-- Location tabs --}}
    <div class="flex flex-wrap gap-2 mb-6 overflow-x-auto">
        @foreach ($locations as $location)
            <a href="{{ route('admin.content.menus.index', ['location' => $location->value]) }}"
               class="px-4 py-2 rounded-lg font-semibold transition-all whitespace-nowrap flex-shrink-0 {{ $location === $activeLocation ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
               @if ($location !== $activeLocation) :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" @endif>
                {{ __('admin.content_menu_location_'.$location->value) }}
            </a>
        @endforeach
    </div>

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if ($items->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="menu" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
                <p class="font-semibold mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_menu_empty') }}</p>
                <a href="{{ route('admin.content.menus.create', ['location' => $activeLocation->value]) }}"
                   class="inline-flex items-center gap-2 text-accent font-semibold hover:underline">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>{{ __('admin.content_menu_add_item') }}</span>
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_label') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_target') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_parent') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr class="border-b last:border-0 transition-colors"
                                :class="darkMode ? 'border-gray-800 hover:bg-gray-800/40' : 'border-gray-100 hover:bg-gray-50'">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 {{ $item->parent_id ? 'ps-6' : '' }}">
                                        @if ($item->parent_id)
                                            <i data-lucide="corner-down-{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'left' : 'right' }}" class="w-4 h-4 text-gray-400"></i>
                                        @endif
                                        @if ($item->icon)
                                            <i data-lucide="{{ $item->icon }}" class="w-4 h-4 text-accent"></i>
                                        @endif
                                        <span class="font-semibold truncate max-w-[16rem]" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ $item->getTranslation('label', 'ar') ?? '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs" dir="ltr">
                                    <span class="truncate inline-block max-w-[16rem]" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                        {{ $item->route_name ?? $item->url ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    {{ $item->parent_id ? ($items->firstWhere('id', $item->parent_id)?->getTranslation('label', 'ar') ?? '#'.$item->parent_id) : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($item->is_active)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">{{ __('admin.active') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-400">{{ __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <form method="POST" action="{{ route('admin.content.menus.move', $item->id) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="p-2 rounded-lg transition-colors hover:text-accent"
                                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                                    title="{{ __('admin.content_move_up') }}">
                                                <i data-lucide="arrow-up" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.content.menus.move', $item->id) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="p-2 rounded-lg transition-colors hover:text-accent"
                                                    :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                                    title="{{ __('admin.content_move_down') }}">
                                                <i data-lucide="arrow-down" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.content.menus.edit', $item->id) }}"
                                           class="p-2 rounded-lg transition-colors hover:text-accent"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                           title="{{ __('admin.edit') }}">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.content.menus.destroy', $item->id) }}"
                                              onsubmit="return window.confirmSubmit(this, { message: {{ Js::from(__('admin.confirm_delete')) }} })">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 rounded-lg text-red-500 hover:bg-red-500/10 transition-colors"
                                                    title="{{ __('admin.delete') }}">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
