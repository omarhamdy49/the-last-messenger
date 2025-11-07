<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') - خاتَمُ المُرْسَلِين</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;900&display=swap" rel="stylesheet">

    <!-- Vite CSS & JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Global Quill Editor Initialization (Vanilla JavaScript) -->
    <script>
/**
 * Initialize all Quill editors on the page
 * Vanilla JavaScript - no Alpine.js dependencies
 */

// Wait for Quill library to load, then initialize editors
function waitForQuill(callback, maxAttempts = 50) {
    if (typeof Quill !== 'undefined') {
        callback();
    } else if (maxAttempts > 0) {
        setTimeout(() => waitForQuill(callback, maxAttempts - 1), 100);
    }
}

waitForQuill(function() {
    // Initialize editors when Quill is ready
    const editorWrappers = document.querySelectorAll('[data-quill-editor]');
    editorWrappers.forEach(wrapper => {
        setTimeout(() => initializeQuillEditor(wrapper), 300);
    });
});

function initializeQuillEditor(wrapper) {
    // Get configuration from data attributes
    const config = {
        fieldName: wrapper.dataset.quillName || 'content',
        language: wrapper.dataset.quillLanguage || 'ar',
        height: parseInt(wrapper.dataset.quillHeight || '400'),
        required: wrapper.dataset.quillRequired === 'true',
        disabled: wrapper.dataset.quillDisabled === 'true',
        content: wrapper.dataset.quillValue || ''
    };

    // Find the editor div and container
    const containerWrapper = wrapper.querySelector('.editor-container-wrapper');
    const editorDiv = wrapper.querySelector('[data-quill-target]');
    const hiddenInput = wrapper.querySelector('input[type="hidden"]');
    const loader = wrapper.querySelector('[data-quill-loader]');

    if (!editorDiv || !hiddenInput || !containerWrapper) {
        console.error('Missing editor elements:', { editorDiv, hiddenInput, containerWrapper });
        return;
    }

    try {
        // Initialize Quill
        const quill = new Quill(editorDiv, {
            theme: 'snow',
            placeholder: getPlaceholder(config.language),
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['blockquote', 'code-block'],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            readOnly: config.disabled
        });

        // Set initial content if provided
        if (config.content) {
            quill.root.innerHTML = config.content;
        }

        // Set RTL if needed
        const isRTL = ['ar', 'ur', 'he'].includes(config.language);
        if (isRTL) {
            editorDiv.setAttribute('dir', 'rtl');
            editorDiv.classList.add('rtl-editor');
        }

        // Update hidden input on text change
        quill.on('text-change', () => {
            const html = quill.root.innerHTML;
            hiddenInput.value = (html === '<p><br></p>' || html === '') ? '' : html;
            updateCharCount(wrapper, quill, config);
        });

        // Apply dark mode if enabled
        updateEditorTheme(editorDiv, config.language);

        // Store quill instance on wrapper for later access
        wrapper.quillInstance = quill;

        // Hide loading and show editor
        if (loader) {
            loader.style.display = 'none';
        }

        // Show the editor container
        containerWrapper.style.display = 'block';

        console.log('Quill editor initialized successfully for field:', config.fieldName);

    } catch (error) {
        console.error('Failed to initialize Quill editor:', error);
        const errorDiv = wrapper.querySelector('[data-quill-error]');
        const errorText = wrapper.querySelector('[data-quill-error-text]');
        if (errorDiv) {
            errorDiv.style.display = 'block';
            if (errorText) {
                errorText.textContent = 'Error: ' + error.message;
            }
        }
        if (loader) {
            loader.style.display = 'none';
        }
    }
}

function getPlaceholder(language) {
    const placeholders = {
        ar: 'اكتب محتوى المقالة هنا...',
        en: 'Write article content here...',
        fr: 'Écrivez le contenu de l\'article ici...',
        tr: 'Makale içeriğini buraya yazın...',
        ur: 'یہاں مضمون کا مواد لکھیں...'
    };
    return placeholders[language] || 'Write content here...';
}

function updateCharCount(wrapper, quill, config) {
    const charCounter = wrapper.querySelector('[data-quill-char-count]');
    if (!charCounter) return;

    const text = quill.getText();
    const count = text.trim().length;
    charCounter.textContent = `${count} characters`;

    // Show min chars warning
    const minCharsText = wrapper.querySelector('[data-quill-min-chars]');
    if (minCharsText && config.required && count < 50) {
        minCharsText.style.display = 'block';
    } else if (minCharsText) {
        minCharsText.style.display = 'none';
    }
}

