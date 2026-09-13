{{-- Shared create/edit form for a reciter profile. Expects $profile (may be unsaved). --}}
@php
    $t = fn (string $key, array $replace = []) => __('quran.profiles_admin.'.$key, $replace);
    $locale = app()->getLocale();
    $inputClass = 'w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all';
    $inputTheme = "darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'";
    $labelTheme = "darkMode ? 'text-gray-300' : 'text-gray-700'";
    $cardTheme = "darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'";
    $languages = ['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'];
    $firstErrorLocale = collect(array_keys($languages))->first(fn ($l) => $errors->has('names.'.$l) || $errors->has('descriptions.'.$l)) ?? 'ar';
    $errorText = fn (string $field, string $id) => $errors->has($field) ? '<p id="'.$id.'-error" class="mt-1.5 text-sm text-red-600 flex items-center gap-1"><i data-lucide="alert-circle" class="w-4 h-4" aria-hidden="true"></i>'.e($errors->first($field)).'</p>' : '';
    $aria = fn (string $field, string $id) => $errors->has($field) ? 'aria-invalid=true aria-describedby='.$id.'-error' : '';
@endphp

<form method="POST" action="{{ $profile->exists ? route('admin.reciter-profiles.update', $profile->id) : route('admin.reciter-profiles.store') }}"
      enctype="multipart/form-data"
      x-data="{ activeTab: @js($firstErrorLocale) }" novalidate>
    @csrf
    @if($profile->exists) @method('PUT') @endif

    @if($errors->any())
        <div role="alert" class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-red-800 flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-5 h-5" aria-hidden="true"></i>{{ $t('fix_errors') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Names & descriptions -->
            <section class="rounded-2xl border p-6" :class="{{ $cardTheme }}" aria-labelledby="names-heading">
                <h2 id="names-heading" class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="languages" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ $t('basic_info') }}
                </h2>

                <div class="flex flex-wrap gap-2 border-b pb-4 mb-6" role="tablist" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach($languages as $lang => $label)
                        <button type="button" role="tab" id="tab-{{ $lang }}" aria-controls="panel-{{ $lang }}" @click="activeTab = '{{ $lang }}'"
                                :aria-selected="(activeTab === '{{ $lang }}').toString()"
                                class="px-4 py-2 rounded-lg font-medium transition-all flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-accent"
                                :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                            {{ $label }}
                            @if($lang === 'ar')<span aria-hidden="true">*</span>@endif
                            @if($errors->has('names.'.$lang) || $errors->has('descriptions.'.$lang))
                                <i data-lucide="alert-circle" class="w-4 h-4 text-red-400" aria-label="{{ $t('has_errors') }}"></i>
                            @endif
                        </button>
                    @endforeach
                </div>

                @foreach(array_keys($languages) as $lang)
                    @php($translation = $profile->exists ? $profile->translations->firstWhere('locale', $lang) : null)
                    <div id="panel-{{ $lang }}" role="tabpanel" aria-labelledby="tab-{{ $lang }}" x-show="activeTab === '{{ $lang }}'" @if($lang !== $firstErrorLocale) x-cloak @endif
                         class="space-y-4" dir="{{ in_array($lang, ['ar', 'ur'], true) ? 'rtl' : 'ltr' }}">
                        <div>
                            <label for="name_{{ $lang }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">
                                {{ $t('name') }} ({{ strtoupper($lang) }}) @if($lang === 'ar')<span class="text-red-500" aria-hidden="true">*</span>@endif
                            </label>
                            <input type="text" id="name_{{ $lang }}" name="names[{{ $lang }}]" maxlength="255"
                                   value="{{ old('names.'.$lang, $translation?->name) }}" @required($lang === 'ar') {!! $aria('names.'.$lang, 'name_'.$lang) !!}
                                   class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                            {!! $errorText('names.'.$lang, 'name_'.$lang) !!}
                        </div>
                        <div>
                            <label for="description_{{ $lang }}" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('description') }} ({{ strtoupper($lang) }})</label>
                            <textarea id="description_{{ $lang }}" name="descriptions[{{ $lang }}]" rows="5" {!! $aria('descriptions.'.$lang, 'description_'.$lang) !!}
                                      class="{{ $inputClass }} resize-y" :class="{{ $inputTheme }}">{{ old('descriptions.'.$lang, $translation?->description) }}</textarea>
                            {!! $errorText('descriptions.'.$lang, 'description_'.$lang) !!}
                        </div>
                    </div>
                @endforeach
            </section>

            <!-- Identity -->
            <section class="rounded-2xl border p-6 grid grid-cols-1 md:grid-cols-2 gap-5" :class="{{ $cardTheme }}" aria-labelledby="identity-heading">
                <h2 id="identity-heading" class="md:col-span-2 text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="fingerprint" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ $t('identity') }}
                </h2>
                <div>
                    <label for="slug" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('slug') }}</label>
                    <input type="text" id="slug" name="slug" dir="ltr" maxlength="255" value="{{ old('slug', $profile->slug) }}" placeholder="mahmoud-khalil-al-hussary"
                           aria-describedby="slug-hint{{ $errors->has('slug') ? ' slug-error' : '' }}" @if($errors->has('slug')) aria-invalid="true" @endif
                           class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                    <p id="slug-hint" class="mt-1.5 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">{{ $t('slug_hint') }}</p>
                    {!! $errorText('slug', 'slug') !!}
                </div>
                <div>
                    <label for="country" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('country') }}</label>
                    <input type="text" id="country" name="country" dir="ltr" maxlength="2" value="{{ old('country', $profile->country) }}" placeholder="EG"
                           aria-describedby="country-hint{{ $errors->has('country') ? ' country-error' : '' }}" @if($errors->has('country')) aria-invalid="true" @endif
                           class="{{ $inputClass }} uppercase" :class="{{ $inputTheme }}">
                    <p id="country-hint" class="mt-1.5 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                        {{ $t('country_hint') }}
                        @if($profile->country) · {{ country_flag($profile->country) }} {{ country_name($profile->country) }} @endif
                    </p>
                    {!! $errorText('country', 'country') !!}
                </div>
            </section>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            {{-- IMAGE-FIELD --}}
            <section class="rounded-2xl border p-6 space-y-4" :class="{{ $cardTheme }}" aria-labelledby="image-heading">
                <h2 id="image-heading" class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="image" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ $t('image') }}
                </h2>
                <x-admin.image-upload
                    name="profile_image_file"
                    idName="profile_image_id"
                    urlName="profile_image_url"
                    :removeName="$profile->exists ? 'remove_profile_image' : null"
                    :currentImage="$profile->profile_image_url"
                    aspectClass="aspect-square"
                    placeholderIcon="mic"
                    maxSize="10MB"
                    formats="JPG, PNG, WebP"
                />
            </section>
            {{-- /IMAGE-FIELD --}}

            <section class="rounded-2xl border p-6 space-y-5" :class="{{ $cardTheme }}" aria-labelledby="settings-heading">
                <h2 id="settings-heading" class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="settings" class="w-5 h-5 text-accent" aria-hidden="true"></i>{{ $t('settings') }}
                </h2>

                @if($profile->exists)
                    <div>
                        <label for="default_recording_id" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('default_recording') }} <span class="text-red-500" aria-hidden="true">*</span></label>
                        <select id="default_recording_id" name="default_recording_id" {!! $aria('default_recording_id', 'default_recording_id') !!} class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                            @foreach($profile->recordings->where('is_active', true)->where('kind', 'recitation') as $recording)
                                <option value="{{ $recording->id }}" @selected((int) old('default_recording_id', $profile->default_recording_id) === $recording->id)>{{ $recording->recordingLabel($locale) }} — #{{ $recording->id }}</option>
                            @endforeach
                        </select>
                        {!! $errorText('default_recording_id', 'default_recording_id') !!}
                    </div>
                @else
                    <p class="text-sm flex items-start gap-2 p-3 rounded-xl" :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-50 text-gray-600'">
                        <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0" aria-hidden="true"></i>{{ $t('default_hint_create') }}
                    </p>
                @endif

                <div>
                    <label for="display_order" class="block text-sm font-medium mb-2" :class="{{ $labelTheme }}">{{ $t('display_order') }}</label>
                    <input type="number" min="0" id="display_order" name="display_order" value="{{ old('display_order', $profile->display_order ?? 0) }}" {!! $aria('display_order', 'display_order') !!}
                           class="{{ $inputClass }}" :class="{{ $inputTheme }}">
                    {!! $errorText('display_order', 'display_order') !!}
                </div>

                @foreach(['is_active' => ['label' => $t('is_active'), 'icon' => 'power'], 'is_featured' => ['label' => $t('is_featured'), 'icon' => 'star']] as $field => $meta)
                    <div class="flex items-center justify-between gap-3">
                        <label for="{{ $field }}" class="text-sm font-medium flex items-center gap-2 cursor-pointer" :class="{{ $labelTheme }}">
                            <i data-lucide="{{ $meta['icon'] }}" class="w-4 h-4 text-accent" aria-hidden="true"></i>{{ $meta['label'] }}
                        </label>
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1" @checked((bool) old($field, $profile->{$field}))
                               class="w-5 h-5 rounded border-gray-300 text-[#0056b3] focus:ring-2 focus:ring-accent cursor-pointer">
                    </div>
                    {!! $errorText($field, $field) !!}
                @endforeach

                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-[1.02] flex items-center justify-center gap-2 shadow-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white focus:outline-none focus:ring-2 focus:ring-accent">
                    <i data-lucide="save" class="w-5 h-5" aria-hidden="true"></i>
                    <span>{{ $profile->exists ? $t('save') : $t('create') }}</span>
                </button>
            </section>
        </div>
    </div>
</form>

{{-- Media Picker Modal (image input "library" option) --}}
<x-admin.media-picker-modal />
