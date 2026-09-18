<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Base for Quran admin forms. Authorisation is the route's `permission:` middleware.
 * Any top-level field that has no rule is rejected with `unexpected_fields`, so a
 * form cannot write columns it was not designed to write.
 */
abstract class QuranAdminRequest extends FormRequest
{
    private const FRAMEWORK_FIELDS = ['_token', '_method'];

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $allowed = array_unique(array_map(static fn (string $rule): string => explode('.', $rule)[0], array_keys($this->rules())));
            $unexpected = array_diff(array_keys($this->all()), $allowed, self::FRAMEWORK_FIELDS);
            if ($unexpected !== []) {
                $v->errors()->add('unexpected_fields', __('quran.manage.unexpected_fields', ['fields' => implode(', ', $unexpected)]));
            }
        });
    }
}
