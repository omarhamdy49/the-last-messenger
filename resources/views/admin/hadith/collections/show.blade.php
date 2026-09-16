@extends('layouts.admin')
@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text">{{ $collection->title_ar }}</h1>
        <p class="mt-2 text-sm font-mono" dir="ltr" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $collection->slug }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($collection->has_books)
        <a href="{{ route('admin.hadith-books.create', ['collection' => $collection->id]) }}" class="px-5 py-2.5 rounded-xl font-semibold border" :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">
            <i data-lucide="plus" class="w-4 h-4 inline"></i> باب جديد
        </a>
        @else
        <a href="{{ route('admin.hadiths.create', ['collection' => $collection->id]) }}" class="px-5 py-2.5 rounded-xl font-semibold border">{{ __('hadith_admin.add') }} {{ __('hadith_admin.hadiths') }}</a>
        @endif
        <a href="{{ route('admin.hadith-collections.edit', $collection) }}" class="px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">{{ __('hadith_admin.edit') }}</a>
    </div>
</div>
@endsection
@section('content')
@include('admin.hadith._flash')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-admin.stats-card icon="book" title="{{ __('hadith_admin.books') }}" :value="$collection->books_count" :gradient="true" />
    <x-admin.stats-card icon="quote" title="{{ __('hadith_admin.hadiths') }}" :value="$collection->hadiths_count" delay="delay-100" />
    <x-admin.stats-card icon="hash" title="إجمالي مُعلن" :value="$collection->total_hadith ?? 0" delay="delay-200" />
    <x-admin.stats-card icon="{{ $collection->is_published ? 'eye' : 'eye-off' }}" title="{{ $collection->is_published ? __('hadith_admin.published') : __('hadith_admin.draft') }}" :value="''" delay="delay-300" />
</div>

@if($hadiths !== null)
<div class="space-y-3">
    <h2 class="font-bold">{{ __('hadith_admin.hadiths') }}</h2>
    @forelse($hadiths as $hadith)
        <div class="rounded-2xl border p-5" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <span class="font-mono" dir="ltr">{{ $hadith->reference_key }}</span>
                <div class="flex items-center gap-2">
                    <x-admin.hadith-status-toggle :action="route('admin.hadiths.toggle-status', $hadith)" :published="$hadith->is_published" />
                    <a href="{{ route('admin.hadiths.show', $hadith) }}" class="text-accent">{{ __('hadith_admin.view') }}</a>
                    <a href="{{ route('admin.hadiths.edit', $hadith) }}" class="text-accent">{{ __('hadith_admin.edit') }}</a>
                    <x-admin.hadith-delete-button :action="route('admin.hadiths.destroy', $hadith)" :label="$hadith->reference_key" />
                </div>
            </div>
            <p class="leading-loose" dir="rtl">{{ Str::limit(strip_tags((string) ($hadith->text_ar ?: $hadith->text_en)), 320) }}</p>
        </div>
    @empty
        <p class="py-12 text-center">{{ __('hadith_admin.no_results') }}</p>
    @endforelse
</div>
<div class="mt-6">{{ $hadiths->links() }}</div>
@else
<div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <div class="px-5 py-4 border-b flex items-center justify-between" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <h2 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('hadith_admin.books') }}</h2>
        <a href="{{ route('admin.hadiths.index', ['collection' => $collection->id]) }}" class="text-sm text-accent hover:underline">عرض كل الأحاديث</a>
    </div>
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-sm">
            <thead>
                <tr :class="darkMode ? 'bg-gray-800/50 text-gray-400' : 'bg-gray-50 text-gray-600'">
                    <th class="text-start px-4 py-3 text-xs font-semibold">#</th>
                    <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.book') }}</th>
                    <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.chapters') }}</th>
                    <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.hadiths') }}</th>
                    <th class="text-start px-4 py-3 text-xs font-semibold">{{ __('hadith_admin.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                <tr class="border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <td class="px-4 py-3 tabular-nums font-mono" dir="ltr">{{ $book->book_number }}</td>
                    <td class="px-4 py-3 font-semibold">{{ $book->title_ar }}</td>
                    <td class="px-4 py-3 tabular-nums">
                        <a href="{{ route('admin.hadith-chapters.index', ['book' => $book->id]) }}" class="hover:text-accent">{{ number_format($book->chapters_count) }}</a>
                    </td>
                    <td class="px-4 py-3 tabular-nums">
                        <a href="{{ route('admin.hadiths.index', ['collection' => $collection->id, 'book' => $book->id]) }}" class="hover:text-accent">{{ number_format($book->hadiths_count) }}</a>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1">
                            <x-admin.hadith-status-toggle :action="route('admin.hadith-books.toggle-status', $book)" :published="$book->is_published" />
                            <a href="{{ route('admin.hadith-books.edit', $book) }}" class="p-2 rounded-lg hover:bg-black/10"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-12 text-center" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('hadith_admin.no_results') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $books->links() }}</div>
@endif
@endsection
