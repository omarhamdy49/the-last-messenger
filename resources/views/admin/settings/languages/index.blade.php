@extends('layouts.admin')

@section('page-header')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <!-- Page Title -->
    <div>
        <div class="flex items-center gap-2 text-sm mb-2" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <a href="#" class="hover:text-accent transition-colors">الإعدادات</a>
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
            <span>اللغات</span>
        </div>
        <h1 class="text-3xl font-bold gradient-text flex items-center gap-3">
            <div class="p-3 rounded-xl bg-gradient-to-r from-[#0056b3] to-[#218838] shadow-lg">
                <i data-lucide="languages" class="w-7 h-7 text-white"></i>
            </div>
            إعدادات اللغات
        </h1>
        <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            إدارة اللغات المدعومة في المنصة
        </p>
    </div>
</div>
@endsection

@section('content')
<div x-data="languageSettings()">

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <x-admin.stats-card
            icon="languages"
            title="إجمالي اللغات"
            :value="count($languages)"
            :gradient="true"
        />

        <x-admin.stats-card
            icon="check-circle"
            title="اللغات النشطة"
            :value="collect($languages)->where('active', true)->count()"
            delay="delay-100"
        />

        <x-admin.stats-card
            icon="star"
            title="اللغة الافتراضية"
            :value="$languages[$defaultLanguage]['native_name'] ?? $defaultLanguage"
            delay="delay-200"
        />
    </div>

    <!-- Languages List -->
    <div class="rounded-2xl border overflow-hidden"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <div class="p-6 border-b" :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
            <h2 class="text-xl font-bold flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
                <i data-lucide="list" class="w-5 h-5 text-accent"></i>
                اللغات المتاحة
            </h2>
        </div>

        <div class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-200'">
            @foreach($languages as $code => $lang)
            <div class="p-6 flex items-center justify-between transition-colors"
                 :class="darkMode ? 'hover:bg-gray-800/50' : 'hover:bg-gray-50'">
                <div class="flex items-center gap-4">
                    <!-- Flag -->
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl"
                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                        {{ $lang['flag'] ?? '' }}
                    </div>

                    <!-- Language Info -->
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold" :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $lang['native_name'] }}
                            </h3>
                            @if($code === $defaultLanguage)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                                <i data-lucide="star" class="w-3 h-3"></i>
                                افتراضية
                            </span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                {{ $lang['name'] }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded font-mono"
                                  :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                                {{ strtoupper($code) }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded"
                                  :class="darkMode ? 'bg-gray-800 text-gray-400' : 'bg-gray-100 text-gray-500'">
                                {{ $lang['direction'] === 'rtl' ? 'RTL' : 'LTR' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3">
                    <!-- Active/Inactive Toggle -->
                    <button @click="toggleLanguage('{{ $code }}')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2"
                            :class="{{ $lang['active'] ? 'true' : 'false' }} ? 'bg-green-500' : (darkMode ? 'bg-gray-700' : 'bg-gray-300')"
                            {{ $code === $defaultLanguage ? 'disabled' : '' }}>
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                              :class="{{ $lang['active'] ? 'true' : 'false' }} ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>

                    <!-- Set as Default -->
                    @if($code !== $defaultLanguage && $lang['active'])
                    <button @click="setDefault('{{ $code }}')"
                            class="p-2 rounded-lg transition-colors"
                            :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'"
                            title="تعيين كلغة افتراضية">
                        <i data-lucide="star" class="w-4 h-4"></i>
                    </button>
                    @endif

                    <!-- Order -->
                    <span class="text-sm font-mono w-8 text-center" :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        #{{ $lang['order'] ?? 0 }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Notes -->
    <div class="mt-6 rounded-2xl border p-6"
         :class="darkMode ? 'bg-gray-900/50 border-gray-800' : 'bg-white border-gray-200'">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2" :class="darkMode ? 'text-white' : 'text-gray-900'">
            <i data-lucide="info" class="w-5 h-5 text-accent"></i>
            ملاحظات
        </h3>
        <ul class="space-y-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <li class="flex items-start gap-2">
                <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0"></i>
                <span>اللغة الافتراضية لا يمكن تعطيلها أو حذفها</span>
            </li>
            <li class="flex items-start gap-2">
                <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0"></i>
                <span>تعطيل لغة لا يحذف المحتوى المترجم بها، فقط يخفيه عن الزوار</span>
            </li>
            <li class="flex items-start gap-2">
                <i data-lucide="check" class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0"></i>
                <span>اللغات ذات الاتجاه RTL (من اليمين لليسار): العربية والأردية</span>
            </li>
        </ul>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function languageSettings() {
        return {
            async toggleLanguage(code) {
                try {
                    const response = await fetch(`{{ url('admin/settings/languages') }}/${code}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.reload();
                    } else {
                        alert(result.message || 'حدث خطأ');
                    }
                } catch (error) {
                    alert('حدث خطأ أثناء تحديث حالة اللغة');
                }
            },

            async setDefault(code) {
                if (!confirm('هل تريد تعيين هذه اللغة كلغة افتراضية؟')) return;

                try {
                    const response = await fetch(`{{ url('admin/settings/languages') }}/${code}/set-default`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const result = await response.json();
                    if (result.success) {
                        window.location.reload();
                    } else {
                        alert(result.message || 'حدث خطأ');
                    }
                } catch (error) {
                    alert('حدث خطأ أثناء تعيين اللغة الافتراضية');
                }
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
    });
</script>
@endpush
