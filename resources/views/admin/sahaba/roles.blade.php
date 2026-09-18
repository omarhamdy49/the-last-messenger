@extends('layouts.admin')

@php
    // Same icon vocabulary as the figures list, so a role reads the same
    // wherever it appears in the panel.
    $roleIcons = [
        'sahabi' => 'star',
        'narrator' => 'quote',
        'scholar' => 'graduation-cap',
        'grader' => 'gavel',
        'compiler' => 'library',
    ];
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="shield-check" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('sahaba.roles_page.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('sahaba.roles_page.subtitle') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.sahaba.index') }}"
           class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
            <i data-lucide="users" class="w-5 h-5"></i>
            <span>{{ __('admin.nav.muslims_all') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Coverage summary -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <x-admin.stats-card
            icon="users"
            :title="__('sahaba.roles_page.total_people')"
            :value="$totalPeople"
            :gradient="true"
            :compact="true"
        />
        <x-admin.stats-card
            icon="shield-check"
            :title="__('sahaba.roles_page.roles_count')"
            :value="$roles->count()"
            :compact="true"
        />
        <x-admin.stats-card
            icon="user-x"
            :title="__('sahaba.roles_page.unassigned')"
            :value="$unassignedCount"
            :compact="true"
        />
    </div>

    {{-- Figures with no role appear on no role-scoped list, so they are easy to
         lose. Only worth saying when there are some. --}}
    @if($unassignedCount > 0)
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl border"
             :class="darkMode ? 'bg-amber-500/10 border-amber-500/30 text-amber-300' : 'bg-amber-50 border-amber-200 text-amber-800'">
            <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div class="text-sm">
                <p class="font-semibold">{{ __('sahaba.roles_page.unassigned') }}: {{ number_format($unassignedCount) }}</p>
                <p class="mt-0.5 opacity-90">{{ __('sahaba.roles_page.unassigned_hint') }}</p>
            </div>
        </div>
    @endif

    <!-- Roles table -->
    <div class="rounded-2xl shadow-xl border" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
         style="overflow: clip;">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-sm">
                <thead class="glass border-b" :class="darkMode ? 'bg-gray-800/80 border-gray-700' : 'bg-gray-100/80 border-gray-300'">
                    <tr>
                        @foreach([
                            ['label' => __('sahaba.roles_page.name_ar'), 'align' => 'start'],
                            ['label' => __('sahaba.roles_page.name_en'), 'align' => 'start'],
                            ['label' => __('sahaba.roles_page.slug'), 'align' => 'start'],
                            ['label' => __('sahaba.roles_page.people'), 'align' => 'center'],
                            ['label' => __('sahaba.roles_page.published'), 'align' => 'center'],
                            ['label' => __('sahaba.roles_page.featured'), 'align' => 'center'],
                            ['label' => __('admin.actions'), 'align' => 'center'],
                        ] as $column)
                            <th class="px-4 py-3 text-{{ $column['align'] }}">
                                <span class="font-bold text-xs uppercase tracking-wider"
                                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $column['label'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr class="border-b transition-colors duration-150"
                            :class="darkMode ? 'border-gray-800 hover:bg-gray-800/60' : 'border-gray-200 hover:bg-gray-50'">

                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                                bg-gradient-to-br from-[#0056b3] to-[#218838]">
                                        <i data-lucide="{{ $roleIcons[$role->slug] ?? 'user-check' }}" class="w-4 h-4 text-white"></i>
                                    </div>
                                    <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ $role->name_ar }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ $role->name_en ?: '—' }}</span>
                            </td>

                            <td class="px-4 py-3">
                                <code class="px-2 py-0.5 rounded text-xs font-mono"
                                      :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'"
                                      dir="ltr">{{ $role->slug }}</code>
                            </td>

                            {{-- Each count links to the list filtered to exactly the
                                 rows it counts. A number an editor cannot open is a
                                 number they have to reproduce by hand in the filters. --}}
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.sahaba.index', ['role' => $role->slug]) }}"
                                   class="font-bold tabular-nums rounded px-1 transition-colors hover:text-accent
                                          focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                   :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ number_format($role->people_count) }}
                                </a>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.sahaba.index', ['role' => $role->slug, 'status' => 'published']) }}"
                                   class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums transition-colors
                                          focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                   :class="darkMode ? 'bg-green-900/30 text-green-400 hover:bg-green-900/50' : 'bg-green-100 text-green-700 hover:bg-green-200'">
                                    {{ number_format($role->published_count) }}
                                </a>
                            </td>

                            {{-- Zero featured is the state that silently empties the
                                 homepage carousel, so it reads as a warning rather than
                                 as an ordinary count of none. --}}
                            <td class="px-4 py-3 text-center">
                                @if($role->featured_count > 0)
                                    <a href="{{ route('admin.sahaba.index', ['role' => $role->slug, 'is_featured' => '1']) }}"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums transition-colors
                                              focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                       :class="darkMode ? 'bg-amber-500/20 text-amber-400 hover:bg-amber-500/30' : 'bg-amber-100 text-amber-700 hover:bg-amber-200'">
                                        <i data-lucide="star" class="w-3 h-3"></i>
                                        {{ number_format($role->featured_count) }}
                                    </a>
                                @else
                                    <a href="{{ route('admin.sahaba.index', ['role' => $role->slug]) }}"
                                       title="{{ __('sahaba.featured_hint') }}"
                                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold tabular-nums transition-colors
                                              focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                       :class="darkMode ? 'bg-gray-800 text-gray-500 hover:bg-gray-700' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                                        <i data-lucide="star-off" class="w-3 h-3"></i>
                                        0
                                    </a>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.sahaba.index', ['role' => $role->slug]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                                   :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                                    <i data-lucide="list" class="w-4 h-4"></i>
                                    <span>{{ __('sahaba.roles_page.view_people') }}</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4"
                                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                                    <i data-lucide="shield-off" class="w-8 h-8" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                </div>
                                <p class="text-base font-medium" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    {{ __('sahaba.roles_page.empty') }}
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Says plainly why there is no "add role" button, rather than leaving an
         editor hunting for one. --}}
    <p class="flex items-start gap-2 text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
        <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
        <span>{{ __('sahaba.roles_page.fixed_notice') }}</span>
    </p>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
