<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table) {
            // Add text_hash column for deduplication
            $table->string('text_hash', 64)->nullable()->after('hadith_number')->comment('SHA256 hash of Arabic text for deduplication');
            $table->index('text_hash');
            $table->unique(['book_id', 'hadith_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table) {
            $table->dropIndex(['book_id', 'hadith_number']);
            $table->dropIndex(['text_hash']);
            $table->dropColumn('text_hash');
        });
    }
};
