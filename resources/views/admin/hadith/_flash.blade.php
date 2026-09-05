{{-- Shared flash block: destroy() refuses on ON DELETE RESTRICT and returns an
     `error`, which would otherwise vanish silently. --}}
@if(session('success'))
<div class="mb-6 p-4 rounded-xl border bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-800 dark:text-emerald-300 flex items-center gap-2">
    <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
    <span>{{ session('success') }}</span>
</div>
@endif
@if(session('error'))
<div class="mb-6 p-4 rounded-xl border bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300 flex items-start gap-2">
    <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
    <span>{{ session('error') }}</span>
</div>
@endif
@if($errors->any())
<div class="mb-6 p-4 rounded-xl border bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300">
    <div class="flex items-center gap-2 font-semibold mb-2">
        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        <span>راجع الحقول التالية:</span>
    </div>
    <ul class="list-disc ms-6 space-y-1 text-sm">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif
