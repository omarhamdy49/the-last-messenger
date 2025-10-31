/**
 * TinyMCE 6 Configuration Module
 * The Last Messenger Admin Dashboard
 *
 * Provides centralized configuration and helper functions for TinyMCE editor instances.
 * Supports multi-language content, RTL/LTR layouts, and dark/light mode themes.
 *
 * @version 1.0.0
 * @author The Last Messenger Development Team
 */

/**
 * Default TinyMCE Configuration
 * Used as base configuration for all editor instances
 */
export const DEFAULT_CONFIG = {
    // Core settings
    license_key: 'gpl', // Free GPL license for development
    promotion: false,
    branding: false,

    // Layout
    height: 500,
    menubar: false,
    statusbar: true,
    resize: true,
    elementpath: false,

    // Plugins
    plugins: [
        'advlist',      // Advanced list options
        'autolink',     // Automatic link detection
        'lists',        // Bullet and numbered lists
        'link',         // Link insertion
        'image',        // Image upload and management
        'charmap',      // Special characters
        'anchor',       // Anchor links
        'searchreplace', // Find and replace
        'visualblocks', // Visual block boundaries
        'code',         // HTML source code editor
        'fullscreen',   // Fullscreen mode
        'insertdatetime', // Date/time insertion
        'media',        // Media embed (video/audio)
        'table',        // Table management
        'help',         // Help documentation
        'wordcount',    // Word and character count
        'directionality', // RTL/LTR text direction
        'paste'         // Enhanced paste functionality
    ],

    // Toolbar configuration (responsive design)
    toolbar: [
        'undo redo | styles | bold italic underline strikethrough | forecolor backcolor',
        'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
        'link image media table | ltr rtl | removeformat code fullscreen help'
    ].join(' | '),

    // Mobile toolbar (simplified for smaller screens)
    toolbar_mode: 'sliding',

    // Block formats
    block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Blockquote=blockquote; Preformatted=pre',

    // Font settings
    font_family_formats: 'Cairo=Cairo, sans-serif; Arial=arial,helvetica,sans-serif; Courier New=courier new,courier,monospace; Georgia=georgia,palatino,serif; Times New Roman=times new roman,times,serif; Verdana=verdana,geneva,sans-serif',
    font_size_formats: '8px 10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 48px',

    // Paste configuration
    paste_as_text: false,
    paste_remove_styles_if_webkit: true,
    paste_merge_formats: true,
    paste_data_images: true,

    // Image settings
    image_advtab: true,
    image_caption: true,
    image_title: true,
    automatic_uploads: true,
    file_picker_types: 'image',

    // Link settings
    link_assume_external_targets: 'https',
    link_title: false,
    target_list: false,
    link_context_toolbar: true,

    // Table settings
    table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
    table_appearance_options: true,
    table_advtab: true,
    table_cell_advtab: true,
    table_row_advtab: true,

    // Content styling
    content_style: `
        body {
            font-family: Cairo, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.8;
            padding: 16px;
        }
        body[dir="rtl"] {
            text-align: right;
        }
        a {
            color: #0056b3;
            text-decoration: underline;
        }
        a:hover {
            color: #218838;
        }
        blockquote {
            border-left: 4px solid #0056b3;
            padding-left: 16px;
            margin: 16px 0;
            font-style: italic;
            background: rgba(0, 86, 179, 0.05);
        }
        body[dir="rtl"] blockquote {
            border-left: none;
            border-right: 4px solid #0056b3;
            padding-left: 0;
            padding-right: 16px;
        }
        pre {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 4px;
            overflow-x: auto;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 16px 0;
        }
        table td, table th {
            border: 1px solid #ddd;
            padding: 8px;
        }
        table th {
            background: #0056b3;
            color: white;
            font-weight: bold;
            text-align: left;
        }
        body[dir="rtl"] table th {
            text-align: right;
        }
        img {
            max-width: 100%;
            height: auto;
        }
    `,

    // Style formats (predefined styles for users)
    style_formats: [
        {
            title: 'Headings',
            items: [
                { title: 'Heading 1', format: 'h1' },
                { title: 'Heading 2', format: 'h2' },
                { title: 'Heading 3', format: 'h3' },
                { title: 'Heading 4', format: 'h4' }
            ]
        },
        {
            title: 'Inline',
            items: [
                { title: 'Bold', format: 'bold' },
                { title: 'Italic', format: 'italic' },
                { title: 'Underline', format: 'underline' },
                { title: 'Strikethrough', format: 'strikethrough' },
                { title: 'Superscript', format: 'superscript' },
                { title: 'Subscript', format: 'subscript' },
                { title: 'Code', format: 'code' }
            ]
        },
        {
            title: 'Blocks',
            items: [
                { title: 'Paragraph', format: 'p' },
                { title: 'Blockquote', format: 'blockquote' },
                { title: 'Div', format: 'div' },
                { title: 'Pre', format: 'pre' }
            ]
        },
        {
            title: 'Alignment',
            items: [
                { title: 'Left', format: 'alignleft' },
                { title: 'Center', format: 'aligncenter' },
                { title: 'Right', format: 'alignright' },
                { title: 'Justify', format: 'alignjustify' }
            ]
        }
    ],

    // Mobile configuration
    mobile: {
        menubar: false,
        toolbar_mode: 'sliding',
        toolbar: 'undo redo | bold italic | alignleft aligncenter alignright | bullist numlist | link image'
    }
};

