{{-- Editor-defined key/value details. @props details, index --}}
@props(['details', 'index' => null])

@if (collect($details)->isNotEmpty())
    <x-public.seerah.section id="more-details" icon="list" :title="__('seerah.public.section_more_details')" :index="$index">
        <dl class="sx-kv">
            @foreach ($details as $detail)
                <div>
                    <dt>{{ $detail['key'] }}</dt>
                    <dd>{{ $detail['value'] }}</dd>
                </div>
            @endforeach
        </dl>
    </x-public.seerah.section>
@endif
