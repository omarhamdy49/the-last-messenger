@extends('layouts.admin')
@section('title', 'إضافة باب جديد')
@section('content')
<div class="mx-auto px-6 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', activeTab: 'ar' }" dir="rtl">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.hadith-chapters.index') }}" class="p-2 rounded-lg"><i data-lucide="arrow-left" class="w-6 h-6"></i></a>
        <h1 class="text-3xl font-bold">إضافة باب جديد</h1>
    </div>

    @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li class="text-red-600">{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.hadith-chapters.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <div class="flex gap-2 border-b pb-4">
                <button type="button" @click="activeTab = 'ar'" :class="activeTab === 'ar' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'text-gray-600'" class="px-4 py-2 rounded-lg">العربية</button>
                <button type="button" @click="activeTab = 'en'" :class="activeTab === 'en' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'text-gray-600'" class="px-4 py-2 rounded-lg">English</button>
            </div>

            <div x-show="activeTab === 'ar'" class="space-y-4">
                <div><label class="block text-sm font-semibold mb-2">اسم الباب (العربية) *</label>
                <input type="text" name="name[ar]" value="{{ old('name.ar') }}" required class="w-full px-4 py-3 rounded-lg border-2"></div>
                <div><label class="block text-sm font-semibold mb-2">الوصف (العربية)</label>
                <textarea name="description[ar]" rows="4" class="w-full px-4 py-3 rounded-lg border-2">{{ old('description.ar') }}</textarea></div>
            </div>

            <div x-show="activeTab === 'en'" class="space-y-4">
                <div><label class="block text-sm font-semibold mb-2">اسم الباب (الإنجليزية) *</label>
                <input type="text" name="name[en]" value="{{ old('name.en') }}" required class="w-full px-4 py-3 rounded-lg border-2"></div>
                <div><label class="block text-sm font-semibold mb-2">الوصف (الإنجليزية)</label>
                <textarea name="description[en]" rows="4" class="w-full px-4 py-3 rounded-lg border-2">{{ old('description.en') }}</textarea></div>
            </div>

            <hr>

            <div class="grid grid-cols-2 gap-6">
                <div><label class="block text-sm font-semibold mb-2">الكتاب *</label>
                <select name="book_id" required class="w-full px-4 py-3 rounded-lg border-2">
                    <option value="">-- اختر الكتاب --</option>
                    @foreach($books as $book)
                        <option value="{{ $book->id }}" {{ old('book_id', $selectedBookId) == $book->id ? 'selected' : '' }}>{{ $book->getTranslation('name', 'ar') }}</option>
                    @endforeach
                </select></div>
                <div><label class="block text-sm font-semibold mb-2">رقم الباب *</label>
                <input type="number" name="chapter_number" value="{{ old('chapter_number', 1) }}" required min="1" class="w-full px-4 py-3 rounded-lg border-2"></div>
                <div><label class="block text-sm font-semibold mb-2">ترتيب العرض</label>
                <input type="number" name="display_order" value="{{ old('display_order', 0) }}" class="w-full px-4 py-3 rounded-lg border-2"></div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.hadith-chapters.index') }}" class="px-6 py-2.5 rounded-lg bg-gray-200">إلغاء</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">إنشاء الباب</button>
        </div>
    </form>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
