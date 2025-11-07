{{-- Recursive option for parent category dropdown --}}
<option value="{{ $category->id }}"
        {{ $selected == $category->id ? 'selected' : '' }}
        @if(isset($currentCategory) && $category->id === $currentCategory->id) disabled @endif>
    {{ str_repeat('─ ', $depth) }}{{ $category->name }}
</option>

@if($category->children && count($category->children) > 0)
    @foreach($category->children as $child)
        @include('admin.categories._parent-option', [
            'category' => $child,
            'depth' => $depth + 1,
            'selected' => $selected
        ])
    @endforeach
@endif
