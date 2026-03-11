{{-- Category Form — used by create.blade.php and edit.blade.php --}}

@php
    $langs = ['ar', 'en', 'tr', 'fr', 'ur'];
    $langMeta = [
        'ar' => ['name' => 'العربية',  'flag' => '🇸🇦', 'dir' => 'rtl'],
        'en' => ['name' => 'English',  'flag' => '🇬🇧', 'dir' => 'ltr'],
        'tr' => ['name' => 'Türkçe',   'flag' => '🇹🇷', 'dir' => 'ltr'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'dir' => 'ltr'],
        'ur' => ['name' => 'اردو',     'flag' => '🇵🇰', 'dir' => 'rtl'],
    ];
    $isEdit  = isset($category) && $category->exists;
    $defLang = app()->getLocale();
    if (!array_key_exists($defLang, $langMeta)) { $defLang = 'ar'; }
@endphp

{{-- Define categoryForm() BEFORE the form so Alpine can find it when it initialises --}}
<script>
function categoryForm() {
    return {
        darkMode:      localStorage.getItem('darkMode') !== 'false',
        activeLanguage:'{{ $defLang }}',
        activeSeoLang: '{{ $defLang }}',

        handleSubmit(e) {
            e.target.submit();
        },

        @if($isEdit)
        deleteCategory() {
            window.showConfirm({
                title:     '{{ __("admin.confirm_delete") }}',
                message:   '{{ __("admin.delete_category_warning") }}',
                type:      'danger',
                onConfirm: () => {
                    const f  = document.createElement('form');
                    f.method = 'POST';
                    f.action = '{{ route("admin.categories.destroy", $category) }}';
                    f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
                                + '<input type="hidden" name="_method" value="DELETE">';
                    document.body.appendChild(f);
                    f.submit();
                },
            });
        },
        @endif
    };
}
</script>

