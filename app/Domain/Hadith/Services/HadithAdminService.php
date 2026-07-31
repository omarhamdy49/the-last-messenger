<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HadithAdminService
{
    public function __construct(
        private readonly ArabicTextNormalizer $normalizer,
        private readonly HadithRepositoryInterface $hadiths,
    ) {}

    /** @param array<string, mixed> $data */
    public function updateHadith(Hadith $hadith, array $data): Hadith
    {
        return DB::transaction(function () use ($hadith, $data): Hadith {
            /** @var Hadith $locked */
            $locked = Hadith::query()->lockForUpdate()->findOrFail($hadith->id);
            $this->guardVersion($locked, (string) $data['updated_at']);

            $locked->update(['is_published' => $data['is_published']]);

            foreach ($data['texts'] as $locale => $text) {
                $body = trim((string) ($text['body'] ?? ''));
                if ($body === '') {
                    continue;
                }

                $locked->texts()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'body' => $body,
                        'search_body' => $this->normalizer->forLocale($body, $locale),
                        'narrated_by' => $text['narrated_by'] ?? null,
                        'sanad' => $text['sanad'] ?? null,
                        'matn' => $text['matn'] ?? null,
                        'translator' => $text['translator'] ?? null,
                        'source_url' => $text['source_url'] ?? null,
                    ],
                );
            }

            $existing = $locked->gradings()->get()->keyBy('id');
            $kept = [];

            foreach ($data['gradings'] ?? [] as $grading) {
                $id = isset($grading['id']) ? (int) $grading['id'] : null;
                $values = [
                    'scholar_id' => $grading['scholar_id'] ?? null,
                    'grade' => $grading['grade'],
                    'scope' => $grading['scope'],
                    'part_label' => $grading['part_label'] ?? null,
                    'authority_rank' => $grading['authority_rank'],
                    'raw_grade' => $grading['raw_grade'],
                    'locale' => $grading['locale'] ?? null,
                    'notes' => $grading['notes'] ?? null,
                    'source_reference' => $grading['source_reference'] ?? null,
                ];

                if ($id !== null) {
                    $claim = $existing->get($id);
                    if ($claim === null) {
                        throw ValidationException::withMessages([
                            'gradings' => __('hadith.admin.grading_mismatch'),
                        ]);
                    }
                    $claim->update($values);
                    $kept[] = $id;
                } else {
                    $kept[] = $locked->gradings()->create($values)->id;
                }
            }

            $locked->gradings()->whereNotIn('id', $kept)->delete();
            $this->hadiths->recomputeDisplayGrade($locked->id);
            $locked->touch();

            return $locked->fresh(['collection', 'book', 'chapter', 'texts', 'gradings.scholar']);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateBook(HadithBook $book, array $data): HadithBook
    {
        return DB::transaction(function () use ($book, $data): HadithBook {
            /** @var HadithBook $locked */
            $locked = HadithBook::query()->lockForUpdate()->findOrFail($book->id);
            $this->guardVersion($locked, (string) $data['updated_at']);
            $locked->update([
                'title' => $data['title'],
                'intro' => $data['intro'],
                'is_published' => $data['is_published'],
            ]);

            return $locked->fresh(['collection']);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateCollection(HadithCollection $collection, array $data): HadithCollection
    {
        return DB::transaction(function () use ($collection, $data): HadithCollection {
            /** @var HadithCollection $locked */
            $locked = HadithCollection::query()->lockForUpdate()->findOrFail($collection->id);
            $this->guardVersion($locked, (string) $data['updated_at']);
            $locked->update([
                'name' => $data['name'],
                'author' => $data['author'],
                'short_intro' => $data['short_intro'],
                'about' => $data['about'],
                'is_published' => $data['is_published'],
                'featured' => $data['featured'],
            ]);

            return $locked->fresh();
        });
    }

    /** @param array<string, mixed> $data */
    public function updateChapter(HadithChapter $chapter, array $data): HadithChapter
    {
        return DB::transaction(function () use ($chapter, $data): HadithChapter {
            /** @var HadithChapter $locked */
            $locked = HadithChapter::query()->lockForUpdate()->findOrFail($chapter->id);
            $this->guardVersion($locked, (string) $data['updated_at']);
            $locked->update([
                'title' => $data['title'],
                'intro' => $data['intro'],
                'ending' => $data['ending'],
                'is_published' => $data['is_published'],
            ]);

            return $locked->fresh(['book.collection']);
        });
    }

    private function guardVersion(Model $model, string $submitted): void
    {
        if ($model->updated_at?->toISOString() !== $submitted) {
            throw ValidationException::withMessages([
                'updated_at' => __('hadith.admin.concurrent_update'),
            ]);
        }
    }
}
