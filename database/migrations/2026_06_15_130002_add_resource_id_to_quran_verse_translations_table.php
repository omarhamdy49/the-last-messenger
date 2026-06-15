<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links existing translations to a translation resource and switches per-verse
 * uniqueness from (verse_id, lang) to (verse_id, resource_id) so a verse can hold
 * several translation editions per language. Existing rows are backfilled to a
 * default resource whose slug matches the editions catalog, so a later
 * `quran:import-translation` of the same edition updates these rows in place
 * instead of duplicating them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quran_verse_translations', function (Blueprint $table) {
            $table->foreignId('resource_id')->nullable()->after('verse_id')
                ->constrained('quran_translation_resources')->nullOnDelete();
        });

        // Map each existing lang code to the catalog edition it was imported from
        // (see config/quran-editions.php). slug, [name_ar, name_en], author, direction.
        $defaults = [
            'ar' => ['ar-uthmani', ['القرآن الكريم (رسم عثماني)', 'The Noble Qur’an (Uthmani)'], 'مجمع الملك فهد لطباعة المصحف الشريف', 'rtl'],
            'en' => ['en-sahih-international', ['صحيح إنترناشونال', 'Saheeh International'], 'Saheeh International', 'ltr'],
            'en-tl' => ['en-transliteration', ['النقحرة اللاتينية', 'Transliteration'], 'Transliteration', 'ltr'],
            'fr' => ['fr-hamidullah', ['حميد الله', 'Muhammad Hamidullah'], 'Muhammad Hamidullah', 'ltr'],
            'tr' => ['tr-diyanet', ['ديانت', 'Diyanet İşleri'], 'Diyanet İşleri', 'ltr'],
            'ur' => ['ur-junagarri', ['محمد جوناگڑھی', 'Muhammad Junagarhi'], 'Maulana Muhammad Junagarhi', 'rtl'],
        ];

        $order = 0;
        foreach (DB::table('quran_verse_translations')->distinct()->pluck('lang') as $lang) {
            [$slug, $names, $author, $dir] = $defaults[$lang]
                ?? ["default-{$lang}", ["Translation ({$lang})", "Translation ({$lang})"], null, 'ltr'];

            $resourceId = DB::table('quran_translation_resources')->insertGetId([
                'slug' => $slug,
                'name' => json_encode(['ar' => $names[0], 'en' => $names[1]], JSON_UNESCAPED_UNICODE),
                'author' => $author,
                'language' => $lang,
                'direction' => $dir,
                'source' => 'tanzil.net',
                'is_default' => true, // each pre-existing edition is the default for its lang
                'is_active' => true,
                'display_order' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('quran_verse_translations')->where('lang', $lang)->update(['resource_id' => $resourceId]);
        }

        // Swap the uniqueness constraint. Guarded to MySQL: SQLite (test driver)
        // can't reliably drop a named unique index, and correctness there is
        // enforced by the import/app layer (matches the tafsir migration).
        if (DB::getDriverName() === 'mysql') {
            Schema::table('quran_verse_translations', function (Blueprint $table) {
                $table->dropUnique(['verse_id', 'lang']);
                $table->unique(['verse_id', 'resource_id']);
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('quran_verse_translations', function (Blueprint $table) {
                $table->dropUnique(['verse_id', 'resource_id']);
                $table->unique(['verse_id', 'lang']);
            });
        }

        Schema::table('quran_verse_translations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resource_id');
        });
    }
};
