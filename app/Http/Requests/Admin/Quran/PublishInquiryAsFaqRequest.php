<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin "publish inquiry as FAQ" action. Authorization is enforced by the admin
 * route group (web + auth + admin role).
 */
final class PublishInquiryAsFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
