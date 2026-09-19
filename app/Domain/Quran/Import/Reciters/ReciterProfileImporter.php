<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\Reciters;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\DTOs\ReciterData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Shared\Services\PublicImageService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Portraits and Arabic names for reciters.
 *
 * The Quran Foundation catalogue carries neither: `/resources/chapter_reciters` returns
 * an id, a name, a style and a qirat and nothing else, so every reciter page we build
 * from QF alone is an initial in a circle. The portraits come from quran.com's public
 * reciter list, which covers about half of the catalogue; the rest stay without a
 * picture until an admin uploads one, which the resource form already accepts.
 *
 * Those images belong to quran.com, so `--download` (taking one copy) is the safer
 * default position than pointing every page view at someone else's CDN, and either way
 * an admin upload always wins: a resource whose image was set by hand is left alone
 * unless the import is forced.
 */
final class ReciterProfileImporter
{
    /** Latin vowels and Arabic diacritics carry no identity in a transliterated name. */
    private const VOWELS = ['a', 'e', 'i', 'o', 'u', 'y'];

    public function __construct(private readonly ResourceRepositoryInterface $resources) {}

    public function import(bool $download, bool $force): ImportReport
    {
        $report = new ImportReport('reciter-profiles');

        $profiles = $this->fetchProfiles($report);
        if ($profiles === []) {
            return $report;
        }

        $reciters = $this->resources->active(ResourceKind::CHAPTER_RECITER)
            ->concat($this->resources->active(ResourceKind::RECITATION));
        $report->expected = $reciters->count();
        $unmatched = [];

        foreach ($reciters as $reciter) {
            $profile = $this->match($reciter, $profiles);

            // Not a failure: the source simply has no portrait for this reciter, and
            // the step's job is to fill what it can and say what it could not.
            if ($profile === null) {
                $unmatched[] = $reciter->slug;
                $report->add(['unchanged' => 1]);

                continue;
            }

            // An uploaded image is editorial: never replaced by an import unless forced.
            $keepImage = $reciter->image !== null && $reciter->image !== '' && ! $force;
            $image = $keepImage ? null : $this->image($profile['picture'], $reciter, $download, $report);
            $names = $this->missingNames($reciter, $profile);

            if ($image === null && $names === []) {
                $report->add(['unchanged' => 1]);

                continue;
            }

            $this->resources->applyFetchedData($reciter->id, new ReciterData(
                name: $reciter->name,
                names: $names,
                style: $reciter->style,
                qirat: $reciter->qirat,
                image: $image,
            ));
            $report->add(['updated' => 1]);
            $report->note($reciter->slug.': '.implode(', ', array_filter([
                $image === null ? null : 'image',
                $names === [] ? null : 'names '.implode('/', array_keys($names)),
            ])));
        }

        QuranCacheKeys::flushResources();
        QuranCacheKeys::flushReciters();

        if ($unmatched !== []) {
            $report->note(count($unmatched).' without a portrait in the source (upload one in admin): '.implode(', ', $unmatched));
        }

        return $report;
    }

    /**
     * @return array<string, array{picture: ?string, nameAr: ?string, nameEn: string}> skeleton => profile
     */
    private function fetchProfiles(ImportReport $report): array
    {
        $url = (string) config('quran.profiles.reciters_url');

        try {
            $response = Http::timeout(30)->acceptJson()->get($url, ['locale' => 'ar', 'fields' => 'profile_picture']);
        } catch (ConnectionException $e) {
            $report->fail('source', $e->getMessage());

            return [];
        }

        if (! $response->successful()) {
            $report->fail('source', $url.' returned HTTP '.$response->status());

            return [];
        }

        $profiles = [];
        foreach ((array) $response->json('reciters', []) as $row) {
            $nameEn = (string) ($row['name'] ?? '');
            $nameAr = $row['translated_name']['name'] ?? null;
            $picture = (string) ($row['profile_picture'] ?? '');

            foreach (array_filter([$nameEn, is_string($nameAr) ? $nameAr : null]) as $name) {
                $key = self::skeleton($name);
                // First row wins: later rows for the same person repeat the picture.
                if ($key !== '' && ! isset($profiles[$key])) {
                    $profiles[$key] = [
                        'picture' => $picture === '' ? null : $picture,
                        'nameAr' => is_string($nameAr) ? $nameAr : null,
                        'nameEn' => $nameEn,
                    ];
                }
            }
        }

        $report->note(count($profiles).' name keys from '.$url);

        return $profiles;
    }

