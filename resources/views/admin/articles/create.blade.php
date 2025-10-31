@extends('layouts.admin')

@section('title', 'Create New Article')

@push('styles')
<style>
    .tab-button.active {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
    }

    .char-counter {
        position: absolute;
        bottom: 8px;
        left: 12px;
        font-size: 0.75rem;
        pointer-events: none;
    }

    .content-field-wrapper {
        position: relative;
    }

    /* Custom tag checkbox styling */
    .tag-checkbox:checked + label {
        background: linear-gradient(135deg, rgba(0, 86, 179, 0.1) 0%, rgba(33, 136, 56, 0.1) 100%);
        border-color: #0056b3;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8" x-data="articleForm()" x-init="init()">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="file-plus" class="w-8 h-8 inline-block ml-2"></i>
                    Create New Article
                </h1>
                <p class="mt-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Create a new Seerah article with multi-language support
                </p>
            </div>
            <a href="{{ route('admin.articles.index') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors"
               :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-900'">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                Back to Articles
            </a>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data"
          @submit="handleSubmit"
          class="transition-colors">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content Column (Left - 2/3 width) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Multi-language Content Card -->
                <div class="rounded-2xl p-6 shadow-xl transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                    <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="languages" class="w-6 h-6"></i>
                        Article Content (Multi-language)
                    </h2>

                    <!-- Language Tabs -->
                    <div class="flex gap-2 mb-6 border-b pb-2 overflow-x-auto" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                        @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                            <button type="button"
                                    @click="activeLanguage = '{{ $lang }}'"
                                    :class="activeLanguage === '{{ $lang }}' ? 'tab-button active' : ''"
                                    class="tab-button px-4 py-2 rounded-lg transition-all whitespace-nowrap"
                                    :class="darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-700 hover:bg-gray-200'">
                                {{ $label }}
                                @if($lang === 'ar')
                                    <span class="text-red-400 ml-1">*</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <!-- Title Fields (Multi-language) -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <i data-lucide="type" class="w-4 h-4 inline-block ml-1"></i>
                            Article Title
                        </label>
                        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                            <div x-show="activeLanguage === '{{ $lang }}'" x-cloak>
                                <div class="content-field-wrapper">
                                    <input type="text"
                                           name="title[{{ $lang }}]"
                                           x-model="form.title.{{ $lang }}"
                                           @input="updateCharCount('title', '{{ $lang }}')"
                                           maxlength="500"
                                           {{ $lang === 'ar' ? 'required' : '' }}
                                           class="w-full px-4 py-3 pb-8 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                                           placeholder="{{ $lang === 'ar' ? 'أدخل عنوان المقال بالعربية (مطلوب)' : 'Enter article title in ' . strtoupper($lang) . ' (optional)' }}"
                                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                                    <div class="char-counter" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        <span x-text="charCounts.title.{{ $lang }}"></span><span class="text-gray-600">/500</span>
                                        <span x-show="charCounts.title.{{ $lang }} > 0" :class="charCounts.title.{{ $lang }} <= 500 ? 'text-green-500' : 'text-red-500'" class="ml-2">
                                            <span x-show="charCounts.title.{{ $lang }} <= 500">✓</span>
                                            <span x-show="charCounts.title.{{ $lang }} > 500">!</span>
                                        </span>
                                    </div>
                                </div>
                                @error("title.$lang")
                                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <!-- Excerpt Fields (Multi-language) -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <i data-lucide="align-left" class="w-4 h-4 inline-block ml-1"></i>
                            Excerpt / Summary
                            <span class="text-gray-500 text-xs font-normal">(Optional - auto-generated from content if empty)</span>
                        </label>
                        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                            <div x-show="activeLanguage === '{{ $lang }}'" x-cloak>
                                <div class="content-field-wrapper">
                                    <textarea name="excerpt[{{ $lang }}]"
                                              x-model="form.excerpt.{{ $lang }}"
                                              @input="updateCharCount('excerpt', '{{ $lang }}')"
                                              rows="3"
                                              maxlength="1000"
                                              class="w-full px-4 py-3 pb-8 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-all"
                                              :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                                              placeholder="{{ $lang === 'ar' ? 'ملخص قصير للمقال (اختياري)' : 'Brief summary of the article (optional)' }}"
                                              dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"></textarea>
                                    <div class="char-counter" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                                        <span x-text="charCounts.excerpt.{{ $lang }}"></span><span class="text-gray-600">/1000</span>
                                    </div>
                                </div>
                                @error("excerpt.$lang")
                                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <!-- Content Fields (Multi-language) with TinyMCE -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-3" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <i data-lucide="file-text" class="w-4 h-4 inline-block ml-1"></i>
                            Full Article Content
                            <span class="text-red-400">*</span>
                        </label>
                        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                            <div x-show="activeLanguage === '{{ $lang }}'" x-cloak>
                                <x-admin.editor
                                    name="content[{{ $lang }}]"
                                    :value="old('content.' . $lang, '')"
                                    language="{{ $lang }}"
                                    :height="500"
                                    :required="$lang === 'ar'"
                                    placeholder="{{ $lang === 'ar' ? 'اكتب محتوى المقال هنا... يدعم التنسيق الغني والوسائط' : 'Write your article content here... Supports rich formatting and media' }}"
                                />
                                @error("content.$lang")
                                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sidebar Column (Right - 1/3 width) -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Publishing Options -->
                <div class="rounded-2xl p-6 shadow-xl transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                        Publishing Options
                    </h3>

                    <!-- Auto-generated Slug -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            URL Slug <span class="text-red-500">*</span>
                            <span class="text-gray-500 text-xs font-normal">(Auto-generated)</span>
                        </label>
                        <div class="px-4 py-2 rounded-lg border transition-colors text-sm break-all"
                             :class="darkMode ? 'bg-gray-800 border-gray-700 text-gray-400' : 'bg-gray-100 border-gray-300 text-gray-600'">
                            <span x-text="generatedSlug || 'untitled-article'"></span>
                        </div>
                        <input type="hidden" name="slug" :value="generatedSlug || 'article-' + Date.now()">
                    </div>

                    <!-- Status Dropdown -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select name="status"
                                x-model="form.status"
                                required
                                class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="draft">Draft</option>
                            <option value="under_review">Under Review</option>
                            <option value="published">Published</option>
                            <option value="archived">Archived</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Published Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Publication Date
                        </label>
                        <input type="datetime-local"
                               name="published_at"
                               x-model="form.published_at"
                               class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
                            Leave empty to use current date/time
                        </p>
                        @error('published_at')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Featured Toggle -->
                    <div class="mb-4 p-3 rounded-lg transition-colors" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="featured"
                                   x-model="form.featured"
                                   value="1"
                                   class="w-5 h-5 rounded border-gray-300 text-[#0056b3] focus:ring-[#0056b3] focus:ring-offset-0 transition-colors">
                            <span class="mr-3">
                                <span class="text-sm font-semibold block" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                    Featured Article
                                </span>
                                <span class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    Display on homepage
                                </span>
                            </span>
                        </label>
                    </div>

                    <!-- Pinned Toggle -->
                    <div class="mb-0 p-3 rounded-lg transition-colors" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="is_pinned"
                                   x-model="form.is_pinned"
                                   value="1"
                                   class="w-5 h-5 rounded border-gray-300 text-[#0056b3] focus:ring-[#0056b3] focus:ring-offset-0 transition-colors">
                            <span class="mr-3">
                                <span class="text-sm font-semibold block" :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                    Pin to Top
                                </span>
                                <span class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    Keep at top of lists
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="rounded-2xl p-6 shadow-xl transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="image" class="w-5 h-5"></i>
                        Featured Image
                    </h3>

                    <div class="mb-4">
                        <input type="file"
                               name="featured_image"
                               accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                               @change="handleImageUpload($event)"
                               class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors text-sm"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                        <div class="mt-2 text-xs space-y-1" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            <p><strong>Formats:</strong> JPEG, PNG, WebP, GIF</p>
                            <p><strong>Max size:</strong> 2MB</p>
                            <p><strong>Recommended:</strong> 1200x630px (16:9 ratio)</p>
                        </div>
                        @error('featured_image')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Image Preview -->
                    <div x-show="imagePreview" x-cloak class="mt-4">
                        <div class="relative rounded-lg overflow-hidden border-2 group transition-colors"
                             :class="darkMode ? 'border-gray-700' : 'border-gray-300'">
                            <img :src="imagePreview" alt="Preview" class="w-full h-56 object-cover">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <button type="button"
                                        @click="clearImage()"
                                        class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors flex items-center gap-2">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    Remove Image
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category & Organization -->
                <div class="rounded-2xl p-6 shadow-xl transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="folder" class="w-5 h-5"></i>
                        Category & Organization
                    </h3>

                    <!-- Category Dropdown -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Category
                        </label>
                        <select name="category_id"
                                x-model="form.category_id"
                                class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">-- Select Category --</option>
                            <template x-for="category in categories" :key="category.id">
                                <option :value="category.id" x-text="category.name.ar || category.name.en || category.name"></option>
                            </template>
                        </select>
                        <p x-show="loadingCategories" class="text-xs mt-1 text-gray-500">Loading categories...</p>
                        @error('category_id')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Series Dropdown -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Series
                            <span class="text-gray-500 text-xs font-normal">(For linked articles)</span>
                        </label>
                        <select name="series_id"
                                x-model="form.series_id"
                                class="w-full px-4 py-3 rounded-lg border focus:outline-none focus:ring-2 focus:ring-[#0056b3] transition-colors"
                                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">-- No Series --</option>
                            <template x-for="series in seriesList" :key="series.id">
                                <option :value="series.id" x-text="series.title.ar || series.title.en || series.title"></option>
                            </template>
                        </select>
                        <p x-show="loadingSeries" class="text-xs mt-1 text-gray-500">Loading series...</p>
                        @error('series_id')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Tags Multi-select -->
                    <div class="mb-0">
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            Tags
                            <span class="text-gray-500 text-xs font-normal">(Select multiple)</span>
                        </label>
                        <div class="border rounded-lg p-3 max-h-64 overflow-y-auto custom-scrollbar transition-colors"
                             :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-300'">
                            <template x-for="tag in tags" :key="tag.id">
                                <label class="flex items-center mb-2 cursor-pointer p-2 rounded transition-colors hover:bg-opacity-50"
                                       :class="darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-100'">
                                    <input type="checkbox"
                                           name="tags[]"
                                           :value="tag.id"
                                           x-model="form.tags"
                                           class="w-4 h-4 rounded border-gray-300 text-[#0056b3] focus:ring-[#0056b3] focus:ring-offset-0">
                                    <span class="mr-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-700'" x-text="tag.name.ar || tag.name.en || tag.name"></span>
                                </label>
                            </template>
                            <p x-show="loadingTags" class="text-sm text-gray-500 text-center py-2">Loading tags...</p>
                            <p x-show="!loadingTags && tags.length === 0" class="text-sm text-gray-500 text-center py-2">No tags available</p>
                        </div>
                        @error('tags')
                            <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="rounded-2xl p-6 shadow-xl transition-colors" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
                    <button type="submit"
                            :disabled="isSubmitting"
                            class="w-full bg-gradient-to-r from-[#0056b3] to-[#218838] text-white px-6 py-4 rounded-lg font-bold text-lg hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed mb-3 flex items-center justify-center gap-2">
                        <span x-show="!isSubmitting" class="flex items-center gap-2">
                            <i data-lucide="check-circle" class="w-6 h-6"></i>
                            Create Article
                        </span>
                        <span x-show="isSubmitting" class="flex items-center gap-2">
                            <i data-lucide="loader-2" class="w-6 h-6 animate-spin"></i>
                            Creating Article...
                        </span>
                    </button>
                    <a href="{{ route('admin.articles.index') }}"
                       class="w-full block text-center px-6 py-3 rounded-lg font-semibold border transition hover:shadow-md"
                       :class="darkMode ? 'border-gray-700 text-white hover:bg-gray-800' : 'border-gray-300 text-gray-900 hover:bg-gray-100'">
                        <i data-lucide="x" class="w-5 h-5 inline-block ml-1"></i>
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function articleForm() {
    return {
        darkMode: localStorage.getItem('darkMode') === 'true',
        activeLanguage: 'ar',
        isSubmitting: false,
        imagePreview: null,
        generatedSlug: '',

        form: {
            title: { ar: '', en: '', fr: '', tr: '', ur: '' },
            excerpt: { ar: '', en: '', fr: '', tr: '', ur: '' },
            content: { ar: '', en: '', fr: '', tr: '', ur: '' },
            status: 'draft',
            published_at: '',
            featured: false,
            is_pinned: false,
            category_id: '',
            series_id: '',
            tags: []
        },

        charCounts: {
            title: { ar: 0, en: 0, fr: 0, tr: 0, ur: 0 },
            excerpt: { ar: 0, en: 0, fr: 0, tr: 0, ur: 0 },
            content: { ar: 0, en: 0, fr: 0, tr: 0, ur: 0 }
        },

        categories: [],
        seriesList: [],
        tags: [],
        loadingCategories: false,
        loadingSeries: false,
        loadingTags: false,

        init() {
            this.loadCategories();
            this.loadSeries();
            this.loadTags();

            // Watch for title changes to generate slug
            this.$watch('form.title.ar', value => {
                this.generatedSlug = this.generateSlug(value);
            });

            // Reinitialize Lucide icons
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        async loadCategories() {
            this.loadingCategories = true;
            try {
                const response = await fetch('/su/portal/v1/admin/categories', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch categories');
                }

                const data = await response.json();
                if (data.success) {
                    this.categories = data.data;
                }
            } catch (error) {
                console.error('Failed to load categories:', error);
                // Show user-friendly error (could integrate with toast notification)
            } finally {
                this.loadingCategories = false;
            }
        },

        async loadSeries() {
            this.loadingSeries = true;
            try {
                const response = await fetch('/su/portal/v1/admin/series', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch series');
                }

                const data = await response.json();
                if (data.success) {
                    this.seriesList = data.data;
                }
            } catch (error) {
                console.error('Failed to load series:', error);
            } finally {
                this.loadingSeries = false;
            }
        },

        async loadTags() {
            this.loadingTags = true;
            try {
                const response = await fetch('/su/portal/v1/admin/tags', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch tags');
                }

                const data = await response.json();
                if (data.success) {
                    this.tags = data.data;
                }
            } catch (error) {
                console.error('Failed to load tags:', error);
            } finally {
                this.loadingTags = false;
            }
        },

        updateCharCount(field, lang) {
            const value = this.form[field][lang];
            this.charCounts[field][lang] = value ? value.length : 0;
        },

        generateSlug(text) {
            if (!text || text.trim() === '') {
                return '';
            }

            // Transliterate Arabic to English (simple version - in production use a proper library)
            const arabicToEnglish = {
                'ا': 'a', 'أ': 'a', 'إ': 'i', 'آ': 'aa', 'ب': 'b', 'ت': 't', 'ث': 'th',
                'ج': 'j', 'ح': 'h', 'خ': 'kh', 'د': 'd', 'ذ': 'th', 'ر': 'r', 'ز': 'z',
                'س': 's', 'ش': 'sh', 'ص': 's', 'ض': 'd', 'ط': 't', 'ظ': 'z', 'ع': 'a',
                'غ': 'gh', 'ف': 'f', 'ق': 'q', 'ك': 'k', 'ل': 'l', 'م': 'm', 'ن': 'n',
                'ه': 'h', 'و': 'w', 'ي': 'y', 'ى': 'a', 'ة': 'h', 'ء': 'a'
            };

            let slug = text.toLowerCase().trim();

            // Transliterate Arabic characters
            slug = slug.split('').map(char => arabicToEnglish[char] || char).join('');

            // Remove special characters and replace spaces with hyphens
            slug = slug
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-+|-+$/g, '');

            // Limit to 100 characters
            return slug.substring(0, 100);
        },

        handleImageUpload(event) {
            const file = event.target.files[0];
            if (!file) {
                return;
            }

            // Validate file size (2MB)
            const maxSize = 2 * 1024 * 1024; // 2MB in bytes
            if (file.size > maxSize) {
                alert('File size must be less than 2MB. Current size: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
                event.target.value = '';
                this.imagePreview = null;
                return;
            }

            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert('Invalid file type. Please upload JPEG, PNG, WebP, or GIF format.');
                event.target.value = '';
                this.imagePreview = null;
                return;
            }

            // Create preview
            this.imagePreview = URL.createObjectURL(file);

            // Reinitialize Lucide icons for the remove button
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        clearImage() {
            this.imagePreview = null;
            const fileInput = document.querySelector('input[name="featured_image"]');
            if (fileInput) {
                fileInput.value = '';
            }
        },

        handleSubmit(event) {
            this.isSubmitting = true;

            // Basic client-side validation
            if (!this.form.title.ar || this.form.title.ar.trim() === '') {
                alert('Arabic title is required');
                this.isSubmitting = false;
                this.activeLanguage = 'ar';
                event.preventDefault();
                return false;
            }

            // Validate Quill editors content
            // Note: Quill editors automatically update hidden inputs on text change
            // The form submission will include all content from hidden inputs

            // Get Arabic content from hidden input
            const arabicContentInput = document.querySelector('input[name="content[ar]"]');
            if (arabicContentInput) {
                const arabicContent = arabicContentInput.value.trim();

                // Simple text extraction from HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = arabicContent;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';

                if (!textContent || textContent.trim().length < 50) {
                    alert('Arabic content is required (minimum 50 characters)');
                    this.isSubmitting = false;
                    this.activeLanguage = 'ar';
                    event.preventDefault();
                    return false;
                }
            }

            // Allow form submission
            return true;
        }
    };
}

// Initialize Lucide icons on page load
document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
});
</script>
@endpush
@endsection
