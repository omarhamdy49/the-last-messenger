{{--
    Fields for one non-descent relationship, shared by the add form and each
    inline edit form.

    On edit the counterpart is fixed: changing who a relationship points at is
    deleting one edge and creating another, so it is shown read-only rather than
    offered as a picker that silently rewires the row.

    @param \App\Domain\People\Models\Muslim $muslim         the person being edited
    @param array<string,string>             $relationshipTypes
    @param array|null                       $relationship   existing row (from relationshipRows), or null to add
    @param string                           $idPrefix       unique per form on the page
--}}
@php
    $isEdit = $relationship !== null;
    $rel = $isEdit ? $relationship['model'] : null;

    // On an edit, `old()` must not bleed across forms — a failed add would
    // otherwise repopulate every inline edit form on the page. Only the form
    // that was submitted carries errors, and errors are what re-open it.
    $typeValue = $isEdit
        ? $rel->relationship_type->value
        : old('relationship_type');
    $notesValue = $isEdit ? $rel->notes : old('notes');
    $sortValue = $isEdit ? $rel->sort_order : old('sort_order', 0);
    $hiddenValue = $isEdit ? $rel->hide_from_tree : old('hide_from_tree', false);

    $inputClasses = 'w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50';
@endphp

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

    {{-- The other person --}}
    <div class="sm:col-span-2">
        <label for="{{ $idPrefix }}-related" class="block text-sm font-semibold mb-1.5"
               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
            {{ __('sahaba.genealogy.related_person') }} @unless($isEdit)<span class="text-red-500">*</span>@endunless
        </label>

        @if($isEdit)
            {{-- Fixed. Submitted as a hidden field so validation still sees the
                 pair it needs to check for duplicates. --}}
            <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl border"
                 :class="darkMode ? 'bg-gray-800/60 border-gray-700 text-gray-300' : 'bg-gray-100 border-gray-300 text-gray-700'">
                <i data-lucide="lock" class="w-3.5 h-3.5 shrink-0 opacity-60"></i>
                <span class="text-sm truncate">{{ $relationship['counterpart_name'] }}</span>
            </div>
            <input type="hidden" name="related_muslim_id"
                   value="{{ $rel->counterpartIdFor($muslim->id) }}">
        @else
            <div x-data="relationshipPicker('{{ $idPrefix }}', {{ $muslim->id }})">
                <div class="relative">
                    <input id="{{ $idPrefix }}-related" type="text" x-model="term"
                           @input.debounce.300ms="search()" autocomplete="off"
                           placeholder="{{ __('sahaba.picker_placeholder') }}"
                           class="{{ $inputClasses }}"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                           data-testid="relationship-picker">
                    <ul class="absolute z-20 inset-x-0 mt-1 rounded-xl border shadow-xl overflow-hidden max-h-64 overflow-y-auto custom-scrollbar"
                        :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'"
                        x-show="results.length" @click.away="results = []" style="display:none" x-cloak>
                        <template x-for="r in results" :key="r.id">
                            <li>
                                <button type="button" @click="select(r)"
                                        class="w-full text-start px-3 py-2 text-sm transition-colors"
                                        :class="darkMode ? 'hover:bg-gray-700 text-gray-200' : 'hover:bg-gray-100 text-gray-800'"
                                        :data-testid="'relationship-option-' + r.slug">
                                    <span x-text="r.name_ar || r.name_en"></span>
                                    <span class="text-xs opacity-60" x-show="!r.is_published">({{ __('sahaba.unpublished') }})</span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>

                <input type="hidden" name="related_muslim_id" :value="selectedId"
                       value="{{ old('related_muslim_id') }}"
                       data-testid="relationship-related-id">

                <p class="mt-1.5 text-sm flex items-center gap-2" x-show="selectedLabel"
                   :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    <i data-lucide="link" class="w-3.5 h-3.5 shrink-0 opacity-60"></i>
                    <span class="truncate" x-text="selectedLabel"></span>
                </p>
            </div>
        @endif

        @error('related_muslim_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Type --}}
    <div>
        <label for="{{ $idPrefix }}-type" class="block text-sm font-semibold mb-1.5"
               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
            {{ __('sahaba.genealogy.relationship_type') }} <span class="text-red-500">*</span>
        </label>
        <select id="{{ $idPrefix }}-type" name="relationship_type" required
                class="{{ $inputClasses }}"
                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                data-testid="relationship-type">
            <option value="">—</option>
            @foreach($relationshipTypes as $value => $label)
                <option value="{{ $value }}" @selected($typeValue === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('relationship_type') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Sort order --}}
    <div>
        <label for="{{ $idPrefix }}-sort" class="block text-sm font-semibold mb-1.5"
               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
            {{ __('sahaba.genealogy.sort_order') }}
        </label>
        <input id="{{ $idPrefix }}-sort" name="sort_order" type="number" min="0" dir="ltr"
               value="{{ $sortValue }}"
               class="{{ $inputClasses }}"
               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
        @error('sort_order') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Notes --}}
    <div class="sm:col-span-2 lg:col-span-3">
        <label for="{{ $idPrefix }}-notes" class="block text-sm font-semibold mb-1.5"
               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
            {{ __('sahaba.genealogy.notes') }}
        </label>
        <input id="{{ $idPrefix }}-notes" name="notes" type="text" maxlength="2000"
               value="{{ $notesValue }}"
               class="{{ $inputClasses }}"
               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
        @error('notes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
    </div>

    {{-- Hidden from the public tree --}}
    <label class="flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl cursor-pointer transition-all border self-end"
           :class="darkMode ? 'bg-gray-800/40 border-gray-700 hover:bg-gray-800' : 'bg-white border-gray-300 hover:bg-gray-50'">
        <span class="text-sm font-semibold flex items-center gap-2"
              :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
            <i data-lucide="eye-off" class="w-4 h-4"></i>
            {{ __('sahaba.genealogy.hidden_from_tree') }}
        </span>
        <input type="checkbox" name="hide_from_tree" value="1" @checked($hiddenValue)
               class="w-4 h-4 rounded text-accent focus:ring-accent">
    </label>
</div>
