@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="quote" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('hadith_admin.hadiths') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            البحث يجري على النص المفهرس، فيطابق ولو اختلفت الحركات.
        </p>
    </div>
    <a href="{{ route('admin.hadiths.create', ['collection' => $filters['collection']]) }}"
       class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
        <i data-lucide="plus" class="w-5 h-5"></i>
        <span>{{ __('hadith_admin.add') }}</span>
    </a>
</div>
@endsection

@section('content')

@include('admin.hadith._flash')

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-admin.stats-card icon="quote" title="{{ __('hadith_admin.total') }}" :value="$stats['total']" :gradient="true" />
    <x-admin.stats-card icon="check-circle" title="{{ __('hadith_admin.published') }}" :value="$stats['published']" delay="delay-100" />
    {{-- Distinct from "published": a hadith is only visible if its collection,
         book and chapter are published too — scopePubliclyVisible() walks up. --}}
    <x-admin.stats-card icon="globe" title="{{ __('hadith_admin.visible_publicly') }}" :value="$stats['visible']" delay="delay-200" />
    <x-admin.stats-card icon="help-circle" title="{{ __('hadith_admin.ungraded') }}" :value="$stats['ungraded']" delay="delay-300" />
</div>

{{-- Cascading filters: choosing a collection reloads so the book list is scoped
     to it, and likewise book → chapter. Loading all 420 books and every chapter
     into three unscoped selects would be unusable. --}}
<div class="mb-6 p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <form method="GET" action="{{ route('admin.hadiths.index') }}"
          x-data class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="lg:col-span-2">
            <label for="q" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('admin.search') }}</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] }}"
                   placeholder="{{ __('hadith_admin.search_placeholder') }}"
                   class="w-full px-4 py-2.5 rounded-xl border transition-all focus:ring-2 focus:ring-accent/50"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
        </div>

        <div>
            <label for="collection" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.collection') }}</label>
            <select id="collection" name="collection" @change="$el.form.submit()"
                    class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('hadith_admin.all_collections') }}</option>
                @foreach($collections as $c)
                    <option value="{{ $c->id }}" @selected($filters['collection'] === $c->id)>{{ $c->title_ar }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="book" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.book') }}</label>
            <select id="book" name="book" @change="$el.form.submit()" @disabled($books->isEmpty())
                    class="w-full px-4 py-2.5 rounded-xl border disabled:opacity-50"
                    :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('hadith_admin.all_books') }}</option>
                @foreach($books as $b)
                    <option value="{{ $b->id }}" @selected($filters['book'] === $b->id)>{{ $b->book_number }} — {{ $b->title_ar }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="chapter" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.chapter') }}</label>
            <select id="chapter" name="chapter" @disabled($chapters->isEmpty())
                    class="w-full px-4 py-2.5 rounded-xl border disabled:opacity-50"
                    :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('hadith_admin.all_chapters') }}</option>
                @foreach($chapters as $ch)
                    <option value="{{ $ch->id }}" @selected($filters['chapter'] === $ch->id)>{{ $ch->chapter_number }} — {{ $ch->title_ar }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="grade" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.grade') }}</label>
            <select id="grade" name="grade"
                    class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('hadith_admin.all_grades') }}</option>
                @foreach($grades as $g)
                    <option value="{{ $g->value }}" @selected($filters['grade'] === $g->value)>{{ $g->label('ar') }}</option>
                @endforeach
                <option value="ungraded" @selected($filters['grade'] === 'ungraded')>{{ __('hadith_admin.ungraded') }}</option>
            </select>
        </div>

        <div>
            <label for="status" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.all_statuses') }}</label>
            <select id="status" name="status"
                    class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('hadith_admin.all_statuses') }}</option>
                <option value="published" @selected($filters['status'] === 'published')>{{ __('hadith_admin.published') }}</option>
                <option value="draft" @selected($filters['status'] === 'draft')>{{ __('hadith_admin.draft') }}</option>
            </select>
        </div>

        <div class="lg:col-span-6 flex items-center gap-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>{{ __('hadith_admin.filter') }}
            </button>
            <a href="{{ route('admin.hadiths.index') }}" class="px-4 py-2.5 rounded-xl font-semibold border"
               :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('hadith_admin.reset') }}</a>
            <span class="ms-auto text-sm tabular-nums" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ number_format($hadiths->total()) }} نتيجة
            </span>
        </div>
    </form>
</div>

<div class="space-y-3">
    @forelse($hadiths as $hadith)
    <div class="rounded-2xl border p-5" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="px-2 py-1 rounded-lg font-semibold" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                    {{ $hadith->collection?->title_ar }}
                </span>
                <span class="font-mono px-2 py-1 rounded-lg" dir="ltr" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                    {{ $hadith->reference_key }}
                </span>
                @if($hadith->grade)
                    <span class="px-2 py-1 rounded-lg font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                        {{ $hadith->grade->label('ar') }}
                    </span>
                @elseif($hadith->raw_grade)
                    {{-- Grade left NULL on purpose by the mapper; raw_grade is the
                         evidence, so show it rather than imply "no grade". --}}
                    <span class="px-2 py-1 rounded-lg font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                          title="{{ __('hadith_admin.raw_grade') }}">
                        {{ Str::limit($hadith->raw_grade, 40) }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-1">
                <x-admin.hadith-status-toggle :action="route('admin.hadiths.toggle-status', $hadith)" :published="$hadith->is_published" />
                <a href="{{ route('admin.hadiths.show', $hadith) }}" class="p-2 rounded-lg hover:bg-black/10" title="{{ __('hadith_admin.view') }}">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                </a>
                <a href="{{ route('admin.hadiths.edit', $hadith) }}" class="p-2 rounded-lg hover:bg-black/10" title="{{ __('hadith_admin.edit') }}">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                </a>
                <x-admin.hadith-delete-button :action="route('admin.hadiths.destroy', $hadith)" :label="$hadith->reference_key" />
            </div>
        </div>

        <p class="leading-loose" dir="rtl" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
            {{ Str::limit(strip_tags(\App\Domain\Hadith\Support\QuranCitation::strip($hadith->text_ar ?: $hadith->text_en)), 320) }}
        </p>

        @if($hadith->book || $hadith->chapter)
        <p class="mt-3 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
            @if($hadith->book)<span>{{ __('hadith_admin.book') }}: {{ $hadith->book->title_ar }}</span>@endif
            @if($hadith->chapter)<span> · {{ __('hadith_admin.chapter') }}: {{ $hadith->chapter->title_ar }}</span>@endif
        </p>
        @endif
    </div>
    @empty
    <div class="rounded-2xl border border-dashed py-16 text-center" :class="darkMode ? 'border-gray-700 text-gray-400' : 'border-gray-300 text-gray-500'">
        <i data-lucide="search-x" class="w-8 h-8 mx-auto mb-3"></i>
        <p>{{ __('hadith_admin.no_results') }}</p>
    </div>
    @endforelse
</div>

<div class="mt-6">{{ $hadiths->links() }}</div>
@endsection
