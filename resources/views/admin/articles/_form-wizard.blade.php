{{--
    Article form as a clickable-step wizard (create + edit).

    Steps are <x-admin.wizard.panel>s; every field keeps its name, so the regular submit,
    old() and server-side validation are unchanged. Quick save (window.adminWizard,
    resources/js/admin/wizard.js) posts the same form over fetch and stays on the page.
--}}

@props([
    'article' => null,
    'formArticle' => null,
    'categories' => [],
    'writers' => [],
    'tags' => [],
    'locales' => array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]))
])

@php
    $isEdit = $article && $article->exists;
    $actionUrl = $isEdit ? route('admin.articles.update', $article) : route('admin.articles.store');
    $localeNames = enabled_content_languages();

    // Steps: key => [icon, error-key prefixes that belong to the step].
    $stepDefs = [
        'basics' => ['file-text', ['title', 'slug', 'description', 'excerpt', 'is_ready']],
        'content' => ['layers', ['content', 'content_blocks', 'summary', 'toc']],
        'classify' => ['folder-tree', ['thumb', 'thumb_id', 'cover', 'cover_id', 'status', 'scheduled_at', 'hijri_date', 'read_time', 'writer_id', 'category_id', 'additional_categories', 'tags', 'is_featured', 'is_pinned']],
        'seo' => ['search', ['seo_title', 'seo_description']],
        'media' => ['images', ['gallery', 'media_items', 'episodes']],
        'details' => ['library', ['references', 'statistics']],
        'review' => ['badge-check', []],
    ];
    $steps = collect($stepDefs)->map(fn (array $def, string $key): array => [
        $def[0], __("wizard.article.steps.{$key}.label"), __("wizard.article.steps.{$key}.hint"),
    ])->all();
    $stepKeys = array_keys($stepDefs);
    $totalSteps = count($stepKeys);
    $matches = fn (string $errorKey, array $prefixes): bool => collect($prefixes)
        ->contains(fn (string $p): bool => $errorKey === $p || str_starts_with($errorKey, $p.'.'));
    $serverStepErrors = collect($stepDefs)
        ->map(fn (array $def): int => collect($errors->keys())->filter(fn (string $k): bool => $matches($k, $def[1]))->count())
        ->filter()
        ->all();

    $wizardMessages = collect(['saving', 'saved', 'unsaved', 'no_changes', 'save_failed', 'fix_errors', 'session_expired', 'network_error', 'saved_toast'])
        ->mapWithKeys(fn (string $k): array => [$k => __("wizard.{$k}")])->all();
    $publicSlug = $isEdit ? ($article->locales->firstWhere('locale', 'ar')?->slug ?? $article->locales->first()?->slug) : null;
    $publicUrl = $isEdit && $article->status === 'published' && $publicSlug ? route('public.articles.show', $publicSlug) : null;
    $statusLabels = collect(['draft', 'pending', 'published', 'scheduled', 'archived'])->mapWithKeys(fn ($s) => [$s => __("articles.status.{$s}")])->all();
@endphp

@push('styles')
@vite('resources/js/admin/rich-text.js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Select2 Dark Mode */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        background-color: transparent;
        border-color: rgb(75, 85, 99);
        border-radius: 0.5rem;
        min-height: 38px;
    }
    .dark .select2-container--default .select2-selection--single,
    .dark .select2-container--default .select2-selection--multiple {
        background-color: rgb(55, 65, 81);
        border-color: rgb(75, 85, 99);
        color: white;
    }
    .select2-container--default .select2-results__option { color: #333; }
    .dark .select2-dropdown { background-color: rgb(55, 65, 81); border-color: rgb(75, 85, 99); }
    .dark .select2-container--default .select2-results__option { color: white; }
    .dark .select2-container--default .select2-results__option--highlighted { background-color: rgb(37, 99, 235); }
    .dark .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: rgb(55, 65, 81); color: white; border-color: rgb(75, 85, 99);
    }

    /* Editor look + typography: resources/css/admin/rich-text.css (.wysiwyg-field).
       Block editors sit inside a bordered block card, so drop the outer frame. */
    /* The pinned toolbar sits below this page's sticky action bar. */
    .wysiwyg-field { --wysiwyg-sticky-top: 1rem; }
    .wysiwyg-field.block-quill-field .ql-toolbar.ql-snow { border-width: 0 0 1px; border-radius: 0; }
    .wysiwyg-field.block-quill-field .ql-container.ql-snow { border: 0; border-radius: 0; }
    .wysiwyg-field.block-quill-field .ql-editor { min-height: 380px; }

    /* Line clamp utility */
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    /* Custom scrollbar for tabs */
    .tabs-scroll::-webkit-scrollbar { height: 2px; }
    .tabs-scroll::-webkit-scrollbar-track { background: transparent; }
    .tabs-scroll::-webkit-scrollbar-thumb { background: rgba(100,204,197,0.4); border-radius: 4px; }
</style>
@endpush

