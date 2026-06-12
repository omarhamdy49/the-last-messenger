@props([
    'name',
    'value' => '',
    'locale' => null,
    'dir' => null,
    'placeholder' => '',
    'height' => '220px',
])

@php
    // Direction follows the field's language, not the UI locale.
    $direction = $dir ?? (in_array($locale, ['ar', 'ur'], true) ? 'rtl' : 'ltr');
    $uid = 'richtext_'.\Illuminate\Support\Str::random(10);
@endphp

{{-- Quill 2 (bundled via Vite — resources/js/admin/rich-text.js, no CDN).
     A hidden <textarea> keeps the field `name`, so server-side validation,
     old() repopulation and form posts are unchanged; the editor HTML is
     synced into it on every change. One instance per locale works inside
     the language-tab panels (x-show keeps hidden panels in the DOM). --}}

@once
    @push('styles')
        @vite('resources/js/admin/rich-text.js')
        <style>
            .rich-text-field .ql-toolbar.ql-snow { border-radius: 0.5rem 0.5rem 0 0; }
            .rich-text-field .ql-container.ql-snow { border-radius: 0 0 0.5rem 0.5rem; font-family: 'Cairo', sans-serif; font-size: 1rem; }
            .rich-text-field[dir="rtl"] .ql-editor { direction: rtl; text-align: right; }
            .dark .rich-text-field .ql-toolbar.ql-snow,
            .dark .rich-text-field .ql-container.ql-snow { border-color: #374151; background-color: #111827; }
            .dark .rich-text-field .ql-editor { color: #f3f4f6; }
            .dark .rich-text-field .ql-editor.ql-blank::before { color: #9ca3af; }
            .dark .rich-text-field .ql-snow .ql-stroke { stroke: #d1d5db; }
            .dark .rich-text-field .ql-snow .ql-fill { fill: #d1d5db; }
            .dark .rich-text-field .ql-snow .ql-picker { color: #d1d5db; }
            .dark .rich-text-field .ql-snow .ql-picker-options { background-color: #1f2937; border-color: #374151; }
            @media (prefers-reduced-motion: no-preference) {
                .rich-text-field .ql-container.ql-snow { transition: border-color 0.2s ease; }
            }
        </style>
    @endpush
@endonce

<div id="{{ $uid }}"
     class="rich-text-field"
     dir="{{ $direction }}"
     x-data="{
         initEditor() {
             const editorEl = this.$refs.editor;
             const input = this.$refs.input;

             if (editorEl.dataset.richTextReady) { return; }
             editorEl.dataset.richTextReady = '1';

             const quill = new window.Quill(editorEl, {
                 theme: 'snow',
                 placeholder: @js($placeholder),
                 modules: {
                     toolbar: [
                         [{ header: [2, 3, false] }],
                         ['bold', 'italic'],
                         ['link'],
                         [{ list: 'ordered' }, { list: 'bullet' }],
                         ['blockquote'],
                         ['clean'],
                     ],
                 },
             });

             if (input.value.trim() !== '') {
                 quill.clipboard.dangerouslyPasteHTML(input.value);
             }

             const sync = () => {
                 // An empty Quill doc serializes as '<p><br></p>' — store '' instead.
                 input.value = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
             };
             quill.on('text-change', sync);

             const form = input.closest('form');
             if (form) { form.addEventListener('submit', sync); }
         },
         init() {
             if (window.Quill) {
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
