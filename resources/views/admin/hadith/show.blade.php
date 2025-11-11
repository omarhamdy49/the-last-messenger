@extends('layouts.admin')
@section('title', 'عرض الحديث')
@section('content')
<div class="mx-auto px-6 py-8" dir="rtl">
    <div class="mb-6 flex justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.hadith.index') }}" class="p-2 rounded-lg"><i data-lucide="arrow-left" class="w-6 h-6"></i></a>
            <div>
                <h1 class="text-3xl font-bold">الحديث رقم {{ $hadith->hadith_number }}</h1>
                <p class="text-gray-600">{{ $hadith->book->getTranslation('name', 'ar') }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.hadith.edit', $hadith->id) }}" class="px-4 py-2 rounded-lg bg-blue-500 text-white">تعديل</a>
            <form action="{{ route('admin.hadith.destroy', $hadith->id) }}" method="POST" onsubmit="return confirm('حذف هذا الحديث؟')">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 text-white">حذف</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Arabic Text -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">النص العربي</h2>
                <p class="text-lg leading-relaxed" dir="rtl">{{ $hadith->arabic_text }}</p>
            </div>

            <!-- English Translation -->
            @if($hadith->getTranslation('en'))
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">الترجمة الإنجليزية</h2>
                <p class="text-lg leading-relaxed">{{ $hadith->getTranslation('en') }}</p>
            </div>
            @endif

            <!-- Narrators -->
            @if($hadith->narrators->count() > 0)
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">سلسلة الرواة (السند)</h2>
                <div class="space-y-2">
                    @foreach($hadith->narrators as $narrator)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold">{{ $loop->iteration }}</div>
                            <div>
                                <div class="font-semibold">{{ $narrator->name }}</div>
                                @if($narrator->full_name)
                                    <div class="text-sm text-gray-600">{{ $narrator->full_name }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold mb-4">التفاصيل</h2>
                <div class="space-y-3 text-sm">
                    <div><span class="font-semibold">المعرّف:</span> {{ $hadith->id }}</div>
                    <div><span class="font-semibold">الكتاب:</span> {{ $hadith->book->getTranslation('name', 'ar') }}</div>
                    @if($hadith->chapter)
                        <div><span class="font-semibold">الباب:</span> {{ $hadith->chapter->getTranslation('name', 'ar') }}</div>
                    @endif
                    <div><span class="font-semibold">الرقم:</span> {{ $hadith->hadith_number }}</div>
                    <div><span class="font-semibold">الدرجة:</span>
                        <span class="px-2 py-1 rounded text-xs font-semibold
                            @if($hadith->grade->value === 'sahih') bg-green-100 text-green-700
                            @elseif($hadith->grade->value === 'hasan') bg-blue-100 text-blue-700
                            @else bg-gray-100 text-gray-700 @endif">
                            @if($hadith->grade->value === 'sahih') صحيح
                            @elseif($hadith->grade->value === 'hasan') حسن
                            @elseif($hadith->grade->value === 'daif') ضعيف
                            @elseif($hadith->grade->value === 'mawdu') موضوع
                            @else غير معروف
                            @endif
                        </span>
                    </div>
                    <div><span class="font-semibold">المشاهدات:</span> {{ number_format($hadith->views_count) }}</div>
                    <div><span class="font-semibold">الحالة:</span>
                        <span class="{{ $hadith->active ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $hadith->active ? 'نشط' : 'غير نشط' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
