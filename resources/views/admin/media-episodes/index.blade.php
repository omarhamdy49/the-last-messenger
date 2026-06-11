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
                    <i data-lucide="list" class="w-7 h-7 text-white"></i>
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
<div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
    @if($episodes->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <i data-lucide="list" class="w-16 h-16 mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-300'"></i>
            <p class="text-xl font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ __('admin.no_episodes_found') }}
            </p>
            <a href="{{ route('admin.media-episodes.create', $program ? ['program_id' => $program->id] : []) }}"
               class="mt-4 px-6 py-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                {{ __('admin.add_episode') }}
            </a>
        </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b" :class="darkMode ? 'border-gray-800 bg-gray-900/80' : 'border-gray-200 bg-gray-50'">
                    <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">#</th>
                    <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.title') }}</th>
                    <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.season_number') }}</th>
                    <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.duration_seconds') }}</th>
                    <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.views') }}</th>
                    <th class="px-4 py-3 text-start font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                @foreach($episodes as $episode)
                <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                    <td class="px-4 py-3">
                        <span class="text-xs font-mono px-2 py-0.5 rounded" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                            S{{ $episode->season_number }}E{{ str_pad($episode->episode_number, 2, '0', STR_PAD_LEFT) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-semibold truncate max-w-[240px]" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ $episode->getTranslation('title', 'ar') ?? $episode->slug }}
                        </p>
                    </td>
                    <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ $episode->season_number }}
                    </td>
                    <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ $episode->duration_seconds ? gmdate('G:i', $episode->duration_seconds) : '—' }}
                    </td>
                    <td class="px-4 py-3 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ number_format($episode->views_count) }}
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

    @if($episodes->hasPages())
    <div class="px-6 py-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        {{ $episodes->appends(request()->query())->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
