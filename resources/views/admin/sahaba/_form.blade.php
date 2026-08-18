{{--
    The Muslim form, shared by create and edit.

    Covers exactly the fields StoreMuslimRequest accepts — nothing more, since
    `validated()` is all that reaches the repository, so an extra input here
    would be a control that silently does nothing.

    @param \App\Domain\People\Models\Muslim|null $muslim
    @param \Illuminate\Support\Collection $roles
--}}
@php
    $muslim = $muslim ?? null;

    // Existing role assignments keyed by role id, so `is_featured` survives a
    // round trip. On a validation failure `old('roles')` is the source instead:
    // it is keyed by the loop index, so it is re-keyed onto role ids here.
    $oldRoles = collect(old('roles', []))
        ->filter(fn ($row) => is_array($row) && isset($row['role_id']))
        ->keyBy(fn ($row) => (int) $row['role_id']);

    $assigned = $oldRoles->isNotEmpty()
        ? $oldRoles->map(fn ($row) => (bool) ($row['is_featured'] ?? false))
        : ($muslim?->roles ?? collect())
            ->mapWithKeys(fn ($role) => [$role->id => (bool) $role->pivot->is_featured]);

@endphp

@if ($errors->any())
    <div class="mb-6 px-5 py-4 rounded-xl border flex items-start gap-3"
         :class="darkMode ? 'bg-red-900/20 border-red-800 text-red-300' : 'bg-red-50 border-red-200 text-red-800'"
         role="alert" data-testid="admin-form-errors">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
        <ul class="space-y-1 text-sm">
            @foreach ($errors->all() as $error)
                <li data-testid="admin-form-error">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-6">

    {{-- Identity: Arabic tab first, per project convention. --}}
    <x-admin.form-section id="sec-identity" :title="__('sahaba.section_identity')"
                          :description="__('sahaba.section_identity_hint')"
                          icon="user">
        <div x-data="{ tab: 'ar' }">
            <div class="flex gap-2 mb-5" role="tablist">
                @foreach (['ar' => 'العربية', 'en' => 'English'] as $code => $label)
                    <button type="button" @click="tab = '{{ $code }}'" role="tab"
                            :aria-selected="tab === '{{ $code }}'"
                            :class="tab === '{{ $code }}'
                                ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg'
                                : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')"
                            class="px-5 py-2 rounded-xl font-semibold text-sm transition-all"
                            data-testid="tab-{{ $code }}">{{ $label }}</button>
                @endforeach
            </div>

            @foreach (['ar' => 'العربية', 'en' => 'English'] as $code => $label)
                <div x-show="tab === '{{ $code }}'" @if($code !== 'ar') style="display:none" @endif
                     class="grid gap-4 sm:grid-cols-2">

                    <div class="sm:col-span-2">
                        <label for="name_{{ $code }}" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ __('sahaba.name') }} ({{ $label }})
                        </label>
                        <input id="name_{{ $code }}" name="name_{{ $code }}" type="text"
                               value="{{ old('name_'.$code, $muslim->{'name_'.$code} ?? '') }}"
                               class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                               dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
                               data-testid="field-name-{{ $code }}">
                        @error('name_'.$code) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="kunya_{{ $code }}" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ __('sahaba.kunya') }} ({{ $label }})
                        </label>
                        <input id="kunya_{{ $code }}" name="kunya[{{ $code }}]" type="text"
                               value="{{ old('kunya.'.$code, $muslim?->getTranslation('kunya', $code, false) ?? '') }}"
                               class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                               dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
                               data-testid="field-kunya-{{ $code }}">
                        @error('kunya.'.$code) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="birth_place_{{ $code }}" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ __('sahaba.birth_place') }} ({{ $label }})
                            <span class="text-xs font-normal opacity-60">— {{ __('locations.public.place_note') }}</span>
                        </label>
                        <input id="birth_place_{{ $code }}" name="birth_place[{{ $code }}]" type="text"
                               value="{{ old('birth_place.'.$code, $muslim?->getTranslation('birth_place', $code, false) ?? '') }}"
                               class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                               dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
                               data-testid="field-birth-place-{{ $code }}">
                        @error('birth_place.'.$code) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="bio_{{ $code }}" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ __('sahaba.bio') }} ({{ $label }})
                        </label>
                        <textarea id="bio_{{ $code }}" name="bio[{{ $code }}]" rows="3"
                                  class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                                  dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
                                  data-testid="field-bio-{{ $code }}">{{ old('bio.'.$code, $muslim?->getTranslation('bio', $code, false) ?? '') }}</textarea>
                        @error('bio.'.$code) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="content_{{ $code }}" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            {{ __('sahaba.content') }} ({{ $label }})
                        </label>
                        <textarea id="content_{{ $code }}" name="content[{{ $code }}]" rows="8"
                                  class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                                  dir="{{ $code === 'ar' ? 'rtl' : 'ltr' }}"
                                  data-testid="field-content-{{ $code }}">{{ old('content.'.$code, $muslim?->getTranslation('content', $code, false) ?? '') }}</textarea>
                        @error('content.'.$code) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach

            {{-- One canonical birthplace for the person, not one per locale:
                 the place carries its own names in all five languages. The
                 per-locale text fields above keep whatever was originally
                 recorded. --}}
            <div class="mt-6 pt-6 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                <x-admin.place-picker
                    name="birth_place_id"
                    :selected="old('birth_place_id', $muslim?->birth_place_id)"
                    :selected-label="$muslim?->birthPlace?->name()"
                    :label="__('locations.public.birth_place')" />
                @error('birth_place_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 mt-6 pt-6 border-t"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <div>
                <label for="slug" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                    {{ __('sahaba.slug') }} <span class="text-red-500">*</span>
                </label>
                <input id="slug" name="slug" type="text" required dir="ltr"
                       value="{{ old('slug', $muslim->slug ?? '') }}"
                       pattern="[a-z0-9\-]+" placeholder="abu-bakr-al-siddiq"
                       class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50 font-mono" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                       data-testid="field-slug">
                <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">a–z, 0–9, -</p>
                @error('slug') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="gender" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                    {{ __('sahaba.gender') }}
                </label>
                <select id="gender" name="gender" class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                        data-testid="field-gender">
                    <option value="">—</option>
                    @foreach (\App\Domain\People\Enums\Gender::options(app()->getLocale()) as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $muslim?->gender?->value) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="thumb" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                    {{ __('sahaba.thumb') }}
                </label>
                <input id="thumb" name="thumb" type="text" dir="ltr"
                       value="{{ old('thumb', $muslim->thumb ?? '') }}"
                       class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                       data-testid="field-thumb">
                <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('sahaba.thumb_hint') }}</p>
                @error('thumb') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="generation" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                    {{ __('sahaba.generation') }}
                </label>
                @php $generationValue = old('generation', $muslim->generation ?? ''); @endphp
                <select id="generation" name="generation"
                        class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                        data-testid="field-generation">
                    <option value="">—</option>
                    @foreach (\App\Domain\People\Enums\Generation::options(app()->getLocale()) as $value => $label)
                        <option value="{{ $value }}" @selected($generationValue === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('generation') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-admin.form-section>

    {{-- Hijri years are signed integers: pre-Hijra is negative, not an error. --}}
    <x-admin.form-section id="sec-dates" :title="__('sahaba.section_dates')" icon="calendar">
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ([
                'birth_year_hijri' => __('sahaba.birth_year'),
                'death_year_hijri' => __('sahaba.death_year'),
            ] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ $label }} ({{ __('sahaba.hijri') }})
                    </label>
                    <input id="{{ $field }}" name="{{ $field }}" type="number" min="-200" max="1500" dir="ltr"
                           value="{{ old($field, $muslim->{$field} ?? '') }}"
                           class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                           data-testid="field-{{ str_replace('_', '-', str_replace('_hijri', '', $field)) }}">
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ __('sahaba.before_hijra') }}: -10</p>
                    @error($field) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    </x-admin.form-section>

    {{-- Roles: a person holds as many as apply. Not separate person records. --}}
    <x-admin.form-section id="sec-roles" :title="__('sahaba.section_roles')"
                          :description="__('sahaba.section_roles_hint')"
                          icon="shield-check">
        @if ($roles->isEmpty())
            <p class="text-sm" :class="darkMode ? 'text-amber-400' : 'text-amber-700'">
                {{ __('sahaba.no_roles_seeded') }}
            </p>
        @else
            <div class="grid gap-3 sm:grid-cols-2" data-testid="field-roles">
                @foreach ($roles as $i => $role)
                    @php
                        $isAssigned = $assigned->has($role->id);
                        $isFeatured = (bool) $assigned->get($role->id, false);
                    @endphp
                    {{-- `checked` drives whether the row is submitted at all;
                         the featured box only matters when it is, so it is
                         disabled until then. --}}
                    <div x-data="{ on: {{ $isAssigned ? 'true' : 'false' }} }"
                         class="px-4 py-3 rounded-xl border transition-all"
                         :class="on
                            ? (darkMode ? 'bg-accent/10 border-accent/40' : 'bg-[#0056b3]/5 border-[#0056b3]/30')
                            : (darkMode ? 'bg-gray-800/40 border-gray-700' : 'bg-white border-gray-300')">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="roles[{{ $i }}][role_id]" value="{{ $role->id }}"
                                   x-model="on" @checked($isAssigned)
                                   class="w-4 h-4 rounded text-accent focus:ring-accent"
                                   data-testid="role-{{ $role->slug }}">
                            <span class="font-semibold text-sm" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">{{ $role->name() }}</span>
                        </label>

                        <label class="flex items-center gap-2 mt-2.5 ms-7 cursor-pointer"
                               :class="on ? '' : 'opacity-40 pointer-events-none'">
                            <input type="checkbox" name="roles[{{ $i }}][is_featured]" value="1"
                                   @checked($isFeatured) :disabled="! on"
                                   class="w-4 h-4 rounded text-amber-500 focus:ring-amber-500"
                                   data-testid="role-featured-{{ $role->slug }}">
                            <span class="text-xs flex items-center gap-1" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                <i data-lucide="star" class="w-3 h-3"></i>
                                {{ __('sahaba.featured_in_role') }}
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>
        @endif
        @error('roles') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
    </x-admin.form-section>

    {{-- Parent selection. A modal browser, not a type-ahead: an editor picking a
         parent often does not know a name to type, so the list opens on the most
         recent people and narrows by search and role from there. Unpublished
         people are offered — an unpublished person is still a valid ancestor. --}}
    <x-admin.form-section id="sec-lineage" :title="__('sahaba.section_lineage')" icon="git-fork">
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach (['father_id' => __('sahaba.father'), 'mother_id' => __('sahaba.mother')] as $field => $label)
                @php
                    $currentParent = $muslim?->{str_replace('_id', '', $field)};
                    $currentId = old($field, $muslim->{$field} ?? '');
                    // Only trust the stored name when the id still matches it —
                    // after a rejected submit old() may hold a different pick.
                    $currentLabel = (string) $currentId !== '' && (int) $currentId === (int) ($muslim->{$field} ?? 0)
                        ? $currentParent?->name(app()->getLocale())
                        : null;
                @endphp
                <div x-data="parentField(@js((string) $currentId), @js($currentLabel ?? ''))"
                     @muslim-picked.window="if ($event.detail.field === '{{ $field }}') pick($event.detail)">

                    <span class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        {{ $label }}
                    </span>

                    <div class="flex items-center gap-2">
                        {{-- Reads as the current value AND the control that changes it. --}}
                        <button type="button"
                                @click="$dispatch('open-muslim-picker', {
                                    field: '{{ $field }}',
                                    title: '{{ $label }}',
                                    excludeId: {{ $muslim->id ?? 'null' }},
                                })"
                                class="flex-1 min-w-0 flex items-center gap-2 px-4 py-2.5 rounded-xl border text-start text-sm transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 hover:bg-gray-750 text-white' : 'bg-white border-gray-300 hover:bg-gray-50 text-gray-900'"
                                data-testid="picker-{{ $field }}">
                            <i data-lucide="user-search" class="w-4 h-4 shrink-0 opacity-60"></i>
                            <span class="truncate" x-show="label" x-text="label"></span>
                            <span class="truncate opacity-60" x-show="! label">{{ __('sahaba.picker_modal.none_selected') }}</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 shrink-0 ms-auto opacity-60"></i>
                        </button>

                        <button type="button" @click="clear()" x-show="selectedId"
                                class="p-2.5 rounded-xl border transition-colors shrink-0"
                                :class="darkMode ? 'border-gray-700 text-gray-400 hover:bg-red-900/30 hover:text-red-400' : 'border-gray-300 text-gray-500 hover:bg-red-50 hover:text-red-600'"
                                aria-label="{{ __('admin.clear') }}"
                                data-testid="picker-clear-{{ $field }}">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    {{-- Always submitted, so an emptied field clears the column
                         instead of leaving the previous parent in place. --}}
                    <input type="hidden" name="{{ $field }}" :value="selectedId"
                           value="{{ $currentId }}"
                           data-testid="picker-value-{{ $field }}">

                    <p class="mt-1.5 text-xs flex items-center gap-1.5" :class="darkMode ? 'text-gray-500' : 'text-gray-500'"
                       x-show="label" data-testid="picker-selected-{{ $field }}">
                        <i data-lucide="link" class="w-3 h-3"></i>
                        <span x-text="label"></span>
                    </p>

                    @error($field) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
    </x-admin.form-section>

    {{-- Extended profile. Every input here is generated from
         MuslimProfile::fields() — adding a field there makes it appear, be
         validated and be stored, with no change to this file and no migration.
         Role-restricted fields (the prophetic mission) only render for a person
         whose roles allow them. --}}
    @php
        // Roles being edited, not just the persisted ones, so ticking "نبي" and
        // reloading reveals the restricted group.
        $profileRoleSlugs = $muslim?->roles->pluck('slug')->all() ?? [];
        $profile = $muslim?->profile ?? \App\Domain\People\ValueObjects\MuslimProfile::fromArray(null);
    @endphp

    @foreach (\App\Domain\People\ValueObjects\MuslimProfile::GROUPS as $group)
        @php $groupFields = \App\Domain\People\ValueObjects\MuslimProfile::fieldsInGroup($group, $profileRoleSlugs); @endphp

        @continue($groupFields === [])

        @php
            // Expanded when the group already holds something, collapsed when it
            // is empty: a filled section is visible at a glance, and the empty
            // ones stay out of the way instead of padding the form.
            $groupHasValues = collect($groupFields)
                ->flatMap(fn ($field) => $field->storageKeys())
                ->contains(fn (string $key) => filled($profile->get($key)));
        @endphp

        <x-admin.form-section :id="'sec-profile-'.$group" :title="__('sahaba.profile_groups.'.$group)"
                              :icon="['identity' => 'id-card', 'birth' => 'baby', 'death' => 'moon', 'mission' => 'sparkles'][$group] ?? 'info'"
                              :collapsible="true"
                              :collapsed="! $groupHasValues">
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($groupFields as $field)
                    @foreach ($field->translatable ? ['ar' => 'العربية', 'en' => 'English'] : [null => null] as $code => $langLabel)
                        @php
                            $storageKey = $field->storageKey($code);
                            $inputName = 'profile['.$storageKey.']';
                            $inputId = 'profile-'.$storageKey;
                            $value = old('profile.'.$storageKey, $profile->get($storageKey));
                        @endphp
                        <div class="{{ $field->type === \App\Domain\People\Enums\ProfileFieldType::LongText ? 'sm:col-span-2' : '' }}">
                            <label for="{{ $inputId }}" class="block text-sm font-semibold mb-1.5"
                                   :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                                {{ $field->label() }}@if($langLabel) <span class="opacity-60">({{ $langLabel }})</span>@endif
                            </label>

                            @if ($field->type === \App\Domain\People\Enums\ProfileFieldType::LongText)
                                <textarea id="{{ $inputId }}" name="{{ $inputName }}" rows="4"
                                          dir="{{ $code === 'en' ? 'ltr' : 'auto' }}"
                                          class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                                          :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                                          data-testid="profile-{{ $storageKey }}">{{ $value }}</textarea>
                            @else
                                <input id="{{ $inputId }}" name="{{ $inputName }}"
                                       type="{{ $field->type === \App\Domain\People\Enums\ProfileFieldType::Number ? 'number' : 'text' }}"
                                       value="{{ $value }}"
                                       dir="{{ $field->type === \App\Domain\People\Enums\ProfileFieldType::Number || $code === 'en' ? 'ltr' : 'auto' }}"
                                       class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50"
                                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                                       data-testid="profile-{{ $storageKey }}">
                            @endif

                            @error('profile.'.$storageKey) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                @endforeach
            </div>
        </x-admin.form-section>
    @endforeach

    <x-admin.form-section id="sec-publishing" :title="__('sahaba.section_publishing')" icon="eye">
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                'is_published' => ['label' => __('sahaba.published'), 'icon' => 'check-circle', 'testid' => 'field-published'],
                'hide_from_tree' => ['label' => __('sahaba.hide_from_tree'), 'icon' => 'eye-off', 'testid' => 'field-hide-from-tree'],
            ] as $field => $meta)
                <label class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl cursor-pointer transition-all border"
                       :class="darkMode ? 'bg-gray-800/40 border-gray-700 hover:bg-gray-800' : 'bg-white border-gray-300 hover:bg-gray-50'">
                    <span class="text-sm font-semibold flex items-center gap-2" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                        <i data-lucide="{{ $meta['icon'] }}" class="w-4 h-4"></i>
                        {{ $meta['label'] }}
                    </span>
                    <input type="checkbox" name="{{ $field }}" value="1"
                           @checked(old($field, $muslim->{$field} ?? false))
                           class="w-4 h-4 rounded text-accent focus:ring-accent"
                           data-testid="{{ $meta['testid'] }}">
                </label>
            @endforeach

            <div>
                <label for="tree_display_order" class="block text-sm font-semibold mb-1.5" :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                    {{ __('sahaba.tree_display_order') }}
                </label>
                <input id="tree_display_order" name="tree_display_order" type="number" min="0" dir="ltr"
                       value="{{ old('tree_display_order', $muslim->tree_display_order ?? '') }}"
                       class="w-full px-4 py-2.5 rounded-xl border text-base sm:text-sm transition-all focus:ring-2 focus:ring-accent/50" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                       data-testid="field-tree-order">
                @error('tree_display_order') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
    </x-admin.form-section>

</div>

@push('scripts')
<script>
    // One parent field. Holds the chosen id/label and listens for the modal's
    // pick; the modal itself owns all the searching and paging.
    function parentField(initialId, initialLabel) {
        return {
            selectedId: initialId ?? '',
            label: initialLabel ?? '',

            pick(detail) {
                this.selectedId = detail.id;
                this.label = detail.name;
            },

            // Empty string, not null: the field must still submit so the
            // controller receives an explicit "no parent".
            clear() {
                this.selectedId = '';
                this.label = '';
            },
        };
    }
</script>
@endpush
