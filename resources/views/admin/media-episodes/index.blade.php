@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.media-programs.index') }}"
           class="p-2 rounded-xl transition-colors"
           :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
            <i data-lucide="{{ in_array(app()->getLocale(), ['ar','ur']) ? 'chevron-right' : 'chevron-left' }}" class="w-5 h-5"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
                <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                    <i data-lucide="clapperboard" class="w-7 h-7 text-white"></i>
                </div>
                {{ __('admin.media_episodes') }}
                @if($program)
                <span class="text-lg font-normal" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                    — {{ $program->getTranslation('title', 'ar') ?? $program->slug }}
                </span>
                @endif
            </h1>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        @if($program)
        <a href="{{ route('admin.media-programs.show', $program->id) }}"
           class="px-4 py-2 rounded-xl font-semibold border transition-colors"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
            {{ __('admin.view_program') }}
        </a>
        @endif
        <a href="{{ route('admin.media-episodes.create', $program ? ['program_id' => $program->id] : []) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            {{ __('admin.add_episode') }}
        </a>
    </div>
</div>
@endsection

@section('content')
<div>
    <!-- Stats Cards -->
    @php
        $durationHours = intdiv($stats['total_duration_seconds'] ?? 0, 3600);
        $durationMinutes = intdiv(($stats['total_duration_seconds'] ?? 0) % 3600, 60);
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <x-admin.stats-card icon="clapperboard" :title="__('admin.total_episodes')" :value="$stats['total'] ?? 0" :gradient="true" :compact="true" />
        <x-admin.stats-card icon="star" :title="__('admin.featured_episodes')" :value="$stats['featured'] ?? 0" :compact="true" />
        <x-admin.stats-card icon="eye" :title="__('admin.views')" :value="number_format($stats['total_views'] ?? 0)" :compact="true" />
        <x-admin.stats-card icon="clock" :title="__('admin.total_duration')" :value="sprintf('%02d:%02d', $durationHours, $durationMinutes)" :compact="true" />
    </div>

    <!-- Search & Filter Bar -->
    <div class="mb-6 p-4 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.media-episodes.index') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.search') }}
                </label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    <input type="text" name="q" value="{{ request('q') }}" maxlength="255"
                           placeholder="{{ __('admin.search_episodes') }}"
                           class="w-full text-base sm:text-sm rounded-lg border ps-9 pe-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0056b3]"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ __('admin.filter_by_program') }}
                </label>
                <select name="program_id" class="text-base sm:text-sm rounded-lg border px-3 py-2 min-w-[180px] max-w-[240px]"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($programs as $prog)
                        <option value="{{ $prog->id }}" {{ request('program_id') == $prog->id ? 'selected' : '' }}>
                            {{ $prog->getTranslation('title', 'ar') ?? $prog->slug }}
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
                    @foreach(['created_at' => __('admin.created_at'), 'episode_number' => __('admin.episode_number'), 'views_count' => __('admin.views'), 'duration_seconds' => __('admin.duration')] as $val => $lbl)
                        <option value="{{ $val }}" {{ request('sort', 'created_at') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white text-sm font-semibold hover:scale-105 transition-all flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>
                {{ __('admin.apply_filters') }}
            </button>
            @if(request('q') || request('program_id') || request('sort') || request('publish_status'))
            <a href="{{ route('admin.media-episodes.index') }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold border"
               :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
                {{ __('admin.reset_all') }}
            </a>
            @endif
        </form>
    </div>

    <!-- Episodes Table -->
    <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if($episodes->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 text-center px-4">
                <i data-lucide="clapperboard" class="w-16 h-16 mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-300'"></i>
                <p class="text-xl font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('admin.no_episodes_found') }}
                </p>
                @if(request('q') || request('program_id'))
                <a href="{{ route('admin.media-episodes.index') }}"
                   class="mt-4 px-6 py-2 rounded-xl border font-semibold transition-colors"
                   :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    {{ __('admin.reset_all') }}
                </a>
                @else
                <a href="{{ route('admin.media-episodes.create', $program ? ['program_id' => $program->id] : []) }}"
                   class="mt-4 px-6 py-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    {{ __('admin.add_episode') }}
                </a>
                @endif
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" :class="darkMode ? 'border-gray-800 bg-gray-900/80' : 'border-gray-200 bg-gray-50'">
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.title') }}
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.season') }} / {{ __('admin.episode') }}
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.publish_status') }}
                        </th>
                        <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('admin.duration') }}
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
                    @foreach($episodes as $episode)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.media-episodes.show', $episode->id) }}" class="flex items-center gap-3 group/title">
                                @if($episode->thumb_url)
                                <img src="{{ $episode->thumb_url }}" loading="lazy"
                                     class="w-16 h-10 rounded-lg object-cover flex-shrink-0 shadow-sm transition-transform group-hover/title:scale-105"
                                     alt="{{ $episode->getTranslation('title', 'ar') ?? $episode->slug }}">
                                @else
                                <div class="w-16 h-10 rounded-lg flex items-center justify-center bg-gradient-to-r from-[#0056b3] to-[#218838] flex-shrink-0 transition-transform group-hover/title:scale-105">
                                    <i data-lucide="play" class="w-4 h-4 text-white"></i>
                                </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-semibold truncate max-w-[240px] group-hover/title:text-[#0056b3] transition-colors"
                                       :class="darkMode ? 'text-white group-hover/title:!text-[#64CCC5]' : 'text-gray-900'">
                                        {{ $episode->getTranslation('title', 'ar') ?? $episode->slug }}
                                    </p>
                                    <p class="text-xs truncate max-w-[240px]" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                        {{ $episode->program?->getTranslation('title', 'ar') ?? $episode->program?->slug }}
                                        @if($episode->is_featured)
                                        <span class="inline-flex items-center gap-0.5 text-yellow-500">
                                            • <i data-lucide="star" class="w-3 h-3"></i>
                                        </span>
                                        @endif
                                    </p>
                                </div>
                            </a>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-xs font-mono px-2 py-0.5 rounded" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                                S{{ $episode->season_number }}E{{ str_pad($episode->episode_number, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @switch($episode->publishStatus())
                                @case('published')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ __('admin.published') }}
                                    </span>
                                    @break
                                @case('scheduled')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 whitespace-nowrap"
                                          title="{{ $episode->publish_at?->format('Y-m-d H:i') }}">
                                        {{ __('admin.scheduled') }} • {{ $episode->publish_at?->format('m-d H:i') }}
                                    </span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-700">
                                        {{ __('admin.draft') }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $episode->duration_formatted ?: '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ number_format($episode->views_count) }}
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ $episode->created_at->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.media-episodes.show', $episode->id) }}"
                                   class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.view') }}"
                                   :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.media-episodes.edit', $episode->id) }}"
                                   class="p-1.5 rounded-lg transition-colors" title="{{ __('admin.edit') }}"
                                   :class="darkMode ? 'hover:bg-gray-700 text-blue-400' : 'hover:bg-blue-50 text-blue-600'">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.media-episodes.destroy', $episode->id) }}"
                                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}')" class="inline">
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

        <!-- Pagination -->
        @if($episodes->hasPages())
        <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            {{ $episodes->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
