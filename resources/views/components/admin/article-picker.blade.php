@props(['article' => null, 'endpoint', 'name' => 'related_article_id'])

@php
    $selected = $article ? (new \App\Http\Resources\ArticleSelectorResource($article))->resolve() : null;
@endphp

<div x-data="articlePicker({{ \Illuminate\Support\Js::from($selected) }}, {{ \Illuminate\Support\Js::from($endpoint) }})">
    <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">المقال المرتبط</label>
    <input type="hidden" name="{{ $name }}" :value="selected?.id ?? ''" value="{{ $article?->id }}">
    <div class="flex items-center gap-2">
        <button type="button" x-ref="trigger" @click="open()" aria-haspopup="dialog" :aria-expanded="isOpen"
                class="flex-1 min-w-0 text-start px-4 py-3 rounded-xl border focus:ring-2 focus:ring-accent"
                :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            <span class="block truncate" x-text="selected ? '#' + selected.id + ' — ' + selected.title : 'اختر مقالاً'"></span>
        </button>
        <button type="button" x-show="selected" x-cloak @click="selected = null"
                class="shrink-0 p-2 text-sm text-red-500 rounded-lg focus:ring-2 focus:ring-accent">إلغاء الربط</button>
    </div>
    <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">ابحث عن مقال بالعنوان أو الرقم لربطه بالحدث.</p>
    @error($name) <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror

    <template x-teleport="body">
        <div x-show="isOpen" x-cloak role="dialog" aria-modal="true" aria-label="اختيار المقال المرتبط"
             @keydown.escape.stop.prevent="close()" @keydown.tab="trapFocus($event)"
             class="fixed inset-0 z-50 flex items-center justify-center p-4" dir="rtl">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close()"></div>
            <div class="relative w-full max-w-2xl max-h-[90vh] flex flex-col rounded-2xl border shadow-2xl overflow-hidden"
                 :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-200 text-gray-900'">
                <div class="flex items-center justify-between p-5 border-b" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <h2 class="text-lg font-bold">اختيار المقال المرتبط</h2>
                    <button type="button" @click="close()" class="px-3 py-2 rounded-lg focus:ring-2 focus:ring-accent">إغلاق</button>
                </div>
                <div class="p-4">
                    <input type="search" x-ref="search" x-model="query" @input="searchChanged()"
                           @keydown.enter.prevent="load(1)" aria-label="البحث بالعنوان أو رقم المقال"
                           placeholder="ابحث بالعنوان أو رقم المقال…" maxlength="200"
                           class="w-full rounded-xl border px-4 py-3 focus:ring-2 focus:ring-accent"
                           :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                </div>
                <div class="flex-1 overflow-y-auto p-4 min-h-[16rem]" :aria-busy="loading" aria-live="polite">
                    <p x-show="loading" class="py-10 text-center">جارٍ تحميل المقالات…</p>
                    <div x-show="error && !loading" class="py-10 text-center" role="alert">
                        <p>تعذر تحميل المقالات. حاول مرة أخرى.</p>
                        <button type="button" @click="load(page)" class="mt-3 px-4 py-2 rounded-lg border">إعادة المحاولة</button>
                    </div>
                    <p x-show="!loading && !error && articles.length === 0" class="py-10 text-center">لا توجد مقالات مطابقة.</p>
                    <ul x-show="!loading && !error" class="space-y-2">
                        <template x-for="item in articles" :key="item.id">
                            <li>
                                <button type="button" @click="choose(item)" :aria-pressed="selected?.id === item.id"
                                        class="w-full text-start p-4 rounded-xl border focus:ring-2 focus:ring-accent"
                                        :class="selected?.id === item.id ? 'border-accent bg-accent/10' : (darkMode ? 'border-gray-700 hover:bg-gray-800' : 'border-gray-200 hover:bg-gray-50')">
                                    <span class="text-sm opacity-60" x-text="'#' + item.id"></span>
                                    <span class="block font-medium" x-text="item.title"></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
                <div class="flex items-center justify-between gap-3 p-4 border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <button type="button" @click="load(page - 1)" :disabled="loading || error || page <= 1"
                            class="px-4 py-2 rounded-lg border disabled:opacity-40">السابق</button>
                    <span class="text-sm" x-text="page + ' / ' + lastPage + ' · ' + total + ' مقال'"></span>
                    <button type="button" @click="load(page + 1)" :disabled="loading || error || page >= lastPage"
                            class="px-4 py-2 rounded-lg border disabled:opacity-40">التالي</button>
                </div>
            </div>
        </div>
    </template>
</div>

@once
@push('scripts')
<script>
    function articlePicker(selected, endpoint) {
        // Keep request handles outside Alpine's reactive proxy.
        let controller = null;
        let timer = null;
        let version = 0;
        let previousOverflow = '';

        return {
            selected, isOpen: false, query: '', articles: [], loading: false, error: false,
            page: 1, lastPage: 1, total: 0,
            open() {
                previousOverflow = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                this.isOpen = true;
                this.query = '';
                this.load(1);
                this.$nextTick(() => this.$refs.search.focus());
            },
            close() {
                this.isOpen = false;
                this.cancel();
                document.body.style.overflow = previousOverflow;
                this.$refs.trigger.focus();
            },
            cancel() {
                clearTimeout(timer);
                controller?.abort();
                version++;
            },
            searchChanged() {
                this.cancel();
                this.loading = true;
                timer = setTimeout(() => this.load(1), 300);
            },
            async load(page) {
                this.cancel();
                if (!this.isOpen) return;
                const requestVersion = version;
                controller = new AbortController();
                this.page = page;
                this.loading = true;
                this.error = false;
                try {
                    const url = new URL(endpoint, window.location.origin);
                    url.searchParams.set('q', this.query.trim());
                    url.searchParams.set('page', page);
                    const response = await fetch(url, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: controller.signal,
                    });
                    if (!response.ok) throw new Error('Article search failed');
                    const payload = await response.json();
                    if (requestVersion !== version) return;
                    this.articles = payload.data;
                    this.page = payload.meta.current_page;
                    this.lastPage = payload.meta.last_page;
                    this.total = payload.meta.total;
                } catch (error) {
                    if (requestVersion === version && error.name !== 'AbortError') {
                        this.error = true;
                        this.articles = [];
                    }
                } finally {
                    if (requestVersion === version) this.loading = false;
                }
            },
            choose(item) {
                this.selected = item;
                this.close();
            },
            trapFocus(event) {
                const controls = [...event.currentTarget.querySelectorAll('button:not([disabled]), input')]
                    .filter(element => element.getClientRects().length);
                const first = controls[0];
                const last = controls[controls.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last?.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first?.focus();
                }
            },
            destroy() {
                this.cancel();
                if (this.isOpen) document.body.style.overflow = previousOverflow;
            },
        };
    }
</script>
@endpush
@endonce
