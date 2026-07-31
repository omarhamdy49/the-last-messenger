@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div>
        <h1 class="flex items-center gap-3 text-3xl font-bold gradient-text">
            <span class="rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] p-3 shadow-lg">
                <i data-lucide="scroll-text" class="h-7 w-7 text-white"></i>
            </span>
            {{ __('admin.manage_hadiths') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ __('hadith.admin.hadith_subtitle') }}</p>
    </div>
    <a href="{{ route('admin.hadith-books.index') }}"
       class="inline-flex items-center gap-2 self-start rounded-xl border-2 px-4 py-2.5 font-semibold transition-all"
       :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
        <i data-lucide="library-big" class="h-5 w-5"></i>
        {{ __('admin.hadith_books') }}
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stats-card icon="scroll-text" :title="__('admin.total_hadiths')" :value="$stats['total']" :gradient="true" :compact="true" />
        <x-admin.stats-card icon="eye" :title="__('hadith.admin.published')" :value="$stats['published']" :compact="true" />
        <x-admin.stats-card icon="circle-help" :title="__('hadith.admin.ungraded')" :value="$stats['ungraded']" :compact="true" />
        <x-admin.stats-card icon="languages" :title="__('hadith.admin.text_records')" :value="$stats['translations']" :compact="true" />
    </div>

    <section class="rounded-2xl border p-5" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-white'">
        <form method="GET" action="{{ route('admin.hadith.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label for="hadith-search" class="mb-2 block text-sm font-semibold">{{ __('admin.search') }}</label>
                <input id="hadith-search" type="search" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('hadith.admin.search_hadith') }}"
                       class="w-full rounded-xl border px-4 py-2.5 focus:border-transparent focus:ring-2 focus:ring-accent"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white text-gray-900'">
            </div>
            <div>
                <label for="collection-filter" class="mb-2 block text-sm font-semibold">{{ __('hadith.collection') }}</label>
                <select id="collection-filter" name="collection_id" class="w-full rounded-xl border px-3 py-2.5"
                        :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($collections as $collection)
                        <option value="{{ $collection->id }}" @selected(request('collection_id') == $collection->id)>
                            {{ $collection->name }} ({{ $collection->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="book-filter" class="mb-2 block text-sm font-semibold">{{ __('hadith.book') }}</label>
                <select id="book-filter" name="book_id" class="w-full rounded-xl border px-3 py-2.5"
                        :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach($books as $book)
                        <option value="{{ $book->id }}" @selected(request('book_id') == $book->id)>
                            {{ $book->book_number }} — {{ $book->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="grade-filter" class="mb-2 block text-sm font-semibold">{{ __('hadith.grade') }}</label>
                <select id="grade-filter" name="grade" class="w-full rounded-xl border px-3 py-2.5"
                        :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach(\App\Domain\Hadith\Enums\HadithGrade::cases() as $grade)
                        <option value="{{ $grade->value }}" @selected(request('grade') === $grade->value)>{{ $grade->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status-filter" class="mb-2 block text-sm font-semibold">{{ __('admin.status') }}</label>
                <select id="status-filter" name="status" class="w-full rounded-xl border px-3 py-2.5"
                        :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="published" @selected(request('status') === 'published')>{{ __('hadith.admin.published') }}</option>
                    <option value="draft" @selected(request('status') === 'draft')>{{ __('hadith.admin.hidden') }}</option>
                </select>
            </div>
            <div class="flex items-end gap-3 xl:col-span-6">
                <button class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] px-5 py-2.5 font-semibold text-white shadow-md">
                    <i data-lucide="filter" class="h-4 w-4"></i>{{ __('admin.filter') }}
                </button>
                <a href="{{ route('admin.hadith.index') }}" class="rounded-xl border px-5 py-2.5 font-semibold"
                   :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-300 hover:bg-gray-50'">{{ __('admin.reset_filters') }}</a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-white'">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] text-start">
                <thead :class="darkMode ? 'bg-gray-800/70 text-gray-300' : 'bg-gray-50 text-gray-600'">
                    <tr>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase tracking-wider">{{ __('hadith.reference') }}</th>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase tracking-wider">{{ __('hadith.collection') }} / {{ __('hadith.book') }}</th>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase tracking-wider">{{ __('hadith.text') }}</th>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase tracking-wider">{{ __('hadith.grade') }}</th>
                        <th class="px-5 py-4 text-start text-xs font-bold uppercase tracking-wider">{{ __('admin.status') }}</th>
                        <th class="px-5 py-4 text-end text-xs font-bold uppercase tracking-wider">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($hadiths as $hadith)
                        @php($preview = $hadith->texts->firstWhere('locale', app()->getLocale()) ?? $hadith->texts->firstWhere('locale', 'ar'))
                        <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                            <td class="px-5 py-4">
                                <div class="font-bold">{{ $hadith->hadith_number }}</div>
                                <div class="mt-1 text-xs text-gray-500">ID {{ $hadith->id }}@if($hadith->sunnah_urn) · URN {{ $hadith->sunnah_urn }}@endif</div>
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.hadith-collections.show', $hadith->collection) }}"
                                   class="font-semibold text-[#0056b3] hover:underline dark:text-[#64CCC5]">
                                    {{ $hadith->collection->name }}
                                </a>
                                <div class="mt-1 text-sm text-gray-500">{{ $hadith->book->book_number }} — {{ $hadith->book->title }}</div>
                                @if($hadith->chapter)<div class="mt-1 text-xs text-gray-500">{{ __('hadith.chapter') }} {{ $hadith->chapter->chapter_number }}</div>@endif
                            </td>
                            <td class="max-w-lg px-5 py-4">
                                <p dir="{{ $preview?->locale === 'ar' ? 'rtl' : 'ltr' }}" class="line-clamp-2 text-sm leading-7">{{ $preview?->body ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $hadith->display_grade->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold {{ $hadith->is_published ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $hadith->is_published ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                    {{ $hadith->is_published ? __('hadith.admin.published') : __('hadith.admin.hidden') }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-end">
                                <a href="{{ route('admin.hadith.edit', $hadith) }}"
                                   class="inline-flex rounded-lg p-2.5 text-[#0056b3] transition-colors hover:bg-blue-50 dark:text-[#64CCC5] dark:hover:bg-gray-800"
                                   title="{{ __('admin.edit') }}" aria-label="{{ __('admin.edit') }}">
                                    <i data-lucide="square-pen" class="h-5 w-5"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-16 text-center text-gray-500">{{ __('admin.no_hadiths_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($hadiths->hasPages())<div class="border-t px-5 py-4" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">{{ $hadiths->links() }}</div>@endif
    </section>

    @if($imports->isNotEmpty())
        <section class="rounded-2xl border p-5" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-white'">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-bold"><i data-lucide="database-zap" class="h-5 w-5 text-accent"></i>{{ __('hadith.admin.recent_imports') }}</h2>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                @foreach($imports as $import)
                    <div class="rounded-xl border p-3" :class="darkMode ? 'border-gray-700 bg-gray-800/50' : 'border-gray-200 bg-gray-50'">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-bold">{{ $import->collection_code }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $import->status->value === 'completed' ? 'bg-green-100 text-green-700' : ($import->status->value === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $import->status->value }}</span>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">{{ optional($import->completed_at ?? $import->started_at)->diffForHumans() }}</p>
                        @if($import->error_message)<p class="mt-2 line-clamp-2 text-xs text-red-500">{{ $import->error_message }}</p>@endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
