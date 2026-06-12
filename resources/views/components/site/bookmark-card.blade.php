@props(['item'])

{{--
    Renders one bookmarked item. $item is a FavoriteItemData DTO.
    Includes an inline remove form (works without JS) that posts to
    public.favorites.destroy; the page-level Alpine handler may intercept it.
--}}
@php
    /** @var \App\Domain\Shared\DTOs\FavoriteItemData $item */
    $isOrphaned = $item->isOrphaned;
@endphp

<article class="card-premium group flex flex-col h-full p-5 rounded-2xl transition-all"
         x-data="{ removing: false }"
         x-show="!removing"
         x-transition.opacity>
    <div class="flex items-start justify-between gap-3 mb-3">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold
                     {{ $isOrphaned ? 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' : 'bg-primary/10 text-primary' }}">
            <i data-lucide="{{ $item->icon }}" class="w-3.5 h-3.5"></i>
            {{ __($item->typeLabelKey) }}
        </span>

        {{-- Remove (destroy) — DELETE via method spoof; disabled while submitting --}}
        <form method="POST" action="{{ route('public.favorites.destroy') }}"
              @submit="removing = true"
              class="shrink-0">
            @csrf
            @method('DELETE')
            <input type="hidden" name="type" value="{{ $item->type->value }}">
            <input type="hidden" name="id" value="{{ $item->contentId }}">
            <button type="submit"
                    :disabled="removing"
                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-50"
                    aria-label="{{ __('profile.remove') }}">
                <i data-lucide="bookmark-x" class="w-4 h-4" x-show="!removing"></i>
                <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="removing" x-cloak></i>
            </button>
        </form>
    </div>

    <h3 class="text-base font-bold text-gray-900 dark:text-white line-clamp-2 mb-1.5 break-words">
        {{ $item->title }}
    </h3>

    @if ($item->excerpt)
        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3 flex-1 break-words">
            {{ $item->excerpt }}
        </p>
    @else
        <div class="flex-1"></div>
    @endif

    <div class="flex items-center justify-between gap-3 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/60">
        <span class="text-xs text-gray-400 dark:text-gray-500">
            {{ __('profile.saved_on') }}
            {{ \Illuminate\Support\Carbon::parse($item->createdAt)->translatedFormat('d M Y') }}
        </span>

        @if ($item->url && ! $isOrphaned)
            <a href="{{ $item->url }}"
               class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                {{ __('profile.open') }}
                <i data-lucide="arrow-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'left' : 'right' }}" class="w-4 h-4"></i>
            </a>
        @endif
    </div>
</article>
