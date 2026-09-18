<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Quran;

use App\Domain\Quran\Enums\Script;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Services\AudioService;
use App\Domain\Quran\Services\QuranApiService;
use App\Http\Requests\Api\Quran\QuranApiRequest;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * `/api/v1/quran/*` in Quran Foundation Content API v4 shapes, served from local data.
 * Every action is a thin call into QuranApiService; `null` from the service is a 404.
 */
final class QuranApiController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly QuranApiService $api) {}

    public function chapters(QuranApiRequest $request): JsonResponse
    {
        return $this->ok($this->api->chapters($request->params()->language));
    }

    public function chapter(QuranApiRequest $request, int $id): JsonResponse
    {
        return $this->ok($this->api->chapter($id, $request->params()->language));
    }

    public function chapterInfo(QuranApiRequest $request, int $id): JsonResponse
    {
        return $this->ok($this->api->chapterInfo($id, $request->params()->language,
            $request->filled('resource_id') ? $request->integer('resource_id') : null, $request->boolean('include_resources')));
    }

    public function versesByChapter(QuranApiRequest $request, int $chapter): JsonResponse
    {
        return $this->ok($chapter >= 1 && $chapter <= 114 ? $this->api->verses('chapter', $chapter, $request->params()) : null);
    }

    public function versesByPage(QuranApiRequest $request, int $page): JsonResponse
    {
        return $this->ok($this->api->verses('page', $page, $request->params()));
    }

    public function versesBySection(QuranApiRequest $request, int $number, string $axis): JsonResponse
    {
        $type = SectionType::from($axis);

        return $this->ok($number >= 1 && $number <= $type->max() ? $this->api->verses($type, $number, $request->params()) : null);
    }

    public function versesByRange(QuranApiRequest $request): JsonResponse
    {
        if (! $request->filled('from') || ! $request->filled('to')) {
            return $this->validationErrorResponse(['from' => ['from and to are required.']]);
        }
        $range = $this->api->rangeIds((string) $request->query('from'), (string) $request->query('to'));

        return $this->ok($range === null ? null : $this->api->verses('range', $range, $request->params()));
    }

    public function verseByKey(QuranApiRequest $request, string $key): JsonResponse
    {
        return $this->ok($this->api->verseByKey($key, $request->params()));
    }

    public function randomVerse(QuranApiRequest $request): JsonResponse
    {
        return $this->ok($this->api->randomVerse($request->axisFilter(), $request->params()));
    }

    public function sections(string $axis): JsonResponse
    {
        return $this->ok($this->api->sections(SectionType::from($axis)));
    }

    public function section(int $number, string $axis): JsonResponse
    {
        return $this->ok($this->api->section(SectionType::from($axis), $number));
    }

    public function pages(QuranApiRequest $request): JsonResponse
    {
        return $this->ok($this->api->pages($this->mushaf($request)));
    }

    public function page(QuranApiRequest $request, int $number): JsonResponse
    {
        return $this->ok($this->api->page($this->mushaf($request), $number));
    }

    public function pagesLookup(QuranApiRequest $request): JsonResponse
    {
        $range = $request->filled('from') && $request->filled('to')
            ? $this->api->rangeIds((string) $request->query('from'), (string) $request->query('to'))
            : ($request->axisFilter() === [] ? null : $this->api->axisRange($request->axisFilter()));

        if ($range === null && $request->axisFilter() === [] && ! $request->filled('from')) {
            return $this->validationErrorResponse(['from' => ['Send one axis filter or from/to.']]);
        }

        return $this->ok($range === null ? null : $this->api->pagesLookup($this->mushaf($request), $range));
    }

    public function scriptVerses(QuranApiRequest $request, string $script): JsonResponse
    {
        $resolved = Script::fromExternal($script);
        if ($resolved === null) {
            return $this->validationErrorResponse(['script' => ['Unknown script '.$script.'.']]);
        }

        return $this->ok($this->api->scriptVerses($resolved, $request->axisFilter()));
    }

    public function translationFull(QuranApiRequest $request, int $resource): JsonResponse
    {
        return $this->ok($this->api->translationFull($resource, $request->axisFilter(), $request->boolean('foot_notes')));
    }

    public function tafsirFull(QuranApiRequest $request, int $resource): JsonResponse
    {
        return $this->ok($this->api->tafsirFull($resource, $request->axisFilter(), $request->integer('page', 1), $request->integer('per_page', 10)));
    }

    public function recitationFiles(QuranApiRequest $request, int $resource): JsonResponse
    {
        return $this->ok($this->api->recitationFiles($resource, $request->axisFilter()));
    }

    public function resources(QuranApiRequest $request, string $kind): JsonResponse
    {
        return $this->ok($this->api->resourceIndex($kind, $request->params()->language));
    }

    public function resourceInfo(string $kind, int $id): JsonResponse
    {
        return $this->ok($this->api->resourceInfo($kind, $id));
    }

    public function chapterRecitations(int $resource): JsonResponse
    {
        return $this->ok($this->api->chapterRecitations($resource));
    }

    public function chapterRecitation(QuranApiRequest $request, int $resource, int $chapter): JsonResponse
    {
        return $this->ok($this->api->chapterRecitation($resource, $chapter, $request->boolean('segments')));
    }

    public function audioTimestamp(QuranApiRequest $request, int $resource, AudioService $audio): JsonResponse
    {
        [$chapter, $verseKey] = $this->audioTarget($request);
        if ($chapter === null) {
            return $this->validationErrorResponse(['chapter_number' => ['Send chapter_number or verse_key.']]);
        }

        return $this->ok($this->api->audioTimestamp($resource, $chapter, $verseKey,
            $request->filled('word') ? $request->integer('word') : null,
            $request->filled('word_from') ? $request->integer('word_from') : null,
            $request->filled('word_to') ? $request->integer('word_to') : null,
            $audio));
    }

    public function audioLookup(QuranApiRequest $request, int $resource, AudioService $audio): JsonResponse
    {
        [$chapter] = $this->audioTarget($request);
        if ($chapter === null || ! $request->filled('timestamp')) {
            return $this->validationErrorResponse(['timestamp' => ['Send chapter_number (or verse_key) and timestamp.']]);
        }

        return $this->ok($this->api->audioLookup($resource, $chapter, $request->integer('timestamp'), $request->boolean('segments'), $audio));
    }

    public function footNote(string $id): JsonResponse
    {
        return $this->ok($this->api->footNote($id));
    }

    public function search(QuranApiRequest $request): JsonResponse
    {
        $query = trim((string) $request->query('query', ''));
        if ($query === '') {
            return $this->validationErrorResponse(['query' => ['The query parameter is required.']]);
        }

        return $this->ok($this->api->search(
            mode: (string) $request->query('mode', 'quick'),
            query: $query,
            locale: $request->params()->language ?? app()->getLocale(),
            page: $request->integer('page', 1),
            size: $request->integer('size', 20),
            translationIds: array_map('intval', $request->csv('translation_ids')),
            exact: $request->boolean('exact_matches_only'),
            highlight: $request->boolean('highlight'),
            navigationLimit: $request->integer('navigationalResultsNumber', 5),
            verseLimit: $request->integer('versesResultsNumber', 5),
        ));
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    private function ok(?array $data): JsonResponse
    {
        return $data === null ? $this->notFoundResponse() : $this->successResponse($data, '');
    }

    private function mushaf(QuranApiRequest $request): int
    {
        return $request->filled('mushaf') ? $request->integer('mushaf') : (int) config('quran.mushaf.default_id', 1);
    }

    /**
     * @return array{0: ?int, 1: ?string} chapter number, verse key
     */
    private function audioTarget(QuranApiRequest $request): array
    {
        if ($request->filled('verse_key')) {
            $key = (string) $request->query('verse_key');

            return [(int) explode(':', $key)[0], $key];
        }

        return [$request->filled('chapter_number') ? $request->integer('chapter_number') : null, null];
    }
}
