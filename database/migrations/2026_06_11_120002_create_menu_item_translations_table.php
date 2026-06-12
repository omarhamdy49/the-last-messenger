<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-locale label for menu items (mirrors the *_translations pattern).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->cascadeOnDelete();
            $table->string('locale', 5)->index();
            $table->string('label', 255)->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->unique(['menu_item_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_translations');
    }
};
