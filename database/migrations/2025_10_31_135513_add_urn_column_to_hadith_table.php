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
            // Add URN column after id (if not exists)
            if (! Schema::hasColumn('hadith', 'urn')) {
                $table->string('urn')->nullable()->after('id')->comment('Unique Resource Number from Sunnah API');
                $table->unique('urn');
                $table->index('urn');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith', function (Blueprint $table) {
            if (Schema::hasColumn('hadith', 'urn')) {
                $table->dropUnique(['urn']);
                $table->dropIndex(['urn']);
                $table->dropColumn('urn');
            }
        });
    }
};
