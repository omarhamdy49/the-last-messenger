@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="users" class="w-7 h-7 text-white"></i>
            </div>
            الصحابة الكرام
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة سجلات الصحابة رضوان الله عليهم
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
        <!-- Filter Toggle Button -->
        <button @click="$dispatch('toggle-filters')"
                x-data
                class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2
                       border-2"
                :class="darkMode ?
                    'border-gray-700 bg-gray-800 text-white hover:bg-gray-700' :
                    'border-gray-300 bg-white text-gray-900 hover:bg-gray-50'">
            <i data-lucide="filter" class="w-5 h-5"></i>
            <span class="hidden sm:inline">{{ __('admin.filters') }}</span>
        </button>

        <!-- Create New Button -->
        <a href="{{ route('admin.sahaba.create') }}"
           class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 flex items-center gap-2 shadow-lg
                  bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>إضافة صحابي</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div x-data="{
    filtersOpen: {{ request()->hasAny(['gender', 'status', 'search']) ? 'true' : 'false' }}
}"
     @toggle-filters.window="filtersOpen = !filtersOpen">

    <!-- Stats Cards Row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <x-admin.stats-card
            icon="users"
            title="إجمالي الصحابة"
            :value="$stats['total'] ?? 0"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="user"
            title="الصحابة (ذكور)"
            :value="$stats['male'] ?? 0"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="user"
            title="الصحابيات (إناث)"
            :value="$stats['female'] ?? 0"
            delay="delay-200"
        />
    </div>

    <!-- Filter Section -->
    <div x-show="filtersOpen"
         x-collapse
         class="mb-6 p-6 rounded-2xl border"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <form method="GET" action="{{ route('admin.sahaba.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    بحث
                </label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ابحث باسم الصحابي..."
                       class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                       :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
            </div>

            <!-- Gender Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الجنس
                </label>
                <select name="gender"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                    <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium mb-2" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                    الحالة / المرتبة
                </label>
                <select name="status"
                        class="w-full px-4 py-2.5 rounded-xl border focus:ring-2 focus:ring-accent focus:border-transparent transition-all"
                        :class="darkMode ? 'bg-gray-800 border-gray-700 text-white' : 'bg-white border-gray-300 text-gray-900'">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach(\App\Domain\Sahaba\Enums\CompanionStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>
                            {{ $status->labelArabic() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-end gap-3">
                <a href="{{ route('admin.sahaba.index') }}"
                   class="px-4 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 border-2"
                   :class="darkMode ? 'border-gray-700 text-gray-400 hover:bg-gray-800' : 'border-gray-300 text-gray-600 hover:bg-gray-100'">
                    {{ __('admin.reset_filters') }}
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl font-semibold transition-all hover:scale-105 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white shadow-lg">
                    {{ __('admin.filter') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Sahaba Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($sahaba ?? [] as $sahabi)
        <div class="rounded-2xl border overflow-hidden transition-all hover:scale-105 hover:shadow-xl"
             :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
            <!-- Image -->
            <div class="relative aspect-square">
                @if($sahabi->getFirstMediaUrl('profile_image'))
                    <img src="{{ $sahabi->getFirstMediaUrl('profile_image', 'thumbnail') ?: $sahabi->getFirstMediaUrl('profile_image') }}"
                         alt="{{ $sahabi->getTranslation('name', 'ar') }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full bg-gradient-to-br from-[#0056b3] to-[#218838] flex items-center justify-center">
                        <i data-lucide="user" class="w-16 h-16 text-white/30"></i>
                    </div>
                @endif

                <!-- Featured Badge -->
                @if($sahabi->is_featured)
                <div class="absolute top-3 start-3">
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-500 text-white shadow-lg">
                        <i data-lucide="star" class="w-3.5 h-3.5"></i>
                        مميز
                    </span>
                </div>
                @endif

                <!-- Gender Badge -->
                <div class="absolute top-3 end-3">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold {{ $sahabi->gender === 'male' ? 'bg-blue-500' : 'bg-pink-500' }} text-white">
                        {{ $sahabi->gender === 'male' ? 'ذكر' : 'أنثى' }}
                    </span>
                </div>

                <!-- Actions Overlay -->
                <div class="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center gap-3">
                    <a href="{{ route('admin.sahaba.show', $sahabi->id) }}"
                       class="p-3 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors"
                       title="عرض">
                        <i data-lucide="eye" class="w-5 h-5"></i>
                    </a>
                    <a href="{{ route('admin.sahaba.edit', $sahabi->id) }}"
                       class="p-3 rounded-full bg-white/20 hover:bg-white/30 text-white transition-colors"
                       title="تعديل">
                        <i data-lucide="edit-2" class="w-5 h-5"></i>
                    </a>
                    <form action="{{ route('admin.sahaba.destroy', $sahabi->id) }}"
                          method="POST"
                          class="inline"
                          onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="p-3 rounded-full bg-white/20 hover:bg-red-500/50 text-white transition-colors"
                                title="حذف">
                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info -->
            <div class="p-4">
                <h3 class="text-lg font-bold mb-1" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $sahabi->getTranslation('name', 'ar') }}
                </h3>
                <p class="text-sm mb-3" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ $sahabi->getTranslation('name', 'en') }}
                </p>

                <div class="flex flex-wrap gap-2">
                    @if($sahabi->tribe)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs"
                          :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-600'">
                        <i data-lucide="flag" class="w-3 h-3"></i>
                        {{ $sahabi->tribe }}
                    </span>
                    @endif
                    @if($sahabi->status)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs"
                          :class="darkMode ? 'bg-blue-900/30 text-blue-400' : 'bg-blue-100 text-blue-800'">
                        {{ $sahabi->status->labelArabic() }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="rounded-2xl border p-12 text-center"
                 :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
                <i data-lucide="users" class="w-16 h-16 mx-auto mb-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                <p class="text-lg font-medium mb-4" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    لا يوجد صحابة حالياً
                </p>
                <a href="{{ route('admin.sahaba.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] text-white font-semibold hover:scale-105 transition-all">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                    إضافة صحابي جديد
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($sahaba) && $sahaba->hasPages())
    <div class="mt-8">
        {{ $sahaba->withQueryString()->links() }}
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
