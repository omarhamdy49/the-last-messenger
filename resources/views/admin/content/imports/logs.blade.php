@extends('layouts.admin')

@section('content')
@php
    $statusStyles = [
        'completed' => 'bg-green-500/10 text-green-500',
        'failed' => 'bg-red-500/10 text-red-500',
        'in_progress' => 'bg-blue-500/10 text-blue-500',
        'pending' => 'bg-yellow-500/10 text-yellow-500',
    ];
@endphp
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <a href="{{ route('admin.content.imports.index') }}" class="hover:text-accent transition-colors">{{ __('admin.content_imports') }}</a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_import_logs') }}</span>
    </nav>

    <div class="mb-8">
        <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_import_logs') }}</h1>
        <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_import_logs_hint') }}</p>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.content.imports.logs') }}"
          class="mb-6 flex flex-wrap items-end gap-4">
        <div>
            <label class="block mb-2 text-sm font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.status') }}</label>
            <select name="status"
                    class="px-4 py-2 rounded-lg border-2 text-base sm:text-sm font-cairo"
                    :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('admin.content_filter_all') }}</option>
                @foreach (['pending', 'in_progress', 'completed', 'failed'] as $status)
                    <option value="{{ $status }}" @selected($activeStatus === $status)>{{ __('admin.content_import_status_'.$status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block mb-2 text-sm font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">{{ __('admin.content_imports_collection') }}</label>
            <select name="collection"
                    class="px-4 py-2 rounded-lg border-2 text-base sm:text-sm font-cairo"
                    :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('admin.content_filter_all') }}</option>
                @foreach ($collections as $collection)
                    <option value="{{ $collection }}" @selected($activeCollection === $collection)>{{ $collection }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                class="px-6 py-2.5 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center gap-2">
            <i data-lucide="filter" class="w-4 h-4"></i>
            <span>{{ __('admin.content_apply_filters') }}</span>
        </button>
    </form>

    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if ($logs->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="scroll-text" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
                <p class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_logs_empty') }}</p>
                @if ($activeStatus || $activeCollection)
                    <a href="{{ route('admin.content.imports.logs') }}"
                       class="mt-3 inline-flex items-center gap-2 text-accent font-semibold hover:underline">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span>{{ __('admin.content_reset_filter') }}</span>
                    </a>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <th class="px-4 py-3 text-start font-semibold">#</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_imports_collection') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_started_at') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_completed_at') }}</th>
                            <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_summary') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="border-b last:border-0 align-top"
                                :class="darkMode ? 'border-gray-800 hover:bg-gray-800/40' : 'border-gray-100 hover:bg-gray-50'">
                                <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $log->id }}</td>
                                <td class="px-4 py-3">
                                    <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $log->collection_name }}</span>
                                    <span class="block text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $log->type }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusStyles[$log->status] ?? 'bg-gray-500/10 text-gray-400' }}">
                                        {{ __('admin.content_import_status_'.$log->status) }}
                                    </span>
                                    @if ($log->error_message)
                                        <p class="mt-1 text-xs text-red-500 max-w-[14rem] break-words">{{ \Illuminate\Support\Str::limit($log->error_message, 120) }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    {{ $log->started_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                    {{ $log->completed_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_array($log->summary) && $log->summary !== [])
                                        <dl class="space-y-0.5 text-xs" dir="ltr">
                                            @foreach ($log->summary as $summaryKey => $summaryValue)
                                                <div class="flex gap-2">
                                                    <dt class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $summaryKey }}:</dt>
                                                    <dd class="break-all" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                                        {{ is_scalar($summaryValue) ? $summaryValue : json_encode($summaryValue, JSON_UNESCAPED_UNICODE) }}
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    @else
                                        <span :class="darkMode ? 'text-gray-500' : 'text-gray-400'">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
