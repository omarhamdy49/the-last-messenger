@extends('layouts.admin')
@section('title', 'عرض الراوي')
@section('content')
<div class="mx-auto px-6 py-8" dir="rtl">
    <div class="mb-6 flex justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.hadith-narrators.index') }}" class="p-2 rounded-lg"><i data-lucide="arrow-left" class="w-6 h-6"></i></a>
            <div>
                <h1 class="text-3xl font-bold">{{ $narrator->name }}</h1>
                @if($narrator->full_name)
                    <p class="text-gray-600">{{ $narrator->full_name }}</p>
                @endif
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.hadith-narrators.edit', $narrator->id) }}" class="px-4 py-2 rounded-lg bg-blue-500 text-white">تعديل</a>
            <form action="{{ route('admin.hadith-narrators.destroy', $narrator->id) }}" method="POST" onsubmit="return confirm('حذف هذا الراوي؟')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 text-white">حذف</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">المعلومات</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div><span class="font-semibold">الاسم:</span> {{ $narrator->name }}</div>
                    <div><span class="font-semibold">الاسم الكامل:</span> {{ $narrator->full_name ?? 'غير متوفر' }}</div>
                    <div><span class="font-semibold">الدرجة:</span>
                        @if($narrator->grade)
                            <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">{{ ucfirst($narrator->grade->value) }}</span>
                        @else
                            غير متوفر
                        @endif
                    </div>
                    <div><span class="font-semibold">العصر:</span> {{ $narrator->era ?? 'غير متوفر' }}</div>
                    <div><span class="font-semibold">الميلاد:</span> {{ $narrator->birth_year ? $narrator->birth_year . ' هـ' : 'غير متوفر' }}</div>
                    <div><span class="font-semibold">الوفاة:</span> {{ $narrator->death_year ? $narrator->death_year . ' هـ' : 'غير متوفر' }}</div>
                </div>
            </div>

            @if($narrator->biography)
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">السيرة الذاتية</h2>
                <p class="text-gray-700 leading-relaxed">{{ $narrator->biography }}</p>
            </div>
            @endif
        </div>

        <!-- Stats -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">الإحصائيات</h2>
                <div class="space-y-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600">0</div>
                        <div class="text-sm text-gray-600">الأحاديث المروية</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
