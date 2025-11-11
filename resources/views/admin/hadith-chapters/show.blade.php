@extends('layouts.admin')
@section('title', 'عرض الباب')
@section('content')
<div class="mx-auto px-6 py-8" dir="rtl">
    <div class="mb-6 flex justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.hadith-chapters.index') }}" class="p-2 rounded-lg"><i data-lucide="arrow-left" class="w-6 h-6"></i></a>
            <div>
                <h1 class="text-3xl font-bold">{{ $chapter->chapter_number }}. {{ $chapter->getTranslation('name', 'ar') }}</h1>
                <p class="text-gray-600">{{ $chapter->getTranslation('name', 'en') }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.hadith-chapters.edit', $chapter->id) }}" class="px-4 py-2 rounded-lg bg-blue-500 text-white">تعديل</a>
            <form action="{{ route('admin.hadith-chapters.destroy', $chapter->id) }}" method="POST" onsubmit="return confirm('حذف هذا الباب؟')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 text-white">حذف</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold mb-4">معلومات الباب</h2>
            <div class="space-y-3">
                <div><span class="font-semibold">الكتاب:</span> {{ $chapter->book->getTranslation('name', 'ar') }}</div>
                <div><span class="font-semibold">رقم الباب:</span> {{ $chapter->chapter_number }}</div>
                <div><span class="font-semibold">ترتيب العرض:</span> {{ $chapter->display_order }}</div>
                @if($chapter->getTranslation('description', 'ar'))
                    <div class="mt-4">
                        <h3 class="font-semibold mb-2">الوصف (العربية):</h3>
                        <p class="text-gray-700">{{ $chapter->getTranslation('description', 'ar') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between mb-4">
                <h2 class="text-xl font-bold">الأحاديث</h2>
                <a href="{{ route('admin.hadith.create', ['chapter_id' => $chapter->id]) }}" class="text-blue-500 hover:underline">إضافة حديث</a>
            </div>
            <div class="text-center text-2xl font-bold text-blue-600">{{ $chapter->hadiths->count() }}</div>
            <p class="text-center text-sm text-gray-600">إجمالي الأحاديث</p>
        </div>
    </div>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
