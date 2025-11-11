@extends('layouts.admin')
@section('title', 'إدارة الأحاديث')
@section('content')
<div class="space-y-6" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" dir="rtl">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">الأحاديث النبوية</h1>
            <p class="text-sm text-gray-600">إدارة مجموعة الأحاديث النبوية</p>
        </div>
        <a href="{{ route('admin.hadith.create') }}" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i> حديث جديد
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-xl p-6 shadow-lg">
            <div class="text-sm text-gray-600">الإجمالي</div>
            <div class="text-2xl font-bold">{{ number_format($stats['total'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-4">
        <form method="GET" class="grid grid-cols-4 gap-4">
            <select name="book_id" onchange="this.form.submit()" class="px-4 py-2 rounded-lg border">
                <option value="">جميع الكتب</option>
            </select>
            <select name="grade" onchange="this.form.submit()" class="px-4 py-2 rounded-lg border">
                <option value="">جميع الدرجات</option>
                <option value="sahih">صحيح</option>
                <option value="hasan">حسن</option>
                <option value="daif">ضعيف</option>
            </select>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث..." class="px-4 py-2 rounded-lg border">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-500 text-white">بحث</button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-right">المعرّف</th>
                    <th class="px-6 py-4 text-right">النص العربي</th>
                    <th class="px-6 py-4 text-right">الكتاب</th>
                    <th class="px-6 py-4 text-right">الرقم</th>
                    <th class="px-6 py-4 text-right">الدرجة</th>
                    <th class="px-6 py-4 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hadith as $item)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4">{{ $item->id }}</td>
                    <td class="px-6 py-4">
                        <div class="max-w-md truncate">{{ Str::limit($item->arabic_text, 100) }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $item->book->getTranslation('name', 'ar') }}</td>
                    <td class="px-6 py-4">{{ $item->hadith_number }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded text-xs font-semibold 
                            @if($item->grade->value === 'sahih') bg-green-100 text-green-700
                            @elseif($item->grade->value === 'hasan') bg-blue-100 text-blue-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($item->grade->value) }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('admin.hadith.show', $item->id) }}" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                            <a href="{{ route('admin.hadith.edit', $item->id) }}" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="edit" class="w-4 h-4"></i></a>
                            <form action="{{ route('admin.hadith.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('حذف هذا الحديث؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 hover:bg-red-50 rounded-lg text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">لا توجد أحاديث</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($hadith->hasPages())
        <div class="px-6 py-4 border-t">{{ $hadith->links() }}</div>
        @endif
    </div>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
