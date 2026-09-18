{{-- Key/value details. @props id, title, icon, items: list of {key,value}, index --}}
@props(['id' => 'more-details', 'title', 'icon' => 'list', 'items', 'index' => null])

@if (collect($items)->isNotEmpty())
    <x-public.editorial.section :id="$id" :icon="$icon" :title="$title" :index="$index">
        <dl class="sx-kv">
            @foreach ($items as $item)
                <div><dt>{{ $item['key'] }}</dt><dd>{{ $item['value'] }}</dd></div>
            @endforeach
        </dl>
    </x-public.editorial.section>
@endif
