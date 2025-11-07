@extends('layouts.admin')

@section('page-header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2"
                :class="darkMode ? 'text-white' : 'text-gray-900'">
                Form Builder Demo
            </h1>
            <p class="text-lg" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                Comprehensive showcase of all form components with multi-language support
            </p>
        </div>
    </div>
@endsection

@section('content')
    <x-admin.form-builder action="/admin/demo/submit" method="POST" enctype="multipart/form-data">

        <!-- Basic Information Section -->
        <x-admin.form-section
            title="Basic Information"
            description="Essential details about your content"
            icon="info"
            collapsible="true">

            <!-- Multi-language Text Input -->
            <x-admin.form.text-input
                name="title"
                label="Title"
                :multilang="true"
                required
                placeholder="Enter title..."
                icon="heading"
                maxlength="200"
                helpText="This will be displayed as the main heading"
            />

            <!-- Single Language Email Input -->
            <x-admin.form.text-input
                name="email"
                label="Email Address"
                type="email"
                placeholder="example@domain.com"
                icon="mail"
                required
                helpText="We'll never share your email with anyone else"
            />

            <!-- URL Input -->
            <x-admin.form.text-input
                name="website"
                label="Website URL"
                type="url"
                placeholder="https://example.com"
                icon="link"
                helpText="Enter a valid URL starting with http:// or https://"
            />

            <!-- Number Input -->
            <x-admin.form.text-input
                name="order"
                label="Display Order"
                type="number"
                value="0"
                placeholder="0"
                icon="hash"
                helpText="Lower numbers appear first"
            />
        </x-admin.form-section>

        <!-- Content Section -->
        <x-admin.form-section
            title="Content"
            description="Detailed content for your item"
            icon="file-text">

            <!-- Multi-language Textarea -->
            <x-admin.form.textarea
                name="description"
                label="Description"
                :multilang="true"
                rows="4"
                maxlength="1000"
                autoResize="true"
                helpText="Provide a detailed description"
            />

            <!-- Single Language Textarea -->
            <x-admin.form.textarea
                name="notes"
                label="Internal Notes"
                rows="3"
                placeholder="Add any internal notes here..."
                helpText="These notes are only visible to administrators"
            />
        </x-admin.form-section>

        <!-- Options Section -->
        <x-admin.form-section
            title="Options & Settings"
            description="Configure visibility and behavior"
            icon="settings"
            collapsible="true"
            collapsed="true">

            <!-- Select Dropdown -->
            <x-admin.form.select
                name="status"
                label="Publication Status"
                :options="[
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                    'scheduled' => 'Scheduled'
                ]"
                value="draft"
                icon="file-check"
                required
                helpText="Control the visibility of this content"
            />

            <!-- Searchable Select -->
            <x-admin.form.select
                name="category"
                label="Category"
                :options="[
                    'seerah' => 'Seerah',
                    'hadith' => 'Hadith',
                    'quran' => 'Quran',
                    'fiqh' => 'Fiqh',
                    'aqeedah' => 'Aqeedah',
                    'tafseer' => 'Tafseer',
                    'history' => 'Islamic History',
                    'biography' => 'Biography',
                    'literature' => 'Islamic Literature',
                    'contemporary' => 'Contemporary Issues'
                ]"
                icon="folder"
                searchable="true"
                helpText="Select a category or search to find one"
            />

            <!-- Multi-select -->
            <x-admin.form.select
                name="tags"
                label="Tags"
                :options="[
                    'featured' => 'Featured',
                    'trending' => 'Trending',
                    'educational' => 'Educational',
                    'beginner' => 'Beginner Friendly',
                    'advanced' => 'Advanced',
                    'popular' => 'Popular'
                ]"
                icon="tag"
                multiple="true"
                searchable="true"
                helpText="Select multiple tags to categorize this content"
            />

            <!-- Radio Buttons -->
            <x-admin.form.radio
                name="visibility"
                label="Visibility Level"
                :options="[
                    'public' => [
                        'label' => 'Public',
                        'description' => 'Visible to everyone',
                        'icon' => 'globe'
                    ],
                    'members' => [
                        'label' => 'Members Only',
                        'description' => 'Visible only to registered users',
                        'icon' => 'users'
                    ],
                    'premium' => [
                        'label' => 'Premium',
                        'description' => 'Requires premium subscription',
                        'icon' => 'crown'
                    ],
                    'private' => [
                        'label' => 'Private',
                        'description' => 'Only visible to administrators',
                        'icon' => 'lock'
                    ]
                ]"
                value="public"
                required
                helpText="Choose who can access this content"
            />

            <!-- Inline Radio Buttons -->
            <x-admin.form.radio
                name="content_type"
                label="Content Type"
                :options="[
                    'article' => 'Article',
                    'video' => 'Video',
                    'audio' => 'Audio',
                    'document' => 'Document'
                ]"
                value="article"
                inline="true"
                required
            />

            <!-- Checkboxes -->
            <x-admin.form.checkbox
                name="featured"
                label="Featured Content"
                description="Display this content prominently on the homepage"
            />

            <x-admin.form.checkbox
                name="allow_comments"
                label="Allow Comments"
                description="Enable users to leave comments on this content"
                checked="true"
            />

            <x-admin.form.checkbox
                name="notify_subscribers"
                label="Notify Subscribers"
                description="Send email notification to subscribers when this is published"
            />
        </x-admin.form-section>

        <!-- Media Section -->
        <x-admin.form-section
            title="Media & Files"
            description="Upload images, videos, and documents"
            icon="image">

            <!-- Image Upload -->
            <x-admin.form.file-input
                name="featured_image"
                label="Featured Image"
                accept="image/jpeg,image/png,image/webp,image/gif"
                maxSize="5"
                preview="true"
                helpText="Upload a featured image (max 5MB). Recommended size: 1200x630px"
            />

            <!-- Multiple File Upload -->
            <x-admin.form.file-input
                name="attachments"
                label="Attachments"
                multiple="true"
                accept="image/*,application/pdf,.doc,.docx"
                maxSize="10"
                helpText="Upload multiple files (images, PDFs, or documents)"
            />

            <!-- Video File -->
            <x-admin.form.file-input
                name="video"
                label="Video File"
                accept="video/mp4,video/webm,video/ogg"
                maxSize="100"
                preview="false"
                helpText="Upload a video file (max 100MB)"
            />
        </x-admin.form-section>

        <!-- Scheduling Section -->
        <x-admin.form-section
            title="Schedule & Timing"
            description="Set publication dates and times"
            icon="clock"
            collapsible="true">

            <!-- Date Picker -->
            <x-admin.form.date-picker
                name="publish_date"
                label="Publication Date"
                helpText="When should this content be published?"
            />

            <!-- Date Time Picker -->
            <x-admin.form.date-picker
                name="scheduled_at"
                label="Schedule Date & Time"
                time="true"
                helpText="Set a specific date and time for automatic publication"
            />

            <!-- Date Range -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-admin.form.date-picker
                    name="start_date"
                    label="Start Date"
                    helpText="When should this become active?"
                />

                <x-admin.form.date-picker
                    name="end_date"
                    label="End Date"
                    helpText="When should this expire?"
                />
            </div>
        </x-admin.form-section>

        <!-- Advanced Options Section -->
        <x-admin.form-section
            title="Advanced Options"
            description="SEO and metadata settings"
            icon="code"
            collapsible="true"
            collapsed="true">

            <!-- SEO Title -->
            <x-admin.form.text-input
                name="seo_title"
                label="SEO Title"
                :multilang="true"
                maxlength="60"
                placeholder="Optimized title for search engines..."
                icon="search"
                helpText="Recommended length: 50-60 characters"
            />

            <!-- Meta Description -->
            <x-admin.form.textarea
                name="meta_description"
                label="Meta Description"
                :multilang="true"
                rows="2"
                maxlength="160"
                helpText="Brief description for search engine results (150-160 characters)"
            />

            <!-- Keywords -->
            <x-admin.form.text-input
                name="keywords"
                label="Keywords"
                placeholder="keyword1, keyword2, keyword3"
                icon="tag"
                helpText="Comma-separated keywords for SEO"
            />

            <!-- Slug -->
            <x-admin.form.text-input
                name="slug"
                label="URL Slug"
                placeholder="my-awesome-article"
                icon="link"
                pattern="[a-z0-9-]+"
                helpText="URL-friendly version (lowercase, hyphens only)"
            />
        </x-admin.form-section>

        <!-- Form Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 p-6 rounded-xl glass"
             :class="darkMode ? 'glass-dark' : 'glass-light'">

            <!-- Left Side Actions -->
            <div class="flex items-center gap-3">
                <button type="button"
                        onclick="window.history.back()"
                        class="px-6 py-3 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2"
                        :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    Cancel
                </button>

                <button type="button"
                        class="px-6 py-3 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2 border-2"
                        :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                    Preview
                </button>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-3">
                <button type="submit"
                        name="action"
                        value="draft"
                        class="px-6 py-3 rounded-lg font-semibold transition-all duration-300 flex items-center gap-2 border-2"
                        :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    Save as Draft
                </button>

                <button type="submit"
                        name="action"
                        value="publish"
                        class="px-8 py-3 rounded-lg font-bold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] hover:shadow-glow hover:scale-105 transition-all duration-300 flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5"></i>
                    Publish
                </button>
            </div>
        </div>

    </x-admin.form-builder>

    <!-- Component Information Card -->
    <div class="mt-8 p-6 rounded-xl glass animate-fadeInUp"
         :class="darkMode ? 'glass-dark' : 'glass-light'"
         style="animation-delay: 0.2s">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center bg-gradient-to-br from-[#0056b3] to-[#218838] flex-shrink-0">
                <i data-lucide="lightbulb" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Form Builder Components
                </h3>
                <p class="mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    This demo showcases all available form components with various configurations:
                </p>
                <ul class="space-y-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        Multi-language support with language tabs
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        Real-time validation with error messages
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        Character count and length indicators
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        Dark mode support throughout
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        RTL-aware layout for Arabic/Urdu
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        Drag & drop file uploads with preview
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        Collapsible form sections
                    </li>
                    <li class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                        Custom-styled radio buttons and checkboxes
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Initialize Lucide icons after Alpine.js finishes rendering
    document.addEventListener('alpine:initialized', () => {
        setTimeout(() => {
            lucide.createIcons();
        }, 100);
    });
</script>
@endpush