    /**
     * @param  array<string, array{picture: ?string, nameAr: ?string, nameEn: string}>  $profiles
     * @return array{picture: ?string, nameAr: ?string, nameEn: string}|null
     */
    private function match(QuranResource $reciter, array $profiles): ?array
    {
        $names = array_filter([
            $reciter->getTranslation('name', 'ar', false),
            $reciter->getTranslation('name', 'en', false),
            $reciter->name,
        ]);

        foreach ($names as $name) {
            $key = self::skeleton((string) $name);
            if ($key !== '' && isset($profiles[$key])) {
                return $profiles[$key];
            }
        }

        // A catalogue name can carry a trailing qualifier the source never had —
        // "Mishari Rashid al-`Afasy Streaming". Fall back to the longest profile the
        // name starts with, long enough that the prefix is the whole name and not a
        // shared first word.
        foreach ($names as $name) {
            $key = self::skeleton((string) $name);
            foreach ($profiles as $candidate => $profile) {
                if (mb_strlen($candidate) >= 8 && str_starts_with($key, $candidate)) {
                    return $profile;
                }
            }
        }

        return null;
    }

    /**
     * Translations the resource is missing and the source can fill. An existing
     * translation is editorial and stays.
     *
     * @param  array{picture: ?string, nameAr: ?string, nameEn: string}  $profile
     * @return array<string, string>
     */
    private function missingNames(QuranResource $reciter, array $profile): array
    {
        $names = [];
        if ($profile['nameAr'] !== null && ($reciter->getTranslation('name', 'ar', false) ?? '') === '') {
            $names['ar'] = $profile['nameAr'];
        }
        if (($reciter->getTranslation('name', 'en', false) ?? '') === '') {
            $names['en'] = $profile['nameEn'];
        }

        return $names;
    }

    /**
     * Delete portrait files on the public disk that no resource references any more
     * (rows deleted before the model forgot its file, or replaced by hand).
     */
    public function pruneImages(): ImportReport
    {
        $report = new ImportReport('reciter-images-prune');
        $disk = Storage::disk('public');
        $referenced = QuranResource::query()->whereNotNull('image')->where('image', 'not like', 'http%')->pluck('image')->all();
        $keep = array_flip($referenced);
        foreach ($referenced as $path) {
            foreach (array_keys(PublicImageService::SIZES) as $size) {
                $keep[PublicImageService::derivedPath($path, $size)] = true;
            }
        }

        foreach ([config('quran.profiles.path'), 'quran/resources'] as $directory) {
            foreach ($disk->files(trim((string) $directory, '/')) as $file) {
                $report->expected++;
                if (isset($keep[$file])) {
                    $report->unchanged++;

                    continue;
                }
                $disk->delete($file);
                $report->deleted++;
                $report->note("removed {$file}");
            }
        }

        return $report;
    }

    /**
     * The stored image: a copy on the public disk, or the remote URL when the caller
     * asked not to download. Null when the source has no picture for this reciter.
     */
    private function image(?string $picture, QuranResource $reciter, bool $download, ImportReport $report): ?string
    {
        if ($picture === null) {
            return null;
        }

        $url = rtrim((string) config('quran.profiles.cdn_url'), '/').'/'.ltrim($picture, '/');
        if (! $download) {
            return $url;
        }

        $extension = pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'jpg';
        $path = trim((string) config('quran.profiles.path'), '/').'/'.$reciter->id.'.'.$extension;

        try {
            $body = Http::timeout(30)->get($url);
            if (! $body->successful()) {
                $report->fail($reciter->slug, 'image download returned HTTP '.$body->status());

                return null;
            }

            $temporary = tempnam(sys_get_temp_dir(), 'reciter');
            file_put_contents($temporary, $body->body());
            Storage::disk('public')->putFileAs(dirname($path), new File($temporary), basename($path));
            @unlink($temporary);
        } catch (Throwable $e) {
            $report->fail($reciter->slug, 'image download failed: '.$e->getMessage());

            return null;
        }

        return $path;
    }

    /**
     * The consonant skeleton of a name, which is what survives transliteration:
     * "AbdulBaset AbdulSamad" and "Abdul Basit Abdul Samad" both reduce to
     * `bdlbstbdlsmd`. Arabic keeps its letters and loses its diacritics; a trailing
     * qualifier such as "(with kids)" is dropped so it matches the plain name.
     */
    private static function skeleton(string $name): string
    {
        $name = (string) preg_replace('/[(\[].*$/u', '', $name);
        $name = (string) preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}]/u', '', $name);
        $name = mb_strtolower($name);

        if (preg_match('/\p{Arabic}/u', $name) === 1) {
            return (string) preg_replace('/[^\p{Arabic}]/u', '', $name);
        }

        return str_replace(self::VOWELS, '', (string) preg_replace('/[^a-z]/', '', $name));
    }
}
