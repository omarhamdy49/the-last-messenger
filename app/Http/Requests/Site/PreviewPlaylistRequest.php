<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a playlist preview request: which people, which surahs, and any
 * per-surah recording the user pinned in a previous preview.
 */
final class PreviewPlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'profile_ids' => ['required', 'array', 'min:1', 'max:30'],
            // A "profile" is an active chapter-reciter resource (006).
            'profile_ids.*' => ['integer', Rule::exists('quran_resources', 'id')->where('kind', 'chapter_reciter')->where('is_active', true)],
            'scope' => ['required', Rule::in(['full', 'custom'])],
            'surah_ids' => ['required_if:scope,custom', 'array', 'max:114'],
            'surah_ids.*' => ['integer', 'between:1,114'],
            'keep' => ['sometimes', 'array'],
            'keep.*' => ['integer'],
        ];
    }

    /**
     * @return list<int>
     */
    public function profileIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->validated('profile_ids'))));
    }

    /**
     * @return list<int>
     */
    public function surahIds(): array
    {
        return $this->validated('scope') === 'full'
            ? range(1, 114)
            : array_values(array_unique(array_map('intval', $this->validated('surah_ids', []))));
    }

    /**
     * @return array<int, int>
     */
    public function keep(): array
    {
        $keep = [];
        foreach ($this->validated('keep', []) as $surah => $recording) {
            if (is_numeric($surah)) {
                $keep[(int) $surah] = (int) $recording;
            }
        }

        return $keep;
    }
}
