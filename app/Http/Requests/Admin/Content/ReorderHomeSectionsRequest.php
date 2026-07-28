<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Domain\Content\DTOs\ReorderHomeSectionsData;

final class ReorderHomeSectionsRequest extends ContentFormRequest
{
    public function rules(): array
    {
        return [
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['required', 'integer', 'distinct', 'exists:home_sections,id'],
            'sections.*.display_order' => ['required', 'integer', 'min:1', 'max:10000', 'distinct'],
        ];
    }

    public function toData(): ReorderHomeSectionsData
    {
        $orders = [];

        foreach ($this->validated('sections') as $section) {
            $orders[(int) $section['id']] = (int) $section['display_order'];
        }

        return new ReorderHomeSectionsData($orders);
    }
}
