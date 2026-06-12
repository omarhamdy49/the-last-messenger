<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an authenticated visitor deleting their own account.
 *
 * The current password must be re-entered and is verified against the acting
 * user's hash (web guard). The type-to-confirm phrase is enforced client-side
 * in the modal; the password check is the server-side gate.
 */
final class DeleteAccountRequest extends FormRequest
{
    /**
     * Named bag so a wrong password here never bleeds into the change-password
     * form, which also validates a `password` field on the same page.
     *
     * @var string
     */
    protected $errorBag = 'deleteAccount';

    public function authorize(): bool
    {
        // Only ever deletes the authenticated user's own account.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password:web'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.current_password' => __('profile.delete_password_mismatch'),
        ];
    }
}
