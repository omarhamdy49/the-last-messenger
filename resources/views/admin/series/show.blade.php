@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="{{ route('admin.series.index') }}" class="hover:text-accent transition-colors">السلاسل</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>{{ $series->getTranslation('title', 'ar') }}</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="layers" class="w-7 h-7 text-white"></i>
            </div>
            {{ $series->getTranslation('title', 'ar') }}
        </h1>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.series.edit', $series->id) }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="edit-2" class="w-5 h-5"></i>
            <span>{{ __('admin.edit') }}</span>
        </a>
        <a href="{{ route('admin.series.index') }}"
           class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 border-2"
           :class="darkMode ? 'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' : 'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="arrow-right" class="w-5 h-5"></i>
            <span>رجوع</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Series Header Card -->
        <div class="rounded-2xl border overflow-hidden"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <div class="relative h-48 bg-gradient-to-br from-[#0056b3] to-[#218838]">
                @if($series->thumbnail)
                    <img src="{{ asset('storage/' . $series->thumbnail) }}"
                         alt="{{ $series->getTranslation('title', 'ar') }}"
                         class="w-full h-full object-cover opacity-60">
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                <div class="absolute bottom-4 start-6 end-6">
                    <h2 class="text-2xl font-bold text-white">
                        {{ $series->getTranslation('title', 'ar') }}
                    </h2>
                    @if($series->getTranslation('title', 'en'))
                    <p class="text-white/70 mt-1">
                        {{ $series->getTranslation('title', 'en') }}
                    </p>
                    @endif
                </div>
            </div>

            @if($series->getTranslation('description', 'ar'))
            <div class="p-6">
                <p class="leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    {{ $series->getTranslation('description', 'ar') }}
                </p>
            </div>
            @endif
        </div>

        <!-- Titles in Different Languages -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-6 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="languages" class="w-5 h-5 text-accent"></i>
                العنوان بلغات مختلفة
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach(enabled_content_languages() as $lang => $label)
                    @if($series->getTranslation('title', $lang, false))
                    <div class="p-4 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                        <label class="text-xs font-medium" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                            {{ $label }}
                        </label>
                        <p class="text-lg font-semibold mt-1" :class="darkMode ? 'text-white' : 'text-gray-900'"
                           dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}">
                            {{ $series->getTranslation('title', $lang, false) }}
                        </p>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Articles in Series (assign / unassign / reorder inline) -->
        @php
            $initialArticles = $series->articles->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->locales->where('locale', 'ar')->first()?->title
                    ?? $a->locales->first()?->title
                    ?? __('admin.untitled'),
                'date' => $a->published_at?->format('Y/m/d'),
                'url' => route('admin.articles.show', $a->id),
            ])->values();
        @endphp
        <div class="rounded-2xl border p-4 sm:p-6"
             x-data="seriesArticlesManager(@js($initialArticles))"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-xl font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                مقالات السلسلة
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"
                      x-text="articles.length"></span>
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-accent" x-show="saving" x-cloak></i>
            </h2>

            <!-- Search & add -->
            <div class="relative mb-4">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2"
                       :class="darkMode ? 'text-gray-500' : 'text-gray-400'"></i>
                    <input type="text"
                           x-model="articleSearch"
                           @input.debounce.300ms="searchArticles"
                           @focus="showArticleResults = articleSearchResults.length > 0"
                           @click.away="showArticleResults = false"
                           class="w-full ps-10 pe-4 py-3 rounded-xl border text-sm focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-500' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
                           placeholder="ابحث عن مقال لإضافته...">
                </div>

                <div x-show="showArticleResults && articleSearchResults.length > 0" x-cloak x-transition
                     class="absolute z-50 w-full mt-1 rounded-xl border shadow-lg max-h-60 overflow-y-auto"
                     :class="darkMode ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'">
                    <template x-for="result in articleSearchResults" :key="result.id">
                        <button type="button" @click="addArticle(result)"
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

                <div x-show="articleSearchLoading" x-cloak class="absolute z-50 w-full mt-1 rounded-xl border p-4 text-center"
                     :class="darkMode ? 'bg-gray-800 border-gray-700 text-gray-400' : 'bg-white border-gray-200 text-gray-500'">
                    <i data-lucide="loader-2" class="w-5 h-5 animate-spin inline-block"></i>
                    <span class="ms-2 text-sm">جاري البحث...</span>
                </div>
            </div>

            <!-- List -->
            <div class="space-y-2" x-show="articles.length > 0">
                <template x-for="(article, index) in articles" :key="article.id">
                    <div class="flex items-center gap-2 sm:gap-3 p-3 rounded-xl border transition-all"
                         :class="darkMode ? 'bg-gray-800/50 border-gray-700' : 'bg-gray-50 border-gray-200'">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white"
                              x-text="index + 1"></span>

                        <div class="flex-1 min-w-0">
                            <a :href="article.url" class="font-medium text-sm truncate block hover:text-accent transition-colors"
                               :class="darkMode ? 'text-white' : 'text-gray-900'" x-text="article.title"></a>
                            <p class="text-xs" x-show="article.date" :class="darkMode ? 'text-gray-500' : 'text-gray-400'" x-text="article.date"></p>
                        </div>

                        {{-- Touch-friendly actions: always visible, ≥40px targets on mobile --}}
                        <div class="flex items-center flex-shrink-0">
                            <button type="button" @click="moveArticle(index, -1)" :disabled="index === 0"
                                    class="p-2.5 sm:p-2 rounded-lg transition-all disabled:opacity-25"
                                    :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-500'"
                                    aria-label="{{ __('admin.move_up') }}">
                                <i data-lucide="chevron-up" class="w-5 h-5 sm:w-4 sm:h-4"></i>
                            </button>
                            <button type="button" @click="moveArticle(index, 1)" :disabled="index === articles.length - 1"
                                    class="p-2.5 sm:p-2 rounded-lg transition-all disabled:opacity-25"
                                    :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-500'"
                                    aria-label="{{ __('admin.move_down') }}">
                                <i data-lucide="chevron-down" class="w-5 h-5 sm:w-4 sm:h-4"></i>
                            </button>
                            <button type="button" @click="removeArticle(index)"
                                    class="p-2.5 sm:p-2 rounded-lg transition-all text-red-500"
                                    :class="darkMode ? 'hover:bg-red-900/30' : 'hover:bg-red-50'"
                                    aria-label="{{ __('admin.remove') }}">
                                <i data-lucide="x" class="w-5 h-5 sm:w-4 sm:h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty state -->
            <div x-show="articles.length === 0" x-cloak
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

        <!-- Series Info -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="info" class="w-5 h-5 text-accent"></i>
                معلومات
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">الرابط المختصر</span>
                    <span class="font-mono text-sm" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $series->slug }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">عدد المقالات</span>
                    <span class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $series->total_articles }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">تاريخ الإضافة</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $series->created_at?->format('Y/m/d') }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl" :class="darkMode ? 'bg-gray-800/50' : 'bg-gray-50'">
                    <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">آخر تحديث</span>
                    <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $series->updated_at?->format('Y/m/d') }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="zap" class="w-5 h-5 text-accent"></i>
                إجراءات سريعة
            </h3>

            <div class="space-y-3">
                <a href="{{ route('admin.series.edit', $series->id) }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="edit-2" class="w-5 h-5 text-accent"></i>
                    <span>تعديل السلسلة</span>
                </a>

                <a href="{{ route('admin.series.create') }}"
                   class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all hover:scale-102"
                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-700 text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-900'">
                    <i data-lucide="plus" class="w-5 h-5 text-accent"></i>
                    <span>إضافة سلسلة جديدة</span>
                </a>
            </div>
        </div>

        <!-- Delete -->
        <div class="rounded-2xl border p-6"
             :class="darkMode ? 'bg-gray-900/50 border-red-800/50' : 'bg-white border-red-200'">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2 text-red-500">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                حذف السلسلة
            </h3>

            <p class="text-sm mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                سيتم حذف هذه السلسلة نهائياً. المقالات المرتبطة لن تُحذف ولكن سيتم فك ارتباطها.
            </p>

            <form action="{{ route('admin.series.destroy', $series->id) }}"
                  method="POST"
                  @submit.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => $el.submit() })">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="w-full px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-red-500 hover:bg-red-600 text-white flex items-center justify-center gap-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span>حذف السلسلة</span>
                </button>
            </form>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    function seriesArticlesManager(initialArticles) {
        return {
            articles: initialArticles || [],
            articleSearch: '',
            articleSearchResults: [],
            articleSearchLoading: false,
            showArticleResults: false,
            saving: false,

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
                    const params = new URLSearchParams({ q });
                    this.articles.forEach(a => params.append('exclude[]', a.id));
                    const response = await fetch(`{{ route('admin.articles.search') }}?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();
                    this.articleSearchResults = data.data || [];
                    this.showArticleResults = this.articleSearchResults.length > 0;
                } catch (e) {
                    this.articleSearchResults = [];
                } finally {
                    this.articleSearchLoading = false;
                    this.$nextTick(() => lucide.createIcons());
                }
            },

            // Persist the whole list (assign + unassign + order) after every change.
            async persist(previous) {
                this.saving = true;
                try {
                    const response = await fetch(`{{ route('admin.series.reorder-articles', $series->id) }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            articles: this.articles.map((a, i) => ({ id: a.id, position: i })),
                        }),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) { throw new Error(data.message || ''); }
                    showToast('{{ __('admin.series_updated') }}', 'success');
                } catch (e) {
                    this.articles = previous; // revert on failure
                    showToast('{{ __('admin.operation_failed') }}', 'error');
                } finally {
                    this.saving = false;
                    this.$nextTick(() => lucide.createIcons());
                }
            },

            addArticle(article) {
                if (this.articles.find(a => a.id === article.id)) return;
                const previous = [...this.articles];
                this.articles.push({
                    id: article.id,
                    title: article.title,
                    date: null,
                    url: '{{ route('admin.articles.show', 9999999) }}'.replace('9999999', article.id),
                });
                this.articleSearch = '';
                this.articleSearchResults = [];
                this.showArticleResults = false;
                this.persist(previous);
            },

            removeArticle(index) {
                const article = this.articles[index];
                showConfirm({
                    title: 'إزالة المقال',
                    message: `هل تريد إزالة "${article.title}" من السلسلة؟`,
                    type: 'danger',
                    confirmText: 'إزالة',
                    onConfirm: () => {
                        const previous = [...this.articles];
                        this.articles.splice(index, 1);
                        this.persist(previous);
                    }
                });
            },

            moveArticle(index, direction) {
                const newIndex = index + direction;
                if (newIndex < 0 || newIndex >= this.articles.length) return;
                const previous = [...this.articles];
                [this.articles[index], this.articles[newIndex]] = [this.articles[newIndex], this.articles[index]];
                this.articles = [...this.articles];
                this.persist(previous);
            }
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
