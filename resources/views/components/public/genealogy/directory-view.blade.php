{{--
    مكون عرض الدليل

    ما يستبدله: ثمانين بطاقة "ميدالية مكررة" كاملة العرض - صورة بحجم
    176 بكسل لكل منها - بترتيب المشي في الرسم البياني، بدون أي إشارة إلى
    من كان أي من هؤلاء الناس للنبي ﷺ.

    ما هو الآن: صفوف مدمجة مجمعة حسب القرابة، بالترتيب الذي يُقرأ به
    النسب. تسمية العلاقة هي نقطة البطاقة، وليست فكرة لاحقة، واللون
    يحمل نفس المعنى الذي يحمله في الشجرة بدلاً من التناوب عبر لوحة
    للتنوع.

    المعاملات المطلوبة:
    @param array $people - قائمة الأشخاص
    @param bool $hasGraph - هل يوجد رسم بياني للشجرة
    @param bool $isRtl - هل اللغة من اليمين لليسار
--}}
@props([
    'people' => [],
    'hasGraph' => false,
    'isRtl' => true,
])

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
                                        {{-- العلاقة هي سبب وجود هذه البطاقة هنا.
                                             إنها تقود، وهي نص - ليس اللون فقط. --}}
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
