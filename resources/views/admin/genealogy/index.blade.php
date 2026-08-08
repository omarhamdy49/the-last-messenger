@extends('layouts.admin')

@php
    $locale = app()->getLocale();
    $rootName = $root ? $root->name($locale) : null;
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="min-w-0">
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg flex-shrink-0">
                <i data-lucide="git-fork" class="w-7 h-7 text-white"></i>
            </div>
            {{ __('sahaba.genealogy.title') }}
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('sahaba.genealogy.subtitle') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.sahaba.index') }}"
           class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
            <i data-lucide="users" class="w-5 h-5"></i>
            <span>{{ __('admin.nav.muslims_all') }}</span>
        </a>
        <a href="{{ route('public.genealogy', $root ? ['root' => $root->slug] : []) }}"
           target="_blank" rel="noopener"
           class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
            <i data-lucide="external-link" class="w-5 h-5"></i>
            <span>{{ __('sahaba.view_public') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Coverage -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <x-admin.stats-card icon="users" :title="__('sahaba.genealogy.stat_people')"
                            :value="$stats['people']" :gradient="true" :compact="true" />
        <x-admin.stats-card icon="git-merge" :title="__('sahaba.genealogy.stat_linked')"
                            :value="$stats['linked']" :compact="true" />
        <x-admin.stats-card icon="git-commit-horizontal" :title="__('sahaba.genealogy.stat_roots')"
                            :value="$stats['roots']" :compact="true" />
        <x-admin.stats-card icon="heart-handshake" :title="__('sahaba.genealogy.stat_edges')"
                            :value="$stats['edges']" :compact="true" />
        <x-admin.stats-card icon="eye-off" :title="__('sahaba.genealogy.stat_hidden')"
                            :value="$stats['hidden']" :compact="true" />
    </div>

    @if($root === null)
        <div class="rounded-2xl border p-12 text-center"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                <i data-lucide="git-fork" class="w-8 h-8" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
            </div>
            <p class="text-base font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                {{ __('sahaba.genealogy.no_people') }}
            </p>
            <a href="{{ route('admin.sahaba.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('sahaba.add_person') }}
            </a>
        </div>
    @else

        <!-- Root + depth controls -->
        <form method="GET" action="{{ route('admin.genealogy.index') }}"
              class="rounded-2xl border p-5"
              :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
              x-data="genealogyRootPicker('{{ $root->slug }}')">
            <div class="grid gap-4 lg:grid-cols-[1fr_auto_auto] lg:items-end">
                <div>
                    <label for="root-picker" class="block text-sm font-semibold mb-1.5"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('sahaba.genealogy.root') }}
                    </label>
                    <div class="relative">
                        <input id="root-picker" type="text" x-model="term" @input.debounce.300ms="search()"
                               autocomplete="off"
                               placeholder="{{ $rootName }}"
                               class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'">
                        <ul class="absolute z-20 inset-x-0 mt-1 rounded-xl border shadow-xl overflow-hidden max-h-64 overflow-y-auto custom-scrollbar"
                            :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'"
                            x-show="results.length" @click.away="results = []" style="display:none" x-cloak>
                            <template x-for="r in results" :key="r.id">
                                <li>
                                    <button type="button" @click="select(r)"
                                            class="w-full text-start px-3 py-2 text-sm transition-colors"
                                            :class="darkMode ? 'hover:bg-gray-700 text-gray-200' : 'hover:bg-gray-100 text-gray-800'">
                                        <span x-text="r.name_ar || r.name_en"></span>
                                        <span class="text-xs opacity-60" x-show="!r.is_published">({{ __('sahaba.unpublished') }})</span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <input type="hidden" name="root" :value="selectedSlug">
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        {{ __('sahaba.genealogy.root_hint') }}
                    </p>
                </div>

                <div>
                    <label for="depth" class="block text-sm font-semibold mb-1.5"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ __('sahaba.genealogy.depth') }}
                    </label>
                    <select id="depth" name="depth"
                            class="w-full lg:w-28 px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                            :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @foreach([2, 3, 4, 6, 8, 12] as $option)
                            <option value="{{ $option }}" @selected($depth === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 shadow-lg flex items-center justify-center gap-2
                               bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                    <i data-lucide="search" class="w-5 h-5"></i>
                    <span>{{ __('sahaba.genealogy.apply') }}</span>
                </button>
            </div>
        </form>

        <!-- Current root -->
        <div class="rounded-2xl border p-5 flex flex-col sm:flex-row sm:items-center gap-4"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="w-12 h-12 rounded-xl shrink-0 grid place-items-center bg-gradient-to-br from-[#0056b3] to-[#218838]">
                <i data-lucide="crown" class="w-6 h-6 text-white"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold uppercase tracking-wider" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                    {{ __('sahaba.genealogy.root') }}
                </p>
                <p class="text-lg font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $rootName }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.genealogy.show', $root) }}"
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2"
                   :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                    {{ __('sahaba.genealogy.manage') }}
                </a>
                <a href="{{ route('admin.sahaba.edit', $root) }}"
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2"
                   :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    {{ __('sahaba.genealogy.open_profile') }}
                </a>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">

            <!-- Ancestors: a chain, so a flat ordered list is the honest shape -->
            <div class="rounded-2xl border overflow-hidden"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="px-6 py-4 border-b flex items-center justify-between gap-3"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <h2 class="font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="arrow-up" class="w-5 h-5 text-accent"></i>
                        {{ __('sahaba.genealogy.ancestors') }}
                    </h2>
                    <span class="text-sm tabular-nums" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        {{ count($ancestors['nodes']) }}
                    </span>
                </div>

                <div class="p-4">
                    @if($ancestors['nodes'] === [])
                        <p class="px-2 py-6 text-sm text-center" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                            {{ __('sahaba.genealogy.no_ancestors') }}
                        </p>
                    @else
                        <ol class="space-y-1">
                            @foreach($ancestors['nodes'] as $node)
                                @php
                                    $name = $locale === 'ar'
                                        ? ($node['name_ar'] ?: $node['name_en'])
                                        : ($node['name_en'] ?: $node['name_ar']);
                                @endphp
                                <li class="flex items-center gap-3">
                                    <span class="shrink-0 px-2 py-0.5 rounded text-[11px] font-semibold tabular-nums"
                                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                                        {{ __('sahaba.genealogy.generation', ['depth' => $node['depth']]) }}
                                    </span>
                                    <a href="{{ route('admin.genealogy.show', $node['id']) }}"
                                       class="min-w-0 flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-sm flex-1 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                       :class="darkMode ? 'hover:bg-gray-800 text-gray-200' : 'hover:bg-gray-100 text-gray-800'">
                                        <span class="font-semibold truncate">{{ $name ?: '—' }}</span>
                                        <span class="shrink-0 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                            {{ __('sahaba.'.$node['relation']) }}
                                        </span>
                                        @unless($node['is_published'])
                                            <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-semibold"
                                                  :class="darkMode ? 'bg-gray-700 text-gray-400' : 'bg-gray-200 text-gray-600'">
                                                {{ __('sahaba.unpublished') }}
                                            </span>
                                        @endunless
                                    </a>
                                </li>
                            @endforeach
                        </ol>

                        @if($ancestors['depth_capped'])
                            <p class="mt-3 flex items-start gap-2 px-3 py-2 rounded-lg text-xs"
                               :class="darkMode ? 'bg-amber-500/10 text-amber-400' : 'bg-amber-50 text-amber-800'"
                               role="status">
                                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-px"></i>
                                {{ __('sahaba.genealogy.depth_capped', ['depth' => $depth]) }}
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Descendants: nested, collapsible -->
            <div class="rounded-2xl border overflow-hidden"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="px-6 py-4 border-b flex items-center justify-between gap-3"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <h2 class="font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="arrow-down" class="w-5 h-5 text-accent"></i>
                        {{ __('sahaba.genealogy.descendants') }}
                    </h2>
                    <span class="text-sm tabular-nums" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        {{ count($descendants['nodes']) }}
                    </span>
                </div>

                <div class="p-4">
                    @if($descendantTree === [])
                        <p class="px-2 py-6 text-sm text-center" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                            {{ __('sahaba.genealogy.no_descendants') }}
                        </p>
                    @else
                        <ul>
                            @foreach($descendantTree as $node)
                                @include('admin.genealogy._node', ['node' => $node])
                            @endforeach
                        </ul>

                        @if($descendants['depth_capped'])
                            <p class="mt-3 flex items-start gap-2 px-3 py-2 rounded-lg text-xs"
                               :class="darkMode ? 'bg-amber-500/10 text-amber-400' : 'bg-amber-50 text-amber-800'"
                               role="status">
                                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-px"></i>
                                {{ __('sahaba.genealogy.depth_capped', ['depth' => $depth]) }}
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Non-descent relations of the root -->
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="px-6 py-4 border-b flex items-center justify-between gap-3"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <h2 class="font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="heart-handshake" class="w-5 h-5 text-accent"></i>
                    {{ __('sahaba.genealogy.relationships') }}
                </h2>
                <a href="{{ route('admin.genealogy.show', $root) }}#relationships"
                   class="px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2"
                   :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    {{ __('sahaba.genealogy.add_relationship') }}
                </a>
            </div>

            <div class="p-4">
                @if($relations === [])
                    <p class="px-2 py-6 text-sm text-center" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        {{ __('sahaba.genealogy.no_relationships') }}
                    </p>
                @else
                    <ul class="flex flex-wrap gap-2">
                        @foreach($relations as $relation)
                            <li class="px-3 py-2 rounded-xl text-sm flex items-center gap-2"
                                :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
                                <span class="font-semibold">{{ $relation['name'] }}</span>
                                <span class="text-xs opacity-70">— {{ $relation['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function genealogyRootPicker(currentSlug) {
        return {
            term: '',
            results: [],
            // Pre-seeded so submitting without touching the picker keeps the
            // current root instead of resetting the explorer.
            selectedSlug: currentSlug,

            async search() {
                if (this.term.length < 2) { this.results = []; return; }
                const url = new URL('{{ route('admin.sahaba.picker') }}', window.location.origin);
                url.searchParams.set('q', this.term);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                this.results = (await res.json()).data;
            },

            select(person) {
                this.selectedSlug = person.slug;
                this.term = person.name_ar || person.name_en;
                this.results = [];
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
