@extends('layouts.admin')

@section('content')
<div>
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ in_array(app()->getLocale(), ['ar', 'ur']) ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_imports') }}</span>
    </nav>

    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.content_imports') }}</h1>
            <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.content_imports_hint') }}</p>
        </div>
        <a href="{{ route('admin.content.imports.logs') }}"
           class="px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
            <i data-lucide="scroll-text" class="w-5 h-5"></i>
            <span>{{ __('admin.content_import_logs') }}</span>
        </a>
    </div>

    {{-- Quran coverage --}}
    <div class="rounded-2xl overflow-hidden shadow-xl border mb-8"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 border-b flex items-center gap-2" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <i data-lucide="book-open" class="w-5 h-5 text-accent"></i>
            <h2 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_imports_quran') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_imports_check') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_imports_actual') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_imports_expected') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quranChecks as $check)
                        <tr class="border-b last:border-0" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                            <td class="px-4 py-3 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $check['label'] }}</td>
                            <td class="px-4 py-3" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ number_format((int) $check['actual']) }}</td>
                            <td class="px-4 py-3 font-mono text-xs" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ $check['expected'] }}</td>
                            <td class="px-4 py-3">
                                @if ($check['passes'])
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">
                                        <i data-lucide="check" class="w-3 h-3"></i> {{ __('admin.content_imports_ok') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-500">
                                        <i data-lucide="x" class="w-3 h-3"></i> {{ __('admin.content_imports_failing') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Hadith coverage --}}
    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 border-b flex items-center gap-2" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <i data-lucide="library" class="w-5 h-5 text-accent"></i>
            <h2 class="text-xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.content_imports_hadith') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" :class="darkMode ? 'border-gray-800 text-gray-300' : 'border-gray-200 text-gray-600'">
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_imports_collection') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_imports_files') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_imports_db_count') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.content_imports_floor') }}</th>
                        <th class="px-4 py-3 text-start font-semibold">{{ __('admin.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hadithRows as $row)
                        <tr class="border-b last:border-0 {{ $row['directory'] === 'TOTAL' ? 'font-bold' : '' }}"
                            :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                            <td class="px-4 py-3 font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $row['directory'] }}
                                @if ($row['source_slug'])
                                    <span class="block text-xs font-normal font-mono" dir="ltr" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $row['source_slug'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                                {{ $row['files'] !== null ? number_format($row['files']) : '—' }}
                            </td>
                            <td class="px-4 py-3" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ number_format($row['db']) }}</td>
                            <td class="px-4 py-3" dir="ltr" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ number_format($row['floor']) }}</td>
                            <td class="px-4 py-3">
                                @if ($row['passes'])
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-500/10 text-green-500">
                                        <i data-lucide="check" class="w-3 h-3"></i> {{ __('admin.content_imports_ok') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-500">
                                        <i data-lucide="x" class="w-3 h-3"></i> {{ __('admin.content_imports_failing') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
