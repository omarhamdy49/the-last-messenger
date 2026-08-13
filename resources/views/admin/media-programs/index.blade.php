@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="tv" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.media_programs') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('admin.manage_programs') }}
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.media-programs.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.add_program') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div>
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <x-admin.stats-card icon="tv" :title="__('admin.total_programs')" :value="$stats['total'] ?? 0" :gradient="true" :compact="true" />
        <x-admin.stats-card icon="star" :title="__('admin.featured_programs')" :value="$stats['featured'] ?? 0" :compact="true" />
        <x-admin.stats-card icon="trending-up" :title="__('admin.trending_programs')" :value="$stats['trending'] ?? 0" :compact="true" />
        <x-admin.stats-card icon="eye" :title="__('admin.views')" :value="number_format($stats['total_views'] ?? 0)" :compact="true" />
    </div>

    <!-- Search & Filter Bar -->
    <div class="mb-6 p-4 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.media-programs.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.search') }}
                </label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    <input type="text" name="q" value="{{ request('q') }}" maxlength="255"
                           placeholder="{{ __('admin.search_programs') }}"
                           class="w-full text-base sm:text-sm rounded-lg border ps-9 pe-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.filter_by_category') }}
                </label>
                <select name="category" class="text-base sm:text-sm rounded-lg border px-3 py-2 min-w-[160px]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->value }}" {{ request('category') === $cat->value ? 'selected' : '' }}>
                            {{ $cat->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.publish_status') }}
                </label>
                <select name="publish_status" class="text-base sm:text-sm rounded-lg border px-3 py-2 min-w-[140px]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach(['published' => __('admin.published'), 'scheduled' => __('admin.scheduled'), 'draft' => __('admin.draft')] as $val => $lbl)
                        <option value="{{ $val }}" {{ request('publish_status') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.sort_by') }}
                </label>
                <select name="sort" class="text-base sm:text-sm rounded-lg border px-3 py-2 min-w-[150px]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @foreach(['created_at' => __('admin.created_at'), 'display_order' => __('admin.display_order'), 'views_count' => __('admin.views'), 'rating' => __('admin.program_rating')] as $val => $lbl)
                        <option value="{{ $val }}" {{ request('sort', 'created_at') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-sm font-semibold hover:scale-105 transition-all flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>
                {{ __('admin.apply_filters') }}
            </button>
            @if(request('category') || request('q') || request('sort') || request('publish_status'))
            <a href="{{ route('admin.media-programs.index') }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold border"
               :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
                {{ __('admin.reset_all') }}
            </a>
            @endif
        </form>
    </div>

    <!-- Programs Table -->
    <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if($programs->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center px-4">
                <i data-lucide="tv" class="w-16 h-16 mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-300'"></i>
                <p class="text-xl font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('admin.no_programs_found') }}
                </p>
                @if(request('category') || request('q'))
                <a href="{{ route('admin.media-programs.index') }}"
                   class="mt-4 px-6 py-2 rounded-xl border font-semibold transition-colors"
                   :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    {{ __('admin.reset_all') }}
                </a>
                @else
                <a href="{{ route('admin.media-programs.create') }}"
                   class="mt-4 px-6 py-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    {{ __('admin.add_program') }}
                </a>
                @endif
            </div>
        @else
        <div x-data="bulkSelect(@js($programs->pluck('id')->all()))">
        {{-- Table above `md`, card stack below it — see components/admin/list-card. --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" :class="darkMode ? 'border-gray-800 bg-gray-900/80' : 'border-gray-200 bg-gray-50'">
                        <th class="ps-4 pe-2 py-3 w-10">
                            <input type="checkbox" :checked="allSelected" @change="toggleAll($event.target.checked)"
                                   aria-label="{{ __('admin.bulk_select_all') }}"
                                   class="w-4 h-4 rounded text-[#0056b3] cursor-pointer">
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.title') }}
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.season') }} / {{ __('admin.episode') }}
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.status') }}
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.views') }}
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.created_at') }}
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @foreach($programs as $program)
                    <tr class="transition-colors"
                        :class="selected.includes({{ $program->id }})
                            ? (darkMode ? 'bg-[#0056b3]/10' : 'bg-blue-50')
                            : (darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50')">
                        <td class="ps-4 pe-2 py-3">
                            <input type="checkbox" value="{{ $program->id }}" x-model.number="selected"
                                   aria-label="{{ __('admin.bulk_select_row') }}"
                                   class="w-4 h-4 rounded text-[#0056b3] cursor-pointer">
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.media-programs.show', $program->id) }}" class="flex items-center gap-3 group/title">
                                @if($program->poster_thumb_url)
                                <img src="{{ $program->poster_thumb_url }}" loading="lazy"
                                     class="w-11 h-11 rounded-lg object-cover flex-shrink-0 shadow-sm transition-transform group-hover/title:scale-105"
                                     alt="{{ $program->getTranslation('title', 'ar') ?? $program->slug }}">
                                @else
                                <div class="w-11 h-11 rounded-lg flex items-center justify-center bg-gradient-to-r from-[#0056b3] to-[#218838] flex-shrink-0 transition-transform group-hover/title:scale-105">
                                    <i data-lucide="tv" class="w-5 h-5 text-white"></i>
                                </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-semibold truncate max-w-[240px] group-hover/title:text-[#0056b3] transition-colors"
                                       :class="darkMode ? 'text-white group-hover/title:!text-[#64CCC5]' : 'text-gray-900'">
                                        {{ $program->getTranslation('title', 'ar') ?? $program->slug }}
                                    </p>
                                    <p class="text-xs truncate" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                        {{ $program->category?->labelAr() }}@if($program->year) • {{ $program->year }}@endif
                                    </p>
                                </div>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            S{{ $program->total_seasons }} • E{{ $program->total_episodes }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @switch($program->publishStatus())
                                    @case('published')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ __('admin.published') }}
                                        </span>
                                        @break
                                    @case('scheduled')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800"
                                              title="{{ $program->publish_at?->format('Y-m-d H:i') }}">
                                            {{ __('admin.scheduled') }} • {{ $program->publish_at?->format('m-d H:i') }}
                                        </span>
                                        @break
                                    @default
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                            {{ __('admin.draft') }}
                                        </span>
                                @endswitch
                                @if($program->is_featured)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ __('admin.featured_only') }}
                                </span>
                                @endif
                                @if($program->is_trending)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ __('admin.is_trending') }}
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ number_format($program->views_count) }}
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ $program->created_at->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.media-programs.show', $program->id) }}"
                                   class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.view') }}"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.media-programs.edit', $program->id) }}"
                                   class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.edit') }}"
                                   :class="darkMode ? 'hover:bg-gray-700 text-blue-400' : 'hover:bg-blue-50 text-blue-600'">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.media-episodes.index', ['program_id' => $program->id]) }}"
                                   class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.media_episodes') }}"
                                   :class="darkMode ? 'hover:bg-gray-700 text-green-400' : 'hover:bg-green-50 text-green-600'">
                                    <i data-lucide="list" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.media-programs.destroy', $program->id) }}"
                                      onsubmit="return window.confirmSubmit(this, { message: '{{ __('admin.confirm_delete') }}' })" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.delete') }}"
                                            :class="darkMode ? 'hover:bg-gray-700 text-red-400' : 'hover:bg-red-50 text-red-600'">
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

        <!-- Mobile card stack (< md) -->
        <div class="md:hidden p-4 space-y-3">
            @foreach($programs as $program)
                <x-admin.list-card
                    :value="$program->id"
                    icon="tv"
                    :title="$program->getTranslation('title', 'ar') ?? $program->slug"
                    :subtitle="collect([$program->category?->labelAr(), $program->year])->filter()->implode(' • ') ?: null">

                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="layers" class="w-3 h-3"></i> S{{ $program->total_seasons }} • E{{ $program->total_episodes }}
                    </span>

                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold"
                          :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                        <i data-lucide="eye" class="w-3 h-3"></i> {{ number_format($program->views_count) }}
                    </span>

                    @switch($program->publishStatus())
                        @case('published')
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ __('admin.published') }}</span>
                            @break
                        @case('scheduled')
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                                  title="{{ $program->publish_at?->format('Y-m-d H:i') }}">{{ __('admin.scheduled') }} • {{ $program->publish_at?->format('m-d H:i') }}</span>
                            @break
                        @default
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400">{{ __('admin.draft') }}</span>
                    @endswitch

                    @if($program->is_featured)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">{{ __('admin.featured_only') }}</span>
                    @endif
                    @if($program->is_trending)
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">{{ __('admin.is_trending') }}</span>
                    @endif

                    <x-slot:actions>
                        <a href="{{ route('admin.media-programs.show', $program->id) }}" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="{{ __('admin.view') }}"><i data-lucide="eye" class="w-4 h-4"></i></a>
                        <a href="{{ route('admin.media-programs.edit', $program->id) }}" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-blue-400' : 'hover:bg-blue-50 text-blue-600'" title="{{ __('admin.edit') }}"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                        <a href="{{ route('admin.media-episodes.index', ['program_id' => $program->id]) }}" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-green-400' : 'hover:bg-green-50 text-green-600'" title="{{ __('admin.media_episodes') }}"><i data-lucide="list" class="w-4 h-4"></i></a>
                        <form method="POST" action="{{ route('admin.media-programs.destroy', $program->id) }}"
                              onsubmit="return window.confirmSubmit(this, { message: '{{ __('admin.confirm_delete') }}' })" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2.5 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-red-400' : 'hover:bg-red-50 text-red-600'" title="{{ __('admin.delete') }}"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                        </form>
                    </x-slot:actions>
                </x-admin.list-card>
            @endforeach
        </div>

        <x-admin.bulk-delete-bar :action="route('admin.media-programs.bulk-delete')"
                                 :confirm="__('admin.bulk_delete_programs_confirm')" />
        </div>

        <!-- Pagination -->
        @if($programs->hasPages())
        <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            {{ $programs->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
