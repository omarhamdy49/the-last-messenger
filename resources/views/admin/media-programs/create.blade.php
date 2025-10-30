@extends('layouts.admin')

@section('title', 'إضافة برنامج إعلامي - محسّن')

@section('content')
<div class="max-w-6xl mx-auto" x-data="mediaProgramForm()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">إضافة برنامج إعلامي جديد</h1>
                <p class="text-gray-600 dark:text-gray-400">إنشاء برنامج إعلامي مع الحلقات</p>
            </div>
            <a href="{{ route('admin.media-programs.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للقائمة</span>
            </a>
        </div>
    </div>

    {{-- Form Card --}}
    <form action="{{ route('admin.media-programs.store') }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit" class="space-y-6">
        @csrf

        {{-- Basic Information Card --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="video" class="w-6 h-6"></i>
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
                           placeholder="program-slug-example" required>
                    @error('slug')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Category & Status Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> التصنيف
                        </label>
                        <select name="category_id" required class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر التصنيف</option>
                            @foreach($categories ?? [] as $category)
                                <option value="{{ $category->id }}">{{ $category->name['ar'] ?? $category->name['en'] }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> الحالة
                        </label>
                        <select name="status" required class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none">
                            <option value="active">نشط</option>
                            <option value="completed">مكتمل</option>
                            <option value="upcoming">قادم</option>
                        </select>
                    </div>
                </div>

                {{-- Thumbnail Upload --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">صورة مصغرة</label>
                    @include('admin.components.file-uploader', [
                        'name' => 'thumbnail',
                        'accept' => 'image/*',
                        'preview' => true
                    ])
                </div>

                {{-- Banner Upload --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">صورة الغلاف</label>
                    @include('admin.components.file-uploader', [
                        'name' => 'banner_image',
                        'accept' => 'image/*',
                        'preview' => true
                    ])
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
                    label="عنوان البرنامج"
                    :value="old('title', [])"
                    required
                />

                {{-- Description Field --}}
                <x-forms.multi-lang-input
                    name="description"
                    label="الوصف"
                    type="textarea"
                    :rows="5"
                    :value="old('description', [])"
                />
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <a href="{{ route('admin.media-programs.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                    إلغاء
                </a>

                <button type="submit" class="px-8 py-3 gradient-primary text-white rounded-lg font-semibold flex items-center gap-2" x-bind:disabled="isSubmitting">
                    <i data-lucide="check-circle" class="w-5 h-5" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? 'جاري الحفظ...' : 'حفظ البرنامج'"></span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function mediaProgramForm() {
    return {
        isSubmitting: false,

        handleSubmit() {
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
