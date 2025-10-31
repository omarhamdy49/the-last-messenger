<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Shared\Enums\SearchModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Save Filter Request Validation
 */
class SaveFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $moduleValues = array_map(
            fn ($case) => $case->value,
            array_filter(SearchModule::cases(), fn ($case) => $case !== SearchModule::ALL)
        );

        return [
            'name' => 'required|string|min:3|max:100',
            'description' => 'nullable|string|max:500',
            'module' => ['required', Rule::in($moduleValues)],
            'filters' => 'required|array',
            'filters.*' => 'nullable',
            'sort_options' => 'nullable|array',
            'sort_options.sort' => 'nullable|string',
            'sort_options.order' => 'nullable|string|in:asc,desc',
            'is_public' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attribute names
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'filter name',
            'module' => 'search module',
            'is_public' => 'public visibility',
            'is_default' => 'default filter',
        ];
    }

    /**
     * Get custom error messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a name for this filter',
            'name.min' => 'Filter name must be at least 3 characters',
            'module.required' => 'Please select a module for this filter',
            'filters.required' => 'Please define at least one filter condition',
        ];
    }
}
