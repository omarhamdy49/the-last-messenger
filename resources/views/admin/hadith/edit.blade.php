@extends('layouts.admin')

@section('title', 'تعديل الحديث')

@push('styles')
<style>
    .tab-button {
        transition: all 0.3s ease;
        position: relative;
    }

    .tab-button.active {
        background: linear-gradient(135deg, #218838 0%, #17a2b8 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(33, 136, 56, 0.3);
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 80%;
        height: 3px;
        background: linear-gradient(135deg, #218838 0%, #17a2b8 100%);
        border-radius: 2px;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeInUp 0.4s ease;
    }

    .form-input {
        transition: all 0.3s ease;
    }

    .form-input:focus {
        box-shadow: 0 0 0 3px rgba(33, 136, 56, 0.1);
        border-color: #218838;
    }

    .btn-submit {
        background: linear-gradient(135deg, #218838 0%, #17a2b8 100%);
        transition: all 0.3s ease;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(33, 136, 56, 0.3);
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    .btn-submit.loading {
        position: relative;
        color: transparent;
    }

    .btn-submit.loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        border: 2px solid white;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .required-asterisk {
        color: #dc2626;
        margin-right: 4px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .status-badge.sahih {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.2) 100%);
        color: #10b981;
    }

    .status-badge.hasan {
        background: rgba(20, 184, 166, 0.2);
        color: #14b8a6;
    }

    .status-badge.daif {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
    }

    .status-badge.mawdu {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }

    .narrator-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: rgba(33, 136, 56, 0.2);
        border: 1px solid rgba(33, 136, 56, 0.3);
        border-radius: 9999px;
        color: #218838;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .narrator-tag button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 1rem;
        height: 1rem;
        background: rgba(239, 68, 68, 0.3);
        border-radius: 50%;
        color: #ef4444;
        transition: all 0.2s;
    }

    .narrator-tag button:hover {
        background: rgba(239, 68, 68, 0.5);
        transform: scale(1.1);
    }

    .gradient-text-green {
        background: linear-gradient(135deg, #218838 0%, #17a2b8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto" x-data="hadithForm()">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold gradient-text-green">تعديل الحديث</h1>
                    <span class="status-badge {{ $hadith->grade->value }}">
                        <i data-lucide="circle" class="w-4 h-4"></i>
                        @if($hadith->grade->value === 'sahih')
                            صحيح
                        @elseif($hadith->grade->value === 'hasan')
                            حسن
                        @elseif($hadith->grade->value === 'daif')
                            ضعيف
                        @elseif($hadith->grade->value === 'mawdu')
                            موضوع
                        @else
                            غير معروف
                        @endif
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400">تعديل بيانات الحديث رقم: {{ $hadith->hadith_number }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.hadith.show', $hadith->id) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                   target="_blank">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                    <span>معاينة</span>
                </a>
                <a href="{{ route('admin.hadith.index') }}"
                   class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    <span>العودة للقائمة</span>
                </a>
            </div>
        </div>

        <!-- Hadith Metadata -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="glass-dark rounded-lg p-4">
                <div class="flex items-center gap-2 text-gray-400 text-sm mb-1">
                    <i data-lucide="book-open" class="w-4 h-4"></i>
                    <span>الكتاب</span>
                </div>
                <p class="text-white font-semibold">{{ $hadith->book->name['ar'] ?? $hadith->book->name['en'] ?? 'غير محدد' }}</p>
            </div>

            <div class="glass-dark rounded-lg p-4">
                <div class="flex items-center gap-2 text-gray-400 text-sm mb-1">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    <span>تاريخ الإضافة</span>
                </div>
                <p class="text-white font-semibold">{{ $hadith->created_at->format('Y-m-d') }}</p>
            </div>

            <div class="glass-dark rounded-lg p-4">
                <div class="flex items-center gap-2 text-gray-400 text-sm mb-1">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span>المشاهدات</span>
                </div>
                <p class="text-white font-semibold">{{ number_format($hadith->views_count ?? 0) }}</p>
            </div>

            <div class="glass-dark rounded-lg p-4">
                <div class="flex items-center gap-2 text-gray-400 text-sm mb-1">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <span>آخر تحديث</span>
                </div>
                <p class="text-white font-semibold">{{ $hadith->updated_at->diffForHumans() }}</p>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <form action="{{ route('admin.hadith.update', $hadith->id) }}" method="POST" @submit="handleSubmit" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information Card -->
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="scroll-text" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                <!-- Book & Chapter Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Book -->
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="required-asterisk">*</span>
                            الكتاب
                        </label>
                        <select name="book_id"
                                id="book_id"
                                x-model="selectedBook"
                                @change="loadChapters"
                                class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none"
                                required>
                            <option value="">اختر الكتاب</option>
                            @foreach($books ?? [] as $book)
                                <option value="{{ $book->id }}" {{ old('book_id', $hadith->book_id) == $book->id ? 'selected' : '' }}>
                                    {{ $book->name['ar'] ?? $book->name['en'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('book_id')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Chapter -->
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            الباب (اختياري)
                        </label>
                        <select name="chapter_id"
                                id="chapter_id"
                                class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none">
                            <option value="">اختر الباب</option>
                            @foreach($chapters ?? [] as $chapter)
                                <option value="{{ $chapter->id }}" {{ old('chapter_id', $hadith->chapter_id) == $chapter->id ? 'selected' : '' }}>
                                    {{ $chapter->name['ar'] ?? $chapter->name['en'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('chapter_id')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Hadith Number & Grade Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Hadith Number -->
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="required-asterisk">*</span>
                            رقم الحديث
                        </label>
                        <input type="number"
                               name="hadith_number"
                               id="hadith_number"
                               value="{{ old('hadith_number', $hadith->hadith_number) }}"
                               class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                               placeholder="مثال: 1"
                               required>
                        @error('hadith_number')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Grade -->
                    <div>
                        <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                            <span class="required-asterisk">*</span>
                            الدرجة / الحكم
                        </label>
                        <select name="grade"
                                id="grade"
                                class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none"
                                required>
                            <option value="">اختر الدرجة</option>
                            <option value="sahih" {{ old('grade', $hadith->grade->value) == 'sahih' ? 'selected' : '' }}>صحيح</option>
                            <option value="hasan" {{ old('grade', $hadith->grade->value) == 'hasan' ? 'selected' : '' }}>حسن</option>
                            <option value="daif" {{ old('grade', $hadith->grade->value) == 'daif' ? 'selected' : '' }}>ضعيف</option>
                            <option value="mawdu" {{ old('grade', $hadith->grade->value) == 'mawdu' ? 'selected' : '' }}>موضوع</option>
                            <option value="unknown" {{ old('grade', $hadith->grade->value) == 'unknown' ? 'selected' : '' }}>غير معروف</option>
                        </select>
                        @error('grade')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Arabic Text -->
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                        <span class="required-asterisk">*</span>
                        النص العربي
                    </label>
                    <textarea name="arabic_text"
                              id="arabic_text"
                              rows="6"
                              class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none custom-scrollbar"
                              placeholder="أدخل نص الحديث بالعربية..."
                              dir="rtl"
                              required>{{ old('arabic_text', $hadith->arabic_text) }}</textarea>
                    @error('arabic_text')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-400">النص الكامل للحديث الشريف</p>
                </div>

                <!-- Narrator Chain -->
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                        سلسلة الإسناد (النص الكامل)
                    </label>
                    <textarea name="narrator_chain"
                              id="narrator_chain"
                              rows="3"
                              class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none custom-scrollbar"
                              placeholder="مثال: حدثنا فلان عن فلان عن فلان..."
                              dir="rtl">{{ old('narrator_chain', $hadith->narrator_chain) }}</textarea>
                    @error('narrator_chain')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-400">السند الكامل للحديث كنص متصل</p>
                </div>

                <!-- Narrators Selection (Optional Advanced Feature) -->
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                        الرواة في السند (اختياري)
                    </label>
                    <div class="space-y-3">
                        <select
                            x-model="selectedNarrator"
                            @change="addNarrator"
                            class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none"
                        >
                            <option value="">اختر راوٍ لإضافته</option>
                            @foreach($narrators ?? [] as $narrator)
                                <option value="{{ $narrator->id }}">
                                    {{ $narrator->name_arabic }} - {{ $narrator->name_english ?? '' }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Selected Narrators -->
                        <div x-show="selectedNarrators.length > 0" class="flex flex-wrap gap-2">
                            <template x-for="(narratorId, index) in selectedNarrators" :key="narratorId">
                                <div class="narrator-tag">
                                    <span x-text="getNarratorName(narratorId)"></span>
                                    <button type="button" @click="removeNarrator(index)">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                    </button>
                                    <input type="hidden" :name="`narrators[${index}]`" :value="narratorId">
                                </div>
                            </template>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">يمكنك اختيار الرواة من قاعدة البيانات لربطهم بالحديث</p>
                </div>

                <!-- Reference URL -->
                <div>
                    <label class="block text-sm font-semibold" :class="darkMode ? \'text-gray-300\' : \'text-gray-700\' mb-2">
                        رابط المرجع (اختياري)
                    </label>
                    <input type="url"
                           name="reference_url"
                           id="reference_url"
                           value="{{ old('reference_url', $hadith->reference_url) }}"
                           class="form-input w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white placeholder-gray-400 focus:outline-none"
                           placeholder="https://sunnah.com/...">
                    @error('reference_url')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-400">رابط إلى مصدر خارجي مثل sunnah.com</p>
                </div>
            </div>
        </div>

        <!-- Multi-Language Translations Card -->
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="languages" class="w-6 h-6"></i>
                الترجمات متعددة اللغات
            </h2>

            {{-- Translation Field --}}
            <x-forms.multi-lang-input
                name="translations"
                label="ترجمة الحديث"
                type="textarea"
                :rows="6"
                :value="old('translations', $hadith->translations ?? [])"
            />
        </div>

        <!-- Form Actions -->
        <div class="rounded-2xl p-6 transition-colors" :class="darkMode ? \'glass-dark\' : \'glass-light-soft\' shadow-xl">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex gap-3">
                    <a href="{{ route('admin.hadith.index') }}"
                       class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                        <i data-lucide="x" class="w-5 h-5"></i>
                        إلغاء
                    </a>

                    <button type="button"
                            onclick="if(confirm('هل أنت متأكد من حذف هذا الحديث؟')) { document.getElementById('delete-form').submit(); }"
                            class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center gap-2">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                        حذف
                    </button>
                </div>

                <div class="flex gap-3">
                    <button type="submit"
                            class="btn-submit px-8 py-3 text-white rounded-lg font-semibold flex items-center gap-2"
                            x-bind:class="{ 'loading': isSubmitting }"
                            x-bind:disabled="isSubmitting">
                        <i data-lucide="save" class="w-5 h-5" x-show="!isSubmitting"></i>
                        <span x-text="isSubmitting ? '' : 'حفظ التغييرات'"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Delete Form (Hidden) -->
    <form id="delete-form" action="{{ route('admin.hadith.destroy', $hadith->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

@push('scripts')
<script>
    function hadithForm() {
        return {
            isSubmitting: false,
            selectedBook: '{{ old('book_id', $hadith->book_id) }}',
            selectedNarrator: '',
            selectedNarrators: @json(old('narrators', $hadith->narrators->pluck('id')->toArray())),
            narratorsList: @json($narrators ?? []),

            handleSubmit(event) {
                this.isSubmitting = true;
                return true;
            },

            addNarrator() {
                if (this.selectedNarrator && !this.selectedNarrators.includes(this.selectedNarrator)) {
                    this.selectedNarrators.push(this.selectedNarrator);
                    this.selectedNarrator = '';
                    this.$nextTick(() => {
                        lucide.createIcons();
                    });
                }
            },

            removeNarrator(index) {
                this.selectedNarrators.splice(index, 1);
            },

            getNarratorName(narratorId) {
                const narrator = this.narratorsList.find(n => n.id == narratorId);
                return narrator ? narrator.name_arabic : 'غير معروف';
            },

            loadChapters() {
                console.log('Loading chapters for book:', this.selectedBook);
            },

            init() {
                this.$nextTick(() => {
                    lucide.createIcons();
                });
            }
        }
    }

    // Initialize icons after page load
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
    });
</script>
@endpush
@endsection
