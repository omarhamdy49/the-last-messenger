<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services\Audio;

use App\Domain\Quran\Infrastructure\Audio\AudioProvider;
use App\Domain\Quran\Infrastructure\Audio\DirectAudioProvider;
use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\RecordingSource;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

    /**
     * Create the surah files a whole-surah source is missing, following the host's
     * naming ({base_url}1.mp3 or {base_url}001.mp3). Each URL is probed first with a
     * one-byte ranged GET, so a gap on the host never becomes a dead public link.
     *
     * @return array{created: int, unavailable: list<int>}
     */
    public function fillMissingSurahs(RecordingSource $source, bool $padded, string $format = 'mp3'): array
    {
        if ($source->kind !== 'surah' || blank($source->base_url)) {
            throw new InvalidArgumentException('Only a whole-surah source with a base URL can be filled.');
        }
        $have = $source->recitations()->where('format', $format)->pluck('surah_id')->all();
        $urls = collect(range(1, 114))->diff($have)
            ->mapWithKeys(fn (int $n) => [$n => rtrim($source->base_url, '/').'/'.($padded ? sprintf('%03d', $n) : $n).'.'.$format]);

        $sizes = [];
        foreach ($urls->chunk(12, true) as $chunk) { // ponytail: 12 at a time is polite to CDNs; lower it if a host rate-limits
            $responses = Http::pool(fn (Pool $pool) => $chunk->map(fn (string $url, int $n) => $pool->as((string) $n)
                ->withHeaders(['Range' => 'bytes=0-0'])->timeout(15)->get($url))->all());
            foreach ($responses as $n => $response) {
                if ($response instanceof Response && $response->successful()) {
                    $total = (int) Str::afterLast((string) $response->header('Content-Range'), '/');
                    $sizes[(int) $n] = $total > 0 ? intdiv($total, 1024) : null;
                }
            }
        }

        DB::transaction(function () use ($source, $urls, $sizes, $format): void {
            foreach ($sizes as $n => $kb) {
                Recitation::create(['reciter_id' => $source->recording_id, 'source_id' => $source->id, 'surah_id' => $n, 'audio_url' => $urls[$n], 'format' => $format, 'file_size_kb' => $kb]);
            }
        });

        return ['created' => count($sizes), 'unavailable' => $urls->keys()->diff(array_keys($sizes))->values()->all()];
    }
}
