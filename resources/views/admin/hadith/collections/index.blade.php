@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="library" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('hadith_admin.collections') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
            كتب الحديث ومصادرها — الأبواب والفصول والأحاديث تتفرّع منها.
        </p>
    </div>
    <a href="{{ route('admin.hadith-collections.create') }}"
       class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
        <i data-lucide="plus" class="w-5 h-5"></i>
        <span>{{ __('hadith_admin.add') }}</span>
    </a>
</div>
@endsection

@section('content')

@include('admin.hadith._flash')

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <x-admin.stats-card icon="library" title="{{ __('hadith_admin.collections') }}" :value="$stats['total']" :gradient="true" />
    <x-admin.stats-card icon="check-circle" title="{{ __('hadith_admin.published') }}" :value="$stats['published']" delay="delay-100" />
    <x-admin.stats-card icon="quote" title="{{ __('hadith_admin.hadiths') }}" :value="$stats['hadiths']" delay="delay-200" />
</div>

<div class="mb-6 p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <form method="GET" action="{{ route('admin.hadith-collections.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
            <label for="q" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.search_collections') }}</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] }}"
                   placeholder="{{ __('hadith_admin.search_collections') }}"
                   class="w-full px-4 py-2.5 rounded-xl border transition-all focus:ring-2 focus:ring-accent/50"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.all_statuses') }}</label>
            <select id="status" name="status"
                    class="w-full px-4 py-2.5 rounded-xl border transition-all focus:ring-2 focus:ring-accent/50"
                    :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('hadith_admin.all_statuses') }}</option>
                <option value="published" @selected($filters['status'] === 'published')>{{ __('hadith_admin.published') }}</option>
                <option value="draft" @selected($filters['status'] === 'draft')>{{ __('hadith_admin.draft') }}</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>{{ __('hadith_admin.filter') }}
            </button>
            <a href="{{ route('admin.hadith-collections.index') }}"
               class="px-4 py-2.5 rounded-xl font-semibold border"
               :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('hadith_admin.reset') }}</a>
        </div>
    </form>
</div>

<div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
    <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-sm">
            <thead>
                <tr :class="darkMode ? 'bg-gray-800/50 text-gray-400' : 'bg-gray-50 text-gray-600'">
                    <th class="text-start font-semibold px-4 py-3 text-xs">{{ __('hadith_admin.collection') }}</th>
                    <th class="text-start font-semibold px-4 py-3 text-xs">{{ __('hadith_admin.books') }}</th>
                    <th class="text-start font-semibold px-4 py-3 text-xs">{{ __('hadith_admin.hadiths') }}</th>
                    <th class="text-start font-semibold px-4 py-3 text-xs">{{ __('hadith_admin.published') }}</th>
                    <th class="text-start font-semibold px-4 py-3 text-xs">{{ __('hadith_admin.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($collections as $collection)
                <tr class="border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.hadith-collections.show', $collection) }}" class="font-semibold hover:text-accent transition-colors">
                            {{ $collection->title_ar }}
                        </a>
                        <div class="text-xs font-mono mt-0.5" dir="ltr" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $collection->slug }}</div>
                    </td>
                    <td class="px-4 py-3 tabular-nums">
                        <a href="{{ route('admin.hadith-books.index', ['collection' => $collection->id]) }}" class="hover:text-accent">
                            {{ number_format($collection->books_count) }}
                        </a>
                    </td>
                    <td class="px-4 py-3 tabular-nums">
                        <a href="{{ route('admin.hadiths.index', ['collection' => $collection->id]) }}" class="hover:text-accent">
                            {{ number_format($collection->hadiths_count) }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.hadith-status-toggle
                            :action="route('admin.hadith-collections.toggle-status', $collection)"
                            :published="$collection->is_published" />
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-1">
                            <a href="{{ route('admin.hadith-collections.show', $collection) }}"
                               class="p-2 rounded-lg hover:bg-black/10 transition-colors" title="{{ __('hadith_admin.view') }}">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            <a href="{{ route('admin.hadith-collections.edit', $collection) }}"
                               class="p-2 rounded-lg hover:bg-black/10 transition-colors" title="{{ __('hadith_admin.edit') }}">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                            <x-admin.hadith-delete-button
                                :action="route('admin.hadith-collections.destroy', $collection)"
                                :label="$collection->title_ar" />
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

<div class="mt-6">{{ $collections->links() }}</div>
@endsection
