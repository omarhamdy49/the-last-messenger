@props([
    'name',
    'value' => '',
    'dir' => 'ltr',
    'height' => '220px',
    'placeholder' => '',
])

@php
    // Unique per instance so the same field can repeat once per language in a loop.
    $uid = 'wysiwyg_'.\Illuminate\Support\Str::random(10);
@endphp

{{-- Open-source Quill (BSD-3) rich editor. A hidden <textarea> keeps the same `name`
     so existing server-side validation/binding is unchanged; Quill HTML is synced into it.
     Assets are injected in JS so the component is portable across any layout (admin/public),
     regardless of which @stack names that layout defines. --}}
<div class="wysiwyg-field" data-wysiwyg>
    <div id="{{ $uid }}_editor"
         class="wysiwyg-editor"
         style="min-height: {{ $height }}"
         dir="{{ $dir }}"></div>
    <textarea name="{{ $name }}" id="{{ $uid }}_input" class="hidden">{{ $value }}</textarea>
</div>

@once
    @push('scripts')
        <script>
            window.__wysiwyg = window.__wysiwyg || {
                cssUrl: 'https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css',
                jsUrl: 'https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js',
                _loading: null,
                ensure: function () {
                    if (this._loading) { return this._loading; }
                    var self = this;
                    this._loading = new Promise(function (resolve) {
                        if (!document.querySelector('link[data-quill]')) {
                            var link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = self.cssUrl;
                            link.setAttribute('data-quill', '1');
                            document.head.appendChild(link);

                            var style = document.createElement('style');
                            style.textContent =
                                ".wysiwyg-field .ql-toolbar.ql-snow{border-radius:.75rem .75rem 0 0}" +
                                ".wysiwyg-field .ql-container.ql-snow{border-radius:0 0 .75rem .75rem;font-family:'Cairo',sans-serif;font-size:1rem}" +
                                ".dark .wysiwyg-field .ql-toolbar.ql-snow,.dark .wysiwyg-field .ql-container.ql-snow{border-color:#374151;background-color:#1f2937}" +
                                ".dark .wysiwyg-field .ql-editor{color:#f3f4f6}" +
                                ".dark .wysiwyg-field .ql-editor.ql-blank::before{color:#9ca3af}" +
                                ".dark .wysiwyg-field .ql-snow .ql-stroke{stroke:#d1d5db}" +
                                ".dark .wysiwyg-field .ql-snow .ql-fill{fill:#d1d5db}" +
                                ".dark .wysiwyg-field .ql-snow .ql-picker{color:#d1d5db}";
                            document.head.appendChild(style);
                        }
                        if (window.Quill) { return resolve(); }
                        var s = document.createElement('script');
                        s.src = self.jsUrl;
                        s.onload = function () { resolve(); };
                        document.head.appendChild(s);
                    });
                    return this._loading;
                },
            };
        </script>
    @endpush
@endonce

@push('scripts')
    <script>
        (function () {
            function boot() {
                var input = document.getElementById('{{ $uid }}_input');
                if (!input || input.dataset.wysiwygReady) { return; }
                input.dataset.wysiwygReady = '1';

                // Same toolbar as the article content-body editor — the shared
                // "full power" configuration for every WYSIWYG in the admin.
                var editor = new Quill('#{{ $uid }}_editor', {
                    theme: 'snow',
                    placeholder: @json($placeholder),
                    modules: {
                        toolbar: [
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
                    },
                });

                if (input.value.trim() !== '') {
                    editor.clipboard.dangerouslyPasteHTML(input.value);
                }

                var sync = function () {
                    // An empty Quill doc serializes as "<p><br></p>"; store '' instead.
                    input.value = (editor.getText().trim() === '') ? '' : editor.root.innerHTML;
                };
                editor.on('text-change', sync);

                var form = input.closest('form');
                if (form) { form.addEventListener('submit', sync); }
            }

            function start() { window.__wysiwyg.ensure().then(boot); }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', start);
            } else {
                start();
            }
        })();
    </script>
@endpush
