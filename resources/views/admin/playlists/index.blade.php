@extends('layouts.admin')

@php
    $t = fn (string $key, array $replace = []) => __('quran.playlists_admin.'.$key, $replace);
    $locale = app()->getLocale();
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all';
    $inputTheme = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
    $labelTheme = "darkMode ? 'text-gray-300' : 'text-gray-700'";
    $num = fn ($n) => $locale === 'ar' ? arabic_digits($n) : number_format((int) $n);
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="list-music" class="w-7 h-7 text-white" aria-hidden="true"></i>
            </div>
            {{ $t('title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $t('subtitle') }}</p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <button type="button" x-data @click="$dispatch('toggle-filters')"
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
                :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5" aria-hidden="true"></i>
            <span>{{ $t('filters') }}</span>
            @if (count($filters))
                <span class="px-2 py-0.5 text-xs rounded-full bg-accent text-white">{{ count($filters) }}</span>
            @endif
        </button>
        <a href="{{ route('public.quran.playlists.index') }}" target="_blank" rel="noopener"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="external-link" class="w-5 h-5" aria-hidden="true"></i>
            <span>{{ $t('view_public') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="playlistsAdmin(@js(count($filters) > 0))" @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <x-admin.stats-card icon="list-music" :title="$t('stats_total')" :value="(int) $stats->total" :gradient="true" />
        <x-admin.stats-card icon="globe" :title="$t('stats_public')" :value="(int) $stats->public_count" delay="delay-100" />
        <x-admin.stats-card icon="star" :title="$t('stats_featured')" :value="(int) $stats->featured" delay="delay-200" />
        <x-admin.stats-card icon="headphones" :title="$t('stats_plays')" :value="(int) $stats->plays" delay="delay-300" />
    </div>

    <!-- Filters -->
    <div x-show="filtersOpen" x-collapse x-cloak class="mb-6">
        <form method="GET" action="{{ route('admin.playlists.index') }}"
              class="p-6 rounded-2xl border grid grid-cols-1 md:grid-cols-4 gap-4"
              :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="md:col-span-2">
                <label for="filter_search" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('search') }}</label>
                <input type="search" id="filter_search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $t('search_placeholder') }}"
                       class="{{ $inputClass }}" :class="{{ $inputTheme }}">
            </div>
            <div>
                <label for="filter_public" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('visibility') }}</label>
                <select id="filter_public" name="public" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                    <option value="">{{ $t('all') }}</option>
                    <option value="1" @selected(($filters['public'] ?? '') === '1')>{{ $t('public') }}</option>
                    <option value="0" @selected(($filters['public'] ?? '') === '0')>{{ $t('private') }}</option>
                </select>
            </div>
            <div>
                <label for="filter_featured" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('featured') }}</label>
                <select id="filter_featured" name="featured" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                    <option value="">{{ $t('all') }}</option>
                    <option value="1" @selected(($filters['featured'] ?? '') === '1')>{{ $t('featured') }}</option>
                    <option value="0" @selected(($filters['featured'] ?? '') === '0')>{{ $t('not_featured') }}</option>
                </select>
            </div>
            <div class="md:col-span-4 flex items-center gap-3">
                <button type="submit" class="px-5 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow focus:outline-none focus:ring-2 focus:ring-accent">{{ $t('apply') }}</button>
                <a href="{{ route('admin.playlists.index') }}" class="px-5 py-2.5 rounded-xl font-semibold border-2" :class="darkMode ? 'border-gray-700 text-white' : 'border-gray-300 text-gray-900'">{{ $t('clear') }}</a>
            </div>
        </form>
    </div>

    <!-- Bulk bar -->
    <form method="POST" action="{{ route('admin.playlists.bulk-unpublish') }}" id="bulk-form"
          x-show="selected.length" x-cloak x-transition
          onsubmit="return window.confirmSubmit(this, { message: {{ Js::from($t('bulk_confirm')) }} })"
          class="mb-4 p-4 rounded-2xl border flex items-center justify-between gap-3"
          :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        @csrf
        <template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
        <span class="text-sm font-semibold" :class="darkMode ? 'text-gray-200' : 'text-gray-800'" x-text="@js($t('selected', ['count' => ':count'])).replace(':count', selected.length)"></span>
        <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold bg-amber-500 text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-accent flex items-center gap-2">
            <i data-lucide="eye-off" class="w-4 h-4"></i>{{ $t('bulk_unpublish') }}
        </button>
    </form>

    <!-- Table -->
    <div class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        @if ($playlists->count())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead :class="darkMode ? 'bg-gray-800/60 text-gray-300' : 'bg-gray-50 text-gray-600'">
                        <tr>
                            <th scope="col" class="px-4 py-3 w-10">
                                <input type="checkbox" class="rounded border-gray-300 text-accent focus:ring-accent" aria-label="select all"
                                       :checked="selected.length === ids.length && ids.length > 0" @change="toggleAll($event.target.checked)">
                            </th>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ $t('col_name') }}</th>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ $t('col_owner') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold">{{ $t('col_surahs') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold">{{ $t('col_public') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold">{{ $t('col_featured') }}</th>
                            <th scope="col" class="px-4 py-3 text-center font-semibold">{{ $t('col_plays') }}</th>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ $t('col_created') }}</th>
                            <th scope="col" class="px-4 py-3 text-end font-semibold">{{ $t('col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                        @foreach ($playlists as $playlist)
                            <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/40' : 'hover:bg-gray-50'">
                                <td class="px-4 py-3">
                                    <input type="checkbox" class="rounded border-gray-300 text-accent focus:ring-accent" value="{{ $playlist->id }}" x-model.number="selected" aria-label="select">
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.playlists.edit', $playlist->id) }}" class="font-bold hover:text-accent transition-colors" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $playlist->name }}</a>
                                    <p class="text-xs" dir="ltr" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $playlist->slug }}</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    <span class="block font-semibold">{{ $playlist->owner?->name }}</span>
                                    <span class="block text-xs" dir="ltr" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $playlist->owner?->email }}</span>
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ $num($playlist->items_count) }}
                                    <span class="block text-[11px]" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $t('scope_'.$playlist->scope) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('admin.playlists.toggle', [$playlist->id, 'public']) }}" class="inline">
                                        @csrf
                                        <button type="submit" role="switch" aria-checked="{{ $playlist->is_public ? 'true' : 'false' }}"
                                                class="relative inline-flex w-11 h-6 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-accent {{ $playlist->is_public ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"
                                                title="{{ $playlist->is_public ? $t('public') : $t('private') }}">
                                            <span class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform {{ $playlist->is_public ? 'start-0.5 rtl:-translate-x-5 ltr:translate-x-5' : 'start-0.5' }}"></span>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <form method="POST" action="{{ route('admin.playlists.toggle', [$playlist->id, 'featured']) }}" class="inline">
                                        @csrf
                                        <button type="submit" aria-pressed="{{ $playlist->is_featured ? 'true' : 'false' }}"
                                                class="w-9 h-9 rounded-full inline-flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-accent {{ $playlist->is_featured ? 'bg-amber-100 text-amber-600' : 'text-gray-400 hover:text-amber-500' }}"
                                                title="{{ $t('featured') }}">
                                            <i data-lucide="star" class="w-4 h-4 {{ $playlist->is_featured ? 'fill-current' : '' }}"></i>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $num($playlist->plays_count) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $playlist->created_at?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('public.quran.playlists.show', $playlist->slug) }}" target="_blank" rel="noopener"
                                           class="w-9 h-9 rounded-lg inline-flex items-center justify-center transition-colors" :class="darkMode ? 'text-gray-400 hover:bg-gray-800 hover:text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900'"
                                           title="{{ $t('view_public') }}"><i data-lucide="external-link" class="w-4 h-4"></i></a>
                                        <a href="{{ route('admin.playlists.edit', $playlist->id) }}"
                                           class="w-9 h-9 rounded-lg inline-flex items-center justify-center transition-colors" :class="darkMode ? 'text-gray-400 hover:bg-gray-800 hover:text-white' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900'"
                                           title="{{ $t('edit') }}"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                                        <form method="POST" action="{{ route('admin.playlists.destroy', $playlist->id) }}" class="inline"
                                              onsubmit="return window.confirmSubmit(this, { message: {{ Js::from($t('delete_message', ['name' => $playlist->name])) }} })">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-9 h-9 rounded-lg inline-flex items-center justify-center text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="{{ $t('delete') }}">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                {{ $playlists->links() }}
            </div>
        @else
            <div class="py-16 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4" :class="darkMode ? 'bg-gray-800 text-gray-500' : 'bg-gray-100 text-gray-400'">
                    <i data-lucide="list-music" class="w-8 h-8"></i>
                </div>
                <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $t('empty_title') }}</h3>
                <p class="mt-1 text-sm max-w-md mx-auto" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ $t('empty_message') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function playlistsAdmin(filtersOpen) {
        return {
            filtersOpen,
            selected: [],
            ids: @js($playlists->getCollection()->pluck('id')->all()),
            toggleAll(on) { this.selected = on ? [...this.ids] : []; },
        };
    }
</script>
@endpush
