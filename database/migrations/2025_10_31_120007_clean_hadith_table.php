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
        Schema::table('hadith', function (Blueprint $table) {
            // Remove urn, collection_name, book_number that came from old migrations
            if (Schema::hasColumn('hadith', 'urn')) {
                $table->dropUnique(['urn']);
                $table->dropColumn('urn');
            }

            if (Schema::hasColumn('hadith', 'collection_name')) {
                $table->dropIndex(['collection_name', 'hadith_number']);
                $table->dropColumn('collection_name');
            }

            if (Schema::hasColumn('hadith', 'book_number')) {
                $table->dropIndex(['book_id', 'book_number']);
                $table->dropColumn('book_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith', function (Blueprint $table) {
            $table->integer('urn')->nullable()->after('id');
            $table->string('collection_name')->nullable()->after('urn');
            $table->integer('book_number')->nullable()->after('collection_name');

            $table->unique('urn');
            $table->index(['collection_name', 'hadith_number']);
            $table->index(['book_id', 'book_number']);
        });
    }
};
