<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\People\Models\Muslim;
use App\Domain\Quran\Models\Verse;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Enums\InteractionType;
use App\Models\Article;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Interaction Request
 * Validates interaction recording data
 */
class StoreInteractionRequest extends FormRequest
{
    /**
     * Stable wire names mapped to trusted model classes. Clients never supply
     * an executable PHP class name.
     *
     * @var array<string, class-string<Model>>
     */
    public const CONTENT_TYPES = [
        'Article' => Article::class,
        'Hadith' => Hadith::class,
        'MediaProgram' => MediaProgram::class,
        'MediaEpisode' => MediaEpisode::class,
        'SeerahEvent' => SeerahEvent::class,
        'Sahabi' => Muslim::class,
        // Chapters are not engageable content; only verses are (006).
        'QuranVerse' => Verse::class,
    ];

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'interactable_type' => ['required', 'string', Rule::in(array_keys(self::CONTENT_TYPES))],
            'interactable_id' => ['required', 'integer', 'min:1'],
            'interaction_type' => ['required', 'string', Rule::enum(InteractionType::class)],
            'metadata' => ['nullable', 'array', 'max:20'],
            'metadata.*' => ['nullable', 'string', 'max:500'],
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

    /** @return class-string<Model> */
    public function interactableClass(): string
    {
        return self::CONTENT_TYPES[(string) $this->validated('interactable_type')];
    }
}
