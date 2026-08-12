@props([
    'name',
    'value' => '',
    'locale' => null,
    'dir' => null,
    'height' => '220px',
    'placeholder' => '',
    'toolbar' => 'full',
    'modules' => false,
])

@php
    $rtlLocales = ['ar', 'ur', 'fa', 'he'];
    $direction = $dir
        ?? ($locale
            ? (in_array(strtolower($locale), $rtlLocales, true) ? 'rtl' : 'ltr')
            : (in_array(app()->getLocale(), $rtlLocales, true) ? 'rtl' : 'ltr'));
    $uid = 'wysiwyg_'.\Illuminate\Support\Str::random(10);
@endphp

{{--
    Canonical admin rich-text component. Quill is bundled locally and the
    hidden textarea preserves normal validation, old() values, and form posts.
    The direction defaults to the field locale (or the app locale) while the
    toolbar always lets an editor toggle RTL/LTR for selected paragraphs.
--}}
@once
    @push('styles')
        @vite('resources/js/admin/rich-text.js')
        <style>
            .wysiwyg-field .ql-toolbar.ql-snow { border-radius: 0.5rem 0.5rem 0 0; }
            .wysiwyg-field .ql-container.ql-snow {
                border-radius: 0 0 0.5rem 0.5rem;
                font-family: 'Cairo', sans-serif;
                font-size: 1rem;
            }
            .wysiwyg-field[data-default-dir="rtl"] .ql-editor.ql-blank::before {
                direction: rtl;
                text-align: right;
                left: 15px;
                right: 15px;
            }
            .dark .wysiwyg-field .ql-toolbar.ql-snow,
            .dark .wysiwyg-field .ql-container.ql-snow { border-color: #374151; background-color: #111827; }
            .dark .wysiwyg-field .ql-editor { color: #f3f4f6; }
            .dark .wysiwyg-field .ql-editor.ql-blank::before { color: #9ca3af; }
            .dark .wysiwyg-field .ql-snow .ql-stroke { stroke: #d1d5db; }
            .dark .wysiwyg-field .ql-snow .ql-fill { fill: #d1d5db; }
            .dark .wysiwyg-field .ql-snow .ql-picker { color: #d1d5db; }
            .dark .wysiwyg-field .ql-snow .ql-picker-options { background-color: #1f2937; border-color: #374151; }

    /* Custom module insert toolbar buttons — override Quill forced sizing */
    .ql-toolbar .ql-module-btns {
        display: inline-flex !important; align-items: center; gap: 5px;
        margin-inline-start: 8px; border-inline-start: 1px solid #ccc; padding-inline-start: 10px;
        vertical-align: middle; height: auto;
    }
    .dark .ql-toolbar .ql-module-btns { border-inline-start-color: rgb(75, 85, 99); }
    .ql-snow .ql-toolbar .ql-module-btn,
    .ql-snow.ql-toolbar .ql-module-btn,
    .ql-toolbar .ql-module-btn {
        display: inline-flex !important; align-items: center !important; gap: 5px;
        width: auto !important; height: 26px !important; padding: 0 10px !important;
        border-radius: 5px; box-sizing: border-box;
        font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid transparent;
        transition: all 0.15s; line-height: 1; white-space: nowrap; vertical-align: middle;
        float: none !important;
    }
    .ql-toolbar .ql-module-btn:hover { transform: translateY(-1px); box-shadow: 0 2px 6px rgba(0,0,0,0.12); }
    .ql-toolbar .ql-module-btn svg { width: 15px; height: 15px; flex-shrink: 0; }
    .ql-toolbar .ql-module-btn span { line-height: 1; }
    .ql-module-btn--verse { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
    .ql-module-btn--verse:hover { background: #bbf7d0; }
    .ql-module-btn--hadith { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }
    .ql-module-btn--hadith:hover { background: #bfdbfe; }
    .ql-module-btn--sahabi { background: #fef3c7; color: #b45309; border-color: #fde68a; }
    .ql-module-btn--sahabi:hover { background: #fde68a; }
    .ql-module-btn--seerah { background: #f3e8ff; color: #7e22ce; border-color: #e9d5ff; }
    .ql-module-btn--seerah:hover { background: #e9d5ff; }
    .dark .ql-module-btn--verse { background: rgba(22,163,74,0.2); color: #86efac; border-color: rgba(22,163,74,0.3); }
    .dark .ql-module-btn--hadith { background: rgba(37,99,235,0.2); color: #93c5fd; border-color: rgba(37,99,235,0.3); }
    .dark .ql-module-btn--sahabi { background: rgba(217,119,6,0.2); color: #fcd34d; border-color: rgba(217,119,6,0.3); }
    .dark .ql-module-btn--seerah { background: rgba(147,51,234,0.2); color: #d8b4fe; border-color: rgba(147,51,234,0.3); }

    /* Embedded module styles inside Quill editor */
    .ql-editor .module-embed {
        display: block; margin: 12px 0; padding: 12px 16px; border-radius: 8px;
        border-inline-start: 4px solid; font-size: 14px; position: relative;
    }
    .ql-editor .module-embed[data-module="verse"] {
        background: #f0fdf4; border-color: #22c55e; color: #166534;
    }
    .ql-editor .module-embed[data-module="hadith"] {
        background: #eff6ff; border-color: #3b82f6; color: #1e40af;
    }
    .ql-editor .module-embed[data-module="sahabi"] {
        background: #fffbeb; border-color: #f59e0b; color: #92400e;
    }
    .ql-editor .module-embed[data-module="seerah"] {
        background: #faf5ff; border-color: #a855f7; color: #6b21a8;
    }
    .dark .ql-editor .module-embed[data-module="verse"] { background: rgba(22,163,74,0.1); color: #86efac; }
    .dark .ql-editor .module-embed[data-module="hadith"] { background: rgba(59,130,246,0.1); color: #93c5fd; }
    .dark .ql-editor .module-embed[data-module="sahabi"] { background: rgba(245,158,11,0.1); color: #fcd34d; }
    .dark .ql-editor .module-embed[data-module="seerah"] { background: rgba(168,85,247,0.1); color: #d8b4fe; }
    .ql-editor .module-embed .embed-label {
        display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.05em; opacity: 0.7; margin-bottom: 4px;
    }
    .ql-editor .module-embed .embed-text { display: block; margin-bottom: 4px; line-height: 1.7; }
    .ql-editor .module-embed .embed-link {
        display: inline-flex; align-items: center; gap: 4px;
        font-size: 12px; font-weight: 600; text-decoration: none; opacity: 0.8;
    }
    .ql-editor .module-embed .embed-link:hover { opacity: 1; text-decoration: underline; }
        </style>
    @endpush
@endonce

<div id="{{ $uid }}"
     class="wysiwyg-field"
     data-wysiwyg
     data-default-dir="{{ $direction }}"
     @wysiwyg-set.window="if ($event.detail.name === @js($name)) setHtml($event.detail.html)"
     @module-inline-insert.window="insertModuleEmbed($event.detail)"
     x-data="{
         editor: null,
         editorId: @js($uid),
         savedRange: null,
         sync: null,
         /**
          * Quill's getText() only sees typed characters, so a body that is nothing
          * but an embed (a verse card, an image) reads as empty and used to be
          * blanked on submit. Anything non-text in the DOM counts as content.
          */
         isEmpty() {
             if (! this.editor) { return true; }
             if (this.editor.getText().trim() !== '') { return false; }

             return this.editor.root.querySelector('.module-embed, img, iframe, video') === null;
         },
         setHtml(html) {
             if (! this.editor) { return; }
             this.editor.setContents(this.editor.clipboard.convert({ html: html }));
             // Injected HTML carries no per-line direction, so re-apply the field's.
             window.AdminWysiwyg.applyDefaultDirection(this.editor, @js($direction));
             this.$refs.input.value = this.isEmpty() ? '' : this.editor.root.innerHTML;
         },
         initEditor() {
             const editorEl = this.$refs.editor;
             const input = this.$refs.input;

             if (editorEl.dataset.wysiwygReady) { return; }
             editorEl.dataset.wysiwygReady = '1';

             this.editor = window.AdminWysiwyg.create(editorEl, {
                 value: input.value,
                 locale: @js($locale),
                 direction: @js($direction),
                 placeholder: @js($placeholder),
                 toolbar: @js($toolbar),
             });

             const sync = () => {
                 input.value = this.isEmpty() ? '' : this.editor.root.innerHTML;
             };
             this.editor.on('text-change', sync);
             this.sync = sync;

             const form = input.closest('form');
             if (form) { form.addEventListener('submit', sync); }

             if (@js((bool) $modules)) { this.addModuleButtons(); }
         },

         /**
          * Append the cross-module insert buttons (Quran / hadith / sahabi / seerah)
          * to this editor's Quill toolbar. Selection is captured on mousedown because
          * clicking the toolbar blurs the editor and Quill drops the range.
          */
         addModuleButtons() {
             const editor = this.editor;
             const toolbar = editor.getModule('toolbar').container;
             const group = document.createElement('span');
             group.className = 'ql-formats ql-module-btns';

             window.AdminWysiwygModules.buttons().forEach((mod) => {
                 const btn = document.createElement('button');
                 btn.type = 'button';
                 btn.className = 'ql-module-btn ' + mod.cls;
                 btn.title = mod.label;
                 btn.innerHTML = mod.icon + '<span>' + mod.label + '</span>';
                 btn.addEventListener('mousedown', () => this.captureSelection());
                 btn.addEventListener('click', (e) => {
                     e.preventDefault();
                     e.stopPropagation();
                     this.captureSelection();
                     window.dispatchEvent(new CustomEvent('open-module-selector', {
                         detail: {
                             type: mod.type,
                             locale: @js($locale),
                             insertMode: 'inline',
                             quillBlockId: this.editorId,
                         },
                     }));
                 });
                 group.appendChild(btn);
             });

             toolbar.appendChild(group);
         },

         captureSelection() {
             const range = this.editor.getSelection();
             this.savedRange = range
                 ? { index: range.index, length: range.length }
                 : { index: Math.max(0, this.editor.getLength() - 1), length: 0 };
         },

         /** Insert whatever the shared picker produced, at the saved caret. */
         insertModuleEmbed(detail) {
             if (! this.editor || detail.quillBlockId !== this.editorId) { return; }
             const range = this.savedRange
                 || this.editor.getSelection()
                 || { index: Math.max(0, this.editor.getLength() - 1), length: 0 };
             window.AdminWysiwygModules.insert(
                 this.editor, range, detail.moduleType, detail.data, @js($direction === 'rtl' ? 'ar' : 'en')
             );
             this.savedRange = null;
             this.sync && this.sync();
             this.editor.focus();
         },
         init() {
             if (window.AdminWysiwyg) {
                 this.initEditor();
             } else {
                 window.addEventListener('rich-text:ready', () => this.initEditor(), { once: true });
             }
         }
     }">
    <div x-ref="editor" style="min-height: {{ $height }}"></div>
    <textarea x-ref="input"
              name="{{ $name }}"
              class="hidden"
              aria-hidden="true">{{ $value }}</textarea>
</div>

@if ($modules)
    {{-- Shared cross-module picker; @once inside means one instance per page. --}}
    <x-admin.module-selector-modal />
@endif

@once
@php
    // Multi-line @json(...) breaks Blade's directive-argument parser, so the map is
    // built here and injected as a ready-made JSON literal.
    $moduleLabelsJson = \Illuminate\Support\Js::from([
        'verse' => __('articles.modules.quran'),
        'hadith' => __('articles.modules.hadith'),
        'sahabi' => __('articles.modules.sahabah'),
        'seerah' => __('articles.modules.seerah'),
        'verseWord' => __('articles.blocks.verse'),
        'hijri' => __('articles.blocks.hijri'),
        'viewEvent' => __('articles.embed.view_event'),
    ])->toHtml();
@endphp
@push('scripts')
<script>
    // Labels for the cross-module toolbar buttons and embed cards. Injected from
    // Blade because rich-text.js is plain JS with no access to the translator.
    (function applyModuleLabels() {
        const labels = {!! $moduleLabelsJson !!};
        if (window.AdminWysiwygModules) {
            window.AdminWysiwygModules.setLabels(labels);
        } else {
            window.addEventListener('rich-text:ready', () => window.AdminWysiwygModules.setLabels(labels), { once: true });
        }
    })();
</script>
@endpush
@endonce
