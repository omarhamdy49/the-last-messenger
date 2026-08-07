<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The everyayah catalogue mixes Quran reciters with recited *translations*
     * (English/Persian/Urdu/Bosnian renderings, word-for-word teaching tracks).
     * Both are audio keyed by verse, so they share this table — `kind` is what
     * keeps translations out of the public qari picker.
     */
    public function up(): void
    {
        Schema::table('quran_reciters', function (Blueprint $table) {
            $table->string('kind', 20)->default('recitation')->after('slug');

            // Composite, not a lone index on `kind`: the column holds two values
            // across a table of tens of rows, so on its own it is too unselective
            // for the planner to use. Every public listing filters kind + is_active
            // and orders by display_order, which this covers end to end.
            $table->index(['kind', 'is_active', 'display_order'], 'quran_reciters_kind_active_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('quran_reciters', function (Blueprint $table) {
            $table->dropIndex('quran_reciters_kind_active_order_index');
            $table->dropColumn('kind');
        });
    }
};
