<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hero quick-search chips / admin-configurable trending terms
 * (الصحابة / المعارك / الأحاديث / السيرة).
 * Translatable label lives in quick_filter_chip_translations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_filter_chips', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            // Target search query term or a route/url the chip points to.
            $table->string('target_query')->nullable();
            $table->string('target_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_filter_chips');
    }
};
