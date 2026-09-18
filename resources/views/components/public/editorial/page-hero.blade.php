{{--
    Directory header: asymmetric — headline on the start side, a data ledger on the end.

    @props eyebrowUrl, eyebrowLabel, eyebrowIcon, title, lead, stats: list of {icon,label,value,small?}
    @slot default (optional, rendered under the lead — e.g. a search box)
--}}
@props(['eyebrowUrl' => null, 'eyebrowLabel' => null, 'eyebrowIcon' => 'route', 'title', 'lead' => null, 'stats' => []])

<header class="sx-dir-hero">
    <div class="grid gap-4 sx-rise">
        @if ($eyebrowLabel)
            <nav aria-label="breadcrumb">
                @if ($eyebrowUrl)
                    <a href="{{ $eyebrowUrl }}" class="sx-eyebrow hover:underline underline-offset-4"><i data-lucide="{{ $eyebrowIcon }}" aria-hidden="true"></i>{{ $eyebrowLabel }}</a>
                @else
                    <span class="sx-eyebrow"><i data-lucide="{{ $eyebrowIcon }}" aria-hidden="true"></i>{{ $eyebrowLabel }}</span>
                @endif
            </nav>
        @endif
        <h1 class="sx-display">{{ $title }}</h1>
        <span class="sx-signature" aria-hidden="true"></span>
        @if ($lead)<p class="sx-lead">{{ $lead }}</p>@endif
        {{ $slot }}
    </div>

    @if ($stats !== [])
        <dl class="sx-surface sx-ledger" style="grid-template-columns: repeat({{ count($stats) }}, minmax(0, 1fr))">
            @foreach ($stats as $stat)
                <div>
                    <dt><i data-lucide="{{ $stat['icon'] }}" aria-hidden="true"></i>{{ $stat['label'] }}</dt>
                    <dd @class(['!text-sm !font-bold' => $stat['small'] ?? false])>{{ $stat['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
</header>
