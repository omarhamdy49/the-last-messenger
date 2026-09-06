/**
 * Shared admin WYSIWYG runtime (Quill 2, bundled locally — no CDN).
 *
 * Every admin rich-text surface, including the article block builder, uses
 * this factory so toolbar features and locale-aware direction stay aligned.
 */
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

/**
 * Cross-module embed (Quran verse, hadith, seerah event).
 *
 * These have to be a registered blot, not pasted HTML: Quill's clipboard drops
 * every tag and attribute it does not recognise, so a `<div class="module-embed"
 * data-module data-id>` pasted via dangerouslyPasteHTML used to survive only as
 * bare text plus a link — losing the module/id that ties the article back to the
 * verse or hadith. As an atomic BlockEmbed the payload round-trips through
 * getSemanticHTML(), the DB, and back into the editor on reload.
 */
const BlockEmbed = Quill.import('blots/block/embed');

const EMBED_ATTRIBUTES = {
    module: 'data-module',
    id: 'data-id',
    label: 'data-label',
    text: 'data-text',
    href: 'data-href',
    linkLabel: 'data-link-label',
    reference: 'data-reference',
    dir: 'data-dir',
};

class ModuleEmbed extends BlockEmbed {
    static blotName = 'moduleEmbed';

    static tagName = 'DIV';

    static className = 'module-embed';

    static create(value = {}) {
        const node = super.create();

        Object.entries(EMBED_ATTRIBUTES).forEach(([key, attribute]) => {
            if (value[key] !== undefined && value[key] !== null && value[key] !== '') {
                node.setAttribute(attribute, String(value[key]));
            }
        });

        node.setAttribute('contenteditable', 'false');
        node.setAttribute('dir', value.dir || 'rtl');
        node.dataset.module = value.module || 'verse';

        // Match the class contract ArticleLocale::renderBlocksToHtml() emits for
        // standalone module blocks, so inline embeds and block embeds share one
        // card design on the public page instead of needing a second stylesheet.
        const moduleType = value.module || 'verse';
        node.classList.add(`module-embed--${moduleType}`);

        const label = document.createElement('span');
        label.className = 'module-embed__label';
        label.textContent = value.label || '';
        node.appendChild(label);

        const body = document.createElement('div');
        body.className = 'module-embed__body';
        if (moduleType === 'verse' || moduleType === 'hadith') {
            body.classList.add('module-embed__arabic');
        }
        const paragraph = document.createElement('p');
        paragraph.textContent = value.text || '';
        body.appendChild(paragraph);
        node.appendChild(body);

        const footer = document.createElement('div');
        footer.className = 'module-embed__footer';

        if (value.href) {
            const link = document.createElement('a');
            link.className = 'module-embed__link';
            link.setAttribute('href', value.href);
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
            link.textContent = value.linkLabel || value.reference || value.href;
            footer.appendChild(link);
        } else if (value.reference) {
            const source = document.createElement('span');
            source.className = 'module-embed__source';
            source.textContent = value.reference;
            footer.appendChild(source);
        }

        if (footer.childNodes.length > 0) {
            node.appendChild(footer);
        }

        return node;
    }

    static value(node) {
        const value = Object.entries(EMBED_ATTRIBUTES).reduce((carry, [key, attribute]) => {
            const attributeValue = node.getAttribute(attribute);

            if (attributeValue !== null) {
                carry[key] = attributeValue;
            }

            return carry;
        }, {});

        return { ...ModuleEmbed.valueFromMarkup(node), ...value };
    }

    /**
     * Recover the payload from the rendered card when the data-* attributes are missing.
     *
     * Content saved before the sanitiser allowed the full payload kept only data-module and
     * data-id — everything else was purified away. Rebuilding from attributes alone would
     * then produce an empty card and the next save would persist it, which is exactly how
     * ten verse cards were lost. The visible text is still in the children, so read it back.
     * Attributes win wherever they exist; this only fills the holes.
     */
    static valueFromMarkup(node) {
        const text = (selector) => node.querySelector(selector)?.textContent?.trim() || undefined;
        const link = node.querySelector('.module-embed__footer a');

        return {
            label: text('.module-embed__label'),
            text: text('.module-embed__body'),
            href: link?.getAttribute('href') || undefined,
            linkLabel: link?.textContent?.trim() || undefined,
            reference: text('.module-embed__source'),
            dir: node.getAttribute('dir') || undefined,
        };
    }
}

Quill.register(ModuleEmbed);

/**
 * Rebuild the blot when saved article HTML is loaded back into an editor.
 * Without this matcher the clipboard would flatten the div on the way back in.
 */
