@extends('layouts.admin')

@php
    $statusBadge = [
        'live' => 'bg-red-500/15 text-red-500',
        'scheduled' => 'bg-amber-500/15 text-amber-500',
        'offline' => 'bg-gray-500/15 text-gray-400',
    ];
@endphp

@section('page-header')
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
        <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
            <i data-lucide="radio" class="w-7 h-7 text-white"></i>
        </div>
        {{ __('admin.live_channels') }}
    </h1>
    <a href="{{ route('admin.live-channels.create') }}"
       class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold shadow-lg hover:shadow-xl transition-all flex items-center gap-2">
        <i data-lucide="plus" class="w-5 h-5"></i>{{ __('admin.add_live_channel') }}
    </a>
</div>
@endsection

@section('content')
@if(session('success'))
<div class="mb-6 p-4 rounded-xl border-s-4 border-green-500" :class="darkMode ? 'bg-green-900/20 text-green-400' : 'bg-green-50 text-green-700'">
    {{ session('success') }}
</div>
@endif

<div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
    @if($channels->isEmpty())
        <div class="py-20 text-center">
            <i data-lucide="radio" class="w-12 h-12 mx-auto mb-4 text-gray-400"></i>
            <p class="text-lg font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.no_live_channels') }}</p>
            <a href="{{ route('admin.live-channels.create') }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-bold">
                <i data-lucide="plus" class="w-5 h-5"></i>{{ __('admin.add_live_channel') }}
            </a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead :class="darkMode ? 'bg-gray-800/60 text-gray-400' : 'bg-gray-50 text-gray-600'">
                    <tr class="text-start">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.title') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.published') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('media.public.views') }}</th>
                        <th class="px-4 py-3 text-end font-semibold">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @foreach($channels as $channel)
                    <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/40' : 'hover:bg-gray-50'">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.live-channels.show', $channel->id) }}" class="flex items-center gap-3 group">
                                <div class="w-16 aspect-video rounded-lg overflow-hidden flex-shrink-0 bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                                    @if($channel->thumb_url)
                                        <img src="{{ $channel->thumb_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <i data-lucide="radio" class="w-5 h-5 text-white/80"></i>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold truncate group-hover:text-[#0056b3] dark:group-hover:text-[#64CCC5] transition-colors" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $channel->trans('title') ?: $channel->slug }}</p>
                                    <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-500'" dir="ltr">{{ $channel->slug }}</p>
                                </div>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold {{ $statusBadge[$channel->status->value] ?? $statusBadge['offline'] }}">
                                @if($channel->status->value === 'live')<span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>@endif
                                {{ __('media.public.live_status_'.$channel->status->value) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($channel->is_published)
                                <span class="text-green-500"><i data-lucide="check-circle" class="w-5 h-5"></i></span>
                            @else
                                <span class="text-gray-400"><i data-lucide="circle" class="w-5 h-5"></i></span>
                            @endif
                        </td>
                        <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ number_format($channel->views_count) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.live-channels.show', $channel->id) }}" class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="{{ __('admin.view') }}">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.live-channels.edit', $channel->id) }}" class="p-2 rounded-lg transition-colors" :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'" title="{{ __('admin.edit') }}">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.live-channels.destroy', $channel->id) }}"
                                      onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 rounded-lg transition-colors text-red-500 hover:bg-red-500/10" title="{{ __('admin.delete') }}">
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

<div class="mt-6">
    {{ $channels->links() }}
</div>
@endsection
