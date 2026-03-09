{{--
    Category Tree Item Component
    Recursive component for displaying nested categories

    Usage:
    <x-admin.category-tree-item :category="$category" />
--}}

@props(['category'])

<div x-data="categoryTreeItem({{ json_encode($category) }})"
     class="category-tree-item"
     data-category-id="{{ $category['id'] }}"
     @expand-all-categories.window="expanded = true"
     @collapse-all-categories.window="expanded = false">

    <!-- Category Row -->
    <div class="flex items-center gap-4 p-4 hover:bg-opacity-50 transition-colors group"
         :class="darkMode ? 'hover:bg-gray-800' : 'hover:bg-gray-50'">

        <!-- Drag Handle -->
        <button type="button"
                class="cursor-move opacity-0 group-hover:opacity-100 transition-opacity"
                :class="darkMode ? 'text-gray-600 hover:text-gray-400' : 'text-gray-400 hover:text-gray-600'">
            <i data-lucide="grip-vertical" class="w-5 h-5"></i>
        </button>

        <!-- Expand/Collapse Toggle -->
        <button type="button"
                @click="toggleExpanded()"
                x-show="hasChildren"
                class="w-6 h-6 flex items-center justify-center rounded transition-all hover:scale-110"
                :class="expanded ? 'rotate-90' : ''">
            <i data-lucide="chevron-right" class="w-5 h-5"
               :class="darkMode ? 'text-gray-400' : 'text-gray-600'"></i>
        </button>
        <div x-show="!hasChildren" class="w-6"></div>

        <!-- Depth Indicator -->
        <div class="flex items-center gap-1">
            <template x-for="i in depth" :key="i">
                <div class="w-1 h-8 rounded"
                     :class="darkMode ? 'bg-gray-800' : 'bg-gray-200'"></div>
            </template>
        </div>

        <!-- Category Icon & Color -->
        <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"
             :style="`background: linear-gradient(135deg, ${category.color}22 0%, ${category.color}44 100%);`">
            <i :data-lucide="category.icon"
               class="w-6 h-6"
               :style="`color: ${category.color};`"></i>
        </div>

        <!-- Category Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3">
                <a :href="`/admin/categories/${category.id}`"
                   class="font-semibold truncate hover:text-accent transition-colors"
                   :class="darkMode ? 'text-white' : 'text-gray-900'"
                   x-text="category.name"></a>

                <!-- Status Badge -->
                <span v-if="category.is_active"
                      class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-500/10 text-green-500 flex items-center gap-1">
                    <i data-lucide="check" class="w-3 h-3"></i>
                    Active
                </span>
                <span v-else
                      class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-500 flex items-center gap-1">
                    <i data-lucide="x" class="w-3 h-3"></i>
                    Inactive
                </span>
            </div>

            <!-- Category Meta -->
            <div class="flex items-center gap-4 mt-1 text-sm"
                 :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                <span class="flex items-center gap-1">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span x-text="category.articles_count || 0"></span> articles
                </span>
                <span class="flex items-center gap-1" x-show="category.children_count > 0">
                    <i data-lucide="folders" class="w-4 h-4"></i>
                    <span x-text="category.children_count"></span> subcategories
                </span>
                <span class="flex items-center gap-1" x-show="category.slug">
                    <i data-lucide="link" class="w-4 h-4"></i>
                    <code class="text-xs" x-text="category.slug"></code>
                </span>
            </div>
        </div>

        <!-- Actions Dropdown -->
        <div x-data="{ dropdownOpen: false }" class="relative">
            <button type="button"
                    @click="dropdownOpen = !dropdownOpen"
                    class="p-2 rounded-lg transition-all hover:scale-110 opacity-0 group-hover:opacity-100"
                    :class="darkMode ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-200 text-gray-600'">
                <i data-lucide="more-vertical" class="w-5 h-5"></i>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="dropdownOpen"
                 @click.away="dropdownOpen = false"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-56 rounded-xl shadow-xl glass z-50 overflow-hidden"
                 :class="darkMode ? 'bg-gray-800/95 border border-gray-700' : 'bg-white/95 border border-gray-200'">

                <a :href="`/admin/categories/${category.id}`"
                   class="flex items-center gap-3 px-4 py-3 transition-colors"
                   :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                    <i data-lucide="eye" class="w-5 h-5 text-blue-500"></i>
                    <span class="font-medium">View Details</span>
                </a>

                <a :href="`/admin/categories/${category.id}/edit`"
                   class="flex items-center gap-3 px-4 py-3 transition-colors"
                   :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                    <i data-lucide="edit" class="w-5 h-5 text-accent"></i>
                    <span class="font-medium">Edit</span>
                </a>

                <a :href="`/admin/categories/create?parent_id=${category.id}`"
                   class="flex items-center gap-3 px-4 py-3 transition-colors"
                   :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                    <i data-lucide="folder-plus" class="w-5 h-5 text-green-500"></i>
                    <span class="font-medium">Add Subcategory</span>
                </a>

                <a :href="`/admin/categories/create?duplicate=${category.id}`"
                   class="flex items-center gap-3 px-4 py-3 transition-colors"
                   :class="darkMode ? 'hover:bg-gray-700 text-white' : 'hover:bg-gray-50 text-gray-900'">
                    <i data-lucide="copy" class="w-5 h-5 text-purple-500"></i>
                    <span class="font-medium">Duplicate</span>
                </a>

                <div class="border-t" :class="darkMode ? 'border-gray-700' : 'border-gray-200'"></div>

                <button type="button"
                        @click="deleteCategory(); dropdownOpen = false;"
                        class="w-full flex items-center gap-3 px-4 py-3 transition-colors"
                        :class="darkMode ? 'hover:bg-red-900/20 text-red-400' : 'hover:bg-red-50 text-red-600'">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                    <span class="font-medium">Delete</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Children (Nested Categories) -->
    <div x-show="expanded && hasChildren"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="children-container">
        <template x-if="category.children && category.children.length > 0">
            <div>
                <template x-for="child in category.children" :key="child.id">
                    <div>
                        <x-admin.category-tree-item :category="child" />
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function categoryTreeItem(initialCategory) {
    return {
        category: initialCategory,
        expanded: false,
        hasChildren: initialCategory.children && initialCategory.children.length > 0,
        depth: initialCategory.depth || 0,

        init() {
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        toggleExpanded() {
            this.expanded = !this.expanded;
            this.$nextTick(() => {
                lucide.createIcons();
            });
        },

        deleteCategory() {
            showConfirm({
                title: '{{ __("admin.confirm_delete") }}',
                message: '{{ __("admin.delete_warning") }}',
                type: 'danger',
                onConfirm: async () => {
                    try {
                        const response = await fetch(`/admin/categories/${this.category.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.ok) {
                            window.dispatchEvent(new CustomEvent('category-deleted', {
                                detail: { categoryId: this.category.id }
                            }));

                            this.$el.style.transition = 'all 0.3s ease-out';
                            this.$el.style.opacity = '0';
                            this.$el.style.transform = 'translateX(-20px)';

                            setTimeout(() => {
                                this.$el.remove();
                            }, 300);

                            showToast('{{ __("admin.deleted_successfully") }}', 'success');
                        } else {
                            const error = await response.json();
                            showToast(error.message || '{{ __("admin.error_occurred") }}', 'error');
                        }
                    } catch (error) {
                        console.error('Failed to delete category:', error);
                        showToast('{{ __("admin.error_occurred") }}', 'error');
                    }
                }
            });
        }
    };
}
</script>
@endpush
