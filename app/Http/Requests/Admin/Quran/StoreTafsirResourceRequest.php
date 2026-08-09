<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a tafsir source (resource).
 *
 * The form posts `name_ar` / `name_en` as separate fields but the column is a
 * JSON name map, so toAttributes() does that translation rather than the
 * controller.
 */
class StoreTafsirResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('quran_tafsir_resources', 'slug')->ignore($this->resourceId())],
            'author' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'string', 'in:ar,en,fr,tr,ur'],
            'source' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Column values for the source.
     *
     * `is_active` is read straight off the request, not out of validated(): an
     * unchecked checkbox posts nothing at all, so a rule would have to be
     * `nullable` and the absent value would come back as null rather than false.
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $validated = $this->validated();

        return [
            'slug' => $validated['slug'],
            'name' => ['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? $validated['name_ar']],
            'author' => $validated['author'] ?? null,
            'language' => $validated['language'],
            'source' => $validated['source'] ?? null,
            'is_active' => $this->boolean('is_active'),
            'display_order' => $validated['display_order'] ?? 0,
        ];
    }

    /**
     * The source being edited, so its own slug does not collide with itself.
     * Null on create.
     */
    private function resourceId(): ?int
    {
        $id = $this->route('tafsir_resource');

        return $id === null ? null : (int) $id;
    }
}
