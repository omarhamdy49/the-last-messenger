@extends('layouts.admin')

@php
    $t = fn (string $key, array $replace = []) => __('quran.profiles_admin.'.$key, $replace);
    $locale = app()->getLocale();
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all';
    $inputTheme = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
    $labelTheme = "darkMode ? 'text-gray-300' : 'text-gray-700'";
    $chipLabels = [
        'search' => fn ($v) => $t('search').': '.$v,
        'status' => fn ($v) => $t('status').': '.$t($v),
        'featured' => fn ($v) => $v === '1' ? $t('featured') : $t('not_featured'),
        'country' => fn ($v) => trim((country_flag($v) ?? '').' '.(country_name($v) ?? $v)),
        'recordings' => fn ($v) => $t('recordings_filter').': '.$t($v),
        'sort' => fn ($v) => $t('sort').': '.$t('sort_'.$v),
    ];
    $rows = $profiles->getCollection()->mapWithKeys(fn ($p) => [$p->id => ['active' => $p->is_active, 'featured' => $p->is_featured]]);
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="users" class="w-7 h-7 text-white" aria-hidden="true"></i>
            </div>
            {{ __('quran.recordings.profiles') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $t('subtitle') }}</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="button" x-data @click="$dispatch('toggle-filters')"
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
                :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5" aria-hidden="true"></i>
            <span>{{ $t('filters') }}</span>
            @if(count($filters))
                <span class="px-2 py-0.5 text-xs rounded-full bg-accent text-white">{{ count($filters) }}</span>
            @endif
        </button>
        <a href="{{ route('admin.reciters.index', ['recordings' => 1]) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="disc-3" class="w-5 h-5" aria-hidden="true"></i>
            <span>{{ __('quran.recordings.recordings') }}</span>
        </a>
        <a href="{{ route('admin.reciter-profiles.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white focus:outline-none focus:ring-2 focus:ring-accent">
            <i data-lucide="plus" class="w-5 h-5" aria-hidden="true"></i>
            <span>{{ $t('create') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="reciterProfilesPage(@js($rows), @js(count($filters) > 0))" @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-admin.stats-card icon="users" :title="$t('stats_total')" :value="(int) $stats->total" :gradient="true" />
        <x-admin.stats-card icon="circle-check" :title="$t('stats_active')" :value="(int) $stats->active" delay="delay-100" />
        <x-admin.stats-card icon="star" :title="$t('stats_featured')" :value="(int) $stats->featured" delay="delay-200" />
        <x-admin.stats-card icon="disc-3" :title="$t('stats_multiple')" :value="(int) $stats->multiple" delay="delay-300"
                            :href="route('admin.reciter-profiles.index', ['recordings' => 'multiple'])" />
    </div>

    <!-- Filters -->
    <div x-show="filtersOpen" x-collapse x-cloak class="mb-6">
        <form method="GET" action="{{ route('admin.reciter-profiles.index') }}"
              class="p-6 rounded-2xl border grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4"
              :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="md:col-span-3 xl:col-span-2">
                <label for="filter_search" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('search') }}</label>
                <input type="search" id="filter_search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $t('search_placeholder') }}"
                       class="{{ $inputClass }}" :class="{{ $inputTheme }}">
            </div>
            <div>
                <label for="filter_status" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('status') }}</label>
                <select id="filter_status" name="status" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                    <option value="">{{ $t('all') }}</option>
                    @foreach(['active', 'inactive'] as $option)
                        <option value="{{ $option }}" @selected(($filters['status'] ?? '') === $option)>{{ $t($option) }}</option>
                    @endforeach
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
            <div>
                <label for="filter_country" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('country') }}</label>
                <select id="filter_country" name="country" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                    <option value="">{{ $t('all') }}</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}" @selected(($filters['country'] ?? '') === $country)>{{ country_flag($country) }} {{ country_name($country) ?? $country }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_recordings" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('recordings_filter') }}</label>
                <select id="filter_recordings" name="recordings" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                    <option value="">{{ $t('all') }}</option>
                    @foreach(['single', 'multiple'] as $option)
                        <option value="{{ $option }}" @selected(($filters['recordings'] ?? '') === $option)>{{ $t($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filter_sort" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('sort') }}</label>
                <select id="filter_sort" name="sort" class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                    @foreach(['order', 'name', 'recordings', 'newest'] as $option)
                        <option value="{{ $option === 'order' ? '' : $option }}" @selected(($filters['sort'] ?? '') === ($option === 'order' ? '' : $option))>{{ $t('sort_'.$option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3 xl:col-span-6 flex flex-wrap justify-end gap-3">
                <a href="{{ route('admin.reciter-profiles.index') }}"
                   class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 border-2 focus:outline-none focus:ring-2 focus:ring-accent"
                   :class="darkMode ? 'border-gray-700 text-gray-400 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100'">{{ $t('reset') }}</a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-accent">
                    <i data-lucide="search" class="w-4 h-4" aria-hidden="true"></i>{{ $t('apply') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Toolbar: active chips + view toggle -->
    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $t('results', ['count' => $profiles->total()]) }}</span>
            @foreach($filters as $key => $value)
                <a href="{{ route('admin.reciter-profiles.index', \Illuminate\Support\Arr::except($filters, $key)) }}"
                   aria-label="{{ $t('remove_filter', ['label' => $chipLabels[$key]($value)]) }}"
                   class="inline-flex items-center gap-1 px-3 py-1 rounded-full border transition-colors focus:outline-none focus:ring-2 focus:ring-accent"
                   :class="darkMode ? 'border-gray-700 bg-gray-800 text-gray-200 hover:border-accent' : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-accent'">
                    {{ $chipLabels[$key]($value) }}
                    <i data-lucide="x" class="w-3.5 h-3.5" aria-hidden="true"></i>
                </a>
            @endforeach
            @if(count($filters) > 1)
                <a href="{{ route('admin.reciter-profiles.index') }}" class="text-accent font-semibold hover:underline">{{ $t('clear_all') }}</a>
            @endif
        </div>
        <div class="inline-flex rounded-xl border p-1 self-start" role="group" aria-label="{{ $t('view_mode') }}"
             :class="darkMode ? 'border-gray-700 bg-gray-900/50' : 'border-gray-200 bg-white'">
            @foreach(['grid' => 'layout-grid', 'table' => 'list'] as $mode => $icon)
                <button type="button" @click="setView('{{ $mode }}')" :aria-pressed="view === '{{ $mode }}'" aria-label="{{ $t('view_'.$mode) }}"
                        class="p-2 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-accent"
                        :class="view === '{{ $mode }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900')">
                    <i data-lucide="{{ $icon }}" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            @endforeach
        </div>
    </div>

    @if($profiles->isEmpty())
        <div class="text-center py-16 px-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="mx-auto mb-4 w-16 h-16 rounded-2xl bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center shadow-lg">
                <i data-lucide="mic-off" class="w-8 h-8 text-white" aria-hidden="true"></i>
            </div>
            <p class="text-lg font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ count($filters) ? $t('empty_filtered') : $t('empty_title') }}</p>
            <p class="text-sm mb-6" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">{{ $t('empty_hint') }}</p>
            @if(count($filters))
                <a href="{{ route('admin.reciter-profiles.index') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold border-2 transition-all hover:scale-105"
                   :class="darkMode ? 'border-gray-700 text-white' : 'border-gray-300 text-gray-900'">{{ $t('clear_all') }}</a>
            @else
                <a href="{{ route('admin.reciter-profiles.create') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    <i data-lucide="plus" class="w-5 h-5" aria-hidden="true"></i>{{ $t('create') }}
                </a>
            @endif
        </div>
    @else
        @php
            // One set of per-profile fragments, reused by both the grid and the table view.
            $prepared = $profiles->map(function ($profile) use ($locale) {
                $name = $profile->displayName($locale);

                return [
                    'model' => $profile,
                    'name' => $name,
                    'initials' => collect(preg_split('/\s+/u', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''),
                    'labels' => $profile->recordings->map(fn ($r) => $r->recordingLabel($locale))->unique()->values(),
                ];
            });
        @endphp

        <!-- Grid view -->
        <div x-show="view === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-5">
            @foreach($prepared as $item)
                @php($profile = $item['model'])
                <article class="group rounded-2xl border overflow-hidden flex flex-col transition-all hover:shadow-xl hover:-translate-y-0.5"
                         :class="darkMode ? 'bg-gray-900/50 border-gray-800 hover:border-gray-700' : 'bg-white border-gray-200 hover:border-gray-300'">
                    <div class="relative aspect-[4/3] bg-gradient-to-br from-[#0056b3] to-[#218838] overflow-hidden">
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-white/90" aria-hidden="true">
                            <span class="text-4xl font-bold">{{ $item['initials'] ?: '' }}</span>
                            @unless($item['initials'])<i data-lucide="mic" class="w-10 h-10"></i>@endunless
                        </div>
                        @if($profile->profile_image_url)
                            <img src="{{ $profile->profile_image_url }}" alt="{{ $item['name'] }}" loading="lazy" onerror="this.remove()"
                                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                        @endif
                        <div class="absolute top-3 inset-x-3 flex flex-wrap gap-2 justify-between">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold shadow"
                                  :class="rows[{{ $profile->id }}].active ? 'bg-green-600 text-white' : 'bg-gray-800/80 text-gray-100'">
                                <i data-lucide="circle-check" class="w-3.5 h-3.5" aria-hidden="true" x-show="rows[{{ $profile->id }}].active"></i>
                                <i data-lucide="circle-off" class="w-3.5 h-3.5" aria-hidden="true" x-show="!rows[{{ $profile->id }}].active"></i>
                                <span x-text="rows[{{ $profile->id }}].active ? @js($t('active')) : @js($t('inactive'))">{{ $profile->is_active ? $t('active') : $t('inactive') }}</span>
                            </span>
                            <span x-show="rows[{{ $profile->id }}].featured" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500 text-white shadow">
                                <i data-lucide="star" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $t('featured') }}
                            </span>
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col gap-3">
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold truncate">
                                <a href="{{ route('admin.reciter-profiles.edit', $profile->id) }}" class="hover:text-accent transition-colors focus:outline-none focus:ring-2 focus:ring-accent rounded"
                                   :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $item['name'] }}</a>
                            </h2>
                            <p class="mt-1 text-sm flex items-center gap-2" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                @if($profile->country)
                                    <span aria-hidden="true">{{ country_flag($profile->country) }}</span>{{ country_name($profile->country) ?? $profile->country }}
                                @else
                                    <i data-lucide="globe" class="w-4 h-4" aria-hidden="true"></i>{{ $t('no_country') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                  :class="darkMode ? 'bg-blue-900/30 text-blue-300' : 'bg-blue-50 text-blue-700'">
                                <i data-lucide="disc-3" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $t('recordings_count', ['count' => $profile->recordings_count]) }}
                            </span>
                            @foreach($item['labels']->take(3) as $label)
                                <span class="px-2 py-0.5 rounded-full text-xs" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'">{{ $label }}</span>
                            @endforeach
                            @if($item['labels']->count() > 3)
                                <span class="px-2 py-0.5 rounded-full text-xs" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'">+{{ $item['labels']->count() - 3 }}</span>
                            @endif
                        </div>
                        <div class="mt-auto pt-3 border-t flex items-center justify-between gap-2" :class="darkMode ? 'border-gray-800' : 'border-gray-100'">
                            @include('admin.reciters.partials.profile-actions', ['profile' => $profile, 'name' => $item['name']])
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <!-- Table view -->
        <div x-show="view === 'table'" x-cloak class="rounded-2xl border overflow-hidden" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead :class="darkMode ? 'bg-gray-800/60 text-gray-300' : 'bg-gray-50 text-gray-600'">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ $t('col_reciter') }}</th>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ $t('country') }}</th>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ __('quran.recordings.recordings') }}</th>
                            <th scope="col" class="px-4 py-3 text-start font-semibold">{{ $t('status') }}</th>
                            <th scope="col" class="px-4 py-3 text-end font-semibold">{{ $t('col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
                        @foreach($prepared as $item)
                            @php($profile = $item['model'])
                            <tr class="transition-colors" :class="darkMode ? 'hover:bg-gray-800/40' : 'hover:bg-gray-50'">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-[14rem]">
                                        <div class="relative w-11 h-11 shrink-0 rounded-xl overflow-hidden bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center text-white text-sm font-bold">
                                            <span aria-hidden="true">{{ $item['initials'] }}</span>
                                            @if($profile->profile_image_url)
                                                <img src="{{ $profile->profile_image_url }}" alt="{{ $item['name'] }}" loading="lazy" onerror="this.remove()" class="absolute inset-0 w-full h-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.reciter-profiles.edit', $profile->id) }}" class="font-bold hover:text-accent transition-colors" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $item['name'] }}</a>
                                            <p class="text-xs" dir="ltr" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ $profile->slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    @if($profile->country)<span aria-hidden="true">{{ country_flag($profile->country) }}</span> {{ country_name($profile->country) ?? $profile->country }}@else — @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5 max-w-xs">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="darkMode ? 'bg-blue-900/30 text-blue-300' : 'bg-blue-50 text-blue-700'">{{ $profile->recordings_count }}</span>
                                        @foreach($item['labels']->take(2) as $label)
                                            <span class="px-2 py-0.5 rounded-full text-xs" :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-600'">{{ $label }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold" :class="rows[{{ $profile->id }}].active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'"
                                              x-text="rows[{{ $profile->id }}].active ? @js($t('active')) : @js($t('inactive'))">{{ $profile->is_active ? $t('active') : $t('inactive') }}</span>
                                        <span x-show="rows[{{ $profile->id }}].featured" class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $t('featured') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        @include('admin.reciters.partials.profile-actions', ['profile' => $profile, 'name' => $item['name']])
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($profiles->hasPages())
            <div class="mt-8">{{ $profiles->links() }}</div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
function reciterProfilesPage(rows, hasFilters) {
    const storageKey = 'admin.reciterProfiles.view';
    let saved = 'grid';
    try { saved = localStorage.getItem(storageKey) || 'grid'; } catch (e) {}

    return {
        rows,
        view: saved === 'table' ? 'table' : 'grid',
        filtersOpen: hasFilters,
        setView(mode) {
            this.view = mode;
            try { localStorage.setItem(storageKey, mode); } catch (e) {}
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
        async toggle(id, field, url) {
            const row = this.rows[id];
            row[field] = !row[field]; // optimistic
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
                const body = await response.json();
                if (!response.ok || !body.success) throw new Error(body.message);
                row[field] = body.data['is_' + field];
                window.showToast && window.showToast(body.message, 'success');
            } catch (error) {
                row[field] = !row[field];
                window.showToast && window.showToast(error.message || @js($t('toggle_failed')), 'error');
            }
            this.$nextTick(() => window.lucide && lucide.createIcons());
        },
    };
}
</script>
@endpush
