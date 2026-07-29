<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->withoutForeignKeys(function (): void {
            $this->dropHadithTables();
            $this->createTargetSchema();
        });
    }

    public function down(): void
    {
        $this->withoutForeignKeys(function (): void {
            $this->dropHadithTables();
            $this->createLegacySchema();
        });
    }

    private function createTargetSchema(): void
    {
        Schema::create('hadith_collections', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('abbreviation', 50)->nullable();
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index(['active', 'featured', 'display_order'], 'idx_hadith_collections_public_order');
        });

        Schema::create('hadith_books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained('hadith_collections')->restrictOnDelete();
            $table->string('book_number', 50);
            $table->json('title');
            $table->json('description')->nullable();
            $table->timestamps();
            $table->unique(['collection_id', 'book_number']);
        });

        Schema::create('hadith_chapters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->restrictOnDelete();
            $table->string('chapter_number', 50);
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['book_id', 'chapter_number']);
        });

        Schema::create('hadith_narrators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sahaba_id')->nullable()->unique()->constrained('sahaba')->nullOnDelete();
            $table->json('name');
            $table->json('full_name')->nullable();
            $table->json('kunya')->nullable();
            $table->json('biography')->nullable();
            $table->integer('birth_year_hijri')->nullable();
            $table->integer('death_year_hijri')->nullable();
            $table->string('reliability_grade', 100)->nullable();
            $table->string('school', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('hadith_hadiths', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chapter_id')->constrained('hadith_chapters')->restrictOnDelete();
            $table->foreignId('primary_narrator_id')->nullable()->constrained('hadith_narrators')->restrictOnDelete();
            $table->string('hadith_number', 50);
            $table->char('text_hash', 64);
            $table->timestamps();
            $table->index(['chapter_id', 'hadith_number']);
            $table->index('text_hash');
        });

        Schema::create('hadith_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->enum('locale', ['ar', 'en', 'fr', 'tr', 'ur']);
            $table->longText('text');
            $table->longText('search_text')->nullable();
            $table->string('translator_name')->nullable();
            $table->unsignedBigInteger('source_urn')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['hadith_id', 'locale']);
        });

        Schema::create('hadith_isnad', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('narrator_id')->constrained('hadith_narrators')->restrictOnDelete();
            $table->unsignedInteger('position_in_chain');
            $table->string('transmission_method', 100)->nullable();
            $table->timestamps();
            $table->unique(['hadith_id', 'position_in_chain']);
            $table->unique(['hadith_id', 'narrator_id']);
        });

        Schema::create('hadith_scholars', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->json('full_name')->nullable();
            $table->json('kunya')->nullable();
            $table->json('biography')->nullable();
            $table->string('school')->nullable();
            $table->integer('birth_year_hijri')->nullable();
            $table->integer('death_year_hijri')->nullable();
            $table->string('region')->nullable();
        });

        Schema::create('hadith_gradings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('scholar_id')->nullable()->constrained('hadith_scholars')->restrictOnDelete();
            $table->enum('grade_key', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown']);
            $table->string('raw_grade', 512);
            $table->text('notes')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('source_name')->nullable();
            $table->string('review_status', 50)->nullable();
            $table->date('evaluated_at')->nullable();
            $table->index(['grade_key', 'hadith_id']);
            $table->index(['hadith_id', 'scholar_id']);
        });

        Schema::create('hadith_commentaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('scholar_id')->nullable()->constrained('hadith_scholars')->restrictOnDelete();
            $table->enum('locale', ['ar', 'en', 'fr', 'tr', 'ur']);
            $table->string('author_name')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('review_status', 50)->nullable();
            $table->longText('text');
            $table->timestamps();
            $table->index(['hadith_id', 'locale']);
        });

        Schema::create('hadith_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->nullable()->unique();
            $table->json('name');
            $table->string('review_status', 50)->nullable();
            $table->string('source_reference', 500)->nullable();
        });

        Schema::create('hadith_hadith_themes', function (Blueprint $table): void {
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('hadith_theme_id')->constrained('hadith_themes')->restrictOnDelete();
            $table->primary(['hadith_id', 'hadith_theme_id']);
            $table->index(['hadith_theme_id', 'hadith_id']);
        });

        Schema::create('hadith_keywords', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->enum('locale', ['ar', 'en', 'fr', 'tr', 'ur']);
            $table->string('keyword', 100);
            $table->string('review_status', 50)->nullable();
            $table->string('source_reference', 500)->nullable();
            $table->unique(['hadith_id', 'locale', 'keyword']);
            $table->index(['locale', 'keyword', 'hadith_id']);
        });

        Schema::create('hadith_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->string('source_system', 50);
            $table->string('source_type', 50);
            $table->string('external_id', 100);
            $table->string('source_file')->nullable();
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->string('reference')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('verification_method', 100)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['source_system', 'external_id']);
        });

        Schema::create('hadith_related_verses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('quran_verse_id')->constrained('quran_verses')->restrictOnDelete();
            $table->json('relevance')->nullable();
            $table->string('review_status', 50)->nullable();
            $table->string('source_reference', 500)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
            $table->unique(['hadith_id', 'quran_verse_id']);
        });

        Schema::create('hadith_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->nullable()->constrained('hadith_collections')->restrictOnDelete();
            $table->string('source_collection_code', 100);
            $table->enum('type', ['json_import'])->default('json_import');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE hadith_translations ADD FULLTEXT INDEX ft_hadith_translations_text (`text`)');
            DB::statement('ALTER TABLE hadith_collections ADD CONSTRAINT chk_hadith_collections_code_lower CHECK (`code` = LOWER(`code`))');
            DB::statement('ALTER TABLE hadith_collections ADD CONSTRAINT chk_hadith_collections_slug_lower CHECK (`slug` = LOWER(`slug`))');
            DB::statement('ALTER TABLE hadith_narrators ADD CONSTRAINT chk_hadith_narrators_reliability_lower CHECK (`reliability_grade` IS NULL OR `reliability_grade` = LOWER(`reliability_grade`))');
            DB::statement('ALTER TABLE hadith_isnad ADD CONSTRAINT chk_hadith_isnad_position_positive CHECK (`position_in_chain` >= 1)');
            DB::statement('ALTER TABLE hadith_isnad ADD CONSTRAINT chk_hadith_isnad_method_lower CHECK (`transmission_method` IS NULL OR `transmission_method` = LOWER(`transmission_method`))');
            DB::statement('ALTER TABLE hadith_gradings ADD CONSTRAINT chk_hadith_gradings_review_lower CHECK (`review_status` IS NULL OR `review_status` REGEXP \'^[a-z][a-z0-9_]*$\')');
            DB::statement('ALTER TABLE hadith_commentaries ADD CONSTRAINT chk_hadith_commentaries_review_lower CHECK (`review_status` IS NULL OR `review_status` REGEXP \'^[a-z][a-z0-9_]*$\')');
            DB::statement('ALTER TABLE hadith_themes ADD CONSTRAINT chk_hadith_themes_slug_lower CHECK (`slug` IS NULL OR `slug` = LOWER(`slug`))');
            DB::statement('ALTER TABLE hadith_themes ADD CONSTRAINT chk_hadith_themes_review_lower CHECK (`review_status` IS NULL OR `review_status` REGEXP \'^[a-z][a-z0-9_]*$\')');
            DB::statement('ALTER TABLE hadith_keywords ADD CONSTRAINT chk_hadith_keywords_review_lower CHECK (`review_status` IS NULL OR `review_status` REGEXP \'^[a-z][a-z0-9_]*$\')');
            DB::statement('ALTER TABLE hadith_sources ADD CONSTRAINT chk_hadith_sources_system_lower CHECK (`source_system` = LOWER(`source_system`))');
            DB::statement('ALTER TABLE hadith_sources ADD CONSTRAINT chk_hadith_sources_type_lower CHECK (`source_type` = LOWER(`source_type`))');
            DB::statement('ALTER TABLE hadith_sources ADD CONSTRAINT chk_hadith_sources_verification_lower CHECK (`verification_method` IS NULL OR `verification_method` = LOWER(`verification_method`))');
            DB::statement('ALTER TABLE hadith_related_verses ADD CONSTRAINT chk_hadith_related_review_lower CHECK (`review_status` IS NULL OR `review_status` REGEXP \'^[a-z][a-z0-9_]*$\')');
            DB::statement('ALTER TABLE hadith_sync_logs ADD CONSTRAINT chk_hadith_sync_source_code_lower CHECK (`source_collection_code` = LOWER(`source_collection_code`))');
        }
    }

    private function createLegacySchema(): void
    {
        Schema::create('hadith_collections', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('slug_ar')->unique();
            $table->string('slug_en')->unique();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('abbreviation', 50)->nullable()->index();
            $table->string('canonical_code', 100)->nullable()->index();
            $table->string('source_slug', 100)->nullable()->index();
            $table->integer('total_hadiths')->default(0);
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index(['featured', 'display_order'], 'idx_hadith_collections_featured_order');
        });

        Schema::create('hadith_books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained('hadith_collections')->cascadeOnDelete();
            $table->string('book_number', 50)->nullable();
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->string('slug_ar')->nullable()->index();
            $table->string('slug_en')->nullable()->index();
            $table->string('chapter_title_ar')->nullable();
            $table->string('chapter_title_en')->nullable();
            $table->timestamps();
            $table->index(['collection_id', 'book_number'], 'idx_hadith_books_collection_book_number');
            $table->index(['collection_id', 'title_ar'], 'idx_hadith_book_collection_title');
        });

        Schema::create('hadith_chapters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->cascadeOnDelete();
            $table->string('chapter_number', 50)->nullable()->index();
            $table->json('name');
            $table->json('description')->nullable();
            $table->integer('display_order')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('hadith_companions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sahaba_id')->nullable()->unique()->constrained('sahaba')->nullOnDelete();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->text('full_name_ar')->nullable();
            $table->text('full_name_en')->nullable();
            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->integer('birth_year_hijri')->nullable();
            $table->integer('death_year_hijri')->nullable();
            $table->string('conversion_year')->nullable();
            $table->text('bio_ar')->nullable();
            $table->text('bio_en')->nullable();
            $table->text('virtues')->nullable();
            $table->boolean('verified')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('hadith_hadiths', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained('hadith_chapters')->nullOnDelete();
            $table->foreignId('companion_id')->nullable()->constrained('hadith_companions')->nullOnDelete();
            $table->string('local_source_id', 50)->nullable()->index();
            $table->string('source_file')->nullable()->index();
            $table->string('source_url', 500)->nullable();
            $table->unsignedBigInteger('sunnah_urn_ar')->nullable()->index();
            $table->unsignedBigInteger('sunnah_urn_en')->nullable()->index();
            $table->string('hadith_number', 50)->nullable();
            $table->char('text_hash', 64)->nullable()->index();
            $table->foreignId('merged_into_id')->nullable()->constrained('hadith_hadiths')->nullOnDelete();
            $table->string('grading_status', 512)->nullable()->index();
            $table->string('grading_agreement')->nullable();
            $table->text('significance_ar')->nullable();
            $table->text('significance_en')->nullable();
            $table->text('historical_context_ar')->nullable();
            $table->text('historical_context_en')->nullable();
            $table->string('verification_status', 50)->nullable()->index();
            $table->timestamp('source_verified_at')->nullable();
            $table->string('source_verification_method', 100)->nullable()->index();
            $table->text('uncertainty_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['book_id', 'hadith_number']);
        });

        Schema::create('hadith_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->string('language_code', 20);
            $table->longText('text');
            $table->string('translator_name')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->unsignedBigInteger('urn')->nullable()->index();
            $table->boolean('verified')->default(false)->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['hadith_id', 'language_code']);
        });

        Schema::create('hadith_narrators', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sahaba_id')->nullable()->unique()->constrained('sahaba')->nullOnDelete();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('full_name_ar', 500)->nullable();
            $table->string('full_name_en', 500)->nullable();
            $table->string('kunya')->nullable();
            $table->integer('birth_year_hijri')->nullable();
            $table->integer('death_year_hijri')->nullable()->index();
            $table->string('reliability_grade', 100)->nullable()->index();
            $table->text('biography')->nullable();
            $table->string('school', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('source_system', 50)->nullable();
            $table->string('source_narrator_id', 100)->nullable();
            $table->timestamps();
            $table->index(['source_system', 'source_narrator_id'], 'idx_hadith_narrators_source_identity');
        });

        Schema::create('hadith_isnad', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('narrator_id')->constrained('hadith_narrators')->cascadeOnDelete();
            $table->integer('position_in_chain')->index();
            $table->string('transmission_method', 100)->nullable();
            $table->timestamps();
            $table->unique(['hadith_id', 'narrator_id', 'position_in_chain']);
        });

        Schema::create('hadith_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('theme_en', 100)->nullable()->index();
            $table->string('theme_ar', 100)->nullable()->index();
            $table->string('review_status', 50)->nullable()->index();
            $table->string('source_reference', 500)->nullable();
        });

        Schema::create('hadith_hadith_themes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('hadith_theme_id')->constrained('hadith_themes')->cascadeOnDelete();
            $table->unique(['hadith_id', 'hadith_theme_id']);
        });

        Schema::create('hadith_keywords', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->string('keyword_ar', 100)->nullable()->index();
            $table->string('keyword_en', 100)->nullable()->index();
            $table->string('review_status', 50)->nullable()->index();
            $table->string('source_reference', 500)->nullable();
        });

        Schema::create('hadith_scholars', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('kunya')->nullable();
            $table->string('full_name')->nullable();
            $table->string('full_name_ar')->nullable();
            $table->string('full_name_en')->nullable();
            $table->string('school')->nullable()->index();
            $table->integer('birth_year_hijri')->nullable();
            $table->integer('death_year_hijri')->nullable()->index();
            $table->string('region')->nullable();
            $table->text('biography')->nullable();
        });

        Schema::create('hadith_grading_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('grade_ar', 512);
            $table->string('grade_en', 512)->unique();
            $table->text('description')->nullable();
            $table->integer('rank')->nullable();
        });

        Schema::create('hadith_gradings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('scholar_id')->constrained('hadith_scholars')->cascadeOnDelete();
            $table->foreignId('grading_level_id')->constrained('hadith_grading_levels')->cascadeOnDelete();
            $table->text('grading_notes')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('source_name')->nullable();
            $table->string('review_status', 50)->nullable()->index();
            $table->date('date_evaluated')->nullable();
            $table->unique(['hadith_id', 'scholar_id']);
        });

        Schema::create('hadith_commentaries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('scholar_id')->nullable()->constrained('hadith_scholars')->nullOnDelete();
            $table->string('language_code', 10)->index();
            $table->string('author')->nullable();
            $table->string('source')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->boolean('verified')->default(false)->index();
            $table->string('review_status', 50)->nullable()->index();
            $table->longText('commentary_text')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('hadith_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->string('local_source_id', 50)->nullable()->index();
            $table->string('source_file')->nullable()->index();
            $table->string('source_type', 50);
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('reference')->nullable();
            $table->string('url', 500)->nullable();
            $table->unsignedBigInteger('sunnah_urn')->nullable()->index();
            $table->string('verification_method', 100)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['hadith_id', 'source_file', 'url'], 'uniq_hadith_sources_trace');
        });

        Schema::create('hadith_related_verses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->unsignedBigInteger('quran_verse_id')->nullable()->index();
            $table->integer('surah');
            $table->string('surah_name_ar')->nullable();
            $table->string('surah_name_en')->nullable();
            $table->string('ayah', 50);
            $table->text('text_ar')->nullable();
            $table->text('text_en')->nullable();
            $table->text('relevance')->nullable();
            $table->string('review_status', 50)->nullable()->index();
            $table->string('source_reference', 500)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
            $table->index(['surah', 'ayah']);
        });

        Schema::create('hadith_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->string('category_ar')->nullable();
            $table->string('category_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->integer('display_order')->default(0);
            $table->string('review_status', 50)->nullable()->index();
            $table->string('source_reference', 500)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('hadith_fiqh_rulings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('scholar_id')->nullable()->constrained('hadith_scholars')->nullOnDelete();
            $table->text('principle');
            $table->string('language_code', 10);
            $table->integer('display_order')->default(0);
            $table->string('review_status', 50)->nullable()->index();
            $table->string('source_reference', 500)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('hadith_source_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_source_id')->constrained('hadith_sources')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->unique(['hadith_source_id', 'locale']);
        });

        Schema::create('hadith_theme_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_theme_id')->constrained('hadith_themes')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->unique(['hadith_theme_id', 'locale']);
        });

        Schema::create('hadith_chapter_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_chapter_id')->constrained('hadith_chapters')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
            $table->unique(['hadith_chapter_id', 'locale']);
        });

        Schema::create('hadith_sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('collection_name');
            $table->string('type', 50)->default('json_import');
            $table->string('status', 50)->default('pending');
            $table->json('metadata')->nullable();
            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE hadith_collections ADD FULLTEXT INDEX ft_hadith_collections_names (name_ar, name_en)');
            DB::statement('ALTER TABLE hadith_books ADD FULLTEXT INDEX ft_hadith_books_titles (title_ar, title_en, chapter_title_ar, chapter_title_en)');
            DB::statement('ALTER TABLE hadith_companions ADD FULLTEXT INDEX ft_companions_names (name_ar, name_en, title_ar, title_en)');
            DB::statement('ALTER TABLE hadith_translations ADD FULLTEXT INDEX ft_hadith_translations_text (`text`)');
            DB::statement('ALTER TABLE hadith_narrators ADD FULLTEXT INDEX ft_hadith_narrators_names (name_ar, name_en, full_name_ar, full_name_en, kunya)');
            DB::statement('ALTER TABLE hadith_themes ADD FULLTEXT INDEX ft_hadith_themes (theme_ar, theme_en)');
            DB::statement('ALTER TABLE hadith_keywords ADD FULLTEXT INDEX ft_hadith_keywords (keyword_ar, keyword_en)');
            DB::statement('ALTER TABLE hadith_scholars ADD FULLTEXT INDEX ft_hadith_scholars (name_ar, name_en, full_name)');
            DB::statement('ALTER TABLE hadith_commentaries ADD FULLTEXT INDEX ft_hadith_commentaries (commentary_text)');
            DB::statement('ALTER TABLE hadith_fiqh_rulings ADD FULLTEXT INDEX ft_hadith_fiqh_rulings (principle)');
            DB::statement('ALTER TABLE hadith_source_translations ADD FULLTEXT INDEX ft_hadith_source_translations (name, description)');
            DB::statement('ALTER TABLE hadith_theme_translations ADD FULLTEXT INDEX ft_hadith_theme_translations (name, description)');
            DB::statement('ALTER TABLE hadith_chapter_translations ADD FULLTEXT INDEX ft_hadith_chapter_translations (name, description)');
        }
    }

    private function dropHadithTables(): void
    {
        foreach ([
            'hadith_chapter_translations',
            'hadith_theme_translations',
            'hadith_source_translations',
            'hadith_fiqh_rulings',
            'hadith_applications',
            'hadith_related_verses',
            'hadith_sources',
            'hadith_keywords',
            'hadith_hadith_themes',
            'hadith_themes',
            'hadith_commentaries',
            'hadith_gradings',
            'hadith_grading_levels',
            'hadith_scholars',
            'hadith_isnad',
            'hadith_translations',
            'hadith_hadiths',
            'hadith_companions',
            'hadith_narrators',
            'hadith_chapters',
            'hadith_books',
            'hadith_sync_logs',
            'hadith_collections',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function withoutForeignKeys(callable $callback): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            $callback();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
