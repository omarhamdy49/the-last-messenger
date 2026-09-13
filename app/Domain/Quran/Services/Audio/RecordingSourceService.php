<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services\Audio;

use App\Domain\Quran\Infrastructure\Audio\AudioProvider;
use App\Domain\Quran\Infrastructure\Audio\DirectAudioProvider;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\RecordingSource;

final class RecordingSourceService
{
    public function adapter(RecordingSource $source): AudioProvider
    {
        return app(config('quran-audio.providers.'.$source->provider, DirectAudioProvider::class));
    }

    public function verseTemplate(Reciter $recording): ?string
    {
        $source = $recording->sources->where('kind', 'ayah')->where('is_active', true)->sortBy([['priority', 'asc'], ['id', 'asc']])->first();

        return $source ? $this->adapter($source)->verseTemplate($source) : null;
    }

    public function verseSource(Reciter $recording, string $folder): RecordingSource
    {
        return RecordingSource::updateOrCreate([
            'recording_id' => $recording->id, 'provider' => 'everyayah', 'kind' => 'ayah', 'source_key' => 'catalogue',
        ], ['base_url' => config('quran-audio.verse_base_url'), 'metadata' => ['folder' => $folder]]);
    }

    /** Keep the internal source stable when a provider moves its CDN folder. */
    public function catalogueSource(Reciter $recording, array $moshaf, ?int $readId): RecordingSource
    {
        $source = $recording->sources()->where('provider', 'mp3quran')->where('kind', 'surah')
            ->where(function ($query) use ($moshaf): void {
                $query->where('external_recording_id', (string) $moshaf['id'])->orWhere('base_url', $moshaf['server']);
            })->first();
        $source ??= new RecordingSource(['recording_id' => $recording->id, 'provider' => 'mp3quran', 'kind' => 'surah', 'source_key' => 'recording:'.$moshaf['id']]);
        $source->fill([
            'external_reciter_id' => (string) $moshaf['reciter_id'], 'external_recording_id' => (string) $moshaf['id'],
            'external_timing_id' => $readId === null ? null : (string) $readId, 'base_url' => $moshaf['server'],
            'metadata' => ['person_name_ar' => $moshaf['reciter']],
        ])->save();
        $recording->unsetRelation('sources');

        return $source;
    }

    /** Preserve direct assets and mixed libraries without applying another file's timings. */
    public function sourceForUrl(int $recordingId, string $url): RecordingSource
    {
        $source = RecordingSource::where('recording_id', $recordingId)->where('kind', 'surah')->get()
            ->filter(fn ($source) => $source->base_url && str_starts_with($url, rtrim($source->base_url, '/').'/'))
            ->sortByDesc(fn ($source) => strlen($source->base_url))->first();
        if ($source) {
            return $source;
        }
        $base = substr($url, 0, (int) strrpos($url, '/') + 1);

        return RecordingSource::firstOrCreate([
            'recording_id' => $recordingId, 'provider' => 'direct', 'kind' => 'surah', 'source_key' => hash('sha256', $base),
        ], ['base_url' => $base]);
    }
}
