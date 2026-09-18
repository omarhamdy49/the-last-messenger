<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quran;

use App\Domain\Quran\DTOs\AudioFileData;
use Illuminate\Validation\Validator;

/**
 * An ayah audio file (target `verse_key`) or a chapter audio file (target `chapter_id`),
 * given as a URL or an uploaded MP3. Which one is decided by the route name.
 */
final class AudioFileRequest extends QuranAdminRequest
{
    public function isChapter(): bool
    {
        return str_contains((string) $this->route()?->getName(), 'chapter-audio');
    }

    private function creating(): bool
    {
        return $this->route('file') === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('quran.audio.max_file_mb', 80) * 1024;
        $rules = [
            'url' => ['nullable', 'required_without:file', 'url:http,https', 'max:500'],
            'file' => ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3', 'max:'.$maxKb],
            'format' => ['nullable', 'string', 'max:8'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],
        ];

        if ($this->isChapter()) {
            return $rules + [
                'chapter_id' => $this->creating() ? ['required', 'integer', 'min:1', 'max:114'] : ['prohibited'],
                'file_size' => ['nullable', 'integer', 'min:0'],
            ];
        }

        return $rules + [
            'verse_key' => $this->creating() ? ['required', 'string', 'exists:quran_verses,verse_key'] : ['prohibited'],
            'segments' => ['nullable', 'json'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $v): void {
            $segments = $this->input('segments');
            if (! is_string($segments) || $segments === '' || $v->errors()->has('segments')) {
                return;
            }
            $decoded = json_decode($segments, true);
            $valid = is_array($decoded) && array_is_list($decoded) && array_reduce($decoded, static fn (bool $ok, mixed $s): bool => $ok
                && is_array($s) && count($s) === 3 && array_filter($s, 'is_int') === $s, true);
            if (! $valid) {
                $v->errors()->add('segments', __('quran.manage.segments_invalid'));
            }
        });
    }

    /**
     * @param  string|int  $target  verse key or chapter number (stored target on update)
     */
    public function toData(string|int $target, string $url, bool $isLocal): AudioFileData
    {
        $segments = $this->input('segments');

        return new AudioFileData(
            target: $target,
            url: $url,
            format: $this->filled('format') ? (string) $this->input('format') : 'mp3',
            durationMs: $this->filled('duration_ms') ? $this->integer('duration_ms') : null,
            fileSize: $this->filled('file_size') ? $this->integer('file_size') : ($this->file('file')?->getSize() ?: null),
            segments: is_string($segments) && $segments !== '' ? json_decode($segments, true) : null,
            isLocal: $isLocal,
        );
    }
}
