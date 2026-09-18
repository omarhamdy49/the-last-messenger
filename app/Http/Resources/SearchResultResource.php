<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Media\Models\MediaProgram;
use App\Domain\People\Models\Muslim;
use App\Domain\Quran\Models\Verse;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = match (true) {
            $this->resource instanceof Article => new ArticleResource($this->resource),
            $this->resource instanceof MediaProgram => new MediaProgramResource($this->resource),
            $this->resource instanceof SeerahEvent => new SeerahEventResource($this->resource),
            $this->resource instanceof Muslim => new MuslimResource($this->resource),
            default => null,
        };

        // Verses have no resource class: the Quran API shapes them in
        // QuranApiService, and search only needs the reference and its text.
        if ($this->resource instanceof Verse) {
            return [
                'id' => $this->resource->id,
                'type' => 'QuranVerse',
                'verse_key' => $this->resource->verse_key,
                'chapter_id' => $this->resource->chapter_id,
                'verse_number' => $this->resource->verse_number,
                'text' => $this->resource->text_uthmani,
            ];
        }

        // A new search module cannot accidentally serialize every model
        // attribute. It gets a minimal identity until a reviewed resource is
        // explicitly added above.
        return $resource?->resolve($request) ?? [
            'id' => $this->resource->getKey(),
            'type' => class_basename($this->resource),
        ];
    }
}
