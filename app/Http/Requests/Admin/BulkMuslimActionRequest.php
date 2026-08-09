<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Bulk Muslim Action Request (Admin)
 *
 * The bulk delete/feature/publish endpoints previously took a bare Request and
 * only ran the ids through intval(), so an empty or malformed selection passed
 * silently instead of being rejected.
 */
class BulkMuslimActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route group already restricts this to admin-tier users.
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:muslims,id'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function ids(): array
    {
        return array_values(array_unique(array_map('intval', $this->validated('ids'))));
    }
}
