<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

final class UpdateWordGlossesRequest extends QuranAdminRequest
{
    /**
     * `glosses[<resource id>] = text`; an empty text removes that gloss.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'glosses' => ['required', 'array', 'max:50'],
            'glosses.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
