@extends('layouts.admin')

@php
    // One list, several role scopes. The heading has to say which scope is in
    // effect — otherwise Companions, Narrators and Scholars are three sidebar
    // items that all land on a page reading "Manage Companions".
    $roleIcons = [
        'sahabi' => 'star',
        'narrator' => 'quote',
        'scholar' => 'graduation-cap',
        'grader' => 'gavel',
        'compiler' => 'library',
    ];
    $activeRole = $activeRole ?? null;
    $scopeQuery = $activeRole ? ['role' => $activeRole->slug] : [];
    $pageIcon = $activeRole ? ($roleIcons[$activeRole->slug] ?? 'users') : 'users';

    // "Add a companion" is wrong on the Narrators view — name the active role.
    $addLabel = $activeRole
        ? __('sahaba.add_in_role', ['role' => $activeRole->name()])
        : __('sahaba.add_person');
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="{{ $pageIcon }}" class="w-7 h-7 text-white"></i>
            </div>
            {{ $activeRole
                ? __('sahaba.admin_role_title', ['role' => $activeRole->name()])
                : __('sahaba.admin_all_title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ $activeRole
                ? __('sahaba.admin_role_subtitle', ['role' => $activeRole->name()])
                : __('sahaba.admin_all_subtitle') }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.sahaba.roles') }}"
           class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
            <i data-lucide="shield-check" class="w-5 h-5"></i>
            <span>{{ __('admin.nav.islamic_roles') }}</span>
        </a>
        <a href="{{ route('admin.sahaba.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ $addLabel }}</span>
        </a>
    </div>
</div>

<!-- Role scope tabs: the same destinations as the sidebar, kept in view so the
     active scope is always visible and switching it is one click. -->
@php
    $scopes = collect([['slug' => null, 'label' => __('admin.nav.muslims_all'), 'icon' => 'users']])
        ->concat($roles->map(fn ($role) => [
            'slug' => $role->slug,
            'label' => $role->name(),
            'icon' => $roleIcons[$role->slug] ?? 'user-check',
        ]));
@endphp
<nav class="mt-5 flex flex-wrap gap-2" aria-label="{{ __('sahaba.role_scope') }}">
    @foreach($scopes as $scope)
        @php $isCurrent = $activeRole?->slug === $scope['slug']; @endphp
        <a href="{{ route('admin.sahaba.index', $scope['slug'] ? ['role' => $scope['slug']] : []) }}"
           @if($isCurrent) aria-current="page" @endif
           class="px-4 py-2 rounded-xl text-sm font-semibold transition-all border-2 flex items-center gap-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent
                  {{ $isCurrent ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white border-transparent shadow-lg' : '' }}"
           @unless($isCurrent)
               :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100'"
           @endunless>
            <i data-lucide="{{ $scope['icon'] }}" class="w-4 h-4"></i>
            {{ $scope['label'] }}
        </a>
    @endforeach
</nav>
@endsection

@section('content')

    {{-- Review queue: rows that name a place in prose but carry no canonical
         reference. The resolver leaves ambiguous and place-less text alone by
         design, so this is the editor's worklist, not an error count. --}}
    @if(($unmappedCount ?? 0) > 0)
        <a href="{{ request()->fullUrlWithQuery(['unmapped' => 1]) }}"
           class="mb-6 flex items-center gap-2 px-4 py-3 rounded-xl border text-sm transition-colors
                  bg-amber-50 border-amber-200 text-amber-900 hover:bg-amber-100
                  dark:bg-amber-900/25 dark:border-amber-800 dark:text-amber-200 dark:hover:bg-amber-900/40">
            <i data-lucide="map-pin-off" class="w-4 h-4 flex-shrink-0"></i>
            <span>{{ __('locations.messages.unmapped_count', ['count' => $unmappedCount]) }}</span>
            <i data-lucide="arrow-left" class="w-4 h-4 ms-auto rtl:rotate-180"></i>
        </a>
    @endif
<div x-data="sahabaAdminTable(@json($sahaba->pluck('id')))" x-init="init()">

    {{-- Compact stats row.

         Publication and featured counts sit here because nothing else on the
         screen answers "why is the public carousel empty" — that is a count of
         pivot rows, invisible in a list of people. Each is a link into the list
         filtered to exactly the rows it counts, so the number is a way in
         rather than a fact to act on somewhere else. --}}
    @php
        // Every stat link keeps the role scope it was read under, or the number
        // and the list it opens would disagree.
        $scopedFilter = fn (array $extra): array => array_filter(
            ['role' => $activeRole?->slug] + $extra,
            fn ($value) => $value !== null && $value !== '',
        );
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
        {{-- The count is role-scoped, so the label has to name the role — a
             narrators page counting "companions" reads as a bug. --}}
        <x-admin.stats-card
            :icon="$pageIcon"
            :title="$activeRole ? $activeRole->name() : __('sahaba.title')"
            :value="$stats['total'] ?? 0"
            :gradient="true"
            :compact="true"
        />
        <x-admin.stats-card
            icon="globe"
            :title="__('sahaba.stat_published')"
            :value="$stats['published'] ?? 0"
            :href="route('admin.sahaba.index', $scopedFilter(['status' => 'published']))"
            :compact="true"
        />
        <x-admin.stats-card
            icon="file-pen-line"
            :title="__('sahaba.stat_draft')"
            :value="$stats['draft'] ?? 0"
            :href="route('admin.sahaba.index', $scopedFilter(['status' => 'draft']))"
            :compact="true"
        />
        <x-admin.stats-card
            icon="star"
            :title="__('sahaba.featured')"
            :value="$stats['featured'] ?? 0"
            :href="route('admin.sahaba.index', $scopedFilter(['is_featured' => '1']))"
            :compact="true"
        />
        <x-admin.stats-card
            icon="user"
            :title="__('sahaba.stat_male')"
            :value="$stats['male'] ?? 0"
            :href="route('admin.sahaba.index', $scopedFilter(['gender' => 'male']))"
            :compact="true"
        />
        <x-admin.stats-card
            icon="user"
            :title="__('sahaba.stat_female')"
            :value="$stats['female'] ?? 0"
            :href="route('admin.sahaba.index', $scopedFilter(['gender' => 'female']))"
            :compact="true"
        />
    </div>

    <!-- Toolbar: search, columns, advanced filters -->
    <form method="GET" action="{{ route('admin.sahaba.index') }}" @submit="submitting = true">
        {{-- The role scope rides along on every submit through the `role` select
             in the advanced panel below — which is inside this form, so it posts
             whether the panel is open or not. Without that, a search on the
             Narrators view would silently widen to every figure. --}}
        <div class="flex flex-col lg:flex-row gap-3 items-start lg:items-center justify-between mb-4">

            <!-- Search (submits on Enter, no live/debounced search) -->
            <div class="relative w-full lg:w-96">
                <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                <input type="text"
                       name="search"
                       value="{{ $filters->search }}"
                       placeholder="{{ __('admin.search_sahaba_placeholder') }}"
                       class="w-full ps-9 pe-3 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Rows per page -->
                <select name="per_page"
                        onchange="this.form.submit()"
                        class="px-3 py-2.5 rounded-xl border text-base sm:text-sm font-medium transition-all focus:ring-2 focus:ring-accent/50"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @foreach([10, 15, 25, 50, 100] as $option)
                        <option value="{{ $option }}" {{ $sahaba->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>

                <!-- Column Visibility -->
                <div class="relative">
                    <button type="button" @click="columnsOpen = !columnsOpen"
                            class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
                            :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
                        <i data-lucide="columns-3" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">{{ __('admin.columns') }}</span>
                    </button>

                    <div x-show="columnsOpen"
                         @click.away="columnsOpen = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute end-0 mt-2 w-56 rounded-xl shadow-xl z-30 overflow-hidden border p-2"
                         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'"
                         style="display:none;" x-cloak>
                        <p class="px-2 py-1.5 text-xs font-bold uppercase tracking-wider"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ __('admin.column_visibility') }}
                        </p>
                        <template x-for="col in toggleableColumns" :key="col.key">
                            <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg cursor-pointer transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-100'">
                                <input type="checkbox" :checked="visibleColumns.includes(col.key)" @change="toggleColumn(col.key)"
                                       class="form-checkbox rounded text-accent focus:ring-accent">
                                <span class="text-sm" :class="darkMode ? 'text-gray-200' : 'text-gray-700'" x-text="col.label"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Advanced Filters Toggle -->
                <button type="button" @click="filtersOpen = !filtersOpen"
                        class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2 relative"
                        :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
                    <i data-lucide="filter" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">{{ __('admin.advanced_filters') }}</span>
                    @if($filters->activeCount() > 0)
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838]">
                            {{ $filters->activeCount() }}
                        </span>
                    @endif
                </button>

                @if($filters->hasAny())
                    {{-- Resets the filters, not the role scope. --}}
                    <a href="{{ route('admin.sahaba.index', $scopeQuery) }}"
                       class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 border-2"
                       :class="darkMode ? 'border-gray-700 text-gray-400 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100'">
                        {{ __('admin.reset_filters') }}
                    </a>
                @endif
            </div>
        </div>

        <!-- Advanced Filters Panel -->
        <div x-show="filtersOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-full"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-full"
             class="fixed inset-y-0 end-0 w-full sm:w-96 z-40 shadow-2xl overflow-y-auto custom-scrollbar"
             :class="darkMode ? 'bg-gray-900/95 border-s border-gray-800' : 'bg-white/95 border-s border-gray-200'"
             style="display:none;" x-cloak>

            <div class="sticky top-0 z-10 glass px-6 py-4 border-b flex items-center justify-between"
                 :class="darkMode ? 'bg-gray-900/95 border-gray-800' : 'bg-white/95 border-gray-200'">
                <h3 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('admin.advanced_filters') }}
                </h3>
                <button type="button" @click="filtersOpen = false"
                        class="p-2 rounded-lg transition-colors hover:scale-110"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="p-6 space-y-6">
                {{-- Role scope. Duplicates the tabs above on purpose: once the
                     panel is open it is the only visible control surface, and a
                     filter you cannot see the value of is worse than none. --}}
                <div class="space-y-2">
                    <label for="filter-role" class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('sahaba.role_scope') }}
                    </label>
                    <select name="role" id="filter-role"
                            class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('sahaba.all_roles') }}</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->slug }}" {{ $activeRole?->slug === $role->slug ? 'selected' : '' }}>
                                {{ $role->name() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Gender -->
                <div class="space-y-2">
                    <label for="filter-gender" class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.gender') }}
                    </label>
                    <select name="gender" id="filter-gender"
                            class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.all') }}</option>
                        <option value="male" {{ $filters->gender === 'male' ? 'selected' : '' }}>{{ __('admin.male') }}</option>
                        <option value="female" {{ $filters->gender === 'female' ? 'selected' : '' }}>{{ __('admin.female') }}</option>
                    </select>
                </div>

                {{-- Publication status. A tri-state select, not a checkbox:
                     "drafts only" is the query an editor actually needs and a
                     checkbox can only ever express "published only". --}}
                <div class="space-y-2">
                    <label for="filter-status" class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('sahaba.filter_status') }}
                    </label>
                    <select name="status" id="filter-status"
                            class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('sahaba.filter_status_all') }}</option>
                        <option value="published" {{ $filters->statusValue() === 'published' ? 'selected' : '' }}>
                            {{ __('sahaba.stat_published') }}
                        </option>
                        <option value="draft" {{ $filters->statusValue() === 'draft' ? 'selected' : '' }}>
                            {{ __('sahaba.stat_draft') }}
                        </option>
                    </select>
                </div>

                {{-- `tribe` and `is_verified` were retired with the Sahabi table
                     and have no source column, so rendering them would be a
                     control that quietly does nothing. --}}
                <div class="space-y-2">
                    <label class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.special_filters') }}
                    </label>
                    <label class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all"
                           :class="darkMode ? 'bg-gray-800 hover:bg-gray-750' : 'bg-gray-100 hover:bg-gray-200'">
                        <span class="text-sm font-medium flex items-center gap-2" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            <i data-lucide="star" class="w-4 h-4"></i>
                            {{ __('admin.featured_only') }}
                        </span>
                        <input type="checkbox" name="is_featured" value="1" {{ $filters->isFeatured ? 'checked' : '' }}
                               class="form-checkbox rounded text-accent focus:ring-accent">
                    </label>

                    <label class="flex items-center justify-between p-3 rounded-lg cursor-pointer transition-all"
                           :class="darkMode ? 'bg-gray-800 hover:bg-gray-750' : 'bg-gray-100 hover:bg-gray-200'">
                        <span class="text-sm font-medium flex items-center gap-2" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            <i data-lucide="eye-off" class="w-4 h-4"></i>
                            {{ __('sahaba.filter_hidden_tree') }}
                        </span>
                        <input type="checkbox" name="hidden_from_tree" value="1" {{ $filters->hiddenFromTree ? 'checked' : '' }}
                               class="form-checkbox rounded text-accent focus:ring-accent">
                    </label>
                </div>
            </div>

            <div class="sticky bottom-0 glass px-6 py-4 border-t flex gap-3"
                 :class="darkMode ? 'bg-gray-900/95 border-gray-800' : 'bg-white/95 border-gray-200'">
                <a href="{{ route('admin.sahaba.index') }}"
                   class="flex-1 text-center px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105"
                   :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                    {{ __('admin.reset_all') }}
                </a>
                <button type="submit" :disabled="submitting"
                        class="flex-1 px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 disabled:opacity-60 disabled:cursor-not-allowed
                               bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                    <i x-show="submitting" data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
                    <span>{{ __('admin.apply_filters') }}</span>
                </button>
            </div>
        </div>

        <!-- Backdrop for the filters panel -->
        <div x-show="filtersOpen" @click="filtersOpen = false"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30" style="display:none;" x-cloak></div>
    </form>

    <!-- Bulk Actions Toolbar -->
    <div x-show="selectedIds.length > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="flex flex-wrap items-center gap-3 px-4 py-3 rounded-xl mb-4"
         :class="darkMode ? 'bg-gray-800/70 border border-gray-700' : 'bg-white border border-gray-300 shadow-sm'"
         style="display:none;" x-cloak>
        <span class="text-sm font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
            <span x-text="selectedIds.length"></span> {{ __('admin.selected') }}
        </span>

        <button type="button" @click="submitBulk('{{ route('admin.sahaba.bulk-featured') }}', {is_featured: '1'})"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105 flex items-center gap-1.5"
                :class="darkMode ? 'bg-amber-500/20 text-amber-400 hover:bg-amber-500/30' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'">
            <i data-lucide="star" class="w-4 h-4"></i>
            {{ __('admin.mark_featured') }}
        </button>
        <button type="button" @click="submitBulk('{{ route('admin.sahaba.bulk-featured') }}', {is_featured: '0'})"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105 flex items-center gap-1.5"
                :class="darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
            <i data-lucide="star-off" class="w-4 h-4"></i>
            {{ __('admin.unmark_featured') }}
        </button>
        <button type="button" @click="submitBulk('{{ route('admin.sahaba.bulk-status') }}', {is_published: '1'})"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105 flex items-center gap-1.5"
                :class="darkMode ? 'bg-green-500/20 text-green-400 hover:bg-green-500/30' : 'bg-green-100 text-green-700 hover:bg-green-200'">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            {{ __('admin.publish') }}
        </button>
        <button type="button" @click="submitBulk('{{ route('admin.sahaba.bulk-status') }}', {is_published: '0'})"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105 flex items-center gap-1.5"
                :class="darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'">
            <i data-lucide="circle-slash" class="w-4 h-4"></i>
            {{ __('admin.unpublish') }}
        </button>

        <button type="button" @click="selectedIds = []"
                class="ms-auto px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105"
                :class="darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900'">
            {{ __('admin.clear') }}
        </button>
    </div>

    <!-- Data Table -->
    <div class="rounded-2xl shadow-xl border relative"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
         style="overflow: clip;">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-sm">
                <thead class="glass border-b" :class="darkMode ? 'bg-gray-800/80 border-gray-700' : 'bg-gray-100/80 border-gray-300'">
                    <tr>
                        <th class="px-4 py-3" style="width: 44px;">
                            <input type="checkbox" :checked="allOnPageSelected" @change="toggleSelectAllOnPage()"
                                   class="w-4 h-4 rounded cursor-pointer text-accent focus:ring-accent"
                                   :class="darkMode ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300'">
                        </th>
                        <th class="px-4 py-3 text-start">
                            <span class="font-bold text-xs uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.name') }}
                            </span>
                        </th>
                        <template x-for="col in toggleableColumns" :key="col.key">
                            <th class="px-4 py-3 text-start" x-show="visibleColumns.includes(col.key)">
                                <span class="font-bold text-xs uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="col.label"></span>
                            </th>
                        </template>
                        <th class="px-4 py-3 text-center" style="width: 100px;">
                            <span class="font-bold text-xs uppercase tracking-wider" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                {{ __('admin.actions') }}
                            </span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sahaba ?? [] as $sahabi)
                        @php
                            $locale = app()->getLocale();
                            $secondaryLocale = $locale === 'ar' ? 'en' : 'ar';
                            $primaryName = $sahabi->getTranslation('name', $locale) ?: $sahabi->getTranslation('name', 'ar');
                            $secondaryName = $sahabi->getTranslation('name', $secondaryLocale);
                            $kunya = $sahabi->getTranslation('kunya', $locale) ?: $sahabi->getTranslation('kunya', 'ar');
                            // Publication is a plain boolean on `muslims` now; the old
                            // per-locale translation `is_ready` flags are gone.
                            $isPublished = (bool) $sahabi->is_published;
                            // Featured is a pivot column, so it is true when any role
                            // the person holds carries it — the reading the bulk action
                            // and the public carousel both use. Roles are eager-loaded,
                            // so this costs no query per row.
                            $isFeatured = $sahabi->roles->contains(fn ($role) => (bool) $role->pivot->is_featured);
                        @endphp
                        <tr class="border-b transition-colors duration-150"
                            :class="darkMode ? 'border-gray-800 hover:bg-gray-800/60' : 'border-gray-200 hover:bg-gray-50'">

                            <!-- Row select -->
                            <td class="px-4 py-2.5">
                                <input type="checkbox" :checked="selectedIds.includes({{ $sahabi->id }})" @change="toggleRow({{ $sahabi->id }})"
                                       class="w-4 h-4 rounded cursor-pointer text-accent focus:ring-accent"
                                       :class="darkMode ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300'">
                            </td>

                            <!-- Photo + Name (always visible) -->
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    @if($sahabi->getFirstMediaUrl('profile_image'))
                                        <img src="{{ $sahabi->getFirstMediaUrl('profile_image', 'avatar') ?: $sahabi->getFirstMediaUrl('profile_image') }}"
                                             alt="{{ $primaryName }}"
                                             class="w-9 h-9 rounded-full object-cover shadow-sm flex-shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                                            <i data-lucide="user" class="w-4 h-4 text-white/80"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('admin.sahaba.show', $sahabi->id) }}"
                                               class="font-semibold text-sm truncate hover:text-accent transition-colors"
                                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                                                {{ $primaryName ?: '—' }}
                                            </a>
                                            {{-- `is_verified` / `is_featured` are not columns on `muslims`:
                                                 featured is per-role and shown as a star in the Roles column. --}}
                                        </div>
                                        @if($secondaryName)
                                            <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $secondaryName }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Kunya -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('kunya')">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $kunya ?: '—' }}</span>
                            </td>

                            {{-- `gender` is nullable, so an unset one must read as
                                 unknown rather than defaulting to female. --}}
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('gender')">
                                @if($sahabi->gender !== null)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white
                                                 {{ $sahabi->gender->value === 'male' ? 'bg-blue-500' : 'bg-pink-500' }}">
                                        {{ $sahabi->gender->label(app()->getLocale()) }}
                                    </span>
                                @else
                                    <span :class="darkMode ? 'text-gray-600' : 'text-gray-400'">—</span>
                                @endif
                            </td>

                            {{-- Islamic roles. Eager-loaded by the repository, so
                                 no query per row. This is the column that makes
                                 the unified identity legible: one person, every
                                 capacity they held, on one line. --}}
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('roles')">
                                @if($sahabi->roles->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($sahabi->roles as $personRole)
                                            <a href="{{ route('admin.sahaba.index', ['role' => $personRole->slug]) }}"
                                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold transition-colors"
                                               :class="darkMode ? 'bg-accent/15 text-accent hover:bg-accent/25' : 'bg-[#0056b3]/10 text-[#0056b3] hover:bg-[#0056b3]/20'">
                                                @if($personRole->pivot->is_featured)
                                                    <i data-lucide="star" class="w-3 h-3"></i>
                                                @endif
                                                {{ $personRole->name() }}
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs" :class="darkMode ? 'text-gray-600' : 'text-gray-400'">
                                        {{ __('sahaba.roles_page.unassigned') }}
                                    </span>
                                @endif
                            </td>

                            <!-- Lifespan (signed Hijri years; negative is pre-Hijra) -->
                            <td class="px-4 py-2.5 whitespace-nowrap" x-show="visibleColumns.includes('lifespan')">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    @if($sahabi->birth_year_hijri !== null || $sahabi->death_year_hijri !== null)
                                        {{ $sahabi->birth_year_hijri ?? '—' }} – {{ $sahabi->death_year_hijri ?? '—' }}
                                        <span class="text-xs opacity-70">{{ __('sahaba.hijri') }}</span>
                                    @else
                                        —
                                    @endif
                                </span>
                            </td>

            {{-- Status, as controls rather than badges.

                 Both flags are one press from the list, which is where an
                 editor notices they are wrong. Each is its own PATCH form —
                 legal here because the toolbar's GET form closes well above the
                 table, so these are not nested inside it. --}}
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('status')">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <x-admin.flag-toggle
                                        :action="route('admin.sahaba.toggle', [$sahabi, 'published'])"
                                        :on="$isPublished"
                                        :on-label="__('admin.published')"
                                        :off-label="__('sahaba.draft')"
                                        on-icon="globe"
                                        off-icon="circle-slash"
                                        tone="emerald"
                                        :title="__('sahaba.published_hint')" />

                                    <x-admin.flag-toggle
                                        :action="route('admin.sahaba.toggle', [$sahabi, 'featured'])"
                                        :on="$isFeatured"
                                        :on-label="__('sahaba.featured')"
                                        :off-label="__('sahaba.not_featured')"
                                        on-icon="star"
                                        off-icon="star-off"
                                        tone="amber"
                                        :title="__('sahaba.featured_all_roles_hint')" />
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('admin.sahaba.show', $sahabi->id) }}"
                                       class="p-1.5 rounded-lg transition-colors"
                                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                                       title="{{ __('admin.view') }}">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('admin.sahaba.edit', $sahabi->id) }}"
                                       class="p-1.5 rounded-lg transition-colors"
                                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                                       title="{{ __('admin.edit') }}">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    <form action="{{ route('admin.sahaba.destroy', $sahabi->id) }}" method="POST" class="inline"
                                          @submit.prevent="$dispatch('show-confirm', { title: '{{ __('admin.confirm_delete') }}', message: '{{ __('admin.delete_warning') }}', type: 'danger', onConfirm: () => $el.submit() })">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 rounded-lg transition-colors"
                                                :class="darkMode ? 'hover:bg-red-900/40 text-gray-400 hover:text-red-400' : 'hover:bg-red-50 text-gray-500 hover:text-red-600'"
                                                title="{{ __('admin.delete') }}">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16">
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4"
                                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                                        <i data-lucide="users" class="w-8 h-8" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                    </div>
                                    <p class="text-base font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ $activeRole && ! $filters->hasAny()
                                            ? __('sahaba.no_people_in_role')
                                            : __('admin.no_sahaba_found') }}
                                    </p>
                                    <a href="{{ route('admin.sahaba.create') }}"
                                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                        {{ $addLabel }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if(isset($sahaba) && $sahaba->hasPages())
        <div class="border-t px-6 py-4" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
            {{ $sahaba->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    function sahabaAdminTable(pageIds) {
        // _v2: the retired laqab / tribe / hadith-count / verified columns were
        // replaced by roles and lifespan, so stored v1 preferences must not
        // carry over — they would hide every new column.
        const STORAGE_KEY = 'sahaba_admin_visible_columns_v2';
        const allColumns = [
            { key: 'kunya', label: '{{ __('sahaba.kunya') }}' },
            { key: 'gender', label: '{{ __('admin.gender') }}' },
            { key: 'roles', label: '{{ __('sahaba.roles') }}' },
            { key: 'lifespan', label: '{{ __('sahaba.death_year') }}' },
            { key: 'status', label: '{{ __('admin.status') }}' },
        ];
        const defaultVisible = ['kunya', 'gender', 'roles', 'status'];

        return {
            filtersOpen: {{ $filters->hasAdvanced() ? 'true' : 'false' }},
            columnsOpen: false,
            submitting: false,
            toggleableColumns: allColumns,
            visibleColumns: [],
            pageIds: pageIds || [],
            selectedIds: [],

            init() {
                let stored = null;
                try {
                    stored = JSON.parse(localStorage.getItem(STORAGE_KEY));
                } catch (e) {
                    stored = null;
                }
                this.visibleColumns = Array.isArray(stored) ? stored : [...defaultVisible];
                this.$watch('visibleColumns', (value) => {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(value));
                    this.$nextTick(() => lucide.createIcons());
                });
                this.$nextTick(() => lucide.createIcons());
            },

            toggleColumn(key) {
                const idx = this.visibleColumns.indexOf(key);
                if (idx > -1) {
                    this.visibleColumns.splice(idx, 1);
                } else {
                    this.visibleColumns.push(key);
                }
            },

            get allOnPageSelected() {
                return this.pageIds.length > 0 && this.pageIds.every((id) => this.selectedIds.includes(id));
            },

            toggleRow(id) {
                const idx = this.selectedIds.indexOf(id);
                if (idx > -1) {
                    this.selectedIds.splice(idx, 1);
                } else {
                    this.selectedIds.push(id);
                }
            },

            toggleSelectAllOnPage() {
                this.selectedIds = this.allOnPageSelected ? [] : [...this.pageIds];
            },

            submitBulk(url, extraFields) {
                if (this.selectedIds.length === 0) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                const csrf = document.querySelector('meta[name=csrf-token]');
                form.appendChild(Object.assign(document.createElement('input'), {
                    type: 'hidden', name: '_token', value: csrf ? csrf.content : '',
                }));

                this.selectedIds.forEach((id) => {
                    form.appendChild(Object.assign(document.createElement('input'), {
                        type: 'hidden', name: 'ids[]', value: id,
                    }));
                });

                Object.entries(extraFields || {}).forEach(([name, value]) => {
                    form.appendChild(Object.assign(document.createElement('input'), {
                        type: 'hidden', name, value,
                    }));
                });

                document.body.appendChild(form);
                form.submit();
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