<form action="{{ $actionUrl }}"
      method="POST"
      enctype="multipart/form-data"
      novalidate
      x-data="articleForm(@js($formArticle ?? null), @js($locales), @js([
          'steps' => $stepKeys,
          'stepErrors' => $serverStepErrors,
          'isEdit' => $isEdit,
          'updateUrl' => route('admin.articles.update', '__ID__'),
          'editUrl' => route('admin.articles.edit', '__ID__'),
          'messages' => $wizardMessages,
          'statusLabels' => $statusLabels,
          'stepLabels' => collect($steps)->map(fn ($s) => $s[1]),
          'checkLabels' => __('wizard.article.checks'),
      ]))"
      @submit.prevent="handleSubmit"
      @episodes-selected.window="addEpisodes($event.detail)"
      class="article-form wz-root">

    @csrf
    @if($isEdit) @method('PUT') @endif

    <x-admin.wizard.hero :eyebrow="__('wizard.article.eyebrow')" icon="newspaper" title-expr="formData.title.ar || formData.title[activeLang]" :steps="$steps">
        <x-slot:meta>
            <span class="wz-pill" :class="formData.status === 'published' ? 'is-success' : (formData.status === 'scheduled' ? 'is-accent' : 'is-warn')">
                <span class="wz-dot" aria-hidden="true"></span><span x-text="statusLabels[formData.status] || formData.status"></span>
            </span>
            <span class="wz-pill" x-show="selectedText('category_id')" x-cloak><i data-lucide="folder" class="w-3.5 h-3.5" aria-hidden="true"></i><span x-text="selectedText('category_id')"></span></span>
            <span class="wz-pill" x-show="selectedText('writer_id')" x-cloak><i data-lucide="pen-line" class="w-3.5 h-3.5" aria-hidden="true"></i><span x-text="selectedText('writer_id')"></span></span>
            @if($isEdit)
                <span class="wz-pill"><i data-lucide="eye" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ number_format($article->views ?? 0) }}</span>
                <span class="wz-pill"><i data-lucide="history" class="w-3.5 h-3.5" aria-hidden="true"></i>{{ $article->updated_at?->diffForHumans() }}</span>
            @endif
        </x-slot:meta>
    </x-admin.wizard.hero>

    <x-admin.wizard.errors />

    <div class="wz-layout">
        <div class="min-w-0">

            {{-- ═══════ 1. BASICS ═══════ --}}
            <x-admin.wizard.panel key="basics" :index="1" :total="$totalSteps" icon="file-text"
                :title="__('wizard.article.steps.basics.label')" :description="__('wizard.article.steps.basics.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$localeNames" model="activeLang" /></x-slot:actions>
                        @foreach($locales as $locale)
                        <div x-show="activeLang === '{{ $locale }}'" x-transition class="space-y-4">

                            {{-- Title --}}
                            <div>
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.fields.title') }} ({{ strtoupper($locale) }})
                                    @if($locale === 'ar')<span class="text-red-500">*</span>@endif
                                </label>
                                <input type="text"
                                       name="title[{{ $locale }}]"
                                       x-model="formData.title.{{ $locale }}"
                                       @input="generateSlug('{{ $locale }}')"
                                       @if($locale === 'ar') required @endif
                                       placeholder="{{ __('articles.placeholders.title') }}"
                                       class="w-full px-4 py-2.5 rounded-lg border text-base focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent transition-all"
                                       :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                       @if(in_array($locale, ['ar', 'ur'])) dir="rtl" @endif>
                            </div>

                            {{-- Slug --}}
                            <div>
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.fields.slug') }}
                                </label>
                                <input type="text"
                                       name="slug[{{ $locale }}]"
                                       x-model="formData.slug.{{ $locale }}"
                                       @input="onSlugManualEdit('{{ $locale }}')"
                                       class="w-full px-4 py-2 rounded-lg border font-mono text-sm focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                       :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                       dir="ltr">
                                <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                    {{ __('articles.help.slug') }}
                                </p>
                            </div>

                            {{-- Description (Quill) --}}
                            <div class="wysiwyg-field" data-default-dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.fields.description') }}
                                </label>
                                <div id="description_{{ $locale }}_editor" class="quill-editor" style="min-height: 120px;"></div>
                                <textarea name="description[{{ $locale }}]"
                                          id="description_{{ $locale }}"
                                          class="hidden">{{ old("description.{$locale}", $article && $article->getArticleLocale($locale) ? $article->getArticleLocale($locale)->description : '') }}</textarea>
                            </div>

                            {{-- Excerpt --}}
                            <div>
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.fields.excerpt') }}
                                </label>
                                <textarea name="excerpt[{{ $locale }}]"
                                          x-model="formData.excerpt.{{ $locale }}"
                                          rows="3" maxlength="500"
                                          placeholder="{{ __('articles.placeholders.excerpt') }}"
                                          class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                          :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                          @if(in_array($locale, ['ar', 'ur'])) dir="rtl" @endif></textarea>
                                <p class="text-xs mt-1 text-end" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                    <span x-text="(formData.excerpt?.{{ $locale }} || '').length"></span> / 500
                                </p>
                            </div>

                            {{-- Is Ready --}}
                            <label class="flex items-center gap-2 cursor-pointer p-3 rounded-lg border"
                                   :class="darkMode ? 'border-gray-700 hover:bg-gray-700/50' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="checkbox" name="is_ready[{{ $locale }}]" value="1"
                                       x-model="formData.is_ready.{{ $locale }}"
                                       class="w-5 h-5 rounded text-green-600 focus:ring-green-500">
                                <span class="text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ __('articles.fields.is_ready') }} ({{ strtoupper($locale) }})
                                </span>
                            </label>
                        </div>
                        @endforeach
            </x-admin.wizard.panel>

            {{-- ═══════ 2. CONTENT ═══════ --}}
            <x-admin.wizard.panel key="content" :index="2" :total="$totalSteps" icon="layers"
                :title="__('wizard.article.steps.content.label')" :description="__('wizard.article.steps.content.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$localeNames" model="activeLang" /></x-slot:actions>
                        @foreach($locales as $locale)
                        <div x-show="activeLang === '{{ $locale }}'" x-transition class="space-y-4">

                            {{-- ═══ BLOCK-BASED CONTENT BUILDER ═══ --}}
                            <div>
                                <label class="block text-sm font-semibold mb-2"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.fields.body') }} ({{ strtoupper($locale) }})
                                    <span class="text-red-500">*</span>
                                </label>

                                {{-- Add Block Toolbar --}}
                                <div class="flex flex-wrap gap-2 p-3 rounded-t-lg border border-b-0"
                                     :class="darkMode ? 'bg-gray-900/50 border-gray-700' : 'bg-gray-50 border-gray-200'">
                                    <span class="text-xs font-medium self-center me-2" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                        {{ __('articles.blocks.add') }}:
                                    </span>
                                    <button type="button" @click="addBlock('wysiwyg', '{{ $locale }}')"
                                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1.5"
                                            :class="darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-sm'">
                                        <i data-lucide="type" class="w-3.5 h-3.5"></i>{{ __('articles.blocks.text') }}
                                    </button>
                                    {{-- Quran, Hadith and Sahabi are inserted from inside the WYSIWYG
                                         toolbar (.ql-module-btn--*), so they embed in the text where the
                                         writer is typing. Standalone block buttons for them were a second,
                                         duplicate insertion path and are gone. --}}
                                    <button type="button" @click="openModuleSelector('seerah', '{{ $locale }}')"
                                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors flex items-center gap-1.5 bg-purple-100 text-purple-700 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:hover:bg-purple-900/50">
                                        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>{{ __('articles.modules.seerah') }}
                                    </button>
                                </div>

                                {{-- Blocks Container --}}
                                <div class="border rounded-b-lg min-h-[200px] p-3 space-y-3"
                                     :class="darkMode ? 'border-gray-700 bg-gray-800/50' : 'border-gray-200 bg-white'">

                                    {{-- Empty State --}}
                                    <div x-show="!getBlocks('{{ $locale }}').length"
                                         class="text-center py-12 border-2 border-dashed rounded-lg"
                                         :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                                        <i data-lucide="layers" class="w-10 h-10 mx-auto mb-2 text-gray-400"></i>
                                        <p class="text-sm mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('articles.blocks.empty') }}</p>
                                        <button type="button" @click="addBlock('wysiwyg', '{{ $locale }}')"
                                                class="text-sm text-[#0056b3] hover:underline">{{ __('articles.blocks.add_first') }}</button>
                                    </div>

                                    {{-- Block List --}}
                                    <template x-for="(block, blockIdx) in getBlocks('{{ $locale }}')" :key="block.id">
                                        <div class="group relative rounded-lg border transition-all"
                                             :class="{
                                                'border-gray-600 bg-gray-800/30': darkMode && block.type === 'wysiwyg',
                                                'border-gray-200 bg-white': !darkMode && block.type === 'wysiwyg',
                                                'border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-900/20': block.type === 'verse',
                                                'border-blue-300 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20': block.type === 'hadith',
                                                'border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20': block.type === 'sahabi',
                                                'border-purple-300 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/20': block.type === 'seerah'
                                             }">

                                            {{-- Block Controls --}}
                                            <div class="absolute -start-10 top-1/2 -translate-y-1/2 flex-col gap-1 hidden group-hover:flex z-10">
                                                <button type="button" @click="moveBlock('{{ $locale }}', blockIdx, -1)"
                                                        :disabled="blockIdx === 0"
                                                        class="p-1 rounded bg-white dark:bg-gray-700 shadow border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-gray-700 disabled:opacity-30">
                                                    <i data-lucide="chevron-up" class="w-3.5 h-3.5"></i>
                                                </button>
                                                <button type="button" @click="moveBlock('{{ $locale }}', blockIdx, 1)"
                                                        :disabled="blockIdx === getBlocks('{{ $locale }}').length - 1"
                                                        class="p-1 rounded bg-white dark:bg-gray-700 shadow border border-gray-200 dark:border-gray-600 text-gray-500 hover:text-gray-700 disabled:opacity-30">
                                                    <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </div>

                                            {{-- Block Header Bar (clickable to collapse/expand) --}}
                                            <div class="flex items-center justify-between px-3 py-1.5 border-b text-xs cursor-pointer select-none"
                                                 :class="[darkMode ? 'border-gray-700' : 'border-gray-200', block._collapsed ? 'border-b-0' : '']"
                                                 @click="block._collapsed = !block._collapsed; $nextTick(() => lucide.createIcons())">
                                                <div class="flex items-center gap-2 font-medium"
                                                     :class="{
                                                        'text-gray-500': block.type === 'wysiwyg',
                                                        'text-green-600 dark:text-green-400': block.type === 'verse',
                                                        'text-blue-600 dark:text-blue-400': block.type === 'hadith',
                                                        'text-amber-600 dark:text-amber-400': block.type === 'sahabi',
                                                        'text-purple-600 dark:text-purple-400': block.type === 'seerah'
                                                     }">
                                                    <i :data-lucide="block._collapsed ? 'chevron-down' : 'chevron-up'" class="w-3.5 h-3.5 transition-transform"></i>
                                                    <i :data-lucide="block.type === 'wysiwyg' ? 'type' : block.type === 'verse' ? 'book-open' : block.type === 'hadith' ? 'quote' : block.type === 'sahabi' ? 'users' : 'map-pin'" class="w-3.5 h-3.5"></i>
                                                    <span x-text="block.type === 'wysiwyg' ? '{{ __('articles.blocks.text') }}' : block.type === 'verse' ? '{{ __('articles.modules.quran') }}' : block.type === 'hadith' ? '{{ __('articles.modules.hadith') }}' : block.type === 'sahabi' ? '{{ __('articles.modules.sahabah') }}' : '{{ __('articles.modules.seerah') }}'"></span>
                                                    {{-- Collapsed preview snippet --}}
                                                    <span x-show="block._collapsed && block.type === 'wysiwyg'" class="text-gray-400 dark:text-gray-500 truncate max-w-[200px]"
                                                          x-text="(block.content || '').replace(/<[^>]*>/g, '').substring(0, 50) + '...'"></span>
                                                    <span x-show="block._collapsed && block.type === 'verse'" class="text-gray-400 dark:text-gray-500"
                                                          x-text="block.surah_name || ''"></span>
                                                    <span x-show="block._collapsed && block.type === 'hadith'" class="text-gray-400 dark:text-gray-500 truncate max-w-[200px]"
                                                          x-text="(block.text || '').substring(0, 50) + '...'"></span>
                                                    <span x-show="block._collapsed && block.type === 'sahabi'" class="text-gray-400 dark:text-gray-500"
                                                          x-text="block.name || ''"></span>
                                                    <span x-show="block._collapsed && block.type === 'seerah'" class="text-gray-400 dark:text-gray-500"
                                                          x-text="block.title || ''"></span>
                                                </div>
                                                <div class="flex items-center gap-1" @click.stop>
                                                    <button type="button" @click="moveBlock('{{ $locale }}', blockIdx, -1)"
                                                            :disabled="blockIdx === 0"
                                                            class="p-1 rounded hover:bg-black/10 dark:hover:bg-white/10 disabled:opacity-30">
                                                        <i data-lucide="arrow-up" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <button type="button" @click="moveBlock('{{ $locale }}', blockIdx, 1)"
                                                            :disabled="blockIdx === getBlocks('{{ $locale }}').length - 1"
                                                            class="p-1 rounded hover:bg-black/10 dark:hover:bg-white/10 disabled:opacity-30">
                                                        <i data-lucide="arrow-down" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <button type="button" @click="removeBlock('{{ $locale }}', blockIdx)"
                                                            class="p-1 rounded text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30">
                                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Collapsible Content --}}
                                            <div x-show="!block._collapsed" x-collapse>

                                            {{-- WYSIWYG Block --}}
                                            <div x-show="block.type === 'wysiwyg'" class="p-0 wysiwyg-field block-quill-field" data-default-dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                                <div :id="'block_editor_' + block.id" class="quill-editor block-quill"
                                                     x-init="$nextTick(() => initBlockQuill(block.id, '{{ $locale }}'))"></div>
                                            </div>

                                            {{-- Verse Block --}}
                                            <div x-show="block.type === 'verse'" class="p-4" dir="rtl">
                                                <template x-if="block.verses && block.verses.length">
                                                    <div class="space-y-3">
                                                        <template x-for="verse in block.verses" :key="verse.number">
                                                            <p class="text-lg leading-loose font-arabic" style="font-family: 'Amiri', 'Cairo', serif;"
                                                               :class="darkMode ? 'text-green-200' : 'text-green-900'">
                                                                <span x-text="verse.text"></span>
                                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold ms-1"
                                                                      :class="darkMode ? 'bg-green-800 text-green-200' : 'bg-green-200 text-green-800'"
                                                                      x-text="verse.number"></span>
                                                            </p>
                                                        </template>
                                                        <div class="flex items-center gap-2 pt-2 border-t"
                                                             :class="darkMode ? 'border-green-800' : 'border-green-200'">
                                                            <i data-lucide="book-open" class="w-4 h-4 text-green-600"></i>
                                                            <span class="text-sm font-semibold" :class="darkMode ? 'text-green-300' : 'text-green-700'"
                                                                  x-text="block.surah_name"></span>
                                                            <span class="text-xs" :class="darkMode ? 'text-green-400' : 'text-green-600'"
                                                                  x-text="block.verses.length > 1 ? ('{{ __('articles.blocks.verses') }} ' + block.verses[0].number + '-' + block.verses[block.verses.length-1].number) : ('{{ __('articles.blocks.verse') }} ' + block.verses[0].number)"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            {{-- Hadith Block --}}
                                            <div x-show="block.type === 'hadith'" class="p-4">
                                                <div class="space-y-3">
                                                    <p class="text-base leading-relaxed"
                                                       :class="darkMode ? 'text-blue-200' : 'text-blue-900'"
                                                       :dir="'{{ in_array($locale, ['ar','ur']) ? 'rtl' : 'ltr' }}'"
                                                       x-text="block.text"></p>
                                                    <div class="flex flex-wrap items-center gap-2 pt-2 border-t"
                                                         :class="darkMode ? 'border-blue-800' : 'border-blue-200'">
                                                        <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                                              :class="darkMode ? 'bg-blue-800 text-blue-200' : 'bg-blue-200 text-blue-800'"
                                                              x-text="block.collection"></span>
                                                        <span class="text-xs" :class="darkMode ? 'text-blue-400' : 'text-blue-600'"
                                                              x-text="'#' + block.number"></span>
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium"
                                                              :class="{
                                                                'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': block.grade === 'sahih',
                                                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300': block.grade === 'hasan',
                                                                'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': block.grade === 'daif' || block.grade === 'mawdu'
                                                              }"
                                                              x-text="block.grade"></span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Sahabi Block --}}
                                            <div x-show="block.type === 'sahabi'" class="p-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                                                         :class="darkMode ? 'bg-amber-900/50' : 'bg-amber-100'">
                                                        <i data-lucide="user" class="w-6 h-6 text-amber-600"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-base" :class="darkMode ? 'text-amber-200' : 'text-amber-900'" x-text="block.name"></p>
                                                        <p class="text-sm" :class="darkMode ? 'text-amber-400' : 'text-amber-600'" x-text="block.kunya" x-show="block.kunya"></p>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Seerah Block --}}
                                            <div x-show="block.type === 'seerah'" class="p-4">
                                                <div class="flex items-start gap-3">
                                                    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0"
                                                         :class="darkMode ? 'bg-purple-900/50' : 'bg-purple-100'">
                                                        <i data-lucide="calendar" class="w-6 h-6 text-purple-600"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-base" :class="darkMode ? 'text-purple-200' : 'text-purple-900'" x-text="block.title"></p>
                                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                                            <span class="text-xs" :class="darkMode ? 'text-purple-400' : 'text-purple-600'"
                                                                  x-text="block.hijri_year ? (block.hijri_year + ' {{ __('articles.blocks.hijri') }}') : ''"></span>
                                                            <span x-show="block.event_type"
                                                                  class="px-2 py-0.5 rounded text-xs font-medium"
                                                                  :class="darkMode ? 'bg-purple-800 text-purple-200' : 'bg-purple-200 text-purple-800'"
                                                                  x-text="block.event_type"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            </div>{{-- end collapsible --}}
                                        </div>
                                    </template>
                                </div>

                                {{-- Hidden textarea for serialized blocks --}}
                                <textarea name="content_blocks[{{ $locale }}]"
                                          id="content_blocks_{{ $locale }}"
                                          class="hidden"></textarea>
                            </div>

                            {{-- TOC Manager --}}
                            <x-admin.toc-manager
                                name="toc"
                                :locale="$locale"
                                :content="'content_' . $locale"
                                :values="old('toc.' . $locale, $article && $article->getArticleLocale($locale) ? ($article->getArticleLocale($locale)->content['toc'] ?? []) : [])"
                                :label="__('articles.fields.toc')"
                            />

                            {{-- Summary (Quill) --}}
                            <div class="wysiwyg-field" data-default-dir="{{ in_array($locale, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.fields.summary') }}
                                </label>
                                <div id="summary_{{ $locale }}_editor" class="quill-editor" style="min-height: 120px;"></div>
                                <textarea name="summary[{{ $locale }}]"
                                          id="summary_{{ $locale }}"
                                          class="hidden">{{ old("summary.{$locale}", $article && $article->getArticleLocale($locale) ? ($article->getArticleLocale($locale)->content['summary'] ?? '') : '') }}</textarea>
                            </div>
                        </div>
                        @endforeach
            </x-admin.wizard.panel>

            {{-- ═══════ 3. CLASSIFY ═══════ --}}
            <x-admin.wizard.panel key="classify" :index="3" :total="$totalSteps" icon="folder-tree"
                :title="__('wizard.article.steps.classify.label')" :description="__('wizard.article.steps.classify.desc')">
                <div class="wz-classify">
                    <x-admin.article.sidebar
                        :article="$article"
                        :formArticle="$formArticle"
                        :categories="$categories"
                        :writers="$writers"
                        :tags="$tags"
                        :currentHijriDate="$currentHijriDate ?? ''"
                    />
                </div>
            </x-admin.wizard.panel>

            {{-- ═══════ 4. SEO ═══════ --}}
            <x-admin.wizard.panel key="seo" :index="4" :total="$totalSteps" icon="search"
                :title="__('wizard.article.steps.seo.label')" :description="__('wizard.article.steps.seo.desc')">
                <x-slot:actions><x-admin.wizard.langs :languages="$localeNames" model="activeLang" /></x-slot:actions>
                        @foreach($locales as $locale)
                        <div x-show="activeLang === '{{ $locale }}'" x-transition class="space-y-5">

                            {{-- SEO Title --}}
                            <div>
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.seo.title') }}
                                    <span class="text-gray-400 font-normal text-xs">({{ __('common.optional') }})</span>
                                </label>
                                <input type="text" name="seo_title[{{ $locale }}]"
                                       x-model="formData.seo_title['{{ $locale }}']" maxlength="70"
                                       class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                       :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                       @if(in_array($locale, ['ar', 'ur'])) dir="rtl" @endif>
                                <div class="flex justify-between mt-1">
                                    <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ __('articles.help.seo_title_length') }}
                                    </p>
                                    <span class="text-xs"
                                          :class="(formData.seo_title['{{ $locale }}']?.length || 0) > 60 ? 'text-orange-500' : (darkMode ? 'text-gray-500' : 'text-gray-400')">
                                        <span x-text="formData.seo_title['{{ $locale }}']?.length || 0"></span>/70
                                    </span>
                                </div>
                            </div>

                            {{-- SEO Description --}}
                            <div>
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.seo.description') }}
                                    <span class="text-gray-400 font-normal text-xs">({{ __('common.optional') }})</span>
                                </label>
                                <textarea name="seo_description[{{ $locale }}]"
                                          x-model="formData.seo_description['{{ $locale }}']"
                                          rows="3" maxlength="160"
                                          class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-[#64CCC5] focus:border-transparent"
                                          :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                          @if(in_array($locale, ['ar', 'ur'])) dir="rtl" @endif></textarea>
                                <div class="flex justify-between mt-1">
                                    <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ __('articles.help.seo_desc_length') }}
                                    </p>
                                    <span class="text-xs"
                                          :class="(formData.seo_description['{{ $locale }}']?.length || 0) > 155 ? 'text-orange-500' : (darkMode ? 'text-gray-500' : 'text-gray-400')">
                                        <span x-text="formData.seo_description['{{ $locale }}']?.length || 0"></span>/160
                                    </span>
                                </div>
                            </div>

                            {{-- Search Preview --}}
                            <div class="rounded-lg border p-4"
                                 :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-gray-50 border-gray-200'">
                                <h4 class="text-xs font-semibold mb-3 uppercase tracking-wide"
                                    :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                                    {{ __('articles.seo.preview') }}
                                </h4>
                                <div class="space-y-1">
                                    <div class="text-blue-600 text-lg font-medium">
                                        <span x-text="formData.seo_title['{{ $locale }}'] || formData.title['{{ $locale }}'] || '{{ __('articles.fields.title') }}'"></span>
                                    </div>
                                    <div class="text-sm text-green-700 flex items-center gap-1">
                                        <span>{{ config('app.url') }}</span>
                                        <span>/</span>
                                        <span x-text="formData.slug['{{ $locale }}'] || 'article-slug'"></span>
                                    </div>
                                    <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        <span x-text="formData.seo_description['{{ $locale }}'] || formData.excerpt?.['{{ $locale }}'] || '...'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
            </x-admin.wizard.panel>

            {{-- ═══════ 5. MEDIA ═══════ --}}
            <x-admin.wizard.panel key="media" :index="5" :total="$totalSteps" icon="images"
                :title="__('wizard.article.steps.media.label')" :description="__('wizard.article.steps.media.desc')">
                <div class="wz-section">
                        <div class="flex items-center justify-between">
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ __('articles.help.gallery') }}
                            </p>
                            <button type="button"
                                    @click="$dispatch('open-media-library', { target: 'gallery', multiple: true })"
                                    class="wz-btn is-soft">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                {{ __('articles.fields.add_image') }}
                            </button>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3" x-show="formData.gallery.length > 0">
                            <template x-for="(item, index) in formData.gallery" :key="item.id">
                                <div class="relative group rounded-lg overflow-hidden border-2"
                                     :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                                    <img :src="item.url" :alt="item.caption" class="w-full aspect-square object-cover">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <button type="button" @click="removeGalleryItem(index)"
                                                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" :name="'gallery[' + index + ']'" :value="item.id">
                                </div>
                            </template>
                        </div>

                        <div x-show="formData.gallery.length === 0"
                             class="wz-empty"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                            <i data-lucide="images" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
                            <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('articles.empty.gallery') }}</p>
                        </div>
                </div>
                <div class="wz-section">
                        <div class="flex items-center justify-between">
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ __('articles.help.media') }}
                            </p>
                            <button type="button"
                                    @click="$dispatch('open-media-library', { target: 'media_items', multiple: true })"
                                    class="wz-btn is-soft">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                {{ __('articles.fields.add_media') }}
                            </button>
                        </div>

                        <div class="space-y-2" x-show="formData.media_items.length > 0">
                            <template x-for="(item, index) in formData.media_items" :key="item.id">
                                <div class="flex items-center gap-3 p-3 rounded-lg border"
                                     :class="darkMode ? 'bg-gray-700/50 border-gray-600' : 'bg-gray-50 border-gray-200'">
                                    <template x-if="item.thumbnail_url">
                                        <img :src="item.thumbnail_url" class="w-12 h-12 rounded-lg object-cover flex-shrink-0" alt="">
                                    </template>
                                    <template x-if="!item.thumbnail_url">
                                        <i data-lucide="video" class="w-8 h-8 text-gray-400 flex-shrink-0"></i>
                                    </template>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.title || item.original_filename"></p>
                                        <p class="text-xs text-gray-500" x-text="item.type || item.mime_type"></p>
                                    </div>
                                    <button type="button" @click="removeMediaItem(index)"
                                            class="p-2 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg flex-shrink-0">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                    <input type="hidden" :name="'media_items[' + index + ']'" :value="item.id">
                                </div>
                            </template>
                        </div>

                        <div x-show="formData.media_items.length === 0"
                             class="wz-empty"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                            <i data-lucide="video" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
                            <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('articles.empty.media') }}</p>
                        </div>

                        {{-- ── Video queue: linked episodes ──────────────────
                             Articles cite existing episodes rather than carrying
                             their own video links, so this is a picker over the
                             episode catalogue, not an upload field. --}}
                        <div class="pt-6 mt-6 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-semibold text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.episodes') }}
                                    </h4>
                                    <p class="text-xs mt-0.5" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        {{ __('articles.help.episodes') }}
                                    </p>
                                </div>
                                <button type="button" @click="$dispatch('open-episode-picker')"
                                        class="wz-btn is-soft shrink-0">
                                    <i data-lucide="list-video" class="w-4 h-4"></i>
                                    {{ __('articles.actions.assign_episodes') }}
                                </button>
                            </div>

                            <div class="space-y-2" x-show="formData.episodes.length > 0">
                                <template x-for="(ep, index) in formData.episodes" :key="ep.id">
                                    <div class="flex items-center gap-3 p-2 rounded-lg border"
                                         :class="darkMode ? 'bg-gray-700/50 border-gray-600' : 'bg-gray-50 border-gray-200'">
                                        <span class="w-6 text-center text-xs font-bold text-gray-400 shrink-0" x-text="index + 1"></span>
                                        <img :src="ep.thumb" x-show="ep.thumb" alt=""
                                             class="w-20 h-12 rounded object-cover bg-gray-200 dark:bg-gray-800 shrink-0">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-sm truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="ep.title"></p>
                                            <p class="text-xs text-gray-500 truncate">
                                                <span x-text="ep.program"></span>
                                                <span x-show="ep.season">· S<span x-text="ep.season"></span></span>
                                                <span x-show="ep.episode">E<span x-text="ep.episode"></span></span>
                                            </p>
                                        </div>
                                        {{-- Reorder: the queue plays in this order. --}}
                                        <button type="button" @click="moveEpisode(index, -1)" :disabled="index === 0"
                                                class="p-1.5 rounded disabled:opacity-30 hover:bg-gray-200 dark:hover:bg-gray-600"
                                                aria-label="{{ __('articles.actions.move_up') }}">
                                            <i data-lucide="chevron-up" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="moveEpisode(index, 1)" :disabled="index === formData.episodes.length - 1"
                                                class="p-1.5 rounded disabled:opacity-30 hover:bg-gray-200 dark:hover:bg-gray-600"
                                                aria-label="{{ __('articles.actions.move_down') }}">
                                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" @click="formData.episodes.splice(index, 1)"
                                                class="p-2 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg shrink-0"
                                                aria-label="{{ __('articles.actions.remove') }}">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>
                                        <input type="hidden" :name="'episodes[' + index + ']'" :value="ep.id">
                                    </div>
                                </template>
                            </div>

                            <div x-show="formData.episodes.length === 0"
                                 class="wz-empty"
                                 :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                                <i data-lucide="list-video" class="w-10 h-10 mx-auto mb-2 text-gray-400"></i>
                                <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('articles.empty.episodes') }}</p>
                            </div>
                        </div>
                </div>
            </x-admin.wizard.panel>

            {{-- ═══════ 6. DETAILS ═══════ --}}
            <x-admin.wizard.panel key="details" :index="6" :total="$totalSteps" icon="library"
                :title="__('wizard.article.steps.details.label')" :description="__('wizard.article.steps.details.desc')">
                <div class="wz-section">
                        <x-admin.repeatable-field
                            name="references"
                            :label="__('articles.fields.references')"
                            :fields="[
                                ['name' => 'name', 'label' => __('articles.fields.reference_name'), 'type' => 'text', 'placeholder' => __('articles.fields.reference_name')],
                                {{-- type=text: type=url makes the browser block submit on a non-URL value
                                     inside a hidden tab ("invalid form control is not focusable") --}}
                                ['name' => 'url', 'label' => __('articles.fields.reference_url'), 'type' => 'text', 'placeholder' => 'https://...'],
                                ['name' => 'type', 'label' => __('articles.fields.reference_type'), 'type' => 'text', 'placeholder' => __('articles.fields.reference_type')]
                            ]"
                            :values="old('references', $article ? $article->references : [])"
                            :addButtonText="__('articles.fields.add_reference')"
                            :emptyMessage="__('common.no_items')"
                            :help="__('articles.help.references')"
                        />

                        <x-admin.repeatable-field
                            name="statistics"
                            :label="__('articles.fields.statistics')"
                            :fields="[
                                ['name' => 'name', 'label' => __('articles.fields.stat_name'), 'type' => 'text', 'placeholder' => __('articles.fields.stat_name')],
                                ['name' => 'value', 'label' => __('articles.fields.stat_value'), 'type' => 'text', 'placeholder' => __('articles.fields.stat_value')]
                            ]"
                            :values="old('statistics', $article ? $article->statistics : [])"
                            :addButtonText="__('articles.fields.add_stat')"
                            :emptyMessage="__('common.no_items')"
                            :help="__('articles.help.statistics')"
                        />
                </div>
            </x-admin.wizard.panel>

            {{-- ═══════ 7. REVIEW ═══════ --}}
            <x-admin.wizard.panel key="review" :index="7" :total="$totalSteps" icon="badge-check"
                :title="__('wizard.article.steps.review.label')" :description="__('wizard.article.steps.review.desc')">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="wz-card p-4 !shadow-none">
                        <p class="text-2xl font-extrabold tabular-nums" :class="reviewCount('required') ? 'text-red-600' : 'text-[color:var(--wz-success)]'" x-text="reviewCount('required')"></p>
                        <p class="wz-help !mt-1">{{ __('wizard.required') }}</p>
                    </div>
                    <div class="wz-card p-4 !shadow-none">
                        <p class="text-2xl font-extrabold tabular-nums text-[color:var(--wz-warn)]" x-text="reviewCount('recommended')"></p>
                        <p class="wz-help !mt-1">{{ __('wizard.recommended') }}</p>
                    </div>
                    <div class="wz-card p-4 !shadow-none">
                        <p class="text-2xl font-extrabold tabular-nums text-[color:var(--wz-success)]" x-text="reviewChecks().filter((c) => c.ok).length"></p>
                        <p class="wz-help !mt-1">{{ __('wizard.ok') }}</p>
                    </div>
                </div>

                <p class="wz-pill !min-h-9 !px-4 w-fit" :class="formData.status === 'published' ? 'is-success' : 'is-warn'">
                    <i data-lucide="info" class="w-4 h-4" aria-hidden="true"></i>
                    <span x-text="formData.status === 'published' ? @js(__('wizard.published_note')) : @js(__('wizard.draft_note'))"></span>
                </p>

                <div class="wz-checks">
                    <template x-for="check in reviewChecks()" :key="check.key">
                        <button type="button" class="wz-check" :class="check.ok ? 'is-ok' : (check.level === 'required' ? 'is-required' : 'is-warn')" @click="goTo(check.step)">
                            <span class="wz-check-icon">
                                <span x-show="check.ok"><i data-lucide="check" aria-hidden="true"></i></span>
                                <span x-show="!check.ok && check.level === 'required'"><i data-lucide="x" aria-hidden="true"></i></span>
                                <span x-show="!check.ok && check.level !== 'required'"><i data-lucide="lightbulb" aria-hidden="true"></i></span>
                            </span>
                            <span class="min-w-0">
                                <span class="wz-check-title block" x-text="check.label"></span>
                                <span class="wz-check-detail block" x-text="check.ok ? @js(__('wizard.ok')) : (check.level === 'required' ? @js(__('wizard.required')) : @js(__('wizard.recommended')))"></span>
                            </span>
                            <span class="wz-check-go" x-text="stepLabels[check.step] || check.step"></span>
                        </button>
                    </template>
                </div>
            </x-admin.wizard.panel>

            <x-admin.wizard.command-bar :preview-url="$isEdit ? route('admin.articles.show', $article) : null" :total="$totalSteps" />
        </div>

        {{-- ═══════ RAIL ═══════ --}}
        <aside class="wz-rail" aria-label="{{ __('wizard.summary') }}">
            <div class="wz-card wz-rail-card">
                <p class="wz-rail-title"><i data-lucide="panel-top" aria-hidden="true"></i>{{ __('wizard.summary') }}</p>
                <div class="wz-summary-media">
                    <template x-if="coverPreview || thumbnailPreview"><img :src="coverPreview || thumbnailPreview" alt=""></template>
                    <template x-if="!coverPreview && !thumbnailPreview"><i data-lucide="image" class="w-8 h-8" aria-hidden="true"></i></template>
                </div>
                <p class="mt-3 font-bold leading-snug break-words" :class="darkMode ? 'text-white' : 'text-slate-900'" x-text="formData.title.ar || @js(__('wizard.untitled'))"></p>
                <dl class="wz-kv">
                    <div><dt>{{ __('wizard.article.rail.status') }}</dt><dd x-text="statusLabels[formData.status] || formData.status"></dd></div>
                    <div><dt>{{ __('wizard.article.rail.writer') }}</dt><dd x-text="selectedText('writer_id') || @js(__('wizard.not_set'))"></dd></div>
                    <div><dt>{{ __('wizard.article.rail.category') }}</dt><dd x-text="selectedText('category_id') || @js(__('wizard.not_set'))"></dd></div>
                    <div><dt>{{ __('wizard.article.rail.blocks') }}</dt><dd class="tabular-nums" x-text="getTotalBlockCount()"></dd></div>
                    <div><dt>{{ __('wizard.article.rail.gallery') }}</dt><dd class="tabular-nums" x-text="formData.gallery.length"></dd></div>
                    <div><dt>{{ __('wizard.article.rail.episodes') }}</dt><dd class="tabular-nums" x-text="formData.episodes.length"></dd></div>
                </dl>
            </div>

            <div class="wz-card wz-rail-card">
                <p class="wz-rail-title"><i data-lucide="languages" aria-hidden="true"></i>{{ __('wizard.languages') }}</p>
                <ul class="grid gap-2">
                    @foreach($localeNames as $code => $langLabel)
                        <li class="flex items-center justify-between gap-2 text-sm">
                            <button type="button" class="font-semibold hover:underline" :class="darkMode ? 'text-slate-200' : 'text-slate-700'" @click="activeLang = '{{ $code }}'; goTo('basics')">{{ $langLabel }}</button>
                            <span class="wz-pill !min-h-6" :class="formData.is_ready['{{ $code }}'] ? 'is-success' : (langFilled('{{ $code }}') ? 'is-warn' : '')">
                                <span class="wz-dot" aria-hidden="true"></span>
                                <span x-text="formData.is_ready['{{ $code }}'] ? @js(__('wizard.show.ready')) : (langFilled('{{ $code }}') ? @js(__('wizard.show.not_ready')) : @js(__('wizard.show.missing')))"></span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="wz-card wz-rail-card grid gap-2">
                @if($publicUrl)
                    <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="wz-btn w-full"><i data-lucide="external-link" aria-hidden="true"></i>{{ __('wizard.open_public') }}</a>
                @endif
                <a href="{{ route('admin.articles.index') }}" class="wz-btn is-ghost w-full"><i data-lucide="list" aria-hidden="true"></i>{{ __('wizard.back_to_list') }}</a>
            </div>
        </aside>
    </div>
