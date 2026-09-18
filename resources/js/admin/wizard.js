/**
 * Admin form wizard mixin.
 *
 * Spread into a form's Alpine component:
 *
 *   return { ...adminWizard({ steps: ['basics', 'content'], ... }), init() { this.wizardInit(); } }
 *
 * What it owns:
 * - `step`: every step is clickable at any time; the current one is mirrored in the URL
 *   hash (#step-seo) so a reload or a shared link lands on the same step.
 * - `dirty`: set by trusted input/change events inside the form (typing, Quill, checkboxes)
 *   and by markDirty() for Alpine-only list changes. Quick save is disabled while clean.
 * - quickSave(): posts the whole form with fetch and stays on the page. A 422 is mapped back
 *   onto fields (by `name`) and steps (by the enclosing `[data-wizard-step]`). The first quick
 *   save on a create page turns the page into its edit page (action, _method, URL).
 *
 * The regular submit button still does a normal POST + redirect, so the form works without
 * this file and server-side @error rendering keeps working.
 */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// "seo.ar.title" -> "seo[ar][title]"
const toFieldName = (key) => {
    const [head, ...rest] = key.split('.');
    return head + rest.map((part) => `[${part}]`).join('');
};

export function adminWizard(options) {
    const opts = {
        steps: [],
        initialStep: null,
        isEdit: false,
        updateUrlTemplate: null, // e.g. /admin/events/__ID__
        editUrlTemplate: null,
        locales: [],
        langProp: null,          // Alpine prop holding the active content language
        beforeSave: null,        // (this) => void: sync editors into their textareas
        afterSave: null,         // (this, json) => void: reset one-shot fields (uploads, removals)
        stepComplete: null,      // (this, key) => bool
        messages: {},
        ...options,
    };

    const t = (key, fallback) => opts.messages[key] ?? fallback;

    return {
        step: opts.initialStep ?? opts.steps[0],
        wizardSteps: opts.steps,
        visited: {},
        dirty: false,
        saving: false,
        saveState: 'idle', // idle | saving | saved | error
        saveMessage: '',
        lastSavedAt: null,
        clock: Date.now(),
        stepErrors: {},
        errorList: [],
        wizardIsEdit: opts.isEdit,
        wizardRecordId: null,

        wizardInit() {
            const fromHash = (window.location.hash.match(/^#step-([\w-]+)$/) ?? [])[1];
            if (fromHash && opts.steps.includes(fromHash) && !opts.initialStep) {
                this.step = fromHash;
            }
            this.visited[this.step] = true;
            window.addEventListener('hashchange', () => {
                const key = (window.location.hash.match(/^#step-([\w-]+)$/) ?? [])[1];
                if (key && key !== this.step) this.goTo(key);
            });

            const form = this.wizardForm();
            if (!form) return;

            const onEdit = (event) => {
                if (!event.isTrusted) return;
                const target = event.target;
                if (target.closest?.('[data-wizard-ignore]')) return;
                if (target.name || target.isContentEditable || target.closest?.('.ql-editor')) {
                    this.markDirty();
                }
            };
            form.addEventListener('input', onEdit);
            form.addEventListener('change', onEdit);
            // Programmatic edits (Quill API inserts, pickers) announce themselves explicitly.
            form.addEventListener('wizard:dirty', () => this.markDirty());

            window.addEventListener('keydown', (event) => {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    this.quickSave();
                }
            });

            window.addEventListener('beforeunload', (event) => {
                if (this.dirty && !form.dataset.wizardSubmitting) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            });
            form.addEventListener('submit', () => { form.dataset.wizardSubmitting = '1'; });

            setInterval(() => { this.clock = Date.now(); }, 30000);
            this.$watch('step', () => this.$nextTick(() => window.lucide?.createIcons()));
        },

        wizardForm() {
            return this.$root.tagName === 'FORM' ? this.$root : this.$root.closest('form');
        },

        // ── Navigation ─────────────────────────────────────────────
        goTo(key, { focus = null } = {}) {
            if (!opts.steps.includes(key)) return;
            this.step = key;
            this.visited[key] = true;
            history.replaceState(null, '', `${window.location.pathname}${window.location.search}#step-${key}`);
            this.$nextTick(() => {
                this.$root.querySelector('.wz-step[aria-current="step"]')
                    ?.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
                const panel = this.$root.querySelector(`[data-wizard-step="${key}"]`);
                if (focus) {
                    focus.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    focus.focus?.({ preventScroll: true });
                } else if (panel && panel.getBoundingClientRect().top < 0) {
                    panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        },

        stepIndex(key = this.step) { return opts.steps.indexOf(key); },
        isFirstStep() { return this.stepIndex() === 0; },
        isLastStep() { return this.stepIndex() === opts.steps.length - 1; },
        nextStep() { if (!this.isLastStep()) this.goTo(opts.steps[this.stepIndex() + 1]); },
        prevStep() { if (!this.isFirstStep()) this.goTo(opts.steps[this.stepIndex() - 1]); },

        stepErrorCount(key) { return this.stepErrors[key] ?? 0; },

        stepIsComplete(key) {
            return opts.stepComplete ? !!opts.stepComplete(this, key) : !!this.visited[key];
        },

        stepStatus(key) {
            if (this.stepErrorCount(key)) return 'error';
            if (this.step === key) return 'current';
            return this.stepIsComplete(key) ? 'done' : 'idle';
        },

        progress() {
            if (!opts.steps.length) return 0;
            const done = opts.steps.filter((key) => !this.stepErrorCount(key) && this.stepIsComplete(key)).length;
            return done / opts.steps.length;
        },

        // ── Dirty / status ─────────────────────────────────────────
        markDirty() {
            this.dirty = true;
            if (this.saveState !== 'saving') this.saveState = 'idle';
        },

        savedAgo() {
            if (!this.lastSavedAt) return '';
            const seconds = Math.round((this.lastSavedAt - this.clock) / 1000);
            const rtf = new Intl.RelativeTimeFormat(document.documentElement.lang || 'ar', { numeric: 'auto' });
            return Math.abs(seconds) < 60 ? rtf.format(0, 'second') : rtf.format(Math.round(seconds / 60), 'minute');
        },

        statusLabel() {
            void this.clock;
            if (this.saveState === 'saving') return t('saving', 'Saving…');
            if (this.saveState === 'error') return this.saveMessage || t('save_failed', 'Save failed');
            if (this.dirty) return t('unsaved', 'Unsaved changes');
            if (this.lastSavedAt) return `${t('saved', 'Saved')} · ${this.savedAgo()}`;
            return t('no_changes', 'No changes');
        },

        statusTone() {
            if (this.saveState === 'saving') return 'saving';
            if (this.saveState === 'error') return 'error';
            if (this.dirty) return 'dirty';
            return this.lastSavedAt ? 'saved' : 'idle';
        },

        canQuickSave() {
            return !this.saving && (this.dirty || !this.wizardIsEdit);
        },

        // ── Errors ─────────────────────────────────────────────────
        clearFieldErrors() {
            const form = this.wizardForm();
            form?.querySelectorAll('.wz-field-error[data-wizard-error]').forEach((node) => node.remove());
            form?.querySelectorAll('[aria-invalid="true"]').forEach((node) => node.removeAttribute('aria-invalid'));
            this.stepErrors = {};
            this.errorList = [];
        },

        findField(key) {
            const form = this.wizardForm();
            const parts = key.split('.');
            while (parts.length) {
                const name = toFieldName(parts.join('.'));
                const field = form.querySelector(`[name="${CSS.escape(name)}"], [name="${CSS.escape(name)}[]"]`);
                if (field) return field;
                parts.pop();
            }
            return null;
        },

        applyErrors(errors) {
            this.clearFieldErrors();
            const counts = {};
            const list = [];

            Object.entries(errors ?? {}).forEach(([key, messages]) => {
                const message = Array.isArray(messages) ? messages[0] : String(messages);
                const field = this.findField(key);
                const stepKey = field?.closest('[data-wizard-step]')?.dataset.wizardStep ?? null;
                const locale = key.split('.').find((part) => opts.locales.includes(part)) ?? null;

                if (stepKey) counts[stepKey] = (counts[stepKey] ?? 0) + 1;
                list.push({ key, message, step: stepKey, locale, field });

                if (field) {
                    field.setAttribute('aria-invalid', 'true');
                    const anchor = field.closest('.wysiwyg-field, [data-wizard-field]')
                        ?? (field.type === 'hidden' || field.type === 'checkbox' ? field.parentElement : field);
                    const note = document.createElement('p');
                    note.className = 'wz-field-error';
                    note.dataset.wizardError = '1';
                    note.setAttribute('role', 'alert');
                    note.textContent = message;
                    anchor.insertAdjacentElement('afterend', note);
                }
            });

            this.stepErrors = counts;
            this.errorList = list;
            if (list.length) this.revealError(list.find((item) => item.step) ?? list[0]);
        },

        revealError(item) {
            if (item.locale && opts.langProp) this[opts.langProp] = item.locale;
            if (item.step) this.goTo(item.step, { focus: item.field });
        },

        // ── Quick save ─────────────────────────────────────────────
        async quickSave() {
            if (!this.canQuickSave()) return;
            const form = this.wizardForm();
            if (!form) return;

            opts.beforeSave?.(this);
            this.saving = true;
            this.saveState = 'saving';
            this.saveMessage = '';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf(),
                    },
                    credentials: 'same-origin',
                });
                const json = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    this.applyErrors(json.errors);
                    this.saveState = 'error';
                    this.saveMessage = t('fix_errors', 'Fix the highlighted fields');
                    return;
                }
                if (response.status === 419) {
                    this.saveState = 'error';
                    this.saveMessage = t('session_expired', 'Session expired, reload the page');
                    return;
                }
                if (!response.ok) {
                    this.saveState = 'error';
                    this.saveMessage = json.message || t('save_failed', 'Save failed');
                    return;
                }

                this.clearFieldErrors();
                const id = json.data?.id ?? json.data?.data?.id ?? null;
                if (!this.wizardIsEdit && id) this.switchToEdit(form, id);

                form.querySelectorAll('input[type="file"]').forEach((input) => { input.value = ''; });
                window.dispatchEvent(new CustomEvent('wizard-saved', { detail: { id, response: json } }));
                opts.afterSave?.(this, json);

                this.dirty = false;
                this.lastSavedAt = Date.now();
                this.clock = Date.now();
                this.saveState = 'saved';
                window.showToast?.(t('saved_toast', 'Saved'), 'success');
            } catch (error) {
                this.saveState = 'error';
                this.saveMessage = t('network_error', 'Network error, try again');
            } finally {
                this.saving = false;
            }
        },

        switchToEdit(form, id) {
            this.wizardIsEdit = true;
            this.wizardRecordId = id;
            if (opts.updateUrlTemplate) form.action = opts.updateUrlTemplate.replace('__ID__', id);

            if (!form.querySelector('input[name="_method"]')) {
                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'PUT';
                form.prepend(method);
            }
            if (opts.editUrlTemplate) {
                history.replaceState(null, '', `${opts.editUrlTemplate.replace('__ID__', id)}#step-${this.step}`);
            }
        },
    };
}

window.adminWizard = adminWizard;
