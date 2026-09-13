{{--
    ⚠️ DEPRECATED FILE - NOT IN USE
    This file is NOT referenced anywhere in the codebase.
    The active article form is _form-wizard.blade.php
    This file has been migrated to components for consistency but should be deleted.
    See: MODULE-ENHANCEMENT-PLAN-2026-08-17.md (Task M2.9)

    Article Form - Component-Based Version
    Migrated from monolithic template to use reusable Blade components
    Original: 1,290 lines → Refactored: ~150 lines
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
    $currentHijriDate = ''; // Set appropriately if needed
@endphp

{{-- Select2 Styles --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
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

        {{-- ==================== SIDEBAR ==================== --}}
        <x-admin.article.sidebar
            :article="$article"
            :formArticle="$formArticle"
            :categories="$categories"
            :writers="$writers"
            :tags="$tags"
            :currentHijriDate="$currentHijriDate"
        />

        {{-- ==================== MAIN CONTENT ==================== --}}
        <div class="lg:flex-1 order-1 lg:order-2">
            <div class="rounded-xl border"
                 :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">

                {{-- Tabs Header --}}
                <div class="border-b" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <nav class="flex gap-2 px-4 overflow-x-auto tabs-scroll" role="tablist">
                        <button type="button"
                                @click="activeTab = 'content'"
                                :class="activeTab === 'content' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors flex items-center gap-1.5">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            {{ __('articles.tabs.content') }}
                        </button>

                        <button type="button"
                                @click="activeTab = 'seo'"
                                :class="activeTab === 'seo' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors flex items-center gap-1.5">
                            <i data-lucide="search" class="w-4 h-4"></i>
                            {{ __('articles.tabs.seo_metadata') }}
                        </button>

                        <button type="button"
                                @click="activeTab = 'gallery'"
                                :class="activeTab === 'gallery' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors flex items-center gap-1.5">
                            <i data-lucide="images" class="w-4 h-4"></i>
                            {{ __('articles.tabs.gallery') }}
                        </button>

                        <button type="button"
                                @click="activeTab = 'media'"
                                :class="activeTab === 'media' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors flex items-center gap-1.5">
                            <i data-lucide="video" class="w-4 h-4"></i>
                            {{ __('articles.tabs.media') }}
                        </button>

                        <button type="button"
                                @click="activeTab = 'stats'"
                                :class="activeTab === 'stats' ?
                                    'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400' :
                                    'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                                class="px-4 py-3 font-medium text-sm whitespace-nowrap transition-colors flex items-center gap-1.5">
                            <i data-lucide="bar-chart" class="w-4 h-4"></i>
                            {{ __('articles.tabs.statistics_info') }}
                        </button>
                    </nav>
                </div>

                {{-- Tab Contents --}}
                <div class="p-6">
                    {{-- Content Tab --}}
                    <x-admin.article.tabs.content
                        :locales="$locales"
                        :article="$article"
                    />

                    {{-- SEO Tab --}}
                    <x-admin.article.tabs.seo
                        :locales="$locales"
                        :article="$article"
                    />

                    {{-- Gallery Tab --}}
                    <x-admin.article.tabs.gallery
                        :article="$article"
                    />

                    {{-- Media Tab --}}
                    <x-admin.article.tabs.media
                        :article="$article"
                    />

                    {{-- Statistics Tab --}}
                    <x-admin.article.tabs.stats
                        :article="$article"
                    />
                </div>

            </div>
        </div>

    </div>

    {{-- Form Actions --}}
    <div class="flex items-center justify-between gap-4 mt-6 p-4 rounded-xl border"
         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">
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

{{-- Scripts --}}
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>
<script>
// Component initialization scripts
window.tocManager = window.tocManager || function(config) {
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
                .replace(/[^\w؀-ۿ\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        }
    };
};

window.repeatableField = window.repeatableField || function(config) {
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
        activeTab: 'content',
        activeLang: 'ar',
        hasUnsavedChanges: false,
        thumbnailPreview: article?.thumb ? '{{ asset('storage') }}/' + article.thumb : null,
        manualSlugEdit: {},
        formData: {
            thumb_id: article?.thumb_id || null,
            writer_id: article?.writer_id || '',
            category_id: article?.category_id || '',
            is_featured: article?.is_featured || false,
            is_pinned: article?.is_pinned || false,
            status: article?.status || 'draft',
            scheduled_at: article?.scheduled_at || '',
            hijri_date: article?.hijri_date || '',
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
                this.manualSlugEdit[locale] = !!article?.locales?.[locale]?.slug;
            });
            window.addEventListener('media-selected', (event) => {
                this.handleMediaSelected(event.detail);
            });
            this.$nextTick(() => {
                this.initializeSelect2();
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
                this.formData.thumb_id = media.id;
                this.thumbnailPreview = media.url;
                this.hasUnsavedChanges = true;
            } else if (target === 'gallery') {
                if (multiple) {
                    media.forEach(item => {
                        if (!this.formData.gallery.find(g => g.id === item.id)) {
                            this.formData.gallery.push(item);
                        }
                    });
                } else {
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
        saveDraft() {
            this.formData.status = 'draft';
            this.$el.querySelector('select[name="status"]').value = 'draft';
            this.$el.submit();
        },
        handleSubmit(e) {
            this.hasUnsavedChanges = false;
        }
    };
}
</script>
@endpush
