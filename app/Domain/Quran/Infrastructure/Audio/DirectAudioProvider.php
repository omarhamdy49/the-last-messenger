<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Audio;

use App\Domain\Quran\Models\RecordingSource;

class DirectAudioProvider implements AudioProvider
{
    public function verseTemplate(RecordingSource $source): ?string
    {
        return $source->kind === 'ayah' ? ($source->metadata['verse_template'] ?? null) : null;
    }

    public function timings(RecordingSource $source, int $surah): array
    {
        return [];
    }
}
