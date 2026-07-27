@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="git-branch" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('admin.manage_genealogy') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('admin.genealogy_page_subtitle') }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.genealogy.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>{{ __('admin.add_person') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="genealogyAdminTable()" x-init="init()">

    <!-- Compact Stats Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
        <x-admin.stats-card
            icon="git-branch"
            :title="__('admin.total')"
            :value="$stats['total'] ?? 0"
            :gradient="true"
            :compact="true"
        />
        <x-admin.stats-card
            icon="link"
            :title="__('admin.relationships')"
            :value="$stats['relationships'] ?? 0"
            :compact="true"
        />
    </div>

    <!-- Toolbar: search, columns, advanced filters -->
    <form method="GET" action="{{ route('admin.genealogy.index') }}" @submit="submitting = true">
        <div class="flex flex-col lg:flex-row gap-3 items-start lg:items-center justify-between mb-4">

            <!-- Search (submits on Enter, no live/debounced search) -->
            <div class="relative w-full lg:w-96">
                <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
                <input type="text"
                       name="search"
                       value="{{ $filters->search }}"
                       placeholder="{{ __('admin.search_genealogy_placeholder') }}"
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
                        <option value="{{ $option }}" {{ $people->perPage() == $option ? 'selected' : '' }}>{{ $option }}</option>
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
                    <a href="{{ route('admin.genealogy.index') }}"
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

                <!-- Person Type -->
                <div class="space-y-2">
                    <label class="text-sm font-bold block" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.type') }}
                    </label>
                    <select name="person_type"
                            class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('admin.all') }}</option>
                        @foreach(\App\Domain\Genealogy\Enums\PersonType::cases() as $type)
                            <option value="{{ $type->value }}" {{ $filters->personType === $type->value ? 'selected' : '' }}>
                                {{ $type->label()[app()->getLocale()] ?? $type->label()['ar'] }}
                            </option>
                        @endforeach
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
                            <i data-lucide="badge-check" class="w-4 h-4"></i>
                            {{ __('admin.verified') }}
                        </span>
                        <input type="checkbox" name="is_verified" value="1" {{ $filters->isVerified ? 'checked' : '' }}
                               class="form-checkbox rounded text-accent focus:ring-accent">
                    </label>
                </div>
            </div>

            <div class="sticky bottom-0 glass px-6 py-4 border-t flex gap-3"
                 :class="darkMode ? 'bg-gray-900/95 border-gray-800' : 'bg-white/95 border-gray-200'">
                <a href="{{ route('admin.genealogy.index') }}"
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

    <!-- Data Table -->
    <div class="rounded-2xl shadow-xl border relative"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
         style="overflow: clip;">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-sm">
                <thead class="glass border-b" :class="darkMode ? 'bg-gray-800/80 border-gray-700' : 'bg-gray-100/80 border-gray-300'">
                    <tr>
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
                    @forelse($people ?? [] as $person)
                        @php
                            $locale = app()->getLocale();
                            $secondaryLocale = $locale === 'ar' ? 'en' : 'ar';
                            $primaryName = $person->getTranslation('name', $locale) ?: $person->getTranslation('name', 'ar');
                            $secondaryName = $person->getTranslation('name', $secondaryLocale);
                            $typeLabel = $person->person_type?->label()[$locale] ?? $person->person_type?->label()['ar'] ?? null;
                        @endphp
                        <tr class="border-b transition-colors duration-150"
                            :class="darkMode ? 'border-gray-800 hover:bg-gray-800/60' : 'border-gray-200 hover:bg-gray-50'">

                            <!-- Photo + Name (always visible, locale-aware) -->
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    @if($person->getFirstMediaUrl('profile_image'))
                                        <img src="{{ $person->getFirstMediaUrl('profile_image', 'avatar') ?: $person->getFirstMediaUrl('profile_image') }}"
                                             alt="{{ $primaryName }}"
                                             class="w-9 h-9 rounded-full object-cover shadow-sm flex-shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                                            <i data-lucide="user" class="w-4 h-4 text-white/80"></i>
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('admin.genealogy.show', $person->id) }}"
                                               class="font-semibold text-sm truncate hover:text-accent transition-colors"
                                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                                                {{ $primaryName ?: '—' }}
                                            </a>
                                            @if($person->is_verified)
                                                <i data-lucide="badge-check" class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" title="{{ __('admin.verified') }}"></i>
                                            @endif
                                        </div>
                                        @if($secondaryName)
                                            <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $secondaryName }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Type -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('type')">
                                @if($typeLabel)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="darkMode ? 'bg-purple-900/30 text-purple-400' : 'bg-purple-100 text-purple-800'">
                                        {{ $typeLabel }}
                                    </span>
                                @else
                                    <span :class="darkMode ? 'text-gray-600' : 'text-gray-400'">—</span>
                                @endif
                            </td>

                            <!-- Gender -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('gender')">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white"
                                      :class="'{{ $person->gender === 'male' ? 'bg-blue-500' : 'bg-pink-500' }}'">
                                    {{ $person->gender === 'male' ? __('admin.male') : __('admin.female') }}
                                </span>
                            </td>

                            <!-- Tribe -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('tribe')">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $person->tribe ?: '—' }}</span>
                            </td>

                            <!-- Father -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('father')">
                                @if($person->father)
                                    <a href="{{ route('admin.genealogy.show', $person->father_id) }}" class="text-accent hover:underline">
                                        {{ $person->father->getTranslation('name', $locale) ?: $person->father->getTranslation('name', 'ar') }}
                                    </a>
                                @else
                                    <span :class="darkMode ? 'text-gray-600' : 'text-gray-400'">—</span>
                                @endif
                            </td>

                            <!-- Mother -->
                            <td class="px-4 py-2.5" x-show="visibleColumns.includes('mother')">
                                @if($person->mother)
                                    <a href="{{ route('admin.genealogy.show', $person->mother_id) }}" class="text-accent hover:underline">
                                        {{ $person->mother->getTranslation('name', $locale) ?: $person->mother->getTranslation('name', 'ar') }}
                                    </a>
                                @else
                                    <span :class="darkMode ? 'text-gray-600' : 'text-gray-400'">—</span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('admin.genealogy.show', $person->id) }}"
                                       class="p-1.5 rounded-lg transition-colors"
                                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                                       title="{{ __('admin.view') }}">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('admin.genealogy.edit', $person->id) }}"
                                       class="p-1.5 rounded-lg transition-colors"
                                       :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                                       title="{{ __('admin.edit') }}">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </a>
                                    <form action="{{ route('admin.genealogy.destroy', $person->id) }}" method="POST" class="inline"
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
                                        <i data-lucide="git-branch" class="w-8 h-8" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                    </div>
                                    <p class="text-base font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ __('admin.no_genealogy_found') }}
                                    </p>
                                    <a href="{{ route('admin.genealogy.create') }}"
                                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                        {{ __('admin.add_person') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if(isset($people) && $people->hasPages())
        <div class="border-t px-6 py-4" :class="darkMode ? 'border-gray-800 bg-gray-900/50' : 'border-gray-200 bg-gray-50'">
            {{ $people->withQueryString()->links() }}
        </div>
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
    function genealogyAdminTable() {
        const STORAGE_KEY = 'genealogy_admin_visible_columns';
        const allColumns = [
            { key: 'type', label: '{{ __('admin.type') }}' },
            { key: 'gender', label: '{{ __('admin.gender') }}' },
            { key: 'tribe', label: '{{ __('sahaba.tribe') }}' },
            { key: 'father', label: '{{ __('admin.father') }}' },
            { key: 'mother', label: '{{ __('admin.mother') }}' },
        ];
        const defaultVisible = ['type', 'gender', 'father', 'mother'];

        return {
            filtersOpen: {{ $filters->hasAdvanced() ? 'true' : 'false' }},
            columnsOpen: false,
            submitting: false,
            toggleableColumns: allColumns,
            visibleColumns: [],

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
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
