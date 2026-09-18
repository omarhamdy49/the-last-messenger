<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

final class BulkLockVersesRequest extends QuranAdminRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['integer', 'min:1'],
            'locked' => ['required', 'boolean'],
        ];
    }
}
