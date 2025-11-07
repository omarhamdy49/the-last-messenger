{{--
    Tag Merge Modal Component
    Allows merging multiple tags into one

    Usage:
    <x-admin.tag-merge-modal />
--}}

<div x-data="tagMergeModal()"
     x-show="isOpen"
     x-cloak
     @open-tag-merge-modal.window="handleOpen($event.detail)"
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 overflow-hidden"
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 glass"
         :class="darkMode ? 'bg-black/80' : 'bg-black/50'">
    </div>

    <!-- Modal Container -->
    <div class="fixed inset-0 overflow-hidden flex items-center justify-center p-4">
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.stop
             class="relative w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden glass"
             :class="darkMode ? 'bg-gray-900/95 border border-gray-800' : 'bg-white border border-gray-200'">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b"
                 :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
                <div>
                    <h2 class="text-2xl font-bold gradient-text">
                        Merge Tags
                    </h2>
                    <p class="text-sm mt-1"
                       :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        Combine multiple tags into a single tag
                    </p>
                </div>

                <button @click="close()"
                        class="p-2 rounded-lg transition-all hover:scale-110"
                        :class="darkMode ? 'hover:bg-gray-800 text-gray-400 hover:text-white' : 'hover:bg-gray-100 text-gray-600 hover:text-gray-900'">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <!-- Content -->
            <form @submit.prevent="mergeTags()" class="p-6">

                <!-- Source Tags Section -->
                <div class="mb-6">
                    <label class="block mb-3 font-semibold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="tags" class="w-5 h-5 inline mr-2 text-accent"></i>
                        Source Tags (will be merged)
                    </label>
                    <div class="flex flex-wrap gap-2 p-4 rounded-lg"
                         :class="darkMode ? 'bg-gray-800' : 'bg-gray-50'">
                        <template x-for="tag in sourceTags" :key="tag.id">
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg shadow-sm border"
                                 :class="darkMode ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'">
                                <div class="w-4 h-4 rounded-full"
                                     :style="`background: ${tag.color};`"></div>
                                <span class="font-medium" :class="darkMode ? 'text-white' : 'text-gray-900'"
                                      x-text="tag.name"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500"
                                      x-text="tag.articles_count + ' articles'"></span>
                            </div>
                        </template>
                    </div>
                    <p class="mt-2 text-sm" :class="darkMode ? 'text-gray-400' : 'text-gray-600'">
                        <i data-lucide="info" class="w-4 h-4 inline mr-1"></i>
                        These tags will be deleted after merging
                    </p>
                </div>

                <!-- Target Tag Section -->
                <div class="mb-6">
                    <label class="block mb-3 font-semibold"
                           :class="darkMode ? 'text-white' : 'text-gray-900'">
                        <i data-lucide="tag" class="w-5 h-5 inline mr-2 text-accent"></i>
                        Target Tag (keep this one)
                    </label>

                    <!-- Target Tag Selection -->
                    <div class="space-y-3">
                        <!-- Option 1: Select from existing tags -->
                        <div>
                            <label class="flex items-center gap-2 mb-2">
                                <input type="radio"
                                       x-model="mergeMode"
                                       value="existing"
                                       class="w-4 h-4 text-accent">
                                <span class="font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    Merge into existing tag
                                </span>
                            </label>
                            <select x-model="targetTagId"
                                    :disabled="mergeMode !== 'existing'"
                                    class="w-full px-4 py-3 rounded-lg border-2 transition-all focus:ring-2 focus:ring-accent/50"
                                    :class="[
                                        mergeMode !== 'existing' ? 'opacity-50 cursor-not-allowed' : '',
                                        darkMode ?
                                            'bg-gray-800 border-gray-700 text-white focus:border-accent' :
                                            'bg-white border-gray-300 text-gray-900 focus:border-accent'
                                    ]">
                                <option value="">-- Select Target Tag --</option>
                                <template x-for="tag in availableTags" :key="tag.id">
                                    <option :value="tag.id" x-text="`${tag.name} (${tag.articles_count} articles)`"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Option 2: Create new tag -->
                        <div>
                            <label class="flex items-center gap-2 mb-2">
                                <input type="radio"
                                       x-model="mergeMode"
                                       value="new"
                                       class="w-4 h-4 text-accent">
                                <span class="font-medium" :class="darkMode ? 'text-gray-300' : 'text-gray-700'">
                                    Create new tag
                                </span>
                            </label>
                            <input type="text"
                                   x-model="newTagName"
                                   :disabled="mergeMode !== 'new'"
                                   placeholder="Enter new tag name..."
                                   class="w-full px-4 py-3 rounded-lg border-2 transition-all focus:ring-2 focus:ring-accent/50"
                                   :class="[
                                       mergeMode !== 'new' ? 'opacity-50 cursor-not-allowed' : '',
                                       darkMode ?
                                           'bg-gray-800 border-gray-700 text-white placeholder-gray-500 focus:border-accent' :
                                           'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-accent'
                                   ]">
                        </div>
                    </div>
                </div>

                <!-- Preview Statistics -->
                <div class="mb-6 p-4 rounded-lg border"
                     :class="darkMode ? 'bg-gray-800/50 border-gray-700' : 'bg-blue-50 border-blue-200'">
                    <h3 class="font-bold mb-3 flex items-center gap-2"
                        :class="darkMode ? 'text-blue-400' : 'text-blue-700'">
                        <i data-lucide="bar-chart" class="w-5 h-5"></i>
                        Merge Preview
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span :class="darkMode ? 'text-gray-400' : 'text-blue-600'">
                                Tags to be merged:
                            </span>
                            <p class="text-2xl font-bold"
                               :class="darkMode ? 'text-white' : 'text-blue-700'"
                               x-text="sourceTags.length"></p>
                        </div>
                        <div>
                            <span :class="darkMode ? 'text-gray-400' : 'text-blue-600'">
                                Total articles affected:
                            </span>
                            <p class="text-2xl font-bold"
                               :class="darkMode ? 'text-white' : 'text-blue-700'"
                               x-text="totalArticles"></p>
                        </div>
                    </div>
                </div>

                <!-- Warning -->
                <div class="mb-6 p-4 rounded-lg border"
                     :class="darkMode ? 'bg-red-900/10 border-red-900/30' : 'bg-red-50 border-red-200'">
                    <div class="flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-red-500 mb-1">
                                Warning: This action cannot be undone
                            </p>
                            <p class="text-sm" :class="darkMode ? 'text-red-400' : 'text-red-600'">
                                All source tags will be permanently deleted and their associations will be transferred to the target tag.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3">
                    <button type="button"
                            @click="close()"
                            class="px-6 py-2.5 rounded-lg font-semibold transition-all hover:scale-105"
                            :class="darkMode ?
                                'bg-gray-800 text-white hover:bg-gray-700' :
                                'bg-gray-100 text-gray-900 hover:bg-gray-200'">
                        Cancel
                    </button>

                    <button type="submit"
                            :disabled="!canMerge"
                            class="px-6 py-2.5 rounded-lg font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed hover:scale-105 disabled:hover:scale-100 flex items-center gap-2">
                        <i data-lucide="git-merge" class="w-5 h-5"></i>
                        <span>Merge Tags</span>
                    </button>
                </div>
            </form>

            <!-- Loading Overlay -->
            <div x-show="isLoading"
                 class="absolute inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-accent border-t-transparent mb-4"></div>
                    <p class="text-white font-semibold">Merging tags...</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function tagMergeModal() {
    return {
        isOpen: false,
        isLoading: false,
        sourceTagIds: [],
        sourceTags: [],
        availableTags: [],
        mergeMode: 'existing', // 'existing' or 'new'
        targetTagId: '',
        newTagName: '',

        handleOpen(detail) {
            this.sourceTagIds = detail.tagIds || [];
            this.loadTags();
            this.isOpen = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async loadTags() {
            try {
                const response = await fetch('/admin/tags?ajax=1', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                this.sourceTags = data.tags.filter(tag => this.sourceTagIds.includes(tag.id));
                this.availableTags = data.tags.filter(tag => !this.sourceTagIds.includes(tag.id));
            } catch (error) {
                console.error('Failed to load tags:', error);
            }
        },

        close() {
            this.isOpen = false;
            this.sourceTagIds = [];
            this.sourceTags = [];
            this.availableTags = [];
            this.mergeMode = 'existing';
            this.targetTagId = '';
            this.newTagName = '';
        },

        get totalArticles() {
            return this.sourceTags.reduce((sum, tag) => sum + (tag.articles_count || 0), 0);
        },

        get canMerge() {
            if (this.mergeMode === 'existing') {
                return this.targetTagId !== '';
            } else if (this.mergeMode === 'new') {
                return this.newTagName.trim() !== '';
            }
            return false;
        },

        async mergeTags() {
            if (!this.canMerge) return;

            if (!confirm(`Are you sure you want to merge ${this.sourceTags.length} tags? This cannot be undone.`)) {
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch('/admin/tags/merge', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        source_tag_ids: this.sourceTagIds,
                        merge_mode: this.mergeMode,
                        target_tag_id: this.mergeMode === 'existing' ? this.targetTagId : null,
                        new_tag_name: this.mergeMode === 'new' ? this.newTagName : null
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    window.location.reload();
                } else {
                    const error = await response.json();
                    alert(error.message || 'Failed to merge tags');
                }
            } catch (error) {
                console.error('Failed to merge tags:', error);
                alert('Failed to merge tags. Please try again.');
            } finally {
                this.isLoading = false;
            }
        }
    };
}
</script>
@endpush
