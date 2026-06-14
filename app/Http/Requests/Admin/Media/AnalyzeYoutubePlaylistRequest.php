<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the playlist URL submitted to the read-only "analyze" preview.
 */
class AnalyzeYoutubePlaylistRequest extends FormRequest
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
                'regex:#^https?://(www\.)?(youtube\.com/.*[?&]list=|youtube\.com/playlist\?list=)[A-Za-z0-9_-]+#i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'playlist_url.regex' => __('admin.yt_playlist_url_invalid'),
        ];
    }
}
