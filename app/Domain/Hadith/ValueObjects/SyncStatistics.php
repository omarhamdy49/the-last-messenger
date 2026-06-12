<?php

declare(strict_types=1);

namespace App\Domain\Hadith\ValueObjects;

/**
 * Value object for tracking hadith sync statistics
 *
 * Immutable data structure to hold import/sync metrics
 * with fluent increment methods that return new instances.
 */
final class SyncStatistics
{
    public function __construct(
        public readonly int $filesProcessed = 0,
        public readonly int $filesFailed = 0,
        public readonly int $collections = 0,
        public readonly int $books = 0,
        public readonly int $chapters = 0,
        public readonly int $hadiths = 0,
        public readonly int $translations = 0,
        public readonly int $narrators = 0,
        public readonly int $gradings = 0,
        public readonly int $errors = 0,
    ) {}

    /**
     * Create empty statistics instance
     */
    public static function empty(): self
    {
        return new self;
    }

    /**
     * Create from array (e.g., from import service results)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            filesProcessed: $data['files_processed'] ?? $data['filesProcessed'] ?? 0,
            filesFailed: $data['files_failed'] ?? $data['filesFailed'] ?? 0,
            collections: $data['collections'] ?? 0,
            books: $data['books'] ?? 0,
            chapters: $data['chapters'] ?? 0,
            hadiths: $data['hadiths'] ?? 0,
            translations: $data['translations'] ?? 0,
            narrators: $data['narrators'] ?? 0,
            gradings: $data['gradings'] ?? 0,
            errors: $data['errors'] ?? 0,
        );
    }

    /**
     * Merge with another statistics instance (combine totals)
     */
    public function merge(self $other): self
    {
        return new self(
            filesProcessed: $this->filesProcessed + $other->filesProcessed,
            filesFailed: $this->filesFailed + $other->filesFailed,
            collections: $this->collections + $other->collections,
            books: $this->books + $other->books,
            chapters: $this->chapters + $other->chapters,
            hadiths: $this->hadiths + $other->hadiths,
            translations: $this->translations + $other->translations,
            narrators: $this->narrators + $other->narrators,
            gradings: $this->gradings + $other->gradings,
            errors: $this->errors + $other->errors,
        );
    }

    /**
     * Increment files processed counter
     */
    public function withFileProcessed(): self
    {
        return new self(
            filesProcessed: $this->filesProcessed + 1,
            filesFailed: $this->filesFailed,
            collections: $this->collections,
            books: $this->books,
            chapters: $this->chapters,
            hadiths: $this->hadiths,
            translations: $this->translations,
            narrators: $this->narrators,
            gradings: $this->gradings,
            errors: $this->errors,
        );
    }

    /**
     * Increment files failed counter
     */
    public function withFileFailed(): self
    {
        return new self(
            filesProcessed: $this->filesProcessed,
            filesFailed: $this->filesFailed + 1,
            collections: $this->collections,
            books: $this->books,
            chapters: $this->chapters,
            hadiths: $this->hadiths,
            translations: $this->translations,
            narrators: $this->narrators,
            gradings: $this->gradings,
            errors: $this->errors,
        );
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return $this->errors > 0 || $this->filesFailed > 0;
    }

    /**
     * Get total files (processed + failed)
     */
    public function totalFiles(): int
    {
        return $this->filesProcessed + $this->filesFailed;
    }

    /**
     * Convert to array for display/logging
     */
    public function toArray(): array
    {
        return [
            'files_processed' => $this->filesProcessed,
            'files_failed' => $this->filesFailed,
            'collections' => $this->collections,
            'books' => $this->books,
            'chapters' => $this->chapters,
            'hadiths' => $this->hadiths,
            'translations' => $this->translations,
            'narrators' => $this->narrators,
            'gradings' => $this->gradings,
            'errors' => $this->errors,
        ];
    }

    /**
     * Get formatted table data for console output
     */
    public function toTableRows(bool $includeFiles = false): array
    {
        $rows = [];

        if ($includeFiles) {
            $rows[] = ['إجمالي الملفات - Total Files', $this->totalFiles()];
            $rows[] = ['الملفات الناجحة - Successful Files', $this->filesProcessed];
            $rows[] = ['الملفات الفاشلة - Failed Files', $this->filesFailed];
        }

        $rows = array_merge($rows, [
            ['المجموعات - Collections', $this->collections],
            ['الكتب - Books', $this->books],
            ['الفصول - Chapters', $this->chapters],
            ['الأحاديث - Hadiths', $this->hadiths],
            ['الترجمات - Translations', $this->translations],
            ['الأخطاء - Errors', $this->errors],
        ]);

        return $rows;
    }
}
