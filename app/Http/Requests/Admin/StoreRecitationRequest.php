<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Recitation Request
 * Validates data for adding a new per-surah audio recording to a reciter.
 */
class StoreRecitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by admin middleware
    }

    /**
     * Inject the reciter id from the route so the validator can use it for the
     * composite uniqueness rule.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reciter_id' => (int) $this->route('reciter'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $reciterId = (int) $this->route('reciter');

        return [
            'reciter_id' => ['required', 'integer', 'exists:quran_reciters,id'],
            'source_id' => ['nullable', 'integer', Rule::exists('quran_recording_sources', 'id')->where('recording_id', $reciterId)->where('kind', 'surah')],
            'surah_id' => [
                'required',
                'integer',
                'exists:quran_surahs,id',
                Rule::unique('quran_recitations', 'surah_id')
                    ->where(fn ($query) => $query
                        ->where('reciter_id', $reciterId)
                        ->when($this->filled('source_id'), fn ($q) => $q->where('source_id', $this->integer('source_id')))
                        ->where('format', $this->input('format'))
                    ),
            ],
            'audio_url' => ['required', 'url', 'max:500'],
            'format' => ['required', Rule::in(['mp3', 'aac', 'opus'])],
            'quality' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'file_size_kb' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom validation messages (Arabic-first).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'surah_id.required' => __('quran.recitations.validation.surah_required'),
            'surah_id.exists' => __('quran.recitations.validation.surah_invalid'),
            'surah_id.unique' => __('quran.recitations.validation.duplicate'),
            'audio_url.required' => __('quran.recitations.validation.audio_url_required'),
            'audio_url.url' => __('quran.recitations.validation.audio_url_invalid'),
            'audio_url.max' => __('quran.recitations.validation.audio_url_max'),
            'format.required' => __('quran.recitations.validation.format_required'),
            'format.in' => __('quran.recitations.validation.format_invalid'),
            'quality.in' => __('quran.recitations.validation.quality_invalid'),
            'duration_seconds.integer' => __('quran.recitations.validation.duration_invalid'),
            'file_size_kb.integer' => __('quran.recitations.validation.file_size_invalid'),
        ];
    }
}
