/**
 * Shared admin WYSIWYG runtime (Quill 2, bundled locally — no CDN).
 *
 * Every admin rich-text surface, including the article block builder, uses
 * this factory so toolbar features and locale-aware direction stay aligned.
 */
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

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
