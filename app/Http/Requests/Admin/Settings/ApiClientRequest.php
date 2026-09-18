<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use App\Models\ApiClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create/update an API client. `key` and the secret are never accepted from input.
 */
final class ApiClientRequest extends FormRequest
{
    private const ALLOWED_FIELDS = ['name', 'scopes', 'allowed_origins', 'rate_limit_per_minute', 'expires_at', 'is_active', '_token', '_method'];

    public function authorize(): bool
    {
        return true; // route middleware: permission:manage api clients
    }

    protected function prepareForValidation(): void
    {
        $origins = $this->input('allowed_origins');
        if (is_string($origins)) {
            $this->merge(['allowed_origins' => array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $origins) ?: [])))]);
        }
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string', Rule::in(ApiClient::SCOPES)],
            'allowed_origins' => ['nullable', 'array', 'max:20'],
            // scheme://host[:port], no path, query or credentials
            'allowed_origins.*' => ['string', 'max:255', 'regex:#^https?://[a-z0-9.-]+(:\d{1,5})?/?$#i'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:6000'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $unexpected = array_diff(array_keys($this->all()), self::ALLOWED_FIELDS);
            if ($unexpected !== []) {
                $v->errors()->add('unexpected_fields', __('admin.api_clients.unexpected_fields', ['fields' => implode(', ', $unexpected)]));
            }
        });
    }
}
