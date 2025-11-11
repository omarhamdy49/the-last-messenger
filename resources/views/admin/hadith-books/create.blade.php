@extends('layouts.admin')

@section('title', 'إضافة كتاب حديث')

@section('content')
<div class="mx-auto px-6 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" dir="rtl">

    {{-- Page Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.hadith-books.index') }}"
               class="p-2 rounded-lg transition-all hover:scale-110"
               :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </a>

            <div class="flex-1">
                <h1 class="text-3xl font-bold gradient-text mb-2">
                    إضافة كتاب حديث جديد
                </h1>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    أضف كتاباً جديداً من كتب الحديث (مثل: صحيح البخاري، صحيح مسلم)
                </p>
            </div>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-6 p-4 rounded-lg border-l-4 border-red-500"
             :class="darkMode ? 'bg-red-900/20' : 'bg-red-50'">
            <div class="flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-red-500 mb-2">يرجى إصلاح الأخطاء التالية:</h3>
                    <ul class="list-disc list-inside space-y-1 text-sm"
                        :class="darkMode ? 'text-red-400' : 'text-red-600'">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('admin.hadith-books.store') }}" method="POST"
          x-data="{ activeTab: 'ar' }"
          class="space-y-6">
        @csrf

        {{-- Main Card --}}
        <div class="rounded-xl shadow-lg overflow-hidden" :class="darkMode ? 'bg-gray-800' : 'bg-white'">
            <div class="p-6 space-y-6">

                {{-- Language Tabs --}}
                <div class="flex gap-2 border-b pb-4" :class="darkMode ? 'border-gray-700' : 'border-gray-200'">
                    <button type="button" @click="activeTab = 'ar'"
                            class="px-4 py-2 rounded-lg font-semibold transition-all"
                            :class="activeTab === 'ar' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        العربية
                    </button>
                    <button type="button" @click="activeTab = 'en'"
                            class="px-4 py-2 rounded-lg font-semibold transition-all"
                            :class="activeTab === 'en' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        English
                    </button>
                    <button type="button" @click="activeTab = 'fr'"
                            class="px-4 py-2 rounded-lg font-semibold transition-all"
                            :class="activeTab === 'fr' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        Français
                    </button>
                    <button type="button" @click="activeTab = 'tr'"
                            class="px-4 py-2 rounded-lg font-semibold transition-all"
                            :class="activeTab === 'tr' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        Türkçe
                    </button>
                    <button type="button" @click="activeTab = 'ur'"
                            class="px-4 py-2 rounded-lg font-semibold transition-all"
                            :class="activeTab === 'ur' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : (darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-600 hover:text-gray-900')">
                        اردو
                    </button>
                </div>

                {{-- Arabic Content --}}
                <div x-show="activeTab === 'ar'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اسم الكتاب (العربية) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name[ar]" value="{{ old('name.ar') }}" required
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="صحيح البخاري">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الوصف (العربية)
                        </label>
                        <textarea name="description[ar]" rows="4"
                                  class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                                  :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  placeholder="وصف الكتاب">{{ old('description.ar') }}</textarea>
                    </div>
                </div>

                {{-- English Content --}}
                <div x-show="activeTab === 'en'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اسم الكتاب (الإنجليزية) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name[en]" value="{{ old('name.en') }}" required
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="Sahih al-Bukhari">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الوصف (الإنجليزية)
                        </label>
                        <textarea name="description[en]" rows="4"
                                  class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                                  :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  placeholder="Book description">{{ old('description.en') }}</textarea>
                    </div>
                </div>

                {{-- French Content --}}
                <div x-show="activeTab === 'fr'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اسم الكتاب (الفرنسية)
                        </label>
                        <input type="text" name="name[fr]" value="{{ old('name.fr') }}"
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="Sahih al-Bukhari">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الوصف (الفرنسية)
                        </label>
                        <textarea name="description[fr]" rows="4"
                                  class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                                  :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  placeholder="Description du livre">{{ old('description.fr') }}</textarea>
                    </div>
                </div>

                {{-- Turkish Content --}}
                <div x-show="activeTab === 'tr'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اسم الكتاب (التركية)
                        </label>
                        <input type="text" name="name[tr]" value="{{ old('name.tr') }}"
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="Sahih al-Bukhari">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الوصف (التركية)
                        </label>
                        <textarea name="description[tr]" rows="4"
                                  class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                                  :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  placeholder="Kitap açıklaması">{{ old('description.tr') }}</textarea>
                    </div>
                </div>

                {{-- Urdu Content --}}
                <div x-show="activeTab === 'ur'" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اسم الكتاب (الأردية)
                        </label>
                        <input type="text" name="name[ur]" value="{{ old('name.ur') }}"
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="صحیح بخاری" dir="rtl">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            الوصف (الأردية)
                        </label>
                        <textarea name="description[ur]" rows="4" dir="rtl"
                                  class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                                  :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                                  placeholder="کتاب کی تفصیل">{{ old('description.ur') }}</textarea>
                    </div>
                </div>

                <hr :class="darkMode ? 'border-gray-700' : 'border-gray-200'">

                {{-- Other Fields --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المعرف (Slug) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="slug" value="{{ old('slug') }}" required
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="sahih-bukhari">
                        <p class="text-xs mt-1" :class="darkMode ? 'text-gray-500' : 'text-gray-600'">
                            حروف صغيرة وأرقام وشرطات فقط
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            اسم المؤلف
                        </label>
                        <input type="text" name="author_name" value="{{ old('author_name') }}"
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'"
                               placeholder="Imam al-Bukhari">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            المجموعة
                        </label>
                        <select name="collection_id"
                                class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                                :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                            <option value="">-- اختر المجموعة --</option>
                            @foreach($collections as $collection)
                                <option value="{{ $collection->id }}" {{ old('collection_id') == $collection->id ? 'selected' : '' }}>
                                    {{ $collection->getTranslation('name', 'ar') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            ترتيب العرض
                        </label>
                        <input type="number" name="display_order" value="{{ old('display_order', 0) }}" min="0"
                               class="w-full px-4 py-3 rounded-lg border-2 transition-all"
                               :class="darkMode ? 'bg-gray-900 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-400 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#0056b3] peer-checked:to-[#218838]"></div>
                            <span class="ml-3 text-sm font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">نشط</span>
                        </label>
                    </div>
                </div>

            </div>

            {{-- Form Actions --}}
            <div class="px-6 py-4 border-t flex items-center justify-end gap-3"
                 :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-gray-50 border-gray-200'">
                <a href="{{ route('admin.hadith-books.index') }}"
                   class="px-6 py-2.5 rounded-lg font-semibold transition-all"
                   :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-200 text-gray-900 hover:bg-gray-300'">
                    إلغاء
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg hover:shadow-xl">
                    إنشاء الكتاب
                </button>
            </div>
        </div>
    </form>

</div>

@push('scripts')
<script>
    lucide.createIcons();
</script>
@endpush
@endsection
