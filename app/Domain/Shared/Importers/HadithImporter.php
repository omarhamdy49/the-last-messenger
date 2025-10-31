<?php

declare(strict_types=1);

namespace App\Domain\Shared\Importers;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Enums\HadithGrade;

/**
 * Hadith Importer
 *
 * Imports hadith from CSV/Excel files
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
            'hadith_number' => 'required|integer',
            'narrators' => 'nullable|string',
            'grade' => 'nullable|in:sahih,hasan,daif,mawdu,mursal',
            'reference' => 'nullable|string|max:255',
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
            'narrators' => 'narrators',
            'grade' => 'grade',
            'reference' => 'reference',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isDuplicate(array $row): bool
    {
        $book = HadithBook::where('name->en', $row['book_name'])->first();

        if (! $book) {
            return false;
        }

        return Hadith::where('hadith_book_id', $book->id)
            ->where('hadith_number', $row['hadith_number'])
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    protected function importRow(array $row): Hadith
    {
        // Find or create book
        $book = HadithBook::where('name->en', $row['book_name'])
            ->orWhere('name->ar', $row['book_name'])
            ->first();

        if (! $book) {
            $book = HadithBook::create([
                'name' => [
                    'ar' => $row['book_name'],
                    'en' => $row['book_name'],
                    'fr' => $row['book_name'],
                    'tr' => $row['book_name'],
                    'ur' => $row['book_name'],
                ],
                'author' => [
                    'ar' => 'مؤلف',
                    'en' => 'Author',
                    'fr' => 'Auteur',
                    'tr' => 'Yazar',
                    'ur' => 'مصنف',
                ],
            ]);
        }

        // Find or create chapter
        $chapter = HadithChapter::where('hadith_book_id', $book->id)
            ->where('name->en', $row['chapter_name'])
            ->first();

        if (! $chapter) {
            $chapterNumber = HadithChapter::where('hadith_book_id', $book->id)->max('chapter_number') + 1;

            $chapter = HadithChapter::create([
                'hadith_book_id' => $book->id,
                'chapter_number' => $chapterNumber,
                'name' => [
                    'ar' => $row['chapter_name'],
                    'en' => $row['chapter_name'],
                    'fr' => $row['chapter_name'],
                    'tr' => $row['chapter_name'],
                    'ur' => $row['chapter_name'],
                ],
            ]);
        }

        // Create hadith
        $hadith = Hadith::create([
            'hadith_book_id' => $book->id,
            'hadith_chapter_id' => $chapter->id,
            'hadith_number' => (int) $row['hadith_number'],
            'arabic_text' => $row['arabic_text'],
            'english_text' => $row['english_text'],
            'grade' => $row['grade'] ? HadithGrade::tryFrom($row['grade']) : null,
            'reference' => $row['reference'] ?? null,
        ]);

        // Attach narrators
        if (! empty($row['narrators'])) {
            $narratorNames = array_map('trim', explode('|', $row['narrators']));
            $narratorIds = [];

            foreach ($narratorNames as $narratorName) {
                $narrator = HadithNarrator::where('name->en', $narratorName)
                    ->orWhere('name->ar', $narratorName)
                    ->first();

                if ($narrator) {
                    $narratorIds[] = $narrator->id;
                }
            }

            if (! empty($narratorIds)) {
                $hadith->narrators()->attach($narratorIds);
            }
        }

        return $hadith;
    }
}
