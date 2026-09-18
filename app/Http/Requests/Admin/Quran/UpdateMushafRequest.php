<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

final class UpdateMushafRequest extends QuranAdminRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
        ];
    }
}
