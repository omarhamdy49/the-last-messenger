{{--
    Article Form - Single Page Layout
    Layout: Sticky Action Bar + Sidebar (aside) + Main Content Tabs
    Features: Multi-language, WYSIWYG (Quill), Select2, Media Library, TOC, Gallery, References, Statistics, Module Embeds
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
    $localeNames = ['ar' => 'العربية', 'en' => 'English', 'tr' => 'Türkçe', 'fr' => 'Français', 'ur' => 'اردو'];
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

    /* Quill Editor */
    .ql-toolbar.ql-snow { border-radius: 0.5rem 0.5rem 0 0; }
    .ql-container.ql-snow { border-radius: 0 0 0.5rem 0.5rem; }
    .dark .ql-toolbar.ql-snow { background: rgb(55, 65, 81); border-color: rgb(75, 85, 99); }
    .dark .ql-container.ql-snow { background: rgb(55, 65, 81); border-color: rgb(75, 85, 99); }
    .dark .ql-editor { color: white; }
    .dark .ql-toolbar .ql-stroke { stroke: #ccc; }
    .dark .ql-toolbar .ql-fill { fill: #ccc; }
    .dark .ql-toolbar .ql-picker-label { color: #ccc; }
    .dark .ql-editor.ql-blank::before { color: rgb(156, 163, 175); }

    /* Block editor Quill instances */
    .block-quill .ql-toolbar.ql-snow { border-top: none; border-radius: 0; border-left: none; border-right: none; }
    .block-quill .ql-container.ql-snow { border: none; border-radius: 0; resize: vertical; overflow: auto; min-height: 400px; }
    .block-quill .ql-editor { min-height: 380px; }

    /* Module toolbar + in-editor embed styles now ship with
       components/admin/form/wysiwyg.blade.php (modules prop). */

    /* Line clamp utility */
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    /* Sidebar card */
    .sidebar-card { transition: box-shadow 0.2s; }
    .sidebar-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

    /* Custom scrollbar for tabs */
    .tabs-scroll::-webkit-scrollbar { height: 2px; }
    .tabs-scroll::-webkit-scrollbar-track { background: transparent; }
    .tabs-scroll::-webkit-scrollbar-thumb { background: rgba(100,204,197,0.4); border-radius: 4px; }
</style>
@endpush

<form action="{{ $actionUrl }}"
      method="POST"
      enctype="multipart/form-data"
      x-data="articleForm(@js($formArticle ?? null), @js($locales))"
      @submit.prevent="handleSubmit"
      @episodes-selected.window="addEpisodes($event.detail)"
      class="article-form">

    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- Unsaved Changes Warning --}}
    <div x-show="showUnsavedToast" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed top-20 end-6 z-50 px-4 py-3 rounded-lg shadow-lg bg-yellow-500 text-white flex items-center gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-medium">{{ __('common.unsaved_changes') }}</span>
        <button type="button" @click="showUnsavedToast = false; toastDismissed = true"
                class="p-0.5 rounded hover:bg-yellow-600 transition-colors flex-shrink-0">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    {{-- ═══════════ STICKY ACTION BAR ═══════════ --}}
    <div class="sticky top-0 z-40 -mx-4 sm:-mx-6 lg:-mx-8 -mt-8 px-4 sm:px-6 lg:px-8 py-3 mb-6 border-b backdrop-blur-xl"
         :class="darkMode ? 'bg-gray-900/95 border-gray-700' : 'bg-white/95 border-gray-200'">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            {{-- Left: Back + Title --}}
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.articles.index') }}"
                   class="p-2 rounded-lg transition-colors flex-shrink-0"
                   :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <div class="min-w-0">
                    <h1 class="text-lg font-bold truncate"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        {{ $isEdit ? __('articles.edit') : __('articles.create') }}
                    </h1>
                    @if($isEdit)
                        <div class="flex items-center gap-3 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                            <span class="truncate">{{ $article->locales->first()->title ?? '' }}</span>
                            <span class="hidden md:inline-flex items-center gap-1">
                                <i data-lucide="clock" class="w-3 h-3"></i>
                                {{ $article->updated_at->diffForHumans() }}
                            </span>
                            <span class="hidden md:inline-flex items-center gap-1">
                                <i data-lucide="eye" class="w-3 h-3"></i>
                                {{ number_format($article->views) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right: Actions --}}
            <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
                @if($isEdit)
                    <a href="{{ route('admin.articles.show', $article) }}"
                       class="px-3 py-2 rounded-lg text-sm transition-colors flex items-center gap-1.5"
                       :class="darkMode ? 'text-gray-400 hover:bg-gray-700 hover:text-gray-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">{{ __('common.view') }}</span>
                    </a>
                    <button type="button"
                            @click.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => {
                                const f=document.createElement('form');
                                f.method='POST';
                                f.action='{{ route('admin.articles.destroy', $article) }}';
                                const csrf=document.createElement('input');csrf.type='hidden';csrf.name='_token';csrf.value='{{ csrf_token() }}';f.appendChild(csrf);
                                const method=document.createElement('input');method.type='hidden';method.name='_method';method.value='DELETE';f.appendChild(method);
                                document.body.appendChild(f);f.submit();
                            } })"
                            class="px-3 py-2 rounded-lg text-sm transition-colors flex items-center gap-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span class="hidden sm:inline">{{ __('common.delete') }}</span>
                    </button>
                    <div class="w-px h-6 mx-1 hidden sm:block" :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"></div>
                @endif
                <button type="button" @click="saveDraft"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors border"
                        :class="darkMode
                            ? 'border-gray-600 text-gray-300 hover:bg-gray-700'
                            : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    <i data-lucide="save" class="w-4 h-4 inline me-1"></i>
                    {{ __('articles.actions.save_draft') }}
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg transition-all">
                    <i data-lucide="check" class="w-4 h-4 inline me-1"></i>
                    {{ $isEdit ? __('common.update') : __('common.create') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════ MAIN LAYOUT ═══════════ --}}
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- ==================== SIDEBAR ==================== --}}
        <aside class="w-full lg:w-80 xl:w-96 space-y-4 order-2 lg:order-1">

            {{-- Thumbnail --}}
            <div class="sidebar-card rounded-xl p-4"
                 :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
                <h3 class="text-sm font-semibold mb-3 flex items-center gap-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="image" class="w-4 h-4 text-gray-400"></i>
                    {{ __('articles.fields.thumb') }}
                </h3>
                <div class="relative aspect-video bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden mb-3">
                    <template x-if="thumbnailPreview">
                        <img :src="thumbnailPreview" class="w-full h-full object-cover" alt="Thumbnail">
                    </template>
                    <template x-if="!thumbnailPreview">
                        <div class="flex items-center justify-center h-full text-gray-400">
                            <i data-lucide="image-plus" class="w-10 h-10"></i>
                        </div>
                    </template>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button"
                            @click="$dispatch('open-media-library', { target: 'thumbnail', multiple: false })"
                            class="px-3 py-2 text-xs rounded-lg border transition-colors text-center"
                            :class="darkMode ? 'border-gray-600 hover:bg-gray-700' : 'border-gray-300 hover:bg-gray-50'">
                        <i data-lucide="folder-open" class="w-3.5 h-3.5 inline me-1"></i>
                        {{ __('articles.fields.select_thumbnail') }}
                    </button>
                    <label class="px-3 py-2 text-xs rounded-lg border transition-colors cursor-pointer text-center"
                           :class="darkMode ? 'border-gray-600 hover:bg-gray-700' : 'border-gray-300 hover:bg-gray-50'">
                        <i data-lucide="upload" class="w-3.5 h-3.5 inline me-1"></i>
                        {{ __('common.upload') }}
                        <input type="file" name="thumb" accept="image/jpeg,image/png,image/webp" @change="handleThumbnailUpload" class="hidden">
                    </label>
                </div>
                <input type="hidden" name="thumb_id" x-model="formData.thumb_id">
            </div>

            {{-- Publication --}}
            <div class="sidebar-card rounded-xl p-4 space-y-3"
                 :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
                <h3 class="text-sm font-semibold flex items-center gap-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                    {{ __('articles.sidebar.publication') }}
                </h3>

                {{-- Status --}}
                <div>
                    <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        {{ __('articles.fields.status') }}
                    </label>
                    <select name="status" x-model="formData.status"
                            class="w-full px-3 py-2 rounded-lg border text-sm"
                            :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="draft">{{ __('articles.status.draft') }}</option>
                        <option value="pending">{{ __('articles.status.pending') }}</option>
                        <option value="published">{{ __('articles.status.published') }}</option>
                        <option value="scheduled">{{ __('articles.status.scheduled') }}</option>
                        <option value="archived">{{ __('articles.status.archived') }}</option>
                    </select>
                </div>

                {{-- Scheduled Date --}}
                <div x-show="formData.status === 'scheduled'" x-transition x-cloak>
                    <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        {{ __('articles.fields.scheduled_at') }}
                    </label>
                    <input type="datetime-local" name="scheduled_at" x-model="formData.scheduled_at"
                           class="w-full px-3 py-2 rounded-lg border text-sm"
                           :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>

                {{-- Hijri Date (calendar picker) --}}
                <div>
                    <x-admin.hijri-date-picker
                        name="hijri_date"
                        :label="__('articles.fields.hijri_date')"
                        :value="$formArticle['hijri_date'] ?? ($currentHijriDate ?? '')" />
                </div>

                {{-- Read Time --}}
                <div>
                    <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        {{ __('articles.fields.read_time_minutes') }}
                    </label>
                    <input type="number" name="read_time" x-model="formData.read_time" min="1" max="999"
                           class="w-full px-3 py-2 rounded-lg border text-sm"
                           :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>

                {{-- Views (edit only) --}}
                @if($isEdit)
                <div>
                    <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        {{ __('articles.fields.views') }}
                    </label>
                    <div class="px-3 py-2 rounded-lg border text-sm flex items-center gap-2"
                         :class="darkMode ? 'bg-gray-900 border-gray-600' : 'bg-gray-50 border-gray-300'">
                        <i data-lucide="eye" class="w-4 h-4 text-gray-400"></i>
                        <span class="font-semibold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ number_format($article->views ?? 0) }}
                        </span>
                    </div>
                </div>
                @endif
            </div>

            {{-- Writer --}}
            <div class="sidebar-card rounded-xl p-4"
                 :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
                <label class="block text-sm font-semibold mb-2 flex items-center gap-2"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                    {{ __('articles.fields.writer') }}
                </label>
                <select name="writer_id" x-model="formData.writer_id"
                        class="select2-writer w-full px-3 py-2 rounded-lg border text-sm"
                        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('articles.placeholders.select_writer') }}</option>
                    @foreach($writers as $writer)
                        <option value="{{ $writer->id }}"
                                {{ old('writer_id', $article ? $article->writer_id : '') == $writer->id ? 'selected' : '' }}>
                            {{ $writer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Categories --}}
            <div class="sidebar-card rounded-xl p-4 space-y-3"
                 :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
                <h3 class="text-sm font-semibold flex items-center gap-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="folder" class="w-4 h-4 text-gray-400"></i>
                    {{ __('articles.fields.categories') }}
                </h3>

                {{-- Primary Category --}}
                <div>
                    <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        {{ __('articles.fields.primary_category') }}
                    </label>
                    <select name="category_id" x-model="formData.category_id"
                            class="select2-category w-full px-3 py-2 rounded-lg border text-sm"
                            :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <option value="">{{ __('articles.placeholders.select_category') }}</option>
                        @foreach($categories as $catId => $catName)
                            <option value="{{ $catId }}"
                                    {{ old('category_id', $article ? $article->category_id : '') == $catId ? 'selected' : '' }}>
                                {{ $catName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Additional Categories --}}
                <div>
                    <label class="block text-xs font-medium mb-1" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
                        {{ __('articles.fields.additional_categories') }}
                    </label>
                    <select name="additional_categories[]" multiple
                            class="select2-categories w-full px-3 py-2 rounded-lg border text-sm"
                            :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        @foreach($categories as $catId => $catName)
                            <option value="{{ $catId }}"
                                    {{ in_array($catId, old('additional_categories', $article && $article->categories ? $article->categories->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                                {{ $catName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Tags --}}
            <div class="sidebar-card rounded-xl p-4"
                 :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
                <label class="block text-sm font-semibold mb-2 flex items-center gap-2"
                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="tag" class="w-4 h-4 text-gray-400"></i>
                    {{ __('articles.fields.tags') }}
                </label>
                <select name="tags[]" multiple
                        class="select2-tags w-full px-3 py-2 rounded-lg border text-sm"
                        :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @foreach($tags as $tag)
                        <option value="{{ $tag->id }}"
                                {{ in_array($tag->id, old('tags', $article && $article->tags ? $article->tags->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                            {{ $tag->getTranslation('name') ?? $tag->slug }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Options --}}
            <div class="sidebar-card rounded-xl p-4 space-y-3"
                 :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">
                <h3 class="text-sm font-semibold flex items-center gap-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="settings" class="w-4 h-4 text-gray-400"></i>
                    {{ __('articles.sidebar.settings') }}
                </h3>
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('articles.fields.is_featured') }}
                    </span>
                    <input type="checkbox" name="is_featured" value="1" x-model="formData.is_featured"
                           class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500">
                </label>
                <label class="flex items-center justify-between cursor-pointer">
                    <span class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        {{ __('articles.fields.is_pinned') }}
                    </span>
                    <input type="checkbox" name="is_pinned" value="1" x-model="formData.is_pinned"
                           class="w-5 h-5 rounded text-blue-600 focus:ring-blue-500">
                </label>
            </div>

        </aside>

        {{-- ==================== MAIN CONTENT ==================== --}}
        <main class="flex-1 min-w-0 order-1 lg:order-2">
            <div class="rounded-xl overflow-hidden"
                 :class="darkMode ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'">

                {{-- Tab Navigation --}}
                <div class="border-b" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <nav class="flex gap-1 px-4 pt-1 overflow-x-auto tabs-scroll" role="tablist">
                        @php
                            $tabs = [
                                ['key' => 'main', 'icon' => 'file-text', 'label' => __('articles.tabs.main_info')],
                                ['key' => 'content', 'icon' => 'layers', 'label' => __('articles.tabs.content')],
                                ['key' => 'seo', 'icon' => 'search', 'label' => __('articles.tabs.seo_metadata')],
                                ['key' => 'gallery', 'icon' => 'images', 'label' => __('articles.tabs.gallery')],
                                ['key' => 'media', 'icon' => 'video', 'label' => __('articles.tabs.media')],
                                ['key' => 'statistics', 'icon' => 'bar-chart-2', 'label' => __('articles.tabs.statistics_info')],
                            ];
                        @endphp
                        @foreach($tabs as $tab)
                            <button type="button"
                                    @click="activeTab = '{{ $tab['key'] }}'"
                                    :class="activeTab === '{{ $tab['key'] }}'
                                        ? 'border-b-2 border-[#0056b3] text-[#0056b3] dark:text-[#64CCC5] dark:border-[#64CCC5]'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="px-3 py-3 font-medium text-sm whitespace-nowrap transition-colors flex items-center gap-1.5">
                                <i data-lucide="{{ $tab['icon'] }}" class="w-4 h-4"></i>
                                {{ $tab['label'] }}
                                @if($tab['key'] === 'content')
                                    <span x-show="getTotalBlockCount() > 0"
                                          class="px-1.5 py-0.5 text-xs rounded-full bg-[#64CCC5] text-white"
                                          x-text="getTotalBlockCount()"></span>
                                @elseif($tab['key'] === 'gallery')
                                    <span x-show="formData.gallery.length > 0"
                                          class="px-1.5 py-0.5 text-xs rounded-full bg-[#218838] text-white"
                                          x-text="formData.gallery.length"></span>
                                @elseif($tab['key'] === 'media')
                                    <span x-show="formData.media_items.length > 0"
                                          class="px-1.5 py-0.5 text-xs rounded-full bg-[#0056b3] text-white"
                                          x-text="formData.media_items.length"></span>
                                @endif
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Tab Content --}}
                <div class="p-5">

                    {{-- ═══════ TAB: CONTENT (Multi-Language Block Builder) ═══════ --}}
                    <div x-show="activeTab === 'main'" x-transition class="space-y-5">

                        {{-- Language Sub-Tabs --}}
                        <div class="flex gap-2 overflow-x-auto tabs-scroll pb-1">
                            @foreach($locales as $locale)
                                <button type="button"
                                        @click="activeLang = '{{ $locale }}'"
                                        :class="activeLang === '{{ $locale }}'
                                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm'
                                            : (darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')"
                                        class="px-4 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap flex items-center gap-1.5">
                                    {{ strtoupper($locale) }}
                                    <span x-show="formData.is_ready?.{{ $locale }}"
                                          class="w-2 h-2 bg-green-400 rounded-full inline-block"></span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Per-Locale Content Panels --}}
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
                            <div>
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.fields.description') }}
                                </label>
                                <div id="description_{{ $locale }}_editor" class="quill-editor" style="height: 120px;"></div>
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
                    </div>

                    {{-- ═══════ TAB: CONTENT (Block Builder) ═══════ --}}
                    <div x-show="activeTab === 'content'" x-transition class="space-y-5">

                        {{-- Language Sub-Tabs --}}
                        <div class="flex gap-2 overflow-x-auto tabs-scroll pb-1">
                            @foreach($locales as $locale)
                                <button type="button"
                                        @click="activeLang = '{{ $locale }}'"
                                        :class="activeLang === '{{ $locale }}'
                                            ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm'
                                            : (darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')"
                                        class="px-4 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap flex items-center gap-1.5">
                                    {{ strtoupper($locale) }}
                                    <span x-show="getBlocks('{{ $locale }}').length > 0"
                                          class="px-1.5 py-0.5 text-xs rounded-full bg-white/20"
                                          x-text="getBlocks('{{ $locale }}').length"></span>
                                </button>
                            @endforeach
                        </div>

                        {{-- Per-Locale Content Panels --}}
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
                                            <div x-show="block.type === 'wysiwyg'" class="p-0">
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
                            <div>
                                <label class="block text-sm font-semibold mb-1.5"
                                       :class="darkMode ? 'text-white' : 'text-gray-900'">
                                    {{ __('articles.fields.summary') }}
                                </label>
                                <div id="summary_{{ $locale }}_editor" class="quill-editor" style="height: 120px;"></div>
                                <textarea name="summary[{{ $locale }}]"
                                          id="summary_{{ $locale }}"
                                          class="hidden">{{ old("summary.{$locale}", $article && $article->getArticleLocale($locale) ? ($article->getArticleLocale($locale)->content['summary'] ?? '') : '') }}</textarea>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- ═══════ TAB: SEO ═══════ --}}
                    <div x-show="activeTab === 'seo'" x-transition class="space-y-5">

                        {{-- Language Tabs for SEO --}}
                        <div class="flex gap-2 overflow-x-auto tabs-scroll pb-1">
                            @foreach($locales as $locale)
                            <button type="button" @click="activeLang = '{{ $locale }}'"
                                    :class="activeLang === '{{ $locale }}'
                                        ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-sm'
                                        : (darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-600 hover:bg-gray-200')"
                                    class="px-4 py-1.5 rounded-full text-sm font-medium transition-all whitespace-nowrap">
                                {{ strtoupper($locale) }}
                            </button>
                            @endforeach
                        </div>

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
                    </div>

                    {{-- ═══════ TAB: GALLERY ═══════ --}}
                    <div x-show="activeTab === 'gallery'" x-transition class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ __('articles.help.gallery') }}
                            </p>
                            <button type="button"
                                    @click="$dispatch('open-media-library', { target: 'gallery', multiple: true })"
                                    class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2 text-sm">
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
                             class="text-center py-16 border-2 border-dashed rounded-lg"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                            <i data-lucide="images" class="w-12 h-12 mx-auto mb-3 text-gray-400"></i>
                            <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('articles.empty.gallery') }}</p>
                        </div>
                    </div>

                    {{-- ═══════ TAB: MEDIA ═══════ --}}
                    <div x-show="activeTab === 'media'" x-transition class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ __('articles.help.media') }}
                            </p>
                            <button type="button"
                                    @click="$dispatch('open-media-library', { target: 'media_items', multiple: true })"
                                    class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2 text-sm">
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
                             class="text-center py-16 border-2 border-dashed rounded-lg"
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
                                        class="px-4 py-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white rounded-lg hover:shadow-lg transition-all flex items-center gap-2 text-sm shrink-0">
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
                                 class="text-center py-10 border-2 border-dashed rounded-lg"
                                 :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                                <i data-lucide="list-video" class="w-10 h-10 mx-auto mb-2 text-gray-400"></i>
                                <p class="text-sm" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('articles.empty.episodes') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- ═══════ TAB: STATISTICS & REFERENCES ═══════ --}}
                    <div x-show="activeTab === 'statistics'" x-transition class="space-y-6">
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

                </div>
            </div>
        </main>

    </div>
