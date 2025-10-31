{{-- Quill Rich Text Editor Component (Enhanced & Resizable) --}}
<div class="relative quill-editor-wrapper"
     data-quill-editor
     data-quill-name="{{ $name ?? 'content' }}"
     data-quill-language="{{ $language ?? 'ar' }}"
     data-quill-height="{{ $height ?? 400 }}"
     data-quill-required="{{ ($required ?? false) ? 'true' : 'false' }}"
     data-quill-disabled="{{ ($disabled ?? false) ? 'true' : 'false' }}"
     data-quill-value="{{ $value ?? '' }}">

    {{-- Loading State --}}
    <div data-quill-loader class="flex items-center justify-center rounded-lg bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700"
         style="min-height: 300px; z-index: 10; position: relative;">
        <div class="text-center">
            <div class="animate-spin inline-block mb-2">
                <svg class="w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <p class="text-gray-700 dark:text-gray-300 text-xs font-medium">
                {{ __('Initializing editor...') }}
            </p>
        </div>
    </div>

    {{-- Editor Container with Shadow and Border Radius --}}
    <div class="editor-container-wrapper relative rounded-lg overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700"
         style="display: none;">
        {{-- Quill Editor Container --}}
        <div class="quill-container-enhanced rounded-t-lg bg-white dark:bg-gray-800 transition-all duration-300"
             style="min-height: var(--editor-height, 400px);"
             data-quill-target></div>

        {{-- Resize Handle --}}
        <div class="resize-handle bg-gray-100 dark:bg-gray-700 h-1.5 cursor-ns-resize hover:bg-blue-400 dark:hover:bg-blue-500 transition-colors flex items-center justify-center group"
             style="touch-action: none;">
        </div>
    </div>

    {{-- Character Counter & Stats --}}
    <div class="mt-3 flex items-center justify-between flex-wrap gap-2 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
        {{-- Character Count --}}
        <div class="flex items-center gap-1.5 text-sm">
            <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3.293 1.293a1 1 0 011.414 0L10 9.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
            <span data-quill-char-count class="font-medium text-gray-700 dark:text-gray-300">0 characters</span>
        </div>

        {{-- Min Characters Warning --}}
        @if($required ?? false)
            <div data-quill-min-chars class="flex items-center gap-1.5 text-red-600 dark:text-red-400 text-xs" style="display: none;">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium">Min 50 chars</span>
            </div>
        @endif

        {{-- Language & Direction Badge --}}
        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-gray-700 rounded-full border border-gray-300 dark:border-gray-600 text-xs">
            <span class="font-semibold text-gray-700 dark:text-gray-300">
                {{ strtoupper($language ?? 'ar') }}
            </span>
            <span class="text-gray-500 dark:text-gray-400">
                {{ in_array($language ?? 'ar', ['ar', 'ur', 'he']) ? '↔' : '↔' }}
            </span>
        </div>
    </div>

    {{-- Error Message --}}
    <div data-quill-error class="mt-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 text-red-700 dark:text-red-400 text-xs font-medium flex items-start gap-2" style="display: none;">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span data-quill-error-text>Error loading editor</span>
    </div>

    {{-- Hidden Input for Form Submission --}}
    <input type="hidden"
           name="{{ $name ?? 'content' }}"
           value="{{ $value ?? '' }}">
</div>

{{-- Quill CDN Stylesheets --}}
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

{{-- Quill JavaScript --}}
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

{{-- Custom CSS for Enhanced Quill Editor --}}
<style>
/* ========== QUILL EDITOR BASE STYLES ========== */
.quill-container-enhanced {
    font-family: 'Cairo', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.8;
    word-wrap: break-word;
}

