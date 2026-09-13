<?php

declare(strict_types=1);

namespace App\Domain\Quran\Infrastructure\Audio;

use App\Domain\Quran\Models\RecordingSource;

interface AudioProvider
{
    public function verseTemplate(RecordingSource $source): ?string;

    /** @return list<array<string, mixed>> */
    public function timings(RecordingSource $source, int $surah): array;
}
