@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div>
        <div class="mb-2 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('admin.hadith-chapters.index') }}" class="hover:text-accent">{{ __('hadith.chapters') }}</a>
            <i data-lucide="chevron-right" class="h-4 w-4 rtl:rotate-180"></i><span>#{{ $chapter->chapter_number }}</span>
        </div>
        <h1 class="flex items-center gap-3 text-3xl font-bold gradient-text">
            <span class="rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] p-3 shadow-lg"><i data-lucide="square-pen" class="h-7 w-7 text-white"></i></span>
            {{ __('hadith.admin.edit_chapter') }}
        </h1>
    </div>
    <a href="{{ route('admin.hadith-chapters.index') }}" class="inline-flex items-center gap-2 self-start rounded-xl border-2 px-4 py-2.5 font-semibold"
       :class="darkMode ? 'border-gray-700 bg-gray-800 text-white' : 'border-gray-300 bg-white text-gray-900'">
        <i data-lucide="arrow-left" class="h-5 w-5 rtl:rotate-180"></i>{{ __('admin.back') }}
    </a>
</div>
@endsection

@section('content')
<form method="POST" action="{{ route('admin.hadith-chapters.update', $chapter) }}" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    @csrf
    @method('PUT')
    <input type="hidden" name="updated_at" value="{{ $chapter->updated_at->toISOString() }}">

    <div class="space-y-6 lg:col-span-2">
        <x-admin.form-section :title="__('hadith.admin.localized_content')" :description="__('hadith.admin.localized_content_help')" icon="languages">
            <x-admin.language-tabs :errors="$errors" :show-copy-button="false">
                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $locale)
                    <x-admin.language-tab-panel :language="$locale" :data-language="$locale">
                        <div>
                            <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.title') }} @if($locale === 'ar')<span class="text-red-500">*</span>@endif</label>
                            <input type="text" name="title[{{ $locale }}]" value="{{ old("title.$locale", $chapter->getTranslation('title', $locale, false)) }}"
                                   @required($locale === 'ar') maxlength="500"
                                   class="w-full rounded-xl border px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-accent"
                                   :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white text-gray-900'">
                            @error("title.$locale")<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.introduction') }}</label>
                            <textarea name="intro[{{ $locale }}]" rows="6" class="w-full rounded-xl border px-4 py-3 leading-7 focus:ring-2 focus:ring-accent"
                                      :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">{{ old("intro.$locale", $chapter->getTranslation('intro', $locale, false)) }}</textarea>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-semibold">{{ __('hadith.admin.ending') }}</label>
                            <textarea name="ending[{{ $locale }}]" rows="5" class="w-full rounded-xl border px-4 py-3 leading-7 focus:ring-2 focus:ring-accent"
                                      :class="darkMode ? 'border-gray-700 bg-gray-900 text-white' : 'border-gray-300 bg-white'">{{ old("ending.$locale", $chapter->getTranslation('ending', $locale, false)) }}</textarea>
                        </div>
                    </x-admin.language-tab-panel>
                @endforeach
            </x-admin.language-tabs>
        </x-admin.form-section>
    </div>

    <aside class="space-y-6">
        <x-admin.form-section :title="__('hadith.admin.publication')" icon="eye">
            <x-admin.form.checkbox name="is_published" :label="__('hadith.admin.published')" :checked="$chapter->is_published"
                                   :description="__('hadith.admin.chapter_publication_help')" />
        </x-admin.form-section>
        <x-admin.form-section :title="__('hadith.admin.canonical_identity')" :description="__('hadith.admin.canonical_read_only')" icon="lock">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.collection') }}</dt><dd class="text-end font-bold">{{ $chapter->book->collection->name }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.book') }}</dt><dd class="text-end font-bold">#{{ $chapter->book->book_number }} {{ $chapter->book->title }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.chapter_number') }}</dt><dd class="font-bold">{{ $chapter->chapter_number }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.admin.order') }}</dt><dd class="font-bold">{{ $chapter->sort_order }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('hadith.hadiths') }}</dt><dd class="font-bold">{{ $chapter->hadiths_count }}</dd></div>
            </dl>
        </x-admin.form-section>
        @error('updated_at')<div class="rounded-xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-700">{{ $message }}</div>@enderror
        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] px-6 py-3 font-bold text-white shadow-lg">
            <i data-lucide="save" class="h-5 w-5"></i>{{ __('admin.save_changes') }}
        </button>
    </aside>
</form>
@endsection
