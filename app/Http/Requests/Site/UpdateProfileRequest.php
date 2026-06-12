<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates an authenticated visitor updating their own profile (name, email,
 * preferred language). Email uniqueness ignores the current user's row.
 */
final class UpdateProfileRequest extends FormRequest
{
    /**
     * Supported UI languages (mirrors LanguageController::$supportedLanguages).
     *
     * @var list<string>
     */
    private const SUPPORTED_LANGUAGES = ['ar', 'en', 'fr', 'tr', 'ur'];

    public function authorize(): bool
    {
        // Only ever operates on the authenticated user's own record; the `auth`
        // middleware guards the route. Object-level ownership is implicit.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getKey();

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'language' => ['required', 'string', Rule::in(self::SUPPORTED_LANGUAGES)],
        ];
    }
}
