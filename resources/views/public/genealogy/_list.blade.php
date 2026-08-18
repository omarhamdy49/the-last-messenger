{{--
    THE DIRECTORY VIEW.

    What it replaces: eighty full-bleed "Refined Medallion" cards — a 176px
    portrait each — in graph-walk order, with no indication of who any of these
    people were to the Prophet ﷺ. On a phone that was one card per screen and
    seven pages of pagination to cross one family.

    What it is now: compact rows grouped by kinship, in the order a nasab
    reads. The relation label is the point of the card, not an afterthought,
    and colour carries the same meaning it carries in the tree rather than
    rotating through a palette for variety.

    Pagination is gone on purpose. Eighty compact rows are one scroll with
    sticky group headings; paging through them in twelves was the slower way
    to read a family.
--}}
<div class="container mx-auto px-4 sm:px-6 lg:px-8">

    @if (! $hasGraph && count($people))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 dark:border-amber-900/50
                    bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
            <i data-lucide="info" class="w-5 h-5 shrink-0 mt-0.5" aria-hidden="true"></i>
            <span>{{ __('genealogy.public.tree_unavailable') }}</span>
        </div>
    @endif

    @if (count($people))
        <div x-ref="listTop" class="space-y-8">
            <template x-for="group in groupedPeople" :key="group.key">
                <section>
                    <h2 class="gl-heading">
                        <span class="gl-heading-dot" :class="`gl-dot--${group.key}`" aria-hidden="true"></span>
                        <span x-text="group.label"></span>
                        <span class="gl-heading-count tabular-nums"
                              x-text="group.people.length"></span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                        <template x-for="(person, idx) in group.people" :key="person.id">
                            <button type="button" @click="openPerson(person.id)"
                                    :style="`--enter-delay: ${Math.min(idx, 10) * 30}ms`"
                                    :title="personName(person)"
                                    class="gl-card card-enter">

                                <span class="gl-ava" :class="`gl-ava--${person.relation}`" aria-hidden="true">
                                    <template x-if="person.isProphet">
                                        <i data-lucide="crown" class="w-4 h-4"></i>
                                    </template>
                                    <template x-if="!person.isProphet">
                                        <span x-text="personInitial(person)"></span>
                                    </template>
                                </span>

                                <span class="gl-meta">
                                    <span class="gl-name" x-html="highlightMatch(personName(person))"></span>

                                    <span class="gl-tags">
                                        {{-- The relation is why this card is
                                             here. It leads, and it is text —
                                             not colour alone. --}}
                                        <span class="gl-rel" :class="`gl-rel--${person.relation}`"
                                              x-text="person.relationLabel"></span>

                                        <template x-if="personDeathYear(person)">
                                            <span class="gl-date tabular-nums">
                                                <i data-lucide="calendar" class="w-3 h-3 shrink-0" aria-hidden="true"></i>
                                                <span x-text="personDeathYear(person)"></span>
                                            </span>
                                        </template>
                                    </span>
                                </span>

                                <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="gl-go" aria-hidden="true"></i>
                            </button>
                        </template>
                    </div>
                </section>
            </template>
        </div>

        <div x-show="groupedPeople.length === 0" x-cloak class="pt-4">
            <x-site.empty-state
                icon="users"
                :title="__('genealogy.public.empty_title')"
                :message="__('site.try_adjusting_filters')"
                :showReset="true"
                @click="activeFilter = 'all'; searchQuery = ''" />
        </div>
    @else
        <x-site.empty-state
            icon="users"
            :title="__('genealogy.public.empty_title')"
            :message="__('genealogy.public.empty_message')" />
    @endif
</div>
