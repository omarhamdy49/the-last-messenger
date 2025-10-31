<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Shared\Enums\SearchModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Search Request Validation
 */
class SearchRequest extends FormRequest
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
        $moduleValues = array_map(fn ($case) => $case->value, SearchModule::cases());

        return [
            'query' => 'required|string|min:2|max:500',
            'module' => ['nullable', Rule::in($moduleValues)],
            'modules' => 'nullable|array',
            'modules.*' => Rule::in($moduleValues),
            'filters' => 'nullable|array',
            'filters.*' => 'nullable',
            'sort' => 'nullable|string|in:relevance,date,newest,oldest,popular,updated',
            'order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'highlight' => 'nullable|boolean',
            'facets' => 'nullable|boolean',
            'save_search' => 'nullable|boolean',
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
            'query' => 'search query',
            'module' => 'search module',
            'modules' => 'search modules',
            'per_page' => 'results per page',
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
            'query.required' => 'Please enter a search query',
            'query.min' => 'Search query must be at least 2 characters',
            'query.max' => 'Search query cannot exceed 500 characters',
            'per_page.max' => 'Cannot request more than 100 results per page',
        ];
    }
}
