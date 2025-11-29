<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Hadith Chapter Repository Interface
 *
 * Contract for Hadith Chapter data access operations.
 */
interface ChapterRepositoryInterface extends RepositoryInterface
{
    /**
     * Get chapters by book
     */
    public function getByBook(int $bookId): Collection;

    /**
     * Get chapter with hadiths
     */
    public function getWithHadiths(int $id): ?HadithChapter;

    /**
     * Get chapter by book and chapter number
     */
    public function getByBookAndNumber(int $bookId, int $chapterNumber): ?HadithChapter;
}
