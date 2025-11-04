/**
 * WYSIWYG Editor Initialization (CKEditor 5)
 * Generic editor that can be used across all admin forms
 */

class WYSIWYGEditor {
    constructor() {
        this.editors = new Map();
        this.initialized = false;
    }

    /**
     * Initialize all editors on the page
     */
    init() {
        if (this.initialized) return;

        // Wait for CKEditor to be available
        if (typeof ClassicEditor === 'undefined') {
            console.error('CKEditor is not loaded');
            return;
        }

        const editorWrappers = document.querySelectorAll('.ckeditor-wrapper');
        editorWrappers.forEach(wrapper => {
            this.initializeEditor(wrapper);
        });

        this.initialized = true;
    }

    /**
     * Initialize a single editor instance
     */
    async initializeEditor(wrapper) {
        const editorElement = wrapper.querySelector('.ckeditor-instance');
        if (!editorElement) return;

        const editorId = editorElement.id;
        const language = editorElement.dataset.language || 'ar';
        const height = editorElement.dataset.height || 400;
        const isRTL = editorElement.dataset.rtl === 'true';
        const placeholder = editorElement.dataset.placeholder || 'Write content here...';
        const hiddenInput = document.getElementById(editorId + '_input');
        const charCounter = document.getElementById(editorId + '_count');

        try {
            // CKEditor 5 Configuration
            const editor = await ClassicEditor.create(editorElement, {
                toolbar: {
                    items: [
                        'heading',
                        '|',
                        'bold',
                        'italic',
                        'underline',
                        'strikethrough',
                        '|',
                        'fontSize',
                        'fontFamily',
                        'fontColor',
                        'fontBackgroundColor',
                        '|',
                        'alignment',
                        '|',
                        'numberedList',
                        'bulletedList',
                        '|',
                        'outdent',
                        'indent',
                        '|',
                        'link',
                        'imageInsert',
                        'blockQuote',
                        'insertTable',
                        'mediaEmbed',
                        '|',
                        'undo',
                        'redo',
                        '|',
                        'sourceEditing'
                    ],
                    shouldNotGroupWhenFull: true
                },
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                        { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' },
                        { model: 'heading5', view: 'h5', title: 'Heading 5', class: 'ck-heading_heading5' },
                        { model: 'heading6', view: 'h6', title: 'Heading 6', class: 'ck-heading_heading6' }
                    ]
                },
                fontSize: {
                    options: [
                        'tiny',
                        'small',
                        'default',
                        'big',
                        'huge'
                    ]
                },
                fontFamily: {
                    options: [
                        'default',
                        'Cairo, sans-serif',
                        'Arial, sans-serif',
                        'Courier New, monospace',
                        'Georgia, serif',
                        'Times New Roman, serif',
                        'Verdana, sans-serif'
                    ],
                    supportAllValues: true
                },
                link: {
                    decorators: {
                        openInNewTab: {
                            mode: 'manual',
                            label: 'Open in a new tab',
                            attributes: {
                                target: '_blank',
                                rel: 'noopener noreferrer'
                            }
                        }
                    }
                },
                image: {
                    toolbar: [
                        'imageTextAlternative',
                        'imageStyle:inline',
                        'imageStyle:block',
                        'imageStyle:side',
                        'linkImage'
                    ]
                },
                table: {
                    contentToolbar: [
                        'tableColumn',
                        'tableRow',
                        'mergeTableCells',
                        'tableCellProperties',
                        'tableProperties'
                    ]
                },
                placeholder: placeholder,
                language: this.getCKEditorLanguage(language),
                initialData: hiddenInput ? hiddenInput.value : ''
            });

            // Set editor height
            editor.editing.view.change((writer) => {
                writer.setStyle('min-height', height + 'px', editor.editing.view.document.getRoot());
            });

            // Set RTL direction if needed
            if (isRTL) {
                editor.editing.view.change((writer) => {
                    const root = editor.editing.view.document.getRoot();
                    writer.setAttribute('dir', 'rtl', root);
                });
            }

            // Update hidden input on content change
            editor.model.document.on('change:data', () => {
                const data = editor.getData();
                if (hiddenInput) {
                    hiddenInput.value = data;
                }

                // Update character counter
                if (charCounter) {
                    const text = this.stripHtml(data);
                    charCounter.textContent = `${text.length} characters`;
                }

                // Trigger validation
                this.triggerValidation(editorId);
            });

            // Store editor instance
            this.editors.set(editorId, editor);
        } catch (error) {
            console.error('Failed to initialize CKEditor:', error);
        }
    }

    /**
     * Strip HTML tags from content
     */
    stripHtml(html) {
        const tmp = document.createElement('DIV');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }

    /**
     * Get CKEditor language code
     */
    getCKEditorLanguage(language) {
        const langMap = {
            'ar': 'ar',
            'en': 'en',
            'tr': 'tr',
            'ur': 'en' // CKEditor doesn't have Urdu, use English
        };
        return langMap[language] || 'en';
    }

    /**
     * Trigger validation for Alpine.js components
     */
    triggerValidation(editorId) {
        const event = new CustomEvent('editor-change', {
            detail: { editorId }
        });
        document.dispatchEvent(event);
    }

    /**
     * Get editor instance by ID
     */
    getEditor(editorId) {
        return this.editors.get(editorId);
    }

    /**
     * Save all editors to their hidden inputs
     */
    saveAll() {
        this.editors.forEach((editor, editorId) => {
            const hiddenInput = document.getElementById(editorId + '_input');
            if (hiddenInput) {
                hiddenInput.value = editor.getData();
            }
        });
    }

    /**
     * Destroy a specific editor
     */
    async destroyEditor(editorId) {
        const editor = this.editors.get(editorId);
        if (editor) {
            await editor.destroy();
            this.editors.delete(editorId);
        }
    }

    /**
     * Destroy all editors
     */
    async destroyAll() {
        const destroyPromises = [];
        this.editors.forEach((editor) => {
            destroyPromises.push(editor.destroy());
        });
        await Promise.all(destroyPromises);
        this.editors.clear();
        this.initialized = false;
    }

    /**
     * Reinitialize editors (useful for dynamic content)
     */
    async reinit() {
        await this.destroyAll();
        this.init();
    }
}

// Create global instance
window.WYSIWYGEditor = new WYSIWYGEditor();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => window.WYSIWYGEditor.init(), 100);
    });
} else {
    setTimeout(() => window.WYSIWYGEditor.init(), 100);
}

// Save all editors before form submission
document.addEventListener('submit', (e) => {
    if (e.target.tagName === 'FORM') {
        window.WYSIWYGEditor.saveAll();
    }
}, true);

