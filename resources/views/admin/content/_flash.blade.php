{{-- Session flash banner shared by the Content admin pages --}}
@if (session('success'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-green-500/30 bg-green-500/10 px-4 py-3 text-green-600 dark:text-green-400"
         role="status">
        <i data-lucide="check-circle" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-red-600 dark:text-red-400"
         role="alert">
        <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
        <span class="text-sm font-semibold">{{ session('error') }}</span>
    </div>
@endif
