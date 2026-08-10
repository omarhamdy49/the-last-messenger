<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Authenticated lookup endpoints used by the article module selector.
 */
final class ArticleModuleController extends Controller
{
    /**
     * Paginated, searchable episode picker for the article's video queue.
     *
     * Paginated rather than dumped whole: a synced YouTube channel runs to
     * hundreds of episodes, which is both a slow response and an unusable
     * list. Search covers the localized title and the slug; `program` narrows
     * to one series.
     */
    public function episodes(Request $request): JsonResponse
    {
        $locale = $this->contentLocale($request);
        $term = trim((string) $request->query('q', ''));
        $programId = $request->query('program');

        $episodes = MediaEpisode::query()
            ->with([
                'translations:id,media_episode_id,locale,title',
                'program:id,slug',
            ])
            ->when($programId !== null && $programId !== '', fn (Builder $q) => $q->where('program_id', (int) $programId))
            ->when($term !== '', fn (Builder $q) => $q->where(function (Builder $inner) use ($term): void {
                $inner->where('slug', 'LIKE', "%{$term}%")
                    ->orWhereHas('translations', fn (Builder $t) => $t->where('title', 'LIKE', "%{$term}%"));
            }))
            ->orderBy('program_id')
            ->orderBy('season_number')
            ->orderBy('episode_number')
            ->paginate(perPage: 12, page: (int) $request->query('page', 1));

        return response()->json([
            'data' => $episodes->getCollection()->map(fn (MediaEpisode $episode): array => [
                'id' => $episode->id,
                'title' => $episode->getTranslation('title', $locale)
                    ?: $episode->getTranslation('title', 'ar')
                    ?: $episode->slug,
                'slug' => $episode->slug,
                'program' => $episode->program?->slug,
                'season' => $episode->season_number,
                'episode' => $episode->episode_number,
                'is_published' => (bool) $episode->is_published,
                'thumb' => $this->youTubeThumb((string) $episode->video_url),
            ])->values(),
            'meta' => [
                'current_page' => $episodes->currentPage(),
                'last_page' => $episodes->lastPage(),
                'total' => $episodes->total(),
            ],
        ]);
    }

    /**
     * Programs list for the picker's filter dropdown.
     */
    public function episodePrograms(): JsonResponse
    {
        return response()->json([
            'data' => MediaProgram::query()
                ->orderBy('slug')
                ->get(['id', 'slug'])
                ->map(fn (MediaProgram $program): array => [
                    'id' => $program->id,
                    'label' => $program->slug,
                ])
                ->values(),
        ]);
    }

    /**
     * Poster frame for an episode's YouTube URL, or null when it is not a
     * recognisable YouTube link (the picker then shows a placeholder).
     */
    private function youTubeThumb(string $url): ?string
    {
        $patterns = [
            '~youtu\.be/([A-Za-z0-9_-]{11})~',
            '~youtube(?:-nocookie)?\.com/(?:embed|v|shorts|live)/([A-Za-z0-9_-]{11})~',
            '~youtube(?:-nocookie)?\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m) === 1) {
                return 'https://i.ytimg.com/vi/'.$m[1].'/mqdefault.jpg';
            }
        }

        return null;
    }

    public function surahs(): JsonResponse
    {
        return response()->json([
            'data' => Surah::query()
                ->orderBy('id')
                ->get(['id', 'revelation_order', 'name_ar', 'name_en', 'verses_count']),
        ]);
    }

    public function verses(Request $request, Surah $surah): JsonResponse
    {
        $locale = $this->contentLocale($request);
        $translationLocales = array_values(array_unique([$locale, 'en', 'ar']));

        $verses = Verse::query()
            ->where('surah_id', $surah->id)
            ->with(['translations' => fn ($query) => $query
                ->whereIn('lang', $translationLocales)
                ->orderBy('resource_id')])
            ->orderBy('verse_number')
            ->get(['id', 'surah_id', 'verse_number', 'verse_key'])
            ->map(function (Verse $verse) use ($locale, $translationLocales): array {
                $translations = $verse->translations
                    ->groupBy('lang')
                    ->map(fn ($items) => $items->first()?->text ?? '');
                $resolvedLocale = collect($translationLocales)
                    ->first(fn (string $candidate): bool => filled($translations->get($candidate)));

                return [
                    'id' => $verse->id,
                    'verse_number' => $verse->verse_number,
                    'verse_key' => $verse->verse_key,
                    'text' => $resolvedLocale ? $translations->get($resolvedLocale, '') : '',
                    'text_locale' => $resolvedLocale ?? $locale,
                ];
            })
            ->values();

        return response()->json(['data' => $verses]);
    }

    public function hadithCollections(): JsonResponse
    {
        $collections = HadithCollection::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title_ar', 'title_en'])
            ->map(fn (HadithCollection $collection): array => [
                'id' => $collection->id,
                'name_ar' => $collection->title_ar,
                'name_en' => $collection->title_en,
            ])
            ->values();

        return response()->json(['data' => $collections]);
    }

    public function hadiths(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'collection_id' => ['nullable', 'integer', 'exists:hadith_collections,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Hadith::query()
            ->publiclyVisible()
            ->with(['collection:id,title_ar,title_en', 'book:id,collection_id,title_ar,title_en'])
            ->select([
                'id',
                'collection_id',
                'book_id',
                'reference_key',
                'number',
                'grade',
                'raw_grade',
                'text_ar',
                'text_en',
                'sort_order',
            ]);

        if (! empty($validated['collection_id'])) {
            $query->where('collection_id', (int) $validated['collection_id']);
        }

        $search = trim((string) ($validated['q'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('text_ar', 'like', "%{$search}%")
                    ->orWhere('text_en', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%")
                    ->orWhere('reference_key', 'like', "%{$search}%");
            });
        }

        $hadiths = $query
            ->orderBy('collection_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate((int) ($validated['per_page'] ?? 10));

        return response()->json([
            'data' => $hadiths->getCollection()
                ->map(fn (Hadith $hadith): array => [
                    'id' => $hadith->id,
                    'text_ar' => $hadith->text_ar ?? '',
                    'text_en' => $hadith->text_en ?? '',
                    'collection' => $hadith->collection?->title_ar ?? '',
                    'collection_en' => $hadith->collection?->title_en ?? '',
                    'book' => $hadith->book?->title_ar ?? '',
                    'number' => $hadith->number,
                    'reference_key' => $hadith->reference_key,
                    'grade' => $hadith->grade?->value ?? '',
                ])
                ->values(),
            'meta' => [
                'current_page' => $hadiths->currentPage(),
                'per_page' => $hadiths->perPage(),
                'total' => $hadiths->total(),
                'last_page' => $hadiths->lastPage(),
            ],
        ]);
    }

    private function contentLocale(Request $request): string
    {
        $requested = strtolower((string) $request->query('locale', app()->getLocale()));
        $supported = array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]));

        return in_array($requested, $supported, true)
            ? $requested
            : (string) app()->getLocale();
    }
}
