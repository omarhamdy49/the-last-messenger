<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->dropIndex('hadith_collection_sunnah_urn_index');
            $table->dropIndex('hadith_collection_sunnah_urn_en_index');
            $table->unique(['collection_id', 'sunnah_urn'], 'hadith_collection_sunnah_urn_unique');
            $table->unique(['collection_id', 'sunnah_urn_en'], 'hadith_collection_sunnah_urn_en_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->dropUnique('hadith_collection_sunnah_urn_unique');
            $table->dropUnique('hadith_collection_sunnah_urn_en_unique');
            $table->index(['collection_id', 'sunnah_urn'], 'hadith_collection_sunnah_urn_index');
            $table->index(['collection_id', 'sunnah_urn_en'], 'hadith_collection_sunnah_urn_en_index');
        });
    }
};
