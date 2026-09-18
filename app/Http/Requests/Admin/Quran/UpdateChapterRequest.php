<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

final class UpdateChapterRequest extends QuranAdminRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:150'],
            'name.*' => ['nullable', 'string', 'max:150'],
            'is_published' => ['boolean'],
            'revelation_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'cover' => ['nullable', 'image', 'max:5120'],
            'remove_cover' => ['boolean'],
        ];
    }
}
