<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links existing tafsirs to a tafsir resource and switches the per-verse
 * uniqueness from (verse_id, lang) to (verse_id, resource_id) so a verse can hold
 * several tafsir sources. Existing rows are backfilled to a default resource built
 * from their language (Arabic = Al-Muyassar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_verse_tafsirs', function (Blueprint $table) {
            $table->foreignId('resource_id')->nullable()->after('verse_id')
                ->constrained('quran_tafsir_resources')->nullOnDelete();
        });

        // Create one default resource per distinct language already present, then
        // point each existing tafsir row at the resource for its language.
        $names = [
            'ar' => ['التفسير الميسر', 'Al-Muyassar'],
            'en' => ['Tafsir (English)', 'Tafsir (English)'],
            'fr' => ['Tafsir (Français)', 'Tafsir (French)'],
            'tr' => ['Tefsir (Türkçe)', 'Tafsir (Turkish)'],
            'ur' => ['تفسیر (اردو)', 'Tafsir (Urdu)'],
        ];

        foreach (DB::table('quran_verse_tafsirs')->distinct()->pluck('lang') as $lang) {
            [$ar, $en] = $names[$lang] ?? ["Tafsir ({$lang})", "Tafsir ({$lang})"];

            $resourceId = DB::table('quran_tafsir_resources')->insertGetId([
                'slug' => 'default-'.$lang,
                'name' => json_encode(['ar' => $ar, 'en' => $en], JSON_UNESCAPED_UNICODE),
                'author' => $lang === 'ar' ? 'مجمع الملك فهد' : null,
                'language' => $lang,
                'is_active' => true,
                'display_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('quran_verse_tafsirs')->where('lang', $lang)->update(['resource_id' => $resourceId]);
        }

        // Swap the uniqueness constraint. Guarded to MySQL: SQLite (test driver)
        // can't reliably drop a named unique index, and correctness there is
        // enforced by the import/app layer.
        if (DB::getDriverName() === 'mysql') {
            Schema::table('quran_verse_tafsirs', function (Blueprint $table) {
                $table->dropUnique(['verse_id', 'lang']);
                $table->unique(['verse_id', 'resource_id']);
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('quran_verse_tafsirs', function (Blueprint $table) {
                $table->dropUnique(['verse_id', 'resource_id']);
                $table->unique(['verse_id', 'lang']);
            });
        }

        Schema::table('quran_verse_tafsirs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resource_id');
        });
    }
};
