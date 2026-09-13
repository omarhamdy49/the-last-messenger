<?php

declare(strict_types=1);

namespace App\Http\Requests\Site;

use App\Domain\Quran\DTOs\PlaylistData;
use App\Domain\Quran\Services\PlaylistService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the save payload for a playlist (create and update).
 *
 * Every (surah, recording) pair is re-checked against playable audio so a
 * stale preview can never store a track that will not play.
 */
final class StorePlaylistRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ownership is enforced in the repository lookups, not here.
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['nullable', 'string', 'max:300'],
            'scope' => ['required', Rule::in(['full', 'custom'])],
            'items' => ['required', 'array', 'min:1', 'max:114'],
            'items.*' => ['nullable', 'integer'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $items = (array) $this->input('items', []);
            foreach (array_keys($items) as $surah) {
                if (! is_numeric($surah) || (int) $surah < 1 || (int) $surah > 114) {
                    $v->errors()->add('items', __('quran.playlists.invalid_surah'));

                    return;
                }
            }
            foreach (app(PlaylistService::class)->invalidPairs($items) as $bad) {
                $v->errors()->add('items.'.$bad['surah'], __('quran.playlists.no_audio_for_pair'));
            }
        });
    }

    public function toData(): PlaylistData
    {
        $items = [];
        foreach ($this->validated('items') as $surah => $recording) {
            $items[(int) $surah] = $recording !== null && $recording !== '' ? (int) $recording : null;
        }

        return new PlaylistData(
            trim($this->validated('name')),
            $this->validated('description') ?: null,
            $this->validated('scope'),
            $items,
            (bool) $this->validated('is_public', false),
        );
    }
}