/**
 * Language configuration mapping
 * Maps application languages to TinyMCE language codes
 */
export const LANGUAGE_MAP = {
    'ar': 'ar',      // Arabic
    'en': 'en',      // English
    'fr': 'fr_FR',   // French
    'tr': 'tr',      // Turkish
    'ur': 'ar'       // Urdu (uses Arabic RTL support)
};

/**
 * RTL Languages
 * Languages that require right-to-left text direction
 */
export const RTL_LANGUAGES = ['ar', 'ur', 'fa', 'he'];

/**
 * Get TinyMCE configuration for specific language
 *
 * @param {string} language - Language code (ar, en, fr, tr, ur)
 * @param {object} customConfig - Additional custom configuration
 * @returns {object} TinyMCE configuration object
 */
export function getConfigForLanguage(language = 'ar', customConfig = {}) {
    const isRtl = RTL_LANGUAGES.includes(language);
    const tinyLanguage = LANGUAGE_MAP[language] || 'en';

    return {
        ...DEFAULT_CONFIG,
        language: tinyLanguage,
        directionality: isRtl ? 'rtl' : 'ltr',
        ...customConfig
    };
}

/**
 * Get dark mode configuration
 *
 * @param {boolean} darkMode - Whether dark mode is enabled
 * @returns {object} Dark mode specific configuration
 */
export function getDarkModeConfig(darkMode = false) {
    return {
        skin: darkMode ? 'oxide-dark' : 'oxide',
        content_css: darkMode ? 'dark' : 'default',
        content_style: DEFAULT_CONFIG.content_style + `
            ${darkMode ? `
                body {
                    background: #1f2937;
                    color: #e5e7eb;
                }
                pre {
                    background: #374151;
                    color: #e5e7eb;
                }
                code {
                    background: #374151;
                    color: #e5e7eb;
                }
                blockquote {
                    background: rgba(0, 86, 179, 0.15);
                    border-color: #64CCC5;
                }
                table td, table th {
                    border-color: #4b5563;
                }
            ` : ''}
        `
    };
}

/**
 * Initialize multiple editor instances for multi-language forms
 *
 * @param {array} editorConfigs - Array of editor configuration objects
 * @param {boolean} darkMode - Whether dark mode is enabled
 * @returns {Promise<array>} Array of initialized editor instances
 */
