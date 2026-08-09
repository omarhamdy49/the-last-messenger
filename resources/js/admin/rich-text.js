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
        return Object.entries(EMBED_ATTRIBUTES).reduce((carry, [key, attribute]) => {
            const attributeValue = node.getAttribute(attribute);

            if (attributeValue !== null) {
                carry[key] = attributeValue;
            }

            return carry;
        }, {});
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

function applyDefaultDirection(editor, direction) {
    editor.root.dataset.defaultDirection = direction;

    if (direction === 'rtl') {
        editor.formatLine(0, Math.max(editor.getLength(), 1), {
            direction: 'rtl',
            align: 'right',
        }, 'silent');
    }
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

    return editor;
}

window.Quill = Quill;
window.AdminWysiwyg = {
    create,
    resolveDirection,
    toolbars,
};
window.dispatchEvent(new CustomEvent('rich-text:ready'));
