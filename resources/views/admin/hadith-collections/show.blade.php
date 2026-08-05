@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div>
        <div class="mb-2 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('admin.hadith-collections.index') }}" class="hover:text-accent">{{ __('hadith.collections') }}</a>
            <i data-lucide="chevron-right" class="h-4 w-4 rtl:rotate-180"></i>
            <span>{{ $collection->code }}</span>
        </div>
        <h1 class="flex items-center gap-3 text-3xl font-bold gradient-text">
            <span class="rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] p-3 shadow-lg">
                <i data-lucide="book-copy" class="h-7 w-7 text-white"></i>
            </span>
            {{ $collection->name }}
        </h1>
    </div>
    <a href="{{ route('admin.hadith-collections.index') }}"
       class="inline-flex items-center gap-2 self-start rounded-xl border-2 px-4 py-2.5 font-semibold"
       :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white text-gray-900'">
        <i data-lucide="arrow-left" class="h-5 w-5 rtl:rotate-180"></i>{{ __('admin.back') }}
    </a>
</div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-admin.stats-card icon="library-big" :title="__('hadith.books')" :value="$collection->books_count" :gradient="true" :compact="true" />
        <x-admin.stats-card icon="list-tree" :title="__('hadith.chapters')" :value="$books->sum('chapters_count')" :compact="true" />
        <x-admin.stats-card icon="scroll-text" :title="__('hadith.hadiths')" :value="$collection->hadiths_count" :compact="true" />
        <x-admin.stats-card icon="languages" :title="__('hadith.admin.available_languages')" :value="$availableLanguages" :compact="true" />
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('admin.hadith-books.index', ['collection_id' => $collection->id]) }}"
           class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 font-semibold"
           :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-300 hover:bg-gray-50'">
            <i data-lucide="library-big" class="h-4 w-4"></i>{{ __('hadith.admin.view_collection_books') }}
        </a>
        <a href="{{ route('admin.hadith.index', ['collection_id' => $collection->id]) }}"
           class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 font-semibold"
           :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-300 hover:bg-gray-50'">
            <i data-lucide="scroll-text" class="h-4 w-4"></i>{{ __('hadith.admin.view_collection_hadiths') }}
        </a>
    </div>

    <form method="POST" action="{{ route('admin.hadith-collections.update', $collection) }}" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        @csrf
        @method('PUT')
        <input type="hidden" name="updated_at" value="{{ $collection->updated_at->toISOString() }}">

        <div class="lg:col-span-2">
            <x-admin.form-section :title="__('hadith.admin.localized_content')" :description="__('hadith.admin.collection_content_help')" icon="languages">
                <x-admin.language-tabs :errors="$errors" :show-copy-button="false">
                    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $locale)
                        <x-admin.language-tab-panel :language="$locale" :data-language="$locale">
                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.name') }} @if($locale === 'ar')<span class="text-red-500">*</span>@endif</label>
                                    <input type="text" name="name[{{ $locale }}]"
                                           value="{{ old("name.$locale", $collection->getTranslation('name', $locale, false)) }}"
                                           @required($locale === 'ar') maxlength="500"
                                           class="w-full rounded-xl border px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-accent"
                                           :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">
                                    @error("name.$locale")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.author') }}</label>
                                    <input type="text" name="author[{{ $locale }}]"
                                           value="{{ old("author.$locale", $collection->getTranslation('author', $locale, false)) }}"
                                           maxlength="500"
                                           class="w-full rounded-xl border px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-accent"
                                           :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.short_intro') }}</label>
                                    <textarea name="short_intro[{{ $locale }}]" rows="4"
                                              class="w-full rounded-xl border px-4 py-3 leading-7 focus:ring-2 focus:ring-accent"
                                              :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">{{ old("short_intro.$locale", $collection->getTranslation('short_intro', $locale, false)) }}</textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.about') }}</label>
                                    <textarea name="about[{{ $locale }}]" rows="8"
                                              class="w-full rounded-xl border px-4 py-3 leading-7 focus:ring-2 focus:ring-accent"
                                              :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">{{ old("about.$locale", $collection->getTranslation('about', $locale, false)) }}</textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.introduction') }}</label>
                                    <textarea name="introduction[{{ $locale }}]" rows="12"
                                              class="w-full rounded-xl border px-4 py-3 leading-7 focus:ring-2 focus:ring-accent"
                                              :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">{{ old("introduction.$locale", $collection->getTranslation('introduction', $locale, false)) }}</textarea>
                                </div>
                            </div>
                        </x-admin.language-tab-panel>
                    @endforeach
                </x-admin.language-tabs>
            </x-admin.form-section>
        </div>

        <aside class="space-y-6">
            <x-admin.form-section :title="__('hadith.admin.publication')" icon="eye">
                <div class="space-y-5">
                    <x-admin.form.checkbox name="is_published" :label="__('hadith.admin.published')" :checked="$collection->is_published"
                                           :description="__('hadith.admin.collection_publication_help')" />
                    <x-admin.form.checkbox name="featured" :label="__('hadith.featured')" :checked="$collection->featured"
                                           :description="__('hadith.admin.collection_featured_help')" />
                </div>
            </x-admin.form-section>
            <x-admin.form-section :title="__('hadith.admin.canonical_identity')" :description="__('hadith.admin.canonical_read_only')" icon="lock">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.code') }}</dt><dd class="font-bold">{{ $collection->code }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.order') }}</dt><dd class="font-bold">{{ $collection->display_order }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.has_books') }}</dt><dd class="font-bold">{{ $collection->has_books ? __('admin.yes') : __('admin.no') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.has_chapters') }}</dt><dd class="font-bold">{{ $collection->has_chapters ? __('admin.yes') : __('admin.no') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.has_volumes') }}</dt><dd class="font-bold">{{ $collection->has_volumes ? __('admin.yes') : __('admin.no') }}</dd></div>
                    <div><dt class="mb-1 text-gray-500">{{ __('hadith.admin.reference_template') }}</dt><dd dir="ltr" class="break-words font-bold">{{ $collection->reference_template ?? '—' }}</dd></div>
                </dl>
            </x-admin.form-section>
            @error('updated_at')<div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $message }}</div>@enderror
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] px-6 py-3 font-bold text-white shadow-lg">
                <i data-lucide="save" class="h-5 w-5"></i>{{ __('admin.save_changes') }}
            </button>
        </aside>
    </form>

    <section class="overflow-hidden rounded-2xl border" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-white'">
        <div class="flex items-center justify-between gap-3 border-b px-5 py-4" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h2 class="text-lg font-bold">{{ __('hadith.books') }}</h2>
            <span class="text-sm text-gray-500">{{ $books->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px]">
                <thead :class="darkMode ? 'bg-gray-800/70 text-gray-300' : 'bg-gray-50 text-gray-600'">
                    <tr>
                        <th class="px-5 py-3 text-start text-xs font-bold uppercase">{{ __('hadith.book') }}</th>
                        <th class="px-5 py-3 text-start text-xs font-bold uppercase">{{ __('hadith.chapters') }}</th>
                        <th class="px-5 py-3 text-start text-xs font-bold uppercase">{{ __('hadith.hadiths') }}</th>
                        <th class="px-5 py-3 text-start text-xs font-bold uppercase">{{ __('admin.status') }}</th>
                        <th class="px-5 py-3 text-end text-xs font-bold uppercase">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                    @forelse($books as $book)
                        <tr :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                            <td class="px-5 py-4"><a href="{{ route('admin.hadith-books.edit', $book) }}" class="font-bold text-[#0056b3] hover:underline dark:text-[#64CCC5]">#{{ $book->book_number }} — {{ $book->title }}</a></td>
                            <td class="px-5 py-4 font-bold">{{ $book->chapters_count }}</td>
                            <td class="px-5 py-4 font-bold">{{ $book->hadiths_count }}</td>
                            <td class="px-5 py-4">{{ $book->is_published ? __('hadith.admin.published') : __('hadith.admin.hidden') }}</td>
                            <td class="px-5 py-4 text-end"><a href="{{ route('admin.hadith-books.edit', $book) }}" class="inline-flex rounded-lg p-2 text-[#0056b3] hover:bg-blue-50 dark:text-[#64CCC5] dark:hover:bg-gray-800" aria-label="{{ __('admin.edit') }}"><i data-lucide="square-pen" class="h-4 w-4"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-500">{{ __('hadith.admin.no_books') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($recentHadiths->isNotEmpty())
        <section class="rounded-2xl border p-5" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-white'">
            <h2 class="mb-4 text-lg font-bold">{{ __('hadith.admin.recent_hadiths') }}</h2>
            <div class="space-y-3">
                @foreach($recentHadiths as $hadith)
                    @php($preview = $hadith->texts->firstWhere('locale', app()->getLocale()) ?? $hadith->texts->firstWhere('locale', 'ar') ?? $hadith->texts->first())
                    <a href="{{ route('admin.hadith.edit', $hadith) }}"
                       class="flex items-start gap-4 rounded-xl border p-4 transition-colors"
                       :class="darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50'">
                        <span class="rounded-lg bg-blue-100 px-3 py-1.5 text-sm font-bold text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">{{ $hadith->hadith_number }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs text-gray-500">#{{ $hadith->book->book_number }} — {{ $hadith->book->title }}</span>
                            <span class="mt-1 block line-clamp-2 leading-7">{{ $preview?->body ?? '—' }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
