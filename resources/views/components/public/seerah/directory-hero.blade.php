{{--
    Events directory header: asymmetric — headline on the start side, a data ledger on the end.

    @props total (int), categories (int)
--}}
@props(['total' => 0, 'categories' => 0])

<header class="sx-dir-hero">
    <div class="grid gap-4 sx-rise">
        <nav aria-label="breadcrumb">
            <a href="{{ route('public.seerah') }}" class="sx-eyebrow hover:underline underline-offset-4">
                <i data-lucide="route" aria-hidden="true"></i>{{ __('seerah.public.breadcrumb_seerah') }}
            </a>
        </nav>
        <h1 class="sx-display">{{ __('seerah.public.events_heading') }}</h1>
        <span class="sx-signature" aria-hidden="true"></span>
        <p class="sx-lead">{{ __('seerah.public.events_subheading') }}</p>
    </div>

    <dl class="sx-surface sx-ledger">
        <div>
            <dt><i data-lucide="book-open" aria-hidden="true"></i>{{ __('seerah.public.results_count') }}</dt>
            <dd>{{ number_format($total) }}</dd>
        </div>
        <div>
            <dt><i data-lucide="layers" aria-hidden="true"></i>{{ __('seerah.public.categories_label') }}</dt>
            <dd>{{ $categories }}</dd>
        </div>
        <div>
            <dt><i data-lucide="hourglass" aria-hidden="true"></i>{{ __('seerah.public.stat_span') }}</dt>
            <dd class="!text-sm !font-bold">{{ __('seerah.public.events_span') }}</dd>
        </div>
    </dl>
</header>
