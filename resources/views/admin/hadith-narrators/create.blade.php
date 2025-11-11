@extends('layouts.admin')
@section('title', 'إضافة راوٍ')
@section('content')
<div class="mx-auto px-6 py-8" dir="rtl">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.hadith-narrators.index') }}" class="p-2 rounded-lg"><i data-lucide="arrow-left" class="w-6 h-6"></i></a>
        <h1 class="text-3xl font-bold">إضافة راوٍ جديد</h1>
    </div>

    @if($errors->any())
    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500">
        <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li class="text-red-600">{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.hadith-narrators.store') }}" method="POST" class="space-y-6">
        @csrf
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block font-semibold mb-2">الاسم (المختصر) *</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 rounded-lg border-2" placeholder="أبو هريرة"></div>

                <div><label class="block font-semibold mb-2">الاسم الكامل</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" class="w-full px-4 py-3 rounded-lg border-2" placeholder="عبد الرحمن بن صخر"></div>

                <div><label class="block font-semibold mb-2">الدرجة</label>
                <select name="grade" class="w-full px-4 py-3 rounded-lg border-2">
                    <option value="">-- اختر الدرجة --</option>
                    <option value="thiqah">ثقة</option>
                    <option value="saduq">صدوق</option>
                    <option value="daif">ضعيف</option>
                    <option value="matruk">متروك</option>
                </select></div>

                <div><label class="block font-semibold mb-2">العصر</label>
                <input type="text" name="era" value="{{ old('era') }}" class="w-full px-4 py-3 rounded-lg border-2" placeholder="الصحابة، التابعون، إلخ"></div>

                <div><label class="block font-semibold mb-2">سنة الميلاد</label>
                <input type="number" name="birth_year" value="{{ old('birth_year') }}" class="w-full px-4 py-3 rounded-lg border-2"></div>

                <div><label class="block font-semibold mb-2">سنة الوفاة</label>
                <input type="number" name="death_year" value="{{ old('death_year') }}" class="w-full px-4 py-3 rounded-lg border-2"></div>
            </div>

            <div><label class="block font-semibold mb-2">السيرة الذاتية</label>
            <textarea name="biography" rows="4" class="w-full px-4 py-3 rounded-lg border-2" placeholder="نبذة مختصرة">{{ old('biography') }}</textarea></div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.hadith-narrators.index') }}" class="px-6 py-2.5 rounded-lg bg-gray-200">إلغاء</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">إضافة الراوي</button>
        </div>
    </form>
</div>
@push('scripts')<script>lucide.createIcons();</script>@endpush
@endsection
