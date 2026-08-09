<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a translation edition (resource).
 *
 * The form posts `name_ar` / `name_en` as separate fields but the column is a
 * JSON name map, so toAttributes() does that translation rather than the
 * controller.
 */
class StoreTranslationResourceRequest extends FormRequest
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
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('quran_translation_resources', 'slug')->ignore($this->resourceId())],
            'author' => ['nullable', 'string', 'max:255'],
            'language' => ['required', 'string', 'in:ar,en,en-tl,fr,tr,ur'],
            'direction' => ['required', 'string', 'in:rtl,ltr'],
            'source' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Column values for the edition.
     *
     * The two flags are read straight off the request, not out of validated():
     * an unchecked checkbox posts nothing at all, so a rule would have to be
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
            'direction' => $validated['direction'],
            'source' => $validated['source'] ?? null,
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
            'display_order' => $validated['display_order'] ?? 0,
        ];
    }

    /**
     * The edition being edited, so its own slug does not collide with itself.
     * Null on create.
     */
    private function resourceId(): ?int
    {
        $id = $this->route('translation_resource');

        return $id === null ? null : (int) $id;
    }
}
