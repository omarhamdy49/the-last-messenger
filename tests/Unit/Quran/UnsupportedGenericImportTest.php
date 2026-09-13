<?php

declare(strict_types=1);

namespace Tests\Unit\Quran;

use App\Domain\Quran\Services\QuranService;
use BadMethodCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class UnsupportedGenericImportTest extends TestCase
{
    public static function imports(): array
    {
        return [
            ['importTranslations', 'quran:import-translation'],
            ['importTafsir', 'quran:import-tafsir'],
            ['importReciters', 'quran:import-reciters'],
        ];
    }

    #[DataProvider('imports')]
    public function test_unsupported_import_reports_the_supported_command(string $method, string $command): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage($command);

        app(QuranService::class)->{$method}(['data' => ['sample']], true);
    }
}
