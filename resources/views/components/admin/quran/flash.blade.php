{{-- Flash messages and validation errors for Quran admin pages. --}}
@if (session('success'))
    <div class="mb-4 rounded-xl border border-green-300 bg-green-50 p-4 text-sm text-green-800" role="status">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <ul class="list-disc ps-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
