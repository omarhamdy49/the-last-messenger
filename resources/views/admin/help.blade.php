@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ darkMode: $store.admin.darkMode, activeCategory: 'getting-started' }">

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold mb-2 gradient-text">Help Center</h1>
        <p class="text-lg" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            Find answers to common questions and learn how to use the admin panel
        </p>
    </div>

    <!-- Search Bar -->
    <div class="mb-8">
        <div class="relative max-w-2xl">
            <i data-lucide="search"
               class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5"
               :class="darkMode ? 'text-gray-400' : 'text-gray-500'"></i>
            <input type="text"
                   placeholder="Search for help articles..."
                   class="w-full ps-12 pe-4 py-4 rounded-xl border-2 transition-all focus:ring-4 focus:ring-brand/20"
                   :class="darkMode ?
                       'bg-gray-800 border-gray-700 text-white focus:border-brand' :
                       'bg-white border-gray-200 text-gray-900 focus:border-brand'">
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

        <!-- Sidebar Navigation -->
        <div class="lg:col-span-1">
            <nav class="space-y-2">
                <button @click="activeCategory = 'getting-started'"
                        :class="activeCategory === 'getting-started' ?
                            'bg-gradient-to-r from-brand to-secondary text-white' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-left">
                    <i data-lucide="rocket" class="w-5 h-5"></i>
                    <span class="font-semibold">Getting Started</span>
                </button>

                <button @click="activeCategory = 'articles'"
                        :class="activeCategory === 'articles' ?
                            'bg-gradient-to-r from-brand to-secondary text-white' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-left">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                    <span class="font-semibold">Articles</span>
                </button>

                <button @click="activeCategory = 'media'"
                        :class="activeCategory === 'media' ?
                            'bg-gradient-to-r from-brand to-secondary text-white' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-left">
                    <i data-lucide="image" class="w-5 h-5"></i>
                    <span class="font-semibold">Media Library</span>
                </button>

                <button @click="activeCategory = 'users'"
                        :class="activeCategory = == 'users' ?
                            'bg-gradient-to-r from-brand to-secondary text-white' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-left">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span class="font-semibold">User Management</span>
                </button>

                <button @click="activeCategory = 'settings'"
                        :class="activeCategory === 'settings' ?
                            'bg-gradient-to-r from-brand to-secondary text-white' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-left">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span class="font-semibold">Settings</span>
                </button>

                <button @click="activeCategory = 'troubleshooting'"
                        :class="activeCategory === 'troubleshooting' ?
                            'bg-gradient-to-r from-brand to-secondary text-white' :
                            (darkMode ? 'text-gray-300 hover:bg-gray-800' : 'text-gray-700 hover:bg-gray-100')"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all text-left">
                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                    <span class="font-semibold">Troubleshooting</span>
                </button>
            </nav>

            <!-- Contact Support -->
            <div class="mt-8 p-6 rounded-xl"
                 :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                <h3 class="font-bold mb-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    Need More Help?
                </h3>
                <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Our support team is here to help
                </p>
                <a href="mailto:support@example.com"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-brand to-secondary text-white text-sm font-semibold hover:scale-105 transition-transform">
                    <i data-lucide="mail" class="w-4 h-4"></i>
                    Contact Support
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="lg:col-span-3">

            <!-- Getting Started -->
            <div x-show="activeCategory === 'getting-started'" x-cloak class="animate-fadeIn">
                <h2 class="text-2xl font-bold mb-6 gradient-text">Getting Started</h2>

                <div class="space-y-4">
                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>How do I navigate the admin panel?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p class="mb-4">The admin panel uses a sidebar navigation on the left (or right for RTL languages). Key sections include:</p>
                            <ul class="list-disc list-inside space-y-2">
                                <li><strong>Dashboard:</strong> Overview and statistics</li>
                                <li><strong>Content:</strong> Manage articles, categories, and tags</li>
                                <li><strong>Media:</strong> Upload and manage images/videos</li>
                                <li><strong>Users:</strong> Manage user accounts and permissions</li>
                                <li><strong>Settings:</strong> Configure system settings</li>
                            </ul>
                        </div>
                    </details>

                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>How do I switch between dark and light mode?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p>Click the sun/moon icon in the top right corner of the header to toggle between dark and light modes. Your preference will be saved automatically.</p>
                        </div>
                    </details>

                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>How do I change the language?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p>Use the language dropdown in the top right corner to switch between Arabic, English, French, Turkish, and Urdu. The interface will automatically adjust for RTL languages.</p>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Articles -->
            <div x-show="activeCategory === 'articles'" x-cloak class="animate-fadeIn">
                <h2 class="text-2xl font-bold mb-6 gradient-text">Managing Articles</h2>

                <div class="space-y-4">
                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>How do I create a new article?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <ol class="list-decimal list-inside space-y-2">
                                <li>Navigate to Content → Articles</li>
                                <li>Click the "Create Article" button</li>
                                <li>Fill in the required fields (Title, Content) for each language</li>
                                <li>Add a featured image, categories, and tags</li>
                                <li>Choose a status (Draft/Published/Scheduled)</li>
                                <li>Click "Publish" or "Save Draft"</li>
                            </ol>
                        </div>
                    </details>

                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>How do I schedule an article for future publication?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p>When creating or editing an article, select "Scheduled" as the status, then use the date-time picker to choose when you want the article to be published. The article will automatically go live at the scheduled time.</p>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Media -->
            <div x-show="activeCategory === 'media'" x-cloak class="animate-fadeIn">
                <h2 class="text-2xl font-bold mb-6 gradient-text">Media Library</h2>

                <div class="space-y-4">
                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>How do I upload images?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p class="mb-4">You can upload images in two ways:</p>
                            <ol class="list-decimal list-inside space-y-2">
                                <li><strong>Drag and Drop:</strong> Simply drag image files from your computer and drop them onto the upload area</li>
                                <li><strong>File Browser:</strong> Click the "Upload" button and select files from your computer</li>
                            </ol>
                            <p class="mt-4">Supported formats: JPG, PNG, GIF, WebP (max 10MB per file)</p>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Users -->
            <div x-show="activeCategory === 'users'" x-cloak class="animate-fadeIn">
                <h2 class="text-2xl font-bold mb-6 gradient-text">User Management</h2>

                <div class="space-y-4">
                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>How do I create a new user?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p>Navigate to Users section, click "Add New User", fill in the required information (name, email, password), assign a role (Admin/Editor/User), and click "Create User".</p>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Settings -->
            <div x-show="activeCategory === 'settings'" x-cloak class="animate-fadeIn">
                <h2 class="text-2xl font-bold mb-6 gradient-text">Settings & Configuration</h2>

                <div class="space-y-4">
                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>Where do I configure site settings?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p>Click on Settings in the sidebar to access site configuration options including general settings, SEO defaults, and language management.</p>
                        </div>
                    </details>
                </div>
            </div>

            <!-- Troubleshooting -->
            <div x-show="activeCategory === 'troubleshooting'" x-cloak class="animate-fadeIn">
                <h2 class="text-2xl font-bold mb-6 gradient-text">Troubleshooting</h2>

                <div class="space-y-4">
                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>I can't upload files. What should I do?</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p class="mb-4">Check the following:</p>
                            <ul class="list-disc list-inside space-y-2">
                                <li>File size is within the limit (10MB for images, 100MB for videos)</li>
                                <li>File format is supported</li>
                                <li>Your internet connection is stable</li>
                                <li>Clear your browser cache and try again</li>
                            </ul>
                            <p class="mt-4">If the problem persists, contact support.</p>
                        </div>
                    </details>

                    <details class="group rounded-xl overflow-hidden"
                             :class="darkMode ? 'bg-gray-800' : 'bg-white'">
                        <summary class="px-6 py-4 cursor-pointer font-semibold flex items-center justify-between"
                                 :class="darkMode ? 'text-white hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-50'">
                            <span>The page is not loading correctly</span>
                            <i data-lucide="chevron-down" class="w-5 h-5 group-open:rotate-180 transition-transform"></i>
                        </summary>
                        <div class="px-6 py-4 border-t"
                             :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-200 text-gray-600'">
                            <p class="mb-4">Try these steps:</p>
                            <ol class="list-decimal list-inside space-y-2">
                                <li>Refresh the page (Ctrl/Cmd + R)</li>
                                <li>Clear your browser cache</li>
                                <li>Try a different browser</li>
                                <li>Check if JavaScript is enabled</li>
                                <li>Disable browser extensions temporarily</li>
                            </ol>
                        </div>
                    </details>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    // Reinitialize Lucide icons
    document.addEventListener('alpine:initialized', () => {
        lucide.createIcons();
    });
</script>
@endpush
@endsection
