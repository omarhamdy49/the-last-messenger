@extends('layouts.admin')

@section('content')
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <a href="{{ route('admin.content.home-cards.index') }}" class="hover:text-accent transition-colors">{{ __('admin.content_home_cards') }}</a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_quick_filters') }}</span>
    </nav>

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_quick_filters') }}</h1>
            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_quick_filters_hint') }}</p>
        </div>
        <a href="{{ route('admin.content.quick-filters.create') }}"
           class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.content_chip_add') }}</span>
        </a>
    </div>

    @include('admin.content._flash')

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if ($chips->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="filter" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
                <p class="font-semibold mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_chips_empty') }}</p>
                <a href="{{ route('admin.content.quick-filters.create') }}"
                   class="inline-flex items-center gap-2 text-accent font-semibold hover:underline">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>{{ __('admin.content_chip_add') }}</span>
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_label') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_key') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_target_query') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_sort_order') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($chips as $chip)
                            <tr class="border-b last:border-0 transition-colors"
                                :class="darkMode ? 'border-gray-800 hover:bg-gray-800/40' : 'border-gray-100 hover:bg-gray-50'">
                                <td class="px-4 py-3">
                                    <span class="font-semibold truncate inline-block max-w-[14rem]" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $chip->getTranslation('label', 'ar') ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $chip->key }}</td>
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    <span class="truncate inline-block max-w-[14rem]">{{ $chip->target_query ?? $chip->target_url ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $chip->sort_order }}</td>
                                <td class="px-4 py-3">
                                    @if ($chip->is_active)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">{{ __('admin.active') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-400">{{ __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <a href="{{ route('admin.content.quick-filters.edit', $chip->id) }}"
                                           class="p-2 rounded-lg transition-colors hover:text-accent"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                           title="{{ __('admin.edit') }}">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.content.quick-filters.destroy', $chip->id) }}"
                                              onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
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
