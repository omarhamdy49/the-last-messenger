@extends('layouts.admin')

@section('title', 'إضافة مقالة جديدة - محسّن')

@push('styles')
<style>
    .tab-button.active {
        background: linear-gradient(135deg, #0056b3 0%, #218838 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 86, 179, 0.3);
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto" x-data="articleForm()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">إضافة مقالة جديدة</h1>
                <p class="text-gray-600 dark:text-gray-400">قم بإنشاء مقالة جديدة متعددة اللغات</p>
            </div>
            <a href="{{ route('admin.articles.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للقائمة</span>
            </a>
        </div>
    </div>

    {{-- Form Card --}}
    <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit" class="space-y-6">
        @csrf

        {{-- Basic Information Card --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="file-text" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                {{-- Slug --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                        <span class="text-red-400">*</span> الرابط الثابت (Slug)
                    </label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                           class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="article-slug-example" required>
                    @error('slug')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category & Series Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">التصنيف</label>
                        <select name="category_id" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر التصنيف</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}">{{ $category->name['ar'] ?? $category->name['en'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">السلسلة</label>
                        <select name="series_id" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر السلسلة (اختياري)</option>
                            @foreach($series ?? [] as $s)
                                <option value="{{ $s->id }}">{{ $s->name['ar'] ?? $s->name['en'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Tags --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الوسوم</label>
                    <select name="tags[]" multiple class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none">
                        @foreach($tags ?? [] as $tag)
                            <option value="{{ $tag->id }}">{{ $tag->name['ar'] ?? $tag->name['en'] }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-400">اضغط Ctrl/Cmd لاختيار عدة وسوم</p>
                </div>

                {{-- Featured Image Upload --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الصورة المميزة</label>
                    @include('admin.components.file-uploader', [
                        'name' => 'featured_image',
                        'accept' => 'image/*',
                        'preview' => true
                    ])
                </div>

                {{-- Status & Featured Row --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> الحالة
                        </label>
                        <select name="status" id="status" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none" required>
                            <option value="draft">مسودة</option>
                            <option value="published">منشور</option>
                            <option value="archived">مؤرشف</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">مقالة مميزة</label>
                        <div class="flex items-center h-[48px]">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_featured" value="0">
                                <input type="checkbox" name="is_featured" value="1" class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:right-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"></div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">تاريخ النشر</label>
                        <input type="datetime-local" name="published_at" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        {{-- Multi-Language Content Card --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="languages" class="w-6 h-6"></i>
                المحتوى متعدد اللغات
            </h2>

            <div class="space-y-6">
                {{-- Title Field --}}
                <x-forms.multi-lang-input
                    name="title"
                    label="العنوان"
                    :value="old('title', [])"
                    required
                />

                {{-- Excerpt Field --}}
                <x-forms.multi-lang-input
                    name="excerpt"
                    label="الملخص"
                    type="textarea"
                    :rows="3"
                    :value="old('excerpt', [])"
                />

                {{-- Content Field --}}
                <x-forms.multi-lang-input
                    name="content"
                    label="المحتوى"
                    type="textarea"
                    :rows="12"
                    :value="old('content', [])"
                    required
                />
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('admin.articles.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                    إلغاء
                </a>

                <div class="flex gap-3">
                    <button type="submit" name="action" value="draft" class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors flex items-center gap-2">
                        <i data-lucide="save" class="w-5 h-5"></i>
                        حفظ كمسودة
                    </button>

                    <button type="submit" name="action" value="publish" class="px-8 py-3 gradient-primary text-white rounded-lg font-semibold flex items-center gap-2" x-bind:disabled="isSubmitting">
                        <i data-lucide="check-circle" class="w-5 h-5" x-show="!isSubmitting"></i>
                        <span x-text="isSubmitting ? 'جاري النشر...' : 'نشر المقالة'"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function articleForm() {
    return {
        isSubmitting: false,

        handleSubmit(event) {
            const clickedButton = event.submitter;
            if (clickedButton?.name === 'action') {
                const statusInput = document.getElementById('status');
                statusInput.value = clickedButton.value === 'draft' ? 'draft' : 'published';
            }
            this.isSubmitting = true;
            return true;
        },

        init() {
            const titleInput = document.querySelector('input[name="title[ar]"]');
            const slugInput = document.getElementById('slug');

            if (titleInput && slugInput && !slugInput.value) {
                titleInput.addEventListener('input', (e) => {
                    slugInput.value = this.generateSlug(e.target.value);
                });
            }

            this.$nextTick(() => lucide.createIcons());
        },

        generateSlug(text) {
            return text.toLowerCase().trim()
                .replace(/[\s_]+/g, '-')
                .replace(/[^\w\-أ-ي]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+|-+$/g, '');
        }
    }
}
</script>
@endpush
