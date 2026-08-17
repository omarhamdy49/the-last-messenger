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
            // Cap at 200, whitelist letters/digits/spaces and ordinary sentence
            // punctuation (Arabic ، ؛ ؟ included — an Arabic-first form that
            // rejects a full stop rejects almost every real message).
            // Links are what lead-gen spam actually carries, so they are blocked
            // by their own rule rather than by banning the dot.
            'message' => [
                'required',
                'string',
                'max:200',
                'regex:/^[\p{L}\p{N}\s?!\-_.,:;()\x{060C}\x{061B}\x{061F}]+$/u',
                'not_regex:/https?:\/\/|www\.|\S@\S|\.[a-z]{2,}\b/iu',
            ],
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
            'message.not_regex' => __('site.contact_message_no_links'),
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
