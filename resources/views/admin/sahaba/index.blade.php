@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="users" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.manage_sahaba') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('admin.sahaba_page_subtitle') }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.sahaba.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.add_sahabi') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="sahabaAdminTable(@json($sahaba->pluck('id')))" x-init="init()">

    <!-- Compact Stats Row -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
        <x-admin.stats-card
            icon="users"
            :title="__('sahaba.stat_total')"
            :value="$stats['total'] ?? 0"
            :gradient="true"
            :compact="true"
        />
        <x-admin.stats-card
            icon="user"
            :title="__('sahaba.stat_male')"
            :value="$stats['male'] ?? 0"
            :compact="true"
        />
        <x-admin.stats-card
            icon="user"
            :title="__('sahaba.stat_female')"
            :value="$stats['female'] ?? 0"
            :compact="true"
        />
    </div>

    <!-- Toolbar: search, columns, advanced filters -->
    <form method="GET" action="{{ route('admin.sahaba.index') }}" @submit="submitting = true">
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
                    <a href="{{ route('admin.sahaba.index') }}"
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
                <!-- Gender -->
                <div class="space-y-2">
                    <label class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.gender') }}
                    </label>
                    <select name="gender"
                            class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.all') }}</option>
                        <option value="male" {{ $filters->gender === 'male' ? 'selected' : '' }}>{{ __('admin.male') }}</option>
                        <option value="female" {{ $filters->gender === 'female' ? 'selected' : '' }}>{{ __('admin.female') }}</option>
                    </select>
                </div>

                <!-- Tribe -->
                <div class="space-y-2">
                    <label class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('sahaba.tribe') }}
                    </label>
                    <input type="text" name="tribe" value="{{ $filters->tribe }}"
                           class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>


                <!-- Toggles -->
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
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                            {{ __('admin.verified') }}
                        </span>
                        <input type="checkbox" name="is_verified" value="1" {{ $filters->isVerified ? 'checked' : '' }}
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
        <button type="button" @click="submitBulk('{{ route('admin.sahaba.bulk-status') }}', {status: 'published'})"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all hover:scale-105 flex items-center gap-1.5"
                :class="darkMode ? 'bg-green-500/20 text-green-400 hover:bg-green-500/30' : 'bg-green-100 text-green-700 hover:bg-green-200'">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            {{ __('admin.publish') }}
        </button>
        <button type="button" @click="submitBulk('{{ route('admin.sahaba.bulk-status') }}', {status: 'unpublished'})"
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
                            $laqab = $sahabi->getTranslation('laqab', $locale) ?: $sahabi->getTranslation('laqab', 'ar');
                            // Publication is a plain boolean on `muslims` now; the old
                            // per-locale translation `is_ready` flags are gone.
                            $isPublished = (bool) $sahabi->is_published;
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
                                            @if($sahabi->is_verified)
                                                <i data-lucide="badge-check" class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" title="{{ __('admin.verified') }}"></i>
                                            @endif
                                            @if($sahabi->is_featured)
                                                <i data-lucide="star" class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" title="{{ __('admin.featured') }}"></i>
                                            @endif
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

                            <!-- Laqab -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('laqab')">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $laqab ?: '—' }}</span>
                            </td>

                            <!-- Gender -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('gender')">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                                      :class="'{{ $sahabi->gender === 'male' ? 'bg-blue-500' : 'bg-pink-500' }}'">
                                    {{ $sahabi->gender === 'male' ? __('admin.male') : __('admin.female') }}
                                </span>
                            </td>

                            <!-- Tribe -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('tribe')">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $sahabi->tribe ?: '—' }}</span>
                            </td>


                            <!-- Hadith count -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('hadith_count')">
                                <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ number_format($sahabi->reported_hadith_count) }}
                                </span>
                            </td>

                            <!-- Verified -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('verified')">
                                @if($sahabi->is_verified)
                                    <i data-lucide="badge-check" class="w-4 h-4 text-blue-500"></i>
                                @else
                                    <span :class="darkMode ? 'text-gray-600' : 'text-gray-400'">—</span>
                                @endif
                            </td>

                            <!-- Status (published/unpublished, driven by is_ready across all locales) -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('status')">
                                @if($isPublished)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="darkMode ? 'bg-green-900/30 text-green-400' : 'bg-green-100 text-green-700'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ __('admin.published') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                          :class="darkMode ? 'bg-gray-700 text-gray-400' : 'bg-gray-200 text-gray-600'">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ __('admin.unpublished') }}
                                    </span>
                                @endif
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
                            <td colspan="11" class="px-6 py-16">
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4"
                                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                                        <i data-lucide="users" class="w-8 h-8" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                    </div>
                                    <p class="text-base font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ __('admin.no_sahaba_found') }}
                                    </p>
                                    <a href="{{ route('admin.sahaba.create') }}"
                                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                        {{ __('admin.add_sahabi') }}
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
        const STORAGE_KEY = 'sahaba_admin_visible_columns';
        const allColumns = [
            { key: 'kunya', label: '{{ __('sahaba.kunya') }}' },
            { key: 'laqab', label: '{{ __('sahaba.laqab') }}' },
            { key: 'gender', label: '{{ __('admin.gender') }}' },
            { key: 'tribe', label: '{{ __('sahaba.tribe') }}' },
            { key: 'hadith_count', label: '{{ __('sahaba.hadith_narrated') }}' },
            { key: 'verified', label: '{{ __('admin.verified') }}' },
            { key: 'status', label: '{{ __('admin.status') }}' },
        ];
        const defaultVisible = ['kunya', 'laqab', 'gender', 'categories', 'status'];

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
