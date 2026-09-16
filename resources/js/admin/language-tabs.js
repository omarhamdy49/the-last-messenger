/**
 * Language Tabs JavaScript Helper
 *
 * Provides additional functionality for language tab management:
 * - Tab switching with keyboard navigation
 * - Validation tracking per language
 * - Auto-save draft per language
 * - Copy content between languages
 * - Translation status tracking
 *
 * @version 1.0.0
 */

class LanguageTabsManager {
    constructor(options = {}) {
        this.container = options.container || document.querySelector('.language-tabs-container');
        this.languages = options.languages || window.enabledContentLocales || ['ar'];
        this.activeLanguage = options.defaultLanguage || 'ar';
        this.autoSaveEnabled = options.autoSave !== false;
        this.autoSaveDelay = options.autoSaveDelay || 3000;
        this.storageKey = options.storageKey || 'language_tabs_draft';

        this.validationRules = {};
        this.completionStatus = {};
        this.autoSaveTimers = {};

        if (this.container) {
            this.init();
        }
    }

    /**
     * Initialize the language tabs manager
     */
    init() {
        this.setupKeyboardNavigation();
        this.setupAutoSave();
        this.setupValidationTracking();
        this.loadDrafts();

        console.log('Language Tabs Manager initialized');
    }

    /**
     * Setup keyboard navigation for tabs
     * Arrow keys to navigate between tabs
     */
    setupKeyboardNavigation() {
        document.addEventListener('keydown', (e) => {
            // Only handle keyboard navigation when not in input fields
            if (e.target.matches('input, textarea, select')) {
                return;
            }

            // Ctrl/Cmd + Left/Right Arrow to switch languages
            if ((e.ctrlKey || e.metaKey) && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
                e.preventDefault();

                const currentIndex = this.languages.indexOf(this.activeLanguage);
                let newIndex;

                if (e.key === 'ArrowLeft') {
                    newIndex = currentIndex > 0 ? currentIndex - 1 : this.languages.length - 1;
                } else {
                    newIndex = currentIndex < this.languages.length - 1 ? currentIndex + 1 : 0;
                }

                const newLanguage = this.languages[newIndex];
                this.switchLanguage(newLanguage);
            }

            // Ctrl/Cmd + S to save draft
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.saveDraft(this.activeLanguage);
            }
        });
    }

    /**
     * Setup auto-save functionality
     */
    setupAutoSave() {
        if (!this.autoSaveEnabled) return;

        this.container.addEventListener('input', (e) => {
            if (e.target.matches('input, textarea, select')) {
                const language = this.getFieldLanguage(e.target);
                if (language) {
                    this.scheduleSave(language);
                }
            }
        });

        // Save on visibility change (tab switch, etc.)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.saveAllDrafts();
            }
        });

        // Save before unload
        window.addEventListener('beforeunload', () => {
            this.saveAllDrafts();
        });
    }

    /**
     * Setup validation tracking
     */
    setupValidationTracking() {
        // Track required fields per language
        this.languages.forEach(lang => {
            const panel = this.container.querySelector(`[data-language="${lang}"]`);
            if (!panel) return;

            const requiredFields = panel.querySelectorAll('[required]');
            this.validationRules[lang] = Array.from(requiredFields).map(field => ({
                name: field.name,
                type: field.type,
                element: field
            }));
        });

        // Listen for form submission attempts
        const form = this.container.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.validateAllLanguages()) {
                    e.preventDefault();
                    this.showValidationSummary();
                }
            });
        }
    }

    /**
     * Switch to a specific language
     */
    switchLanguage(language) {
        if (!this.languages.includes(language)) {
            console.warn(`Language "${language}" not available`);
            return;
        }

        // Save current language draft before switching
        if (this.autoSaveEnabled) {
            this.saveDraft(this.activeLanguage);
        }

        this.activeLanguage = language;

        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('language-changed', {
            detail: { language }
        }));

        console.log(`Switched to language: ${language}`);
    }

    /**
     * Get language from field name
     */
    getFieldLanguage(field) {
        const name = field.name;
        if (!name) return null;

        // Assuming field names end with _<lang> (e.g., title_ar, title_en)
        const match = name.match(/_([a-z]{2})$/);
        return match ? match[1] : null;
    }

    /**
     * Schedule auto-save for a language
     */
    scheduleSave(language) {
        // Clear existing timer
        if (this.autoSaveTimers[language]) {
            clearTimeout(this.autoSaveTimers[language]);
        }

        // Schedule new save
        this.autoSaveTimers[language] = setTimeout(() => {
            this.saveDraft(language);
        }, this.autoSaveDelay);
    }

    /**
     * Save draft for a specific language
     */
    saveDraft(language) {
        const panel = this.container.querySelector(`[data-language="${language}"]`);
        if (!panel) return;

        const data = {};
        const fields = panel.querySelectorAll('input, textarea, select');

        fields.forEach(field => {
            if (field.name && field.value) {
                data[field.name] = field.value;
            }
        });

        // Save to localStorage
        const drafts = this.getDrafts();
        drafts[language] = {
            data,
            timestamp: Date.now()
        };

        localStorage.setItem(this.storageKey, JSON.stringify(drafts));

        // Dispatch event
        window.dispatchEvent(new CustomEvent('draft-saved', {
            detail: { language, data }
        }));

        console.log(`Draft saved for language: ${language}`);
    }

    /**
     * Save drafts for all languages
     */
    saveAllDrafts() {
        this.languages.forEach(lang => {
            this.saveDraft(lang);
        });
    }

    /**
     * Get all drafts from storage
     */
    getDrafts() {
        try {
            const stored = localStorage.getItem(this.storageKey);
            return stored ? JSON.parse(stored) : {};
        } catch (e) {
            console.error('Error loading drafts:', e);
            return {};
        }
    }

    /**
     * Load drafts for all languages
     */
    loadDrafts() {
        const drafts = this.getDrafts();

        Object.keys(drafts).forEach(language => {
            const draft = drafts[language];
            const panel = this.container.querySelector(`[data-language="${language}"]`);

            if (!panel || !draft.data) return;

            // Restore field values
            Object.keys(draft.data).forEach(name => {
                const field = panel.querySelector(`[name="${name}"]`);
                if (field && !field.value) {
                    field.value = draft.data[name];
                }
            });
        });

        console.log('Drafts loaded');
    }

    /**
     * Clear draft for a specific language
     */
    clearDraft(language) {
        const drafts = this.getDrafts();
        delete drafts[language];
        localStorage.setItem(this.storageKey, JSON.stringify(drafts));

        console.log(`Draft cleared for language: ${language}`);
    }

    /**
     * Clear all drafts
     */
    clearAllDrafts() {
        localStorage.removeItem(this.storageKey);
        console.log('All drafts cleared');
    }

    /**
     * Validate a specific language
     */
    validateLanguage(language) {
        const rules = this.validationRules[language];
        if (!rules) return true;

        const errors = [];

        rules.forEach(rule => {
            const field = rule.element;
            if (!field) return;

            // Check required
            if (!field.value || field.value.trim() === '') {
                errors.push({
                    field: rule.name,
                    message: 'This field is required'
                });
            }

            // Check specific types
            if (field.value) {
                if (rule.type === 'email' && !this.isValidEmail(field.value)) {
                    errors.push({
                        field: rule.name,
                        message: 'Invalid email format'
                    });
                }

                if (rule.type === 'url' && !this.isValidUrl(field.value)) {
                    errors.push({
                        field: rule.name,
                        message: 'Invalid URL format'
                    });
                }
            }
        });

        return errors.length === 0 ? true : errors;
    }

    /**
     * Validate all languages
     */
    validateAllLanguages() {
        const allErrors = {};
        let hasErrors = false;

        this.languages.forEach(lang => {
            const result = this.validateLanguage(lang);
            if (result !== true) {
                allErrors[lang] = result;
                hasErrors = true;
            }
        });

        return hasErrors ? allErrors : true;
    }

    /**
     * Show validation summary
     */
    showValidationSummary() {
        const errors = this.validateAllLanguages();

        if (errors === true) return;

        // Find first language with errors
        const firstErrorLang = Object.keys(errors)[0];

        // Switch to that language
        this.switchLanguage(firstErrorLang);

        // Dispatch event for UI to show errors
        window.dispatchEvent(new CustomEvent('language-validation-error', {
            detail: {
                language: firstErrorLang,
                errors: errors[firstErrorLang]
            }
        }));
    }

    /**
     * Calculate completion percentage for a language
     */
    getCompletionPercentage(language) {
        const panel = this.container.querySelector(`[data-language="${language}"]`);
        if (!panel) return 0;

        const requiredFields = panel.querySelectorAll('[required]');
        if (requiredFields.length === 0) return 100;

        let filledCount = 0;
        requiredFields.forEach(field => {
            if (field.value && field.value.trim() !== '') {
                filledCount++;
            }
        });

        return Math.round((filledCount / requiredFields.length) * 100);
    }

    /**
     * Get completion status for all languages
     */
    getAllCompletionStatus() {
        const status = {};
        this.languages.forEach(lang => {
            status[lang] = this.getCompletionPercentage(lang);
        });
        return status;
    }

    /**
     * Copy content from one language to another
     */
    copyFromLanguage(sourceLang, targetLang) {
        const sourcePanel = this.container.querySelector(`[data-language="${sourceLang}"]`);
        const targetPanel = this.container.querySelector(`[data-language="${targetLang}"]`);

        if (!sourcePanel || !targetPanel) {
            console.warn('Source or target panel not found');
            return false;
        }

        const sourceFields = sourcePanel.querySelectorAll('input[type="text"], textarea');
        let copiedCount = 0;

        sourceFields.forEach(sourceField => {
            const name = sourceField.name;
            if (!name) return;

            // Generate target field name
            const baseName = name.replace(`_${sourceLang}`, '');
            const targetName = `${baseName}_${targetLang}`;
            const targetField = targetPanel.querySelector(`[name="${targetName}"]`);

            if (targetField && sourceField.value) {
                targetField.value = sourceField.value;
                targetField.dispatchEvent(new Event('input', { bubbles: true }));
                copiedCount++;
            }
        });

        console.log(`Copied ${copiedCount} fields from ${sourceLang} to ${targetLang}`);

        // Dispatch event
        window.dispatchEvent(new CustomEvent('language-content-copied', {
            detail: {
                source: sourceLang,
                target: targetLang,
                count: copiedCount
            }
        }));

        return true;
    }

    /**
     * Email validation helper
     */
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * URL validation helper
     */
    isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    /**
     * Destroy the manager instance
     */
    destroy() {
        // Clear all timers
        Object.values(this.autoSaveTimers).forEach(timer => {
            clearTimeout(timer);
        });

        // Save one last time
        if (this.autoSaveEnabled) {
            this.saveAllDrafts();
        }

        console.log('Language Tabs Manager destroyed');
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LanguageTabsManager;
}

// Make available globally
window.LanguageTabsManager = LanguageTabsManager;

// Auto-initialize if container exists
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('.language-tabs-container');
    if (container && !window.languageTabsManager) {
        window.languageTabsManager = new LanguageTabsManager({ container });
    }
});
