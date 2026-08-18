{{--
    THE NASAB SPINE — the tree as it reads on a phone.

    A pan-and-zoom canvas is the wrong instrument below a laptop screen: at
    375px it showed about one and a half of eighty cards, truncated every name,
    and — because a canvas must claim the touch gesture in order to pan — a
    swipe that started on it did not scroll the page. This renders the same
    graph the canvas does, laid out the way a nasab is actually read: one
    generation per row, oldest at the top, descending to the Prophet ﷺ.

    It is entirely server-rendered. No d3, no canvas, no dynamic import — so it
    is present in the HTML for a crawler, it paints before any script runs, and
    it survives the tree chunk failing to load. Alpine only opens and closes
    the collapsible parts.

    Expects: $spine (rows, oldest first), $isRtl, $lineageComplete.
--}}
@php
    // A twenty-one generation chain is a long scroll to reach the subject of
    // the page. The oldest forefather anchors the top, the last few
    // generations before the Prophet ﷺ carry the names most readers came for,
    // and the stretch between them folds away by default.
    $head = 2;
    $tail = 4;
    $foldFrom = $head;
    $foldTo = max($head, count($spine) - $tail - 1);
    $foldedCount = max(0, $foldTo - $foldFrom);
@endphp

<div class="gs" x-data="{ midOpen: false }">
    <ol class="gs-rail" role="list">
        @foreach ($spine as $index => $row)
            @php
                $person = $row['person'];
                $isFolded = $foldedCount > 1 && $index >= $foldFrom && $index < $foldTo;
                $isFocus = $row['isFocus'];
            @endphp

            {{-- The fold control sits where the folded run begins. --}}
            @if ($foldedCount > 1 && $index === $foldFrom)
                <li class="gs-fold">
                    <button type="button" @click="midOpen = !midOpen"
                            :aria-expanded="midOpen ? 'true' : 'false'"
                            class="gs-fold-btn">
                        <i data-lucide="chevrons-down" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                        <span x-show="!midOpen">{{ trans_choice('genealogy.public.expand_generations', $foldedCount, ['count' => $foldedCount]) }}</span>
                        <span x-show="midOpen" x-cloak>{{ __('genealogy.public.collapse_generations') }}</span>
                    </button>
                </li>
            @endif

            <li class="gs-row{{ $isFocus ? ' gs-row--focus' : '' }}"
                @if ($isFolded) x-show="midOpen" x-cloak x-collapse @endif>

                {{-- Generation marker on the rail. --}}
                <span class="gs-marker{{ $isFocus ? ' gs-marker--focus' : '' }}" aria-hidden="true">
                    @if ($isFocus)
                        <i data-lucide="crown" class="w-3.5 h-3.5"></i>
                    @else
                        <span class="gs-marker-n tabular-nums">{{ $row['step'] }}</span>
                    @endif
                </span>

                <div class="gs-body">
                    <button type="button" @click="openPerson({{ (int) $person['id'] }})"
                            class="gs-card{{ $isFocus ? ' gs-card--focus' : '' }}">
                        <span class="gs-ava{{ $isFocus ? ' gs-ava--focus' : '' }}" aria-hidden="true">
                            {{ $person['monogram'] ?? mb_substr($person['name'], 0, 1) }}
                        </span>

                        <span class="gs-meta">
                            <span class="gs-name">{{ $person['name'] }}</span>

                            <span class="gs-tags">
                                @if ($isFocus)
                                    <span class="gs-tag gs-tag--focus">{{ __('genealogy.public.prophet_relation') }}</span>
                                @elseif ($row['isOldest'])
                                    <span class="gs-tag gs-tag--root">{{ __('genealogy.public.spine_root_note') }}</span>
                                @else
                                    {{-- The same wording the directory card
                                         uses for this person, so the two views
                                         never label him differently. --}}
                                    <span class="gs-tag">{{ $row['relationLabel'] }}</span>
                                @endif

                                @if (! $isFocus && $row['childName'])
                                    <span class="gs-tag gs-tag--soft">{{ __('genealogy.public.father_of', ['name' => $row['childName']]) }}</span>
                                @endif
                            </span>
                        </span>

                        @php
                            $life = array_filter([$person['birthDateHijri'] ?? null, $person['deathDateHijri'] ?? null]);
                        @endphp
                        @if ($life !== [])
                            <span class="gs-life tabular-nums">{{ implode(' – ', $life) }}</span>
                        @endif

                        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="gs-go" aria-hidden="true"></i>
                    </button>

                    {{-- The mother recorded for this step. She is not a second
                         competing spine — she hangs off the generation she
                         belongs to, beside the son she bore. --}}
                    @if ($row['mother'])
                        <button type="button" @click="openPerson({{ (int) $row['mother']['id'] }})" class="gs-mother">
                            <i data-lucide="heart" class="w-3.5 h-3.5 shrink-0" aria-hidden="true"></i>
                            <span class="gs-mother-name">{{ $row['mother']['name'] }}</span>
                            <span class="gs-mother-rel">{{ __('genealogy.public.mother_of', ['name' => $row['childName'] ?? '']) }}</span>
                        </button>
                    @endif

                    {{-- Collateral lines: the uncles and aunts on this rank,
                         each holding their own children. Folded by default —
                         fourteen uncles opened at once bury the chain. --}}
                    @if ($row['branches'] !== [])
                        <div x-data="{ open: false }" class="gs-branches">
                            <button type="button" @click="open = !open"
                                    :aria-expanded="open ? 'true' : 'false'"
                                    class="gs-branch-toggle">
                                <i data-lucide="git-branch" class="w-3.5 h-3.5 shrink-0" aria-hidden="true"></i>
                                <span>
                                    @if ($row['isFocus'])
                                        {{ __('genealogy.public.marriages') }} · {{ count($row['branches']) }}
                                    @else
                                        {{ __('genealogy.public.kin_uncles_aunts') }} · {{ count($row['branches']) }}
                                    @endif
                                </span>
                                <i data-lucide="chevron-down" class="w-4 h-4 shrink-0 gs-caret"
                                   :class="open && 'gs-caret--open'" aria-hidden="true"></i>
                            </button>

                            <div x-show="open" x-cloak x-collapse class="gs-branch-list">
                                @foreach ($row['branches'] as $branch)
                                    <div class="gs-branch">
                                        <button type="button" @click="openPerson({{ (int) $branch['id'] }})"
                                                class="gs-chip{{ ($branch['kind'] ?? '') === 'spouse' ? ' gs-chip--spouse' : '' }}">
                                            <span class="gs-chip-ava" aria-hidden="true">{{ $branch['monogram'] ?? mb_substr($branch['name'], 0, 1) }}</span>
                                            <span class="gs-chip-text">
                                                <span class="gs-chip-name">{{ $branch['name'] }}</span>
                                                @if ($branch['title'])
                                                    <span class="gs-chip-sub">{{ $branch['title'] }}</span>
                                                @endif
                                            </span>
                                        </button>

                                        {{-- One more hop: this branch's own
                                             children (the cousins, or the
                                             children a wife bore). --}}
                                        @if (($branch['children'] ?? []) !== [])
                                            <div class="gs-kids">
                                                @foreach ($branch['children'] as $kid)
                                                    <button type="button" @click="openPerson({{ (int) $kid['id'] }})" class="gs-kid">
                                                        {{ $kid['name'] }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    @unless ($lineageComplete)
        <p class="gs-note">
            <i data-lucide="info" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
            <span>{{ __('genealogy.public.lineage_incomplete') }}</span>
        </p>
    @endunless
</div>
