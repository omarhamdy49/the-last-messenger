<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MediaProgram;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Inline thumbnail change/remove from the episode hero (Facebook-style edit).
 */
class UpdateMediaEpisodeThumbnailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasAnyRole(['admin', 'super_admin']);
    }

    public function rules(): array
    {
        return [
            'thumbnail' => ['required_without:remove', 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'remove' => ['nullable', 'boolean'],
        ];
    }
}
