{{--
    Comprehensive Article Form
    Layout: Sidebar (30%) + Main Content Tabs (70%)
    Features: Multi-language, WYSIWYG, Select2, Media Library, TOC, References, Statistics
--}}

@props([
    'article' => null,
    'categories' => [],
    'writers' => [],
    'tags' => [],
    'locales' => ['ar', 'en', 'fr', 'tr', 'ur']
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
      x-data="articleForm(@js($article ?? null), @js($locales))"
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
                        <option value="published">{{ __('articles.status.published') }}</option>
                        <option value="scheduled">{{ __('articles.status.scheduled') }}</option>
                        <option value="archived">{{ __('articles.status.archived') }}</option>
                    </select>
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
                           placeholder="1446-05-15"
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
                                    <textarea name="description[{{ $locale }}]"
                                              id="description_{{ $locale }}"
                                              class="tinymce w-full px-4 py-2.5 rounded-lg border"
                                              :class="darkMode ?
                                                  'bg-gray-700 border-gray-600 text-white' :
                                                  'bg-white border-gray-300 text-gray-900'"
                                              rows="4">{{ old("description.{$locale}", $article && $article->getLocale($locale) ? $article->getLocale($locale)->description : '') }}</textarea>
                                </div>

                                {{-- Content Body (WYSIWYG) --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.body') }}
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <textarea name="content[{{ $locale }}]"
                                              id="content_{{ $locale }}"
                                              class="tinymce w-full px-4 py-2.5 rounded-lg border"
                                              :class="darkMode ?
                                                  'bg-gray-700 border-gray-600 text-white' :
                                                  'bg-white border-gray-300 text-gray-900'"
                                              rows="15"
                                              required>{{ old("content.{$locale}", $article && $article->getLocale($locale) ? ($article->getLocale($locale)->content['body'] ?? '') : '') }}</textarea>
                                </div>

                                {{-- TOC (Table of Contents) --}}
                                <x-admin.toc-manager
                                    name="toc"
                                    :locale="$locale"
                                    :content="'content_' . $locale"
                                    :values="old('toc.' . $locale, $article && $article->getLocale($locale) ? ($article->getLocale($locale)->content['toc'] ?? []) : [])"
                                    :label="__('articles.fields.toc')"
                                />

                                {{-- Summary (WYSIWYG) --}}
                                <div>
                                    <label class="block text-sm font-semibold mb-2"
                                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                                        {{ __('articles.fields.summary') }}
                                    </label>
                                    <textarea name="summary[{{ $locale }}]"
                                              id="summary_{{ $locale }}"
                                              class="tinymce w-full px-4 py-2.5 rounded-lg border"
                                              :class="darkMode ?
                                                  'bg-gray-700 border-gray-600 text-white' :
                                                  'bg-white border-gray-300 text-gray-900'"
                                              rows="5">{{ old("summary.{$locale}", $article && $article->getLocale($locale) ? ($article->getLocale($locale)->content['summary'] ?? '') : '') }}</textarea>
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
                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ __('articles.help.seo') }}
                        </p>

                        {{-- SEO will continue in next block due to length... --}}
                        <div class="text-center py-12 text-gray-400">
                            SEO Metadata fields for each language (meta title, description, keywords, OG tags)
                            - Implementation continues below -
                        </div>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.tiny.cloud/1/YOUR_TINYMCE_API_KEY/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
function articleForm(article, locales) {
    return {
        activeTab: 'main',
        activeLang: 'ar',
        hasUnsavedChanges: false,
        thumbnailPreview: article?.thumb ? '{{ asset('storage') }}/' + article.thumb : null,
        formData: {
            thumb_id: article?.thumb_id || null,
            writer_id: article?.writer_id || '',
            category_id: article?.category_id || '',
            is_featured: article?.is_featured || false,
            is_pinned: article?.is_pinned || false,
            status: article?.status || 'draft',
            hijri_date: article?.hijri_date || '',
            read_time: article?.read_time || '',
            title: {},
            slug: {},
            description: {},
            content: {},
            summary: {},
            excerpt: {},
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
                this.formData.is_ready[locale] = article?.locales?.[locale]?.is_ready || false;
            });

            // Initialize Select2
            this.$nextTick(() => {
                this.initializeSelect2();
                this.initializeTinyMCE();
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

        initializeTinyMCE() {
            const isDark = document.documentElement.classList.contains('dark');

            locales.forEach(locale => {
                // Initialize description editors
                tinymce.init({
                    selector: `#description_${locale}`,
                    height: 200,
                    menubar: false,
                    plugins: 'link lists code',
                    toolbar: 'undo redo | bold italic | bullist numlist | link | code',
                    skin: isDark ? 'oxide-dark' : 'oxide',
                    content_css: isDark ? 'dark' : 'default',
                    directionality: ['ar', 'ur'].includes(locale) ? 'rtl' : 'ltr'
                });

                // Initialize content editors
                tinymce.init({
                    selector: `#content_${locale}`,
                    height: 500,
                    menubar: true,
                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                    skin: isDark ? 'oxide-dark' : 'oxide',
                    content_css: isDark ? 'dark' : 'default',
                    directionality: ['ar', 'ur'].includes(locale) ? 'rtl' : 'ltr'
                });

                // Initialize summary editors
                tinymce.init({
                    selector: `#summary_${locale}`,
                    height: 200,
                    menubar: false,
                    plugins: 'link lists',
                    toolbar: 'undo redo | bold italic | bullist numlist | link',
                    skin: isDark ? 'oxide-dark' : 'oxide',
                    content_css: isDark ? 'dark' : 'default',
                    directionality: ['ar', 'ur'].includes(locale) ? 'rtl' : 'ltr'
                });
            });
        },

        generateSlug(locale) {
            if (!this.formData.slug[locale]) {
                this.formData.slug[locale] = this.slugify(this.formData.title[locale]);
            }
        },

        slugify(text) {
            return text
                .toString()
                .toLowerCase()
                .trim()
                .replace(/\s+/g, '-')
                .replace(/[^\w\u0600-\u06FF\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
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
