<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-manageable navigation items for the public site chrome.
 *
 * `location` partitions items into the header nav and the footer link groups
 * ('header' | 'footer_quick' | 'footer_modules' | 'footer_social').
 * Stored as a string (not a DB enum) so new locations don't require DDL and
 * the migration stays portable across drivers.
 *
 * Link target: `route_name` (resolved via route()) takes precedence over
 * `url` (absolute/external). Translatable label lives in menu_item_translations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('location', 32);
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('menu_items')
                ->nullOnDelete();
            $table->string('route_name')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // The public site always reads one location's active items in order.
            $table->index(['location', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
