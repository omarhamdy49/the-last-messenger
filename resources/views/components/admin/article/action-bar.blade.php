{{--
    Article Form Action Bar Component
    Sticky top action bar with navigation and form actions
    Features: Back button, title, stats, save draft, submit
--}}

@props([
    'article' => null,
    'isEdit' => false,
])

<div class="sticky top-0 z-40 -mx-4 sm:-mx-6 lg:-mx-8 -mt-8 px-4 sm:px-6 lg:px-8 py-3 mb-6 border-b backdrop-blur-xl"
     :class="darkMode ? 'bg-gray-900/95 border-gray-700' : 'bg-white/95 border-gray-200'">
    <div class="flex items-center justify-between gap-4 flex-wrap">
        {{-- Left: Back + Title --}}
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('admin.articles.index') }}"
               class="p-2 rounded-lg transition-colors flex-shrink-0"
               :class="darkMode ? 'hover:bg-gray-800 text-gray-400' : 'hover:bg-gray-100 text-gray-600'">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="min-w-0">
                <h1 class="text-lg font-bold truncate"
                    :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ $isEdit ? __('articles.edit') : __('articles.create') }}
                </h1>
                @if($isEdit && $article)
                    <div class="flex items-center gap-3 text-xs" :class="darkMode ? 'text-gray-400' : 'text-gray-500'">
                        <span class="truncate">{{ $article->locales->first()->title ?? '' }}</span>
                        <span class="hidden md:inline-flex items-center gap-1">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            {{ $article->updated_at->diffForHumans() }}
                        </span>
                        <span class="hidden md:inline-flex items-center gap-1">
                            <i data-lucide="eye" class="w-3 h-3"></i>
                            {{ number_format($article->views) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
            @if($isEdit && $article)
                <a href="{{ route('admin.articles.show', $article) }}"
                   class="px-3 py-2 rounded-lg text-sm transition-colors flex items-center gap-1.5"
                   :class="darkMode ? 'text-gray-400 hover:bg-gray-700 hover:text-gray-200' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700'">
                    <i data-lucide="eye" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">{{ __('common.view') }}</span>
                </a>
                <button type="button"
                        @click.prevent="$dispatch('show-confirm', { title: '{{ __("admin.confirm_delete") }}', message: '{{ __("admin.delete_warning") }}', type: 'danger', onConfirm: () => {
                            const f=document.createElement('form');
                            f.method='POST';
                            f.action='{{ route('admin.articles.destroy', $article) }}';
                            const csrf=document.createElement('input');csrf.type='hidden';csrf.name='_token';csrf.value='{{ csrf_token() }}';f.appendChild(csrf);
                            const method=document.createElement('input');method.type='hidden';method.name='_method';method.value='DELETE';f.appendChild(method);
                            document.body.appendChild(f);f.submit();
                        } })"
                        class="px-3 py-2 rounded-lg text-sm transition-colors flex items-center gap-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span class="hidden sm:inline">{{ __('common.delete') }}</span>
                </button>
                <div class="w-px h-6 mx-1 hidden sm:block" :class="darkMode ? 'bg-gray-700' : 'bg-gray-200'"></div>
            @endif
            <button type="button" @click="saveDraft"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors border"
                    :class="darkMode
                        ? 'border-gray-600 text-gray-300 hover:bg-gray-700'
                        : 'border-gray-300 text-gray-700 hover:bg-gray-50'">
                <i data-lucide="save" class="w-4 h-4 inline me-1"></i>
                {{ __('articles.actions.save_draft') }}
            </button>
            <button type="submit"
                    class="px-5 py-2 rounded-lg text-sm font-semibold bg-gradient-to-r from-[#0056b3] to-[#218838] text-white hover:shadow-lg transition-all">
                <i data-lucide="check" class="w-4 h-4 inline me-1"></i>
                {{ $isEdit ? __('common.update') : __('common.create') }}
            </button>
        </div>
    </div>
</div>
