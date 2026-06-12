<?php

declare(strict_types=1);

namespace App\Console\Concerns;

use App\Domain\Hadith\ValueObjects\SyncStatistics;
use DateTimeInterface;

/**
 * Trait for displaying hadith sync command output
 *
 * Provides consistent, bilingual (Arabic/English) output formatting
 * for hadith sync commands with support for RTL text.
 */
trait DisplaysHadithSyncOutput
{
    protected ?DateTimeInterface $operationStartTime = null;

    /**
     * Display command banner
     */
    protected function displayBanner(string $title, string $subtitle = ''): void
    {
        $this->newLine();
        $this->line('╔═══════════════════════════════════════════════════════════════╗');
        $this->line("║   {$title}");
        if ($subtitle) {
            $this->line("║   {$subtitle}");
        }
        $this->line('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * Display dry run warning
     */
    protected function displayDryRunWarning(): void
    {
        $this->warn('🔸 وضع المحاكاة نشط - لن يتم حفظ البيانات');
        $this->warn('🔸 DRY RUN MODE - No data will be saved');
        $this->newLine();
    }

    /**
     * Display processing file message
     */
    protected function displayProcessingFile(string $filename): void
    {
        $this->info("📂 معالجة ملف - Processing file: {$filename}");
        $this->newLine();
    }

    /**
     * Display processing directory message
     */
    protected function displayProcessingDirectory(string $path, bool $recursive = false): void
    {
        $this->info("📁 مسح المجلد - Scanning directory: {$path}");
        if ($recursive) {
            $this->info('🔄 وضع المسح المتكرر نشط - Recursive scanning enabled');
        }
        $this->newLine();
    }

    /**
     * Display files found message
     */
    protected function displayFilesFound(int $count): void
    {
        $this->info("📊 تم العثور على {$count} ملف JSON");
        $this->info("📊 Found {$count} JSON files");
        $this->newLine();
    }

    /**
     * Display no files found warning
     */
    protected function displayNoFilesFound(): void
    {
        $this->warn('⚠️  لم يتم العثور على ملفات JSON في المجلد');
        $this->warn('⚠️  No JSON files found in directory');
    }

    /**
     * Display path not found error
     */
    protected function displayPathNotFound(string $path): void
    {
        $this->error("❌ المسار غير موجود - Path not found: {$path}");
    }

    /**
     * Display invalid path error
     */
    protected function displayInvalidPath(): void
    {
        $this->error('❌ المسار غير صالح - Invalid path (not a file or directory)');
    }

    /**
     * Display import failed error
     */
    protected function displayImportFailed(string $message): void
    {
        $this->newLine();
        $this->error('❌ فشل الاستيراد - Import failed: '.$message);
    }

    /**
     * Display import summary table
     */
    protected function displayImportSummary(SyncStatistics $stats, string $filename): void
    {
        $this->newLine();
        $this->line('╔═══════════════════════════════════════════════════════════════╗');
        $this->line("║   ملخص الاستيراد - Import Summary: {$filename}");
        $this->line('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $tableData = array_map(
            fn ($row) => [$row[0], $this->formatCount($row[1], $row[0])],
            $stats->toTableRows()
        );

        $this->table(
            ['المقياس - Metric', 'العدد - Count'],
            $tableData
        );

        $this->displayDuration();
    }

    /**
     * Display overall summary for directory import
     */
    protected function displayOverallSummary(SyncStatistics $stats): void
    {
        $this->newLine();
        $this->line('╔═══════════════════════════════════════════════════════════════╗');
        $this->line('║           الملخص الإجمالي - Overall Summary                ║');
        $this->line('╚═══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $tableData = array_map(
            fn ($row) => [$row[0], $this->formatCount($row[1], $row[0])],
            $stats->toTableRows(includeFiles: true)
        );

        $this->table(
            ['المقياس - Metric', 'القيمة - Value'],
            $tableData
        );

        $this->displayDuration('إجمالي المدة - Total Duration');
        $this->displayCompletionMessage($stats);
    }

    /**
     * Display sync log ID
     */
    protected function displaySyncLogId(int $id): void
    {
        $this->newLine();
        $this->info("📋 معرف سجل المزامنة - Sync Log ID: {$id}");
    }

    /**
     * Display completion message based on results
     */
    protected function displayCompletionMessage(SyncStatistics $stats): void
    {
        $this->newLine();
        if (! $stats->hasErrors()) {
            $this->info('✅ اكتملت جميع الملفات بنجاح!');
            $this->info('✅ All files completed successfully!');
        } else {
            $failedCount = $stats->filesFailed;
            $this->warn("⚠️  اكتمل مع {$failedCount} أخطاء - Completed with {$failedCount} errors");
            $this->warn('💡 راجع السجلات للحصول على التفاصيل - Check logs for details');
        }
    }

    /**
     * Start tracking operation duration
     */
    protected function startTimer(): void
    {
        $this->operationStartTime = new \DateTimeImmutable;
    }

    /**
     * Display operation duration
     */
    protected function displayDuration(string $label = 'المدة - Duration'): void
    {
        if (! $this->operationStartTime) {
            return;
        }

        $duration = $this->formatDuration($this->operationStartTime);
        $this->newLine();
        $this->info("⏱️  {$label}: {$duration}");
    }

    /**
     * Format duration since start time
     */
    protected function formatDuration(DateTimeInterface $startTime): string
    {
        $now = new \DateTimeImmutable;
        $diff = $startTime->diff($now);

        if ($diff->h > 0) {
            return sprintf(
                '%d ساعة %d دقيقة %d ثانية - %d hours %d minutes %d seconds',
                $diff->h, $diff->i, $diff->s,
                $diff->h, $diff->i, $diff->s
            );
        }

        if ($diff->i > 0) {
            return sprintf(
                '%d دقيقة %d ثانية - %d minutes %d seconds',
                $diff->i, $diff->s,
                $diff->i, $diff->s
            );
        }

        return sprintf('%d ثانية - %d seconds', $diff->s, $diff->s);
    }

    /**
     * Format count with color based on context
     */
    protected function formatCount(int $count, string $label = ''): string
    {
        $isError = str_contains(strtolower($label), 'error') ||
                   str_contains(strtolower($label), 'fail') ||
                   str_contains($label, 'أخطاء') ||
                   str_contains($label, 'فاشل');

        if ($isError) {
            return $this->formatErrorCount($count);
        }

        return (string) $count;
    }

    /**
     * Format error count with color
     */
    protected function formatErrorCount(int $count): string
    {
        return match (true) {
            $count === 0 => "<fg=green>{$count}</>",
            $count < 10 => "<fg=yellow>{$count}</>",
            default => "<fg=red>{$count}</>",
        };
    }

    /**
     * Create and display a simple progress bar
     */
    protected function createSimpleProgressBar(string $message = 'جاري الاستيراد... Importing...'): mixed
    {
        $progressBar = $this->output->createProgressBar();
        $progressBar->setFormat(' [%bar%] %message%');
        $progressBar->setMessage($message);
        $progressBar->start();

        return $progressBar;
    }

    /**
     * Create and display a progress bar with count
     */
    protected function createCountProgressBar(int $total): mixed
    {
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $progressBar->setMessage('جاري التحضير... Preparing...');
        $progressBar->start();

        return $progressBar;
    }

    /**
     * Finish progress bar
     */
    protected function finishProgressBar(mixed $progressBar, string $message = 'اكتمل! Completed!'): void
    {
        $progressBar->setMessage($message);
        $progressBar->finish();
        $this->newLine(2);
    }
}
