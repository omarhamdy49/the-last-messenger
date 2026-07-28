<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

final class ToggleHomeSectionRequest extends ContentFormRequest
{
    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }
}
