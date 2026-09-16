<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleSelectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->locales->firstWhere('locale', 'ar')?->title
                ?? $this->locales->whereIn('locale', enabled_content_locales())->first()?->title
                ?? ('Article #'.$this->id),
        ];
    }
}
