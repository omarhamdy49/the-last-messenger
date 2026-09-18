<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Domain\Quran\Enums\ExternalSource;
use App\Domain\Quran\Enums\RecitationStyle;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Shared\Services\SlugGenerator;
use App\Http\Requests\Admin\Content\Concerns\SanitizesRichText;
use Illuminate\Support\Str;
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

        $this->merge(['slug' => $this->resolvedSlug()]);
    }

    /**
     * A typed slug always goes through Str::slug(); a blank one is generated from the English
     * name, then the base name, then the Arabic name, and made unique within the kind.
     *
     * A stored slug that was not touched is kept verbatim: Str::slug() turns `_` into `-`, so
     * normalising on every save would silently rewrite imported slugs (`chapter_reciter-1`),
     * which are public URLs.
     */
    private function resolvedSlug(): string
    {
        $typed = trim((string) $this->input('slug'));
        $existing = $this->existing();

        if ($typed !== '') {
            return $typed === $existing?->slug ? $typed : Str::limit(Str::slug($typed), 120, '');
        }

        $names = array_filter([
            $this->input('name_translations.en'),
            $this->input('name'),
            $this->input('name_translations.ar'),
        ], static fn (mixed $v): bool => is_string($v) && Str::slug($v) !== '');
        $source = reset($names);

        if ($source === false) {
            return ''; // Nothing sluggable: the `required` rule reports it on the slug field.
        }

        $kind = $this->kind()->value;

        return app(SlugGenerator::class)->unique(
            Str::limit(Str::slug($source), 110, ''),
            static fn (string $candidate): bool => QuranResource::query()
                ->where('kind', $kind)->where('slug', $candidate)
                ->when($existing !== null, static fn ($q) => $q->whereKeyNot($existing->id))
                ->exists(),
        );
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
            'image_id' => ['nullable', 'integer', 'exists:media,id'],
            // External CDN image, kept as a URL in the same column (255 chars).
            'image_url' => ['nullable', 'url:http,https', 'max:255'],
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
