@props([
    'name' => 'toc',
    'locale' => 'ar',
    'content' => '',
    'values' => [],
    'label' => 'جدول المحتويات',
])

<div x-data="tocManager({
    name: '{{ $name }}[{{ $locale }}]',
    locale: '{{ $locale }}',
    values: {{ json_encode($values) }},
    content: '{{ $content }}'
})" class="toc-manager">

    <!-- Header with Auto-Generate Button -->
    <div class="flex items-center justify-between mb-3">
        <label class="block text-sm font-semibold"
               :class="darkMode ? 'text-white' : 'text-gray-900'">
            {{ $label }}
        </label>

        <button type="button"
                @click="autoGenerateTOC"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                :class="darkMode ?
                    'bg-blue-900/30 text-blue-400 hover:bg-blue-900/50' :
                    'bg-blue-50 text-blue-600 hover:bg-blue-100'">
            <i data-lucide="sparkles" class="w-4 h-4"></i>
            <span>{{ __('articles.fields.auto_generate_toc') }}</span>
        </button>
    </div>

    <!-- TOC Items List -->
    <div class="space-y-2 mb-3">
        <template x-for="(item, index) in items" :key="item.id">
            <div class="flex items-start gap-2 p-3 rounded-lg border transition-all"
                 :class="darkMode ?
                    'bg-gray-800 border-gray-700' :
                    'bg-gray-50 border-gray-200'">

                <!-- Drag Handle -->
                <div class="mt-2 text-gray-400 cursor-move" title="اسحب لإعادة الترتيب">
                    <i data-lucide="grip-vertical" class="w-4 h-4"></i>
                </div>

                <!-- Title Input -->
                <div class="flex-1">
                    <input type="text"
                           :name="`${name}[${index}][title]`"
                           x-model="item.title"
                           placeholder="{{ __('articles.fields.toc_title') }}"
                           class="w-full px-3 py-2 rounded-lg border mb-2 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           :class="darkMode ?
                               'bg-gray-700 border-gray-600 text-white' :
                               'bg-white border-gray-300 text-gray-900'">

                    <!-- Link/Anchor Input -->
                    <div class="relative">
                        <span class="absolute start-3 top-2.5 text-gray-400 text-sm">#</span>
                        <input type="text"
                               :name="`${name}[${index}][link]`"
                               x-model="item.link"
                               placeholder="{{ __('articles.fields.toc_link') }}"
                               class="w-full ps-8 pe-3 py-2 rounded-lg border transition-colors focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               :class="darkMode ?
                                   'bg-gray-700 border-gray-600 text-white' :
                                   'bg-white border-gray-300 text-gray-900'">
                    </div>
                </div>

                <!-- Level Indicator (Visual) -->
                <div class="mt-2 px-2 py-1 rounded text-xs font-semibold"
                     :class="item.level === 2 ?
                        (darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-700') :
                        (darkMode ? 'bg-purple-900/30 text-purple-400' : 'bg-purple-100 text-purple-700')">
                    H<span x-text="item.level"></span>
                </div>

                <!-- Remove Button -->
                <button type="button"
                        @click="removeItem(index)"
                        class="mt-2 p-1.5 rounded-lg transition-colors hover:bg-red-100 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="items.length === 0"
             class="text-center py-6 px-4 rounded-lg border-2 border-dashed"
             :class="darkMode ? 'border-gray-700 text-gray-500' : 'border-gray-300 text-gray-400'">
            <i data-lucide="list" class="w-10 h-10 mx-auto mb-2 opacity-50"></i>
            <p class="text-sm mb-3">لا توجد عناصر في جدول المحتويات</p>
            <p class="text-xs">اضغط على "إنشاء تلقائي" لاستخراج العناوين من المحتوى</p>
        </div>
    </div>

    <!-- Add Manual Item Button -->
    <button type="button"
            @click="addItem"
            class="w-full px-4 py-2 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 border-2 border-dashed"
            :class="darkMode ?
                'border-gray-700 text-gray-400 hover:border-gray-600 hover:text-gray-300' :
                'border-gray-300 text-gray-600 hover:border-gray-400 hover:text-gray-700'">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>{{ __('articles.fields.add_toc_item') }}</span>
    </button>

    <!-- Preview -->
    <div x-show="items.length > 0"
         x-transition
         class="mt-4 p-4 rounded-lg border"
         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-gray-50 border-gray-200'">
        <h4 class="text-sm font-semibold mb-3"
            :class="darkMode ? 'text-white' : 'text-gray-900'">
            معاينة
        </h4>
        <ul class="space-y-2">
            <template x-for="(item, index) in items" :key="item.id">
                <li :class="item.level === 3 ? 'ms-4' : ''"
                    class="flex items-center gap-2 text-sm">
                    <span class="text-gray-400">•</span>
                    <a :href="'#' + item.link"
                       class="hover:text-blue-500 transition-colors"
                       :class="darkMode ? 'text-gray-300' : 'text-gray-700'"
                       x-text="item.title || 'بدون عنوان'"></a>
                </li>
            </template>
        </ul>
    </div>
</div>

{{-- TOC Manager script moved to _form-new.blade.php to prevent loading order issues --}}
@if(false)
@once
@push('scripts')
<script>
// TOC Manager Component (DISABLED - Defined in main form)
if (typeof tocManager === 'undefined') {
    window.tocManager = function(config) {
    return {
        name: config.name,
        locale: config.locale,
        items: [],
        content: config.content || '',
        nextId: 1,

        init() {
            // Initialize with existing values
            if (config.values && config.values.length > 0) {
                this.items = config.values.map(value => ({
                    ...value,
                    id: this.nextId++,
                    level: value.level || 2
                }));
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        },

        addItem() {
            this.items.push({
                id: this.nextId++,
                title: '',
                link: '',
                level: 2
            });

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        autoGenerateTOC() {
            // Get content from Quill editor
            let contentHtml = this.content;

            // Try to get from Quill editor (Quill stores editors globally or we can access via DOM)
            const editorContainer = document.querySelector(`#content_${this.locale}_editor`);
            if (editorContainer && editorContainer.querySelector('.ql-editor')) {
                contentHtml = editorContainer.querySelector('.ql-editor').innerHTML;
            }

            if (!contentHtml) {
                showToast('الرجاء كتابة المحتوى أولاً', 'warning');
                return;
            }

            // Parse HTML to extract h2 and h3 tags
            const parser = new DOMParser();
            const doc = parser.parseFromString(contentHtml, 'text/html');
            const headings = doc.querySelectorAll('h2, h3');

            if (headings.length === 0) {
                showToast('لم يتم العثور على عناوين (H2, H3) في المحتوى', 'warning');
                return;
            }

            // Clear existing items
            this.items = [];

            // Create TOC items from headings
            headings.forEach((heading, index) => {
                const text = heading.textContent.trim();
                const level = parseInt(heading.tagName.substring(1)); // Get 2 or 3 from H2/H3
                const link = this.slugify(text);

                // Add ID to heading if it doesn't have one
                if (!heading.id) {
                    heading.id = link;
                }

                this.items.push({
                    id: this.nextId++,
                    title: text,
                    link: link,
                    level: level
                });
            });

            // Update the content in Quill with IDs added to headings
            if (editorContainer && editorContainer.querySelector('.ql-editor')) {
                editorContainer.querySelector('.ql-editor').innerHTML = doc.body.innerHTML;
                // Also update the hidden textarea
                const textarea = document.getElementById(`content_${this.locale}`);
                if (textarea) {
                    textarea.value = doc.body.innerHTML;
                }
            }

            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });

            // Show success message
            this.showToast('تم إنشاء جدول المحتويات بنجاح! (' + headings.length + ' عنصر)', 'success');
        },

        slugify(text) {
            return text
                .toString()
                .toLowerCase()
                .trim()
                .replace(/\s+/g, '-')           // Replace spaces with -
                .replace(/[^\w\u0600-\u06FF\-]+/g, '')     // Remove all non-word chars except Arabic
                .replace(/\-\-+/g, '-')         // Replace multiple - with single -
                .replace(/^-+/, '')             // Trim - from start of text
                .replace(/-+$/, '');            // Trim - from end of text
        },

        showToast(message, type = 'info') {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 end-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    };
    };
}
</script>
@endpush
@endonce
@endif
