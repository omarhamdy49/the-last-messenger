{{--
    Article Form Partial with TinyMCE Integration
    Multi-language support with comprehensive field set
--}}

@props([
    'article' => null,
    'categories' => [],
    'tags' => [],
    'authors' => [],
])

@php
    $languages = array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]));
    $isEdit = $article && $article->exists;
@endphp

<form action="{{ $isEdit ? route('admin.articles.update', $article) : route('admin.articles.store') }}"
      method="POST"
      x-data="articleForm({{ json_encode($article ?? []) }})"
      @submit="handleSubmit"
      class="space-y-6">

    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content Column (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Main Content Section -->
            <x-admin.form-section
                title="Article Content"
                description="Enter article information in multiple languages"
                icon="file-text">

                <x-admin.language-tabs
                    :languages="$languages"
                    :errors="$errors"
                    defaultLanguage="ar"
                    :showCompletionStatus="true"
                    :showCopyButton="true">

                    @foreach($languages as $lang)
                        <x-admin.language-tab-panel :language="$lang">
                            <div class="space-y-6">
                                <!-- Title -->
                                <x-admin.form.text-input
                                    name="title_{{ $lang }}"
                                    label="Title"
                                    :value="old('title_'.$lang, $article->translate($lang)->title ?? '')"
                                    required
                                    maxlength="200"
                                    icon="heading"
                                    helpText="Maximum 200 characters"
                                    placeholder="Enter article title..." />

                                <!-- Slug -->
                                <x-admin.form.text-input
                                    name="slug_{{ $lang }}"
                                    label="Slug (URL)"
                                    :value="old('slug_'.$lang, $article->translate($lang)->slug ?? '')"
                                    icon="link"
                                    helpText="Leave empty to auto-generate from title"
                                    placeholder="article-url-slug" />

                                <!-- Excerpt -->
                                <x-admin.form.textarea
                                    name="excerpt_{{ $lang }}"
                                    label="Excerpt"
                                    :value="old('excerpt_'.$lang, $article->translate($lang)->excerpt ?? '')"
                                    rows="3"
                                    maxlength="500"
                                    icon="file-text"
                                    helpText="Brief summary (max 500 characters)"
                                    placeholder="Brief article summary..." />

                                <!-- Content with TinyMCE -->
                                <div class="space-y-2">
                                    <label class="block font-semibold"
                                           :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                                        Content <span class="text-red-500">*</span>
                                    </label>

                                    <textarea
                                        name="content_{{ $lang }}"
                                        class="tinymce-editor"
                                        data-language="{{ $lang }}"
                                        required>{{ old('content_'.$lang, $article->translate($lang)->content ?? '') }}</textarea>

                                    @error('content_'.$lang)
                                        <div class="mt-2 flex items-start gap-2 text-red-500 text-sm error-message animate-fadeInUp">
                                            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                                            <span>{{ $message }}</span>
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </x-admin.language-tab-panel>
                    @endforeach
                </x-admin.language-tabs>
            </x-admin.form-section>

            <!-- SEO Section (Collapsible) -->
            <x-admin.form-section
                title="SEO Metadata"
                description="Optimize for search engines"
                icon="search"
                :collapsible="true"
                :collapsed="true">

                <x-admin.language-tabs
                    :languages="$languages"
                    :errors="$errors"
                    defaultLanguage="ar"
                    :showCompletionStatus="false"
                    :showCopyButton="true">

                    @foreach($languages as $lang)
                        <x-admin.language-tab-panel :language="$lang">
                            <div class="space-y-6">
                                <!-- Meta Title -->
                                <x-admin.form.text-input
                                    name="meta_title_{{ $lang }}"
                                    label="Meta Title"
                                    :value="old('meta_title_'.$lang, $article->translate($lang)->meta_title ?? '')"
                                    maxlength="60"
                                    icon="tag"
                                    helpText="Recommended: 50-60 characters"
                                    placeholder="SEO optimized title..." />

                                <!-- Meta Description -->
                                <x-admin.form.textarea
                                    name="meta_description_{{ $lang }}"
                                    label="Meta Description"
                                    :value="old('meta_description_'.$lang, $article->translate($lang)->meta_description ?? '')"
                                    rows="3"
                                    maxlength="160"
                                    icon="file-text"
                                    helpText="Recommended: 150-160 characters"
                                    placeholder="SEO description for search results..." />

                                <!-- Meta Keywords -->
                                <x-admin.form.text-input
                                    name="meta_keywords_{{ $lang }}"
                                    label="Meta Keywords"
                                    :value="old('meta_keywords_'.$lang, $article->translate($lang)->meta_keywords ?? '')"
                                    icon="hash"
                                    helpText="Comma-separated keywords"
                                    placeholder="keyword1, keyword2, keyword3" />
                            </div>
                        </x-admin.language-tab-panel>
                    @endforeach
                </x-admin.language-tabs>

                <!-- OG Image -->
                <div class="mt-6 pt-6 border-t"
                     :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <label class="block mb-3 font-semibold"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        Open Graph Image
                    </label>

                    <input type="hidden"
                           name="og_image_id"
                           x-model="ogImageId">

                    <button type="button"
                            @click="chooseOgImage()"
                            class="px-4 py-3 rounded-lg border-2 border-dashed font-medium transition-all hover:scale-[1.02] inline-flex items-center gap-2"
                            :class="darkMode ?
                                'border-gray-700 hover:border-gray-600 text-gray-300 hover:bg-gray-800' :
                                'border-gray-300 hover:border-gray-400 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="image-plus" class="w-5 h-5"></i>
                        <span x-text="ogImage ? 'Change OG Image' : 'Choose OG Image'"></span>
                    </button>

                    <!-- OG Image Preview -->
                    <div x-show="ogImage" x-transition class="mt-4">
                        <div class="relative inline-block group">
                            <img :src="ogImage?.url"
                                 :alt="ogImage?.alt_text"
                                 class="w-48 h-32 object-cover rounded-lg border-2 border-accent">

                            <button type="button"
                                    @click="removeOgImage()"
                                    class="absolute top-2 right-2 p-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </x-admin.form-section>

            <!-- Advanced Section (Collapsible) -->
            <x-admin.form-section
                title="Advanced Settings"
                description="Custom code and additional metadata"
                icon="settings"
                :collapsible="true"
                :collapsed="true">

                <!-- Hijri Date -->
                <x-admin.form.text-input
                    name="hijri_date"
                    label="Hijri Date (Optional)"
                    :value="old('hijri_date', $article->hijri_date ?? '')"
                    icon="calendar"
                    helpText="Islamic calendar date (e.g., 15 Ramadan 1445)"
                    placeholder="e.g., 15 Ramadan 1445" />

                <!-- References -->
                <x-admin.form.textarea
                    name="references"
                    label="References"
                    :value="old('references', $article->references ?? '')"
                    rows="4"
                    icon="book-open"
                    helpText="Sources and references (one per line or JSON format)"
                    placeholder="List references and sources..." />

                <!-- Custom CSS -->
                <x-admin.form.textarea
                    name="custom_css"
                    label="Custom CSS"
                    :value="old('custom_css', $article->custom_css ?? '')"
                    rows="6"
                    icon="code"
                    helpText="Additional CSS for this article only"
                    placeholder=".custom-class { color: red; }" />

                <!-- Custom JavaScript -->
                <x-admin.form.textarea
                    name="custom_js"
                    label="Custom JavaScript"
                    :value="old('custom_js', $article->custom_js ?? '')"
                    rows="6"
                    icon="code-2"
                    helpText="Additional JavaScript for this article only (without <script> tags)"
                    placeholder="console.log('Custom JS');" />
            </x-admin.form-section>
        </div>

        <!-- Sidebar Column (1/3 width) -->
        <div class="space-y-6">

            <!-- Publishing Section -->
            <x-admin.form-section
                title="Publishing"
                description="Control article visibility"
                icon="send">

                <!-- Status -->
                <div class="space-y-3">
                    <label class="block font-semibold mb-3"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        Status <span class="text-red-500">*</span>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        @foreach([
                            'draft' => ['icon' => 'file-edit', 'label' => 'Draft', 'color' => 'gray'],
                            'published' => ['icon' => 'check-circle', 'label' => 'Published', 'color' => 'green'],
                            'scheduled' => ['icon' => 'clock', 'label' => 'Scheduled', 'color' => 'blue'],
                            'archived' => ['icon' => 'archive', 'label' => 'Archived', 'color' => 'orange'],
                        ] as $status => $config)
                            <label class="relative cursor-pointer">
                                <input type="radio"
                                       name="status"
                                       value="{{ $status }}"
                                       x-model="articleStatus"
                                       {{ old('status', $article->status ?? 'draft') === $status ? 'checked' : '' }}
                                       required
                                       class="peer sr-only">

                                <div class="p-4 rounded-lg border-2 transition-all duration-300 text-center"
                                     :class="articleStatus === '{{ $status }}' ?
                                         'border-accent bg-accent/10 shadow-lg shadow-accent/20' :
                                         (darkMode ? 'border-gray-700 hover:border-gray-600 bg-gray-900' : 'border-gray-300 hover:border-gray-400 bg-white')">
                                    <i data-lucide="{{ $config['icon'] }}" class="w-6 h-6 mx-auto mb-2"
                                       :class="articleStatus === '{{ $status }}' ? 'text-accent' : (darkMode ? 'text-gray-400' : 'text-gray-500')"></i>
                                    <span class="text-sm font-semibold"
                                          :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                        {{ $config['label'] }}
                                    </span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Publish Date (show only if scheduled) -->
                <div x-show="articleStatus === 'scheduled'" x-transition x-cloak>
                    <x-admin.form.date-picker
                        name="published_at"
                        label="Publish Date & Time"
                        :value="old('published_at', $article->published_at ?? '')"
                        :time="true"
                        required
                        icon="calendar-clock"
                        helpText="When to auto-publish this article" />
                </div>

                <!-- Featured & Pinned -->
                <div class="pt-4 space-y-3">
                    <x-admin.form.checkbox
                        name="is_featured"
                        label="Featured Article"
                        :checked="old('is_featured', $article->is_featured ?? false)"
                        description="Show in featured section on homepage" />

                    <x-admin.form.checkbox
                        name="is_pinned"
                        label="Pin to Top"
                        :checked="old('is_pinned', $article->is_pinned ?? false)"
                        description="Keep at the top of article listings" />

                    <x-admin.form.checkbox
                        name="allow_comments"
                        label="Allow Comments"
                        :checked="old('allow_comments', $article->allow_comments ?? true)"
                        description="Enable comments for this article" />
                </div>
            </x-admin.form-section>

            <!-- Media Section -->
            <x-admin.form-section
                title="Featured Media"
                description="Images and gallery"
                icon="image">

                <!-- Featured Image -->
                <div class="mb-6">
                    <label class="block mb-3 font-semibold"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        Featured Image <span class="text-red-500">*</span>
                    </label>

                    <input type="hidden"
                           name="featured_image_id"
                           x-model="featuredImageId">

                    <button type="button"
                            @click="chooseFeaturedImage()"
                            class="px-4 py-3 rounded-lg border-2 border-dashed font-medium transition-all hover:scale-[1.02] inline-flex items-center gap-2 w-full justify-center"
                            :class="darkMode ?
                                'border-gray-700 hover:border-gray-600 text-gray-300 hover:bg-gray-800' :
                                'border-gray-300 hover:border-gray-400 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="image-plus" class="w-5 h-5"></i>
                        <span x-text="featuredImage ? 'Change Image' : 'Choose Image'"></span>
                    </button>

                    @error('featured_image_id')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror

                    <!-- Image Preview -->
                    <div x-show="featuredImage" x-transition class="mt-4">
                        <div class="relative group">
                            <img :src="featuredImage?.url"
                                 :alt="featuredImage?.alt_text"
                                 class="w-full aspect-video object-cover rounded-lg border-2 border-accent shadow-lg">

                            <div class="absolute inset-0 bg-black/50 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button type="button"
                                        @click="viewImage(featuredImage.url)"
                                        class="p-2 rounded-lg bg-white/90 hover:bg-white transition-colors">
                                    <i data-lucide="eye" class="w-5 h-5 text-gray-900"></i>
                                </button>
                                <button type="button"
                                        @click="removeFeaturedImage()"
                                        class="p-2 rounded-lg bg-red-500 hover:bg-red-600 transition-colors">
                                    <i data-lucide="trash-2" class="w-5 h-5 text-white"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gallery -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block font-semibold"
                               :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                            Gallery
                        </label>
                        <span x-show="galleryImages.length > 0"
                              class="text-xs px-2 py-1 rounded-full bg-accent/20 text-accent font-semibold"
                              x-text="galleryImages.length + ' images'"></span>
                    </div>

                    <template x-for="(image, index) in galleryImages" :key="image.id">
                        <input type="hidden" name="gallery_images[]" :value="image.id">
                    </template>

                    <button type="button"
                            @click="chooseGalleryImages()"
                            class="px-4 py-3 rounded-lg border-2 border-dashed font-medium transition-all hover:scale-[1.02] inline-flex items-center gap-2 w-full justify-center"
                            :class="darkMode ?
                                'border-gray-700 hover:border-gray-600 text-gray-300 hover:bg-gray-800' :
                                'border-gray-300 hover:border-gray-400 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="images" class="w-5 h-5"></i>
                        <span x-text="galleryImages.length > 0 ? 'Manage Gallery' : 'Add Gallery'"></span>
                    </button>

                    <!-- Gallery Preview -->
                    <div x-show="galleryImages.length > 0" x-transition class="mt-4">
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="(image, index) in galleryImages" :key="image.id">
                                <div class="relative aspect-square group">
                                    <img :src="image.url"
                                         :alt="image.alt_text"
                                         class="w-full h-full object-cover rounded-lg">

                                    <div class="absolute top-1 left-1 w-5 h-5 rounded-full bg-gradient-to-r from-[#0056b3] to-[#218838] flex items-center justify-center">
                                        <span class="text-white text-xs font-bold" x-text="index + 1"></span>
                                    </div>

                                    <button type="button"
                                            @click="removeFromGallery(index)"
                                            class="absolute top-1 right-1 p-1 rounded-lg bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </x-admin.form-section>

            <!-- Categories Section -->
            <x-admin.form-section
                title="Categories"
                description="Organize your content"
                icon="folder">

                <!-- Primary Category -->
                <x-admin.form.select
                    name="category_id"
                    label="Primary Category"
                    :value="old('category_id', $article->category_id ?? '')"
                    :options="$categories"
                    placeholder="Select primary category"
                    required
                    searchable
                    icon="folder-open" />

                <!-- Additional Categories -->
                <div class="mt-6">
                    <label class="block mb-3 font-semibold"
                           :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        Additional Categories
                    </label>

                    <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar p-2 rounded-lg"
                         :class="darkMode ? 'bg-gray-900/50' : 'bg-gray-50'">
                        @foreach($categories as $catId => $catName)
                            <label class="flex items-center gap-2 p-2 rounded-lg cursor-pointer transition-colors"
                                   :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-100'">
                                <input type="checkbox"
                                       name="additional_categories[]"
                                       value="{{ $catId }}"
                                       {{ in_array($catId, old('additional_categories', $article->additional_categories ?? [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-accent focus:ring-accent">
                                <span class="text-sm"
                                      :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    {{ $catName }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </x-admin.form-section>

            <!-- Tags Section -->
            <x-admin.form-section
                title="Tags"
                description="Add relevant tags"
                icon="tag">

                <x-admin.form.select
                    name="tags"
                    label="Select Tags"
                    :value="old('tags', $article->tags->pluck('id')->toArray() ?? [])"
                    :options="$tags"
                    placeholder="Select tags"
                    multiple
                    searchable
                    icon="tags" />

                <button type="button"
                        @click="showNewTagModal = true"
                        class="mt-3 px-4 py-2 rounded-lg font-medium transition-all hover:scale-105 inline-flex items-center gap-2 text-sm bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Add New Tag</span>
                </button>
            </x-admin.form-section>

            <!-- Author Section -->
            <x-admin.form-section
                title="Author"
                description="Article writer"
                icon="user">

                <x-admin.form.select
                    name="author_id"
                    label="Writer/Author"
                    :value="old('author_id', $article->author_id ?? auth()->id())"
                    :options="$authors"
                    required
                    searchable
                    icon="user-pen" />

                <!-- Read Time -->
                <div class="mt-6">
                    <x-admin.form.text-input
                        name="read_time"
                        label="Read Time (minutes)"
                        :value="old('read_time', $article->read_time ?? '')"
                        type="number"
                        min="1"
                        icon="clock"
                        helpText="Auto-calculated from content if left empty"
                        placeholder="e.g., 5" />
                </div>
            </x-admin.form-section>
        </div>
    </div>

    <!-- Action Buttons (Sticky at bottom) -->
    <div class="sticky bottom-0 z-30 glass border-t py-4 -mx-6 px-6 mt-8"
         :class="darkMode ? 'glass-dark border-gray-800' : 'glass-light border-gray-200'">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.articles.index') }}"
                   class="px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105 inline-flex items-center gap-2"
                   :class="darkMode ?
                       'bg-gray-800 hover:bg-gray-700 text-white' :
                       'bg-gray-100 hover:bg-gray-200 text-gray-900'">
                    <i data-lucide="x" class="w-5 h-5"></i>
                    <span>Cancel</span>
                </a>

                <button type="button"
                        @click="saveDraft"
                        class="px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105 inline-flex items-center gap-2"
                        :class="darkMode ?
                            'bg-gray-700 hover:bg-gray-600 text-white' :
                            'bg-gray-200 hover:bg-gray-300 text-gray-900'">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    <span>Save Draft</span>
                </button>
            </div>

            <div class="flex items-center gap-3">
                @if($isEdit)
                    <a href="{{ route('admin.articles.show', $article) }}"
                       target="_blank"
                       class="px-6 py-3 rounded-lg font-semibold transition-all hover:scale-105 inline-flex items-center gap-2 border"
                       :class="darkMode ?
                           'border-gray-700 text-gray-300 hover:bg-gray-800' :
                           'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        <i data-lucide="external-link" class="w-5 h-5"></i>
                        <span>Preview</span>
                    </a>
                @endif

                <button type="submit"
                        class="px-8 py-3 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg shadow-accent/30 inline-flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5"></i>
                    <span>{{ $isEdit ? 'Update Article' : 'Publish Article' }}</span>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Include Media Picker Modal -->
<x-admin.media-picker-modal />

@push('scripts')
<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
function articleForm(article) {
    return {
        articleStatus: '{{ old('status', $article->status ?? 'draft') }}',
        featuredImage: @json($article->featuredImage ?? null),
        featuredImageId: {{ old('featured_image_id', $article->featured_image_id ?? 'null') }},
        galleryImages: @json($article->galleryImages ?? []),
        ogImage: @json($article->ogImage ?? null),
        ogImageId: {{ old('og_image_id', $article->og_image_id ?? 'null') }},
        showNewTagModal: false,
        autoSaveInterval: null,

        init() {
            this.initTinyMCE();
            this.setupAutoSave();

            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        initTinyMCE() {
            tinymce.init({
                selector: '.tinymce-editor',
                height: 500,
                menubar: false,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic forecolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | link image media | code | help',
                content_style: 'body { font-family: Cairo, sans-serif; font-size: 16px; }',
                directionality: 'auto',
                language_url: '/vendor/tinymce/langs/ar.js',
                language: 'ar',

                // Media picker integration
                file_picker_callback: (callback, value, meta) => {
                    MediaPicker.open({
                        mode: 'single',
                        type: meta.filetype === 'image' ? 'image' : 'all',
                        onSelect: (media) => {
                            callback(media.url, {
                                alt: media.alt_text,
                                title: media.original_filename
                            });
                        }
                    });
                },

                // Image upload handler
                images_upload_handler: async (blobInfo, progress) => {
                    return new Promise((resolve, reject) => {
                        const formData = new FormData();
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        formData.append('collection_name', 'articles');

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', @json(route('admin.media-library.store')));
                        xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);

                        xhr.upload.addEventListener('progress', (e) => {
                            progress(e.loaded / e.total * 100);
                        });

                        xhr.addEventListener('load', () => {
                            if (xhr.status === 201) {
                                const response = JSON.parse(xhr.responseText);
                                resolve(response.data.url);
                            } else {
                                reject('Upload failed: ' + xhr.status);
                            }
                        });

                        xhr.addEventListener('error', () => {
                            reject('Upload failed');
                        });

                        xhr.send(formData);
                    });
                },

                setup: (editor) => {
                    // Update directionality based on language tab
                    editor.on('init', () => {
                        const lang = editor.getElement().dataset.language;
                        const dir = ['ar', 'ur'].includes(lang) ? 'rtl' : 'ltr';
                        editor.getBody().setAttribute('dir', dir);
                    });
                }
            });
        },

        setupAutoSave() {
            // Auto-save to localStorage every 30 seconds
            this.autoSaveInterval = setInterval(() => {
                this.autoSaveDraft();
            }, 30000);
        },

        autoSaveDraft() {
            const formData = new FormData(this.$el);
            const draftData = {};

            for (let [key, value] of formData.entries()) {
                draftData[key] = value;
            }

            // Get TinyMCE content
            tinymce.editors.forEach(editor => {
                draftData[editor.id] = editor.getContent();
            });

            localStorage.setItem('article_draft', JSON.stringify({
                data: draftData,
                timestamp: Date.now()
            }));

            console.log('Draft auto-saved');
        },

        saveDraft() {
            this.articleStatus = 'draft';
            this.$nextTick(() => {
                this.$el.submit();
            });
        },

        handleSubmit(e) {
            // Update TinyMCE content before submit
            tinymce.triggerSave();

            // Clear auto-saved draft on successful submit
            localStorage.removeItem('article_draft');
        },

        chooseFeaturedImage() {
            MediaPicker.open({
                mode: 'single',
                type: 'image',
                onSelect: (media) => {
                    this.featuredImage = media;
                    this.featuredImageId = media.id;
                    this.$nextTick(() => lucide.createIcons());
                }
            });
        },

        removeFeaturedImage() {
            if (confirm('Remove featured image?')) {
                this.featuredImage = null;
                this.featuredImageId = null;
            }
        },

        chooseGalleryImages() {
            MediaPicker.open({
                mode: 'multiple',
                type: 'image',
                onSelect: (images) => {
                    this.galleryImages = images;
                    this.$nextTick(() => lucide.createIcons());
                }
            });
        },

        removeFromGallery(index) {
            if (confirm('Remove this image?')) {
                this.galleryImages.splice(index, 1);
            }
        },

        chooseOgImage() {
            MediaPicker.open({
                mode: 'single',
                type: 'image',
                onSelect: (media) => {
                    this.ogImage = media;
                    this.ogImageId = media.id;
                    this.$nextTick(() => lucide.createIcons());
                }
            });
        },

        removeOgImage() {
            if (confirm('Remove OG image?')) {
                this.ogImage = null;
                this.ogImageId = null;
            }
        },

        viewImage(url) {
            window.open(url, '_blank');
        }
    };
}
</script>
@endpush
