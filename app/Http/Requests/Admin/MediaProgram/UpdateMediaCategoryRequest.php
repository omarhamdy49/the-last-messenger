<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MediaProgram;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'is_featured' => ['required', 'boolean'],
        ];
    }
}
