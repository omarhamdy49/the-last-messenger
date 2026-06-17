<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Http\Support\QuestionSanitizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an "Ask Quran" AI submission. Registered users only; the question is
 * length-bounded by config (default 10–200 chars) and sanitised to an allowlist
 * (letters/digits/whitespace + ! ? - _) before validation. Geo arrives via
 * headers and is handled (whitelisted) in the controller, not here.
 */
final class AskQuranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $min = (int) config('services.quran_ai.min_chars', 10);
        $max = (int) config('services.quran_ai.max_chars', 200);

        return [
            // The string is already reduced to the allowlist in prepareForValidation;
            // the regex makes that contract explicit and rejects anything that slips through.
            'question' => ['required', 'string', 'min:'.$min, 'max:'.$max, 'regex:'.QuestionSanitizer::PATTERN],
            // Opt-in to a thorough answer (costs an extra quota slot). Read via boolean().
            'detailed' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('question'))) {
            $this->merge(['question' => QuestionSanitizer::sanitize($this->input('question'))]);
        }
    }
}
