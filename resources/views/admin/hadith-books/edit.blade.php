@extends('layouts.admin')
@section('title', 'تعديل كتاب الحديث')
@section('content')
<div class="mx-auto px-6 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true', activeTab: 'ar' }" dir="rtl">
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.hadith-books.index') }}" class="p-2 rounded-lg">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </a>
            <h1 class="text-3xl font-bold">تعديل: {{ $book->getTranslation('name', 'ar') }}</h1>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li class="text-red-600">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.hadith-books.update', $book->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <!-- Language Tabs -->
            <div class="flex gap-2 border-b pb-4">
                <button type="button" @click="activeTab = 'ar'" :class="activeTab === 'ar' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'text-gray-600'" class="px-4 py-2 rounded-lg">العربية</button>
                <button type="button" @click="activeTab = 'en'" :class="activeTab === 'en' ? 'bg-gradient-to-r from-[#0056b3] to-[#218838] text-white' : 'text-gray-600'" class="px-4 py-2 rounded-lg">English</button>
            </div>

            <!-- Arabic -->
            <div x-show="activeTab === 'ar'" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">اسم الكتاب (العربية) *</label>
                    <input type="text" name="name[ar]" value="{{ old('name.ar', $book->getTranslation('name', 'ar')) }}" required class="w-full px-4 py-3 rounded-lg border-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">الوصف (العربية)</label>
                    <textarea name="description[ar]" rows="4" class="w-full px-4 py-3 rounded-lg border-2">{{ old('description.ar', $book->getTranslation('description', 'ar')) }}</textarea>
                </div>
            </div>

            <!-- English -->
            <div x-show="activeTab === 'en'" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">اسم الكتاب (الإنجليزية) *</label>
                    <input type="text" name="name[en]" value="{{ old('name.en', $book->getTranslation('name', 'en')) }}" required class="w-full px-4 py-3 rounded-lg border-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">الوصف (الإنجليزية)</label>
                    <textarea name="description[en]" rows="4" class="w-full px-4 py-3 rounded-lg border-2">{{ old('description.en', $book->getTranslation('description', 'en')) }}</textarea>
                </div>
            </div>

            <hr>

            <!-- Other Fields -->
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-2">المعرف (Slug) *</label>
                    <input type="text" name="slug" value="{{ old('slug', $book->slug) }}" required class="w-full px-4 py-3 rounded-lg border-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">اسم المؤلف</label>
                    <input type="text" name="author_name" value="{{ old('author_name', $book->author_name) }}" class="w-full px-4 py-3 rounded-lg border-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">المجموعة</label>
                    <select name="collection_id" class="w-full px-4 py-3 rounded-lg border-2">
                        <option value="">-- اختر --</option>
                        @foreach($collections as $collection)
                            <option value="{{ $collection->id }}" {{ $book->collection_id == $collection->id ? 'selected' : '' }}>{{ $collection->getTranslation('name', 'ar') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">ترتيب العرض</label>
                    <input type="number" name="display_order" value="{{ old('display_order', $book->display_order) }}" class="w-full px-4 py-3 rounded-lg border-2">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="active" value="1" {{ $book->active ? 'checked' : '' }} class="w-4 h-4">
                    <label class="ml-2">نشط</label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.hadith-books.index') }}" class="px-6 py-2.5 rounded-lg bg-gray-200">إلغاء</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">تحديث الكتاب</button>
        </div>
    </form>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
