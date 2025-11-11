<?php

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
        Schema::table('hadith_sync_logs', function (Blueprint $table) {
            $table->json('checkpoint')->nullable()->after('error_details')->comment('Resume point for sync (collection, book, page)');
            $table->json('metadata')->nullable()->after('checkpoint')->comment('Additional sync metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith_sync_logs', function (Blueprint $table) {
            $table->dropColumn(['checkpoint', 'metadata']);
        });
    }
};
