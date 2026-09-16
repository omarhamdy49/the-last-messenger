@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.series.index') }}" class="hover:text-accent transition-colors">السلاسل</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <a href="{{ route('admin.series.show', $series->id) }}" class="hover:text-accent transition-colors">{{ $series->getTranslation('title', 'ar') }}</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ __('admin.edit') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="edit-2" class="w-7 h-7 text-white"></i>
            </div>
            تعديل السلسلة: {{ $series->getTranslation('title', 'ar') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.series.show', $series->id) }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="x" class="w-5 h-5"></i>
            <span>{{ __('admin.cancel') }}</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<form action="{{ route('admin.series.update', $series->id) }}" method="POST" enctype="multipart/form-data"
      x-data="seriesForm(@js($series->articles->map(fn($a) => ['id' => $a->id, 'title' => $a->locales->firstWhere('locale', 'ar')?->title ?? $a->locales->first()?->title ?? __('admin.untitled'), 'status' => $a->status])->values()))">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Title & Description (Multi-language) -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                    العنوان والوصف
                </h2>

                <!-- Language Tabs -->
                <div class="flex flex-wrap gap-2 border-b pb-4 mb-6" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    @foreach(enabled_content_languages() as $lang => $label)
                    <button type="button"
                            @click="activeTab = '{{ $lang }}'"
                            class="px-4 py-2 rounded-lg font-medium transition-all"
                            :class="activeTab === '{{ $lang }}' ?
                                'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' :
                                (darkMode ? 'bg-gray-800 text-gray-400 hover:text-white' : 'bg-gray-100 text-gray-600 hover:text-gray-900')">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                <!-- Tab Contents -->
                @foreach(enabled_content_locales() as $lang)
                <div x-show="activeTab === '{{ $lang }}'" class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            العنوان ({{ strtoupper($lang) }})
                            @if($lang === 'ar') <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="text"
                               name="title[{{ $lang }}]"
                               value="{{ old("title.{$lang}", $series->getTranslation('title', $lang, false)) }}"
                               class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                               placeholder="أدخل عنوان السلسلة..."
                               {{ $lang === 'ar' ? 'required' : '' }}>
                        @error("title.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الوصف ({{ strtoupper($lang) }})
                        </label>
                        <textarea name="description[{{ $lang }}]"
                                  rows="4"
                                  class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all resize-none"
                                  :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                  placeholder="أدخل وصف السلسلة...">{{ old("description.{$lang}", $series->getTranslation('description', $lang, false)) }}</textarea>
                        @error("description.{$lang}")
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Details -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                    التفاصيل
                </h2>

                <div>
                    <!-- Slug -->
                    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        الرابط المختصر (Slug)
                    </label>
                    <input type="text"
                           name="slug"
                           value="{{ old('slug', $series->slug) }}"
                           class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                           dir="ltr"
                           placeholder="seerah-series"
                           pattern="[a-z0-9\-]+">
                    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        أحرف إنجليزية صغيرة وأرقام وشرطات فقط
                    </p>
                    @error('slug')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Articles Manager -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                    مقالات السلسلة
                    <span class="text-sm font-normal px-2 py-0.5 rounded-full ms-auto"
                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'"
                          x-text="articles.length + ' مقال'"></span>
                </h2>

                <!-- Search to Add -->
                <div class="relative mb-4">
                    <div class="relative">
                        <i data-lucide="search" class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2"
                           :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                        <input type="text"
                               x-model="articleSearch"
                               @input.debounce.300ms="searchArticles"
                               @focus="showArticleResults = articleSearchResults.length > 0"
                               @click.away="showArticleResults = false"
                               class="w-full ps-10 pe-4 py-2.5 rounded-xl border text-sm focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                               :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                               placeholder="ابحث عن مقال لإضافته...">
                    </div>

                    <!-- Search Results Dropdown -->
                    <div x-show="showArticleResults && articleSearchResults.length > 0"
                         x-cloak
                         x-transition
                         class="absolute z-50 w-full mt-1 rounded-xl border shadow-lg max-h-60 overflow-y-auto"
                         :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">
                        <template x-for="result in articleSearchResults" :key="result.id">
                            <button type="button"
                                    @click="addArticle(result)"
                                    class="w-full text-start px-4 py-3 flex items-center gap-3 transition-colors border-b last:border-b-0"
                                    :class="darkMode ? 'hover:bg-gray-700 border-gray-700' : 'hover:bg-gray-50 border-gray-100'">
                                <i data-lucide="plus-circle" class="w-4 h-4 text-green-500 flex-shrink-0"></i>
                                <span class="flex-1 text-sm truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="result.title"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                                      :class="result.status === 'published' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'"
                                      x-text="result.status === 'published' ? 'منشور' : 'مسودة'"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Loading -->
                    <div x-show="articleSearchLoading" x-cloak class="absolute z-50 w-full mt-1 rounded-xl border p-4 text-center"
                         :class="darkMode ? 'bg-gray-800 border-gray-700 text-gray-400' : 'bg-white border-gray-200 text-gray-500'">
                        <i data-lucide="loader-2" class="w-5 h-5 animate-spin inline-block"></i>
                        <span class="ms-2 text-sm">جاري البحث...</span>
                    </div>
                </div>

                <!-- Articles List -->
                <div class="space-y-2" x-show="articles.length > 0">
                    <template x-for="(article, index) in articles" :key="article.id">
                        <div class="flex items-center gap-2 p-3 rounded-xl border transition-all group"
                             :class="darkMode ? 'bg-gray-800/50 border-gray-700 hover:border-gray-600' : 'bg-gray-50 border-gray-200 hover:border-gray-300'">
                            <!-- Position Number -->
                            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"
                                  x-text="index + 1"></span>

                            <!-- Article Title -->
                            <span class="flex-1 text-sm truncate" :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="article.title"></span>

                            <!-- Hidden input for form submission -->
                            <input type="hidden" :name="'article_ids[' + index + ']'" :value="article.id">

                            <!-- Move Up -->
                            <button type="button" @click="moveArticle(index, -1)"
                                    x-show="index > 0"
                                    class="p-1.5 rounded-lg opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-all"
                                    :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-500'">
                                <i data-lucide="chevron-up" class="w-4 h-4"></i>
                            </button>

                            <!-- Move Down -->
                            <button type="button" @click="moveArticle(index, 1)"
                                    x-show="index < articles.length - 1"
                                    class="p-1.5 rounded-lg opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-all"
                                    :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-500'">
                                <i data-lucide="chevron-down" class="w-4 h-4"></i>
                            </button>

                            <!-- Remove -->
                            <button type="button" @click="removeArticle(index)"
                                    class="p-1.5 rounded-lg opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-all text-red-500"
                                    :class="darkMode ? 'hover:bg-red-900/30' : 'hover:bg-red-50'">
                                <i data-lucide="x" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="articles.length === 0"
                     class="text-center py-8 rounded-xl border-2 border-dashed"
                     :class="darkMode ? 'border-gray-700 text-gray-500' : 'border-gray-200 text-gray-400'">
                    <i data-lucide="file-text" class="w-10 h-10 mx-auto mb-3 opacity-50"></i>
                    <p class="text-sm">لا توجد مقالات في هذه السلسلة</p>
                    <p class="text-xs mt-1">استخدم البحث أعلاه لإضافة مقالات</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Thumbnail -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <x-admin.image-upload
                    name="thumbnail"
                    idName="thumbnail_id"
                    :label="__('articles.fields.thumbnail')"
                    :currentImage="$series->thumbnail ? asset('storage/' . $series->thumbnail) : null"
                    placeholderIcon="layers"
                    maxSize="10MB"
                    formats="JPG, PNG, WebP, GIF"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                />
            </div>

            <!-- Info -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                    معلومات
                </h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد المقالات</span>
                        <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $series->total_articles }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإنشاء</span>
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $series->created_at?->format('Y/m/d') }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر تحديث</span>
                        <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $series->updated_at?->format('Y/m/d') }}</span>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="rounded-2xl border p-6"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <button type="submit"
                        class="w-full px-6 py-3 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg flex items-center justify-center gap-2">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    {{ __('admin.save') }}
                </button>

                <div class="flex gap-3 mt-4">
                    <a href="{{ route('admin.series.show', $series->id) }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        {{ __('admin.cancel') }}
                    </a>
                    <a href="{{ route('admin.series.index') }}"
                       class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-center transition-all hover:scale-105 border"
                       :class="darkMode ? 'border-gray-700 bg-gray-800 hover:bg-gray-700 text-white' : 'border-gray-200 bg-white hover:bg-gray-50 text-gray-900'">
                        القائمة
                    </a>
                </div>
            </div>

        </div>
    </div>
