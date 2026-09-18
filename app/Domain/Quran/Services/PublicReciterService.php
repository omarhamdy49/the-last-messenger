<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Repositories\AudioRepositoryInterface;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Public reciters directory and reciter page. The directory lists whole-chapter reciters;
 * a verse-by-verse recitation still has its own page, reachable from the reader.
 */
final class PublicReciterService
{
    public function __construct(
        private readonly ResourceRepositoryInterface $resources,
        private readonly AudioRepositoryInterface $audio,
        private readonly ReaderService $reader,
    ) {}

    /**
     * @return array{all: list<array<string, mixed>>, featured: list<array<string, mixed>>, total: int}
     */
    public function listing(string $locale): array
    {
        return Cache::remember(QuranCacheKeys::reciterDirectory($locale), 3600, function () use ($locale): array {
            // Whole-surah reciters only, which is every reciter the site plays: the
            // reader seeks inside one gapless recording, so a verse-by-verse recitation
            // is never offered. Listing both also put the same person on the page
            // twice — which is what a reader reads as a duplicate.
            $active = $this->resources->active(ResourceKind::CHAPTER_RECITER);
            $ids = $active->pluck('id')->flip();
            $recordings = $active->whereNotNull('parent_id')->countBy('parent_id');

            // A recording filed under a reciter is reached from that reciter's page. It
            // only stands alone here when its reciter is not listed (hidden or removed).
            $all = $active
                ->filter(static fn (QuranResource $r): bool => $r->parent_id === null || ! $ids->has($r->parent_id))
                ->map(fn (QuranResource $r): array => ['recordingsCount' => 1 + (int) ($recordings[$r->id] ?? 0)] + $this->card($r, $locale))
                ->values()->all();

            $all = ReciterLabeller::disambiguate($all, ['styleLabel', 'sourceHint', 'kindLabel']);

            return [
                'all' => $all,
                'featured' => array_slice(array_values(array_filter($all, static fn (array $r): bool => $r['isFeatured'])), 0, 3),
                'total' => count($all),
            ];
        });
    }

    /**
     * @return array{reciter: array<string, mixed>, recitations: list<array{surah_number: int, surah_name: string, audio_url: string, duration_ms: ?int, file_size: ?int, has_timestamps: bool}>}|null
     */
    public function show(string $slug, string $locale): ?array
    {
        $reciter = $this->resources->findBySlug(ResourceKind::CHAPTER_RECITER, $slug)
            ?? $this->resources->findBySlug(ResourceKind::RECITATION, $slug);
        if ($reciter === null || ! $reciter->is_active) {
            return null;
        }

        return Cache::remember(QuranCacheKeys::reciterPage($reciter->id, $locale), 3600, function () use ($reciter, $locale): array {
            $names = collect($this->reader->chapterList($locale))->pluck('nameAr', 'number');
            $recitations = [];

            if ($reciter->kind === ResourceKind::CHAPTER_RECITER) {
                foreach ($this->audio->chapterFiles($reciter->id) as $file) {
                    $recitations[] = [
                        'surah_number' => (int) $file->chapter_id,
                        'surah_name' => (string) ($names[$file->chapter_id] ?? ''),
                        'audio_url' => (string) AudioService::absolutize($file->url),
                        'duration_ms' => $file->duration_ms,
                        'file_size' => $file->file_size,
                        'has_timestamps' => (bool) $file->has_timestamps,
                    ];
                }
            }

            return [
                'recordings' => $this->group($reciter, $locale),
                'reciter' => $this->card($reciter, $locale) + [
                    'name' => $reciter->displayName($locale),
                    'image' => $this->imageUrl($reciter),
                    'riwayah' => $reciter->qirat,
                    'is_featured' => $reciter->is_default,
                    'followers_count' => 0,
                    'country' => null,
                    'country_flag' => null,
                    'country_name' => null,
                    'info' => $reciter->info,
                ],
                'recitations' => $recitations,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function card(QuranResource $r, string $locale): array
    {
        $nameAr = (string) ($r->getTranslation('name', 'ar', false) ?? $r->name);
        $nameEn = (string) ($r->getTranslation('name', 'en', false) ?? $r->name);

        return [
            'id' => $r->id,
            'slug' => $r->slug,
            'kind' => $r->kind->value,
            'nameAr' => $nameAr,
            'nameEn' => $nameEn,
            'country' => null,
            'countryFlag' => null,
            'countryName' => null,
            'style' => $r->style?->value,
            'styleLabel' => $r->style?->label($locale),
            'kindLabel' => __($r->kind === ResourceKind::CHAPTER_RECITER ? 'quran.public.kind_chapter' : 'quran.public.kind_ayah'),
            'sourceHint' => ReciterLabeller::sourceHint($r->name),
            'styles' => array_values(array_filter([$r->style?->value, $r->qirat !== null && str_contains(strtolower($r->qirat), 'hafs') ? 'hafs' : null])),
            'qirat' => $r->qirat,
            'isFeatured' => $r->is_default,
            'followersCount' => 0,
            'profileImage' => $this->imageUrl($r),
            'description' => $r->getTranslation('description', $locale, false),
            'recitationsCount' => $r->kind === ResourceKind::CHAPTER_RECITER ? $r->records_count : (int) round($r->records_count / 6236 * 114),
            'recordingsCount' => 1,
            'aliases' => implode(' ', array_unique([$r->slug, $nameAr, $nameEn])),
        ];
    }

    /**
     * The reciter and everything filed under them, for the page's recording switcher.
     * Empty when the reciter has a single recording — there is nothing to switch to.
     *
     * @return list<array{slug: string, name: string, label: string, count: int, current: bool}>
     */
    private function group(QuranResource $reciter, string $locale): array
    {
        $rootId = $reciter->parent_id ?? $reciter->id;
        $members = $this->resources->active($reciter->kind)
            ->filter(static fn (QuranResource $r): bool => $r->id === $rootId || $r->parent_id === $rootId)
            ->sortBy(static fn (QuranResource $r): int => $r->id === $rootId ? 0 : 1)
            ->values();

        if ($members->count() < 2) {
            return [];
        }

        return $members->map(static fn (QuranResource $r): array => [
            'slug' => $r->slug,
            'name' => $r->displayName($locale),
            'label' => implode(' · ', array_filter([$r->style?->label($locale), $r->qirat])) ?: $r->displayName($locale),
            'count' => (int) $r->records_count,
            'current' => $r->id === $reciter->id,
        ])->all();
    }

    private function imageUrl(QuranResource $r): ?string
    {
        if ($r->image === null || $r->image === '') {
            return null;
        }

        return str_starts_with($r->image, 'http') ? $r->image : Storage::disk('public')->url($r->image);
    }
}
