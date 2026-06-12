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
            // Visibility toggle posts is_featured alone; the edit modal posts
            // the presentation fields without it. At least one must be present.
            'is_featured' => ['required_without:name', 'boolean'],
            'name' => ['nullable', 'array'],
            'name.*' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