export async function initMultiLanguageEditors(editorConfigs, darkMode = false) {
    if (typeof tinymce === 'undefined') {
        throw new Error('TinyMCE is not loaded');
    }

    const promises = editorConfigs.map(config => {
        const languageConfig = getConfigForLanguage(config.language);
        const darkModeConfig = getDarkModeConfig(darkMode);

        return tinymce.init({
            selector: config.selector,
            ...languageConfig,
            ...darkModeConfig,
            ...config.customConfig,
            setup: (editor) => {
                // Call custom setup if provided
                if (config.setup && typeof config.setup === 'function') {
                    config.setup(editor);
                }

                // Default setup handlers
                editor.on('init', () => {
                    console.log(`Editor initialized: ${config.selector}`);
                });

                editor.on('change', () => {
                    // Trigger change event for form validation
                    editor.save();
                });
            }
        });
    });

    return Promise.all(promises);
}

/**
 * Extract content from all TinyMCE editors before form submission
 *
 * @returns {object} Object with editor IDs as keys and content as values
 */
export function extractAllEditorContent() {
    if (typeof tinymce === 'undefined') {
        return {};
    }

    const content = {};
    tinymce.editors.forEach(editor => {
        content[editor.id] = editor.getContent();
    });

    return content;
}

/**
 * Validate editor content
 *
 * @param {string} editorId - Editor ID
 * @param {object} rules - Validation rules (minWords, maxWords, required)
 * @returns {object} Validation result { valid: boolean, error: string }
 */
export function validateEditorContent(editorId, rules = {}) {
    if (typeof tinymce === 'undefined') {
        return { valid: false, error: 'TinyMCE not loaded' };
    }

    const editor = tinymce.get(editorId);
    if (!editor) {
        return { valid: false, error: 'Editor not found' };
    }

    const content = editor.getContent({ format: 'text' }).trim();
    const wordCount = editor.plugins.wordcount.body.getWordCount();

    // Required validation
    if (rules.required && content.length === 0) {
        return { valid: false, error: 'Content is required' };
    }

    // Minimum words validation
    if (rules.minWords && wordCount < rules.minWords) {
        return { valid: false, error: `Minimum ${rules.minWords} words required (current: ${wordCount})` };
    }

    // Maximum words validation
    if (rules.maxWords && wordCount > rules.maxWords) {
        return { valid: false, error: `Maximum ${rules.maxWords} words allowed (current: ${wordCount})` };
    }

    return { valid: true, error: null };
}

/**
 * Remove all TinyMCE editor instances
 * Useful for cleanup before navigation or component unmount
 */
export function removeAllEditors() {
    if (typeof tinymce !== 'undefined') {
        tinymce.remove();
    }
}

/**
 * Get editor instance by ID
 *
 * @param {string} editorId - Editor ID
 * @returns {object|null} TinyMCE editor instance or null
 */
export function getEditor(editorId) {
    if (typeof tinymce === 'undefined') {
        return null;
    }

    return tinymce.get(editorId) || null;
}

/**
 * Set editor content by ID
 *
 * @param {string} editorId - Editor ID
 * @param {string} content - HTML content to set
 * @returns {boolean} Success status
 */
export function setEditorContent(editorId, content) {
    const editor = getEditor(editorId);
    if (editor) {
        editor.setContent(content);
        return true;
    }
    return false;
}

/**
 * Get editor content by ID
 *
 * @param {string} editorId - Editor ID
 * @param {string} format - Content format ('html' or 'text')
 * @returns {string} Editor content
 */
export function getEditorContent(editorId, format = 'html') {
    const editor = getEditor(editorId);
    if (editor) {
        return editor.getContent({ format });
    }
    return '';
}

/**
 * Focus editor by ID
 *
 * @param {string} editorId - Editor ID
 * @returns {boolean} Success status
 */
export function focusEditor(editorId) {
    const editor = getEditor(editorId);
    if (editor) {
        editor.focus();
        return true;
    }
    return false;
}

// Export all functions as default module
export default {
    DEFAULT_CONFIG,
    LANGUAGE_MAP,
    RTL_LANGUAGES,
    getConfigForLanguage,
    getDarkModeConfig,
    initMultiLanguageEditors,
    extractAllEditorContent,
    validateEditorContent,
    removeAllEditors,
    getEditor,
    setEditorContent,
    getEditorContent,
    focusEditor
};
