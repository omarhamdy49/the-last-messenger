<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Domain\Content\Enums\MenuLocation;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

/**
 * Validates menu item creation. The link target must be either an
 * existing named route or a valid absolute URL.
 */
class StoreMenuItemRequest extends ContentFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'location' => ['required', 'string', Rule::in(MenuLocation::values())],
            'route_name' => [
                'nullable',
                'required_without:url',
                'string',
                'max:150',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && $value !== '' && ! Route::has($value)) {
                        $fail(__('admin.content_route_name_unknown'));
                    }
                },
            ],
            'url' => ['nullable', 'required_without:route_name', 'string', 'max:500', 'url'],
            'icon' => ['nullable', 'string', 'max:50'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('menu_items', 'id'),
                Rule::notIn([(int) ($this->route('menu') ?? 0)]),
            ],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            ...$this->localizedRules('label', true, ['string', 'max:150']),
        ];
    }

    /**
     * Treat empty link fields as absent so required_without works
     * for forms that submit empty strings.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'route_name' => $this->filled('route_name') ? $this->input('route_name') : null,
            'url' => $this->filled('url') ? $this->input('url') : null,
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
        ]);
    }
}
