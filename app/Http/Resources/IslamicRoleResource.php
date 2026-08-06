<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\People\Models\IslamicRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IslamicRole
 */
class IslamicRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name(app()->getLocale()),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
        ];
    }
}
