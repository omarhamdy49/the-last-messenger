<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add curation flags to hadith_collections.
 *
 * The admin CollectionController (toggleFeatured / toggleActive / reorder), the
 * CollectionRepository, and the admin collections index view already reference
 * `featured`, `active`, and `display_order` — but the columns were never created,
 * leaving that curation feature broken. This migration adds them so the public
 * library can surface admin-curated featured collections.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_collections', function (Blueprint $table): void {
            $table->boolean('featured')->default(false)->after('total_hadiths')
                ->comment('Surface this collection in the public featured rail');
            $table->boolean('active')->default(true)->after('featured')
                ->comment('Whether the collection is publicly listed');
            $table->unsignedInteger('display_order')->default(0)->after('active')
                ->comment('Manual ordering weight (lower shows first)');

            // Hot path: public library orders featured-first then by display_order.
            $table->index(['featured', 'display_order'], 'idx_hadith_collections_featured_order');
        });
    }

    public function down(): void
    {
        Schema::table('hadith_collections', function (Blueprint $table): void {
            $table->dropIndex('idx_hadith_collections_featured_order');
            $table->dropColumn(['featured', 'active', 'display_order']);
        });
    }
};