</form>

{{-- ═══════════ EPISODE PICKER MODAL ═══════════
     Lives outside the <form> like the module selector, so it hands its
     selection back through a window event rather than reaching into the
     form's Alpine scope. Server-side paginated: a synced channel runs to
     hundreds of episodes, so the list is never fetched whole. --}}
<div x-data="episodePicker()" x-show="show" x-cloak
     @open-episode-picker.window="open()"
     @keydown.escape.window="show && close()"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="close()"></div>

    <div class="relative w-full max-w-3xl max-h-[85vh] rounded-xl shadow-2xl flex flex-col overflow-hidden"
         :class="darkMode ? 'bg-gray-800' : 'bg-white'" @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between p-4 border-b shrink-0"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <h3 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                {{ __('articles.actions.assign_episodes') }}
            </h3>
            <button type="button" @click="close()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                    aria-label="{{ __('articles.public.close') }}">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        {{-- Search + filter --}}
        <div class="p-4 grid gap-3 sm:grid-cols-[1fr_14rem] shrink-0 border-b"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <div class="relative">
                <i data-lucide="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"></i>
                <input type="search" x-model.debounce.300ms="q" @input="page = 1; fetch()"
                       placeholder="{{ __('articles.placeholders.search_episodes') }}"
                       class="w-full ps-9 pe-3 py-2 rounded-lg border text-sm"
                       :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300'">
            </div>
            <select x-model="program" @change="page = 1; fetch()"
                    aria-label="{{ __('articles.fields.program') }}"
                    class="w-full px-3 py-2 rounded-lg border text-sm"
                    :class="darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-white border-gray-300'">
                <option value="">{{ __('articles.fields.all_programs') }}</option>
                <template x-for="p in programs" :key="p.id">
                    <option :value="p.id" x-text="p.label"></option>
                </template>
            </select>
        </div>

        {{-- Results --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-2 min-h-[16rem]">
            <template x-if="loading">
                <div class="py-16 text-center text-sm text-gray-500">{{ __('articles.public.loading') }}</div>
            </template>

            <template x-if="!loading && rows.length === 0">
                <div class="py-16 text-center">
                    <i data-lucide="list-video" class="w-10 h-10 mx-auto mb-2 text-gray-400"></i>
                    <p class="text-sm text-gray-500">{{ __('articles.empty.episodes_search') }}</p>
                </div>
            </template>

            <template x-for="row in rows" :key="row.id">
                <label class="flex items-center gap-3 p-2 rounded-lg border cursor-pointer transition-colors"
                       :class="isPicked(row.id)
                            ? 'border-[#0056b3] bg-[#0056b3]/5'
                            : (darkMode ? 'border-gray-700 hover:bg-gray-700/50' : 'border-gray-200 hover:bg-gray-50')">
                    <input type="checkbox" class="w-4 h-4 shrink-0"
                           :checked="isPicked(row.id)" @change="toggle(row)">
                    <img :src="row.thumb" x-show="row.thumb" alt=""
                         class="w-24 h-14 rounded object-cover bg-gray-200 dark:bg-gray-700 shrink-0">
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-medium truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="row.title"></span>
                        <span class="block text-xs text-gray-500 truncate">
                            <span x-text="row.program"></span>
                            <span x-show="row.season">· S<span x-text="row.season"></span></span>
                            <span x-show="row.episode">E<span x-text="row.episode"></span></span>
                        </span>
                    </span>
                    {{-- Unpublished episodes stay pickable but are flagged: the
                         reader filters them out until they go live. --}}
                    <span x-show="!row.is_published"
                          class="shrink-0 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        {{ __('articles.status.draft') }}
                    </span>
                </label>
            </template>
        </div>

        {{-- Pagination + confirm --}}
        <div class="p-4 border-t flex items-center justify-between gap-3 shrink-0"
             :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
            <div class="flex items-center gap-2">
                <button type="button" @click="go(-1)" :disabled="meta.current_page <= 1"
                        class="px-3 py-1.5 rounded-lg border text-sm disabled:opacity-40"
                        :class="darkMode ? 'border-gray-600' : 'border-gray-300'">
                    {{ __('articles.public.previous') }}
                </button>
                <span class="text-xs text-gray-500 tabular-nums">
                    <span x-text="meta.current_page"></span> / <span x-text="meta.last_page || 1"></span>
                </span>
                <button type="button" @click="go(1)" :disabled="meta.current_page >= (meta.last_page || 1)"
                        class="px-3 py-1.5 rounded-lg border text-sm disabled:opacity-40"
                        :class="darkMode ? 'border-gray-600' : 'border-gray-300'">
                    {{ __('articles.public.next') }}
                </button>
            </div>

            <button type="button" @click="confirm()" :disabled="picked.length === 0"
                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] disabled:opacity-40">
                {{ __('articles.actions.add_selected') }}
                <span x-show="picked.length > 0">(<span x-text="picked.length"></span>)</span>
            </button>
        </div>
    </div>
</div>

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
            const contentTab = document.querySelector('[x-show="activeTab === \'content\'"]');
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
function articleForm(article, locales) {
    return {
        activeTab: 'main',
        activeLang: 'ar',
        darkMode: localStorage.getItem('darkMode') === 'true',
        hasUnsavedChanges: false,
        showUnsavedToast: false,
        toastDismissed: false,
        _unsavedTimer: null,
        thumbnailPreview: article?.thumb ? '{{ asset("storage") }}/' + article.thumb : null,
        manualSlugEdit: {},
        quillEditors: {},
        blockIdCounter: 0,
        contentBlocks: {},
        formData: {
            thumb_id: article?.thumb_id || null,
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

            // Watch for unsaved changes and show toast with delay
            this.$watch('hasUnsavedChanges', (val) => {
                clearTimeout(this._unsavedTimer);
                if (val && !this.toastDismissed) {
                    this._unsavedTimer = setTimeout(() => {
                        this.showUnsavedToast = true;
                        this.$nextTick(() => lucide.createIcons());
                    }, 2000);
                } else if (!val) {
                    this.showUnsavedToast = false;
                    this.toastDismissed = false;
                }
            });

            this.$nextTick(() => {
                this.initializeSelect2();
                this.initializeRichText();
                this.setupUnsavedChangesWarning();
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

            // Add custom module insert buttons to toolbar
            this.addModuleToolbarButtons(editor, blockId, locale);

            editor.on('text-change', () => {
                const b = this.contentBlocks[locale]?.find(b => b.id === blockId);
                if (b) b.content = editor.root.innerHTML;
                this.syncContentBlocks(locale);
                this.hasUnsavedChanges = true;
            });

            this.quillEditors[editorKey] = editor;
        },

        addModuleToolbarButtons(editor, blockId, locale) {
            const toolbar = editor.getModule('toolbar').container;
            const btnGroup = document.createElement('span');
            btnGroup.className = 'ql-formats ql-module-btns';

            const modules = [
                { type: 'verse', label: '{{ __("articles.modules.quran_short") }}', cls: 'ql-module-btn--verse',
                  icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>' },
                { type: 'hadith', label: '{{ __("articles.modules.hadith_short") }}', cls: 'ql-module-btn--hadith',
                  icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>' },
                { type: 'sahabi', label: '{{ __("articles.modules.sahabi_short") }}', cls: 'ql-module-btn--sahabi',
                  icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' },
                { type: 'seerah', label: '{{ __("articles.modules.seerah_short") }}', cls: 'ql-module-btn--seerah',
                  icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>' }
            ];

            modules.forEach(mod => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `ql-module-btn ${mod.cls}`;
                btn.innerHTML = `${mod.icon}<span>${mod.label}</span>`;
                btn.title = mod.label;
                btn.addEventListener('mousedown', () => {
                    const range = editor.getSelection();
                    this.activeInlineSelection = range
                        ? { index: range.index, length: range.length }
                        : { index: Math.max(0, editor.getLength() - 1), length: 0 };
                });
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!this.activeInlineSelection) {
                        const range = editor.getSelection();
                        this.activeInlineSelection = range
                            ? { index: range.index, length: range.length }
                            : { index: Math.max(0, editor.getLength() - 1), length: 0 };
                    }
                    this.activeInlineQuillId = blockId;
                    this.activeInlineLocale = locale;
                    window.dispatchEvent(new CustomEvent('open-module-selector', {
                        detail: { type: mod.type, locale, insertMode: 'inline', quillBlockId: blockId }
                    }));
                });
                btnGroup.appendChild(btn);
            });

            toolbar.appendChild(btnGroup);
        },

        activeInlineQuillId: null,
        activeInlineLocale: null,
        activeInlineSelection: null,

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
            if (!editor) return;

            const range = this.activeInlineSelection || editor.getSelection() || {
                index: Math.max(0, editor.getLength() - 1),
                length: 0
            };

            if (moduleType === 'sahabi') {
                const link = `/sahaba/${encodeURIComponent(data.slug || data.sahabi_id)}`;

                if (range.length > 0) {
                    editor.formatText(range.index, range.length, 'link', link, 'user');
                    editor.setSelection(range.index, range.length, 'silent');
                } else {
                    const label = data.name || '';
                    const previousCharacter = range.index > 0
                        ? editor.getText(range.index - 1, 1)
                        : '';
                    const leadingSeparator = previousCharacter && !/\s/.test(previousCharacter) ? ' ' : '';
                    const labelIndex = range.index + leadingSeparator.length;

                    if (leadingSeparator) {
                        editor.insertText(range.index, leadingSeparator, { link: false }, 'user');
                    }
                    editor.insertText(labelIndex, label, { link }, 'user');
                    editor.insertText(labelIndex + label.length, ' ', { link: false }, 'user');
                    editor.setSelection(labelIndex + label.length + 1, 0, 'silent');
                    editor.format('link', false, 'silent');
                }

                const block = this.contentBlocks[locale]?.find(item => item.id === quillBlockId);
                if (block) block.content = editor.root.innerHTML;
                this.syncContentBlocks(locale);
                this.hasUnsavedChanges = true;
                this.activeInlineSelection = null;
                editor.focus();
                return;
            }

            // Build a structured payload rather than an HTML string: the
            // moduleEmbed blot (resources/js/admin/rich-text.js) is what keeps
            // data-module/data-id alive through Quill, so the article stays
            // linked to the verse/hadith/event it cites.
            let embed = null;
            const moduleDirection = ['ar', 'ur', 'fa', 'he'].includes(locale) ? 'rtl' : 'ltr';

            if (moduleType === 'verse') {
                const versesText = data.verses.map(v => v.text).join(' ');
                const verseRange = data.verses.length > 1
                    ? `${data.verses[0].number}-${data.verses[data.verses.length-1].number}`
                    : String(data.verses[0].number);
                embed = {
                    module: 'verse',
                    id: data.surah_id,
                    label: @js(__('articles.modules.quran')),
                    text: versesText,
                    reference: `${data.surah_name} — {{ __('articles.blocks.verse') }} ${verseRange}`,
                    href: `/quran/surah/${data.surah_id}#${data.verses[0].number}`,
                    linkLabel: `📖 ${data.surah_name} — {{ __('articles.blocks.verse') }} ${verseRange}`,
                    dir: 'rtl',
                };
            } else if (moduleType === 'hadith') {
                embed = {
                    module: 'hadith',
                    id: data.hadith_id,
                    label: @js(__('articles.modules.hadith')),
                    text: data.text,
                    reference: `${data.collection} #${data.number}`,
                    href: `/hadith/${data.hadith_id}`,
                    linkLabel: `📜 ${data.collection} #${data.number}`,
                    dir: moduleDirection,
                };
            } else if (moduleType === 'seerah') {
                embed = {
                    module: 'seerah',
                    id: data.event_id,
                    label: @js(__('articles.modules.seerah')),
                    text: `${data.title}${data.hijri_year ? ' — ' + data.hijri_year + ' {{ __("articles.blocks.hijri") }}' : ''}`,
                    href: `/seerah/${data.slug || data.event_id}`,
                    linkLabel: `📍 {{ __('articles.embed.view_event') }}`,
                    dir: moduleDirection,
                };
            }
            // 'sahabi' never reaches here — companions read better as an inline
            // link inside the sentence, handled by the early return above.

            if (embed) {
                const insertionIndex = range.index;
                if (range.length > 0) {
                    editor.deleteText(insertionIndex, range.length, 'user');
                }

                editor.insertEmbed(insertionIndex, 'moduleEmbed', embed, 'user');
                // Leave an empty paragraph after the card so the editor can keep
                // typing below it instead of being trapped against the embed.
                editor.insertText(insertionIndex + 1, '\n', 'user');
                editor.setSelection(insertionIndex + 2, 0, 'silent');

                const block = this.contentBlocks[locale]?.find(item => item.id === quillBlockId);
                if (block) block.content = editor.root.innerHTML;
                this.syncContentBlocks(locale);
                this.hasUnsavedChanges = true;
            }

            this.activeInlineSelection = null;
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
                placeholder: function() { return $(this).data('placeholder') || '{{ __("common.select") }}...'; },
                allowClear: true,
                dir: dir
            });
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

        handleThumbnailUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => { this.thumbnailPreview = e.target.result; };
                reader.readAsDataURL(file);
                this.hasUnsavedChanges = true;
            }
        },

        handleMediaSelected(detail) {
            const { target, media, multiple } = detail;
            if (target === 'thumbnail') {
                this.formData.thumb_id = media.id;
                this.thumbnailPreview = media.url;
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

        setupUnsavedChangesWarning() {
            window.addEventListener('beforeunload', (e) => {
                if (this.hasUnsavedChanges) { e.preventDefault(); e.returnValue = ''; }
            });
        },

        saveDraft() {
            this.formData.status = 'draft';
            const statusEl = this.$el.querySelector('select[name="status"]');
            if (statusEl) statusEl.value = 'draft';
            this.handleSubmit();
        },

        handleSubmit() {
            // Serialize content blocks to hidden textareas
            locales.forEach(locale => {
                this.syncAllBlockQuills(locale);
                this.syncContentBlocks(locale);
            });
            this.hasUnsavedChanges = false;
            this.$el.submit();
        }
    };
}

// Module Selector Alpine Component
// moduleSelector() moved to components/admin/module-selector-modal.blade.php
function episodePicker() {
    return {
        show: false,
        loading: false,
        q: '',
        program: '',
        programs: [],
        rows: [],
        picked: [],
        page: 1,
        meta: { current_page: 1, last_page: 1, total: 0 },
        get darkMode() { return document.documentElement.classList.contains('dark'); },

        open() {
            this.show = true;
            this.picked = [];
            if (this.programs.length === 0) this.loadPrograms();
            this.fetch();
        },

        close() { this.show = false; },

        async loadPrograms() {
            try {
                const res = await fetch('{{ route('admin.articles.modules.episode-programs') }}', { headers: { 'Accept': 'application/json' } });
                this.programs = (await res.json()).data || [];
            } catch (e) { this.programs = []; }
        },

        async fetch() {
            this.loading = true;
            try {
                const url = new URL('{{ route('admin.articles.modules.episodes') }}', window.location.origin);
                url.searchParams.set('page', this.page);
                if (this.q) url.searchParams.set('q', this.q);
                if (this.program) url.searchParams.set('program', this.program);

                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                this.rows = json.data || [];
                this.meta = json.meta || { current_page: 1, last_page: 1, total: 0 };
            } catch (e) {
                this.rows = [];
            } finally {
                this.loading = false;
                this.$nextTick(() => window.lucide?.createIcons());
            }
        },

        go(delta) {
            const next = this.page + delta;
            if (next < 1 || next > (this.meta.last_page || 1)) return;
            this.page = next;
            this.fetch();
        },

        isPicked(id) { return this.picked.some(e => e.id === id); },

        // Selection survives paging and re-searching, so an editor can gather
        // episodes from several pages before confirming once.
        toggle(row) {
            const i = this.picked.findIndex(e => e.id === row.id);
            i === -1 ? this.picked.push(row) : this.picked.splice(i, 1);
        },

        confirm() {
            window.dispatchEvent(new CustomEvent('episodes-selected', { detail: this.picked }));
            this.close();
        },
    };
}
</script>
@endpush
