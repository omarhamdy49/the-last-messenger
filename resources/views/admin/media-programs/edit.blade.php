@extends('layouts.admin')

@section('title', 'تعديل برنامج إعلامي - محسّن')

@section('content')
<div class="max-w-6xl mx-auto" x-data="mediaProgramForm()" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">تعديل برنامج: {{ $program->title['ar'] ?? $program->title['en'] }}</h1>
                <p class="text-gray-600 dark:text-gray-400">تحديث معلومات البرنامج</p>
            </div>
            <a href="{{ route('admin.media-programs.index') }}"
               class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة للقائمة</span>
            </a>
        </div>
    </div>

    {{-- Form Card --}}
    <form action="{{ route('admin.media-programs.update', $program) }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit" class="space-y-6">
        @csrf
        @method('PUT')

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
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $program->slug) }}"
                           class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white placeholder-gray-400\' : \'glass-light-strong text-gray-900 placeholder-gray-500\' focus:outline-none focus:ring-2 focus:ring-blue-500"
                           required>
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
                                <option value="{{ $category->id }}" {{ $program->category_id == $category->id ? 'selected' : '' }}>
                                    {{ $category->name['ar'] ?? $category->name['en'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="text-red-400">*</span> الحالة
                        </label>
                        <select name="status" required class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none">
                            <option value="active" {{ $program->status->value == 'active' ? 'selected' : '' }}>نشط</option>
                            <option value="completed" {{ $program->status->value == 'completed' ? 'selected' : '' }}>مكتمل</option>
                            <option value="upcoming" {{ $program->status->value == 'upcoming' ? 'selected' : '' }}>قادم</option>
                        </select>
                    </div>
                </div>

                {{-- Current Thumbnail --}}
                @if($program->thumbnail)
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الصورة المصغرة الحالية</label>
                    <img src="{{ $program->thumbnail }}" alt="Thumbnail" class="w-48 h-auto rounded-lg">
                </div>
                @endif

                {{-- Thumbnail Upload --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">تحديث الصورة المصغرة</label>
                    @include('admin.components.file-uploader', [
                        'name' => 'thumbnail',
                        'accept' => 'image/*',
                        'preview' => true
                    ])
                </div>

                {{-- Current Banner --}}
                @if($program->banner_image)
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">صورة الغلاف الحالية</label>
                    <img src="{{ $program->banner_image }}" alt="Banner" class="w-full h-auto rounded-lg">
                </div>
                @endif

                {{-- Banner Upload --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">تحديث صورة الغلاف</label>
                    @include('admin.components.file-uploader', [
                        'name' => 'banner_image',
                        'accept' => 'image/*',
                        'preview' => true
                    ])
                </div>

                {{-- Stats Display --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-400 mb-2">عدد الحلقات</label>
                        <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $program->total_episodes }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-400 mb-2">إجمالي المشاهدات</label>
                        <p class="text-white font-semibold px-4 py-3 bg-white/5 rounded-lg">{{ $program->total_views }}</p>
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
                    label="عنوان البرنامج"
                    :value="old('title', $program->title)"
                    required
                />

                {{-- Description Field --}}
                <x-forms.multi-lang-input
                    name="description"
                    label="الوصف"
                    type="textarea"
                    :rows="5"
                    :value="old('description', $program->description)"
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
                    <i data-lucide="save" class="w-5 h-5" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? 'جاري التحديث...' : 'حفظ التغييرات'"></span>
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
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>
@endpush
