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
        Schema::table('hadith_hadiths', function (Blueprint $table) {
            // Track when a hadith is merged into another
            $table->foreignId('merged_into_id')
                ->nullable()
                ->after('text_hash')
                ->constrained('hadith_hadiths')
                ->onDelete('set null')
                ->comment('If set, this hadith was merged into the hadith with this ID');

            $table->index('merged_into_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table) {
            $table->dropForeignIdFor(relatedModel: \App\Domain\Hadith\Models\Hadith::class, column: 'merged_into_id');
            $table->dropIndex(['merged_into_id']);
            $table->dropColumn('merged_into_id');
        });
    }
};
