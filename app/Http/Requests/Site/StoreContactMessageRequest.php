<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

final class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public contact form — open to guests; abuse is curbed by the
        // route-level throttle and the honeypot field below.
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            // Cap at 200 and whitelist letters/digits/spaces + ? - _ ! only.
            // Blocking . : / @ kills the URLs every lead-gen spam carries.
            'message' => ['required', 'string', 'max:200', 'regex:/^[\p{L}\p{N}\s?\-_!]+$/u'],
            // Honeypot: hidden field humans never fill — any value rejects.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.regex' => __('site.contact_message_charset'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('site.contact_name'),
            'email' => __('site.contact_email'),
            'subject' => __('site.contact_subject'),
            'message' => __('site.contact_message'),
        ];
    }
}
