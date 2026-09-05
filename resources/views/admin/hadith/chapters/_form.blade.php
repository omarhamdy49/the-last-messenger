@php($ch = $chapter ?? null)
@include('admin.hadith._flash')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 rounded-2xl border p-6 space-y-5" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div>
            <label for="book_id" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.book') }}<span class="text-red-500 ms-1">*</span></label>
            <select id="book_id" name="book_id" class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                @foreach($books as $b)
                    <option value="{{ $b->id }}" @selected(old('book_id', $ch?->book_id ?? ($presetBook ?? null)) == $b->id)>{{ $b->collection?->title_ar }} — {{ $b->book_number }} — {{ $b->title_ar }}</option>
                @endforeach
            </select>
            @error('book_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
        </div>
        @include('admin.hadith._field', ['name' => 'chapter_number', 'label' => 'رقم الفصل', 'value' => $ch?->chapter_number, 'dir' => 'ltr', 'required' => true])
        @include('admin.hadith._field', ['name' => 'title_ar', 'label' => 'العنوان بالعربية', 'value' => $ch?->title_ar, 'dir' => 'rtl', 'required' => true])
        @include('admin.hadith._field', ['name' => 'title_en', 'label' => 'العنوان بالإنجليزية', 'value' => $ch?->title_en, 'dir' => 'ltr'])
        @include('admin.hadith._field', ['name' => 'intro', 'label' => 'المقدمة', 'value' => $ch?->intro, 'type' => 'textarea', 'dir' => 'rtl'])
        @include('admin.hadith._field', ['name' => 'ending', 'label' => 'الخاتمة', 'value' => $ch?->ending, 'type' => 'textarea', 'dir' => 'rtl'])
    </div>
    <div class="space-y-6">
        <div class="rounded-2xl border p-6 space-y-4" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            @include('admin.hadith._field', ['name' => 'sort_order', 'label' => 'ترتيب العرض', 'value' => $ch?->sort_order ?? 0, 'type' => 'number'])
            @include('admin.hadith._checkbox', ['name' => 'is_published', 'label' => 'منشور', 'checked' => $ch?->is_published ?? false])
        </div>
        <div class="flex gap-3">
            <button type="submit" class="flex-1 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">{{ __('hadith_admin.save') }}</button>
            <a href="{{ route('admin.hadith-chapters.index') }}" class="px-6 py-3 rounded-xl font-semibold border" :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('hadith_admin.cancel') }}</a>
        </div>
    </div>
</div>
