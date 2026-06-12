<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Importers;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Domain\Shared\Importers\BaseImporter;

/**
 * Hadith Importer
 *
 * Imports Hadith records from CSV/Excel files.
 *
 * Expected CSV columns (required): arabic_text, english_text, book_name, chapter_name, hadith_number
 * Optional columns: grade, narrators, reference
 *
 * The importer resolves book_name to an existing HadithBook (matched by title_en, case-insensitive).
 * If no matching book is found, a new HadithBook is created under a collection whose name_en
 * matches the book_name value. Missing collections are also created on-the-fly.
 *
 * Translation rows (arabic_text as 'ar', english_text as 'en') are stored as HadithTranslation
 * child records with the appropriate language codes.
 */
class HadithImporter extends BaseImporter
{
    /**
     * {@inheritdoc}
     */
    protected function getValidationRules(): array
    {
        return [
            'arabic_text' => 'required|string',
            'english_text' => 'required|string',
            'book_name' => 'required|string|max:255',
            'chapter_name' => 'required|string|max:255',
            'hadith_number' => 'required|string|max:50',
            'grade' => 'nullable|in:sahih,hasan,daif,mawdu,mutawatir,mursal',
            'narrators' => 'nullable|string',
            'reference' => 'nullable|string|max:500',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnMapping(): array
    {
        return [
            'arabic_text' => 'arabic_text',
            'english_text' => 'english_text',
            'book_name' => 'book_name',
            'chapter_name' => 'chapter_name',
            'hadith_number' => 'hadith_number',
            'grade' => 'grade',
            'narrators' => 'narrators',
            'reference' => 'reference',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * A Hadith is considered a duplicate when the same hadith_number already
     * exists in the resolved book.
     */
    protected function isDuplicate(array $row): bool
    {
        $book = $this->resolveBook($row['book_name'] ?? '', $row['chapter_name'] ?? '');

        return Hadith::where('book_id', $book->id)
            ->where('hadith_number', $row['hadith_number'] ?? '')
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Hadith
    {
        $book = $this->resolveBook($row['book_name'] ?? '', $row['chapter_name'] ?? '');

        if ($this->options['update_existing'] ?? false) {
            $hadith = Hadith::where('book_id', $book->id)
                ->where('hadith_number', $row['hadith_number'])
                ->first();

            if ($hadith) {
                $hadith->update($this->buildHadithData($book->id, $row));
                $this->syncTranslations($hadith, $row);

                return $hadith;
            }
        }

        $hadith = Hadith::create($this->buildHadithData($book->id, $row));
        $this->syncTranslations($hadith, $row);

        return $hadith;
    }

    /**
     * Build the core Hadith attribute array from a CSV row.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildHadithData(int $bookId, array $row): array
    {
        $grade = $row['grade'] ?? null;

        return [
            'book_id' => $bookId,
            'hadith_number' => (string) $row['hadith_number'],
            'grading_status' => in_array($grade, Hadith::getGradingStatuses(), true) ? $grade : null,
        ];
    }

    /**
     * Create or update HadithTranslation records for Arabic and English text.
     */
    private function syncTranslations(Hadith $hadith, array $row): void
    {
        $translations = [
            'ar' => trim((string) ($row['arabic_text'] ?? '')),
            'en' => trim((string) ($row['english_text'] ?? '')),
        ];

        foreach ($translations as $language => $text) {
            if ($text === '') {
                continue;
            }

            HadithTranslation::updateOrCreate(
                ['hadith_id' => $hadith->id, 'language_code' => $language],
                ['text' => $text]
            );
        }
    }

    /**
     * Resolve or create a HadithBook from the book_name and chapter_name values.
     *
     * Lookup is case-insensitive on title_en. If no book is found, a new one is
     * created under a collection whose name_en matches book_name; that collection
     * is also created if missing.
     */
    private function resolveBook(string $bookName, string $chapterName): HadithBook
    {
        $existing = HadithBook::whereRaw('LOWER(title_en) = ?', [strtolower($bookName)])
            ->select(['id', 'collection_id', 'title_en', 'title_ar'])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $collection = $this->resolveCollection($bookName);

        return HadithBook::create([
            'collection_id' => $collection->id,
            'title_en' => $bookName,
            'title_ar' => $bookName,
            'chapter_title_en' => $chapterName,
            'chapter_title_ar' => $chapterName,
        ]);
    }

    /**
     * Resolve or create a HadithCollection whose name_en matches the given name.
     */
    private function resolveCollection(string $name): HadithCollection
    {
        $existing = HadithCollection::whereRaw('LOWER(name_en) = ?', [strtolower($name)])
            ->select(['id', 'name_ar', 'name_en'])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return HadithCollection::create([
            'name_en' => $name,
            'name_ar' => $name,
        ]);
    }
}
