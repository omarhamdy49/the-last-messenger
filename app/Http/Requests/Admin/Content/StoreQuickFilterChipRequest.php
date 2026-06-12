<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use Illuminate\Validation\Rule;

/**
 * Validates quick-filter chip creation.
 */
class StoreQuickFilterChipRequest extends ContentFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_-]+$/',
                Rule::unique('quick_filter_chips', 'key')->ignore($this->route('quick_filter')),
            ],
            'target_query' => ['nullable', 'string', 'max:255'],
            'target_url' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            ...$this->localizedRules('label', true, ['string', 'max:100']),
        ];
    }
}
