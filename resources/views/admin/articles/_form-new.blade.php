{{--
    Comprehensive Article Form
    Layout: Sidebar (30%) + Main Content Tabs (70%)
    Features: Multi-language, WYSIWYG, Select2, Media Library, TOC, References, Statistics
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
@endphp

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Select2 Dark Mode Support */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        background-color: transparent;
        border-color: rgb(75, 85, 99);
    }

    .dark .select2-container--default .select2-selection--single,
    .dark .select2-container--default .select2-selection--multiple {
        background-color: rgb(55, 65, 81);
        border-color: rgb(75, 85, 99);
        color: white;
    }

    .select2-container--default .select2-results__option {
        color: #333;
    }

    .dark .select2-dropdown {
        background-color: rgb(55, 65, 81);
        border-color: rgb(75, 85, 99);
    }

    .dark .select2-container--default .select2-results__option {
        color: white;
    }

    .dark .select2-container--default .select2-results__option--highlighted {
        background-color: rgb(37, 99, 235);
    }
</style>
@endpush

<form action="{{ $actionUrl }}"
      method="POST"
      enctype="multipart/form-data"
      x-data="articleForm(@js($formArticle ?? $article ?? null), @js($locales))"
      @submit.prevent="handleSubmit"
      class="article-form">

    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    {{-- Unsaved Changes Warning --}}
    <div x-show="hasUnsavedChanges"
         x-transition
         class="fixed top-20 end-6 z-50 px-4 py-3 rounded-lg shadow-lg bg-yellow-500 text-white flex items-center gap-2">
        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        <span class="text-sm font-medium">{{ __('common.unsaved_changes') }}</span>
    </div>

    {{-- Main Layout: Sidebar + Content --}}
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- ==================== SIDEBAR (30%) ==================== --}}
        <div class="lg:w-[30%] space-y-4">

            {{-- Thumbnail Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <h3 class="text-sm font-semibold mb-3"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('articles.fields.thumb') }}
                </h3>

                <div class="space-y-3">
                    {{-- Thumbnail Preview --}}
                    <div class="relative aspect-video bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                        <template x-if="thumbnailPreview">
                            <img :src="thumbnailPreview"
                                 class="w-full h-full object-cover"
                                 alt="Thumbnail">
                        </template>
                        <template x-if="!thumbnailPreview">
                            <div class="flex items-center justify-center h-full text-gray-400">
                                <i data-lucide="image" class="w-12 h-12"></i>
                            </div>
                        </template>
                    </div>

                    {{-- Upload/Select Buttons --}}
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                                @click="$dispatch('open-media-library', { target: 'thumbnail', multiple: false })"
                                class="px-3 py-2 text-sm rounded-lg border transition-colors"
                                :class="darkMode ?
                                    'border-gray-600 hover:bg-gray-700' :
                                    'border-gray-300 hover:bg-gray-50'">
                            <i data-lucide="folder-open" class="w-4 h-4 inline me-1"></i>
                            {{ __('articles.fields.select_thumbnail') }}
                        </button>
                        <label class="px-3 py-2 text-sm rounded-lg border transition-colors cursor-pointer text-center"
                               :class="darkMode ?
                                   'border-gray-600 hover:bg-gray-700' :
                                   'border-gray-300 hover:bg-gray-50'">
                            <i data-lucide="upload" class="w-4 h-4 inline me-1"></i>
                            {{ __('common.upload') }}
                            <input type="file"
                                   name="thumb"
                                   accept="image/*"
                                   @change="handleThumbnailUpload"
                                   class="hidden">
                        </label>
                    </div>

                    <input type="hidden" name="thumb_id" x-model="formData.thumb_id">
                </div>
            </div>

            {{-- Writer Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <label class="block text-sm font-semibold mb-2"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('articles.fields.writer') }}
                </label>
                <select name="writer_id"
                        x-model="formData.writer_id"
                        class="select2-writer w-full px-3 py-2 rounded-lg border"
                        :class="darkMode ?
                            'bg-gray-700 border-gray-600 text-white' :
                            'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('articles.placeholders.select_writer') }}</option>
                    @foreach($writers as $writer)
                        <option value="{{ $writer->id }}"
                                {{ old('writer_id', $article ? $article->writer_id : '') == $writer->id ? 'selected' : '' }}>
                            {{ $writer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Features Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 space-y-3">
                <h3 class="text-sm font-semibold"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('articles.sidebar.featured') }}
                </h3>

                {{-- Featured Toggle --}}
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('articles.fields.is_featured') }}
                    </span>
                    <input type="checkbox"
                           name="is_featured"
                           value="1"
                           x-model="formData.is_featured"
                           class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500">
                </label>

                {{-- Pinned Toggle --}}
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('articles.fields.is_pinned') }}
                    </span>
                    <input type="checkbox"
                           name="is_pinned"
                           value="1"
                           x-model="formData.is_pinned"
                           class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500">
                </label>
            </div>

            {{-- Category Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <label class="block text-sm font-semibold mb-2"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('articles.fields.primary_category') }}
                </label>
                <select name="category_id"
                        x-model="formData.category_id"
                        class="select2-category w-full px-3 py-2 rounded-lg border"
                        :class="darkMode ?
                            'bg-gray-700 border-gray-600 text-white' :
                            'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('articles.placeholders.select_category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                                {{ old('category_id', $article ? $article->category_id : '') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                {{-- Additional Categories --}}
                <label class="block text-sm font-semibold mt-3 mb-2"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('articles.fields.sub_categories') }}
                </label>
                <select name="additional_categories[]"
                        multiple
                        class="select2-categories w-full px-3 py-2 rounded-lg border"
                        :class="darkMode ?
                            'bg-gray-700 border-gray-600 text-white' :
                            'bg-white border-gray-300 text-gray-900'">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}"
                                {{ in_array($category->id, old('additional_categories', $article && $article->categories ? $article->categories->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Tags Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <label class="block text-sm font-semibold mb-2"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('articles.fields.tags') }}
                </label>
                <select name="tags[]"
                        multiple
                        class="select2-tags w-full px-3 py-2 rounded-lg border"
                        :class="darkMode ?
                            'bg-gray-700 border-gray-600 text-white' :
                            'bg-white border-gray-300 text-gray-900'">
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}"
                                {{ in_array($tag->id, old('tags', $article && $article->tags ? $article->tags->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                            {{ $tag->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status & Dates Section --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 space-y-3">
                <h3 class="text-sm font-semibold"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('articles.sidebar.publication') }}
                </h3>

                {{-- Status --}}
                <div>
                    <label class="block text-sm font-medium mb-1"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('articles.fields.status') }}
                    </label>
                    <select name="status"
                            x-model="formData.status"
                            class="w-full px-3 py-2 rounded-lg border"
                            :class="darkMode ?
                                'bg-gray-700 border-gray-600 text-white' :
                                'bg-white border-gray-300 text-gray-900'">
                        <option value="draft">{{ __('articles.status.draft') }}</option>
                        <option value="pending">{{ __('articles.status.pending') }}</option>
                        <option value="published">{{ __('articles.status.published') }}</option>
                        <option value="scheduled">{{ __('articles.status.scheduled') }}</option>
                        <option value="archived">{{ __('articles.status.archived') }}</option>
                    </select>
                </div>

                {{-- Scheduled Publish Date/Time (shown only when status = scheduled) --}}
                <div x-show="formData.status === 'scheduled'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-cloak>
                    <label class="block text-sm font-medium mb-1"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('articles.fields.scheduled_at') }}
                    </label>
                    <input type="datetime-local"
                           name="scheduled_at"
                           x-model="formData.scheduled_at"
                           class="w-full px-3 py-2 rounded-lg border"
                           :class="darkMode ?
                               'bg-gray-700 border-gray-600 text-white' :
                               'bg-white border-gray-300 text-gray-900'">
                    <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        {{ __('articles.help.scheduled_at') }}
                    </p>
                </div>

                {{-- Hijri Date --}}
                <div>
                    <label class="block text-sm font-medium mb-1"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('articles.fields.hijri_date') }}
                    </label>
                    <input type="text"
                           name="hijri_date"
                           x-model="formData.hijri_date"
                           placeholder="{{ __('articles.help.hijri_date') }}"
                           class="w-full px-3 py-2 rounded-lg border"
                           :class="darkMode ?
                               'bg-gray-700 border-gray-600 text-white' :
                               'bg-white border-gray-300 text-gray-900'">
                    <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        {{ __('articles.help.hijri_date') }}
                    </p>
                </div>

                {{-- Read Time --}}
                <div>
                    <label class="block text-sm font-medium mb-1"
                           :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('articles.fields.read_time_minutes') }}
                    </label>
                    <input type="number"
                           name="read_time"
                           x-model="formData.read_time"
                           min="1"
                           max="999"
                           class="w-full px-3 py-2 rounded-lg border"
                           :class="darkMode ?
                               'bg-gray-700 border-gray-600 text-white' :
                               'bg-white border-gray-300 text-gray-900'">
                    <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        {{ __('articles.help.read_time') }}
                    </p>
                </div>

                {{-- Views (Display Only for Edit) --}}
                @if($isEdit)
                    <div>
                        <label class="block text-sm font-medium mb-1"
                               :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ __('articles.fields.views') }}
                        </label>
                        <div class="px-3 py-2 rounded-lg border bg-gray-50 dark:bg-gray-900 flex items-center gap-2"
                             :class="darkMode ? 'border-gray-600' : 'border-gray-300'">
                            <i data-lucide="eye" class="w-4 h-4 text-gray-400"></i>
                            <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ number_format($article->views ?? 0) }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>

        </div>

        {{-- ==================== MAIN CONTENT (70%) ==================== --}}
        <div class="lg:w-[70%]">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">

                {{-- Tabs Header --}}
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex gap-2 px-4 overflow-x-auto" role="tablist">
                        <button type="button"
                                @click="activeTab = 'main'"
                                :class="activeTab === 'main' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors">
                            <i data-lucide="file-text" class="w-4 h-4 inline me-1"></i>
                            {{ __('articles.tabs.main_info') }}
                        </button>

                        <button type="button"
                                @click="activeTab = 'seo'"
                                :class="activeTab === 'seo' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors">
                            <i data-lucide="search" class="w-4 h-4 inline me-1"></i>
                            {{ __('articles.tabs.seo_metadata') }}
                        </button>

                        <button type="button"
                                @click="activeTab = 'gallery'"
                                :class="activeTab === 'gallery' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors">
                            <i data-lucide="images" class="w-4 h-4 inline me-1"></i>
                            {{ __('articles.tabs.gallery') }}
                        </button>

                        <button type="button"
                                @click="activeTab = 'media'"
                                :class="activeTab === 'media' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors">
                            <i data-lucide="video" class="w-4 h-4 inline me-1"></i>
                            {{ __('articles.tabs.media') }}
                        </button>

                        <button type="button"
                                @click="activeTab = 'statistics'"
                                :class="activeTab === 'statistics' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors">
                            <i data-lucide="bar-chart" class="w-4 h-4 inline me-1"></i>
                            {{ __('articles.tabs.statistics_info') }}
                        </button>
                    </nav>
                </div>

                {{-- Tab Contents --}}
                <div class="p-6">

                    {{-- TAB 1: Main Info (Multi-Language) --}}
                    <div x-show="activeTab === 'main'" x-transition class="space-y-6">

                        {{-- Language Sub-Tabs --}}
                        <div class="border-b border-gray-200 dark:border-gray-700 -mx-6 px-6">
                            <nav class="flex gap-2 overflow-x-auto" role="tablist">
                                @foreach($locales as $locale)
                                    <button type="button"
                                            @click="activeLang = '{{ $locale }}'"
                                            :class="activeLang === '{{ $locale }}' ?
                                                'border-b-2 border-green-500 text-green-600 dark:text-green-400' :
                                                'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                                            class="px-3 py-2 font-medium text-sm whitespace-nowrap transition-colors">
                                            {{ strtoupper($locale) }}
                                            <span x-show="formData.is_ready?.{{ $locale }}"
                                                  class="inline-flex w-2 h-2 bg-green-500 rounded-full ms-1"></span>
                                        </button>
                                @endforeach
                            </nav>
                        </div>

                        {{-- Language Content Panels --}}
                        @foreach($locales as $locale)
                            <div x-show="activeLang === '{{ $locale }}'" x-transition class="space-y-4">

                                {{-- Title --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.title') }} ({{ strtoupper($locale) }})
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="title[{{ $locale }}]"
                                           x-model="formData.title.{{ $locale }}"
                                           @input="generateSlug('{{ $locale }}')"
                                           required
                                           class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           :class="darkMode ?
                                               'bg-gray-700 border-gray-600 text-white' :
                                               'bg-white border-gray-300 text-gray-900'">
                                </div>

                                {{-- Slug --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.slug') }}
                                    </label>
                                    <input type="text"
                                           name="slug[{{ $locale }}]"
                                           x-model="formData.slug.{{ $locale }}"
                                           @input="onSlugManualEdit('{{ $locale }}')"
                                           class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                                           :class="darkMode ?
                                               'bg-gray-700 border-gray-600 text-white' :
                                               'bg-white border-gray-300 text-gray-900'">
                                    <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        {{ __('articles.help.slug') }}
                                    </p>
                                </div>

                                {{-- Description (WYSIWYG) --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.description') }}
                                    </label>
                                    <div id="description_{{ $locale }}_editor" class="quill-editor" style="height: 150px;"></div>
                                    <textarea name="description[{{ $locale }}]"
                                              id="description_{{ $locale }}"
                                              class="hidden">{{ old("description.{$locale}", $article && $article->getArticleLocale($locale) ? $article->getArticleLocale($locale)->description : '') }}</textarea>
                                </div>

                                {{-- Content Body (WYSIWYG) --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.body') }}
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <div id="content_{{ $locale }}_editor" class="quill-editor" style="height: 400px;"></div>
                                    <textarea name="content[{{ $locale }}]"
                                              id="content_{{ $locale }}"
                                              class="hidden"
                                              required>{{ old("content.{$locale}", $article && $article->getArticleLocale($locale) ? ($article->getArticleLocale($locale)->content['body'] ?? '') : '') }}</textarea>
                                </div>

                                {{-- TOC (Table of Contents) --}}
                                <x-admin.toc-manager
                                    name="toc"
                                    :locale="$locale"
                                    :content="'content_' . $locale"
                                    :values="old('toc.' . $locale, $article && $article->getArticleLocale($locale) ? ($article->getArticleLocale($locale)->content['toc'] ?? []) : [])"
                                    :label="__('articles.fields.toc')"
                                />

                                {{-- Summary (WYSIWYG) --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.summary') }}
                                    </label>
                                    <div id="summary_{{ $locale }}_editor" class="quill-editor" style="height: 150px;"></div>
                                    <textarea name="summary[{{ $locale }}]"
                                              id="summary_{{ $locale }}"
                                              class="hidden">{{ old("summary.{$locale}", $article && $article->getArticleLocale($locale) ? ($article->getArticleLocale($locale)->content['summary'] ?? '') : '') }}</textarea>
                                </div>

                                {{-- Excerpt --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.excerpt') }}
                                    </label>
                                    <textarea name="excerpt[{{ $locale }}]"
                                              x-model="formData.excerpt.{{ $locale }}"
                                              rows="3"
                                              maxlength="500"
                                              class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                              :class="darkMode ?
                                                  'bg-gray-700 border-gray-600 text-white' :
                                                  'bg-white border-gray-300 text-gray-900'"
                                              placeholder="{{ __('articles.placeholders.excerpt') }}"></textarea>
                                    <p class="text-xs mt-1 text-end" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        <span x-text="(formData.excerpt.{{ $locale }} || '').length"></span> / 500
                                    </p>
                                </div>

                                {{-- Is Ready Checkbox --}}
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           name="is_ready[{{ $locale }}]"
                                           value="1"
                                           x-model="formData.is_ready.{{ $locale }}"
                                           class="w-5 h-5 rounded text-green-600 focus:ring-green-500">
                                    <span class="text-sm font-medium"
                                          :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        {{ __('articles.fields.is_ready') }} ({{ strtoupper($locale) }})
                                    </span>
                                </label>

                            </div>
                        @endforeach

                    </div>

                    {{-- TAB 2: SEO Metadata --}}
                    <div x-show="activeTab === 'seo'" x-transition class="space-y-6">
                        <div class="rounded-lg border p-4"
                             :class="darkMode ? 'bg-blue-900/10 border-blue-800' : 'bg-blue-50 border-blue-200'">
                            <div class="flex items-start gap-3">
                                <i data-lucide="info" class="w-5 h-5 flex-shrink-0 mt-0.5"
                                   :class="darkMode ? 'text-blue-400' : 'text-blue-600'"></i>
                                <div>
                                    <h4 class="font-semibold mb-1"
                                        :class="darkMode ? 'text-blue-400' : 'text-blue-600'">
                                        {{ __('articles.seo.title') }}
                                    </h4>
                                    <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        تحسين المقال لمحركات البحث (SEO) يساعد في ظهور المحتوى في نتائج البحث. الحقول أدناه اختيارية.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Language Tabs for SEO --}}
                        <div class="flex gap-2 border-b"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                            @foreach($locales as $locale)
                            <button type="button"
                                    @click="activeLang = '{{ $locale }}'"
                                    class="px-4 py-2 font-medium transition-colors"
                                    :class="activeLang === '{{ $locale }}' ?
                                        'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                        'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'">
                                {{ strtoupper($locale) }}
                            </button>
                            @endforeach
                        </div>

                        {{-- SEO Fields per Language --}}
                        @foreach($locales as $locale)
                        <div x-show="activeLang === '{{ $locale }}'" x-transition class="space-y-6">

                            {{-- SEO Title --}}
                            <div>
                                <label class="block text-sm font-semibold mb-2"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.seo.title') }}
                                    <span class="text-gray-400 font-normal">({{ __('common.optional') }})</span>
                                </label>
                                <input type="text"
                                       name="seo_title[{{ $locale }}]"
                                       x-model="formData.seo_title['{{ $locale }}']"
                                       maxlength="70"
                                       placeholder="{{ __('articles.fields.title') }}"
                                       class="w-full px-4 py-2 rounded-lg border transition-colors focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       :class="darkMode ?
                                           'bg-gray-700 border-gray-600 text-white placeholder-gray-400' :
                                           'bg-white border-gray-300 text-gray-900 placeholder-gray-500'">
                                <div class="flex items-center justify-between mt-1">
                                    <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        يظهر كعنوان في نتائج البحث. الطول المثالي: 50-60 حرف
                                    </p>
                                    <span class="text-xs"
                                          :class="(formData.seo_title['{{ $locale }}']?.length || 0) > 60 ? 'text-orange-500' : (darkMode ? 'text-gray-500' : 'text-gray-400')">
                                        <span x-text="formData.seo_title['{{ $locale }}']?.length || 0"></span>/70
                                    </span>
                                </div>
                            </div>

                            {{-- SEO Description --}}
                            <div>
                                <label class="block text-sm font-semibold mb-2"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.seo.description') }}
                                    <span class="text-gray-400 font-normal">({{ __('common.optional') }})</span>
                                </label>
                                <textarea name="seo_description[{{ $locale }}]"
                                          x-model="formData.seo_description['{{ $locale }}']"
                                          rows="3"
                                          maxlength="160"
                                          placeholder="{{ __('articles.placeholders.excerpt') }}"
                                          class="w-full px-4 py-2 rounded-lg border transition-colors focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                          :class="darkMode ?
                                              'bg-gray-700 border-gray-600 text-white placeholder-gray-400' :
                                              'bg-white border-gray-300 text-gray-900 placeholder-gray-500'"></textarea>
                                <div class="flex items-center justify-between mt-1">
                                    <p class="text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        يظهر كوصف في نتائج البحث. الطول المثالي: 150-160 حرف
                                    </p>
                                    <span class="text-xs"
                                          :class="(formData.seo_description['{{ $locale }}']?.length || 0) > 155 ? 'text-orange-500' : (darkMode ? 'text-gray-500' : 'text-gray-400')">
                                        <span x-text="formData.seo_description['{{ $locale }}']?.length || 0"></span>/160
                                    </span>
                                </div>
                            </div>

                            {{-- Preview Card --}}
                            <div class="rounded-lg border p-4"
                                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
                                <h4 class="text-xs font-semibold mb-3 uppercase tracking-wide"
                                    :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    معاينة نتائج البحث
                                </h4>
                                <div class="space-y-1">
                                    <div class="text-blue-600 text-lg font-medium hover:underline cursor-pointer">
                                        <span x-text="formData.seo_title['{{ $locale }}'] || formData.title['{{ $locale }}'] || 'عنوان المقال'"></span>
                                    </div>
                                    <div class="text-sm text-green-700 flex items-center gap-1">
                                        <span>{{ config('app.url') }}</span>
                                        <i data-lucide="chevron-right" class="w-3 h-3"></i>
                                        <span x-text="formData.slug['{{ $locale }}'] || 'article-slug'"></span>
                                    </div>
                                    <div class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                        <span x-text="formData.seo_description['{{ $locale }}'] || formData.excerpt['{{ $locale }}'] || formData.description['{{ $locale }}']?.substring(0, 160) || 'وصف المقال سيظهر هنا...'"></span>
                                    </div>
                                </div>
                            </div>

                        </div>
                        @endforeach
                    </div>

                    {{-- TAB 3: Gallery Management --}}
                    <div x-show="activeTab === 'gallery'" x-transition class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ __('articles.help.gallery') }}
                            </p>
                            <button type="button"
                                    @click="$dispatch('open-media-library', { target: 'gallery', multiple: true })"
                                    class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                {{ __('articles.fields.add_image') }}
                            </button>
                        </div>

                        {{-- Gallery Grid --}}
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" x-show="formData.gallery.length > 0">
                            <template x-for="(item, index) in formData.gallery" :key="item.id">
                                <div class="relative group rounded-lg overflow-hidden border-2"
                                     :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                                    <img :src="item.url" :alt="item.caption" class="w-full aspect-square object-cover">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                        <button type="button"
                                                @click="removeGalleryItem(index)"
                                                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                    <input type="hidden" :name="'gallery[' + index + ']'" :value="item.id">
                                </div>
                            </template>
                        </div>

                        {{-- Empty State --}}
                        <div x-show="formData.gallery.length === 0"
                             class="text-center py-12 border-2 border-dashed rounded-lg"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                            <i data-lucide="images" class="w-16 h-16 mx-auto mb-4 text-gray-400"></i>
                            <p class="text-gray-500 mb-4">لا توجد صور في المعرض</p>
                        </div>
                    </div>

                    {{-- TAB 4: Media Episodes --}}
                    <div x-show="activeTab === 'media'" x-transition class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ __('articles.help.media') }}
                            </p>
                            <button type="button"
                                    @click="$dispatch('open-media-episodes-modal')"
                                    class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                {{ __('articles.fields.add_media') }}
                            </button>
                        </div>

                        {{-- Media Items List --}}
                        <div class="space-y-2" x-show="formData.media_items.length > 0">
                            <template x-for="(item, index) in formData.media_items" :key="item.id">
                                <div class="flex items-center gap-3 p-3 rounded-lg border"
                                     :class="darkMode ? 'bg-gray-700 border-gray-600' : 'bg-gray-50 border-gray-200'">
                                    <i data-lucide="video" class="w-8 h-8 text-gray-400"></i>
                                    <div class="flex-1">
                                        <p class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="item.title"></p>
                                        <p class="text-sm text-gray-500" x-text="item.type"></p>
                                    </div>
                                    <button type="button"
                                            @click="removeMediaItem(index)"
                                            class="p-2 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                    <input type="hidden" :name="'media_items[' + index + ']'" :value="item.id">
                                </div>
                            </template>
                        </div>

                        {{-- Empty State --}}
                        <div x-show="formData.media_items.length === 0"
                             class="text-center py-12 border-2 border-dashed rounded-lg"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                            <i data-lucide="video" class="w-16 h-16 mx-auto mb-4 text-gray-400"></i>
                            <p class="text-gray-500 mb-4">لا توجد وسائط مرتبطة</p>
                        </div>
                    </div>

                    {{-- TAB 5: Statistics & References --}}
                    <div x-show="activeTab === 'statistics'" x-transition class="space-y-6">

                        {{-- References Section --}}
                        <x-admin.repeatable-field
                            name="references"
                            :label="__('articles.fields.references')"
                            :fields="[
                                ['name' => 'name', 'label' => __('articles.fields.reference_name'), 'type' => 'text', 'placeholder' => 'اسم المرجع'],
                                ['name' => 'url', 'label' => __('articles.fields.reference_url'), 'type' => 'url', 'placeholder' => 'https://...'],
                                ['name' => 'type', 'label' => __('articles.fields.reference_type'), 'type' => 'text', 'placeholder' => 'كتاب، موقع، إلخ']
                            ]"
                            :values="old('references', $article ? $article->references : [])"
                            :addButtonText="__('articles.fields.add_reference')"
                            :emptyMessage="__('common.no_items')"
                            :help="__('articles.help.references')"
                        />

                        {{-- Statistics Section --}}
                        <x-admin.repeatable-field
                            name="statistics"
                            :label="__('articles.fields.statistics')"
                            :fields="[
                                ['name' => 'name', 'label' => __('articles.fields.stat_name'), 'type' => 'text', 'placeholder' => 'اسم الإحصائية'],
                                ['name' => 'value', 'label' => __('articles.fields.stat_value'), 'type' => 'text', 'placeholder' => 'القيمة']
                            ]"
                            :values="old('statistics', $article ? $article->statistics : [])"
                            :addButtonText="__('articles.fields.add_stat')"
                            :emptyMessage="__('common.no_items')"
                            :help="__('articles.help.statistics')"
                        />

                    </div>

                </div>

            </div>
        </div>

    </div>

    {{-- Form Actions --}}
    <div class="flex items-center justify-between gap-4 mt-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <a href="{{ route('admin.articles.index') }}"
           class="px-6 py-2.5 rounded-lg font-semibold transition-all border-2"
           :class="darkMode ?
               'border-gray-600 text-gray-300 hover:bg-gray-700' :
               'border-gray-300 text-gray-700 hover:bg-gray-50'">
            {{ __('common.cancel') }}
        </a>

        <div class="flex items-center gap-3">
            <button type="button"
                    @click="saveDraft"
                    class="px-6 py-2.5 rounded-lg font-semibold transition-all border-2"
                    :class="darkMode ?
                        'border-yellow-600 text-yellow-400 hover:bg-yellow-900/20' :
                        'border-yellow-600 text-yellow-700 hover:bg-yellow-50'">
                <i data-lucide="save" class="w-4 h-4 inline me-1"></i>
                {{ __('articles.actions.save_draft') }}
            </button>

            <button type="submit"
                    class="px-6 py-2.5 rounded-lg font-semibold transition-all bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg">
                <i data-lucide="check" class="w-4 h-4 inline me-1"></i>
                {{ $isEdit ? __('common.update') : __('common.create') }}
            </button>
        </div>
    </div>

</form>

@push('scripts')
<!-- jQuery (Required for Select2) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Quill Editor (Open Source Alternative) -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>
<script>
// Component Functions (Must be defined before Alpine initializes)
window.tocManager = function(config) {
    return {
        name: config.name,
        locale: config.locale,
        items: config.values || [],
        content: config.content || '',
        nextId: 1,

        init() {
            if (this.items.length > 0) {
                this.items = this.items.map(value => ({
                    ...value,
                    id: this.nextId++,
                    level: value.level || 2
                }));
            }
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        addItem() {
            this.items.push({
                id: this.nextId++,
                title: '',
                link: '',
                level: 2
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        autoGenerateTOC() {
            const editorContainer = document.querySelector(`#content_${this.locale}_editor`);
            let contentHtml = '';

            if (editorContainer && editorContainer.querySelector('.ql-editor')) {
                contentHtml = editorContainer.querySelector('.ql-editor').innerHTML;
            }

            if (!contentHtml) {
                showToast('الرجاء كتابة المحتوى أولاً', 'warning');
                return;
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(contentHtml, 'text/html');
            const headings = doc.querySelectorAll('h2, h3');

            if (headings.length === 0) {
                showToast('لم يتم العثور على عناوين (H2, H3) في المحتوى', 'warning');
                return;
            }

            this.items = [];
            headings.forEach((heading) => {
                const text = heading.textContent.trim();
                const level = parseInt(heading.tagName.substring(1));
                const link = this.slugify(text);

                if (!heading.id) heading.id = link;

                this.items.push({
                    id: this.nextId++,
                    title: text,
                    link: link,
                    level: level
                });
            });

            if (editorContainer && editorContainer.querySelector('.ql-editor')) {
                editorContainer.querySelector('.ql-editor').innerHTML = doc.body.innerHTML;
                const textarea = document.getElementById(`content_${this.locale}`);
                if (textarea) textarea.value = doc.body.innerHTML;
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        slugify(text) {
            return text.toString().toLowerCase().trim()
                .replace(/\s+/g, '-')
                .replace(/[^\w\u0600-\u06FF\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        }
    };
};

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
                this.items = this.items.map((value) => ({
                    ...value,
                    id: this.nextId++
                }));
            } else {
                for (let i = 0; i < this.minItems; i++) {
                    this.addItem();
                }
            }
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        addItem() {
            if (this.items.length >= this.maxItems) return;

            const newItem = { id: this.nextId++ };
            this.fields.forEach(field => {
                newItem[field.name] = field.default || '';
            });
            this.items.push(newItem);

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        removeItem(index) {
            if (this.items.length <= this.minItems) return;
            this.items.splice(index, 1);
        }
    };
};

// Article Form
function articleForm(article, locales) {
    return {
        activeTab: 'main',
        activeLang: 'ar',
        hasUnsavedChanges: false,
        thumbnailPreview: article?.thumb ? '{{ asset('storage') }}/' + article.thumb : null,
        manualSlugEdit: {}, // Track which locales have manually edited slugs
        formData: {
            thumb_id: article?.thumb_id || null,
            writer_id: article?.writer_id || '',
            category_id: article?.category_id || '',
            is_featured: article?.is_featured || false,
            is_pinned: article?.is_pinned || false,
            status: article?.status || 'draft',
            scheduled_at: article?.scheduled_at || '',
            hijri_date: article?.hijri_date || '{{ $currentHijriDate ?? '' }}',
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
            media_items: article?.media_items || []
        },

        init() {
            // Initialize form data for each locale
            locales.forEach(locale => {
                this.formData.title[locale] = article?.locales?.[locale]?.title || '';
                this.formData.slug[locale] = article?.locales?.[locale]?.slug || '';
                this.formData.description[locale] = article?.locales?.[locale]?.description || '';
                this.formData.content[locale] = article?.locales?.[locale]?.content?.body || '';
                this.formData.summary[locale] = article?.locales?.[locale]?.content?.summary || '';
                this.formData.excerpt[locale] = article?.locales?.[locale]?.excerpt || '';
                this.formData.seo_title[locale] = article?.locales?.[locale]?.seo?.title || '';
                this.formData.seo_description[locale] = article?.locales?.[locale]?.seo?.description || '';
                this.formData.is_ready[locale] = article?.locales?.[locale]?.is_ready || false;

                // Mark slug as manually edited if article already has a slug
                this.manualSlugEdit[locale] = !!article?.locales?.[locale]?.slug;
            });

            // Listen for media selection events
            window.addEventListener('media-selected', (event) => {
                this.handleMediaSelected(event.detail);
            });

            // Initialize Select2
            this.$nextTick(() => {
                this.initializeSelect2();
                this.initializeQuill();
                this.setupUnsavedChangesWarning();
                lucide.createIcons();
            });
        },

        initializeSelect2() {
            $('.select2-writer, .select2-category, .select2-categories, .select2-tags').select2({
                theme: 'default',
                placeholder: function() {
                    return $(this).data('placeholder') || 'اختر...';
                },
                allowClear: true,
                dir: 'rtl'
            });
        },

        initializeQuill() {
            const self = this;

            locales.forEach(locale => {
                const isRtl = ['ar', 'ur'].includes(locale);

                // Description editor (simple toolbar)
                const descEditor = new Quill(`#description_${locale}_editor`, {
                    theme: 'snow',
                    placeholder: 'أدخل الوصف...',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link'],
                            ['clean']
                        ]
                    }
                });

                // Set RTL if needed
                if (isRtl) {
                    descEditor.format('direction', 'rtl');
                    descEditor.format('align', 'right');
                }

                // Load initial content
                const descTextarea = document.getElementById(`description_${locale}`);
                if (descTextarea.value) {
                    descEditor.root.innerHTML = descTextarea.value;
                }

                // Sync to textarea on change
                descEditor.on('text-change', () => {
                    descTextarea.value = descEditor.root.innerHTML;
                    self.hasUnsavedChanges = true;
                });

                // Content editor (full toolbar)
                const contentEditor = new Quill(`#content_${locale}_editor`, {
                    theme: 'snow',
                    placeholder: 'أدخل محتوى المقال...',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'align': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'indent': '-1'}, { 'indent': '+1' }],
                            ['blockquote', 'code-block'],
                            ['link', 'image', 'video'],
                            ['clean']
                        ]
                    }
                });

                // Set RTL if needed
                if (isRtl) {
                    contentEditor.format('direction', 'rtl');
                    contentEditor.format('align', 'right');
                }

                // Load initial content
                const contentTextarea = document.getElementById(`content_${locale}`);
                if (contentTextarea.value) {
                    contentEditor.root.innerHTML = contentTextarea.value;
                }

                // Sync to textarea on change
                contentEditor.on('text-change', () => {
                    contentTextarea.value = contentEditor.root.innerHTML;
                    self.hasUnsavedChanges = true;
                });

                // Summary editor (simple toolbar)
                const summaryEditor = new Quill(`#summary_${locale}_editor`, {
                    theme: 'snow',
                    placeholder: 'أدخل الملخص...',
                    modules: {
                        toolbar: [
                            ['bold', 'italic'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link']
                        ]
                    }
                });

                // Set RTL if needed
                if (isRtl) {
                    summaryEditor.format('direction', 'rtl');
                    summaryEditor.format('align', 'right');
                }

                // Load initial content
                const summaryTextarea = document.getElementById(`summary_${locale}`);
                if (summaryTextarea.value) {
                    summaryEditor.root.innerHTML = summaryTextarea.value;
                }

                // Sync to textarea on change
                summaryEditor.on('text-change', () => {
                    summaryTextarea.value = summaryEditor.root.innerHTML;
                    self.hasUnsavedChanges = true;
                });
            });
        },

        generateSlug(locale) {
            // Only auto-generate if user hasn't manually edited the slug
            if (!this.manualSlugEdit[locale]) {
                const title = this.formData.title[locale] || '';
                this.formData.slug[locale] = this.slugify(title);
            }
        },

        onSlugManualEdit(locale) {
            // Mark this locale's slug as manually edited
            this.manualSlugEdit[locale] = true;
        },

        slugify(text) {
            if (!text) return '';

            return text
                .toString()
                .trim()
                // Remove Arabic diacritics (tashkeel)
                .replace(/[\u064B-\u065F\u0670]/g, '')
                // Replace spaces and special chars with hyphens
                .replace(/[\s\/_]+/g, '-')
                // Keep only Arabic letters, English letters, numbers, and hyphens
                .replace(/[^\w\u0600-\u06FF\-]+/g, '')
                // Remove multiple consecutive hyphens
                .replace(/\-\-+/g, '-')
                // Remove leading/trailing hyphens
                .replace(/^-+/, '')
                .replace(/-+$/, '')
                // Convert to lowercase (for English parts)
                .toLowerCase();
        },

        handleThumbnailUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.thumbnailPreview = e.target.result;
                };
                reader.readAsDataURL(file);
                this.hasUnsavedChanges = true;
            }
        },

        removeGalleryItem(index) {
            this.formData.gallery.splice(index, 1);
            this.hasUnsavedChanges = true;
        },

        removeMediaItem(index) {
            this.formData.media_items.splice(index, 1);
            this.hasUnsavedChanges = true;
        },

        setupUnsavedChangesWarning() {
            window.addEventListener('beforeunload', (e) => {
                if (this.hasUnsavedChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        },

        handleMediaSelected(detail) {
            const { target, media, multiple } = detail;

            if (target === 'thumbnail') {
                // Handle thumbnail selection
                this.formData.thumb_id = media.id;
                this.thumbnailPreview = media.url;
                this.hasUnsavedChanges = true;
            } else if (target === 'gallery') {
                // Handle gallery selection
                if (multiple) {
                    // Add multiple images to gallery
                    media.forEach(item => {
                        if (!this.formData.gallery.find(g => g.id === item.id)) {
                            this.formData.gallery.push(item);
                        }
                    });
                } else {
                    // Add single image to gallery
                    if (!this.formData.gallery.find(g => g.id === media.id)) {
                        this.formData.gallery.push(media);
                    }
                }
                this.hasUnsavedChanges = true;
            }

            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        removeGalleryImage(index) {
            this.formData.gallery.splice(index, 1);
            this.hasUnsavedChanges = true;
        },

        saveDraft() {
            this.formData.status = 'draft';
            this.$el.querySelector('select[name="status"]').value = 'draft';
            this.$el.submit();
        },

        handleSubmit(e) {
            this.hasUnsavedChanges = false;
            // Form will submit normally
        }
    };
}
</script>
@endpush
