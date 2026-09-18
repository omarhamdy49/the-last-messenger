<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — the catalogue.
 *
 * quran_resources holds every QF resource kind (translation, tafsir, chapter_info,
 * verse_media, recitation, chapter_reciter, word_translation, word_transliteration)
 * plus manually added ones. QF resources carry `qf_id` (the sync `resource_id`);
 * manual ones do not. Reciter kinds may be backed by an external source
 * (`external_source` + `external_ref_id`) fetched on demand from the admin.
 *
 * quran_word_glosses is created here because it references quran_resources.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_languages', function (Blueprint $table): void {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('name', 60);
            $table->string('native_name', 60)->nullable();
            $table->string('iso_code', 10)->unique();
            $table->string('direction', 3)->default('ltr');
            $table->unsignedSmallInteger('translations_count')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('quran_resources', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 24);
            $table->unsignedInteger('qf_id')->nullable();
            $table->unsignedInteger('qf_content_id')->nullable();
            $table->string('source', 10)->default('manual');
            $table->string('external_source', 12)->nullable();
            $table->string('external_ref_id', 50)->nullable();
            $table->string('slug', 120);
            $table->string('name', 200);
            $table->string('author_name', 200)->nullable();
            $table->string('language_iso', 10)->nullable();
            $table->string('style', 20)->nullable();
            $table->string('qirat', 40)->nullable();
            $table->longText('info')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('sync_enabled')->default(false);
            $table->boolean('store_audio_locally')->default(false);
            $table->unsignedInteger('records_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_fetched_at')->nullable();
            $table->json('last_fetch_report')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['kind', 'qf_id']);
            $table->unique(['kind', 'slug']);
            $table->unique(['kind', 'external_source', 'external_ref_id'], 'qr_external_unique');
            $table->index(['kind', 'is_active', 'display_order']);
            $table->index(['kind', 'language_iso', 'is_default']);
        });

        Schema::create('quran_resource_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resource_id')->constrained('quran_resources')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['resource_id', 'locale']);
        });

        Schema::create('quran_word_glosses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('word_id');
            $table->foreignId('resource_id')->constrained('quran_resources')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('language_iso', 10);
            $table->string('text', 500);
            $table->unsignedTinyInteger('priority')->nullable();
            $table->unsignedBigInteger('qf_id')->nullable();
            $table->timestamps();

            $table->foreign('word_id')->references('id')->on('quran_words')->cascadeOnDelete();
            $table->unique(['word_id', 'resource_id']);
            $table->index(['resource_id', 'word_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_word_glosses');
        Schema::dropIfExists('quran_resource_translations');
        Schema::dropIfExists('quran_resources');
        Schema::dropIfExists('quran_languages');
    }
};
