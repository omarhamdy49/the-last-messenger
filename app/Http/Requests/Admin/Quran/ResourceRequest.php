<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\QuranResource;
use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Validation\Rule;

/**
 * Create (kind from the route segment) or update (kind from the stored row) a catalogue
 * resource. `kind`, `qf_id` and `source` are never accepted from the body.
 */
final class ResourceRequest extends QuranAdminRequest
{
    use SanitizesRichText;

    private ?QuranResource $existing = null;

    public function kind(): ResourceKind
    {
        $segment = $this->route('kind');
        if (is_string($segment)) {
            return ResourceKind::fromRouteSegment($segment) ?? abort(404);
        }

        return $this->existing()?->kind ?? abort(404);
    }

    public function existing(): ?QuranResource
    {
        $id = $this->route('resource');

        return $this->existing ??= $id === null ? null : QuranResource::query()->select(['id', 'kind', 'slug', 'qf_id'])->find((int) $id);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('info'))) {
            $this->merge(['info' => $this->sanitizeRichTextValue($this->input('info'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $kind = $this->kind();
        $id = $this->existing()?->id;

        $rules = [
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('quran_resources', 'slug')->where('kind', $kind->value)->ignore($id)],
            'name' => ['required', 'string', 'max:200'],
            'name_translations' => ['nullable', 'array'],
            'name_translations.*' => ['nullable', 'string', 'max:200'],
            'description_translations' => ['nullable', 'array'],
            'description_translations.*' => ['nullable', 'string', 'max:2000'],
            'author_name' => ['nullable', 'string', 'max:200'],
            'language_iso' => ['nullable', 'string', 'exists:quran_languages,iso_code'],
            'info' => ['nullable', 'string', 'max:65000'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['boolean'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sync_enabled' => ['boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ];

        if ($kind->isReciter()) {
            $rules += [
                'style' => ['nullable', Rule::enum(RecitationStyle::class)],
                'qirat' => ['nullable', 'string', 'max:40'],
                'external_source' => ['nullable', Rule::enum(ExternalSource::class)],
                'external_ref_id' => [
                    'nullable', 'required_with:external_source', 'string', 'max:50',
                    Rule::unique('quran_resources', 'external_ref_id')
                        ->where('kind', $kind->value)
                        ->where('external_source', (string) $this->input('external_source'))
                        ->ignore($id),
                ],
                'store_audio_locally' => ['boolean'],
            ];
        }

        return $rules;
    }
}
