<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Blog;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithCollection;
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
    public function surahs(): JsonResponse
    {
        return response()->json([
            'data' => Surah::query()
                ->orderBy('id')
                ->get(['id', 'revelation_order', 'name_ar', 'name_en', 'verses_count']),
        ]);
    }

    public function verses(Surah $surah): JsonResponse
    {
        $verses = Verse::query()
            ->where('surah_id', $surah->id)
            ->with(['translations' => fn ($query) => $query
                ->where('lang', 'ar')
                ->orderBy('resource_id')])
            ->orderBy('verse_number')
            ->get(['id', 'surah_id', 'verse_number', 'verse_key'])
            ->map(fn (Verse $verse): array => [
                'id' => $verse->id,
                'verse_number' => $verse->verse_number,
                'verse_key' => $verse->verse_key,
                'text' => $verse->translations->first()?->text ?? '',
            ])
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
}
