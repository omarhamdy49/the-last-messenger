<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Media;

use App\Domain\Media\Support\YouTubeUrl;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the YouTube source submitted to the import endpoints — the
 * read-only "analyze" preview and the confirmed sync. A source is either a
 * playlist URL or a single video URL.
 *
 * The field stays named `playlist_url` because that is the column the run is
 * recorded against (youtube_sync_runs.playlist_url).
 */
class YouTubeSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'playlist_url' => [
                'required', 'string', 'max:1000',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! YouTubeUrl::isSupportedSource(is_string($value) ? $value : null)) {
                        $fail(__('admin.yt_source_url_invalid'));
                    }
                },
            ],
            // The mapping confirmed in the modal. Absent on the analyze call
            // and optional on sync — no mapping means "import everything".
            'items' => ['sometimes', 'array', 'max:500'],
            'items.*.video_id' => ['required', 'string', 'max:32'],
            'items.*.episode_number' => ['required', 'integer', 'min:1', 'max:10000'],
            'items.*.title' => ['nullable', 'string', 'max:500'],
        ];
    }
}
