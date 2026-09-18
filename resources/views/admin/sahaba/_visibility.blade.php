{{--
    Where this person does and does not appear on the public site, and what the
    missing flag is in each case.

    Every condition below mirrors a real query, not a guess:
      - directory  → Site\SahabaController::index — published + `sahabi` role
      - carousel   → HomeController::index — the same, plus a featured pivot row
      - tree       → GenealogyTreeService::relations — published + not hidden
    A flag flipped here and not there is the single most common reason an editor
    cannot find a person they just saved.

    @param \App\Domain\People\Models\Muslim $muslim
--}}
@php
    $roleSlugs = $muslim->roles->pluck('slug')->all();
    $isSahabi = in_array('sahabi', $roleSlugs, true);
    $isFeatured = $muslim->roles->contains(fn ($role) => (bool) $role->pivot->is_featured);
    $isPublished = (bool) $muslim->is_published;

    $surfaces = [
        [
            'key' => 'profile',
            'icon' => 'globe',
            'label' => __('sahaba.visibility.profile'),
            'ok' => $isPublished,
            'blocker' => $isPublished ? null : __('sahaba.visibility.needs_published'),
        ],
        [
            'key' => 'directory',
            'icon' => 'users',
            'label' => __('sahaba.visibility.directory'),
            'ok' => $isPublished && $isSahabi,
            'blocker' => match (true) {
                ! $isSahabi => __('sahaba.visibility.needs_sahabi_role'),
                ! $isPublished => __('sahaba.visibility.needs_published'),
                default => null,
            },
        ],
        [
            'key' => 'carousel',
            'icon' => 'star',
            'label' => __('sahaba.visibility.home_carousel'),
            'ok' => $isPublished && $isSahabi && $isFeatured,
            'blocker' => match (true) {
                ! $isSahabi => __('sahaba.visibility.needs_sahabi_role'),
                ! $isPublished => __('sahaba.visibility.needs_published'),
                ! $isFeatured => __('sahaba.visibility.needs_featured'),
                default => null,
            },
        ],
        [
            'key' => 'tree',
            'icon' => 'git-fork',
            'label' => __('sahaba.visibility.tree'),
            'ok' => $isPublished && ! $muslim->hide_from_tree,
            'blocker' => match (true) {
                (bool) $muslim->hide_from_tree => __('sahaba.visibility.needs_tree_visible'),
                ! $isPublished => __('sahaba.visibility.needs_published'),
                default => null,
            },
        ],
    ];

    $liveCount = collect($surfaces)->where('ok', true)->count();
@endphp

<div class="rounded-2xl border overflow-hidden"
     :class="darkMode ? 'bg-gray-900/60 border-gray-800' : 'bg-white border-gray-200'"
     data-testid="visibility-report">

    <div class="px-5 py-4 border-b flex items-center justify-between gap-3"
         :class="darkMode ? 'border-gray-800' : 'border-gray-200'">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="p-2 rounded-lg bg-gradient-to-br from-[#0056b3] to-[#218838] shrink-0">
                <i data-lucide="radar" class="w-4 h-4 text-white"></i>
            </span>
            <div class="min-w-0">
                <h2 class="text-sm font-bold truncate" :class="darkMode ? 'text-white' : 'text-gray-900'">
                    {{ __('sahaba.visibility.title') }}
                </h2>
                <p class="text-xs truncate" :class="darkMode ? 'text-gray-500' : 'text-gray-500'">
                    {{ __('sahaba.visibility.subtitle') }}
                </p>
            </div>
        </div>

        <span class="shrink-0 px-2.5 py-1 rounded-lg text-xs font-bold tabular-nums"
              :class="darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-700'">
            {{ $liveCount }}/{{ count($surfaces) }}
        </span>
    </div>

    <ul class="divide-y" :class="darkMode ? 'divide-gray-800' : 'divide-gray-100'">
        @foreach ($surfaces as $surface)
            <li class="px-5 py-3 flex items-start gap-3" data-testid="visibility-{{ $surface['key'] }}">
                {{-- Icon AND text carry the state: colour alone would fail both
                     a colour-blind editor and a screen reader. --}}
                <i data-lucide="{{ $surface['ok'] ? 'check-circle-2' : 'x-circle' }}"
                   class="w-4 h-4 mt-0.5 shrink-0 {{ $surface['ok'] ? 'text-emerald-500' : 'text-gray-400' }}"></i>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold flex items-center gap-1.5"
                       :class="darkMode ? 'text-gray-200' : 'text-gray-800'">
                        <i data-lucide="{{ $surface['icon'] }}" class="w-3.5 h-3.5 opacity-50"></i>
                        {{ $surface['label'] }}
                    </p>

                    @if ($surface['blocker'])
                        <p class="mt-0.5 text-xs" :class="darkMode ? 'text-amber-400' : 'text-amber-700'">
                            {{ $surface['blocker'] }}
                        </p>
                    @endif
                </div>

                @if ($surface['ok'])
                    <span class="shrink-0 text-xs font-semibold text-emerald-500">
                        {{ __('sahaba.visibility.live') }}
                    </span>
                @else
                    <span class="shrink-0 text-xs font-semibold"
                          :class="darkMode ? 'text-gray-500' : 'text-gray-400'">
                        {{ __('sahaba.visibility.hidden') }}
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
</div>
