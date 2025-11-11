@extends('layouts.admin')
@section('title', 'رواة الحديث')
@section('content')
<div class="space-y-6" dir="rtl">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">رواة الحديث</h1>
            <p class="text-sm text-gray-600">إدارة رواة الأحاديث</p>
        </div>
        <a href="{{ route('admin.hadith-narrators.create') }}" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i> راوٍ جديد
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-4">
        <form method="GET" class="flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث عن الرواة..." class="flex-1 px-4 py-2 rounded-lg border">
            <button type="submit" class="px-6 py-2 rounded-lg bg-blue-500 text-white">بحث</button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-right">الاسم</th>
                    <th class="px-6 py-4 text-right">الاسم الكامل</th>
                    <th class="px-6 py-4 text-right">الدرجة</th>
                    <th class="px-6 py-4 text-right">العصر</th>
                    <th class="px-6 py-4 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($narrators as $narrator)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-6 py-4 font-semibold">{{ $narrator->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ Str::limit($narrator->full_name ?? '', 50) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-700">
                            {{ $narrator->grade ? ucfirst($narrator->grade->value) : 'غير متوفر' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $narrator->era ?? 'غير متوفر' }}</td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('admin.hadith-narrators.show', $narrator->id) }}" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="eye" class="w-4 h-4"></i></a>
                            <a href="{{ route('admin.hadith-narrators.edit', $narrator->id) }}" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="edit" class="w-4 h-4"></i></a>
                            <form action="{{ route('admin.hadith-narrators.destroy', $narrator->id) }}" method="POST" class="inline" onsubmit="return confirm('حذف هذا الراوي؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 hover:bg-red-50 rounded-lg text-red-500"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">لا يوجد رواة</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($narrators->hasPages())
        <div class="px-6 py-4 border-t">{{ $narrators->links() }}</div>
        @endif
    </div>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
