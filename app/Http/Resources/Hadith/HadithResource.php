<?php

declare(strict_types=1);

namespace App\Http\Resources\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Services\ReferenceBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The hadith shape from contracts/hadith-api.md §8.
 *
 * @mixin Hadith
 */
class HadithResource extends JsonResource
{
    use ResolvesLocale;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $locale = $this->locale();
        $reference = app(ReferenceBuilder::class)->build($this->resource);

        return [
            'collection' => $this->whenLoaded('collection', fn (): ?string => $this->collection->code),
            'book_number' => $this->whenLoaded('book', fn (): ?string => $this->book?->book_number),
            'chapter_number' => $this->whenLoaded('chapter', fn (): ?string => $this->chapter?->chapter_number),
            'hadith_number' => (string) $this->hadith_number,
            'in_book_number' => $this->in_book_number,
            'display_grade' => $this->display_grade->value,
            'reference' => $reference->toArray(),

            // Only locales that HAVE text: an Arabic-only hadith has one entry,
            // never an entry carrying an empty body (FR-015).
            'texts' => $this->whenLoaded('texts', fn (): array => $this->texts
                ->map(static fn ($text): array => array_filter([
                    'locale' => $text->locale,
                    'body' => $text->body,
                    'narrated_by' => $text->narrated_by,
                    'sanad' => $text->sanad,
                ], static fn ($value): bool => $value !== null))
                ->values()->all()),

            // One entry per CLAIM. A single source string can produce two.
            'gradings' => $this->whenLoaded('gradings', fn (): array => $this->gradings
                ->map(static fn ($grading): array => [
                    'grade' => $grading->grade->value,
                    'scope' => $grading->scope->value,
                    'part_label' => $grading->part_label,
                    'raw_grade' => $grading->raw_grade,
                    'scholar' => $grading->scholar?->getTranslation('name', $locale, true) ?: null,
                ])
                ->values()->all()),

            // Distinguishes an explicit independent verdict from a source that
            // prints no per-hadith grading. Collection classification remains
            // provenance only and never changes display_grade.
            'grading_context' => $this->whenLoaded('gradings', function (): array {
                $source = is_array($this->source_metadata)
                    ? ($this->source_metadata['grading_context'] ?? [])
                    : [];
                $source = is_array($source) ? $source : [];

                return [
                    'per_hadith_status' => $this->gradings->isNotEmpty()
                        ? 'explicit'
                        : ($source['per_hadith_status'] ?? 'not_recorded'),
                    'collection_classification' => $source['collection_classification'] ?? null,
                    'source_note' => $source['source_note'] ?? null,
                ];
            }),

            // Present only on search results, per §10.
            'score' => $this->when(isset($this->score), fn (): float => round((float) $this->score, 4)),
        ];
    }
}
