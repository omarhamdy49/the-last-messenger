@php($h = $hadith ?? null)
@include('admin.hadith._flash')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-2xl border p-6 space-y-5" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">نص الحديث</h2>
            @include('admin.hadith._field', ['name' => 'text_ar', 'label' => __('hadith_admin.text_ar'), 'value' => $h?->text_ar, 'type' => 'textarea', 'dir' => 'rtl', 'required' => true, 'rows' => 10, 'help' => 'يُعاد بناء فهرس البحث تلقائيًّا عند الحفظ.'])
            @include('admin.hadith._field', ['name' => 'text_en', 'label' => __('hadith_admin.text_en'), 'value' => $h?->text_en, 'type' => 'textarea', 'dir' => 'ltr', 'rows' => 8])
            @include('admin.hadith._field', ['name' => 'narrated_by', 'label' => __('hadith_admin.narrated_by'), 'value' => $h?->narrated_by, 'dir' => 'rtl'])
        </div>

        <div class="rounded-2xl border p-6 space-y-5" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">المراجع</h2>
            @include('admin.hadith._field', ['name' => 'canonical_reference', 'label' => 'المرجع المعتمد', 'value' => $h?->canonical_reference, 'dir' => 'rtl'])
            @include('admin.hadith._field', ['name' => 'in_book_reference', 'label' => 'المرجع داخل الباب', 'value' => $h?->in_book_reference, 'dir' => 'rtl'])
            @include('admin.hadith._field', ['name' => 'translation_reference', 'label' => 'مرجع الترجمة', 'value' => $h?->translation_reference, 'dir' => 'ltr'])
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border p-6 space-y-4" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">الموضع</h2>
            <div>
                <label for="collection_id" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.collection') }}<span class="text-red-500 ms-1">*</span></label>
                <select id="collection_id" name="collection_id" class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    @foreach($collections as $c)<option value="{{ $c->id }}" @selected(old('collection_id', $h?->collection_id ?? ($presetCollection ?? null)) == $c->id)>{{ $c->title_ar }}</option>@endforeach
                </select>
                @error('collection_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="book_id" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.book') }}</label>
                <select id="book_id" name="book_id" class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">—</option>
                    @foreach($books as $b)<option value="{{ $b->id }}" data-collection="{{ $b->collection_id }}" @selected(old('book_id', $h?->book_id) == $b->id)>{{ $b->book_number }} — {{ $b->title_ar }}</option>@endforeach
                </select>
                @error('book_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="chapter_id" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.chapter') }}</label>
                <select id="chapter_id" name="chapter_id" class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">—</option>
                    @foreach($chapters as $ch)<option value="{{ $ch->id }}" data-book="{{ $ch->book_id }}" @selected(old('chapter_id', $h?->chapter_id) == $ch->id)>{{ $ch->chapter_number }} — {{ $ch->title_ar }}</option>@endforeach
                </select>
                @error('chapter_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="rounded-2xl border p-6 space-y-4" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <h2 class="text-lg font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">التعريف والدرجة</h2>
            @include('admin.hadith._field', ['name' => 'reference_key', 'label' => __('hadith_admin.reference_key'), 'value' => $h?->reference_key, 'dir' => 'ltr', 'required' => true, 'help' => 'يدخل في الرابط العام /hadith/{كتاب}:{المفتاح} ويجب ألا يتكرر داخل الكتاب.'])
            @include('admin.hadith._field', ['name' => 'number', 'label' => __('hadith_admin.number'), 'value' => $h?->number, 'dir' => 'ltr'])
            @include('admin.hadith._field', ['name' => 'in_book_number', 'label' => 'الرقم داخل الباب', 'value' => $h?->in_book_number, 'dir' => 'ltr'])
            <div>
                <label for="grade" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.grade') }}</label>
                <select id="grade" name="grade" class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('hadith_admin.ungraded') }}</option>
                    @foreach($grades as $g)<option value="{{ $g->value }}" @selected(old('grade', $h?->grade?->value) === $g->value)>{{ $g->label('ar') }}</option>@endforeach
                </select>
                <p class="mt-1 text-xs" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">اتركها فارغة إن كان المصدر غير حاسم — لا تُخمَّن.</p>
            </div>
            @include('admin.hadith._field', ['name' => 'raw_grade', 'label' => __('hadith_admin.raw_grade'), 'value' => $h?->raw_grade, 'dir' => 'rtl'])
            @include('admin.hadith._field', ['name' => 'sort_order', 'label' => 'ترتيب العرض', 'value' => $h?->sort_order ?? 0, 'type' => 'number'])
            @include('admin.hadith._checkbox', ['name' => 'is_published', 'label' => 'منشور', 'checked' => $h?->is_published ?? false, 'help' => 'لا يظهر للزوار إلا إذا كان الكتاب والباب والفصل منشورة كذلك.'])
        </div>

        <div class="flex gap-3">
            <button type="submit" class="flex-1 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">{{ __('hadith_admin.save') }}</button>
            <a href="{{ route('admin.hadiths.index') }}" class="px-6 py-3 rounded-xl font-semibold border" :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('hadith_admin.cancel') }}</a>
        </div>
    </div>
</div>
