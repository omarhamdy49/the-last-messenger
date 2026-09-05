@php($c = $collection ?? null)
@include('admin.hadith._flash')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-2xl border p-6 space-y-5" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">بيانات الكتاب</h2>
            @include('admin.hadith._field', ['name' => 'title_ar', 'label' => 'العنوان بالعربية', 'value' => $c?->title_ar, 'dir' => 'rtl', 'required' => true])
            @include('admin.hadith._field', ['name' => 'title_en', 'label' => 'العنوان بالإنجليزية', 'value' => $c?->title_en, 'dir' => 'ltr'])
            @include('admin.hadith._field', ['name' => 'slug', 'label' => 'المعرّف (slug)', 'value' => $c?->slug, 'dir' => 'ltr', 'required' => true, 'help' => 'يدخل في الرابط العام /hadith/{slug} — حروف لاتينية صغيرة وشرطات فقط، وتغييره يكسر الروابط القديمة.'])
            @include('admin.hadith._field', ['name' => 'intro_ar', 'label' => 'مقدمة بالعربية', 'value' => $c?->intro_ar, 'type' => 'textarea', 'dir' => 'rtl'])
            @include('admin.hadith._field', ['name' => 'intro_en', 'label' => 'مقدمة بالإنجليزية', 'value' => $c?->intro_en, 'type' => 'textarea', 'dir' => 'ltr'])
        </div>
    </div>
    <div class="space-y-6">
        <div class="rounded-2xl border p-6 space-y-4" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">الإعدادات</h2>
            @include('admin.hadith._field', ['name' => 'reference_template', 'label' => 'قالب المرجع', 'value' => $c?->reference_template, 'dir' => 'ltr', 'help' => 'يبني المرجع العام للحديث؛ قالب خاطئ ينتج روابط ميتة.'])
            @include('admin.hadith._field', ['name' => 'sort_order', 'label' => 'ترتيب العرض', 'value' => $c?->sort_order ?? 0, 'type' => 'number'])
            <div class="space-y-3 pt-2">
                @include('admin.hadith._checkbox', ['name' => 'has_books', 'label' => 'ينقسم إلى أبواب', 'checked' => $c?->has_books ?? true])
                @include('admin.hadith._checkbox', ['name' => 'has_chapters', 'label' => 'ينقسم إلى فصول', 'checked' => $c?->has_chapters ?? true])
                @include('admin.hadith._checkbox', ['name' => 'is_featured', 'label' => 'مميّز', 'checked' => $c?->is_featured ?? false])
                @include('admin.hadith._checkbox', ['name' => 'is_published', 'label' => 'منشور', 'checked' => $c?->is_published ?? false, 'help' => 'إخفاء الكتاب يُخفي كل أحاديثه من الموقع العام.'])
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button type="submit" class="flex-1 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">{{ __('hadith_admin.save') }}</button>
            <a href="{{ route('admin.hadith-collections.index') }}" class="px-6 py-3 rounded-xl font-semibold border" :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('hadith_admin.cancel') }}</a>
        </div>
    </div>
</div>
