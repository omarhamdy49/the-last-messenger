{{--
    Tag Form Component
    Used by both create and edit pages
--}}

@php
    $supportedLanguages = ['ar', 'en', 'tr', 'fr', 'ur']; // Hardcoded for testing
    $isEdit = isset($tag) && $tag->exists;
    $defLang = app()->getLocale();
    if (!in_array($defLang, $supportedLanguages)) { $defLang = 'ar'; }
@endphp

{{-- Define tagForm() BEFORE the form so Alpine can find it when it initialises --}}
<script>
function tagForm() {
    return {
        darkMode: localStorage.getItem('darkMode') === 'true',
        activeLanguage: '{{ $defLang }}',

        handleSubmit(e) {
            e.target.submit();
        },

        @if($isEdit)
        deleteTag() {
            window.showConfirm({
                title: '{{ __("admin.confirm_delete") }}',
                message: '{{ __("admin.delete_warning") }}',
                type: 'danger',
                onConfirm: () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("admin.tags.destroy", $tag) }}';
                    form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
                                   + '<input type="hidden" name="_method" value="DELETE">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        @endif
    };
}
</script>

<form method="POST"
      action="{{ $isEdit ? route('admin.tags.update', $tag) : route('admin.tags.store') }}"
      x-data="tagForm()"
      @submit.prevent="handleSubmit"
      class="max-w-4xl mx-auto space-y-6">

    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <!-- Multi-language Fields Card -->
    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">

        <div class="p-6 border-b"
             :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h2 class="text-xl font-bold flex items-center gap-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                <span>Tag Information</span>
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
                        @if($errors->has("name.{$lang}") || $errors->has("slug.{$lang}"))
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
                            {{ __('admin.tag_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="name[{{ $lang }}]"
                               value="{{ old("name.{$lang}", $isEdit ? $tag->translate($lang)?->name : '') }}"
                               required
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="Enter tag name in {{ $langData['name'] ?? $lang }}"
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
                               value="{{ old("slug.{$lang}", $isEdit ? $tag->translate($lang)?->slug : '') }}"
                               required
                               dir="ltr"
                               placeholder="tag-slug"
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
                                  rows="3"
                                  dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                  placeholder="Brief description of this tag..."
                                  class="w-full px-4 py-3 rounded-lg border-2 transition-all duration-300 font-cairo focus:ring-2 focus:ring-accent/50"
                                  :class="darkMode ?
                                      'bg-gray-900 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                                      'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'">{{ old("description.{$lang}", $isEdit ? $tag->translate($lang)?->description : '') }}</textarea>
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

    <!-- Color Picker Card -->
    <div class="rounded-2xl overflow-hidden shadow-xl border"
         :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6">
            <label class="block mb-3 font-semibold"
                   :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="palette" class="w-5 h-5 inline mr-2 text-accent"></i>
                Tag Color
            </label>
            <x-admin.color-picker
                name="color"
                :value="old('color', $isEdit ? $tag->color : '#0056b3')"
            />
            <p class="mt-3 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Choose a color to distinguish this tag visually
            </p>
        </div>
    </div>

    @if($isEdit && $tag->articles_count > 0)
        <!-- Usage Stats -->
        <div class="rounded-2xl overflow-hidden shadow-xl border"
             :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
            <div class="p-6">
                <h3 class="font-bold mb-4 flex items-center gap-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="bar-chart" class="w-5 h-5 text-accent"></i>
                    Usage Statistics
                </h3>
                <div class="flex items-center justify-between p-4 rounded-lg"
                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        Used in Articles
                    </span>
                    <span class="text-2xl font-bold"
                          :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $tag->articles_count }}
                    </span>
                </div>
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex items-center justify-between gap-4">
        <a href="{{ route('admin.tags.index') }}"
           class="px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ?
               'border-gray-700 text-gray-300 hover:bg-gray-800' :
               'border-gray-300 text-gray-700 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>Cancel</span>
        </a>

        <div class="flex flex-wrap items-center gap-3">
            @if($isEdit)
                <button type="button"
                        @click="deleteTag"
                        class="px-6 py-3 rounded-lg font-semibold bg-red-500 text-white hover:bg-red-600 transition-all hover:scale-105 flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>Delete</span>
                </button>
            @endif

            <button type="submit"
                    class="px-8 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2">
                <i data-lucide="save" class="w-5 h-5"></i>
                <span>{{ $isEdit ? 'Update Tag' : 'Create Tag' }}</span>
            </button>
        </div>
    </div>
</form>
