@extends('layouts.admin')

@section('title', __('articles.edit') . ' - ' . ($article->locales->first()->title ?? 'Untitled'))

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
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold gradient-text">
                        {{ __('articles.edit') }}
                    </h1>

                    {{-- Status Badges --}}
                    @if($article->is_featured)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                            <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
                            {{ __('articles.fields.is_featured') }}
                        </span>
                    @endif

                    @if($article->is_pinned)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400">
                            <i data-lucide="pin" class="w-3.5 h-3.5"></i>
                            {{ __('articles.fields.is_pinned') }}
                        </span>
                    @endif
                </div>

                <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    {{ $article->locales->first()->title ?? __('common.untitled') }}
                </p>
            </div>

            {{-- Quick Actions --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.articles.show', $article) }}"
                   class="px-4 py-2 rounded-lg border transition-colors flex items-center gap-2"
                   :class="darkMode ?
                       'border-gray-600 hover:bg-gray-700' :
                       'border-gray-300 hover:bg-gray-50'">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span>{{ __('common.view') }}</span>
                </a>

                <form action="{{ route('admin.articles.destroy', $article) }}"
                      method="POST"
                      class="inline"
                      onsubmit="return confirm('{{ __('articles.confirm.delete') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 rounded-lg border border-red-300 hover:bg-red-50 dark:border-red-700 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition-colors flex items-center gap-2">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        <span>{{ __('common.delete') }}</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Meta Info --}}
        <div class="flex items-center gap-4 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
            <span class="flex items-center gap-1">
                <i data-lucide="calendar" class="w-4 h-4"></i>
                {{ __('common.created') }}: {{ $article->created_at->diffForHumans() }}
            </span>
            <span class="flex items-center gap-1">
                <i data-lucide="clock" class="w-4 h-4"></i>
                {{ __('common.updated') }}: {{ $article->updated_at->diffForHumans() }}
            </span>
            <span class="flex items-center gap-1">
                <i data-lucide="eye" class="w-4 h-4"></i>
                {{ number_format($article->views) }} {{ __('articles.fields.views') }}
            </span>
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

    {{-- Article Wizard Form --}}
    @include('admin.articles._form-wizard', [
        'article' => $article,
        'formArticle' => $formArticle,
        'categories' => $categories,
        'writers' => $writers,
        'tags' => $tags,
        'locales' => $locales
    ])

</div>

{{-- Media Picker Modal --}}
<x-admin.media-picker-modal />

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
