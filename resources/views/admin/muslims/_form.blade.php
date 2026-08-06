@php($muslim = $muslim ?? null)

@if ($errors->any())
    <div class="mb-4 px-4 py-3 rounded-xl bg-red-100 text-red-800" data-testid="admin-form-errors">
        <ul>
            @foreach ($errors->all() as $error)
                <li data-testid="admin-form-error">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Arabic is the default active tab (project convention). --}}
<div x-data="{ tab: 'ar' }" class="mb-6">
    <div class="flex gap-2 mb-4">
        <button type="button" @click="tab = 'ar'" :class="tab === 'ar' ? 'bg-[#0056b3] text-white' : 'bg-gray-100'"
                class="px-4 py-2 rounded-lg" data-testid="tab-ar">العربية</button>
        <button type="button" @click="tab = 'en'" :class="tab === 'en' ? 'bg-[#0056b3] text-white' : 'bg-gray-100'"
                class="px-4 py-2 rounded-lg" data-testid="tab-en">English</button>
    </div>

    <div x-show="tab === 'ar'" class="grid gap-4">
        <label class="grid gap-1">
            <span class="text-sm">{{ __('people.name') }} (ar)</span>
            <input name="name_ar" value="{{ old('name_ar', $muslim->name_ar ?? '') }}"
                   class="border rounded-lg px-3 py-2" data-testid="field-name-ar">
        </label>
        <label class="grid gap-1">
            <span class="text-sm">{{ __('people.kunya') }} (ar)</span>
            <input name="kunya[ar]" value="{{ old('kunya.ar', $muslim?->getTranslation('kunya', 'ar', false) ?? '') }}"
                   class="border rounded-lg px-3 py-2" data-testid="field-kunya-ar">
        </label>
        <label class="grid gap-1">
            <span class="text-sm">{{ __('people.bio') }} (ar)</span>
            <textarea name="bio[ar]" rows="4" class="border rounded-lg px-3 py-2"
                      data-testid="field-bio-ar">{{ old('bio.ar', $muslim?->getTranslation('bio', 'ar', false) ?? '') }}</textarea>
        </label>
    </div>

    <div x-show="tab === 'en'" class="grid gap-4" style="display:none">
        <label class="grid gap-1">
            <span class="text-sm">{{ __('people.name') }} (en)</span>
            <input name="name_en" value="{{ old('name_en', $muslim->name_en ?? '') }}"
                   class="border rounded-lg px-3 py-2" data-testid="field-name-en">
        </label>
        <label class="grid gap-1">
            <span class="text-sm">{{ __('people.kunya') }} (en)</span>
            <input name="kunya[en]" value="{{ old('kunya.en', $muslim?->getTranslation('kunya', 'en', false) ?? '') }}"
                   class="border rounded-lg px-3 py-2" data-testid="field-kunya-en">
        </label>
        <label class="grid gap-1">
            <span class="text-sm">{{ __('people.bio') }} (en)</span>
            <textarea name="bio[en]" rows="4" class="border rounded-lg px-3 py-2"
                      data-testid="field-bio-en">{{ old('bio.en', $muslim?->getTranslation('bio', 'en', false) ?? '') }}</textarea>
        </label>
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2 mb-6">
    <label class="grid gap-1">
        <span class="text-sm">{{ __('people.slug') }}</span>
        <input name="slug" value="{{ old('slug', $muslim->slug ?? '') }}" required
               class="border rounded-lg px-3 py-2" data-testid="field-slug">
    </label>
    <label class="grid gap-1">
        <span class="text-sm">{{ __('people.gender') }}</span>
        <select name="gender" class="border rounded-lg px-3 py-2" data-testid="field-gender">
            <option value="">—</option>
            @foreach (\App\Domain\People\Enums\Gender::options(app()->getLocale()) as $value => $label)
                <option value="{{ $value }}" @selected(old('gender', $muslim?->gender?->value) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-1">
        {{-- Signed: pre-Hijra years are negative and normal. --}}
        <span class="text-sm">{{ __('people.birth_year') }}</span>
        <input type="number" name="birth_year_hijri" value="{{ old('birth_year_hijri', $muslim->birth_year_hijri ?? '') }}"
               class="border rounded-lg px-3 py-2" data-testid="field-birth-year">
    </label>
    <label class="grid gap-1">
        <span class="text-sm">{{ __('people.death_year') }}</span>
        <input type="number" name="death_year_hijri" value="{{ old('death_year_hijri', $muslim->death_year_hijri ?? '') }}"
               class="border rounded-lg px-3 py-2" data-testid="field-death-year">
    </label>
</div>

{{-- Roles: a person holds as many as apply. Not separate person records. --}}
<fieldset class="mb-6">
    <legend class="text-sm font-semibold mb-2">{{ __('people.roles') }}</legend>
    <div class="flex flex-wrap gap-3" data-testid="field-roles">
        @foreach ($roles as $i => $role)
            <label class="flex items-center gap-2">
                <input type="checkbox" name="roles[{{ $i }}][role_id]" value="{{ $role->id }}"
                       data-testid="role-{{ $role->slug }}"
                       @checked(collect(old('roles', $muslim?->roles->pluck('id')->all() ?? []))
                           ->flatten()->contains($role->id))>
                <span>{{ $role->name() }}</span>
            </label>
        @endforeach
    </div>
</fieldset>

{{-- Parent pickers. They search BOTH name columns and deliberately include
     unpublished people: an unpublished person is still a valid ancestor. --}}
@foreach (['father_id' => __('people.father'), 'mother_id' => __('people.mother')] as $field => $label)
    <div class="mb-6" x-data="muslimPicker('{{ $field }}', {{ $muslim->id ?? 'null' }})">
        <label class="grid gap-1">
            <span class="text-sm">{{ $label }}</span>
            <input type="text" x-model="term" @input.debounce.300ms="search()"
                   placeholder="{{ __('people.picker_placeholder') }}"
                   class="border rounded-lg px-3 py-2" data-testid="picker-{{ $field }}">
        </label>
        <input type="hidden" name="{{ $field }}" :value="selectedId"
               value="{{ old($field, $muslim->{$field} ?? '') }}" data-testid="picker-value-{{ $field }}">
        <ul class="mt-2 border rounded-lg divide-y" x-show="results.length"
            data-testid="picker-results-{{ $field }}">
            <template x-for="r in results" :key="r.id">
                <li class="px-3 py-2 cursor-pointer hover:bg-gray-50"
                    @click="select(r)" :data-testid="'picker-option-' + r.slug">
                    <span x-text="r.name_ar || r.name_en"></span>
                    <span class="text-xs text-gray-500" x-show="!r.is_published"
                          data-testid="picker-unpublished-badge">({{ __('people.unpublished') }})</span>
                </li>
            </template>
        </ul>
        <p class="text-sm text-gray-600 mt-1" x-show="selectedLabel"
           data-testid="picker-selected-{{ $field }}" x-text="selectedLabel"></p>
    </div>
@endforeach

<div class="flex flex-wrap gap-6 mb-6">
    <label class="flex items-center gap-2">
        <input type="checkbox" name="is_published" value="1"
               @checked(old('is_published', $muslim->is_published ?? false)) data-testid="field-published">
        <span>{{ __('people.published') }}</span>
    </label>
    <label class="flex items-center gap-2">
        <input type="checkbox" name="hide_from_tree" value="1"
               @checked(old('hide_from_tree', $muslim->hide_from_tree ?? false)) data-testid="field-hide-from-tree">
        <span>{{ __('people.hide_from_tree') }}</span>
    </label>
    <label class="grid gap-1">
        <span class="text-sm">{{ __('people.tree_display_order') }}</span>
        <input type="number" name="tree_display_order"
               value="{{ old('tree_display_order', $muslim->tree_display_order ?? '') }}"
               class="border rounded-lg px-3 py-2 w-32" data-testid="field-tree-order">
    </label>
</div>

@push('scripts')
<script>
function muslimPicker(field, excludeId) {
    return {
        term: '',
        results: [],
        selectedId: document.querySelector('[data-testid="picker-value-' + field + '"]')?.value || '',
        selectedLabel: '',
        async search() {
            if (this.term.length < 2) { this.results = []; return; }
            const url = new URL('{{ route('admin.muslims.picker') }}', window.location.origin);
            url.searchParams.set('q', this.term);
            if (excludeId) url.searchParams.set('exclude', excludeId);
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            this.results = (await res.json()).data;
        },
        select(r) {
            this.selectedId = r.id;
            this.selectedLabel = r.name_ar || r.name_en;
            this.results = [];
            this.term = '';
        },
    };
}
</script>
@endpush