/* ========== TOOLBAR STYLES ========== */
.ql-toolbar {
    border-radius: 0.5rem 0.5rem 0 0 !important;
    padding: 10px 12px !important;
    background: linear-gradient(to right, #f9fafb, #ffffff) !important;
    border: 1px solid #e5e7eb !important;
    border-bottom: 1px solid #e5e7eb !important;
    gap: 4px !important;
    display: flex !important;
    flex-wrap: wrap !important;
}

.dark-editor .ql-toolbar.ql-snow {
    background: linear-gradient(to right, #1f2937, #111827) !important;
    border-color: #374151 !important;
}

/* Toolbar Button Styles */
.ql-toolbar button {
    width: 32px !important;
    height: 32px !important;
    padding: 4px !important;
    border-radius: 4px !important;
    transition: all 0.15s ease !important;
    border: 1px solid transparent !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.ql-toolbar button:hover:not(.ql-picker-label) {
    background-color: rgba(0, 86, 179, 0.08) !important;
    border-color: rgba(0, 86, 179, 0.2) !important;
}

.ql-toolbar button.ql-active {
    background-color: #0056b3 !important;
    color: white !important;
    border-color: #0056b3 !important;
    box-shadow: 0 1px 3px rgba(0, 86, 179, 0.3) !important;
}

.dark-editor .ql-toolbar button:hover:not(.ql-picker-label) {
    background-color: rgba(96, 165, 250, 0.1) !important;
}

.dark-editor .ql-toolbar button.ql-active {
    background-color: #3b82f6 !important;
}

/* Toolbar Icons */
.ql-toolbar .ql-stroke {
    stroke: #6b7280 !important;
    stroke-width: 1.5 !important;
}

.ql-toolbar .ql-fill,
.ql-toolbar .ql-picker-label {
    fill: #6b7280 !important;
}

.ql-toolbar button.ql-active .ql-stroke {
    stroke: white !important;
}

.ql-toolbar button.ql-active .ql-fill {
    fill: white !important;
}

.dark-editor .ql-toolbar .ql-stroke {
    stroke: #9ca3af !important;
}

.dark-editor .ql-toolbar .ql-fill {
    fill: #9ca3af !important;
}

/* Picker (Dropdown) Styles */
.ql-picker-label {
    font-size: 12px !important;
    height: 32px !important;
    padding: 4px 8px !important;
}

.ql-picker {
    display: flex !important;
    align-items: center !important;
}

/* ========== DARK MODE STYLES ========== */
.dark-editor {
    background-color: #1f2937 !important;
    color: white !important;
    border-color: #374151 !important;
}

.dark-editor .ql-editor {
    color: white !important;
    background-color: #1f2937 !important;
}

/* ========== RTL EDITOR STYLES ========== */
.quill-container-enhanced[dir="rtl"],
.quill-container-enhanced.rtl-editor {
    direction: rtl !important;
    text-align: right !important;
}

.quill-container-enhanced.rtl-editor p,
.quill-container-enhanced.rtl-editor li,
.quill-container-enhanced.rtl-editor blockquote {
    text-align: right !important;
}

/* ========== EDITOR CONTENT STYLES ========== */
.ql-container.ql-snow {
    border: none !important;
    border-radius: 0 0 0.5rem 0.5rem !important;
}

.ql-editor {
    padding: 16px !important;
    font-size: 14px !important;
    line-height: 1.8 !important;
    min-height: inherit !important;
    background: white;
    color: #1f2937;
}

.ql-editor.ql-blank::before {
    color: #9ca3af !important;
    font-style: italic !important;
    font-size: 13px !important;
}

/* ========== HEADING STYLES ========== */
.ql-editor h1,
.ql-editor h2,
.ql-editor h3 {
    margin: 12px 0 8px 0 !important;
    font-weight: 700 !important;
    line-height: 1.3 !important;
}

.ql-editor h1 {
    font-size: 28px !important;
    color: #111827 !important;
}

.ql-editor h2 {
    font-size: 24px !important;
    color: #1f2937 !important;
}

.ql-editor h3 {
    font-size: 20px !important;
    color: #374151 !important;
}

.dark-editor .ql-editor h1,
.dark-editor .ql-editor h2,
.dark-editor .ql-editor h3 {
    color: white !important;
}

/* ========== PARAGRAPH & LIST STYLES ========== */
.ql-editor p {
    margin-bottom: 12px !important;
}

.ql-editor ol,
.ql-editor ul {
    margin: 8px 0 !important;
    padding-left: 24px !important;
}

.ql-editor li {
    margin-bottom: 6px !important;
}

/* ========== LINK STYLES ========== */
.ql-snow a {
    color: #0056b3 !important;
    text-decoration: underline !important;
    font-weight: 500 !important;
}

.ql-snow a:hover {
    color: #003d82 !important;
}

.dark-editor .ql-snow a {
    color: #60a5fa !important;
}

/* ========== IMAGE STYLES ========== */
.ql-snow img {
    max-width: 100% !important;
    height: auto !important;
    border-radius: 6px !important;
    margin: 8px 0 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
}

.dark-editor .ql-snow img {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
}

/* ========== CODE STYLES ========== */
.ql-snow .ql-code-block {
    background-color: #f3f4f6 !important;
    color: #1f2937 !important;
    padding: 12px !important;
    border-radius: 6px !important;
    border-left: 3px solid #0056b3 !important;
    font-family: 'Monaco', 'Courier New', monospace !important;
    font-size: 12px !important;
    line-height: 1.5 !important;
    overflow-x: auto !important;
}

.dark-editor .ql-code-block {
    background-color: #111827 !important;
    color: #d1d5db !important;
    border-left-color: #3b82f6 !important;
}

.ql-editor code {
    background-color: #f3f4f6 !important;
    color: #dc2626 !important;
    padding: 2px 5px !important;
    border-radius: 3px !important;
    font-family: 'Monaco', 'Courier New', monospace !important;
    font-size: 12px !important;
}

.dark-editor .ql-editor code {
    background-color: #1f2937 !important;
    color: #fca5a5 !important;
}

/* ========== BLOCKQUOTE STYLES ========== */
.ql-snow .ql-quote {
    border-left: 3px solid #0056b3 !important;
    padding-left: 12px !important;
    color: #4b5563 !important;
    font-style: italic !important;
    margin: 8px 0 !important;
    background-color: #f9fafb !important;
    padding: 12px 12px 12px 12px !important;
    border-radius: 4px !important;
}

.dark-editor .ql-quote {
    color: #d1d5db !important;
    background-color: #111827 !important;
    border-left-color: #3b82f6 !important;
}

/* ========== TEXT FORMATTING ========== */
.ql-editor strong {
    font-weight: 700 !important;
}

.ql-editor em {
    font-style: italic !important;
}

.ql-editor u {
    text-decoration: underline !important;
}

.ql-editor s {
    text-decoration: line-through !important;
}

/* ========== SELECTION ========== */
.ql-editor ::selection {
    background-color: rgba(0, 86, 179, 0.2) !important;
}

.dark-editor .ql-editor ::selection {
    background-color: rgba(96, 165, 250, 0.3) !important;
}

/* ========== RESIZE HANDLE ========== */
.resize-handle {
    border-radius: 0 0 0.5rem 0.5rem;
    cursor: ns-resize;
    user-select: none;
    -webkit-user-select: none;
    transition: all 0.2s ease;
}

.resize-handle:hover {
    background-color: #bfdbfe !important;
}

.dark-editor + .resize-handle:hover {
    background-color: #3b82f6 !important;
}

/* ========== ANIMATIONS ========== */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

/* ========== RESPONSIVE DESIGN ========== */
@media (max-width: 768px) {
    .ql-toolbar {
        padding: 8px 10px !important;
        gap: 3px !important;
    }

    .ql-toolbar button {
        width: 30px !important;
        height: 30px !important;
        padding: 3px !important;
    }

    .ql-editor {
        padding: 12px !important;
        font-size: 13px !important;
    }

    .ql-editor h1 {
        font-size: 24px !important;
    }

    .ql-editor h2 {
        font-size: 20px !important;
    }

    .ql-editor h3 {
        font-size: 18px !important;
    }
}

@media (max-width: 480px) {
    .ql-toolbar {
        padding: 6px 8px !important;
        gap: 2px !important;
    }

    .ql-toolbar button {
        width: 28px !important;
        height: 28px !important;
        padding: 2px !important;
    }

    .ql-editor {
        padding: 10px !important;
        font-size: 12px !important;
    }
}
</style>

{{-- Resize Handle Script --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const resizeHandles = document.querySelectorAll('.resize-handle');

    resizeHandles.forEach(handle => {
        let isResizing = false;
        let startY = 0;
        let startHeight = 0;

        handle.addEventListener('mousedown', (e) => {
            isResizing = true;
            startY = e.clientY;
            const editorWrapper = handle.closest('.editor-container-wrapper');
            const editorDiv = editorWrapper?.querySelector('[data-quill-target]');
            startHeight = editorDiv?.offsetHeight || 400;
            document.body.style.cursor = 'ns-resize';
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isResizing) return;

            const diff = e.clientY - startY;
            const newHeight = Math.max(200, startHeight + diff);
            const editorWrapper = handle.closest('.editor-container-wrapper');
            const editorDiv = editorWrapper?.querySelector('[data-quill-target]');

            if (editorDiv) {
                editorDiv.style.minHeight = newHeight + 'px';
                editorWrapper?.style.setProperty('--editor-height', newHeight + 'px');
            }
        });

        document.addEventListener('mouseup', () => {
            isResizing = false;
            document.body.style.cursor = 'default';
        });

        // Touch support
        handle.addEventListener('touchstart', (e) => {
            isResizing = true;
            startY = e.touches[0].clientY;
            const editorWrapper = handle.closest('.editor-container-wrapper');
            const editorDiv = editorWrapper?.querySelector('[data-quill-target]');
            startHeight = editorDiv?.offsetHeight || 400;
        });

        document.addEventListener('touchmove', (e) => {
            if (!isResizing) return;

            const diff = e.touches[0].clientY - startY;
            const newHeight = Math.max(200, startHeight + diff);
            const editorWrapper = handle.closest('.editor-container-wrapper');
            const editorDiv = editorWrapper?.querySelector('[data-quill-target]');

            if (editorDiv) {
                editorDiv.style.minHeight = newHeight + 'px';
            }
        });

        document.addEventListener('touchend', () => {
            isResizing = false;
        });
    });
});
</script>
