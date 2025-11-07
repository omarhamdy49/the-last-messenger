@extends('layouts.admin')

@section('title', 'Create New Article')

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
                <h1 class="text-3xl font-bold gradient-text mb-2">
                    Create New Article
                </h1>
                <p class="text-sm"
                   :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                    Fill in the details below to create a new article for your site
                </p>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="glass rounded-lg p-4"
             :class="darkMode ? 'glass-dark' : 'glass-light'">
            <div class="flex items-center justify-between relative">
                <div class="absolute top-1/2 left-0 right-0 h-0.5 -translate-y-1/2"
                     :class="darkMode ? 'bg-gray-700' : 'bg-gray-300'">
                </div>

                @foreach([
                    ['step' => 1, 'label' => 'Content', 'icon' => 'file-text'],
                    ['step' => 2, 'label' => 'Media', 'icon' => 'image'],
                    ['step' => 3, 'label' => 'Categories', 'icon' => 'folder'],
                    ['step' => 4, 'label' => 'Publish', 'icon' => 'send'],
                ] as $item)
                    <div class="relative z-10 flex flex-col items-center gap-2">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-300"
                             :class="darkMode ?
                                 'bg-gray-800 text-gray-400 border-2 border-gray-700' :
                                 'bg-white text-gray-600 border-2 border-gray-300'">
                            <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5"></i>
                        </div>
                        <span class="text-xs font-semibold"
                              :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            {{ $item['label'] }}
                        </span>
                    </div>
                @endforeach
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

    <!-- Success Alert (if any) -->
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

    <!-- Tips & Best Practices -->
    <div class="mb-6 p-4 rounded-lg border-l-4 border-blue-500"
         :class="darkMode ? 'bg-blue-900/20' : 'bg-blue-50'"
         x-data="{ showTips: true }">
        <div class="flex items-start gap-3">
            <i data-lucide="lightbulb" class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold"
                        :class="darkMode ? 'text-blue-400' : 'text-blue-600'">
                        Article Creation Tips
                    </h3>
                    <button @click="showTips = !showTips"
                            class="text-sm font-medium"
                            :class="darkMode ? 'text-blue-400 hover:text-blue-300' : 'text-blue-600 hover:text-blue-700'">
                        <span x-text="showTips ? 'Hide' : 'Show'"></span>
                    </button>
                </div>

                <div x-show="showTips" x-collapse>
                    <ul class="space-y-1 text-sm"
                        :class="darkMode ? 'text-blue-300' : 'text-blue-700'">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <span>Write compelling titles (50-60 characters) for better SEO</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <span>Choose a high-quality featured image (recommended: 1200x630px)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <span>Fill in all language tabs for better reach</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <span>Use SEO metadata to improve search engine visibility</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <span>Add relevant tags and categories for better organization</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                            <span>Your work is auto-saved every 30 seconds</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Article Form -->
    @include('admin.articles._form', [
        'article' => null,
        'categories' => $categories ?? [],
        'tags' => $tags ?? [],
        'authors' => $authors ?? [],
    ])
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

        // Check for auto-saved draft
        const savedDraft = localStorage.getItem('article_draft');
        if (savedDraft) {
            const draft = JSON.parse(savedDraft);
            const timeDiff = Date.now() - draft.timestamp;
            const minutes = Math.floor(timeDiff / 60000);

            if (minutes < 60) {
                if (confirm(`Found an auto-saved draft from ${minutes} minute(s) ago. Would you like to restore it?`)) {
                    // Restore draft data
                    Object.keys(draft.data).forEach(key => {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input && input.type !== 'file') {
                            input.value = draft.data[key];
                        }
                    });

                    // Trigger Alpine.js reactivity
                    form.dispatchEvent(new Event('input', { bubbles: true }));
                }
            } else {
                // Clear old draft
                localStorage.removeItem('article_draft');
            }
        }
    });
</script>
@endpush
@endsection
