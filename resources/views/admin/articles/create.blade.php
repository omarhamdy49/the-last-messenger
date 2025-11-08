@extends('layouts.admin')

@section('title', __('articles.create'))

@section('content')
<div class="mx-auto px-6 py-8">

    {{-- Page Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.articles.index') }}"
               class="p-2 rounded-lg transition-all hover:scale-110"
               :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </a>

            <div class="flex-1">
                <h1 class="text-3xl font-bold gradient-text mb-2">
                    {{ __('articles.create') }}
                </h1>
                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ __('articles.description') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Validation Errors --}}
    @if($errors->any())
        <div class="mb-6 p-4 rounded-lg border-l-4 border-red-500 animate-fadeInUp"
             :class="darkMode ? 'bg-red-900/20 border-red-500' : 'bg-red-50 border-red-500'">
            <div class="flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-red-500 mb-2">
                        {{ __('common.please_fix_errors') }}
                    </h3>
                    <ul class="list-disc list-inside space-y-1 text-sm"
                        :class="darkMode ? 'text-red-400' : 'text-red-600'">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Success Alert --}}
    @if(session('success'))
        <div class="mb-6 p-4 rounded-lg border-l-4 border-green-500 animate-fadeInUp"
             :class="darkMode ? 'bg-green-900/20' : 'bg-green-50'">
            <div class="flex items-start gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <p class="font-semibold"
                       :class="darkMode ? 'text-green-400' : 'text-green-600'">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Article Form --}}
    @include('admin.articles._form-new', [
        'article' => null,
        'categories' => $categories,
        'writers' => $writers,
        'tags' => $tags,
        'locales' => ['ar', 'en', 'fr', 'tr', 'ur']
    ])

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endpush
