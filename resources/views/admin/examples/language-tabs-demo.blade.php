@extends('layouts.admin')

@section('title', 'Language Tabs Demo')

@section('content')
<div class="container mx-auto px-4 py-8">

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2"
            :class="darkMode ? 'text-white' : 'text-gray-900'">
            Language Tabs Component Demo
        </h1>
        <p class="text-lg"
           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Examples of multi-language content editing with validation and completion tracking
        </p>
    </div>

    <!-- Demo Form -->
    <div class="max-w-4xl">
        <form action="{{ route('admin.articles.store') }}" method="POST" class="space-y-8">
            @csrf

            <!-- Example 1: Basic Language Tabs -->
            <div class="glass rounded-2xl p-6"
                 :class="darkMode ? 'glass-dark' : 'glass-light'">

                <h2 class="text-xl font-bold mb-4"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Example 1: Basic Article Form
                </h2>

                <x-admin.language-tabs :errors="$errors">
                    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                        <x-admin.language-tab-panel :language="$lang">

                            {{-- Title Field --}}
                            <x-admin.form.text-input
                                :name="'title_' . $lang"
                                label="Article Title"
                                placeholder="Enter article title..."
                                required
                                icon="file-text"
                                maxlength="200"
                                helpText="A clear, concise title for the article"
                            />

                            {{-- Slug Field --}}
                            <x-admin.form.text-input
                                :name="'slug_' . $lang"
                                label="URL Slug"
                                placeholder="article-slug"
                                icon="link"
                                helpText="Auto-generated from title, but can be customized"
                            />

                            {{-- Excerpt Field --}}
                            <x-admin.form.textarea
                                :name="'excerpt_' . $lang"
                                label="Excerpt"
                                placeholder="Brief summary of the article..."
                                rows="3"
                                maxlength="300"
                                helpText="Short description shown in listings"
                            />

                            {{-- Content Field --}}
                            <x-admin.form.textarea
                                :name="'content_' . $lang"
                                label="Content"
                                placeholder="Write your article content here..."
                                required
                                rows="8"
                                autoResize
                                helpText="Full article content with formatting"
                            />

                            {{-- Meta Description --}}
                            <x-admin.form.textarea
                                :name="'meta_description_' . $lang"
                                label="Meta Description"
                                placeholder="SEO meta description..."
                                rows="2"
                                maxlength="160"
                                helpText="For search engine results (160 chars max)"
                            />

                        </x-admin.language-tab-panel>
                    @endforeach
                </x-admin.language-tabs>

            </div>

            <!-- Example 2: With Header and Status Indicators -->
            <div class="glass rounded-2xl p-6"
                 :class="darkMode ? 'glass-dark' : 'glass-light'">

                <h2 class="text-xl font-bold mb-4"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Example 2: With Panel Headers
                </h2>

                <x-admin.language-tabs :errors="$errors" :show-completion-status="true">
                    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                        <x-admin.language-tab-panel :language="$lang" :show-header="true">

                            {{-- Category Name --}}
                            <x-admin.form.text-input
                                :name="'category_name_' . $lang"
                                label="Category Name"
                                placeholder="Enter category name..."
                                required
                                icon="folder"
                            />

                            {{-- Category Description --}}
                            <x-admin.form.textarea
                                :name="'category_description_' . $lang"
                                label="Description"
                                placeholder="Describe this category..."
                                rows="4"
                            />

                        </x-admin.language-tab-panel>
                    @endforeach
                </x-admin.language-tabs>

            </div>

            <!-- Example 3: Compact Form (No Copy Button) -->
            <div class="glass rounded-2xl p-6"
                 :class="darkMode ? 'glass-dark' : 'glass-light'">

                <h2 class="text-xl font-bold mb-4"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Example 3: Compact Mode
                </h2>

                <x-admin.language-tabs
                    :errors="$errors"
                    :show-completion-status="false"
                    :show-copy-button="false">

                    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                        <x-admin.language-tab-panel :language="$lang">

                            {{-- Tag Name --}}
                            <x-admin.form.text-input
                                :name="'tag_name_' . $lang"
                                label="Tag Name"
                                placeholder="Enter tag name..."
                                required
                                icon="tag"
                                maxlength="50"
                            />

                        </x-admin.language-tab-panel>
                    @endforeach
                </x-admin.language-tabs>

            </div>

            <!-- Example 4: Different Default Language -->
            <div class="glass rounded-2xl p-6"
                 :class="darkMode ? 'glass-dark' : 'glass-light'">

                <h2 class="text-xl font-bold mb-4"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Example 4: English as Default
                </h2>

                <x-admin.language-tabs
                    :errors="$errors"
                    default-language="en">

                    @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                        <x-admin.language-tab-panel :language="$lang">

                            {{-- Page Title --}}
                            <x-admin.form.text-input
                                :name="'page_title_' . $lang"
                                label="Page Title"
                                placeholder="Enter page title..."
                                required
                                icon="file"
                            />

                            {{-- Page Content --}}
                            <x-admin.form.textarea
                                :name="'page_content_' . $lang"
                                label="Page Content"
                                placeholder="Write page content..."
                                required
                                rows="6"
                            />

                        </x-admin.language-tab-panel>
                    @endforeach
                </x-admin.language-tabs>

            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.articles.index') }}"
                   class="px-6 py-3 rounded-lg font-semibold transition-all"
                   :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                    <i data-lucide="arrow-left" class="w-4 h-4 inline me-2"></i>
                    Cancel
                </a>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            name="action"
                            value="draft"
                            class="px-6 py-3 rounded-lg font-semibold transition-all"
                            :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        <i data-lucide="save" class="w-4 h-4 inline me-2"></i>
                        Save Draft
                    </button>

                    <button type="submit"
                            name="action"
                            value="publish"
                            class="px-6 py-3 rounded-lg font-semibold text-white bg-gradient-primary hover:shadow-glow transition-all">
                        <i data-lucide="send" class="w-4 h-4 inline me-2"></i>
                        Publish
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Features Documentation -->
    <div class="max-w-4xl mt-12">
        <div class="glass rounded-2xl p-6"
             :class="darkMode ? 'glass-dark' : 'glass-light'">

            <h2 class="text-2xl font-bold mb-4"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                Features & Usage
            </h2>

            <div class="space-y-6">

                <!-- Feature 1 -->
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-gradient-primary flex items-center justify-center flex-shrink-0">
                        <i data-lucide="languages" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold mb-1"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Multi-Language Support
                        </h3>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Supports Arabic (RTL), English, French, Turkish, and Urdu (RTL) with automatic text direction detection.
                        </p>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-gradient-primary flex items-center justify-center flex-shrink-0">
                        <i data-lucide="check-circle" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold mb-1"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Validation Tracking
                        </h3>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Red dots indicate languages with validation errors. Automatically switches to the first language with errors on form submission.
                        </p>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-gradient-primary flex items-center justify-center flex-shrink-0">
                        <i data-lucide="percent" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold mb-1"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Completion Status
                        </h3>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Progress bar shows completion percentage based on required fields. Green checkmark appears when 100% complete.
                        </p>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-gradient-primary flex items-center justify-center flex-shrink-0">
                        <i data-lucide="copy" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold mb-1"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Copy Between Languages
                        </h3>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Quickly copy content from one language to another as a starting point for translations.
                        </p>
                    </div>
                </div>

                <!-- Feature 5 -->
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-gradient-primary flex items-center justify-center flex-shrink-0">
                        <i data-lucide="save" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold mb-1"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Auto-Save Drafts
                        </h3>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            JavaScript helper automatically saves drafts to localStorage every 3 seconds. Drafts are restored on page reload.
                        </p>
                    </div>
                </div>

                <!-- Feature 6 -->
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg bg-gradient-primary flex items-center justify-center flex-shrink-0">
                        <i data-lucide="keyboard" class="w-5 h-5 text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold mb-1"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Keyboard Navigation
                        </h3>
                        <p class="text-sm"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Use Ctrl/Cmd + Arrow keys to quickly switch between languages. Ctrl/Cmd + S to save draft.
                        </p>
                    </div>
                </div>

            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/language-tabs.js') }}"></script>
@endpush
