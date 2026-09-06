{{--
    مكون بطاقة الشخص

    بطاقة شخص موحدة قابلة لإعادة الاستخدام لعرض معلومات الشخص الأساسية
    في سياقات مختلفة (السلسلة، الدليل، اللوحة).

    المعاملات المطلوبة:
    @param array $person - بيانات الشخص (id, name, monogram, relation, relationLabel, etc.)
    @param string $variant - نوع البطاقة: 'spine', 'directory', 'canvas', 'chip'
    @param bool $isFocus - هل هذا الشخص هو محور التركيز (النبي ﷺ)
    @param bool $isRtl - هل اللغة من اليمين لليسار
    @param bool $showLife - عرض تواريخ الميلاد والوفاة
    @param string|null $childName - اسم الطفل (للعرض "والد...")
--}}
@props([
    'person' => [],
    'variant' => 'directory',
    'isFocus' => false,
    'isRtl' => true,
    'showLife' => false,
    'childName' => null,
])

@php
    $personId = $person['id'] ?? 0;
    $personName = $person['name'] ?? __('genealogy.public.unnamed');
    $monogram = $person['monogram'] ?? mb_substr($personName, 0, 1);
    $relation = $person['relation'] ?? 'relative';
    $relationLabel = $person['relationLabel'] ?? '';
    $title = $person['title'] ?? '';
    $isProphet = $person['isProphet'] ?? false;
    $birthDate = $person['birthDateHijri'] ?? null;
    $deathDate = $person['deathDateHijri'] ?? null;
    $life = array_filter([$birthDate, $deathDate]);
@endphp

@if ($variant === 'spine')
    {{-- بطاقة السلسلة - الأكبر والأكثر تفصيلاً --}}
    <button type="button" @click="openPerson({{ (int) $personId }})"
            class="gs-card{{ $isFocus ? ' gs-card--focus' : '' }}">
        <span class="gs-ava{{ $isFocus ? ' gs-ava--focus' : '' }}" aria-hidden="true">
            {{ $monogram }}
        </span>

        <span class="gs-meta">
            <span class="gs-name">{{ $personName }}</span>

            <span class="gs-tags">
                @if ($isFocus)
                    <span class="gs-tag gs-tag--focus">{{ __('genealogy.public.prophet_relation') }}</span>
                @else
                    <span class="gs-tag">{{ $relationLabel }}</span>
                @endif

                @if (! $isFocus && $childName)
                    <span class="gs-tag gs-tag--soft">{{ __('genealogy.public.father_of', ['name' => $childName]) }}</span>
                @endif
            </span>
        </span>

        @if ($showLife && $life !== [])
            <span class="gs-life tabular-nums">{{ implode(' – ', $life) }}</span>
        @endif

        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="gs-go" aria-hidden="true"></i>
    </button>

@elseif ($variant === 'directory')
    {{-- بطاقة الدليل - مدمجة مع التركيز على العلاقة --}}
    <button type="button" @click="openPerson({{ (int) $personId }})"
            :title="'{{ $personName }}'"
            class="gl-card card-enter">

        <span class="gl-ava gl-ava--{{ $relation }}" aria-hidden="true">
            @if ($isProphet)
                <i data-lucide="crown" class="w-4 h-4"></i>
            @else
                {{ $monogram }}
            @endif
        </span>

        <span class="gl-meta">
            <span class="gl-name">{{ $personName }}</span>

            <span class="gl-tags">
                <span class="gl-rel gl-rel--{{ $relation }}">{{ $relationLabel }}</span>

                @if ($deathDate)
                    <span class="gl-date tabular-nums">
                        <i data-lucide="calendar" class="w-3 h-3 shrink-0" aria-hidden="true"></i>
                        <span>{{ $deathDate }}</span>
                    </span>
                @endif
            </span>
        </span>

        <i data-lucide="chevron-{{ $isRtl ? 'left' : 'right' }}" class="gl-go" aria-hidden="true"></i>
    </button>

@elseif ($variant === 'chip')
    {{-- رقاقة مدمجة - للفروع والزوجات والأطفال --}}
    <button type="button" @click="openPerson({{ (int) $personId }})"
            class="gs-chip{{ $relation === 'wife' ? ' gs-chip--spouse' : '' }}">
        <span class="gs-chip-ava" aria-hidden="true">{{ $monogram }}</span>
        <span class="gs-chip-text">
            <span class="gs-chip-name">{{ $personName }}</span>
            @if ($title)
                <span class="gs-chip-sub">{{ $title }}</span>
            @endif
        </span>
    </button>

@elseif ($variant === 'canvas')
    {{-- بطاقة اللوحة - للعرض التفاعلي --}}
    <button type="button" @click="openPerson({{ (int) $personId }})"
            class="gt-card{{ $isFocus ? ' gt-card--focus' : '' }}{{ $relation === 'ancestor' ? ' gt-card--ancestor' : '' }}{{ $relation === 'wife' ? ' gt-card--spouse' : '' }}">

        @if ($isProphet)
            <span class="gt-crown">
                <i data-lucide="crown" class="w-3.5 h-3.5"></i>
            </span>
        @endif

        <span class="gt-ava{{ $isFocus ? ' gt-ava--focus' : '' }}" aria-hidden="true">
            {{ $monogram }}
        </span>

        <span class="gt-meta">
            <span class="gt-name">{{ $personName }}</span>
            @if ($title || $relationLabel)
                <span class="gt-sub">{{ $title ?: $relationLabel }}</span>
            @endif
        </span>
    </button>

@else
    {{-- الافتراضي: بطاقة بسيطة --}}
    <div class="flex items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-800">
        <span class="w-8 h-8 rounded-full flex items-center justify-center
                     bg-gradient-to-br from-primary to-secondary
                     text-white font-bold text-sm">
            {{ $monogram }}
        </span>
        <span class="flex-1 min-w-0">
            <span class="block font-semibold text-sm text-gray-900 dark:text-white truncate">
                {{ $personName }}
            </span>
            @if ($relationLabel)
                <span class="block text-xs text-gray-600 dark:text-gray-400 truncate">
                    {{ $relationLabel }}
                </span>
            @endif
        </span>
    </div>
@endif
