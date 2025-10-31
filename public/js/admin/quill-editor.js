/**
 * Quill Editor Alpine.js Component
 * Initializes Quill WYSIWYG editor with multi-language support
 * This must be loaded BEFORE Alpine.js evaluates x-data directives
 */

function quillEditor(config) {
    return {
        fieldName: config.fieldName,
        language: config.language,
        height: config.height || 400,
        required: config.required || false,
        disabled: config.disabled || false,
        content: config.content || '',

        // UI State
        quill: null,
        isLoading: true,
        isRTL: ['ar', 'ur', 'he'].includes(config.language),
        darkMode: localStorage.getItem('darkMode') !== 'false',
        charCount: 0,
        minChars: 50,
        hasError: false,
        errorMessage: '',

        /**
         * Initialize Quill Editor
         */
        init() {
            const self = this;

            // Simulate loading for CDN
            setTimeout(() => {
                self.initializeQuill();
            }, 300);

            // Watch for dark mode changes
            this.$watch('darkMode', (newVal) => {
                localStorage.setItem('darkMode', newVal);
                this.updateEditorTheme();
            });
        },

        /**
         * Initialize Quill with configuration
         */
        initializeQuill() {
            try {
                const editorOptions = {
                    theme: 'snow',
                    placeholder: this.getPlaceholder(),
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
                    readOnly: this.disabled
                };

                // Initialize Quill
                this.quill = new Quill(this.$refs.editor, editorOptions);

                // Set direction (RTL/LTR)
                if (this.isRTL) {
                    this.quill.root.setAttribute('dir', 'rtl');
                    this.$refs.editor.classList.add('rtl-editor');
                }

                // Set initial content
                if (this.content) {
                    this.quill.root.innerHTML = this.content;
                    this.updateCharCount();
                }

                // Update hidden input on text change
                this.quill.on('text-change', () => {
                    this.updateCharCount();
                    this.$refs.hiddenInput.value = this.getContent();
                });

                // Update theme
                this.updateEditorTheme();

                // Set loading to false
                this.isLoading = false;

            } catch (error) {
                console.error('Failed to initialize Quill editor:', error);
                this.hasError = true;
                this.errorMessage = 'Failed to load editor';
                this.isLoading = false;
            }
        },

        /**
         * Update editor theme based on dark mode
         */
        updateEditorTheme() {
            if (!this.quill) return;

            const toolbar = this.$refs.editor.previousElementSibling?.querySelector('.ql-toolbar');
            const container = this.$refs.editor;

            if (this.darkMode) {
                container?.classList.add('dark-editor');
                toolbar?.classList.add('dark-toolbar');
            } else {
                container?.classList.remove('dark-editor');
                toolbar?.classList.remove('dark-toolbar');
            }
        },

        /**
         * Get editor content as HTML
         */
        getContent() {
            if (!this.quill) return this.content;

            const html = this.quill.root.innerHTML;

            // Remove empty paragraph tags
            if (html === '<p><br></p>' || html === '') {
                return '';
            }

            return html;
        },

        /**
         * Update character count
         */
        updateCharCount() {
            if (!this.quill) return;

            const text = this.quill.getText();
            this.charCount = text.trim().length;

            // Validate minimum chars
            if (this.required && this.charCount < this.minChars) {
                this.hasError = true;
                this.errorMessage = `Minimum ${this.minChars} characters required`;
            } else {
                this.hasError = false;
                this.errorMessage = '';
            }
        },

        /**
         * Get placeholder text based on language
         */
        getPlaceholder() {
            const placeholders = {
                ar: 'اكتب محتوى المقالة هنا...',
                en: 'Write article content here...',
                fr: 'Écrivez le contenu de l\'article ici...',
                tr: 'Makale içeriğini buraya yazın...',
                ur: 'یہاں مضمون کا مواد لکھیں...'
            };

            return placeholders[this.language] || 'Write content here...';
        },

        /**
         * Validate editor content
         */
        validate() {
            this.updateCharCount();

            if (this.required && this.charCount < this.minChars) {
                this.hasError = true;
                return false;
            }

            this.hasError = false;
            return true;
        },

        /**
         * Clear editor content
         */
        clear() {
            if (this.quill) {
                this.quill.setContents([]);
                this.updateCharCount();
            }
        },

        /**
         * Set editor content
         */
        setContent(html) {
            if (this.quill) {
                this.quill.root.innerHTML = html;
                this.updateCharCount();
            }
        },

        /**
         * Focus editor
         */
        focus() {
            if (this.quill) {
                this.quill.focus();
            }
        }
    };
}