function registerEmbedMatcher(editor) {
    editor.clipboard.addMatcher('DIV', (node, delta) => {
        if (!node.classList || !node.classList.contains('module-embed')) {
            return delta;
        }

        const Delta = Quill.import('delta');

        return new Delta().insert({ moduleEmbed: ModuleEmbed.value(node) });
    });
}

const toolbars = {
    full: [
        [{ header: [1, 2, 3, 4, 5, 6, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ color: [] }, { background: [] }],
        [{ align: [] }, { direction: 'rtl' }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        [{ indent: '-1' }, { indent: '+1' }],
        ['blockquote', 'code-block'],
        ['link', 'image'],
        ['clean'],
    ],
    compact: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ align: [] }, { direction: 'rtl' }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['blockquote', 'link'],
        ['clean'],
    ],
    minimal: [
        ['bold', 'italic'],
        [{ align: [] }, { direction: 'rtl' }],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link', 'clean'],
    ],
};

function resolveDirection({ direction, locale } = {}) {
    if (direction === 'rtl' || direction === 'ltr') {
        return direction;
    }

    if (locale) {
        return ['ar', 'ur', 'fa', 'he'].includes(String(locale).toLowerCase()) ? 'rtl' : 'ltr';
    }

    return document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr';
}

/**
 * Apply the field's reading direction to every line.
 *
 * Quill stores direction/alignment per line, so content that arrives already
 * built — an edit form loading stored HTML, or a programmatic setHtml — has no
 * direction classes and renders left-aligned inside an RTL field. Re-applying
 * after any bulk content change keeps Arabic right-aligned and Latin
 * left-aligned without touching what the editor typed by hand.
 */
function applyDefaultDirection(editor, direction) {
    editor.root.dataset.defaultDirection = direction;

    const length = Math.max(editor.getLength(), 1);

    editor.formatLine(0, length, {
        direction: direction === 'rtl' ? 'rtl' : false,
        align: direction === 'rtl' ? 'right' : false,
    }, 'silent');
}

/**
 * Keep pasted and injected content on the field's direction.
 *
 * `applyDefaultDirection` only runs at init. Typed text is fine because Quill
 * carries the previous line's format onto the next one, but a paste (or any
 * programmatic insert) creates lines with **no** direction format at all, which
 * render left-aligned inside an `dir="rtl"` editor — Arabic content laid out
 * LTR. Re-apply whenever a change introduces new lines; single keystrokes never
 * do, so this costs nothing while typing.
 *
 * Only RTL fields are normalised: LTR is the CSS default, so an LTR field needs
 * no per-line format and re-applying there would strip an editor's deliberate
 * RTL paragraph inside an English body.
 */
function keepDirectionOnPaste(editor, direction) {
    if (direction !== 'rtl') { return; }

    let queued = false;

    editor.on('text-change', (delta) => {
        const addsLines = (delta.ops || []).some(
            (op) => typeof op.insert === 'string' && op.insert.includes('\n')
        );

        if (! addsLines || queued) { return; }

        // Defer: Quill is mid-update, and formatLine during text-change fights it.
        queued = true;
        setTimeout(() => {
            queued = false;
            const selection = editor.getSelection();
            applyDefaultDirection(editor, direction);
            if (selection) { editor.setSelection(selection, 'silent'); }
        }, 0);
    });
}

function create(element, options = {}) {
    const direction = resolveDirection(options);
    const toolbar = Array.isArray(options.toolbar)
        ? options.toolbar
        : (toolbars[options.toolbar || 'full'] || toolbars.full);

    const editor = new Quill(element, {
        theme: 'snow',
        placeholder: options.placeholder || '',
        modules: { toolbar },
    });

    registerEmbedMatcher(editor);

    // The editing surface follows the content locale, not the admin chrome, so
    // an English article is authored left-to-right inside the Arabic panel.
    editor.root.setAttribute('dir', direction);

    if (options.value && String(options.value).trim() !== '') {
        editor.clipboard.dangerouslyPasteHTML(String(options.value));
    }

    applyDefaultDirection(editor, direction);
    keepDirectionOnPaste(editor, direction);

    return editor;
}

/**
 * Cross-module insert support (Quran verse / hadith / sahabi / seerah event).
 *
 * Lives here rather than in a single form so every screen using
 * <x-admin.form.wysiwyg modules> gets the same buttons and the same embed
 * payload. The labels are injected once from Blade (see setLabels) because this
 * file is plain JS with no access to the translator.
 */
const MODULE_ICONS = {
    verse: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    hadith: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/></svg>',
    sahabi: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    seerah: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
};

// Overwritten from Blade so the buttons and embed cards read in the admin locale.
let moduleLabels = {
    verse: 'Quran', hadith: 'Hadith', sahabi: 'Companion', seerah: 'Seerah',
    verseWord: 'Verse', hijri: 'AH', viewEvent: 'View event',
};

