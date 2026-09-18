@props(['item'])

{{--
    Renders one bookmarked item. $item is a FavoriteItemData DTO.
    Includes an inline remove form (works without JS) that posts to
    public.favorites.destroy; the page-level Alpine handler may intercept it.

    Wears `.sx-card` so it sits in the same visual world as every other card,
    but it is an <article>, not the anchor-wrapped <x-public.editorial.card>:
    the remove form and its submit button cannot legally nest inside a link,
    and a bookmark carries no image for the cross-page hero morph.
--}}
@php
    /** @var \App\Domain\Shared\DTOs\FavoriteItemData $item */
    $isOrphaned = $item->isOrphaned;
@endphp

<article class="sx-card group h-full"
         x-data="{ removing: false }"
         x-show="!removing"
         x-transition.opacity>
    {{-- flex, not the component's grid: the excerpt has to absorb the spare
         height so the "saved on" footer lines up across a row of cards. --}}
    <div class="sx-card-body flex flex-col h-full gap-3">
        <div class="flex items-start justify-between gap-3">
            <span @class(['sx-chip !min-h-0 !py-1 !text-xs !font-bold', '!text-[color:var(--sx-accent)] !bg-[color:var(--sx-accent-soft)]' => ! $isOrphaned])>
                <i data-lucide="{{ $item->icon }}" aria-hidden="true"></i>
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
                        class="grid h-11 w-11 place-items-center rounded-full text-[color:var(--sx-muted)] transition-colors hover:text-[color:var(--sx-danger)] hover:bg-[color:var(--sx-danger-soft)] disabled:opacity-50"
                        aria-label="{{ __('profile.remove') }}"
                        title="{{ __('profile.remove') }}">
                    <i data-lucide="bookmark-x" class="w-4 h-4" x-show="!removing"></i>
                    <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="removing" x-cloak></i>
                </button>
            </form>
        </div>

        <h3 class="sx-card-title !text-base" title="{{ $item->title }}">{{ $item->title }}</h3>

        @if ($item->excerpt)
            <p class="sx-card-desc sx-clamp-3 flex-1">{{ $item->excerpt }}</p>
        @else
            <div class="flex-1"></div>
        @endif

        <div class="mt-auto flex items-center justify-between gap-3 border-t border-[color:var(--sx-line)] pt-3">
            <span class="text-xs text-[color:var(--sx-muted)]">
                {{ __('profile.saved_on') }}
                {{ \Illuminate\Support\Carbon::parse($item->createdAt)->translatedFormat('d M Y') }}
            </span>

            @if ($item->url && ! $isOrphaned)
                <a href="{{ $item->url }}"
                   class="inline-flex items-center gap-1 text-sm font-bold text-[color:var(--sx-accent)] hover:underline underline-offset-4">
                    {{ __('profile.open') }}
                    <i data-lucide="arrow-{{ in_array(app()->getLocale(), ['ar', 'ur'], true) ? 'left' : 'right' }}" class="w-4 h-4" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </div>
</article>
