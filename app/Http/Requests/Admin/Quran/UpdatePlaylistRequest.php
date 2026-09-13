<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Admin middleware guards the route group.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'is_public' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'items' => ['sometimes', 'array', 'max:114'],
            'items.*' => ['nullable', 'integer', Rule::exists('quran_reciters', 'id')],
        ];
    }

    /**
     * surahId => recordingId|null, only for surahs actually sent.
     *
     * @return array<int, int|null>
     */
    public function items(): array
    {
        $items = [];
        foreach ($this->validated('items', []) as $surah => $recording) {
            if (is_numeric($surah) && (int) $surah >= 1 && (int) $surah <= 114) {
                $items[(int) $surah] = $recording !== null && $recording !== '' ? (int) $recording : null;
            }
        }

        return $items;
    }
}
