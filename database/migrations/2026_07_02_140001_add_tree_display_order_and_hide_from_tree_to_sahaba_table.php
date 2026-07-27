<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets admins curate which descendants (children/grandchildren) appear in
 * the public family tree, and in what order, without touching father_id/
 * mother_id. Descendants have no join-table row of their own (they're
 * identified purely by father_id/mother_id on this same table), so these
 * columns live directly on `sahaba` — one row per person, no ambiguity
 * about "which relationship" the flag/order applies to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sahaba', function (Blueprint $table): void {
            $table->unsignedInteger('tree_display_order')->nullable()->after('mother_id');
            $table->boolean('hide_from_tree')->default(false)->after('tree_display_order');
        });
    }

    public function down(): void
    {
        Schema::table('sahaba', function (Blueprint $table): void {
            $table->dropColumn(['tree_display_order', 'hide_from_tree']);
        });
    }
};
