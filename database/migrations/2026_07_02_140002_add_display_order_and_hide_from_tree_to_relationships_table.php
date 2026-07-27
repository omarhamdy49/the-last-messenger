<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets admins curate which relationships (spouses, primarily) appear in the
 * public family tree, and in what order — mirrors the sahaba-table columns
 * added for descendants, but scoped to one `relationships` row since a
 * spouse edge already has a dedicated pivot row to carry it on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relationships', function (Blueprint $table): void {
            $table->unsignedInteger('display_order')->nullable()->after('notes');
            $table->boolean('hide_from_tree')->default(false)->after('display_order');
        });
    }

    public function down(): void
    {
        Schema::table('relationships', function (Blueprint $table): void {
            $table->dropColumn(['display_order', 'hide_from_tree']);
        });
    }
};
