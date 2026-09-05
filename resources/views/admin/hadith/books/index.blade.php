@extends('layouts.admin')
@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
        <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg"><i data-lucide="book" class="w-7 h-7 text-white"></i></div>
        {{ __('hadith_admin.books') }}
    </h1>
    <a href="{{ route('admin.hadith-books.create', ['collection' => $filters['collection']]) }}"
       class="px-6 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
        <i data-lucide="plus" class="w-5 h-5"></i><span>{{ __('hadith_admin.add') }}</span>
    </a>
</div>
@endsection
@section('content')
@include('admin.hadith._flash')

<div class="mb-6 p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <form method="GET" action="{{ route('admin.hadith-books.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <label for="q" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.search') }}</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] }}" class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
        </div>
        <div>
            <label for="collection" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.collection') }}</label>
            <select id="collection" name="collection" class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('hadith_admin.all_collections') }}</option>
                @foreach($collections as $c)<option value="{{ $c->id }}" @selected($filters['collection'] === $c->id)>{{ $c->title_ar }}</option>@endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">{{ __('hadith_admin.filter') }}</button>
            <a href="{{ route('admin.hadith-books.index') }}" class="px-4 py-2.5 rounded-xl border" :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('hadith_admin.reset') }}</a>
        </div>
    </form>
</div>

<div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-sm">
            <thead><tr :class="darkMode ? 'bg-gray-800/50 text-gray-400' : 'bg-gray-50 text-gray-600'">
                <th class="text-start px-4 py-3 text-xs font-semibold">#</th>
                <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.book') }}</th>
                <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.collection') }}</th>
                <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.chapters') }}</th>
                <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.hadiths') }}</th>
                <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.actions') }}</th>
            </tr></thead>
            <tbody>
                @forelse($books as $book)
                <tr class="border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <td class="px-4 py-3 font-mono tabular-nums" dir="ltr">{{ $book->book_number }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $book->title_ar }}</td>
                    <td class="px-4 py-3 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $book->collection?->title_ar }}</td>
                    <td class="px-4 py-3 tabular-nums"><a href="{{ route('admin.hadith-chapters.index', ['book' => $book->id]) }}" class="hover:text-accent">{{ number_format($book->chapters_count) }}</a></td>
                    <td class="px-4 py-3 tabular-nums"><a href="{{ route('admin.hadiths.index', ['book' => $book->id]) }}" class="hover:text-accent">{{ number_format($book->hadiths_count) }}</a></td>
                    <td class="px-4 py-3"><div class="flex items-center gap-1">
                        <x-admin.hadith-status-toggle :action="route('admin.hadith-books.toggle-status', $book)" :published="$book->is_published" />
                        <a href="{{ route('admin.hadith-books.edit', $book) }}" class="p-2 rounded-lg hover:bg-black/10"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                        <x-admin.hadith-delete-button :action="route('admin.hadith-books.destroy', $book)" :label="$book->title_ar" />
                    </div></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-12 text-center" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('hadith_admin.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $books->links() }}</div>
@endsection
