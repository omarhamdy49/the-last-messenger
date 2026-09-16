{{-- Shared create/edit form. Arabic tab is selected by default (constitution V). --}}
@php
    $locales = enabled_content_languages();
    $current = $location ?? null;
    $aliasList = old('aliases', $current?->aliasList() ?? []);
@endphp

<div x-data="{ tab: 'ar', aliases: {{ Js::from(array_values(array_filter($aliasList))) }} }" class="space-y-6">

    @if($errors->any())
        <div class="p-4 rounded-xl border bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300">
            <ul class="list-disc list-inside space-y-1 text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Names per locale --}}
    <div class="p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="flex flex-wrap gap-2 mb-5">
            @foreach($locales as $code => $label)
                <button type="button" @click="tab = '{{ $code }}'"
                        :class="tab === '{{ $code }}'
                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white'
                            : (darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700')"
                        class="px-4 py-2 rounded-xl text-sm font-semibold transition-all">
                    {{ $label }}@if($code === 'ar')<span class="opacity-70"> *</span>@endif
                </button>
            @endforeach
        </div>

        @foreach($locales as $code => $label)
            <div x-show="tab === '{{ $code }}'" x-cloak class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('locations.fields.name') }} — {{ $label }}
                    </label>
                    <input type="text" name="name[{{ $code }}]"
                           value="{{ old('name.'.$code, $current?->getTranslation('name', $code, false)) }}"
                           class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('locations.fields.description') }} — {{ $label }}
                    </label>
                    <textarea name="description[{{ $code }}]" rows="3"
                              class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                              :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">{{ old('description.'.$code, $current?->getTranslation('description', $code, false)) }}</textarea>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Identity and placement --}}
    <div class="p-6 rounded-2xl border grid grid-cols-1 md:grid-cols-2 gap-5"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.fields.slug') }} *</label>
            <input type="text" name="slug" dir="ltr" required value="{{ old('slug', $current?->slug) }}"
                   class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            <p class="mt-1 text-xs opacity-60">{{ __('locations.hints.slug') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.fields.type') }} *</label>
            <select name="type" required
                    class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                    :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                @foreach($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $current?->type?->value) === $type->value)>
                        {{ __('locations.types.'.$type->value) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.fields.parent') }}</label>
            <select name="parent_id"
                    class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                    :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <option value="">{{ __('locations.fields.no_parent') }}</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected((int) old('parent_id', $current?->parent_id) === $parent->id)>
                        {{ str_repeat('— ', $parent->depth) }}{{ $parent->name() ?? $parent->slug }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs opacity-60">{{ __('locations.hints.parent') }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.fields.latitude') }}</label>
            <input type="text" inputmode="decimal" dir="ltr" name="latitude" value="{{ old('latitude', $current?->latitude) }}"
                   class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.fields.longitude') }}</label>
            <input type="text" inputmode="decimal" dir="ltr" name="longitude" value="{{ old('longitude', $current?->longitude) }}"
                   class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            <p class="mt-1 text-xs opacity-60">{{ __('locations.hints.coordinates') }}</p>
        </div>
    </div>

    {{-- Aliases: what the resolver matches legacy text against. --}}
    <div class="p-6 rounded-2xl border" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.fields.aliases') }}</label>
        <p class="mb-3 text-xs opacity-60">{{ __('locations.hints.aliases') }}</p>

        <template x-for="(alias, i) in aliases" :key="i">
            <div class="flex items-center gap-2 mb-2">
                <input type="text" :name="'aliases[' + i + ']'" x-model="aliases[i]"
                       class="flex-1 px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                <button type="button" @click="aliases.splice(i, 1)"
                        class="p-2.5 rounded-xl text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30"
                        :title="'{{ __('locations.actions.remove_alias') }}'">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </template>

        <button type="button" @click="aliases.push('')"
                class="mt-2 px-4 py-2 rounded-xl text-sm font-semibold flex items-center gap-2"
                :class="darkMode ? 'bg-gray-800 text-gray-200' : 'bg-gray-100 text-gray-700'">
            <i data-lucide="plus" class="w-4 h-4"></i>{{ __('locations.actions.add_alias') }}
        </button>
    </div>

    {{-- Flags --}}
    <div class="p-6 rounded-2xl border flex flex-wrap items-end gap-6"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <label class="flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $current?->is_active ?? true))
                   class="rounded border-gray-300 text-[#0056b3] focus:ring-[#0056b3]">
            {{ __('locations.fields.is_active') }}
        </label>

        <div>
            <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('locations.fields.display_order') }}</label>
            <input type="number" name="display_order" min="0" value="{{ old('display_order', $current?->display_order ?? 0) }}"
                   class="w-32 px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-[#0056b3]"
                   :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit"
                class="px-6 py-2.5 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
            <i data-lucide="save" class="w-5 h-5"></i>{{ __('locations.actions.save') }}
        </button>
        <a href="{{ route('admin.locations.index') }}"
           class="px-6 py-2.5 rounded-xl font-semibold" :class="darkMode ? 'bg-gray-800 text-gray-200' : 'bg-gray-100 text-gray-700'">
            {{ __('locations.actions.cancel') }}
        </a>
    </div>
</div>
