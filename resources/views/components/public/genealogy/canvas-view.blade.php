{{--
    مكون عرض اللوحة - إطارات العرض الواسعة، والورقة بملء الشاشة على الجوال.

    البطاقات هي أزرار DOM موضوعة بشكل مطلق في مشهد محول، وليست
    <foreignObject> (انظر رأس resources/js/genealogy-tree.js لمعرفة السبب).
    فقط مسارات الموصل هي SVG.

    المعاملات المطلوبة:
    @param bool $hasGraph - هل يوجد رسم بياني متاح
    @param bool $hasSpine - هل يوجد عرض سلسلة متاح
    @param bool $isRtl - هل اللغة من اليمين لليسار
--}}
@props([
    'hasGraph' => false,
    'hasSpine' => false,
    'isRtl' => true,
])

@if ($hasSpine)
    {{-- زر لفتح اللوحة بملء الشاشة على الجوال --}}
    @if ($hasGraph)
        <button type="button" @click="openCanvas()"
                class="mt-5 inline-flex items-center justify-center gap-2 w-full min-h-[3rem] px-6 rounded-xl
                       text-sm font-bold text-white active:scale-[0.98]
                       bg-gradient-to-l from-primary to-secondary
                       shadow-[0_10px_26px_-12px_rgba(0,86,179,.7)] transition-transform">
            <i data-lucide="maximize-2" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
            <span>{{ __('genealogy.public.open_canvas') }}</span>
        </button>

        <p x-show="canvasError" x-cloak role="alert" class="gs-note mt-2">
            <i data-lucide="triangle-alert" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
            <span>{{ __('genealogy.public.canvas_failed') }}</span>
        </p>
    @endif
@endif

@if ($hasGraph)
    {{-- اللوحة. مضمنة على إطارات العرض الواسعة؛ على الهاتف توجد
         فقط كورقة بملء الشاشة، لأن اللوحة القابلة للتحريك
         المضمنة ستبتلع تمرير الصفحة. --}}
    <div x-show="canvasFullscreen" x-cloak>

        <div x-ref="treeStage" class="gt-stage gt-stage--full">
            <div class="gt-controls" x-show="treeReady" x-transition.opacity>
                <button type="button" class="gt-ctrl gt-ctrl--close"
                        @click="closeCanvas()"
                        title="{{ __('genealogy.public.close_canvas') }}"
                        aria-label="{{ __('genealogy.public.close_canvas') }}">
                    <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
                </button>
                <button type="button" class="gt-ctrl" @click="treeApi?.zoomIn()"
                        title="{{ __('genealogy.public.zoom_in') }}" aria-label="{{ __('genealogy.public.zoom_in') }}">
                    <i data-lucide="zoom-in" class="w-4 h-4" aria-hidden="true"></i>
                </button>
                <button type="button" class="gt-ctrl" @click="treeApi?.zoomOut()"
                        title="{{ __('genealogy.public.zoom_out') }}" aria-label="{{ __('genealogy.public.zoom_out') }}">
                    <i data-lucide="zoom-out" class="w-4 h-4" aria-hidden="true"></i>
                </button>
                <button type="button" class="gt-ctrl" @click="treeApi?.centerFocus()"
                        title="{{ __('genealogy.public.center_focus') }}" aria-label="{{ __('genealogy.public.center_focus') }}">
                    <i data-lucide="crosshair" class="w-4 h-4" aria-hidden="true"></i>
                </button>
                <button type="button" class="gt-ctrl" @click="treeApi?.fit()"
                        title="{{ __('genealogy.public.fit_tree') }}" aria-label="{{ __('genealogy.public.fit_tree') }}">
                    <i data-lucide="maximize-2" class="w-4 h-4" aria-hidden="true"></i>
                </button>
                <button type="button" class="gt-ctrl hidden sm:flex" @click="treeApi?.expandAll()"
                        title="{{ __('genealogy.public.expand_all') }}" aria-label="{{ __('genealogy.public.expand_all') }}">
                    <i data-lucide="chevrons-down" class="w-4 h-4" aria-hidden="true"></i>
                </button>
                <button type="button" class="gt-ctrl hidden sm:flex" @click="treeApi?.collapseAll()"
                        title="{{ __('genealogy.public.collapse_all') }}" aria-label="{{ __('genealogy.public.collapse_all') }}">
                    <i data-lucide="chevrons-up" class="w-4 h-4" aria-hidden="true"></i>
                </button>
            </div>

            <div x-show="!treeReady" class="absolute inset-0 z-[5] flex flex-col items-center justify-center gap-3">
                <i data-lucide="loader-2" class="w-8 h-8 text-primary dark:text-accent animate-spin" aria-hidden="true"></i>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                    {{ __('genealogy.public.canvas_label') }}
                </span>
            </div>

            {{-- يتم تركيب المشهد هنا. --}}
            <div x-ref="treeCanvas" class="absolute inset-0"></div>
        </div>

    </div>
@endif
