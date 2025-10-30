@extends('layouts.admin')

@section('title', 'تعديل المقالة - ' . ($article->title['ar'] ?? 'مقالة'))

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
                <h1 class="text-3xl font-bold gradient-text mb-2">تعديل المقالة</h1>
                <p class="text-gray-600 dark:text-gray-400">{{ $article->title['ar'] ?? $article->title['en'] ?? 'مقالة' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.articles.show', $article->id) }}" target="_blank"
                   class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                    <span>معاينة</span>
                </a>
                <a href="{{ route('admin.articles.index') }}"
                   class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    <span>العودة للقائمة</span>
                </a>
            </div>
        </div>

        {{-- Info Bar --}}
        <div class="glass-dark rounded-xl p-4 flex items-center justify-between text-sm text-gray-300">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-gray-500"></i>
                    <span>تاريخ الإنشاء: {{ $article->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-gray-500"></i>
                    <span>آخر تحديث: {{ $article->updated_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <i data-lucide="eye" class="w-4 h-4 text-gray-500"></i>
                    <span>المشاهدات: {{ number_format($article->views_count ?? 0) }}</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $article->status === 'published' ? 'bg-green-500/20 text-green-400' : ($article->status === 'draft' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-gray-500/20 text-gray-400') }}">
                    {{ $article->status === 'published' ? 'منشور' : ($article->status === 'draft' ? 'مسودة' : 'مؤرشف') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Form Card --}}
    <form action="{{ route('admin.articles.update', $article->id) }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit" class="space-y-6">
        @csrf
        @method('PUT')

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
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $article->slug) }}"
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
                                <option value="{{ $category->id }}" {{ old('category_id', $article->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name['ar'] ?? $category->name['en'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">السلسلة</label>
                        <select name="series_id" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">اختر السلسلة (اختياري)</option>
                            @foreach($series ?? [] as $s)
                                <option value="{{ $s->id }}" {{ old('series_id', $article->series_id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->name['ar'] ?? $s->name['en'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Tags --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الوسوم</label>
                    <select name="tags[]" multiple class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none">
                        @foreach($tags ?? [] as $tag)
                            <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $article->tags->pluck('id')->toArray())) ? 'selected' : '' }}>
                                {{ $tag->name['ar'] ?? $tag->name['en'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Featured Image Upload --}}
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">الصورة المميزة</label>

                    {{-- Current Image Preview --}}
                    @if($article->featured_image)
                        <div class="mb-4 relative inline-block">
                            <img src="{{ $article->featured_image }}" alt="Featured Image" class="w-48 h-32 object-cover rounded-lg border-2 border-white/20">
                            <button type="button" @click="removeCurrentImage" class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    @endif

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
                            <option value="draft" {{ old('status', $article->status) === 'draft' ? 'selected' : '' }}>مسودة</option>
                            <option value="published" {{ old('status', $article->status) === 'published' ? 'selected' : '' }}>منشور</option>
                            <option value="archived" {{ old('status', $article->status) === 'archived' ? 'selected' : '' }}>مؤرشف</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">مقالة مميزة</label>
                        <div class="flex items-center h-[48px]">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="is_featured" value="0">
                                <input type="checkbox" name="is_featured" value="1" class="sr-only peer" {{ old('is_featured', $article->is_featured) ? 'checked' : '' }}>
                                <div class="w-14 h-7 bg-gray-600 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-0.5 after:right-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"></div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">تاريخ النشر</label>
                        <input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500" :class="darkMode ? \'bg-white/10 border border-white/20 text-white\' : \'glass-light-strong text-gray-900\' focus:outline-none">
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
                    :value="old('title', $article->title ?? [])"
                    required
                />

                {{-- Excerpt Field --}}
                <x-forms.multi-lang-input
                    name="excerpt"
                    label="الملخص"
                    type="textarea"
                    :rows="3"
                    :value="old('excerpt', $article->excerpt ?? [])"
                />

                {{-- Content Field --}}
                <x-forms.multi-lang-input
                    name="content"
                    label="المحتوى"
                    type="textarea"
                    :rows="12"
                    :value="old('content', $article->content ?? [])"
                    required
                />
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <button type="button" @click="confirmDelete" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    حذف المقالة
                </button>

                <div class="flex gap-3">
                    <a href="{{ route('admin.articles.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                        <i data-lucide="x" class="w-5 h-5"></i>
                        إلغاء
                    </a>

                    <button type="submit" class="px-8 py-3 gradient-primary text-white rounded-lg font-semibold flex items-center gap-2" x-bind:disabled="isSubmitting">
                        <i data-lucide="save" class="w-5 h-5" x-show="!isSubmitting"></i>
                        <span x-text="isSubmitting ? 'جاري التحديث...' : 'تحديث المقالة'"></span>
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
            this.isSubmitting = true;
            return true;
        },

        confirmDelete() {
            if (!confirm('هل أنت متأكد من حذف هذه المقالة؟ لا يمكن التراجع عن هذا الإجراء.')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('admin.articles.destroy', $article->id) }}';
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        },

        removeCurrentImage() {
            if (!confirm('هل تريد إزالة الصورة الحالية؟')) return;
            // Implement image removal logic
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>
@endpush