</form>

<x-admin.episode-picker-modal />

{{-- Cross-module picker now lives in a shared component so other screens reuse it. --}}
<x-admin.module-selector-modal />

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// TOC Manager Component
window.tocManager = function(config) {
    return {
        name: config.name,
        locale: config.locale,
        items: config.values || [],
        content: config.content || '',
        nextId: 1,
        init() {
            if (this.items.length > 0) {
                this.items = this.items.map(v => ({
                    id: this.nextId++,
                    title: v.title || v.text || '',
                    link: v.link || v.id || '',
                    level: v.level || 2
                }));
            }
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },
        addItem() {
            this.items.push({ id: this.nextId++, title: '', link: '', level: 2 });
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },
        removeItem(index) { this.items.splice(index, 1); },
        autoGenerateTOC() {
            // Collect HTML from all WYSIWYG block editors for this locale
            let html = '';

            // Find the content tab, then the locale panel within it
            const contentTab = document.querySelector('[data-wizard-step="content"]');
            if (contentTab) {
                // Get all block quill editors visible in the current locale
                const editors = contentTab.querySelectorAll('.block-quill .ql-editor');
                editors.forEach(ed => {
                    // Only include editors whose parent block is visible (correct locale)
                    if (ed.offsetParent !== null) {
                        html += ed.innerHTML;
                    }
                });
            }

            // Fallback: scan all block quill editors on the page
            if (!html) {
                document.querySelectorAll('[id^="block_editor_"] .ql-editor').forEach(ed => {
                    html += ed.innerHTML;
                });
            }

            if (!html || html.replace(/<p><br><\/p>/g, '').trim() === '') {
                showToast('{{ __('articles.validation.content_required') }}', 'warning');
                return;
            }

            const doc = new DOMParser().parseFromString(html, 'text/html');
            const headings = doc.querySelectorAll('h2, h3');
            if (!headings.length) {
                showToast('{{ __('articles.help.toc') }}', 'warning');
                return;
            }

            this.items = [];
            headings.forEach(h => {
                const text = h.textContent.trim();
                if (!text) return;
                const link = this.slugify(text);
                this.items.push({ id: this.nextId++, title: text, link, level: parseInt(h.tagName[1]) });
            });

            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },
        slugify(t) {
            return t.toString().toLowerCase().trim()
                .replace(/\s+/g, '-').replace(/[^\w\u0600-\u06FF\-]+/g, '')
                .replace(/\-\-+/g, '-').replace(/^-+/, '').replace(/-+$/, '');
        }
    };
};

