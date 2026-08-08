@extends('layouts.admin')

@php
    $locale = app()->getLocale();
    $personName = $muslim->name($locale);
    $backArrow = in_array($locale, ['ar', 'ur']) ? 'right' : 'left';

    $inputClasses = 'w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50';
@endphp

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div class="min-w-0">
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg flex-shrink-0">
                <i data-lucide="git-fork" class="w-7 h-7 text-white"></i>
            </div>
            <span class="truncate">{{ __('sahaba.genealogy.person_title', ['name' => $personName]) }}</span>
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            {{ __('sahaba.genealogy.person_subtitle') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.sahaba.edit', $muslim) }}"
           class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
            <i data-lucide="user" class="w-5 h-5"></i>
            <span>{{ __('sahaba.genealogy.open_profile') }}</span>
        </a>
        <a href="{{ route('admin.genealogy.index', ['root' => $muslim->slug]) }}"
           class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="git-fork" class="w-5 h-5"></i>
            <span>{{ __('sahaba.genealogy.explorer') }}</span>
        </a>
        <a href="{{ route('admin.genealogy.index') }}"
           class="px-5 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-100'">
            <i data-lucide="arrow-{{ $backArrow }}" class="w-5 h-5"></i>
            <span>{{ __('sahaba.back_to_list') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-6">

    @if($errors->any())
        <div class="px-5 py-4 rounded-xl border flex items-start gap-3"
             :class="darkMode ? 'bg-red-900/20 border-red-800 text-red-300' : 'bg-red-50 border-red-200 text-red-800'"
             role="alert" data-testid="genealogy-form-errors">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <ul class="space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Descent: parents + placement -->
    <form method="POST" action="{{ route('admin.genealogy.update', $muslim) }}"
          x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @method('PUT')

        <x-admin.form-section :title="__('sahaba.genealogy.parents')"
                              :description="__('sahaba.genealogy.parents_hint')"
                              icon="users">
            <div class="grid gap-5 sm:grid-cols-2">
                @foreach([
                    'father_id' => ['label' => __('sahaba.father'), 'relation' => 'father'],
                    'mother_id' => ['label' => __('sahaba.mother'), 'relation' => 'mother'],
                ] as $field => $meta)
                    @php
                        $current = $muslim->{$meta['relation']};
                        // old() wins on a rejected submit so the editor does not
                        // lose the pick that failed the cycle check.
                        $currentId = old($field, $muslim->{$field});
                        $currentLabel = (int) $currentId === (int) ($muslim->{$field} ?? 0)
                            ? $current?->name($locale)
                            : null;
                    @endphp
                    <div x-data="genealogyPicker('{{ $field }}', {{ $muslim->id }}, @js((string) ($currentId ?? '')), @js($currentLabel ?? ''))">
                        <label for="picker-{{ $field }}" class="block text-sm font-semibold mb-1.5"
                               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ $meta['label'] }}
                        </label>

                        <div class="relative">
                            <input id="picker-{{ $field }}" type="text" x-model="term"
                                   @input.debounce.300ms="search()" autocomplete="off"
                                   placeholder="{{ __('sahaba.picker_placeholder') }}"
                                   class="{{ $inputClasses }}"
                                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                                   data-testid="picker-{{ $field }}">
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

                        {{-- Always submitted, so an emptied picker clears the
                             column instead of leaving the old parent in place. --}}
                        <input type="hidden" name="{{ $field }}" :value="selectedId"
                               value="{{ old($field, $muslim->{$field} ?? '') }}"
                               data-testid="picker-value-{{ $field }}">

                        <div class="mt-1.5 flex items-center gap-2 text-sm" x-show="selectedLabel"
                             data-testid="picker-selected-{{ $field }}">
                            <i data-lucide="link" class="w-3.5 h-3.5 shrink-0"
                               :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                            <span class="truncate" :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                                  x-text="selectedLabel"></span>
                            <button type="button" @click="clear()"
                                    class="shrink-0 text-xs font-semibold text-red-500 hover:underline"
                                    data-testid="picker-clear-{{ $field }}">
                                {{ __('sahaba.genealogy.clear_parent') }}
                            </button>
                        </div>

                        @error($field) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </x-admin.form-section>

        <div class="mt-6">
            <x-admin.form-section :title="__('sahaba.genealogy.placement')" icon="list-ordered">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="tree_display_order" class="block text-sm font-semibold mb-1.5"
                               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ __('sahaba.tree_display_order') }}
                        </label>
                        <input id="tree_display_order" name="tree_display_order" type="number" min="0" dir="ltr"
                               value="{{ old('tree_display_order', $muslim->tree_display_order ?? '') }}"
                               class="{{ $inputClasses }}"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               data-testid="field-tree-order">
                        @error('tree_display_order') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl cursor-pointer transition-all border self-end"
                           :class="darkMode ? 'bg-gray-800/40 border-gray-700 hover:bg-gray-800' : 'bg-white border-gray-300 hover:bg-gray-50'">
                        <span class="text-sm font-semibold flex items-center gap-2"
                              :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            <i data-lucide="eye-off" class="w-4 h-4"></i>
                            {{ __('sahaba.hide_from_tree') }}
                        </span>
                        <input type="checkbox" name="hide_from_tree" value="1"
                               @checked(old('hide_from_tree', $muslim->hide_from_tree))
                               class="w-4 h-4 rounded text-accent focus:ring-accent"
                               data-testid="field-hide-from-tree">
                    </label>
                </div>
            </x-admin.form-section>
        </div>

        <div class="mt-6">
            <button type="submit" :disabled="submitting"
                    class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 shadow-lg flex items-center gap-2
                           disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100
                           bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                <i x-show="submitting" data-lucide="loader-2" class="w-5 h-5 animate-spin" style="display:none"></i>
                <i x-show="! submitting" data-lucide="save" class="w-5 h-5"></i>
                <span>{{ __('sahaba.save') }}</span>
            </button>
        </div>
    </form>

    <!-- Children. Read-only on purpose: the link lives on the child's row, so
         it is edited there rather than in two places. -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="px-6 py-4 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h2 class="font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="baby" class="w-5 h-5 text-accent"></i>
                {{ __('sahaba.genealogy.children') }}
                <span class="text-sm font-normal tabular-nums"
                      :class="darkMode ? 'text-gray-500' : 'text-gray-500'">({{ $children->count() }})</span>
            </h2>
            <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                {{ __('sahaba.genealogy.children_hint') }}
            </p>
        </div>

        <div class="p-4">
            @if($children->isEmpty())
                <p class="px-2 py-6 text-sm text-center" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                    {{ __('sahaba.genealogy.no_children') }}
                </p>
            @else
                <ul class="grid gap-2 sm:grid-cols-2">
                    @foreach($children as $child)
                        <li>
                            <a href="{{ route('admin.genealogy.show', $child) }}"
                               class="flex items-center gap-2 px-3 py-2.5 rounded-xl text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                               :class="darkMode ? 'bg-gray-800/40 hover:bg-gray-800 text-gray-200' : 'bg-gray-50 hover:bg-gray-100 text-gray-800'">
                                <span class="font-semibold truncate flex-1">{{ $child->name($locale) ?: '—' }}</span>
                                @if($child->tree_display_order !== null)
                                    <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-semibold tabular-nums"
                                          :class="darkMode ? 'bg-gray-700 text-gray-400' : 'bg-gray-200 text-gray-600'">
                                        #{{ $child->tree_display_order }}
                                    </span>
                                @endif
                                @unless($child->is_published)
                                    <span class="shrink-0 px-1.5 py-0.5 rounded text-[11px] font-semibold"
                                          :class="darkMode ? 'bg-gray-700 text-gray-400' : 'bg-gray-200 text-gray-600'">
                                        {{ __('sahaba.unpublished') }}
                                    </span>
                                @endunless
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <!-- Non-descent relationships: full CRUD -->
    <div id="relationships" class="rounded-2xl border overflow-hidden scroll-mt-24"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'"
         x-data="{ adding: {{ $errors->hasAny(['related_muslim_id', 'relationship_type', 'notes']) ? 'true' : 'false' }}, editing: null }">

        <div class="px-6 py-4 border-b flex flex-wrap items-center justify-between gap-3"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <div>
                <h2 class="font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="heart-handshake" class="w-5 h-5 text-accent"></i>
                    {{ __('sahaba.genealogy.relationships') }}
                    <span class="text-sm font-normal tabular-nums"
                          :class="darkMode ? 'text-gray-500' : 'text-gray-500'">({{ count($relationships) }})</span>
                </h2>
                <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                    {{ __('sahaba.genealogy.relationships_hint') }}
                </p>
            </div>

            <button type="button" @click="adding = ! adding; editing = null"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                           bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"
                    data-testid="toggle-add-relationship">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('sahaba.genealogy.add_relationship') }}
            </button>
        </div>

        <!-- Add form -->
        {{-- Plain x-show. x-collapse measures the content height at init, so a
             panel that starts hidden measures 0 and never opens; and Alpine's
             class-swap transitions were observed sticking at `enter-start`
             (opacity-0) here. A disclosure that reliably appears beats an
             animated one that sometimes does not. --}}
        <div x-show="adding">
            <form method="POST" action="{{ route('admin.genealogy.relationships.store', $muslim) }}"
                  class="p-5 border-b" :class="darkMode ? 'border-gray-800 bg-gray-800/20' : 'border-gray-200 bg-gray-50'">
                @csrf
                @include('admin.genealogy._relationship_fields', [
                    'muslim' => $muslim,
                    'relationshipTypes' => $relationshipTypes,
                    'relationship' => null,
                    'idPrefix' => 'new',
                ])

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="submit"
                            class="px-5 py-2 rounded-xl text-sm font-semibold transition-all hover:scale-105 shadow-lg flex items-center gap-2
                                   bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        {{ __('sahaba.genealogy.add_relationship') }}
                    </button>
                    <button type="button" @click="adding = false"
                            class="px-5 py-2 rounded-xl text-sm font-semibold transition-colors"
                            :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'">
                        {{ __('admin.cancel') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Existing rows -->
        <div class="p-4">
            @if($relationships === [])
                <p class="px-2 py-6 text-sm text-center" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                    {{ __('sahaba.genealogy.no_relationships') }}
                </p>
            @else
                <ul class="space-y-2">
                    @foreach($relationships as $row)
                        @php $rel = $row['model']; @endphp
                        <li class="rounded-xl border" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                            <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($row['counterpart'])
                                            <a href="{{ route('admin.genealogy.show', $row['counterpart']) }}"
                                               class="font-semibold text-sm truncate hover:text-accent transition-colors"
                                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                                                {{ $row['counterpart_name'] }}
                                            </a>
                                        @else
                                            <span class="font-semibold text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                                {{ $row['counterpart_name'] }}
                                            </span>
                                        @endif

                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                              :class="darkMode ? 'bg-accent/15 text-accent' : 'bg-[#0056b3]/10 text-[#0056b3]'">
                                            {{ $row['label'] }}
                                        </span>

                                        @if($rel->hide_from_tree)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold flex items-center gap-1"
                                                  :class="darkMode ? 'bg-amber-500/20 text-amber-400' : 'bg-amber-100 text-amber-700'">
                                                <i data-lucide="eye-off" class="w-3 h-3"></i>
                                                {{ __('sahaba.genealogy.hidden_from_tree') }}
                                            </span>
                                        @endif

                                        {{-- The row may be stored from the other
                                             end; saying so explains why the type
                                             in the edit form reads differently. --}}
                                        @if($row['is_reversed'])
                                            {{-- gray-400, not gray-500: on a gray-800
                                                 surface gray-500 sits right on the 3:1
                                                 floor for secondary text. --}}
                                            <span class="px-2 py-0.5 rounded-full text-xs"
                                                  :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                                                {{ __('sahaba.genealogy.reversed_hint') }}:
                                                {{ $rel->relationship_type->label($locale) }}
                                            </span>
                                        @endif
                                    </div>

                                    @if($rel->notes)
                                        <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $rel->notes }}</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button" @click="editing = editing === {{ $rel->id }} ? null : {{ $rel->id }}; adding = false"
                                            class="p-2 rounded-lg transition-colors"
                                            :class="darkMode ? 'hover:bg-gray-700 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-500 hover:text-gray-900'"
                                            :aria-expanded="editing === {{ $rel->id }} ? 'true' : 'false'"
                                            aria-label="{{ __('sahaba.genealogy.edit_relationship') }}"
                                            data-testid="edit-relationship-{{ $rel->id }}">
                                        <i data-lucide="edit-2" class="w-4 h-4"></i>
                                    </button>

                                    <form method="POST" action="{{ route('admin.genealogy.relationships.destroy', [$muslim, $rel]) }}"
                                          @submit.prevent="$dispatch('show-confirm', {
                                              title: '{{ __('admin.confirm_delete') }}',
                                              message: '{{ __('admin.delete_warning') }}',
                                              type: 'danger',
                                              onConfirm: () => $el.submit(),
                                          })">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg transition-colors"
                                                :class="darkMode ? 'hover:bg-red-900/40 text-gray-400 hover:text-red-400' : 'hover:bg-red-50 text-gray-500 hover:text-red-600'"
                                                aria-label="{{ __('admin.delete') }}"
                                                data-testid="delete-relationship-{{ $rel->id }}">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Inline edit -->
                            <div x-show="editing === {{ $rel->id }}">
                                <form method="POST" action="{{ route('admin.genealogy.relationships.update', [$muslim, $rel]) }}"
                                      class="px-4 pb-4 pt-1 border-t" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                                    @csrf
                                    @method('PUT')
                                    @include('admin.genealogy._relationship_fields', [
                                        'muslim' => $muslim,
                                        'relationshipTypes' => $relationshipTypes,
                                        'relationship' => $row,
                                        'idPrefix' => 'rel-'.$rel->id,
                                    ])

                                    <div class="mt-4 flex flex-wrap items-center gap-3">
                                        <button type="submit"
                                                class="px-5 py-2 rounded-xl text-sm font-semibold transition-all hover:scale-105 shadow-lg flex items-center gap-2
                                                       bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                                            <i data-lucide="save" class="w-4 h-4"></i>
                                            {{ __('sahaba.save') }}
                                        </button>
                                        <button type="button" @click="editing = null"
                                                class="px-5 py-2 rounded-xl text-sm font-semibold transition-colors"
                                                :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-200 text-gray-800 hover:bg-gray-300'">
                                            {{ __('admin.cancel') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initial id and label are passed in rather than read back out of the DOM:
    // Alpine initialises this component before the hidden input below it is
    // guaranteed to be queryable.
    // The add-relationship picker. Separate from genealogyPicker because it
    // starts empty and needs no clear affordance.
    function relationshipPicker(prefix, excludeId) {
        return {
            term: '',
            results: [],
            selectedId: '',
            selectedLabel: '',

            async search() {
                if (this.term.length < 2) { this.results = []; return; }
                const url = new URL('{{ route('admin.sahaba.picker') }}', window.location.origin);
                url.searchParams.set('q', this.term);
                if (excludeId) url.searchParams.set('exclude', excludeId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                this.results = (await res.json()).data;
            },

            select(person) {
                this.selectedId = person.id;
                this.selectedLabel = person.name_ar || person.name_en;
                this.results = [];
                this.term = '';
            },
        };
    }

    function genealogyPicker(field, excludeId, initialId, initialLabel) {
        return {
            term: '',
            results: [],
            selectedId: initialId ?? '',
            selectedLabel: initialLabel ?? '',

            async search() {
                if (this.term.length < 2) { this.results = []; return; }
                const url = new URL('{{ route('admin.sahaba.picker') }}', window.location.origin);
                url.searchParams.set('q', this.term);
                if (excludeId) url.searchParams.set('exclude', excludeId);
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                this.results = (await res.json()).data;
            },

            select(person) {
                this.selectedId = person.id;
                this.selectedLabel = person.name_ar || person.name_en;
                this.results = [];
                this.term = '';
            },

            // Empty string, not null: the field must still be submitted so the
            // controller receives an explicit "no parent".
            clear() {
                this.selectedId = '';
                this.selectedLabel = '';
                this.results = [];
                this.term = '';
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>
@endpush