function updateEditorTheme(editorDiv, language) {
    const darkMode = localStorage.getItem('darkMode') !== 'false';
    if (darkMode) {
        editorDiv.classList.add('dark-editor');
        const toolbar = editorDiv.parentElement?.querySelector('.ql-toolbar');
        if (toolbar) toolbar.classList.add('dark-toolbar');
    } else {
        editorDiv.classList.remove('dark-editor');
        const toolbar = editorDiv.parentElement?.querySelector('.ql-toolbar');
        if (toolbar) toolbar.classList.remove('dark-toolbar');
    }
}
    </script>

    <!-- Alpine.js Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- x-cloak CSS for Alpine.js -->
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- FilePond for file uploads -->
    <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet">
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet">
    <script src="https://unpkg.com/filepond/dist/filepond.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-validate-size/dist/filepond-plugin-image-validate-size.js"></script>

    <!-- Cropper.js for image cropping -->
    <link href="https://unpkg.com/cropperjs/dist/cropper.css" rel="stylesheet">
    <script src="https://unpkg.com/cropperjs/dist/cropper.js"></script>


    <style>
        * {
            font-family: 'Cairo', sans-serif;
        }

        /* ===== GLASS MORPHISM ===== */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-dark {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Premium Glass for Light Mode */
        .glass-light {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(0, 86, 179, 0.1);
            box-shadow: 0 8px 32px rgba(0, 86, 179, 0.05);
        }

        .glass-light-soft {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(33, 136, 56, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        .glass-light-strong {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(100, 204, 197, 0.15);
            box-shadow: 0 10px 40px rgba(0, 86, 179, 0.08);
        }

        /* ===== GRADIENTS ===== */
        .gradient-primary {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        }

        .gradient-text {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-light {
            background: linear-gradient(135deg, rgba(0, 86, 179, 0.15) 0%, rgba(33, 136, 56, 0.15) 100%);
        }

        /* ===== SIDEBAR ===== */
        aside {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Minimized sidebar styling */
        aside.lg\:w-20 .sidebar-item,
        [class*="lg:w-20"] .sidebar-item {
            justify-content: center;
            padding: 0.75rem;
        }

        aside.lg\:w-20 .sidebar-item i,
        [class*="lg:w-20"] .sidebar-item i {
            margin: 0;
        }

        /* Logo section in minimized state */
        aside.lg\:w-20 .p-6,
        [class*="lg:w-20"] .p-6 {
            padding: 1rem;
        }

        aside.lg\:w-20 .flex.items-center.justify-between,
        [class*="lg:w-20"] .flex.items-center.justify-between {
            justify-content: center;
        }

        .sidebar-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-item:hover {
            transform: translateX(-5px);
        }

        /* Light Mode Sidebar Item */
        .light .sidebar-item:hover {
            background: linear-gradient(135deg, rgba(0, 86, 179, 0.08) 0%, rgba(33, 136, 56, 0.08) 100%);
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.05);
        }

        .sidebar-item.active {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
        }

        /* Tooltip for minimized sidebar */
        .sidebar-item[title]:hover::after {
            content: attr(title);
            position: absolute;
            left: 100%;
            margin-right: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 50;
            font-weight: 500;
        }

        /* ===== CARDS ===== */
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Light Mode Card Hover */
        .light .card-hover:hover {
            box-shadow: 0 15px 35px rgba(0, 86, 179, 0.12);
        }

        .card-light {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(0, 86, 179, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }

        .card-light:hover {
            border-color: rgba(0, 86, 179, 0.2);
            box-shadow: 0 8px 32px rgba(0, 86, 179, 0.1);
        }

        /* ===== CUSTOM SCROLLBAR ===== */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        /* Light Mode Scrollbar */
        body:not(.dark) .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 86, 179, 0.05);
            border-radius: 10px;
        }

        body:not(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            border-radius: 10px;
            opacity: 0.6;
        }

        body:not(.dark) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            opacity: 0.8;
            background: linear-gradient(135deg, #218838 0%, #0056b3 100%);
        }

        /* Dark Mode Scrollbar */
        .dark .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #218838 0%, #0056b3 100%);
        }

        /* ===== RTL SPECIFIC ===== */
        [dir="rtl"] .sidebar-item:hover {
            transform: translateX(5px);
        }

        /* ===== PARTICLES BACKGROUND ===== */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            animation: float 20s infinite;
        }

        /* Light Mode Particles */
        body:not(.dark) .particle {
            background: radial-gradient(circle, rgba(0, 86, 179, 0.08) 0%, transparent 70%);
        }

        /* Dark Mode Particles */
        .dark .particle {
            background: radial-gradient(circle, rgba(0, 86, 179, 0.1) 0%, transparent 70%);
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0; }
            10% { opacity: 0.3; }
            50% { transform: translate(50px, -100vh) scale(0.5); opacity: 0; }
        }

        /* ===== SMOOTH TRANSITIONS ===== */
        * {
            transition-property: background-color, border-color, color, box-shadow;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }

        * {
            transition-duration: 200ms;
        }

        /* ===== FOCUS STATES ===== */
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }

        .dark input:focus,
        .dark select:focus,
        .dark textarea:focus {
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.2);
        }

        /* ===== RESPONSIVE UTILITIES ===== */
        /* Ensure modals are always on top */
        [class*="z-[100]"] {
            z-index: 100 !important;
        }

        /* Mobile-optimized touch targets */
        @media (max-width: 768px) {
            button,
            a,
            input[type="checkbox"],
            input[type="radio"] {
                min-height: 44px;
                min-width: 44px;
            }

            /* Better tap experience */
            * {
                -webkit-tap-highlight-color: rgba(0, 86, 179, 0.1);
            }

            /* Reduce animations on mobile for better performance */
            .particle,
            .floating-orb {
                display: none;
            }

            /* Optimize font sizes for mobile */
            h1 { font-size: 1.75rem !important; }
            h2 { font-size: 1.5rem !important; }
            h3 { font-size: 1.25rem !important; }
        }

        /* Tablet optimizations */
        @media (min-width: 768px) and (max-width: 1024px) {
            .sidebar-item span {
                display: inline-block;
            }
        }

        /* Smooth scrolling for all scrollable areas */
        .custom-scrollbar,
        .overflow-x-auto,
        .overflow-y-auto {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }

        /* Better table responsiveness */
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }

            th, td {
                padding: 0.75rem 0.5rem !important;
                font-size: 0.875rem;
            }
        }

        /* Ensure sidebar doesn't interfere on mobile */
        @media (max-width: 1023px) {
            aside {
                box-shadow: -4px 0 12px rgba(0, 0, 0, 0.15);
            }
        }

        /* Optimize modal on mobile */
        @media (max-width: 640px) {
            [class*="max-w-2xl"],
            [class*="max-w-4xl"] {
                max-width: 100vw !important;
                margin: 0 !important;
                border-radius: 1rem 1rem 0 0 !important;
            }

            .fixed.inset-0[class*="z-[100]"] {
                align-items: flex-end !important;
            }
        }
    </style>

    <!-- WYSIWYG Editor (CKEditor 5) - Global Assets -->
    <style>
        /* CKEditor Custom Styles */
        .ck-editor__editable {
            min-height: 200px;
            max-height: 600px;
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            line-height: 1.6;
        }

        .ck.ck-editor {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .ck.ck-toolbar {
            background: #f9fafb !important;
            border-color: #e5e7eb !important;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }

        .dark .ck.ck-toolbar {
            background: #111827 !important;
            border-color: #374151 !important;
        }

        .ck.ck-editor__main > .ck-editor__editable {
            background: white;
            border-color: #e5e7eb;
            color: #111827;
        }

        .dark .ck.ck-editor__main > .ck-editor__editable {
            background: #1f2937;
            border-color: #374151;
            color: #f9fafb;
        }

        .ck.ck-editor__main > .ck-editor__editable:focus {
            border-color: #0056b3;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }

        /* RTL Support */
        .ck-editor[dir="rtl"] .ck-editor__editable {
            direction: rtl;
            text-align: right;
        }

        /* Dark mode toolbar buttons */
        .dark .ck.ck-button,
        .dark .ck.ck-dropdown__button {
            color: #9ca3af !important;
        }

        .dark .ck.ck-button:hover,
        .dark .ck.ck-dropdown__button:hover {
            background: #374151 !important;
        }

        .dark .ck.ck-button.ck-on {
            background: #0056b3 !important;
            color: white !important;
        }
    </style>

    @stack('styles')
</head>
<body class="min-h-screen" x-data="{
    sidebarOpen: window.innerWidth >= 1024,
    darkMode: localStorage.getItem('darkMode') === 'true',
    init() {
        // Handle responsive sidebar on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                this.sidebarOpen = true;
            }
        });
    }
}" :class="darkMode ? 'dark bg-gray-900' : 'light'"
      @keydown.escape="if (window.innerWidth < 1024) { sidebarOpen = false }">
    <style>
        body.light {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(240, 245, 255, 0.6) 100%);
            position: relative;
        }

        body.light::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(0, 86, 179, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(33, 136, 56, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
    </style>

    <!-- Floating Particles Background -->
    <div class="particles">
        @for ($i = 0; $i < 15; $i++)
            <div class="particle" style="width: {{ rand(50, 150) }}px; height: {{ rand(50, 150) }}px; left: {{ rand(0, 100) }}%; animation-delay: {{ $i * 0.5 }}s;"></div>
        @endfor
    </div>

    <div class="flex h-screen overflow-hidden">
        <!-- Mobile Overlay -->
        <div x-show="sidebarOpen && window.innerWidth < 1024"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 lg:hidden"
             style="display: none;"></div>

        <!-- Sidebar -->
        <div class="fixed lg:relative inset-y-0 right-0 z-40 lg:z-10 transition-transform duration-300 lg:translate-x-0"
             :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'"
             x-cloak>
            @include('admin.partials.sidebar')
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden w-full">
            <!-- Header -->
            @include('admin.partials.header')

            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto custom-scrollbar p-4 lg:p-6 relative z-10">
                <!-- Breadcrumb -->
                @if(isset($breadcrumbs))
                    <nav class="mb-6" aria-label="Breadcrumb">
                        <ol class="flex items-center space-x-2 space-x-reverse">
                            @foreach($breadcrumbs as $index => $breadcrumb)
                                <li class="flex items-center">
                                    @if($index > 0)
                                        <i data-lucide="chevron-left" class="w-4 h-4 mx-2 text-gray-400"></i>
                                    @endif
                                    @if($loop->last)
                                        <span class="text-gray-600 dark:text-gray-300 font-semibold">{{ $breadcrumb['title'] }}</span>
                                    @else
                                        <a href="{{ $breadcrumb['url'] }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 transition-colors">
                                            {{ $breadcrumb['title'] }}
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @endif

                <!-- Page Content -->
                <div class="animate-fade-in-up">
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border-r-4 border-green-500 text-green-800 dark:text-green-300 rounded-lg shadow-sm animate-fade-in-up">
                            <div class="flex items-center">
                                <i data-lucide="check-circle" class="w-5 h-5 ml-3"></i>
                                <span class="font-semibold">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-r-4 border-red-500 text-red-800 dark:text-red-300 rounded-lg shadow-sm animate-fade-in-up">
                            <div class="flex items-center">
                                <i data-lucide="alert-circle" class="w-5 h-5 ml-3"></i>
                                <span class="font-semibold">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            @include('admin.partials.footer')
        </div>
    </div>

    <!-- Toast Notification Component -->
    @include('admin.components.toast')

    <!-- Confirmation Dialog Component -->
    @include('admin.components.confirm-dialog')

    {{-- DISABLED: Global Media Picker Modal --}}
    {{-- <x-media.picker-modal /> --}}

    {{-- DISABLED: Global Image Cropper Modal --}}
    {{-- <x-media.cropper-modal /> --}}

    <!-- Media Picker Component Script -->
    <script src="{{ asset('js/media-picker.js') }}"></script>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Dark mode toggle
        function toggleDarkMode() {
            const darkMode = localStorage.getItem('darkMode') !== 'true';
            localStorage.setItem('darkMode', darkMode);
            window.location.reload();
        }

        // Add CSRF token to all AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        if (token) {
            window.axios = {
                defaults: {
                    headers: {
                        common: {
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }
                }
            };
        }

        /**
         * Media Picker Alpine.js Component
         * Supports both single and multiple selection modes
         */
        function mediaPicker(mode = 'single', selectedMedia = null, selectedMediaItems = []) {
            return {
                // Core state
                pickerMode: mode,
                selectedMedia: selectedMedia,
                selectedMediaItems: [...selectedMediaItems],

                // Modal state
                isOpen: false,
                loading: false,

                // Filter state
                search: '',
                typeFilter: 'all',
                dateFilter: 'all',

                // Data
                allItems: [],
                filteredItems: [],

                // Temporary selection (for modal confirmation)
                tempSelected: [],

                // Pagination
                pagination: {
                    currentPage: 1,
                    lastPage: 1,
                    total: 0,
                    from: 0,
                    to: 0,
                    perPage: 20
                },

                /**
                 * Initialize the component
                 */
                init() {
                    // If we have pre-selected media, ensure it's in the right format
                    if (this.pickerMode === 'single' && this.selectedMedia) {
                        this.selectedMedia = typeof this.selectedMedia === 'object' ? this.selectedMedia : null;
                    }

                    if (this.pickerMode === 'multiple' && this.selectedMediaItems.length > 0) {
                        this.tempSelected = [...this.selectedMediaItems.map(item => item.id)];
                    }
                },

                /**
                 * Open the media library modal
                 */
                openModal() {
                    this.isOpen = true;

                    // Reset temp selection to current selection
                    if (this.pickerMode === 'single' && this.selectedMedia) {
                        this.tempSelected = [this.selectedMedia.id];
                    } else if (this.pickerMode === 'multiple') {
                        this.tempSelected = [...this.selectedMediaItems.map(item => item.id)];
                    } else {
                        this.tempSelected = [];
                    }

                    // Load media
                    this.loadMedia();

                    // Reinitialize Lucide icons after modal renders
                    this.$nextTick(() => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    });
                },

                /**
                 * Load media from the API
                 */
                async loadMedia(page = 1) {
                    this.loading = true;

                    try {
                        const params = new URLSearchParams({
                            page: page,
                            per_page: this.pagination.perPage,
                            type: this.typeFilter !== 'all' ? this.typeFilter : '',
                            search: this.search
                        });

                        const response = await fetch(`/su/portal/v1/admin/simple-media?${params}`, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Failed to load media');
                        }

                        const data = await response.json();

                        // Update media list
                        this.allItems = data.data || [];
                        this.filteredItems = [...this.allItems];

                        // Update pagination
                        this.pagination = {
                            currentPage: data.current_page || 1,
                            lastPage: data.last_page || 1,
                            total: data.total || 0,
                            from: data.from || 0,
                            to: data.to || 0,
                            perPage: data.per_page || 20
                        };

                    } catch (error) {
                        console.error('Error loading media:', error);
                        alert('فشل تحميل الوسائط. يرجى المحاولة مرة أخرى.');
                    } finally {
                        this.loading = false;
                    }
                },

                /**
                 * Filter media based on search and filters
                 */
                filterMedia() {
                    // Reload from API with filters
                    this.loadMedia(1);
                },

                /**
                 * Select/deselect a media item
                 */
                selectMedia(media) {
                    if (this.pickerMode === 'single') {
                        // Single mode: replace selection
                        this.tempSelected = [media.id];
                    } else {
                        // Multiple mode: toggle selection
                        const index = this.tempSelected.indexOf(media.id);
                        if (index > -1) {
                            this.tempSelected.splice(index, 1);
                        } else {
                            this.tempSelected.push(media.id);
                        }
                    }
                },

                /**
                 * Check if a media item is selected
                 */
                isMediaSelected(media) {
                    return this.tempSelected.includes(media.id);
                },

                /**
                 * Confirm selection and close modal
                 */
                confirmSelection() {
                    if (this.pickerMode === 'single') {
                        // Single mode: set selectedMedia
                        const selected = this.allItems.find(item => item.id === this.tempSelected[0]);
                        this.selectedMedia = selected || null;
                    } else {
                        // Multiple mode: set selectedMediaItems
                        this.selectedMediaItems = this.allItems.filter(item =>
                            this.tempSelected.includes(item.id)
                        );
                    }

                    this.isOpen = false;
                },

                /**
                 * Remove selected media (single mode)
                 */
                removeMedia() {
                    this.selectedMedia = null;
                },

                /**
                 * Remove a media item from selection (multiple mode)
                 */
                removeMediaItem(index) {
                    this.selectedMediaItems.splice(index, 1);
                },

                /**
                 * Format file size for display
                 */
                formatFileSize(bytes) {
                    if (!bytes) return '0 Bytes';

                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));

                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                },

                /**
                 * Pagination: Go to specific page
                 */
                goToPage(page) {
                    if (page >= 1 && page <= this.pagination.lastPage) {
                        this.loadMedia(page);
                    }
                },

                /**
                 * Pagination: Go to next page
                 */
                nextPage() {
                    if (this.pagination.currentPage < this.pagination.lastPage) {
                        this.loadMedia(this.pagination.currentPage + 1);
                    }
                },

                /**
                 * Pagination: Go to previous page
                 */
                previousPage() {
                    if (this.pagination.currentPage > 1) {
                        this.loadMedia(this.pagination.currentPage - 1);
                    }
                },

                /**
                 * Calculate page numbers to display
                 */
                pageNumbers() {
                    const current = this.pagination.currentPage;
                    const last = this.pagination.lastPage;
                    const delta = 2; // Number of pages to show on each side
                    const pages = [];

                    for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
                        pages.push(i);
                    }

                    return pages;
                }
            };
        }

        // Make mediaPicker globally available
        window.mediaPicker = mediaPicker;
    </script>

    <!-- WYSIWYG Editor (CKEditor 5) - Global Script -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.1.0/classic/ckeditor.js"></script>
    <script src="{{ asset('js/admin/wysiwyg-editor.js') }}"></script>

    @stack('scripts')
</body>
</html>