const RTL_LOCALES = ['ar', 'ur', 'fa', 'he'];

const AdminWysiwygModules = {
    setLabels(labels) {
        moduleLabels = { ...moduleLabels, ...labels };
    },

    buttons() {
        return ['verse', 'hadith', 'sahabi', 'seerah'].map((type) => ({
            type,
            label: moduleLabels[type],
            cls: `ql-module-btn--${type}`,
            icon: MODULE_ICONS[type],
        }));
    },

    /**
     * Build the structured moduleEmbed payload. Returns null for `sahabi`, which
     * reads better as an inline link inside the sentence than as a card — see
     * insertInline().
     */
    toEmbedValue(moduleType, data, locale = 'ar') {
        const dir = RTL_LOCALES.includes(locale) ? 'rtl' : 'ltr';

        if (moduleType === 'verse') {
            const verses = data.verses || [];
            const range = verses.length > 1
                ? `${verses[0].number}-${verses[verses.length - 1].number}`
                : String(verses[0]?.number ?? '');
            const reference = `${data.surah_name} — ${moduleLabels.verseWord} ${range}`;

            return {
                module: 'verse',
                id: data.surah_id,
                label: moduleLabels.verse,
                text: verses.map((v) => v.text).join(' '),
                reference,
                href: `/quran/surah/${data.surah_id}#${verses[0]?.number ?? ''}`,
                linkLabel: `📖 ${reference}`,
                dir: 'rtl',
            };
        }

        if (moduleType === 'hadith') {
            // The public route is /hadith/{collection}:{referenceKey}, not an id.
            // `/hadith/123` matches the *collection* route with slug "123" and 404s,
            // so fall back to the hadith hub rather than emit a dead link.
            const ref = data.collection_slug && data.reference_key
                ? `/hadith/${data.collection_slug}:${data.reference_key}`
                : '/hadith';

            return {
                module: 'hadith',
                id: data.hadith_id,
                label: moduleLabels.hadith,
                text: data.text,
                reference: `${data.collection} #${data.number}`,
                href: ref,
                linkLabel: `📜 ${data.collection} #${data.number}`,
                dir,
            };
        }

        if (moduleType === 'seerah') {
            return {
                module: 'seerah',
                id: data.event_id,
                label: moduleLabels.seerah,
                text: `${data.title}${data.hijri_year ? ` — ${data.hijri_year} ${moduleLabels.hijri}` : ''}`,
                href: `/seerah/event/${data.slug || data.event_id}`,
                linkLabel: `📍 ${moduleLabels.viewEvent}`,
                dir,
            };
        }

        return null;
    },

    /**
     * Insert a picked module at `range` in `editor`.
     *
     * Companions become a link on the selected text (or an inserted name), so the
     * prose keeps reading as a sentence; everything else becomes an atomic card
     * followed by an empty line so the caret is not trapped against the embed.
     */
    insert(editor, range, moduleType, data, locale = 'ar') {
        if (moduleType === 'sahabi') {
            const link = `/sahaba/${encodeURIComponent(data.slug || data.sahabi_id)}`;

            if (range.length > 0) {
                editor.formatText(range.index, range.length, 'link', link, 'user');
                editor.setSelection(range.index, range.length, 'silent');
                return;
            }

            const label = data.name || '';
            const previousCharacter = range.index > 0 ? editor.getText(range.index - 1, 1) : '';
            const leadingSeparator = previousCharacter && !/\s/.test(previousCharacter) ? ' ' : '';
            const labelIndex = range.index + leadingSeparator.length;

            if (leadingSeparator) {
                editor.insertText(range.index, leadingSeparator, { link: false }, 'user');
            }
            editor.insertText(labelIndex, label, { link }, 'user');
            editor.insertText(labelIndex + label.length, ' ', { link: false }, 'user');
            editor.setSelection(labelIndex + label.length + 1, 0, 'silent');
            editor.format('link', false, 'silent');
            return;
        }

        const embed = this.toEmbedValue(moduleType, data, locale);
        if (!embed) { return; }

        if (range.length > 0) {
            editor.deleteText(range.index, range.length, 'user');
        }

        editor.insertEmbed(range.index, 'moduleEmbed', embed, 'user');
        editor.insertText(range.index + 1, '\n', 'user');
        editor.setSelection(range.index + 2, 0, 'silent');
    },
};

window.Quill = Quill;
window.AdminWysiwyg = {
    create,
    resolveDirection,
    applyDefaultDirection,
    toolbars,
};
window.AdminWysiwygModules = AdminWysiwygModules;
window.dispatchEvent(new CustomEvent('rich-text:ready'));
