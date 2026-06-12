<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Homepage module-discovery cards (السيرة النبوية / الحديث الشريف / المرئيات ...).
 * Translatable title/description live in home_module_card_translations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_module_cards', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // stable identifier e.g. "seerah"
            $table->string('icon')->nullable();          // lucide icon name
            $table->string('image')->nullable();         // stored image path/url
            $table->string('target_url')->nullable();    // route name or absolute url
            $table->string('accent_color', 32)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_module_cards');
    }
};
