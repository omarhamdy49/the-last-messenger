@extends('layouts.admin')

@section('title', 'تعديل القارئ')

@section('content')
<div class="max-w-6xl mx-auto" x-data="reciterForm({{ json_encode($reciter ?? []) }})" x-init="init()">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold gradient-text mb-2">تعديل القارئ</h1>
                <p class="text-gray-600 dark:text-gray-400">تعديل بيانات القارئ</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.reciters.show', $reciter->id) }}"
                   class="flex items-center gap-2 px-4 py-2 bg-blue-500/20 text-blue-400 rounded-lg hover:bg-blue-500/30 transition-colors">
                    <i data-lucide="eye" class="w-5 h-5"></i>
                    <span>عرض</span>
                </a>
                <a href="{{ route('admin.reciters.index') }}"
                   class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i data-lucide="arrow-right" class="w-5 h-5"></i>
                    <span>العودة للقائمة</span>
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.reciters.update', $reciter->id) }}" method="POST" enctype="multipart/form-data" @submit="handleSubmit" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Information Card --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="user" class="w-6 h-6"></i>
                المعلومات الأساسية
            </h2>

            <div class="space-y-6">
                {{-- Multi-language Name --}}
                <div>
                    <label class="block text-sm font-semibold mb-4" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        <span class="text-red-400">*</span> الاسم (متعدد اللغات)
                    </label>

                    <div class="flex flex-wrap gap-2 mb-4" x-data="{ activeTab: 'ar' }">
                        @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                            <button
                                type="button"
                                @click="activeTab = '{{ $lang }}'"
                                :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'glass-dark text-gray-400' : 'glass-light-soft text-gray-600')"
                                class="px-4 py-2 rounded-full text-sm font-semibold transition-all hover:scale-105"
                            >
                                {{ $label }}
                            </button>
                        @endforeach

                        @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                            <div x-show="activeTab === '{{ $lang }}'" x-cloak class="w-full mt-2">
                                <input
                                    type="text"
                                    name="name[{{ $lang }}]"
                                    value="{{ old('name.' . $lang, $reciter->name[$lang] ?? '') }}"
                                    class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                                    dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                                    placeholder="اسم القارئ"
                                    required
                                >
                                @error('name.' . $lang)
                                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Country & Style --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الدولة
                        </label>
                        <select
                            name="country"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900'"
                        >
                            <option value="">اختر الدولة</option>
                            <option value="السعودية" {{ old('country', $reciter->country) == 'السعودية' ? 'selected' : '' }}>السعودية</option>
                            <option value="مصر" {{ old('country', $reciter->country) == 'مصر' ? 'selected' : '' }}>مصر</option>
                            <option value="السودان" {{ old('country', $reciter->country) == 'السودان' ? 'selected' : '' }}>السودان</option>
                            <option value="العراق" {{ old('country', $reciter->country) == 'العراق' ? 'selected' : '' }}>العراق</option>
                            <option value="سوريا" {{ old('country', $reciter->country) == 'سوريا' ? 'selected' : '' }}>سوريا</option>
                            <option value="المغرب" {{ old('country', $reciter->country) == 'المغرب' ? 'selected' : '' }}>المغرب</option>
                            <option value="تونس" {{ old('country', $reciter->country) == 'تونس' ? 'selected' : '' }}>تونس</option>
                            <option value="الجزائر" {{ old('country', $reciter->country) == 'الجزائر' ? 'selected' : '' }}>الجزائر</option>
                        </select>
                        @error('country')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            أسلوب القراءة
                        </label>
                        <select
                            name="style"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900'"
                        >
                            <option value="">اختر الأسلوب</option>
                            <option value="Tajweed" {{ old('style', $reciter->style) == 'Tajweed' ? 'selected' : '' }}>التجويد</option>
                            <option value="Qaloon" {{ old('style', $reciter->style) == 'Qaloon' ? 'selected' : '' }}>قالون</option>
                            <option value="Warsh" {{ old('style', $reciter->style) == 'Warsh' ? 'selected' : '' }}>ورش</option>
                            <option value="Hafs" {{ old('style', $reciter->style) == 'Hafs' ? 'selected' : '' }}>حفص</option>
                            <option value="Kalon" {{ old('style', $reciter->style) == 'Kalon' ? 'selected' : '' }}>خلف</option>
                            <option value="Duri" {{ old('style', $reciter->style) == 'Duri' ? 'selected' : '' }}>الدوري</option>
                            <option value="Kasai" {{ old('style', $reciter->style) == 'Kasai' ? 'selected' : '' }}>الكسائي</option>
                        </select>
                        @error('style')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Profile Image --}}
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        صورة الملف الشخصي
                    </label>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-32 h-32 rounded-xl overflow-hidden border-2" :class="darkMode ? 'border-white/20' : 'border-gray-200'">
                                <img x-ref="imagePreview" src="{{ $reciter->profile_image ?? '/images/default-avatar.png' }}" alt="Preview" class="w-full h-full object-cover">
                            </div>
                        </div>
                        <div class="flex-1">
                            <input
                                type="file"
                                name="profile_image"
                                accept="image/*"
                                @change="previewImage"
                                class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900'"
                            >
                            <p class="mt-2 text-xs text-gray-500">PNG, JPG, GIF حتى 2MB (اترك فارغًا للاحتفاظ بالصورة الحالية)</p>
                            @if($reciter->profile_image)
                                <label class="mt-2 flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="keep_image" value="1" checked class="rounded">
                                    <span :class="darkMode ? 'text-gray-300' : 'text-gray-700'">الاحتفاظ بالصورة الحالية</span>
                                </label>
                            @endif
                            @error('profile_image')
                                <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Biography Card --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="file-text" class="w-6 h-6"></i>
                السيرة الذاتية
            </h2>

            <div class="flex flex-wrap gap-2 mb-4" x-data="{ activeTab: 'ar' }">
                @foreach(['ar' => 'العربية', 'en' => 'English', 'fr' => 'Français', 'tr' => 'Türkçe', 'ur' => 'اردو'] as $lang => $label)
                    <button
                        type="button"
                        @click="activeTab = '{{ $lang }}'"
                        :class="activeTab === '{{ $lang }}' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'glass-dark text-gray-400' : 'glass-light-soft text-gray-600')"
                        class="px-4 py-2 rounded-full text-sm font-semibold transition-all hover:scale-105"
                    >
                        {{ $label }}
                    </button>
                @endforeach

                @foreach(['ar', 'en', 'fr', 'tr', 'ur'] as $lang)
                    <div x-show="activeTab === '{{ $lang }}'" x-cloak class="w-full mt-2">
                        <textarea
                            name="biography[{{ $lang }}]"
                            rows="6"
                            class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="darkMode ? 'bg-white/10 border border-white/20 text-white placeholder-gray-400' : 'glass-light-strong text-gray-900 placeholder-gray-500'"
                            dir="{{ in_array($lang, ['ar', 'ur']) ? 'rtl' : 'ltr' }}"
                            placeholder="السيرة الذاتية للقارئ..."
                        >{{ old('biography.' . $lang, $reciter->biography[$lang] ?? '') }}</textarea>
                        @error('biography.' . $lang)
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Status & Featured --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <i data-lucide="settings" class="w-6 h-6"></i>
                الإعدادات
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                        الحالة
                    </label>
                    <select
                        name="status"
                        class="w-full px-4 py-3 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :class="darkMode ? 'bg-white/10 border border-white/20 text-white' : 'glass-light-strong text-gray-900'"
                    >
                        <option value="active" {{ old('status', $reciter->status) == 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="inactive" {{ old('status', $reciter->status) == 'inactive' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                    @error('status')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-end">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="featured" value="1" {{ old('featured', $reciter->featured) ? 'checked' : '' }} class="w-5 h-5 rounded">
                        <span class="font-semibold" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            <i data-lucide="star" class="w-5 h-5 inline-block text-orange-400"></i>
                            قارئ مميز
                        </span>
                    </label>
                    @error('featured')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="rounded-2xl p-6 transition-colors shadow-xl" :class="darkMode ? 'glass-dark' : 'glass-light-soft'">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.reciters.index') }}" class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors flex items-center gap-2">
                        <i data-lucide="x" class="w-5 h-5"></i>
                        إلغاء
                    </a>

                    <button
                        type="button"
                        @click="if(confirm('هل أنت متأكد من حذف هذا القارئ؟')) { $refs.deleteForm.submit(); }"
                        class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center gap-2"
                    >
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                        حذف
                    </button>
                </div>

                <button type="submit" class="px-8 py-3 gradient-primary text-white rounded-lg font-semibold flex items-center gap-2 hover:shadow-lg transition-all" x-bind:disabled="isSubmitting">
                    <i data-lucide="save" class="w-5 h-5" x-show="!isSubmitting"></i>
                    <span x-text="isSubmitting ? 'جاري الحفظ...' : 'حفظ التغييرات'"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- Hidden Delete Form --}}
    <form x-ref="deleteForm" action="{{ route('admin.reciters.destroy', $reciter->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
function reciterForm(reciter) {
    return {
        isSubmitting: false,
        reciter: reciter,

        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.$refs.imagePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

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
