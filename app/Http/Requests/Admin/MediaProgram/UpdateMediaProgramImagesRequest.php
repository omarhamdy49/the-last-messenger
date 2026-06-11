<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MediaProgram;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Inline poster/banner update from the program hero (Facebook-style edit).
 */
class UpdateMediaProgramImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'poster' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'banner' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'poster_id' => ['nullable', 'integer', 'exists:media,id'],
            'banner_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }
}
