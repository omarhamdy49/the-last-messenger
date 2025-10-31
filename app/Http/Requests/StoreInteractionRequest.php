<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Shared\Enums\InteractionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Interaction Request
 * Validates interaction recording data
 */
class StoreInteractionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'interactable_type' => ['required', 'string', Rule::in([
                'App\\Domain\\Blog\\Models\\Article',
                'App\\Domain\\Hadith\\Models\\Hadith',
                'App\\Domain\\Media\\Models\\MediaProgram',
                'App\\Domain\\Media\\Models\\MediaEpisode',
                'App\\Domain\\Seerah\\Models\\SeerahEvent',
                'App\\Domain\\Sahaba\\Models\\Sahabi',
                'App\\Domain\\Quran\\Models\\QuranVerse',
                'App\\Domain\\Quran\\Models\\QuranSurah',
            ])],
            'interactable_id' => ['required', 'integer', 'min:1'],
            'interaction_type' => ['required', 'string', Rule::enum(InteractionType::class)],
            'metadata' => ['nullable', 'array'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'], // Max 24 hours
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'interactable_type.required' => 'Content type is required',
            'interactable_type.in' => 'Invalid content type',
            'interactable_id.required' => 'Content ID is required',
            'interactable_id.integer' => 'Content ID must be an integer',
            'interaction_type.required' => 'Interaction type is required',
            'interaction_type.enum' => 'Invalid interaction type',
            'duration_seconds.integer' => 'Duration must be an integer',
            'duration_seconds.max' => 'Duration cannot exceed 24 hours',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Allow short class names and convert to full class name
        if ($this->has('interactable_type')) {
            $shortToFull = [
                'Article' => 'App\\Domain\\Blog\\Models\\Article',
                'Hadith' => 'App\\Domain\\Hadith\\Models\\Hadith',
                'MediaProgram' => 'App\\Domain\\Media\\Models\\MediaProgram',
                'MediaEpisode' => 'App\\Domain\\Media\\Models\\MediaEpisode',
                'SeerahEvent' => 'App\\Domain\\Seerah\\Models\\SeerahEvent',
                'Sahabi' => 'App\\Domain\\Sahaba\\Models\\Sahabi',
                'QuranVerse' => 'App\\Domain\\Quran\\Models\\QuranVerse',
                'QuranSurah' => 'App\\Domain\\Quran\\Models\\QuranSurah',
            ];

            $type = $this->input('interactable_type');
            if (isset($shortToFull[$type])) {
                $this->merge(['interactable_type' => $shortToFull[$type]]);
            }
        }
    }
}
