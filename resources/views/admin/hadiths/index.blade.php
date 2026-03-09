@extends('layouts.admin')

@php
use Illuminate\Support\Str;
@endphp

@section('title', __('admin.hadiths'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.hadiths') }}</h1>
            <p class="text-sm mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.manage_hadiths') }}</p>
        </div>
        <a href="{{ route('admin.hadith.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all">
            <i data-lucide="plus"></i>
            {{ __('admin.add_hadith') }}
        </a>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.total_hadiths') }}</p>
                    <p class="text-3xl font-bold mt-2" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadiths->total() }}</p>
                </div>
                <div class="p-3 rounded-lg" :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
                    <i data-lucide="book" class="w-6 h-6 text-[#0056b3]"></i>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('admin.current_page') }}</p>
                    <p class="text-3xl font-bold mt-2" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadiths->currentPage() }}</p>
                </div>
                <div class="p-3 rounded-lg" :class="darkMode ? 'bg-gray-700' : 'bg-gray-100'">
                    <i data-lucide="list" class="w-6 h-6 text-[#218838]"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-6 rounded-lg" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.search') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.search_hadith_number') }}"
                        class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>

                <!-- Book Filter -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.book') }}</label>
                    <select name="book_id" class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.all') }}</option>
                        @foreach($books as $book)
                            <option value="{{ $book->id }}" @selected(request('book_id') == $book->id)>
                                {{ $book->title_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Grading Filter -->
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.grading') }}</label>
                    <select name="grading_status" class="w-full px-4 py-2 rounded-lg border" :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.all') }}</option>
                        @foreach($gradingLevels as $level)
                            <option value="{{ $level->id }}" @selected(request('grading_status') == $level->id)>
                                {{ $level->grade_ar }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-[#0056b3] text-white rounded-lg hover:bg-opacity-90 transition-all">
                    {{ __('admin.filter') }}
                </button>
                <a href="{{ route('admin.hadith.index') }}" class="px-4 py-2 border rounded-lg" :class="darkMode ? 'border-gray-600 text-gray-300 hover:bg-gray-700' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
                    {{ __('admin.reset') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="p-6 rounded-lg overflow-x-auto" :class="darkMode ? 'bg-gray-800' : 'bg-white'" style="box-shadow: 0 1px 3px rgba(0,0,0,0.1)">
        <table class="w-full">
            <thead>
                <tr class="border-b" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <th class="text-start px-4 py-3 font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.id') }}</th>
                    <th class="text-start px-4 py-3 font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.hadith_number') }}</th>
                    <th class="text-start px-4 py-3 font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.collection') }}</th>
                    <th class="text-start px-4 py-3 font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.book') }}</th>
                    <th class="text-start px-4 py-3 font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.hadith') }}</th>
                    <th class="text-start px-4 py-3 font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.grading') }}</th>
                    <th class="text-center px-4 py-3 font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hadiths as $hadith)
                    <tr class="border-b" :class="darkMode ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-gray-50'">
                        <td class="px-4 py-3" :class="darkMode ? 'text-white' : 'text-gray-900'">#{{ $hadith->id }}</td>
                        <td class="px-4 py-3" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $hadith->hadith_number }}</td>
                        <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-sm font-medium">{{ $hadith->book?->collection?->name_ar ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <span class="text-sm">{{ $hadith->book?->title_ar ?? '—' }}</span>
                        </td>
                        <td class="px-4 py-3 max-w-xs" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <p class="line-clamp-2 text-sm" title="{{ $hadith->translations->where('language_code', 'ar')->first()?->text ?? '' }}">
                                {{ Str::limit($hadith->translations->where('language_code', 'ar')->first()?->text ?? '—', 100) }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            @if($hadith->grading_status)
                                <span class="inline-block px-3 py-1 text-xs rounded-full whitespace-nowrap" :class="darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800'">
                                    {{ $hadith->grading_status }}
                                </span>
                            @else
                                <span class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.ungraded') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('admin.hadith.show', $hadith) }}" class="p-2 rounded hover:bg-blue-100 text-blue-600 transition-colors" :class="darkMode && 'hover:bg-blue-900'">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <a href="{{ route('admin.hadith.edit', $hadith) }}" class="p-2 rounded hover:bg-yellow-100 text-yellow-600 transition-colors" :class="darkMode && 'hover:bg-yellow-900'">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </a>
                                <form action="{{ route('admin.hadith.destroy', $hadith) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 rounded hover:bg-red-100 text-red-600 transition-colors" :class="darkMode && 'hover:bg-red-900'"
                                        onclick="return confirm('{{ __('admin.confirm_delete') }}')">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ __('admin.no_hadiths_found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $hadiths->links() }}
    </div>
</div>

<script>
    lucide.createIcons();
</script>
@endsection
