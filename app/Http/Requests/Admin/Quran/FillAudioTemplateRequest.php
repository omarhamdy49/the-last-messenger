<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

final class FillAudioTemplateRequest extends QuranAdminRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url_template' => ['required', 'string', 'max:500', 'regex:#^https?://#i'],
            'chapter' => ['nullable', 'integer', 'min:1', 'max:114'],
        ];
    }
}
