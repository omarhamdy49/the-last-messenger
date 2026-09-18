{{-- Labelled facts that overlap the hero's bottom edge. @props facts: list of {icon,label,value} --}}
@props(['facts'])

@if (collect($facts)->isNotEmpty())
    <div class="sx-wrap">
        <dl class="sx-surface sx-facts overflow-hidden">
            @foreach ($facts as $fact)
                <div>
                    <dt><i data-lucide="{{ $fact['icon'] }}" aria-hidden="true"></i>{{ $fact['label'] }}</dt>
                    <dd>{{ $fact['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </div>
@endif
