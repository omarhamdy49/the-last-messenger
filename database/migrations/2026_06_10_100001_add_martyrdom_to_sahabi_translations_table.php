<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a structured "martyrdom" section to sahabi_translations.
 *
 * The public companion biography page (sahabaah-single prototype) renders
 * three structured sections — story, heroism, martyrdom. Story maps to
 * `biography`, heroism maps to `virtues`; martyrdom had no column. It is
 * nullable because only martyred companions have this section.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sahabi_translations', function (Blueprint $table) {
            $table->longText('martyrdom')->nullable()->after('famous_narrations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sahabi_translations', function (Blueprint $table) {
            $table->dropColumn('martyrdom');
        });
    }
};
