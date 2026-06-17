<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin edit of a stored "Ask Quran" AI inquiry — the question and the
 * rich-text (WYSIWYG) answer. Authorization is enforced by the admin route
 * group (web + auth + admin role); the answer HTML is allowlist-sanitized
 * before validation so the persisted markup is safe.
 */
final class UpdateInquiryAnswerRequest extends FormRequest
{
    use SanitizesRichText;

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
            'question' => ['required', 'string', 'min:5', 'max:500'],
            'answer' => ['required', 'string', 'min:1', 'max:20000'],
        ];
    }

    /**
     * Sanitize the WYSIWYG answer HTML before validation so validated() is clean.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('answer'))) {
            $this->merge(['answer' => $this->sanitizeRichTextValue($this->input('answer')) ?? '']);
        }
    }
}
