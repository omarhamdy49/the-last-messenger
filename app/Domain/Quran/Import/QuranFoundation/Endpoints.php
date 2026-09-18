<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Enums\SyncResourceGroup;
use InvalidArgumentException;

/**
 * Typed wrappers for every QF Content API endpoint the module uses. Paths are
 * exactly QF's (api-docs.quran.foundation, Content APIs v4). Each method returns the
 * decoded body; mapping to rows happens in Mappers/.
 */
final class Endpoints
{
    public function __construct(private readonly Client $client) {}

    /** @return array<string, mixed> */
    public function chapters(string $language = 'en'): array
    {
        return $this->client->get('chapters', ['language' => $language]);
    }

    /** @return array<string, mixed> */
    public function chapter(int $id, string $language = 'en'): array
    {
        return $this->client->get("chapters/{$id}", ['language' => $language]);
    }

    /** @return array<string, mixed> */
    public function chapterInfo(int $id, string $language, ?string $resourceId = null, bool $includeResources = false): array
    {
        return $this->client->get("chapters/{$id}/info", [
            'language' => $language,
            'resource_id' => $resourceId,
            'include_resources' => $includeResources ? 'true' : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function sections(SectionType $type): array
    {
        return $this->client->get($type->qfPath());
    }

    /** @return array<string, mixed> */
    public function pages(int $mushaf): array
    {
        return $this->client->get('pages', ['mushaf' => $mushaf]);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function pagesLookup(array $query): array
    {
        return $this->client->get('pages/lookup', $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function versesByChapter(int $chapter, array $query = []): array
    {
        return $this->client->get("verses/by_chapter/{$chapter}", $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function versesByKey(string $key, array $query = []): array
    {
        return $this->client->get("verses/by_key/{$key}", $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function random(array $query = []): array
    {
        return $this->client->get('verses/random', $query);
    }

    /**
     * Whole Quran (or one axis) in one script.
     *
     * @param  array<string, scalar|null>  $filters
     * @return array<string, mixed>
     */
    public function scriptVerses(Script $script, array $filters = []): array
    {
        return $this->client->get('quran/verses/'.$script->qfPath(), $filters);
    }

    /** @return array<string, mixed> */
    public function languages(?string $language = null): array
    {
        return $this->client->get('resources/languages', ['language' => $language]);
    }

    /** @return array<string, mixed> */
    public function catalogue(ResourceKind $kind, ?string $language = null): array
    {
        $path = $kind->qfCataloguePath()
            ?? throw new InvalidArgumentException("Resource kind {$kind->value} has no QF catalogue.");

        return $this->client->get($path, ['language' => $language]);
    }

    /** @return array<string, mixed> */
    public function resourceInfo(ResourceKind $kind, int $id): array
    {
        if (! $kind->hasQfInfo()) {
            throw new InvalidArgumentException("Resource kind {$kind->value} has no QF info endpoint.");
        }

        return $this->client->get($kind->qfCataloguePath()."/{$id}/info");
    }

    /** @return array<string, mixed> */
    public function recitationStyles(): array
    {
        return $this->client->get('resources/recitation_styles');
    }

    /**
     * Ayah-by-ayah audio files of one recitation, filtered by an axis or verse_key.
     *
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function recitationFiles(int $recitationId, array $query = []): array
    {
        return $this->client->get("quran/recitations/{$recitationId}", $query);
    }

    /** @return array<string, mixed> */
    public function chapterRecitations(int $reciterId): array
    {
        return $this->client->get("chapter_recitations/{$reciterId}");
    }

    /** @return array<string, mixed> */
    public function chapterRecitation(int $reciterId, int $chapter, bool $segments = false): array
    {
        return $this->client->get("chapter_recitations/{$reciterId}/{$chapter}", [
            'segments' => $segments ? 'true' : null,
        ]);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function audioTimestamp(int $reciterId, array $query): array
    {
        return $this->client->get("audio/reciters/{$reciterId}/timestamp", $query);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function audioLookup(int $reciterId, array $query): array
    {
        return $this->client->get("audio/reciters/{$reciterId}/lookup", $query);
    }

    /**
     * @param  array<string, scalar|null>  $query  resources, bootstrap, sync_token, per_page
     * @return array<string, mixed>
     */
    public function sync(array $query): array
    {
        return $this->client->get('resources/sync', $query);
    }

    /**
     * Follow a `next_page_url` or `snapshot_url` returned by sync.
     *
     * @return array<string, mixed>
     */
    public function followRelative(string $relativeApiPath): array
    {
        return $this->client->getUrl($this->client->absoluteUrl($relativeApiPath), 'resources/sync:follow');
    }

    /** @return array<string, mixed> */
    public function snapshot(SyncResourceGroup $group, int $id): array
    {
        return $this->client->get("resources/snapshots/{$group->value}/{$id}");
    }

    /** @return array<string, mixed> */
    public function hadithReferences(string $verseKey, string $language = 'en'): array
    {
        return $this->client->get("hadiths/{$verseKey}/references", ['language' => $language]);
    }

    /**
     * Quran Reflect lessons/reflections feed (gateway outside /content/api/v4).
     *
     * @param  array<string, scalar|null>  $query
     * @return array<string, mixed>
     */
    public function reflectFeed(array $query): array
    {
        $url = rtrim((string) config('quran.qf.reflect_url'), '/').'/posts/feed';

        return $this->client->getUrl($url, 'quran-reflect/posts/feed', $query);
    }

    /** @return array<string, mixed> */
    public function footNote(int $id): array
    {
        return $this->client->get("foot_notes/{$id}");
    }
}