<form method="POST"
      action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      x-data="categoryForm()"
      @submit.prevent="handleSubmit"
      enctype="multipart/form-data"
      class="space-y-8">

    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- ══════════════════════════════════════════════════
             LEFT / MAIN COLUMN
        ══════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- ─── Multi-language Fields ─── --}}
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

                {{-- Card Header --}}
                <div class="px-6 py-4 border-b flex items-center gap-2"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                    <h2 class="text-lg font-bold"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ __('admin.category_information') }}
                    </h2>
                </div>

                <div class="p-6">

                    {{-- Language Tab Buttons --}}
                    <div role="tablist"
                         class="flex flex-wrap gap-2 mb-6 pb-5 border-b"
                         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        @foreach($langs as $lang)
                            <button type="button"
                                    role="tab"
                                    id="tab-{{ $lang }}"
                                    :aria-selected="activeLanguage === '{{ $lang }}'"
                                    @click="activeLanguage = '{{ $lang }}'"
                                    class="px-4 py-2 rounded-lg font-semibold transition-all duration-200 flex items-center gap-2 text-sm"
                                    :class="activeLanguage === '{{ $lang }}'
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105'
                                        : (darkMode
                                            ? 'bg-gray-800 text-gray-300 hover:bg-gray-700'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                                <span>{{ $langMeta[$lang]['flag'] }}</span>
                                <span>{{ $langMeta[$lang]['name'] }}</span>
                                @if($errors->hasAny(["name.{$lang}", "description.{$lang}"]))
                                    <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    {{-- Language Panels --}}
                    @foreach($langs as $lang)
                        @php $ldir = $langMeta[$lang]['dir']; @endphp
                        <x-admin.language-tab-panel :language="$lang">

                            {{-- Name --}}
                            <div>
                                <label dir="{{ $ldir }}"
                                       class="block mb-1.5 text-sm font-semibold"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                                    {{ __('admin.category_name') }}
                                    <span class="text-red-500 ms-0.5">*</span>
                                </label>
                                <input type="text"
                                       name="name[{{ $lang }}]"
                                       value="{{ old("name.{$lang}", $isEdit ? ($category->translate($lang)?->name ?? '') : '') }}"
                                       dir="{{ $ldir }}"
                                       placeholder="{{ __('admin.enter_name_in', ['lang' => $langMeta[$lang]['name']]) }}"
                                       class="w-full px-4 py-2.5 rounded-lg border-2 text-sm transition-all focus:ring-2 focus:ring-accent/40 font-cairo"
                                       :class="darkMode
                                           ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent'
                                           : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
                                @error("name.{$lang}")
                                    <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div>
                                <label dir="{{ $ldir }}"
                                       class="block mb-1.5 text-sm font-semibold"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                                    {{ __('admin.description') }}
                                </label>
                                <textarea name="description[{{ $lang }}]"
                                          rows="4"
                                          dir="{{ $ldir }}"
                                          placeholder="{{ __('admin.brief_description') }}"
                                          class="w-full px-4 py-2.5 rounded-lg border-2 text-sm transition-all focus:ring-2 focus:ring-accent/40 font-cairo resize-none"
                                          :class="darkMode
                                              ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent'
                                              : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">{{ old("description.{$lang}", $isEdit ? ($category->translate($lang)?->description ?? '') : '') }}</textarea>
                                @error("description.{$lang}")
                                    <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                        </x-admin.language-tab-panel>
                    @endforeach

                    {{-- ─── Single Shared Slug ─── --}}
                    <div class="pt-5 border-t"
                         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        <label dir="ltr"
                               class="block mb-1.5 text-sm font-semibold"
                               :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                            <i data-lucide="link" class="w-3.5 h-3.5 inline me-1"></i>
                            {{ __('admin.slug') }}
                        </label>
                        <input type="text"
                               name="slug"
                               value="{{ old('slug', $isEdit ? $category->slug : '') }}"
                               dir="ltr"
                               placeholder="category-slug"
                               class="w-full px-4 py-2.5 rounded-lg border-2 text-sm transition-all focus:ring-2 focus:ring-accent/40 font-mono"
                               :class="darkMode
                                   ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent'
                                   : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
                        <p class="mt-1 text-xs flex items-center gap-1"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i>
                            {{ __('admin.url_friendly_hint') }}
                        </p>
                        @error('slug')
                            <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- ─── SEO Settings ─── --}}
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

                <div class="px-6 py-4 border-b flex items-center justify-between"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <div class="flex items-center gap-2">
                        <i data-lucide="search" class="w-5 h-5 text-accent"></i>
                        <div>
                            <h2 class="text-lg font-bold"
                                :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ __('admin.seo_settings') }}
                            </h2>
                            <p class="text-xs mt-0.5"
                               :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                {{ __('admin.seo_settings_desc') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    {{-- SEO Language Tabs --}}
                    <div class="flex flex-wrap gap-2 mb-5">
                        @foreach($langs as $lang)
                            <button type="button"
                                    @click="activeSeoLang = '{{ $lang }}'"
                                    class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-all"
                                    :class="activeSeoLang === '{{ $lang }}'
                                        ? 'bg-accent text-white'
                                        : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                                {{ $langMeta[$lang]['flag'] }} {{ $langMeta[$lang]['name'] }}
                            </button>
                        @endforeach
                    </div>

                    {{-- SEO Panels --}}
                    @foreach($langs as $lang)
                        @php $ldir = $langMeta[$lang]['dir']; @endphp
                        <div x-show="activeSeoLang === '{{ $lang }}'"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             dir="{{ $ldir }}"
                             class="space-y-4">

                            <div>
                                <label dir="{{ $ldir }}"
                                       class="block mb-1.5 text-sm font-semibold"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                                    {{ __('admin.meta_title') }}
                                </label>
                                <input type="text"
                                       name="meta_title[{{ $lang }}]"
                                       value="{{ old("meta_title.{$lang}", $isEdit ? ($category->translate($lang)?->meta_title ?? '') : '') }}"
                                       maxlength="60"
                                       dir="{{ $ldir }}"
                                       placeholder="{{ __('admin.seo_title_placeholder') }}"
                                       class="w-full px-4 py-2.5 rounded-lg border-2 text-sm transition-all focus:ring-2 focus:ring-accent/40"
                                       :class="darkMode
                                           ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent'
                                           : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
                            </div>

                            <div>
                                <label dir="{{ $ldir }}"
                                       class="block mb-1.5 text-sm font-semibold"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-700'">
                                    {{ __('admin.meta_description') }}
                                </label>
                                <textarea name="meta_description[{{ $lang }}]"
                                          rows="3"
                                          maxlength="160"
                                          dir="{{ $ldir }}"
                                          placeholder="{{ __('admin.seo_desc_placeholder') }}"
                                          class="w-full px-4 py-2.5 rounded-lg border-2 text-sm transition-all focus:ring-2 focus:ring-accent/40 resize-none"
                                          :class="darkMode
                                              ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent'
                                              : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">{{ old("meta_description.{$lang}", $isEdit ? ($category->translate($lang)?->meta_description ?? '') : '') }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             RIGHT / SIDEBAR
        ══════════════════════════════════════════════════ --}}
        <div class="space-y-5">

            {{-- ─── Parent Category ─── --}}
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-5">
                    <label class="block mb-2 font-semibold text-sm"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="folder-tree" class="w-4 h-4 inline me-1.5 text-accent"></i>
                        {{ __('admin.parent_category') }}
                    </label>
                    <select name="parent_id"
                            class="w-full px-4 py-2.5 rounded-lg border-2 text-sm transition-all focus:ring-2 focus:ring-accent/40"
                            :class="darkMode
                                ? 'bg-gray-800 border-gray-700 text-white focus:border-accent'
                                : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        <option value="">{{ __('admin.root_level') }}</option>
                        @foreach($categories ?? [] as $cat)
                            @include('admin.categories._parent-option', [
                                'category' => $cat,
                                'depth'    => 0,
                                'selected' => old('parent_id', $isEdit ? $category->parent_id : null),
                            ])
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        {{ __('admin.leave_empty_root_category') }}
                    </p>
                </div>
            </div>

            {{-- ─── Type ─── --}}
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-5">
                    <label class="block mb-2 font-semibold text-sm"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="tag" class="w-4 h-4 inline me-1.5 text-accent"></i>
                        {{ __('admin.type') }}
                    </label>
                    <select name="type"
                            class="w-full px-4 py-2.5 rounded-lg border-2 text-sm transition-all focus:ring-2 focus:ring-accent/40"
                            :class="darkMode
                                ? 'bg-gray-800 border-gray-700 text-white focus:border-accent'
                                : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        <option value="blog"     {{ old('type', $isEdit ? $category->type->value : 'blog') === 'blog'     ? 'selected' : '' }}>{{ __('admin.blog') }}</option>
                        <option value="media"    {{ old('type', $isEdit ? $category->type->value : 'blog') === 'media'    ? 'selected' : '' }}>{{ __('admin.media') }}</option>
                        <option value="timeline" {{ old('type', $isEdit ? $category->type->value : 'blog') === 'timeline' ? 'selected' : '' }}>{{ __('admin.timeline') }}</option>
                    </select>
                </div>
            </div>

            {{-- ─── Icon & Color ─── --}}
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-5 space-y-5">

                    <div>
                        <label class="block mb-2 font-semibold text-sm"
                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="smile" class="w-4 h-4 inline me-1.5 text-accent"></i>
                            {{ __('admin.icon') }}
                        </label>
                        <x-admin.icon-picker
                            name="icon"
                            :value="old('icon', $isEdit ? $category->icon : 'folder')"
                        />
                    </div>

                    <div>
                        <label class="block mb-2 font-semibold text-sm"
                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="palette" class="w-4 h-4 inline me-1.5 text-accent"></i>
                            {{ __('admin.color') }}
                        </label>
                        <x-admin.color-picker
                            name="color"
                            :value="old('color', $isEdit ? $category->color : '#0056b3')"
                        />
                    </div>
                </div>
            </div>

            {{-- ─── Order & Status ─── --}}
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-5 space-y-5">

                    <div>
                        <label class="block mb-2 font-semibold text-sm"
                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="arrow-up-down" class="w-4 h-4 inline me-1.5 text-accent"></i>
                            {{ __('admin.display_order') }}
                        </label>
                        <input type="number"
                               name="order"
                               value="{{ old('order', $isEdit ? $category->order : 0) }}"
                               min="0"
                               dir="ltr"
                               class="w-full px-4 py-2.5 rounded-lg border-2 text-sm transition-all focus:ring-2 focus:ring-accent/40"
                               :class="darkMode
                                   ? 'bg-gray-800 border-gray-700 text-white focus:border-accent'
                                   : 'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        <p class="mt-1.5 text-xs"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            {{ __('admin.lower_numbers_first') }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-sm"
                              :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="eye" class="w-4 h-4 inline me-1.5 text-accent"></i>
                            {{ __('admin.active_status') }}
                        </span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $isEdit ? $category->is_active : true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 rounded-full transition-colors peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"
                                 :class="darkMode ? 'bg-gray-700' : 'bg-gray-300'"></div>
                            <div class="absolute start-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5 shadow-md"></div>
                        </label>
                    </div>

                </div>
            </div>

            {{-- ─── Action Buttons ─── --}}
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-5 space-y-2.5">

                    <button type="submit"
                            name="action" value="save"
                            class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:opacity-90 hover:scale-[1.02] transition-all shadow-lg flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>{{ __('admin.save_category') }}</span>
                    </button>

                    <button type="submit"
                            name="action" value="save_close"
                            class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm hover:scale-[1.02] transition-all flex items-center justify-center gap-2"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>{{ __('admin.save_and_close') }}</span>
                    </button>

                    <button type="submit"
                            name="action" value="save_new"
                            class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm hover:scale-[1.02] transition-all flex items-center justify-center gap-2"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>{{ __('admin.save_and_new') }}</span>
                    </button>

                    <a href="{{ route('admin.categories.index') }}"
                       class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm border-2 hover:scale-[1.02] transition-all flex items-center justify-center gap-2"
                       :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="x" class="w-4 h-4"></i>
                        <span>{{ __('admin.cancel') }}</span>
                    </a>

                    @if($isEdit)
                        <hr :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        <button type="button"
                                @click="deleteCategory"
                                class="w-full px-5 py-2.5 rounded-lg font-semibold text-sm bg-red-500 text-white hover:bg-red-600 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                            <span>{{ __('admin.delete_category') }}</span>
                        </button>
                    @endif
                </div>
            </div>

        </div>
    </div>
</form>

