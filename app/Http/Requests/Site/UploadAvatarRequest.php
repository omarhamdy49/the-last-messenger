<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates an authenticated visitor uploading their own profile avatar.
 *
 * Both the extension and the real MIME type are whitelisted (jpg/png/webp),
 * size is capped at 2 MB and dimensions must be sane. The client filename is
 * never trusted — storage uses a server-generated hashed name.
 */
final class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Always operates on the acting user's own record (no IDOR surface).
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'file',
                'image',
                // MIME sniffed from content + literal extension, both whitelisted (§6).
                'mimetypes:image/jpeg,image/png,image/webp',
                'extensions:jpg,jpeg,png,webp',
                'max:2048',
                Rule::dimensions()
                    ->minWidth(64)->minHeight(64)
                    ->maxWidth(4096)->maxHeight(4096),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => __('profile.avatar_invalid'),
            'avatar.mimetypes' => __('profile.avatar_invalid'),
            'avatar.dimensions' => __('profile.avatar_dimensions'),
        ];
    }
}
