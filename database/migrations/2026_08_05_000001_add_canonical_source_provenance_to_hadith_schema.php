<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_collections', function (Blueprint $table): void {
            $table->string('source_url', 500)->nullable();
            $table->string('about_url', 500)->nullable();
            $table->string('source_category', 50)->nullable();
            $table->json('source_metadata')->nullable();
        });

        Schema::table('hadith_books', function (Blueprint $table): void {
            $table->string('source_url', 500)->nullable();
            $table->json('source_metadata')->nullable();
        });

        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            // URNs are unique within a collection, not globally: the aggregate
            // `forty` source intentionally repeats nawawi40's source records.
            $table->dropUnique(['sunnah_urn']);
            $table->dropUnique(['sunnah_urn_en']);
            $table->index(['collection_id', 'sunnah_urn'], 'hadith_collection_sunnah_urn_index');
            $table->index(['collection_id', 'sunnah_urn_en'], 'hadith_collection_sunnah_urn_en_index');

            $table->string('source_container_id', 100)->nullable();
            $table->string('source_reference', 255)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->char('source_text_sha256', 64)->nullable();
            $table->json('source_metadata')->nullable();
            $table->json('source_unavailable_fields')->nullable();
        });

        Schema::create('hadith_narrators', function (Blueprint $table): void {
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('scholar_id')->constrained('hadith_scholars')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('display_name_ar', 500)->nullable();
            $table->json('source_metadata')->nullable();
            $table->timestamps();

            $table->primary(['hadith_id', 'position']);
            $table->unique(['hadith_id', 'scholar_id', 'position'], 'hadith_narrator_scholar_position_unique');
            $table->index(['scholar_id', 'hadith_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_narrators');

        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->dropIndex('hadith_collection_sunnah_urn_index');
            $table->dropIndex('hadith_collection_sunnah_urn_en_index');
            $table->dropColumn([
                'source_container_id', 'source_reference', 'canonical_url',
                'source_text_sha256', 'source_metadata', 'source_unavailable_fields',
            ]);
            $table->unique('sunnah_urn');
            $table->unique('sunnah_urn_en');
        });

        Schema::table('hadith_books', function (Blueprint $table): void {
            $table->dropColumn(['source_url', 'source_metadata']);
        });

        Schema::table('hadith_collections', function (Blueprint $table): void {
            $table->dropColumn(['source_url', 'about_url', 'source_category', 'source_metadata']);
        });
    }
};
