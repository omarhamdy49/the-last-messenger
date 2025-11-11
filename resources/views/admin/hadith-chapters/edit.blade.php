@extends('layouts.admin')
@section('title', 'تعديل الباب')
@section('content')
<div class="mx-auto px-6 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', activeTab: 'ar' }" dir="rtl">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.hadith-chapters.index') }}" class="p-2 rounded-lg"><i data-lucide="arrow-left" class="w-6 h-6"></i></a>
        <h1 class="text-3xl font-bold">تعديل: {{ $chapter->getTranslation('name', 'ar') }}</h1>
    </div>

    <form action="{{ route('admin.hadith-chapters.update', $chapter->id) }}" method="POST" class="space-y-6">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <div class="flex gap-2 border-b pb-4">
                <button type="button" @click="activeTab = 'ar'" :class="activeTab === 'ar' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'text-gray-600'" class="px-4 py-2 rounded-lg">العربية</button>
                <button type="button" @click="activeTab = 'en'" :class="activeTab === 'en' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'text-gray-600'" class="px-4 py-2 rounded-lg">English</button>
            </div>

            <div x-show="activeTab === 'ar'" class="space-y-4">
                <div><label>اسم الباب (العربية) *</label>
                <input type="text" name="name[ar]" value="{{ old('name.ar', $chapter->getTranslation('name', 'ar')) }}" required class="w-full px-4 py-3 rounded-lg border-2"></div>
                <div><label>الوصف (العربية)</label>
                <textarea name="description[ar]" rows="4" class="w-full px-4 py-3 rounded-lg border-2">{{ old('description.ar', $chapter->getTranslation('description', 'ar')) }}</textarea></div>
            </div>

            <div x-show="activeTab === 'en'" class="space-y-4">
                <div><label>اسم الباب (الإنجليزية) *</label>
                <input type="text" name="name[en]" value="{{ old('name.en', $chapter->getTranslation('name', 'en')) }}" required class="w-full px-4 py-3 rounded-lg border-2"></div>
                <div><label>الوصف (الإنجليزية)</label>
                <textarea name="description[en]" rows="4" class="w-full px-4 py-3 rounded-lg border-2">{{ old('description.en', $chapter->getTranslation('description', 'en')) }}</textarea></div>
            </div>

            <hr>

            <div class="grid grid-cols-2 gap-6">
                <div><label>الكتاب *</label>
                <select name="book_id" required class="w-full px-4 py-3 rounded-lg border-2">
                    @foreach($books as $book)
                        <option value="{{ $book->id }}" {{ $chapter->book_id == $book->id ? 'selected' : '' }}>{{ $book->getTranslation('name', 'ar') }}</option>
                    @endforeach
                </select></div>
                <div><label>رقم الباب *</label>
                <input type="number" name="chapter_number" value="{{ old('chapter_number', $chapter->chapter_number) }}" required class="w-full px-4 py-3 rounded-lg border-2"></div>
                <div><label>ترتيب العرض</label>
                <input type="number" name="display_order" value="{{ old('display_order', $chapter->display_order) }}" class="w-full px-4 py-3 rounded-lg border-2"></div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.hadith-chapters.index') }}" class="px-6 py-2.5 rounded-lg bg-gray-200">إلغاء</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">تحديث الباب</button>
        </div>
    </form>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
