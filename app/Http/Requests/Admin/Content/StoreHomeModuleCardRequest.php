<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Domain\Content\Enums\HomeModuleCardPlacement;
use Illuminate\Validation\Rule;

/**
 * Validates home module card creation.
 */
class StoreHomeModuleCardRequest extends ContentFormRequest
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
                Rule::unique('home_module_cards', 'key')->ignore($this->route('home_card')),
            ],
            'placement' => ['required', Rule::enum(HomeModuleCardPlacement::class)],
            'icon' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'string', 'max:500'],
            'target_url' => ['nullable', 'string', 'max:500'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            ...$this->localizedRules('title', true, ['string', 'max:255']),
            ...$this->localizedRules('description', false, ['string', 'max:1000']),
        ];
    }
}
