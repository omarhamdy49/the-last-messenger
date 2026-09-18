<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site\Quran;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SectionType;
use App\Domain\Quran\Services\AudioService;
use App\Domain\Quran\Services\NavigationService;
use App\Domain\Quran\Services\ReaderService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public Quran reader: index, chapter page and its lazy JSON endpoints, verse links.
 * Everything is served from local data (no Quran Foundation calls at request time).
 */
final class ReaderController extends Controller
{
    public function __construct(
        private readonly ReaderService $reader,
        private readonly NavigationService $navigation,
    ) {}

    public function index(): View
    {
        $locale = app()->getLocale();
        $surahs = $this->reader->chapterList($locale);

        $sections = [];
        foreach (SectionType::cases() as $type) {
            $sections[$type->value] = $this->navigation->sections($type);
        }

        return view('public.quran.index', [
            'surahs' => $surahs,
            'stats' => [
                'total' => count($surahs),
                'verses' => array_sum(array_column($surahs, 'verses')),
                'makki' => count(array_filter($surahs, static fn (array $s): bool => $s['type'] === 'makki')),
                'madani' => count(array_filter($surahs, static fn (array $s): bool => $s['type'] === 'madani')),
            ],
            'sections' => $sections,
            'verseOfTheDay' => $this->navigation->verseOfTheDay($locale),
        ]);
    }

    public function surahsJson(): JsonResponse
    {
        return response()->json(['surahs' => $this->reader->chapterList(app()->getLocale())]);
    }

    public function surah(Request $request, int $surahNumber): View
    {
        abort_if($surahNumber < 1 || $surahNumber > 114, 404);

        $locale = app()->getLocale();
        $options = $this->reader->options($request->query(), $locale);
        $payload = $this->reader->chapterPayload($surahNumber, $options, $locale) ?? abort(404);
        $reciters = $this->reader->readerReciters($surahNumber, $locale);

        $ids = array_column($reciters, 'id');
        $forced = $request->integer('reciter');
        $default = collect($reciters)->firstWhere('isDefault', true)['id'] ?? ($reciters[0]['id'] ?? 0);

        return view('public.quran.surah', $payload + [
            'reciters' => $reciters,
            'defaultReciterId' => $default,
            'forcedReciterId' => $forced > 0 && in_array($forced, $ids, true) ? $forced : null,
            'firstRecitationUrl' => collect($reciters)->firstWhere('id', $default)['surahAudioUrl'] ?? null,
            'currentLocale' => $locale,
        ]);
    }

    /**
     * `/quran/verse/2:255` → the chapter page anchored at the verse (JSON: the location).
     */
    public function verse(Request $request, string $key): RedirectResponse|JsonResponse
    {
        [$chapter, $verse] = $this->navigation->verseLocation($key) ?? abort(404);

        if ($request->expectsJson()) {
            return response()->json(['verse_key' => "{$chapter}:{$verse}", 'chapter' => $chapter, 'verse' => $verse,
                'url' => route('public.quran.surah', $chapter).'#v'.$verse]);
        }

        return redirect()->to(route('public.quran.surah', $chapter).'#v'.$verse);
    }

    public function random(): RedirectResponse
    {
        $key = $this->navigation->randomVerseKey() ?? abort(404);

        return redirect()->route('public.quran.verse', $key);
    }

    public function translationResource(int $resource): View
    {
        return $this->resourcePage(ResourceKind::TRANSLATION, $resource);
    }

    public function tafsirResource(int $resource): View
    {
        return $this->resourcePage(ResourceKind::TAFSIR, $resource);
    }

    public function chapterTafsir(int $resource, int $surahNumber): JsonResponse
    {
        abort_if($surahNumber < 1 || $surahNumber > 114, 404);

        return response()->json($this->reader->chapterTafsir($surahNumber, $resource));
    }

    private function resourcePage(ResourceKind $kind, int $id): View
    {
        $locale = app()->getLocale();

        return view('public.quran.resource', [
            'resource' => $this->reader->publicResource($kind, $id, $locale) ?? abort(404),
            'surahs' => $this->reader->chapterList($locale),
        ]);
    }

    public function verseTafsir(int $verseId, int $resourceId): JsonResponse
    {
        return response()->json(['text' => $this->reader->verseTafsir($verseId, $resourceId)]);
    }

    public function verseWords(int $verseId): JsonResponse
    {
        return response()->json(['words' => $this->reader->verseWords($verseId, app()->getLocale())]);
    }

    public function surahInfo(int $surahNumber): JsonResponse
    {
        abort_if($surahNumber < 1 || $surahNumber > 114, 404);

        return response()->json(['infos' => $this->reader->chapterInfos($surahNumber)]);
    }

    public function surahTranslation(int $surahNumber, int $resourceId): JsonResponse
    {
        abort_if($surahNumber < 1 || $surahNumber > 114, 404);

        return response()->json($this->reader->chapterTranslation($surahNumber, $resourceId));
    }

    /**
     * Verse timings of one chapter reciter's file: `{surah, reciter_id, ayahs: [[ayah, startMs, endMs]]}`.
     * Always 200 — no timings means no highlighting, nothing else.
     */
    public function surahTimings(int $surahNumber, int $reciterId, AudioService $audio): JsonResponse
    {
        abort_if($surahNumber < 1 || $surahNumber > 114, 404);

        return response()->json([
            'surah' => $surahNumber,
            'reciter_id' => $reciterId,
            'ayahs' => $this->reader->timings($surahNumber, $reciterId, $audio),
        ]);
    }
}
