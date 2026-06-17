@extends('layouts.admin')

@section('content')
@php($rtl = in_array(app()->getLocale(), ['ar', 'ur']))
@php($flag = fn (?string $cc): string => $cc && preg_match('/^[A-Za-z]{2}$/', $cc)
    ? implode('', array_map(fn ($c) => mb_chr(127397 + ord($c)), str_split(strtoupper($cc))))
    : '')
<div x-data="aiInquiriesPage()">
    <nav class="mb-6 flex items-center gap-2 text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="{{ $rtl ? 'chevron-left' : 'chevron-right' }}" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ __('admin.quran_ai_inquiries') }}</span>
    </nav>

    <div class="mb-6">
        <h1 class="text-3xl font-bold gradient-text mb-2">{{ __('admin.quran_ai_inquiries') }}</h1>
        <p class="text-sm" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.quran_ai_inquiries_hint') }}</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg px-4 py-3 text-sm bg-green-100 text-green-800 border border-green-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg px-4 py-3 text-sm bg-red-100 text-red-800 border border-red-200">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <input type="text" name="q" value="{{ $filters['search'] }}" placeholder="{{ __('admin.search') }}"
               class="text-base sm:text-sm rounded-lg border px-3 py-2 flex-1 min-w-[200px]"
               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300'">
        <select name="source" class="text-base sm:text-sm rounded-lg border px-3 py-2"
                :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300'">
            <option value="">{{ __('admin.quran_ai_source') }}</option>
            <option value="llm" @selected($filters['source'] === 'llm')>{{ __('admin.quran_ai_source_llm') }}</option>
            <option value="cache" @selected($filters['source'] === 'cache')>{{ __('admin.quran_ai_source_cache') }}</option>
        </select>
        <select name="published" class="text-base sm:text-sm rounded-lg border px-3 py-2"
                :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300'">
            <option value="">{{ __('admin.quran_ai_published') }}</option>
            <option value="1" @selected($filters['published'] === '1')>{{ __('admin.yes') }}</option>
            <option value="0" @selected($filters['published'] === '0')>{{ __('admin.no') }}</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            {{ __('admin.search') }}
        </button>
    </form>

    <div class="rounded-2xl overflow-hidden shadow-xl border" :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
        @if ($inquiries->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="message-circle-question" class="w-10 h-10 mx-auto mb-3 text-accent"></i>
                <p class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-600'">{{ __('admin.quran_ai_inquiries_empty') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-50 text-gray-600'">
                        <tr class="text-{{ $rtl ? 'right' : 'left' }}">
                            <th class="px-4 py-3 font-semibold">#</th>
                            <th class="px-4 py-3 font-semibold">{{ __('admin.quran_ai_question') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('admin.quran_ai_user') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('admin.quran_ai_country') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('admin.quran_ai_source') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('admin.quran_ai_cost') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('admin.quran_ai_published') }}</th>
                            <th class="px-4 py-3 font-semibold">{{ __('admin.quran_ai_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($inquiries as $row)
                            @php($country = $row->geo['country'] ?? null)
                            <tr class="border-t" :class="darkMode ? 'border-gray-800 text-gray-200' : 'border-gray-100 text-gray-700'">
                                <td class="px-4 py-3">{{ $row->id }}</td>
                                <td class="px-4 py-3 max-w-xs truncate" title="{{ $row->question }}">{{ \Illuminate\Support\Str::limit($row->question, 60) }}</td>
                                <td class="px-4 py-3">
                                    @if ($row->user)
                                        <a href="{{ route('admin.users.show', $row->user_id) }}"
                                           class="text-accent font-semibold hover:underline">{{ $row->user->name }}</a>
                                    @else — @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($country)
                                        <span class="inline-flex items-center gap-1.5"><span>{{ $flag($country) }}</span><span>{{ $country }}</span></span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $row->source === 'cache' ? __('admin.quran_ai_source_cache') : __('admin.quran_ai_source_llm') }}</td>
                                <td class="px-4 py-3 tabular-nums">${{ number_format((float) $row->cost_usd, 4) }}</td>
                                <td class="px-4 py-3">
                                    <span id="pub-{{ $row->id }}">
                                        @if ($row->published_faq_id)
                                            <span class="inline-flex items-center gap-1 text-green-600"><i data-lucide="check" class="w-4 h-4"></i></span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.quran.ai-inquiries.show', $row->id) }}" class="text-accent font-semibold hover:underline inline-flex items-center gap-1">
                                            <i data-lucide="eye" class="w-4 h-4"></i> {{ __('admin.quran_ai_view') }}
                                        </a>
                                        @unless ($row->published_faq_id)
                                            <button type="button" id="pubbtn-{{ $row->id }}"
                                                    @click="openPublish({{ $row->id }}, @js($row->question))"
                                                    class="inline-flex items-center gap-1 font-semibold text-[#0056b3] hover:underline">
                                                <i data-lucide="megaphone" class="w-4 h-4"></i> {{ __('admin.quran_ai_publish_faq') }}
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $inquiries->links() }}</div>
        @endif
    </div>

    {{-- Premium publish-as-FAQ modal (single instance; categories lazy-loaded on open) --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-[70]" style="display:none" role="dialog" aria-modal="true">
        <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="open = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" @click.stop
                 class="relative w-full max-w-lg rounded-2xl shadow-2xl p-6 sm:p-8"
                 :class="darkMode ? 'bg-gray-900 text-white' : 'bg-white text-gray-900'">

                <button type="button" @click="open = false" class="absolute top-4 end-4 text-gray-400 hover:text-gray-600" aria-label="{{ __('admin.cancel') }}">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl text-white bg-gradient-to-br from-[#0056b3] to-[#218838]">
                        <i data-lucide="megaphone" class="w-5 h-5"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold">{{ __('admin.quran_ai_publish_modal_title') }}</h2>
                        <p class="text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">{{ __('admin.quran_ai_publish_modal_hint') }}</p>
                    </div>
                </div>

                <p class="mb-4 rounded-lg px-3 py-2 text-sm font-medium truncate"
                   :class="darkMode ? 'bg-gray-800 text-gray-200' : 'bg-gray-50 text-gray-700'" x-text="question" :title="question"></p>

                <label class="block text-xs font-semibold uppercase tracking-wider mb-2" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">{{ __('admin.quran_ai_category') }}</label>
                <select x-model="selectedCategory"
                        class="w-full rounded-lg border px-3 py-2 text-base sm:text-sm mb-2"
                        :class="darkMode ? 'bg-gray-950 border-gray-700 text-white' : 'bg-gray-50 border-gray-300'">
                    <option value="">{{ __('admin.quran_ai_select_category') }}</option>
                    <template x-for="c in categories" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
                <input type="text" x-model="newCategory" placeholder="{{ __('admin.quran_ai_new_category') }}"
                       class="w-full rounded-lg border px-3 py-2 text-base sm:text-sm"
                       :class="darkMode ? 'bg-gray-950 border-gray-700 text-white' : 'bg-gray-50 border-gray-300'">

                <label class="flex items-center gap-2 text-sm mt-4 cursor-pointer">
                    <input type="checkbox" x-model="isActive" class="rounded">
                    <span>{{ __('admin.quran_ai_active') }}</span>
                </label>

                <p x-show="error" x-cloak class="mt-3 text-sm text-red-500" x-text="error"></p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg font-semibold border"
                            :class="darkMode ? 'border-gray-700 text-gray-300 hover:bg-gray-800' : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                        {{ __('admin.cancel') }}
                    </button>
                    <button type="button" @click="submit()" :disabled="submitting"
                            class="px-5 py-2 rounded-lg font-semibold text-white bg-gradient-to-r from-[#0056b3] to-[#218838] inline-flex items-center gap-2 disabled:opacity-60">
                        <span x-show="submitting" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span x-text="submitting ? @js(__('admin.quran_ai_publishing')) : @js(__('admin.quran_ai_publish_faq'))"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function aiInquiriesPage() {
        return {
            open: false,
            submitting: false,
            error: '',
            inquiryId: null,
            question: '',
            selectedCategory: '',
            newCategory: '',
            isActive: true,
            categories: [],
            categoriesLoaded: false,
            categoriesUrl: @json(route('admin.quran.ai-inquiries.categories')),
            publishBase: @json(url('/su/portal/v1/admin/ai-inquiries')),
            csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',

            async openPublish(id, question) {
                this.inquiryId = id;
                this.question = question;
                this.selectedCategory = '';
                this.newCategory = '';
                this.isActive = true;
                this.error = '';
                this.open = true;

                if (!this.categoriesLoaded) {
                    try {
                        const res = await fetch(this.categoriesUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        const data = await res.json();
                        this.categories = Array.isArray(data.categories) ? data.categories : [];
                    } catch (e) { /* ignore — admin can type a new category */ }
                    this.categoriesLoaded = true;
                }
                this.$nextTick(() => window.lucide?.createIcons());
            },

            category() {
                return (this.newCategory || '').trim() || this.selectedCategory || null;
            },

            async submit() {
                this.submitting = true;
                this.error = '';
                try {
                    const res = await fetch(this.publishBase + '/' + this.inquiryId + '/publish', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrf,
                        },
                        body: JSON.stringify({ category: this.category(), is_active: this.isActive ? 1 : 0 }),
                    });
                    if (!res.ok) throw new Error('http ' + res.status);

                    // Reflect the publish in the row without a full reload.
                    const cell = document.getElementById('pub-' + this.inquiryId);
                    if (cell) cell.innerHTML = '<span class="inline-flex items-center gap-1 text-green-600"><i data-lucide="check" class="w-4 h-4"></i></span>';
                    const btn = document.getElementById('pubbtn-' + this.inquiryId);
                    if (btn) btn.remove();
                    this.open = false;
                    window.lucide?.createIcons();
                } catch (e) {
                    this.error = @json(__('admin.operation_failed'));
                } finally {
                    this.submitting = false;
                }
            },
        };
    }
</script>
@endpush
@endsection
