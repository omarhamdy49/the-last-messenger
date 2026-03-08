<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

class CropMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit media') ?? false;
    }

    public function rules(): array
    {
        return [
            'x' => ['required', 'numeric', 'min:0'],
            'y' => ['required', 'numeric', 'min:0'],
            'width' => ['required', 'numeric', 'min:1'],
            'height' => ['required', 'numeric', 'min:1'],
        ];
    }
}
