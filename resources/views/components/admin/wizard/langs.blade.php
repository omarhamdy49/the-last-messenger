{{--
    Content-language switch (segmented control). Binds to an Alpine prop (default `lang`).
    A green dot marks languages that already have content, via the component's `langFilled(code)`.

    @props languages: array<string code, string label>, model: string
--}}
@props(['languages' => [], 'model' => 'lang'])

<div class="wz-langs" role="group" aria-label="{{ __('wizard.languages') }}">
    @foreach($languages as $code => $label)
        <button type="button" class="wz-lang"
                :class="{ 'is-active': {{ $model }} === '{{ $code }}', 'is-filled': typeof langFilled === 'function' && langFilled('{{ $code }}') }"
                :aria-pressed="{{ $model }} === '{{ $code }}'"
                @click="{{ $model }} = '{{ $code }}'">
            <span class="wz-dot" aria-hidden="true"></span>{{ $label }}
        </button>
    @endforeach
</div>