// Repeatable Field Component
window.repeatableField = function(config) {
    return {
        name: config.name,
        fields: config.fields,
        items: config.values || [],
        minItems: config.minItems || 0,
        maxItems: config.maxItems || 999,
        nextId: 1,
        init() {
            if (this.items.length > 0) {
                this.items = this.items.map(v => ({ ...v, id: this.nextId++ }));
            } else {
                for (let i = 0; i < this.minItems; i++) this.addItem();
            }
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },
        addItem() {
            if (this.items.length >= this.maxItems) return;
            const item = { id: this.nextId++ };
            this.fields.forEach(f => { item[f.name] = f.default || ''; });
            this.items.push(item);
            this.$nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });
        },
        removeItem(index) {
            if (this.items.length <= this.minItems) return;
            this.items.splice(index, 1);
        }
    };
};

// Article Form Alpine Component
function articleForm(article, locales, wizard) {
    return {
        ...window.adminWizard({
            steps: wizard.steps,
            isEdit: wizard.isEdit,
            updateUrlTemplate: wizard.updateUrl,
            editUrlTemplate: wizard.editUrl,
            locales,
            langProp: 'activeLang',
            messages: wizard.messages,
            stepComplete: (form, key) => form.isStepComplete(key),
            beforeSave: (form) => locales.forEach((locale) => { form.syncAllBlockQuills(locale); form.syncContentBlocks(locale); }),
            // A library thumbnail is copied on save; don't copy it again on the next quick save.
            afterSave: (form) => { form.formData.thumb_id = null; form.formData.cover_id = null; form.summaryTick++; },
        }),
        statusLabels: wizard.statusLabels,
        stepLabels: wizard.stepLabels,
        checkLabels: wizard.checkLabels,
        summaryTick: 0,
        activeLang: 'ar',
        darkMode: window.resolveDarkMode(),
        hasUnsavedChanges: false,
        thumbnailPreview: article?.thumb ? '{{ asset("storage") }}/' + article.thumb : null,
        coverPreview: article?.cover ? '{{ asset("storage") }}/' + article.cover : null,
        manualSlugEdit: {},
        quillEditors: {},
        blockIdCounter: 0,
        contentBlocks: {},
        formData: {
            thumb_id: article?.thumb_id || null,
            cover_id: null,
            writer_id: article?.writer_id || '',
            category_id: article?.category_id || '',
            is_featured: article?.is_featured || false,
            is_pinned: article?.is_pinned || false,
            status: article?.status || '{{ old("status", $article ? $article->status : "draft") }}',
            scheduled_at: article?.scheduled_at || '{{ old("scheduled_at", $article ? $article->scheduled_at : "") }}',
            hijri_date: article?.hijri_date || '{{ $currentHijriDate ?? "" }}',
            read_time: article?.read_time || '',
            title: {},
            slug: {},
            description: {},
            content: {},
            summary: {},
            excerpt: {},
            seo_title: {},
            seo_description: {},
            is_ready: {},
            gallery: article?.gallery || [],
            media_items: article?.media_items || [],
            episodes: article?.episodes || []
        },

        init() {
            this.stepErrors = wizard.stepErrors ?? {};
            this.wizardInit();

            locales.forEach(locale => {
                this.formData.title[locale] = article?.locales?.[locale]?.title || '';
                this.formData.slug[locale] = article?.locales?.[locale]?.slug || '';
                this.formData.description[locale] = article?.locales?.[locale]?.description || '';
                this.formData.summary[locale] = article?.locales?.[locale]?.content?.summary || '';
                this.formData.excerpt[locale] = article?.locales?.[locale]?.excerpt || '';
                this.formData.seo_title[locale] = article?.locales?.[locale]?.seo?.title || '';
                this.formData.seo_description[locale] = article?.locales?.[locale]?.seo?.description || '';
                this.formData.is_ready[locale] = article?.locales?.[locale]?.is_ready || false;
                this.manualSlugEdit[locale] = !!article?.locales?.[locale]?.slug;

                // Initialize content blocks from existing data
                const body = article?.locales?.[locale]?.content?.body;
                if (Array.isArray(body) && body.length) {
                    // Already blocks format
                    this.contentBlocks[locale] = body.map(b => ({ ...b, id: 'b_' + (++this.blockIdCounter) }));
                } else if (typeof body === 'string' && body.trim()) {
                    // Legacy HTML string — wrap in single WYSIWYG block
                    this.contentBlocks[locale] = [{ id: 'b_' + (++this.blockIdCounter), type: 'wysiwyg', content: body }];
                } else {
                    // Default: start with one empty WYSIWYG block
                    this.contentBlocks[locale] = [{ id: 'b_' + (++this.blockIdCounter), type: 'wysiwyg', content: '' }];
                }
            });

            window.addEventListener('media-selected', (e) => this.handleMediaSelected(e.detail));
            window.addEventListener('module-block-add', (e) => this.addModuleBlock(e.detail));
            window.addEventListener('module-inline-insert', (e) => this.insertModuleInline(e.detail));

            // Legacy flag set by the block editor: forward it to the wizard's dirty state.
            this.$watch('hasUnsavedChanges', (value) => {
                if (value) { this.markDirty(); this.summaryTick++; this.hasUnsavedChanges = false; }
            });
            ['formData.gallery', 'formData.media_items', 'formData.episodes'].forEach((key) =>
                this.$watch(key, () => { this.markDirty(); this.summaryTick++; }));
            const form = this.wizardForm();
            ['input', 'change', 'wizard:dirty'].forEach((type) => form?.addEventListener(type, () => { this.summaryTick++; }));

            this.$nextTick(() => {
                this.initializeSelect2();
                this.initializeRichText();
                locales.forEach(locale => this.syncContentBlocks(locale));
                lucide.createIcons();
            });
        },

        // === Block Management ===
        getBlocks(locale) {
            return this.contentBlocks[locale] || [];
        },

        getTotalBlockCount() {
            let total = 0;
            for (const locale of locales) {
                total += (this.contentBlocks[locale] || []).length;
            }
            return total;
        },

        addBlock(type, locale) {
            if (type === 'wysiwyg') {
                if (!this.contentBlocks[locale]) this.contentBlocks[locale] = [];
                this.contentBlocks[locale].push({ id: 'b_' + (++this.blockIdCounter), type: 'wysiwyg', content: '' });
                this.syncContentBlocks(locale);
                this.hasUnsavedChanges = true;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        openModuleSelector(type, locale) {
            window.dispatchEvent(new CustomEvent('open-module-selector', { detail: { type, locale } }));
        },

        addModuleBlock(detail) {
            const { locale, block } = detail;
            if (!this.contentBlocks[locale]) this.contentBlocks[locale] = [];
            block.id = 'b_' + (++this.blockIdCounter);
            this.contentBlocks[locale].push(block);
            this.syncContentBlocks(locale);
            this.hasUnsavedChanges = true;
            this.$nextTick(() => lucide.createIcons());
        },

        removeBlock(locale, index) {
            const block = this.contentBlocks[locale][index];
            if (block.type === 'wysiwyg') {
                const editorKey = `block_${block.id}`;
                if (this.quillEditors[editorKey]) {
                    delete this.quillEditors[editorKey];
                }
                delete this.blockModules[editorKey];
            }
            this.contentBlocks[locale].splice(index, 1);
            this.syncContentBlocks(locale);
            this.hasUnsavedChanges = true;
            this.$nextTick(() => lucide.createIcons());
        },

        moveBlock(locale, index, direction) {
            const blocks = this.contentBlocks[locale];
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= blocks.length) return;
            // Sync WYSIWYG content before moving
            this.syncAllBlockQuills(locale);
            const temp = blocks[index];
            blocks[index] = blocks[newIndex];
            blocks[newIndex] = temp;
            // Force reactivity
            this.contentBlocks[locale] = [...blocks];
            this.syncContentBlocks(locale);
            this.hasUnsavedChanges = true;
            this.$nextTick(() => {
                // Re-init quill editors for WYSIWYG blocks
                blocks.forEach(b => {
                    if (b.type === 'wysiwyg') {
                        this.initBlockQuill(b.id, locale);
                    }
                });
                lucide.createIcons();
            });
        },

        initBlockQuill(blockId, locale) {
            const editorKey = `block_${blockId}`;
            const el = document.getElementById(`block_editor_${blockId}`);
            if (!el || this.quillEditors[editorKey]) return;

            if (!window.AdminWysiwyg) {
                if (!el.dataset.wysiwygWaiting) {
                    el.dataset.wysiwygWaiting = '1';
                    window.addEventListener('rich-text:ready', () => {
                        delete el.dataset.wysiwygWaiting;
                        this.initBlockQuill(blockId, locale);
                    }, { once: true });
                }
                return;
            }

            const block = this.contentBlocks[locale]?.find(b => b.id === blockId);
            const editor = window.AdminWysiwyg.create(el, {
                value: block?.content || '',
                locale,
                placeholder: '{{ __("articles.placeholders.content") }}',
                toolbar: 'full',
            });

            // Same Quran / hadith / sahabi / seerah buttons as every other admin editor.
            this.blockModules[editorKey] = window.AdminWysiwygModules.attachToolbar(editor, { id: blockId, locale });

            editor.on('text-change', () => {
                const b = this.contentBlocks[locale]?.find(b => b.id === blockId);
                if (b) b.content = editor.root.innerHTML;
                this.syncContentBlocks(locale);
                this.hasUnsavedChanges = true;
            });

            this.quillEditors[editorKey] = editor;
        },

        blockModules: {},

        getBlockQuill(blockId) {
            const container = document.getElementById(`block_editor_${blockId}`);
            const liveEditor = container && window.Quill?.find
                ? window.Quill.find(container)
                : null;

            if (liveEditor) return liveEditor;

            const trackedEditor = this.quillEditors[`block_${blockId}`];
            return window.Alpine?.raw ? window.Alpine.raw(trackedEditor) : trackedEditor;
        },

        insertModuleInline(detail) {
            const { quillBlockId, locale, moduleType, data } = detail;
            const editor = this.getBlockQuill(quillBlockId);
            const modules = this.blockModules[`block_${quillBlockId}`];
            if (!editor || !modules) return;

            window.AdminWysiwygModules.insert(editor, modules.takeRange(), moduleType, data, locale);

            const block = this.contentBlocks[locale]?.find(item => item.id === quillBlockId);
            if (block) block.content = editor.root.innerHTML;
            this.syncContentBlocks(locale);
            this.hasUnsavedChanges = true;
            editor.focus();
        },

        escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        },

        syncAllBlockQuills(locale) {
            (this.contentBlocks[locale] || []).forEach(block => {
                if (block.type === 'wysiwyg') {
                    const editor = this.getBlockQuill(block.id);
                    if (editor) block.content = editor.root.innerHTML;
                }
            });
        },

        syncContentBlocks(locale) {
            const blocks = (this.contentBlocks[locale] || []).map(block => {
                const { id, _collapsed, ...contentBlock } = block;
                return contentBlock;
            });
            const textarea = document.getElementById(`content_blocks_${locale}`);
            if (textarea) textarea.value = JSON.stringify(blocks);
        },

        // === Standard Methods ===
        initializeSelect2() {
            const dir = document.documentElement.dir || 'rtl';
            $('.select2-writer, .select2-category, .select2-categories, .select2-tags').select2({
                theme: 'default',
                // The step is display:none at init, so Select2 cannot measure it.
                width: '100%',
                placeholder: function() { return $(this).data('placeholder') || '{{ __("common.select") }}...'; },
                allowClear: true,
                dir: dir
            }).on('select2:select select2:unselect select2:clear', () => { this.markDirty(); this.summaryTick++; });
        },

        initializeRichText() {
            const boot = () => this.$nextTick(() => this.initializeQuill());

            if (window.AdminWysiwyg) {
                boot();
            } else {
                window.addEventListener('rich-text:ready', boot, { once: true });
            }
        },

        initializeQuill() {
            const self = this;
            locales.forEach(locale => {
                // Description editor
                const descTa = document.getElementById(`description_${locale}`);
                const descEditor = window.AdminWysiwyg.create(document.getElementById(`description_${locale}_editor`), {
                    value: descTa?.value || '',
                    locale,
                    placeholder: '{{ __("articles.placeholders.content") }}',
                    toolbar: 'compact',
                });
                descEditor.on('text-change', () => { if (descTa) descTa.value = descEditor.root.innerHTML; self.hasUnsavedChanges = true; });

                // Summary editor
                const summaryTa = document.getElementById(`summary_${locale}`);
                const summaryEditor = window.AdminWysiwyg.create(document.getElementById(`summary_${locale}_editor`), {
                    value: summaryTa?.value || '',
                    locale,
                    placeholder: '{{ __("articles.placeholders.excerpt") }}',
                    toolbar: 'minimal',
                });
                summaryEditor.on('text-change', () => { if (summaryTa) summaryTa.value = summaryEditor.root.innerHTML; self.hasUnsavedChanges = true; });
            });
        },

        generateSlug(locale) {
            if (!this.manualSlugEdit[locale]) {
                this.formData.slug[locale] = this.slugify(this.formData.title[locale] || '');
            }
        },

        onSlugManualEdit(locale) { this.manualSlugEdit[locale] = true; },

        slugify(text) {
            if (!text) return '';
            return text.toString().trim()
                .replace(/[\u064B-\u065F\u0670]/g, '')
                .replace(/[\s\/_]+/g, '-')
                .replace(/[^\w\u0600-\u06FF\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '').replace(/-+$/, '')
                .toLowerCase();
        },

        previewUpload(event, previewKey) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => { this[previewKey] = e.target.result; };
                reader.readAsDataURL(file);
                this.hasUnsavedChanges = true;
            }
        },

        handleMediaSelected(detail) {
            const { target, media, multiple } = detail;
            if (target === 'thumbnail') {
                this.formData.thumb_id = media.id;
                this.thumbnailPreview = media.url;
            } else if (target === 'cover') {
                this.formData.cover_id = media.id;
                this.coverPreview = media.url;
            } else if (target === 'gallery') {
                const items = multiple ? media : [media];
                items.forEach(item => {
                    if (!this.formData.gallery.find(g => g.id === item.id)) {
                        this.formData.gallery.push(item);
                    }
                });
            } else if (target === 'media_items') {
                const items = multiple ? media : [media];
                items.forEach(item => {
                    if (!this.formData.media_items.find(m => m.id === item.id)) {
                        this.formData.media_items.push(item);
                    }
                });
            }
            this.hasUnsavedChanges = true;
            this.$nextTick(() => lucide.createIcons());
        },

        removeGalleryItem(index) { this.formData.gallery.splice(index, 1); this.hasUnsavedChanges = true; },
        /**
         * Merge the picker's selection into the queue, skipping episodes that
         * are already cited so a second visit to the picker cannot duplicate a
         * row (the pivot's unique index would reject it on save anyway).
         */
        addEpisodes(selected) {
            const existing = new Set(this.formData.episodes.map(e => e.id));
            (selected || []).forEach(ep => {
                if (!existing.has(ep.id)) {
                    this.formData.episodes.push(ep);
                    existing.add(ep.id);
                }
            });
        },

        moveEpisode(index, delta) {
            const to = index + delta;
            if (to < 0 || to >= this.formData.episodes.length) return;
            const list = this.formData.episodes;
            [list[index], list[to]] = [list[to], list[index]];
        },

        removeMediaItem(index) { this.formData.media_items.splice(index, 1); this.hasUnsavedChanges = true; },

        // ── Wizard: summary, completeness, review ──
        selectedText(name) {
            void this.summaryTick;
            const el = this.$root.querySelector(`select[name="${name}"]`);
            return el?.value ? (el.selectedOptions?.[0]?.textContent ?? '').trim() : '';
        },

        fieldValue(name) {
            void this.summaryTick;
            const el = this.$root.querySelector(`[name="${name}"]`);
            return String(el?.value ?? '').trim();
        },

        selectedCount(name) {
            void this.summaryTick;
            return this.$root.querySelector(`select[name="${name}"]`)?.selectedOptions?.length ?? 0;
        },

        filledRows(prefix) {
            void this.summaryTick;
            return [...this.$root.querySelectorAll(`[name^="${prefix}["]`)]
                .some((el) => el.type !== 'hidden' && String(el.value).trim() !== '');
        },

        langFilled(code) {
            return (this.formData.title?.[code] ?? '').trim() !== '';
        },

        contentFilled(locale) {
            void this.summaryTick;
            return (this.contentBlocks[locale] || []).some((block) => block.type !== 'wysiwyg'
                || /module-embed|<img|<iframe/.test(block.content || '')
                || (block.content || '').replace(/<[^>]*>/g, '').trim() !== '');
        },

        isStepComplete(key) {
            switch (key) {
                case 'basics': return this.langFilled('ar');
                case 'content': return this.contentFilled('ar');
                case 'classify': return !!this.thumbnailPreview && this.fieldValue('category_id') !== '';
                case 'seo': return !!(this.formData.seo_title.ar || this.formData.seo_description.ar);
                case 'media': return this.formData.gallery.length + this.formData.media_items.length + this.formData.episodes.length > 0;
                case 'details': return this.filledRows('references') || this.filledRows('statistics');
                default: return !!this.visited[key];
            }
        },

        reviewChecks() {
            void this.step;
            return [
                { key: 'title', step: 'basics', level: 'required', ok: this.langFilled('ar') },
                { key: 'content', step: 'content', level: 'required', ok: this.contentFilled('ar') },
                { key: 'ready', step: 'basics', level: 'recommended', ok: Object.values(this.formData.is_ready).some(Boolean) },
                { key: 'thumb', step: 'classify', level: 'recommended', ok: !!this.thumbnailPreview },
                { key: 'category', step: 'classify', level: 'recommended', ok: this.fieldValue('category_id') !== '' },
                { key: 'writer', step: 'classify', level: 'recommended', ok: this.fieldValue('writer_id') !== '' },
                { key: 'tags', step: 'classify', level: 'recommended', ok: this.selectedCount('tags[]') > 0 },
                { key: 'excerpt', step: 'basics', level: 'recommended', ok: !!(this.formData.excerpt.ar || '').trim() },
                { key: 'seo', step: 'seo', level: 'recommended', ok: !!(this.formData.seo_title.ar || '').trim() },
                { key: 'references', step: 'details', level: 'recommended', ok: this.filledRows('references') },
            ].map((check) => ({ ...check, label: this.checkLabels[check.key] ?? check.key }));
        },

        reviewCount(level) {
            return this.reviewChecks().filter((check) => !check.ok && check.level === level).length;
        },

        handleSubmit() {
            // Serialize content blocks to hidden textareas
            locales.forEach(locale => {
                this.syncAllBlockQuills(locale);
                this.syncContentBlocks(locale);
            });
            this.hasUnsavedChanges = false;
            this.dirty = false;
            this.$el.dataset.wizardSubmitting = '1';
            this.$el.submit();
        }
    };
}

// Module Selector Alpine Component
// moduleSelector() moved to components/admin/module-selector-modal.blade.php
</script>
@endpush
