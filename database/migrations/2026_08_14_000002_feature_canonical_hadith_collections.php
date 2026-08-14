<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Editorial default: feature the Kutub al-Sittah so the library's featured
 * shelf isn't empty. Guarded — skipped entirely if an admin already
 * featured anything.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('hadith_collections')->where('is_featured', true)->exists()) {
            return;
        }

        DB::table('hadith_collections')
            ->whereIn('slug', ['bukhari', 'muslim', 'nasai', 'abudawud', 'tirmidhi', 'ibnmajah'])
            ->update(['is_featured' => true]);

        \App\Domain\Hadith\Services\HadithBrowseService::forget();
    }

    public function down(): void
    {
        // Editorial data; nothing sensible to restore.
    }
};
