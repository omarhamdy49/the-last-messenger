@extends('layouts.admin')
@section('title', 'تعديل الراوي')
@section('content')
<div class="mx-auto px-6 py-8" dir="rtl">
    <div class="mb-6 flex items-center gap-4">
        <a href="{{ route('admin.hadith-narrators.index') }}" class="p-2 rounded-lg"><i data-lucide="arrow-left" class="w-6 h-6"></i></a>
        <h1 class="text-3xl font-bold">تعديل: {{ $narrator->name }}</h1>
    </div>

    <form action="{{ route('admin.hadith-narrators.update', $narrator->id) }}" method="POST" class="space-y-6">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl shadow-lg p-6 space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div><label class="block font-semibold mb-2">الاسم (المختصر) *</label>
                <input type="text" name="name" value="{{ old('name', $narrator->name) }}" required class="w-full px-4 py-3 rounded-lg border-2"></div>

                <div><label class="block font-semibold mb-2">الاسم الكامل</label>
                <input type="text" name="full_name" value="{{ old('full_name', $narrator->full_name) }}" class="w-full px-4 py-3 rounded-lg border-2"></div>

                <div><label class="block font-semibold mb-2">الدرجة</label>
                <select name="grade" class="w-full px-4 py-3 rounded-lg border-2">
                    <option value="">-- اختر الدرجة --</option>
                    <option value="thiqah" {{ $narrator->grade?->value === 'thiqah' ? 'selected' : '' }}>ثقة</option>
                    <option value="saduq" {{ $narrator->grade?->value === 'saduq' ? 'selected' : '' }}>صدوق</option>
                    <option value="daif" {{ $narrator->grade?->value === 'daif' ? 'selected' : '' }}>ضعيف</option>
                    <option value="matruk" {{ $narrator->grade?->value === 'matruk' ? 'selected' : '' }}>متروك</option>
                </select></div>

                <div><label class="block font-semibold mb-2">العصر</label>
                <input type="text" name="era" value="{{ old('era', $narrator->era) }}" class="w-full px-4 py-3 rounded-lg border-2"></div>

                <div><label class="block font-semibold mb-2">سنة الميلاد</label>
                <input type="number" name="birth_year" value="{{ old('birth_year', $narrator->birth_year) }}" class="w-full px-4 py-3 rounded-lg border-2"></div>

                <div><label class="block font-semibold mb-2">سنة الوفاة</label>
                <input type="number" name="death_year" value="{{ old('death_year', $narrator->death_year) }}" class="w-full px-4 py-3 rounded-lg border-2"></div>
            </div>

            <div><label class="block font-semibold mb-2">السيرة الذاتية</label>
            <textarea name="biography" rows="4" class="w-full px-4 py-3 rounded-lg border-2">{{ old('biography', $narrator->biography) }}</textarea></div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.hadith-narrators.index') }}" class="px-6 py-2.5 rounded-lg bg-gray-200">إلغاء</a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">تحديث الراوي</button>
        </div>
    </form>
</div>
@push('scripts'><script>lucide.createIcons();</script>@endpush
@endsection
