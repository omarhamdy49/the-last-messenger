<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add API sync fields to hadith_books
        Schema::table('hadith_books', function (Blueprint $table) {
            $table->string('collection_name')->nullable()->after('slug')->comment('Sunnah.com collection name');
            $table->json('book_metadata')->nullable()->after('display_order')->comment('Metadata from Sunnah API');
            $table->timestamp('synced_at')->nullable()->after('updated_at')->comment('Last sync timestamp');

            // Indexes
            $table->index('collection_name');
            $table->index('synced_at');
        });

        // Add API sync fields to hadith_chapters
        Schema::table('hadith_chapters', function (Blueprint $table) {
            $table->string('external_chapter_id')->nullable()->after('chapter_number')->comment('Sunnah.com chapter ID');
            $table->integer('book_number')->nullable()->after('external_chapter_id')->comment('Book number from API');
            $table->timestamp('synced_at')->nullable()->after('updated_at')->comment('Last sync timestamp');

            // Indexes
            $table->index('external_chapter_id');
            $table->index(['book_id', 'book_number']);
            $table->index('synced_at');
        });

        // Add API sync fields to hadith
        Schema::table('hadith', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('id')->comment('Sunnah.com hadith ID');
            $table->integer('urn')->nullable()->after('external_id')->comment('Unique Resource Number from API');
            $table->string('collection_name')->nullable()->after('urn')->comment('Collection this hadith belongs to');
            $table->integer('book_number')->nullable()->after('collection_name')->comment('Book number in collection');
            $table->timestamp('synced_at')->nullable()->after('updated_at')->comment('Last sync timestamp');

            // Indexes
            $table->unique('external_id');
            $table->unique('urn');
            $table->index(['collection_name', 'hadith_number']);
            $table->index(['book_id', 'book_number']);
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith_books', function (Blueprint $table) {
            $table->dropIndex(['collection_name']);
            $table->dropIndex(['synced_at']);
            $table->dropColumn(['collection_name', 'book_metadata', 'synced_at']);
        });

        Schema::table('hadith_chapters', function (Blueprint $table) {
            $table->dropIndex(['external_chapter_id']);
            $table->dropIndex(['book_id', 'book_number']);
            $table->dropIndex(['synced_at']);
            $table->dropColumn(['external_chapter_id', 'book_number', 'synced_at']);
        });

        Schema::table('hadith', function (Blueprint $table) {
            $table->dropUnique(['external_id']);
            $table->dropUnique(['urn']);
            $table->dropIndex(['collection_name', 'hadith_number']);
            $table->dropIndex(['book_id', 'book_number']);
            $table->dropIndex(['synced_at']);
            $table->dropColumn(['external_id', 'urn', 'collection_name', 'book_number', 'synced_at']);
        });
    }
};
