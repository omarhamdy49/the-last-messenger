{{--
    Category Form Component
    Used by both create and edit pages
--}}

@php
    $supportedLanguages = ['ar', 'en', 'tr', 'fr', 'ur']; // Hardcoded for now, fix config later
    $isEdit = isset($category) && $category->exists;
@endphp

<form method="POST"
      action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
      x-data="categoryForm()"
      @submit.prevent="handleSubmit"
      class="space-y-8">

    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Main Form (Left Column) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Multi-language Fields Card -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

                <div class="p-6 border-b"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <h2 class="text-xl font-bold flex items-center gap-2"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                        <span>Category Information</span>
                    </h2>
                </div>

                <div class="p-6">

                    <!-- Language Tabs -->
                    <div class="flex flex-wrap gap-2 mb-6 pb-6 border-b"
                         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                        @foreach($supportedLanguages as $lang)
                            @php
                                $langNames = [
                                    'ar' => ['name' => 'العربية', 'flag' => '🇸🇦'],
                                    'en' => ['name' => 'English', 'flag' => '🇬🇧'],
                                    'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷'],
                                    'fr' => ['name' => 'Français', 'flag' => '🇫🇷'],
                                    'ur' => ['name' => 'اردو', 'flag' => '🇵🇰'],
                                ];
                                $langData = $langNames[$lang] ?? ['name' => $lang, 'flag' => '🌐'];
                            @endphp
                            <button type="button"
                                    @click="activeLanguage = '{{ $lang }}'"
                                    class="px-4 py-2 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2"
                                    :class="activeLanguage === '{{ $lang }}'
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg scale-105'
                                        : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                                <span>{{ $langData['flag'] }}</span>
                                <span>{{ $langData['name'] }}</span>
                                @if($errors->has("name.{$lang}") || $errors->has("description.{$lang}"))
                                    <span class="inline-block w-2 h-2 bg-red-500 rounded-full"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <!-- Language Panels -->
                    @foreach($supportedLanguages as $lang)
                        <x-admin.language-tab-panel :language="$lang">

                            <!-- Name Field -->
                            <div class="mb-6">
                                <label class="block mb-2 font-semibold"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                    {{ __('admin.category_name') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="name[{{ $lang }}]"
                                       value="{{ old("name.{$lang}", $isEdit ? $category->translate($lang)?->name : '') }}"
                                       required
                                       dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                       placeholder="Enter category name in {{ $langData['name'] ?? $lang }}"
                                       class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo focus:ring-2 focus:ring-accent/50"
                                       :class="darkMode ?
                                           'bg-gray-900 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                                           'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
                                @error("name.{$lang}")
                                    <div class="mt-2 flex items-start gap-2 text-red-500 text-sm">
                                        <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <!-- Slug Field -->
                            <div class="mb-6">
                                <label class="block mb-2 font-semibold"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                    {{ __('admin.slug') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="slug[{{ $lang }}]"
                                       value="{{ old("slug.{$lang}", $isEdit ? $category->translate($lang)?->slug : '') }}"
                                       required
                                       dir="ltr"
                                       placeholder="category-slug"
                                       class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-mono focus:ring-2 focus:ring-accent/50"
                                       :class="darkMode ?
                                           'bg-gray-900 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                                           'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
                                <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                                    URL-friendly version of the name (lowercase, hyphens only)
                                </p>
                                @error("slug.{$lang}")
                                    <div class="mt-2 flex items-start gap-2 text-red-500 text-sm">
                                        <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                            <!-- Description Field -->
                            <div>
                                <label class="block mb-2 font-semibold"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                    {{ __('admin.description') }}
                                </label>
                                <textarea name="description[{{ $lang }}]"
                                          rows="4"
                                          dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                          placeholder="Brief description of this category..."
                                          class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo focus:ring-2 focus:ring-accent/50"
                                          :class="darkMode ?
                                              'bg-gray-900 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                                              'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">{{ old("description.{$lang}", $isEdit ? $category->translate($lang)?->description : '') }}</textarea>
                                @error("description.{$lang}")
                                    <div class="mt-2 flex items-start gap-2 text-red-500 text-sm">
                                        <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                            </div>

                        </x-admin.language-tab-panel>
                    @endforeach
                </div>
            </div>

            <!-- SEO Fields Card -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

                <div class="p-6 border-b"
                     :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                    <h2 class="text-xl font-bold flex items-center gap-2"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="search" class="w-5 h-5 text-accent"></i>
                        <span>SEO Settings</span>
                    </h2>
                    <p class="text-sm mt-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Optimize for search engines
                    </p>
                </div>

                <div class="p-6" x-data="{ activeSeoLang: '{{ app()->getLocale() }}' }">

                    <!-- SEO Language Tabs -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach($supportedLanguages as $lang)
                            <button type="button"
                                    @click="activeSeoLang = '{{ $lang }}'"
                                    class="px-3 py-1.5 rounded-lg text-sm font-semibold transition-all"
                                    :class="activeSeoLang === '{{ $lang }}'
                                        ? 'bg-accent text-white'
                                        : (darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200')">
                                {{ $langNames[$lang]['flag'] ?? '🌐' }}
                            </button>
                        @endforeach
                    </div>

                    <!-- SEO Panels -->
                    @foreach($supportedLanguages as $lang)
                        <div x-show="activeSeoLang === '{{ $lang }}'"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="space-y-4">

                            <!-- Meta Title -->
                            <div>
                                <label class="block mb-2 font-semibold text-sm"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                    Meta Title
                                </label>
                                <input type="text"
                                       name="meta_title[{{ $lang }}]"
                                       value="{{ old("meta_title.{$lang}", $isEdit ? $category->translate($lang)?->meta_title : '') }}"
                                       maxlength="60"
                                       dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                       placeholder="SEO title (max 60 characters)"
                                       class="w-full px-4 py-2.5 rounded-lg border-2 transition-all text-sm focus:ring-2 focus:ring-accent/50"
                                       :class="darkMode ?
                                           'bg-gray-900 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                                           'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">
                            </div>

                            <!-- Meta Description -->
                            <div>
                                <label class="block mb-2 font-semibold text-sm"
                                       :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                    Meta Description
                                </label>
                                <textarea name="meta_description[{{ $lang }}]"
                                          rows="3"
                                          maxlength="160"
                                          dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                          placeholder="SEO description (max 160 characters)"
                                          class="w-full px-4 py-2.5 rounded-lg border-2 transition-all text-sm focus:ring-2 focus:ring-accent/50"
                                          :class="darkMode ?
                                              'bg-gray-900 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                                              'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">{{ old("meta_description.{$lang}", $isEdit ? $category->translate($lang)?->meta_description : '') }}</textarea>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar (Right Column) -->
        <div class="space-y-6">

            <!-- Parent Category -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-6">
                    <label class="block mb-3 font-semibold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="folder-tree" class="w-5 h-5 inline mr-2 text-accent"></i>
                        Parent Category
                    </label>
                    <select name="parent_id"
                            class="w-full px-4 py-3 rounded-lg border-2 transition-all focus:ring-2 focus:ring-accent/50"
                            :class="darkMode ?
                                'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                                'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        <option value="">-- Root Level --</option>
                        @foreach($categories ?? [] as $cat)
                            @include('admin.categories._parent-option', [
                                'category' => $cat,
                                'depth' => 0,
                                'selected' => old('parent_id', $isEdit ? $category->parent_id : null)
                            ])
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Leave empty for root level category
                    </p>
                </div>
            </div>

            <!-- Icon & Color -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-6 space-y-6">

                    <!-- Icon Picker -->
                    <div>
                        <label class="block mb-3 font-semibold"
                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="smile" class="w-5 h-5 inline mr-2 text-accent"></i>
                            Icon
                        </label>
                        <x-admin.icon-picker
                            name="icon"
                            :value="old('icon', $isEdit ? $category->icon : 'folder')"
                        />
                    </div>

                    <!-- Color Picker -->
                    <div>
                        <label class="block mb-3 font-semibold"
                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="palette" class="w-5 h-5 inline mr-2 text-accent"></i>
                            Color
                        </label>
                        <x-admin.color-picker
                            name="color"
                            :value="old('color', $isEdit ? $category->color : '#0056b3')"
                        />
                    </div>
                </div>
            </div>

            <!-- Featured Image -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-6">
                    <label class="block mb-3 font-semibold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="image" class="w-5 h-5 inline mr-2 text-accent"></i>
                        Featured Image
                    </label>
                    <x-admin.form.file-input
                        name="featured_image_id"
                        :value="old('featured_image_id', $isEdit ? $category->featured_image_id : null)"
                        :preview="$isEdit && $category->featuredImage ? $category->featuredImage->url : null"
                    />
                </div>
            </div>

            <!-- Order & Status -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-6 space-y-6">

                    <!-- Order -->
                    <div>
                        <label class="block mb-3 font-semibold"
                               :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="arrow-up-down" class="w-5 h-5 inline mr-2 text-accent"></i>
                            Display Order
                        </label>
                        <input type="number"
                               name="order"
                               value="{{ old('order', $isEdit ? $category->order : 0) }}"
                               min="0"
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all focus:ring-2 focus:ring-accent/50"
                               :class="darkMode ?
                                   'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                                   'bg-white border-gray-300 text-gray-900 focus:border-accent'">
                        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Lower numbers appear first
                        </p>
                    </div>

                    <!-- Status Toggle -->
                    <div>
                        <label class="flex items-center justify-between cursor-pointer">
                            <span class="font-semibold"
                                  :class="darkMode ? 'text-white' : 'text-gray-900'">
                                <i data-lucide="eye" class="w-5 h-5 inline mr-2 text-accent"></i>
                                Active Status
                            </span>
                            <div class="relative">
                                <input type="checkbox"
                                       name="is_active"
                                       value="1"
                                       {{ old('is_active', $isEdit ? $category->is_active : true) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-14 h-8 rounded-full transition-colors peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"
                                     :class="darkMode ? 'bg-gray-700' : 'bg-gray-300'">
                                </div>
                                <div class="absolute left-1 top-1 w-6 h-6 bg-white rounded-full transition-transform peer-checked:translate-x-6 shadow-lg">
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-6 space-y-3">
                    <button type="submit"
                            name="action"
                            value="save"
                            class="w-full px-6 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        <span>Save Category</span>
                    </button>

                    <button type="submit"
                            name="action"
                            value="save_close"
                            class="w-full px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105 flex items-center justify-center gap-2"
                            :class="darkMode ?
                                'bg-gray-800 text-white hover:bg-gray-700' :
                                'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        <span>Save & Close</span>
                    </button>

                    <button type="submit"
                            name="action"
                            value="save_new"
                            class="w-full px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105 flex items-center justify-center gap-2"
                            :class="darkMode ?
                                'bg-gray-800 text-white hover:bg-gray-700' :
                                'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        <i data-lucide="plus" class="w-5 h-5"></i>
                        <span>Save & New</span>
                    </button>

                    <a href="{{ route('admin.categories.index') }}"
                       class="w-full px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105 flex items-center justify-center gap-2 border-2"
                       :class="darkMode ?
                           'border-gray-700 text-gray-300 hover:bg-gray-800' :
                           'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="x" class="w-5 h-5"></i>
                        <span>Cancel</span>
                    </a>

                    @if($isEdit)
                        <button type="button"
                                @click="deleteCategory"
                                class="w-full px-6 py-3 rounded-lg font-semibold bg-red-500 text-white hover:bg-red-600 transition-all hover:scale-105 flex items-center justify-center gap-2">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                            <span>Delete Category</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
function categoryForm() {
    return {
        activeLanguage: '{{ app()->getLocale() }}',

        handleSubmit(e) {
            // Form will submit normally
            e.target.submit();
        },

        @if($isEdit)
        deleteCategory() {
            window.showConfirm({
                title: '{{ __("admin.confirm_delete") }}',
                message: 'هل أنت متأكد من حذف هذا التصنيف؟ سيتم حذف جميع التصنيفات الفرعية أيضاً.',
                type: 'danger',
                onConfirm: () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("admin.categories.destroy", $category) }}';
                    form.innerHTML = `
                        @csrf
                        @method('DELETE')
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        @endif
    };
}
</script>
@endpush