</form>

{{-- Media Picker Modal --}}
<x-admin.media-picker-modal />

@endsection

@push('scripts')
<script>
    function seriesForm(initialArticles) {
        return {
            activeTab: 'ar',
            articles: initialArticles || [],
            articleSearch: '',
            articleSearchResults: [],
            articleSearchLoading: false,
            showArticleResults: false,

            async searchArticles() {
                const q = this.articleSearch.trim();
                if (q.length < 2) {
                    this.articleSearchResults = [];
                    this.showArticleResults = false;
                    return;
                }

                this.articleSearchLoading = true;
                this.showArticleResults = false;

                try {
                    const excludeIds = this.articles.map(a => a.id);
                    const params = new URLSearchParams({ q });
                    excludeIds.forEach(id => params.append('exclude[]', id));

                    const response = await fetch(`{{ route('admin.articles.search') }}?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();
                    this.articleSearchResults = data.data || [];
                    this.showArticleResults = this.articleSearchResults.length > 0;
                } catch (e) {
                    console.error('Article search failed:', e);
                    this.articleSearchResults = [];
                } finally {
                    this.articleSearchLoading = false;
                    this.$nextTick(() => lucide.createIcons());
                }
            },

            addArticle(article) {
                if (this.articles.find(a => a.id === article.id)) return;
                this.articles.push({ id: article.id, title: article.title, status: article.status });
                this.articleSearch = '';
                this.articleSearchResults = [];
                this.showArticleResults = false;
                this.$nextTick(() => lucide.createIcons());
                showToast('تمت إضافة المقال بنجاح', 'success');
            },

            removeArticle(index) {
                const articleTitle = this.articles[index]?.title || '';
                showConfirm({
                    title: 'إزالة المقال',
                    message: `هل تريد إزالة "${articleTitle}" من السلسلة؟`,
                    type: 'danger',
                    confirmText: 'إزالة',
                    onConfirm: () => {
                        this.articles.splice(index, 1);
                        showToast('تمت إزالة المقال من السلسلة', 'success');
                    }
                });
            },

            moveArticle(index, direction) {
                const newIndex = index + direction;
                if (newIndex < 0 || newIndex >= this.articles.length) return;
                const temp = this.articles[index];
                this.articles[index] = this.articles[newIndex];
                this.articles[newIndex] = temp;
                this.articles = [...this.articles];
            }
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
