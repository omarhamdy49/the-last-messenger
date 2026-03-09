@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto">

    <!-- Breadcrumbs -->
    <nav class="mb-6 flex items-center gap-2 text-sm"
         :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
        <a href="{{ route('admin.dashboard') }}"
           class="hover:text-accent transition-colors">
            <i data-lucide="home" class="w-4 h-4"></i>
        </a>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <a href="{{ route('admin.categories.index') }}"
           class="hover:text-accent transition-colors">
            Categories
        </a>
        <i data-lucide="chevron-right" class="w-4 h-4"></i>
        <span :class="darkMode ? 'text-white' : 'text-gray-900'">{{ $category->name }}</span>
    </nav>

    <!-- Page Header with Actions -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div class="flex items-start gap-4">
            <!-- Category Icon -->
            <div class="flex-shrink-0 w-16 h-16 rounded-xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, {{ $category->color }}22 0%, {{ $category->color }}44 100%);">
                <i data-lucide="{{ $category->icon }}"
                   class="w-8 h-8"
                   style="color: {{ $category->color }};"></i>
            </div>

            <div>
                <h1 class="text-3xl font-bold mb-2"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $category->name }}
                </h1>
                <div class="flex items-center gap-3 flex-wrap">
                    @if($category->is_active)
                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-500/10 text-green-500 flex items-center gap-1">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                            Active
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-gray-500/10 text-gray-500 flex items-center gap-1">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                            Inactive
                        </span>
                    @endif

                    @if($category->parent)
                        <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                            <i data-lucide="folder-tree" class="w-4 h-4 inline"></i>
                            Child of: <a href="{{ route('admin.categories.show', $category->parent) }}"
                                        class="text-accent hover:underline">{{ $category->parent->name }}</a>
                        </span>
                    @endif

                    <span class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <i data-lucide="calendar" class="w-4 h-4 inline"></i>
                        Created {{ $category->created_at->diffForHumans() }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.categories.edit', $category) }}"
               class="px-6 py-2.5 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:scale-105 transition-all shadow-lg flex items-center gap-2">
                <i data-lucide="edit" class="w-5 h-5"></i>
                <span>Edit</span>
            </a>

            <button @click="duplicateCategory"
                    class="px-6 py-2.5 rounded-lg font-semibold transition-all hover:scale-105 flex items-center gap-2"
                    :class="darkMode ? 'bg-gray-800 text-white hover:bg-gray-700' : 'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                <i data-lucide="copy" class="w-5 h-5"></i>
                <span>Duplicate</span>
            </button>

            <button @click="deleteCategory"
                    class="px-6 py-2.5 rounded-lg font-semibold bg-red-500 text-white hover:bg-red-600 transition-all hover:scale-105 flex items-center gap-2">
                <i data-lucide="trash-2" class="w-5 h-5"></i>
                <span>Delete</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Description -->
            @if($category->description)
                <div class="rounded-2xl overflow-hidden shadow-xl border"
                     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-4 flex items-center gap-2"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                            Description
                        </h2>
                        <p class="leading-relaxed" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                            {{ $category->description }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Hierarchy Path -->
            @if($category->ancestors && count($category->ancestors) > 0)
                <div class="rounded-2xl overflow-hidden shadow-xl border"
                     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-4 flex items-center gap-2"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="git-branch" class="w-5 h-5 text-accent"></i>
                            Hierarchy Path
                        </h2>
                        <div class="flex items-center gap-2 flex-wrap">
                            @foreach($category->ancestors as $ancestor)
                                <a href="{{ route('admin.categories.show', $ancestor) }}"
                                   class="px-3 py-2 rounded-lg font-medium transition-all hover:scale-105 flex items-center gap-2"
                                   :class="darkMode ? 'bg-gray-800 text-gray-300 hover:bg-gray-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                                    <i data-lucide="{{ $ancestor->icon }}" class="w-4 h-4"></i>
                                    {{ $ancestor->name }}
                                </a>
                                <i data-lucide="chevron-right" class="w-4 h-4" :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                            @endforeach
                            <span class="px-3 py-2 rounded-lg font-semibold bg-accent/10 text-accent flex items-center gap-2">
                                <i data-lucide="{{ $category->icon }}" class="w-4 h-4"></i>
                                {{ $category->name }}
                            </span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Children Categories -->
            @if($category->children && count($category->children) > 0)
                <div class="rounded-2xl overflow-hidden shadow-xl border"
                     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <div class="p-6">
                        <h2 class="text-xl font-bold mb-4 flex items-center gap-2"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            <i data-lucide="folders" class="w-5 h-5 text-accent"></i>
                            Subcategories ({{ count($category->children) }})
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($category->children as $child)
                                <a href="{{ route('admin.categories.show', $child) }}"
                                   class="p-4 rounded-xl transition-all hover:scale-105 flex items-center gap-3 group"
                                   :class="darkMode ? 'bg-gray-800 hover:bg-gray-750' : 'bg-gray-50 hover:bg-gray-100'">
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0"
                                         style="background: linear-gradient(135deg, {{ $child->color }}22 0%, {{ $child->color }}44 100%);">
                                        <i data-lucide="{{ $child->icon }}" class="w-6 h-6" style="color: {{ $child->color }};"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold truncate group-hover:text-accent transition-colors"
                                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ $child->name }}
                                        </h3>
                                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ $child->articles_count ?? 0 }} articles
                                        </p>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-5 h-5 opacity-0 group-hover:opacity-100 transition-opacity"
                                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recent Articles -->
            @if($recentArticles && count($recentArticles) > 0)
                <div class="rounded-2xl overflow-hidden shadow-xl border"
                     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold flex items-center gap-2"
                                :class="darkMode ? 'text-white' : 'text-gray-900'">
                                <i data-lucide="file-text" class="w-5 h-5 text-accent"></i>
                                Recent Articles
                            </h2>
                            <a href="{{ route('admin.articles.index', ['category' => $category->id]) }}"
                               class="text-sm font-medium text-accent hover:underline">
                                View All
                            </a>
                        </div>
                        <div class="space-y-3">
                            @foreach($recentArticles as $article)
                                <a href="{{ route('admin.articles.show', $article) }}"
                                   class="flex items-center gap-4 p-3 rounded-lg transition-all hover:scale-[1.02] group"
                                   :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">
                                    @if($article->featured_image)
                                        <img src="{{ $article->featured_image->url }}"
                                             alt="{{ $article->title }}"
                                             class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                    @else
                                        <div class="w-16 h-16 rounded-lg flex items-center justify-center flex-shrink-0"
                                             :class="darkMode ? 'bg-gray-800' : 'bg-gray-100'">
                                            <i data-lucide="file-text" class="w-6 h-6"
                                               :class="darkMode ? 'text-gray-600' : 'text-gray-400'"></i>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold truncate group-hover:text-accent transition-colors"
                                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                                            {{ $article->title }}
                                        </h3>
                                        <p class="text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                            {{ $article->created_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-5 h-5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Stats Card -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-6">
                    <h2 class="text-lg font-bold mb-4"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        Statistics
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <i data-lucide="file-text" class="w-4 h-4 inline mr-2"></i>
                                Articles
                            </span>
                            <span class="font-bold text-lg"
                                  :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $category->articles_count ?? 0 }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <i data-lucide="folders" class="w-4 h-4 inline mr-2"></i>
                                Subcategories
                            </span>
                            <span class="font-bold text-lg"
                                  :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ count($category->children ?? []) }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <i data-lucide="layers" class="w-4 h-4 inline mr-2"></i>
                                Depth Level
                            </span>
                            <span class="font-bold text-lg"
                                  :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $category->depth ?? 0 }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                <i data-lucide="arrow-up-down" class="w-4 h-4 inline mr-2"></i>
                                Display Order
                            </span>
                            <span class="font-bold text-lg"
                                  :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $category->order }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Card -->
            <div class="rounded-2xl overflow-hidden shadow-xl border"
                 :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                <div class="p-6">
                    <h2 class="text-lg font-bold mb-4"
                        :class="darkMode ? 'text-white' : 'text-gray-900'">
                        Details
                    </h2>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="block mb-1 font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                Created At
                            </span>
                            <span :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $category->created_at->format('M d, Y h:i A') }}
                            </span>
                        </div>

                        <div>
                            <span class="block mb-1 font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                Updated At
                            </span>
                            <span :class="darkMode ? 'text-white' : 'text-gray-900'">
                                {{ $category->updated_at->format('M d, Y h:i A') }}
                            </span>
                        </div>

                        @if($category->slug)
                            <div>
                                <span class="block mb-1 font-semibold" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                                    Slug
                                </span>
                                <code class="px-2 py-1 rounded text-xs font-mono"
                                      :class="darkMode ? 'bg-gray-800 text-accent' : 'bg-gray-100 text-accent'">
                                    {{ $category->slug }}
                                </code>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Featured Image -->
            @if($category->featuredImage)
                <div class="rounded-2xl overflow-hidden shadow-xl border"
                     :class="darkMode ? 'bg-gray-900 border-gray-800' : 'bg-white border-gray-200'">
                    <div class="p-6">
                        <h2 class="text-lg font-bold mb-4"
                            :class="darkMode ? 'text-white' : 'text-gray-900'">
                            Featured Image
                        </h2>
                        <img src="{{ $category->featuredImage->url }}"
                             alt="{{ $category->name }}"
                             class="w-full rounded-lg">
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function duplicateCategory() {
    window.showConfirm({
        title: 'نسخ التصنيف',
        message: 'سيتم إنشاء نسخة من هذا التصنيف. هل تريد المتابعة؟',
        type: 'warning',
        onConfirm: () => {
            window.location.href = '{{ route("admin.categories.create") }}?duplicate={{ $category->id }}';
        }
    });
}

function deleteCategory() {
    window.showConfirm({
        title: '{{ __("admin.confirm_delete") }}',
        message: 'هل أنت متأكد من حذف هذا التصنيف؟ سيتم حذف جميع التصنيفات الفرعية أيضاً.',
        type: 'danger',
        onConfirm: () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.categories.destroy", $category) }}';
            form.innerHTML = `
                @csrf
                @method('DELETE')
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
@endsection
