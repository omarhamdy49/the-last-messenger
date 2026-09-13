<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        if (Artisan::call('quran:group-reciters') !== 0) {
            throw new RuntimeException('Canonical reciter backfill failed: '.Artisan::output());
        }
    }

    public function down(): void
    {
        // Associations are additive; the preceding schema migration removes them.
    }
};
