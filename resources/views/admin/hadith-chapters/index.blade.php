@extends('layouts.admin')
@section('title', 'أبواب الحديث')
@section('content')
<div class="space-y-6" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" dir="rtl">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">أبواب الحديث</h1>
            <p class="text-sm text-gray-600">إدارة أبواب الأحاديث ضمن الكتب</p>
        </div>
        <a href="{{ route('admin.hadith-chapters.create') }}" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5 inline"></i> باب جديد
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-4">
        <form method="GET" class="flex gap-4">
            <select name="book_id" onchange="this.form.submit()" class="px-4 py-2 rounded-lg border">
                <option value="">جميع الكتب</option>
                @foreach($books as $book)
                    <option value="{{ $book->id }}" {{ request('book_id') == $book->id ? 'selected' : '' }}>{{ $book->getTranslation('name', 'ar') }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-right">#</th>
                    <th class="px-6 py-4 text-right">اسم الباب</th>
                    <th class="px-6 py-4 text-right">الكتاب</th>
                    <th class="px-6 py-4 text-right">عدد الأحاديث</th>
                    <th class="px-6 py-4 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($chapters as $chapter)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $chapter->chapter_number }}</td>
                    <td class="px-6 py-4">
                        <div class="font-semibold">{{ $chapter->getTranslation('name', 'ar') }}</div>
                        <div class="text-sm text-gray-600">{{ $chapter->getTranslation('name', 'en') }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $chapter->book->getTranslation('name', 'ar') }}</td>
                    <td class="px-6 py-4">{{ $chapter->hadiths->count() }}</td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('admin.hadith-chapters.show', $chapter->id) }}" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                            <a href="{{ route('admin.hadith-chapters.edit', $chapter->id) }}" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="edit" class="w-4 h-4"></i></a>
                            <form action="{{ route('admin.hadith-chapters.destroy', $chapter->id) }}" method="POST" class="inline" onsubmit="return confirm('هل تريد حذف هذا الباب؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 hover:bg-red-50 rounded-lg text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">لا توجد أبواب</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($chapters->hasPages())
        <div class="px-6 py-4 border-t">{{ $chapters->links() }}</div>
        @endif
    </div>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
