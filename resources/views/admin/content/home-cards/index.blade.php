@extends('layouts.admin')

@section('content')
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_home_cards') }}</span>
    </nav>

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_home_cards') }}</h1>
            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_home_cards_hint') }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.content.quick-filters.index') }}"
               class="px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 border-2"
               :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                <i data-lucide="filter" class="w-5 h-5"></i>
                <span>{{ __('admin.content_quick_filters') }}</span>
            </a>
            <a href="{{ route('admin.content.home-cards.create') }}"
               class="px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>{{ __('admin.content_card_add') }}</span>
            </a>
        </div>
    </div>

    @include('admin.content._flash')

    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ([
            'all' => __('admin.content_placement_all'),
            'hero' => __('admin.content_placement_hero'),
            'explore' => __('admin.content_placement_explore'),
        ] as $placement => $label)
            <a href="{{ route('admin.content.home-cards.index', $placement === 'all' ? [] : ['placement' => $placement]) }}"
               class="px-4 py-2 rounded-lg font-semibold transition-all {{ $activePlacement === $placement ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg' : '' }}"
               @if ($activePlacement !== $placement) :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" @endif>
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if ($cards->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="layout-grid" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
                <p class="font-semibold mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_cards_empty') }}</p>
                <a href="{{ route('admin.content.home-cards.create') }}"
                   class="inline-flex items-center gap-2 text-accent font-semibold hover:underline">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>{{ __('admin.content_card_add') }}</span>
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.title') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_key') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_placement') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_target') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_featured') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3 text-end font-semibold">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cards as $card)
                            <tr class="border-b last:border-0 transition-colors"
                                :class="darkMode ? 'border-gray-800 hover:bg-gray-800/40' : 'border-gray-100 hover:bg-gray-50'">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        @if ($card->icon)
                                            <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                                  style="background-color: {{ $card->accent_color ?? '#64CCC5' }}20; color: {{ $card->accent_color ?? '#64CCC5' }}">
                                                <i data-lucide="{{ $card->icon }}" class="w-4 h-4"></i>
                                            </span>
                                        @endif
                                        <span class="font-semibold truncate max-w-[16rem]" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ $card->getTranslation('title', 'ar') ?? '—' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $card->key }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                                        {{ __("admin.content_placement_{$card->placement->value}") }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs" dir="ltr">
                                    <span class="truncate inline-block max-w-[14rem]" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $card->target_url ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($card->is_featured)
                                        <i data-lucide="star" class="w-4 h-4 text-yellow-500"></i>
                                    @else
                                        <span :class="darkMode ? 'text-gray-500' : 'text-gray-400'">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($card->is_active)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">{{ __('admin.active') }}</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-400">{{ __('admin.inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-end gap-1">
                                        <a href="{{ route('admin.content.home-cards.edit', $card->id) }}"
                                           class="p-2 rounded-lg transition-colors hover:text-accent"
                                           :class="darkMode ? 'text-gray-300' : 'text-gray-600'"
                                           title="{{ __('admin.edit') }}">
                                            <i data-lucide="pencil" class="w-4 h-4"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.content.home-cards.destroy', $card->id) }}"
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
