<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Audio;

use App\Domain\Quran\Models\RecordingSource;

final class EveryAyahProvider extends DirectAudioProvider
{
    public function verseTemplate(RecordingSource $source): ?string
    {
        $folder = $source->metadata['folder'] ?? null;

        return $folder ? rtrim($source->base_url ?? config('quran-audio.verse_base_url'), '/').'/'.trim($folder, '/').'/{surah}{ayah}.mp3' : null;
    }
}
