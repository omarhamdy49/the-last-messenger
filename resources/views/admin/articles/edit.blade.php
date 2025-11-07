@extends('layouts.admin')

@section('title', 'Edit Article - ' . $article->title)

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.articles.index') }}"
               class="p-2 rounded-lg transition-all hover:scale-110"
               :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                <i data-lucide="arrow-left" class="w-6 h-6"></i>
            </a>

            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold gradient-text">
                        Edit Article
                    </h1>

                    <!-- Status Badge -->
                    @php
                        $statusConfig = [
                            'draft' => ['color' => 'gray', 'icon' => 'file-edit', 'label' => 'Draft'],
                            'published' => ['color' => 'green', 'icon' => 'check-circle', 'label' => 'Published'],
                            'scheduled' => ['color' => 'blue', 'icon' => 'clock', 'label' => 'Scheduled'],
                            'archived' => ['color' => 'orange', 'icon' => 'archive', 'label' => 'Archived'],
                        ];
                        $status = $statusConfig[$article->status] ?? $statusConfig['draft'];
                    @endphp

                    <span class="px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 bg-{{ $status['color'] }}-100 text-{{ $status['color'] }}-700">
                        <i data-lucide="{{ $status['icon'] }}" class="w-3.5 h-3.5"></i>
                        {{ $status['label'] }}
                    </span>

                    @if($article->is_featured)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                            <i data-lucide="star" class="w-3.5 h-3.5"></i>
                            Featured
                        </span>
                    @endif

                    @if($article->is_pinned)
                        <span class="px-3 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 bg-purple-100 text-purple-700">
                            <i data-lucide="pin" class="w-3.5 h-3.5"></i>
                            Pinned
                        </span>
                    @endif
                </div>

                <p class="text-sm"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Last updated: {{ $article->updated_at->diffForHumans() }}
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.articles.show', $article) }}"
                   target="_blank"
                   class="px-4 py-2 rounded-lg font-semibold transition-all hover:scale-105 inline-flex items-center gap-2 border"
                   :class="darkMode ?
                       'border-gray-700 text-gray-300 hover:bg-gray-800' :
                       'border-gray-300 text-gray-700 hover:bg-gray-50'">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span>View</span>
                </a>

                @if($article->status === 'published')
                    <a href="{{ route('articles.show', $article) }}"
                       target="_blank"
                       class="px-4 py-2 rounded-lg font-semibold transition-all hover:scale-105 inline-flex items-center gap-2 bg-gradient-to-r from-[#0056b3] to-[#218838] text-white">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                        <span>View Live</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Article Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="glass rounded-lg p-4"
                 :class="darkMode ? 'glass-dark' : 'glass-light'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                        <i data-lucide="eye" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ number_format($article->views_count ?? 0) }}
                        </p>
                        <p class="text-xs"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Views
                        </p>
                    </div>
                </div>
            </div>

            <div class="glass rounded-lg p-4"
                 :class="darkMode ? 'glass-dark' : 'glass-light'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                        <i data-lucide="heart" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ number_format($article->likes_count ?? 0) }}
                        </p>
                        <p class="text-xs"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Likes
                        </p>
                    </div>
                </div>
            </div>

            <div class="glass rounded-lg p-4"
                 :class="darkMode ? 'glass-dark' : 'glass-light'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="message-circle" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ number_format($article->comments_count ?? 0) }}
                        </p>
                        <p class="text-xs"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Comments
                        </p>
                    </div>
                </div>
            </div>

            <div class="glass rounded-lg p-4"
                 :class="darkMode ? 'glass-dark' : 'glass-light'">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center">
                        <i data-lucide="share-2" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                            {{ number_format($article->shares_count ?? 0) }}
                        </p>
                        <p class="text-xs"
                           :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            Shares
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert for validation errors -->
    @if($errors->any())
        <div class="mb-6 p-4 rounded-lg border-l-4 border-red-500 animate-fadeInUp"
             :class="darkMode ? 'bg-red-900/20 border-red-500' : 'bg-red-50 border-red-500'">
            <div class="flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <h3 class="font-bold text-red-500 mb-2">
                        Please fix the following errors:
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

    <!-- Success Alert -->
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

    <!-- Revision History -->
    @if($article->revisions && $article->revisions->count() > 0)
        <div class="mb-6 p-4 rounded-lg border-l-4 border-amber-500"
             :class="darkMode ? 'bg-amber-900/20' : 'bg-amber-50'"
             x-data="{ showHistory: false }">
            <div class="flex items-start gap-3">
                <i data-lucide="history" class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5"></i>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold"
                            :class="darkMode ? 'text-amber-400' : 'text-amber-600'">
                            Revision History ({{ $article->revisions->count() }} versions)
                        </h3>
                        <button @click="showHistory = !showHistory"
                                class="text-sm font-medium"
                                :class="darkMode ? 'text-amber-400 hover:text-amber-300' : 'text-amber-600 hover:text-amber-700'">
                            <span x-text="showHistory ? 'Hide' : 'Show'"></span>
                        </button>
                    </div>

                    <div x-show="showHistory" x-collapse>
                        <div class="space-y-2 mt-3">
                            @foreach($article->revisions->take(5) as $revision)
                                <div class="flex items-center justify-between p-2 rounded-lg"
                                     :class="darkMode ? 'bg-gray-800/50' : 'bg-white/50'">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono"
                                              :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ $revision->created_at->format('M d, Y H:i') }}
                                        </span>
                                        <span class="text-xs"
                                              :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                                            by {{ $revision->user->name }}
                                        </span>
                                    </div>
                                    <a href="{{ route('admin.articles.revision', [$article, $revision]) }}"
                                       class="text-xs font-medium text-accent hover:underline">
                                        View
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Article Form -->
    @include('admin.articles._form', [
        'article' => $article,
        'categories' => $categories ?? [],
        'tags' => $tags ?? [],
        'authors' => $authors ?? [],
    ])

    <!-- Danger Zone -->
    <div class="mt-8 p-6 rounded-lg border-2 border-red-500/50"
         :class="darkMode ? 'bg-red-900/10' : 'bg-red-50/50'">
        <h3 class="text-lg font-bold text-red-500 mb-4 flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            Danger Zone
        </h3>

        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <p class="font-semibold"
                   :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                    Delete this article
                </p>
                <p class="text-sm mt-1"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Once deleted, the article will be moved to trash. It can be restored within 30 days.
                </p>
            </div>

            <form action="{{ route('admin.articles.destroy', $article) }}"
                  method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this article? It will be moved to trash.');">
                @csrf
                @method('DELETE')

                <button type="submit"
                        class="px-6 py-2.5 rounded-lg font-semibold bg-red-500 hover:bg-red-600 text-white transition-all hover:scale-105 inline-flex items-center gap-2">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Delete Article</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Auto-save indicator -->
<div x-data="{ show: false, message: '' }"
     @auto-save-success.window="show = true; message = $event.detail.message; setTimeout(() => show = false, 3000)"
     x-show="show"
     x-transition
     class="fixed bottom-8 right-8 z-50 px-4 py-3 rounded-lg shadow-lg glass animate-fadeInUp"
     :class="darkMode ? 'glass-dark' : 'glass-light'">
    <div class="flex items-center gap-2">
        <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
        <span class="text-sm font-medium"
              :class="darkMode ? 'text-gray-200' : 'text-gray-800'"
              x-text="message"></span>
    </div>
</div>

@push('scripts')
<script>
    // Warn before leaving if there are unsaved changes
    let formChanged = false;

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form');

        if (form) {
            form.addEventListener('input', () => {
                formChanged = true;
            });

            form.addEventListener('submit', () => {
                formChanged = false;
            });
        }

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
    });
</script>
@endpush
@endsection
