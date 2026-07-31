@extends('layouts.admin')

@section('page-header')
<div>
    <h1 class="flex items-center gap-3 text-3xl font-bold gradient-text">
        <span class="rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] p-3 shadow-lg"><i data-lucide="library-big" class="h-7 w-7 text-white"></i></span>
        {{ __('admin.hadith_books') }}
    </h1>
    <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith.admin.books_subtitle') }}</p>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stats-card icon="library-big" :title="__('hadith.admin.total_books')" :value="$stats['total']" :gradient="true" :compact="true" />
        <x-admin.stats-card icon="eye" :title="__('hadith.admin.published')" :value="$stats['published']" :compact="true" />
        <x-admin.stats-card icon="list-tree" :title="__('hadith.chapters')" :value="$stats['chapters']" :compact="true" />
        <x-admin.stats-card icon="scroll-text" :title="__('hadith.hadiths')" :value="$stats['hadiths']" :compact="true" />
    </div>

    <section class="rounded-2xl border p-5" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-white'">
        <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div>
                <label for="book-search" class="mb-2 block text-sm font-semibold">{{ __('admin.search') }}</label>
                <input id="book-search" type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('hadith.admin.search_books') }}"
                       class="w-full rounded-xl border px-4 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
            </div>
            <div>
                <label for="book-collection" class="mb-2 block text-sm font-semibold">{{ __('hadith.collection') }}</label>
                <select id="book-collection" name="collection_id" class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($collections as $collection)<option value="{{ $collection->id }}" @selected(request('collection_id') == $collection->id)>{{ $collection->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="book-status" class="mb-2 block text-sm font-semibold">{{ __('admin.status') }}</label>
                <select id="book-status" name="status" class="w-full rounded-xl border px-3 py-2.5" :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="published" @selected(request('status') === 'published')>{{ __('hadith.admin.published') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>{{ __('hadith.admin.hidden') }}</option>
                </select>
            </div>
            <div class="flex items-end gap-3">
                <button class="rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] px-5 py-2.5 font-semibold text-white">{{ __('admin.filter') }}</button>
                <a href="{{ route('admin.hadith-books.index') }}" class="rounded-xl border px-4 py-2.5 font-semibold" :class="darkMode ? 'border-gray-700' : 'border-gray-300'">{{ __('admin.reset_filters') }}</a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-white'">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead :class="darkMode ? 'bg-gray-800/70 text-gray-300' : 'bg-gray-50 text-gray-600'">
                    <tr>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase">{{ __('hadith.book') }}</th>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase">{{ __('hadith.collection') }}</th>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase">{{ __('hadith.chapters') }}</th>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase">{{ __('hadith.hadiths') }}</th>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase">{{ __('admin.status') }}</th>
                        <th class="px-5 py-4 text-end text-xs font-bold uppercase">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($books as $book)
                        <tr :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                            <td class="px-5 py-4"><div class="font-bold">{{ $book->title }}</div><div class="mt-1 text-xs text-gray-500">#{{ $book->book_number }} · {{ __('hadith.admin.order') }} {{ $book->sort_order }}</div></td>
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.hadith-collections.show', $book->collection) }}"
                                   class="font-semibold text-[#0056b3] hover:underline dark:text-[#64CCC5]">
                                    {{ $book->collection->name }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $book->collection->code }}</div>
                            </td>
                            <td class="px-5 py-4 font-bold tabular-nums">{{ number_format($book->chapters_count) }}</td>
                            <td class="px-5 py-4 font-bold tabular-nums">{{ number_format($book->hadiths_count) }}</td>
                            <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $book->is_published ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">{{ $book->is_published ? __('hadith.admin.published') : __('hadith.admin.hidden') }}</span></td>
                            <td class="px-5 py-4 text-end"><a href="{{ route('admin.hadith-books.edit', $book) }}" class="inline-flex rounded-lg p-2.5 text-[#0056b3] hover:bg-blue-50 dark:text-[#64CCC5] dark:hover:bg-gray-800" aria-label="{{ __('admin.edit') }}"><i data-lucide="square-pen" class="h-5 w-5"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center text-gray-500">{{ __('hadith.admin.no_books') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($books->hasPages())<div class="border-t px-5 py-4" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">{{ $books->links() }}</div>@endif
    </section>
</div>
@endsection
