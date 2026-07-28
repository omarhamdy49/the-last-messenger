<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Domain\Content\DTOs\UpdateHomeSectionData;

final class UpdateHomeSectionRequest extends ContentFormRequest
{
    public function rules(): array
    {
        return [
            ...$this->localizedRules('heading', false, ['string', 'max:255']),
            ...$this->localizedRules('subheading', false, ['string', 'max:2000']),
        ];
    }

    public function toData(int $id): UpdateHomeSectionData
    {
        return new UpdateHomeSectionData(
            id: $id,
            headings: $this->validated('heading', []),
            subheadings: $this->validated('subheading', []),
        );
    }
}
