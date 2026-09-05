@php($b = $book ?? null)
@include('admin.hadith._flash')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 rounded-2xl border p-6 space-y-5" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div>
            <label for="collection_id" class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">{{ __('hadith_admin.collection') }}<span class="text-red-500 ms-1">*</span></label>
            <select id="collection_id" name="collection_id" class="w-full px-4 py-2.5 rounded-xl border" :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                @foreach($collections as $c)
                    <option value="{{ $c->id }}" @selected(old('collection_id', $b?->collection_id ?? ($presetCollection ?? null)) == $c->id)>{{ $c->title_ar }}</option>
                @endforeach
            </select>
            @error('collection_id')<p class="mt-1 text-sm text-red-500">{{ $message }}</p>@enderror
        </div>
        @include('admin.hadith._field', ['name' => 'book_number', 'label' => 'رقم الباب', 'value' => $b?->book_number, 'dir' => 'ltr', 'required' => true])
        @include('admin.hadith._field', ['name' => 'title_ar', 'label' => 'العنوان بالعربية', 'value' => $b?->title_ar, 'dir' => 'rtl', 'required' => true])
        @include('admin.hadith._field', ['name' => 'title_en', 'label' => 'العنوان بالإنجليزية', 'value' => $b?->title_en, 'dir' => 'ltr'])
        @include('admin.hadith._field', ['name' => 'intro', 'label' => 'المقدمة', 'value' => $b?->intro, 'type' => 'textarea', 'dir' => 'rtl'])
    </div>
    <div class="space-y-6">
        <div class="rounded-2xl border p-6 space-y-4" :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            @include('admin.hadith._field', ['name' => 'first_hadith_number', 'label' => 'أول حديث', 'value' => $b?->first_hadith_number, 'dir' => 'ltr'])
            @include('admin.hadith._field', ['name' => 'last_hadith_number', 'label' => 'آخر حديث', 'value' => $b?->last_hadith_number, 'dir' => 'ltr'])
            @include('admin.hadith._field', ['name' => 'sort_order', 'label' => 'ترتيب العرض', 'value' => $b?->sort_order ?? 0, 'type' => 'number'])
            @include('admin.hadith._checkbox', ['name' => 'is_published', 'label' => 'منشور', 'checked' => $b?->is_published ?? false])
        </div>
        <div class="flex gap-3">
            <button type="submit" class="flex-1 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">{{ __('hadith_admin.save') }}</button>
            <a href="{{ route('admin.hadith-books.index') }}" class="px-6 py-3 rounded-xl font-semibold border" :class="darkMode ? 'border-gray-700 text-gray-300' : 'border-gray-300 text-gray-700'">{{ __('hadith_admin.cancel') }}</a>
        </div>
    </div>
</div>
