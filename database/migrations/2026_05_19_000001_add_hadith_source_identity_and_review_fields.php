<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_collections', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_collections', 'canonical_code')) {
                $table->string('canonical_code', 100)->nullable()->after('abbreviation');
                $table->index('canonical_code');
            }
            if (! Schema::hasColumn('hadith_collections', 'source_slug')) {
                $table->string('source_slug', 100)->nullable()->after('canonical_code');
                $table->index('source_slug');
            }
        });

        Schema::table('hadith_books', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_books', 'book_number')) {
                $table->string('book_number', 50)->nullable()->after('collection_id');
                $table->index(['collection_id', 'book_number'], 'idx_hadith_books_collection_book_number');
            }
        });

        Schema::table('hadith_hadiths', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_hadiths', 'local_source_id')) {
                $table->string('local_source_id', 50)->nullable()->after('book_id');
                $table->index('local_source_id');
            }
            if (! Schema::hasColumn('hadith_hadiths', 'source_file')) {
                $table->string('source_file', 255)->nullable()->after('local_source_id');
                $table->index('source_file');
            }
            if (! Schema::hasColumn('hadith_hadiths', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('source_file');
            }
            if (! Schema::hasColumn('hadith_hadiths', 'sunnah_urn_ar')) {
                $table->unsignedBigInteger('sunnah_urn_ar')->nullable()->after('source_url');
                $table->index('sunnah_urn_ar');
            }
            if (! Schema::hasColumn('hadith_hadiths', 'sunnah_urn_en')) {
                $table->unsignedBigInteger('sunnah_urn_en')->nullable()->after('sunnah_urn_ar');
                $table->index('sunnah_urn_en');
            }
            if (! Schema::hasColumn('hadith_hadiths', 'source_verified_at')) {
                $table->timestamp('source_verified_at')->nullable()->after('verification_status');
            }
            if (! Schema::hasColumn('hadith_hadiths', 'source_verification_method')) {
                $table->string('source_verification_method', 100)->nullable()->after('source_verified_at');
                $table->index('source_verification_method');
            }
        });

        Schema::table('hadith_translations', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_translations', 'translator_name')) {
                $table->string('translator_name', 255)->nullable()->after('text');
            }
            if (! Schema::hasColumn('hadith_translations', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('translator_name');
            }
            if (! Schema::hasColumn('hadith_translations', 'urn')) {
                $table->unsignedBigInteger('urn')->nullable()->after('source_url');
                $table->index('urn');
            }
            if (! Schema::hasColumn('hadith_translations', 'verified')) {
                $table->boolean('verified')->default(false)->after('urn');
                $table->index('verified');
            }
            if (! Schema::hasColumn('hadith_translations', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('verified');
                $table->index('is_primary');
            }
        });

        Schema::table('hadith_narrators', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_narrators', 'source_system')) {
                $table->string('source_system', 50)->nullable()->after('region');
            }
            if (! Schema::hasColumn('hadith_narrators', 'source_narrator_id')) {
                $table->string('source_narrator_id', 100)->nullable()->after('source_system');
                $table->index(['source_system', 'source_narrator_id'], 'idx_hadith_narrators_source_identity');
            }
        });

        Schema::table('hadith_scholars', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_scholars', 'full_name_ar')) {
                $table->string('full_name_ar', 255)->nullable()->after('full_name');
            }
            if (! Schema::hasColumn('hadith_scholars', 'full_name_en')) {
                $table->string('full_name_en', 255)->nullable()->after('full_name_ar');
            }
        });

        Schema::table('hadith_gradings', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_gradings', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('source_reference');
            }
            if (! Schema::hasColumn('hadith_gradings', 'source_name')) {
                $table->string('source_name', 255)->nullable()->after('source_url');
            }
            if (! Schema::hasColumn('hadith_gradings', 'review_status')) {
                $table->string('review_status', 50)->nullable()->after('source_name');
                $table->index('review_status');
            }
        });

        Schema::table('hadith_commentaries', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_commentaries', 'scholar_id')) {
                $table->foreignId('scholar_id')->nullable()->after('hadith_id')->constrained('hadith_scholars')->nullOnDelete();
            }
            if (! Schema::hasColumn('hadith_commentaries', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('source');
            }
            if (! Schema::hasColumn('hadith_commentaries', 'verified')) {
                $table->boolean('verified')->default(false)->after('source_url');
                $table->index('verified');
            }
            if (! Schema::hasColumn('hadith_commentaries', 'review_status')) {
                $table->string('review_status', 50)->nullable()->after('verified');
                $table->index('review_status');
            }
            if (! Schema::hasColumn('hadith_commentaries', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        Schema::table('hadith_sources', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_sources', 'local_source_id')) {
                $table->string('local_source_id', 50)->nullable()->after('hadith_id');
                $table->index('local_source_id');
            }
            if (! Schema::hasColumn('hadith_sources', 'source_file')) {
                $table->string('source_file', 255)->nullable()->after('local_source_id');
                $table->index('source_file');
            }
            if (! Schema::hasColumn('hadith_sources', 'sunnah_urn')) {
                $table->unsignedBigInteger('sunnah_urn')->nullable()->after('url');
                $table->index('sunnah_urn');
            }
            if (! Schema::hasColumn('hadith_sources', 'verification_method')) {
                $table->string('verification_method', 100)->nullable()->after('sunnah_urn');
            }
            if (! Schema::hasColumn('hadith_sources', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_method');
            }
        });

        Schema::table('hadith_keywords', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_keywords', 'review_status')) {
                $table->string('review_status', 50)->nullable()->after('keyword_en');
                $table->index('review_status');
            }
            if (! Schema::hasColumn('hadith_keywords', 'source_reference')) {
                $table->string('source_reference', 500)->nullable()->after('review_status');
            }
        });

        Schema::table('hadith_themes', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_themes', 'review_status')) {
                $table->string('review_status', 50)->nullable()->after('theme_ar');
                $table->index('review_status');
            }
            if (! Schema::hasColumn('hadith_themes', 'source_reference')) {
                $table->string('source_reference', 500)->nullable()->after('review_status');
            }
        });

        Schema::table('hadith_related_verses', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_related_verses', 'quran_verse_id')) {
                $table->unsignedBigInteger('quran_verse_id')->nullable()->after('hadith_id');
                $table->index('quran_verse_id');
            }
            if (! Schema::hasColumn('hadith_related_verses', 'review_status')) {
                $table->string('review_status', 50)->nullable()->after('relevance');
                $table->index('review_status');
            }
            if (! Schema::hasColumn('hadith_related_verses', 'source_reference')) {
                $table->string('source_reference', 500)->nullable()->after('review_status');
            }
            if (! Schema::hasColumn('hadith_related_verses', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('source_reference');
            }
        });

        Schema::table('hadith_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_applications', 'review_status')) {
                $table->string('review_status', 50)->nullable()->after('display_order');
                $table->index('review_status');
            }
            if (! Schema::hasColumn('hadith_applications', 'source_reference')) {
                $table->string('source_reference', 500)->nullable()->after('review_status');
            }
            if (! Schema::hasColumn('hadith_applications', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('source_reference');
            }
        });

        Schema::table('hadith_fiqh_rulings', function (Blueprint $table) {
            if (! Schema::hasColumn('hadith_fiqh_rulings', 'scholar_id')) {
                $table->foreignId('scholar_id')->nullable()->after('hadith_id')->constrained('hadith_scholars')->nullOnDelete();
            }
            if (! Schema::hasColumn('hadith_fiqh_rulings', 'review_status')) {
                $table->string('review_status', 50)->nullable()->after('display_order');
                $table->index('review_status');
            }
            if (! Schema::hasColumn('hadith_fiqh_rulings', 'source_reference')) {
                $table->string('source_reference', 500)->nullable()->after('review_status');
            }
            if (! Schema::hasColumn('hadith_fiqh_rulings', 'source_url')) {
                $table->string('source_url', 500)->nullable()->after('source_reference');
            }
        });

        $this->addUniqueIfMissing('hadith_sources', 'uniq_hadith_sources_trace', ['hadith_id', 'source_file', 'url']);
    }

    public function down(): void
    {
        // Intentionally conservative: this migration adds metadata/provenance
        // fields. Dropping them would discard audit data captured after import.
    }

    private function addUniqueIfMissing(string $table, string $index, array $columns): void
    {
        if (config('database.default') !== 'mysql') {
            return;
        }

        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();

        if (! $exists) {
            DB::statement(sprintf(
                'ALTER TABLE %s ADD UNIQUE INDEX %s (%s)',
                $table,
                $index,
                implode(', ', $columns),
            ));
        }
    }
};
