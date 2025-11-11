@extends('layouts.admin')
@section('title', 'عرض كتاب الحديث')
@section('content')
<div class="mx-auto px-6 py-8" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" dir="rtl">
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.hadith-books.index') }}" class="p-2 rounded-lg">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold">{{ $book->getTranslation('name', 'ar') }}</h1>
                <p class="text-gray-600">{{ $book->getTranslation('name', 'en') }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.hadith-books.edit', $book->id) }}" class="px-4 py-2 rounded-lg bg-blue-500 text-white">تعديل</a>
            <form action="{{ route('admin.hadith-books.destroy', $book->id) }}" method="POST" onsubmit="return confirm('حذف هذا الكتاب؟')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 text-white">حذف</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Book Info -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h2 class="text-xl font-bold mb-4">معلومات الكتاب</h2>
            <div class="grid grid-cols-2 gap-4">
                <div><span class="font-semibold">المعرف:</span> {{ $book->slug }}</div>
                <div><span class="font-semibold">المؤلف:</span> {{ $book->author_name ?? 'غير متوفر' }}</div>
                <div><span class="font-semibold">المجموعة:</span> {{ $book->collection->getTranslation('name', 'ar') ?? 'غير متوفر' }}</div>
                <div><span class="font-semibold">إجمالي الأحاديث:</span> {{ number_format($book->total_hadith_count) }}</div>
                <div><span class="font-semibold">الحالة:</span>
                    @if($book->active)
                        <span class="text-green-600">نشط</span>
                    @else
                        <span class="text-gray-500">غير نشط</span>
                    @endif
                </div>
                <div><span class="font-semibold">ترتيب العرض:</span> {{ $book->display_order }}</div>
            </div>

            @if($book->getTranslation('description', 'ar'))
            <div class="mt-6">
                <h3 class="font-semibold mb-2">الوصف (العربية):</h3>
                <p class="text-gray-700">{{ $book->getTranslation('description', 'ar') }}</p>
            </div>
            @endif

            @if($book->getTranslation('description', 'en'))
            <div class="mt-4">
                <h3 class="font-semibold mb-2">الوصف (الإنجليزية):</h3>
                <p class="text-gray-700">{{ $book->getTranslation('description', 'en') }}</p>
            </div>
            @endif
        </div>

        <!-- Chapters -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">الأبواب</h2>
                <a href="{{ route('admin.hadith-chapters.create', ['book_id' => $book->id]) }}" class="text-blue-500 hover:underline">إضافة باب</a>
            </div>
            @if($book->chapters->count() > 0)
                <div class="space-y-2">
                    @foreach($book->chapters->take(10) as $chapter)
                        <a href="{{ route('admin.hadith-chapters.show', $chapter->id) }}" class="block p-3 rounded-lg hover:bg-gray-50">
                            <div class="font-semibold">{{ $chapter->chapter_number }}. {{ $chapter->getTranslation('name', 'ar') }}</div>
                            <div class="text-sm text-gray-600">{{ $chapter->hadiths->count() }} أحاديث</div>
                        </a>
                    @endforeach
                </div>
                @if($book->chapters->count() > 10)
                    <a href="{{ route('admin.hadith-chapters.index', ['book_id' => $book->id]) }}" class="block mt-4 text-center text-blue-500 hover:underline">عرض جميع الأبواب ({{ $book->chapters->count() }})</a>
                @endif
            @else
                <p class="text-gray-500 text-center py-4">لا توجد أبواب بعد</p>
            @endif
        </div>
    </div>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
